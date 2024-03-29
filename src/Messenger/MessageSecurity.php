<?php
namespace Coa\MessengerBundle\Messenger;

use Coa\MessengerBundle\Messenger\Message\DefaulfMessage;
use Coa\MessengerBundle\Messenger\Stamp\CoaDiscardStamp;
use Coa\MessengerBundle\Messenger\Stamp\CoaStamp;
use Coa\MessengerBundle\Messenger\Stamp\CoaWhoIsEchoStamp;
use Coa\MessengerBundle\Messenger\Stamp\CoaWhoIsRequestStamp;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * @author Zacharie Assagou <zacharie.assagou@coteouest.ci>
 */
class MessageSecurity{

    private ContainerBagInterface $container;
    private MessageBusInterface $bus;
    private Serializer $serializer;
    private SettingInterface $setting;
    private string $db_file;
    private string $key_file;

    /**
     * @param ContainerBagInterface $container
     * @param MessageBusInterface $bus
     */
    public function __construct(ContainerBagInterface $container, MessageBusInterface $bus){
        $this->container = $container;
        $this->bus = $bus;
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $this->serializer = new Serializer($normalizers, $encoders);

        $fs = new Filesystem();
        $folder = $this->container->get('kernel.project_dir')."/applog/broker";
        if(!$fs->exists($folder)){
            $fs->mkdir($folder);
        }

        $this->db_file = $folder."/coa_messenger.db";
        $this->key_file = $folder."/coa_messenger.key";
    }

    /**
     * @param Envelope $envelope
     * @return Envelope
     */
    public function addStamp(Envelope $envelope):Envelope{
        if (null === $envelope->last(CoaStamp::class)) {
            $message = $envelope->getMessage();
            $payload = $this->serializer->serialize($message, 'json');
            $setting = $this->getSetting();
            $payloadToken = hash_hmac("sha256",$payload,base64_decode($setting->getToken()));
            $envelope = $envelope->with(new CoaStamp($setting->getId(),$payloadToken));
        }
        return $envelope;
    }


    /**
     * @param Envelope $envelope
     * @return bool
     * @throws \Exception
     */
    public function verify(Envelope &$envelope): bool{
        /** @var CoaStamp $stamp */
        if (!($stamp = $envelope->last(CoaStamp::class))) {
            return false;
        }

        $payload = $this->serializer->serialize($envelope->getMessage(), 'json');
        $this->getSetting();

        $message = $envelope->getMessage();
        if($message instanceof DefaulfMessage){
            try {
                $this->handleWhoIsMessage($envelope);
            }
            catch (\Exception $e){

            }
        }

        $producers = $this->setting->getProducers();
        $producers[] = new Producer($this->setting->getId(),$this->setting->getToken());

        $producer = array_values(array_filter($producers,function (Producer $el) use(&$stamp){
            return ($el->getId() === $stamp->getProducerId());
        }));
        $producer = array_pop($producer);

        if(!isset($producer)){
            if($stamp->getProducerId() != $this->setting->getId()){
                if(!$envelope->last(CoaWhoIsRequestStamp::class) && !$envelope->last(CoaWhoIsEchoStamp::class)){
                    $howisrequest = new WhoIsRequest($stamp->getProducerId());
                    if(!$this->setting->hasWhoIsRequest($howisrequest)){
                        $this
                            ->setting
                            ->addWhoIsRequest($howisrequest)
                            ->addMessage($message)
                            ->save($this->db_file, $this->key_file)
                        ;

                        $this->bus->dispatch(new DefaulfMessage([
                            "action"=>"whois.req",
                            "payload"=>["id"=>$stamp->getProducerId()]
                        ]),[
                            new AmqpStamp('whois.req', AMQP_NOPARAM, [
                                "content_type"=>"application/json",
                                "delivery_mode"=>2,
                                "reply_to"=>$_ENV["RABBITMQ_OWN_QUEUE"],
                            ]),
                        ]);
                    }
                }
            }
            return false;
        }
        else{
            $token = hash_hmac("sha256",$payload,base64_decode($producer->getToken()));
            if(!hash_equals($token,$stamp->getPayloadToken())){
                // on doit redemander les credentials du producer
                $reason = 'Invalid x-coa-stamp header value';
                $envelope = $envelope->with(new CoaDiscardStamp($reason));
                throw new MessageDecodingFailedException($reason);
                //throw new \Exception("impossible de traiter ce message 3");
            }
        }
        return true;
    }


    /**
     * @return Setting
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function getSetting() :Setting {

        $this
            ->createSettingFile()
            ->checkSettingFileIntegrity()
        ;
        $this->setting = Setting::loadData($this->db_file,$this->key_file);
        return $this->setting;
    }

    public function createSettingFile() :self {
        $this->setting = Setting::create($this->db_file,$this->key_file);
        return $this;
    }

    public function checkSettingFileIntegrity(): self{
        Setting::checkIntegrity($this->db_file,$this->key_file);
        return $this;
    }

    public function handleWhoIsMessage(Envelope &$envelope){
        /** @var CoaStamp $stamp */
        $stamp = $envelope->last(CoaStamp::class);
        $message = $envelope->getMessage();

        switch ($message->getAction()){
            case "whois.req":
                // empecher de s'envoyer soi meme un whois
                if($stamp->getProducerId() == $this->setting->getId()){
                    $reason = "Got whois.req, i'am the sender, nothing to be done";
                    $envelope = $envelope->with(new CoaDiscardStamp($reason));
                    throw new MessageDecodingFailedException($reason);
                }

                if($message->getPayload()["id"] != "*"){ // broadcast whois.req
                    if(@$message->getPayload()["id"] != $this->setting->getId()){
                        $reason = "Got whois.req it's not me";
                        $envelope = $envelope->with(new CoaDiscardStamp($reason));
                        throw new MessageDecodingFailedException($reason);
                    }
                }
                $envelope = $envelope->with(new CoaWhoIsRequestStamp($stamp->getProducerId(),$this->setting->getId()));

                // ce producer n'est pas deja dans notre base de données
                if(!($producer = $this->setting->getProducer($stamp->getProducerId()))){
                    $howisrequest = new WhoIsRequest($stamp->getProducerId());

                    // ce producer n'est pas dans la table whoisRequest avec l'état pending
                    // il faut l'enregistrer et envoyer une requeste whois.req
                    if(!$this->setting->hasWhoIsRequest($howisrequest)){
                        $this
                            ->setting
                            ->addWhoIsRequest($howisrequest)
                            ->save($this->db_file, $this->key_file)
                        ;

                        $this->bus->dispatch(new DefaulfMessage([
                            "action"=>"whois.req",
                            "payload"=>["id"=>$stamp->getProducerId()]
                        ]),[
                            new AmqpStamp('whois.req', AMQP_NOPARAM, [
                                "content_type"=>"application/json",
                                "delivery_mode"=>2,
                                "reply_to"=>$_ENV["RABBITMQ_OWN_QUEUE"],
                            ]),
                        ]);
                    }
                }

                // on envoi directement un echo
                // position bas
                $this->bus->dispatch(new DefaulfMessage([
                    "action"=>"whois.echo",
                    "payload"=>["token"=>$this->setting->getToken(),"id"=>$this->setting->getId()]
                ]),[
                    new AmqpStamp('whois.echo', AMQP_NOPARAM, [
                        "content_type"=>"application/json",
                        "delivery_mode"=>2,
                        "reply_to"=>$_ENV["RABBITMQ_OWN_QUEUE"],
                    ]),
                ]);
                $envelope = $envelope->with(new CoaDiscardStamp(""));
                break;

            case "whois.echo":

                // empecher de s'envoyer soi meme un whois
                if($stamp->getProducerId() == $this->setting->getId()){
                    $reason = "Got whois.echo, i'am the sender, nothing to be done";
                    $envelope = $envelope->with(new CoaDiscardStamp($reason));
                    throw new MessageDecodingFailedException($reason);
                }

                if(!isset($message->getPayload()["token"])){
                    $reason = "Got whois.echo but does not contain producer credentials";
                    $envelope = $envelope->with(new CoaDiscardStamp($reason));
                    throw new MessageDecodingFailedException($reason);
                }

                // on doit verifier si le client local a effectué une demande auparavant
                $howisrequest = new WhoIsRequest($message->getPayload()["id"]);

                if(!$this->setting->hasWhoIsRequest($howisrequest)){
                    $reason = "Got whois.echo but local producer did not request whois.req";
                    $envelope = $envelope->with(new CoaDiscardStamp($reason));
                    throw new MessageDecodingFailedException($reason);
                }
                $envelope = $envelope->with(new CoaWhoIsEchoStamp($stamp->getProducerId(),$this->setting->getId()));

                if(!($producer = $this->setting->getProducer($stamp->getProducerId()))){
                    $this
                        ->setting
                        ->addProducer(new Producer($stamp->getProducerId(),$message->getPayload()["token"]))
                        ;
                }
                $this
                    ->setting
                    ->removeWhoIsRequest($howisrequest)
                    ->save($this->db_file,$this->key_file)
                ;
                $envelope = $envelope->with(new CoaDiscardStamp(""));
                break;
        }
    }
}


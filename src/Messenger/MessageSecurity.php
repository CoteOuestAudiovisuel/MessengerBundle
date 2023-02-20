<?php
namespace Coa\MessengerBundle\Messenger;

use Coa\MessengerBundle\Messenger\Message\DefaulfMessage;
use Coa\MessengerBundle\Messenger\Stamp\CoaStamp;
use Coa\MessengerBundle\Messenger\Stamp\CoaWhoIsEchoStamp;
use Coa\MessengerBundle\Messenger\Stamp\CoaWhoIsRequestStamp;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
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

        $this->db_file = $this->container->get('kernel.project_dir')."/applog/broker-messaging.db";
        $this->key_file = $this->container->get('kernel.project_dir')."/applog/broker-messaging.key";
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
            $this->handleBultinMessage($this->setting,$envelope);
        }

        $producers = array_map(function ($el){
            return new Producer($el["id"],$el["token"]);
        },$this->setting->getProducers());

        $producers[] = new Producer($this->setting->getId(),$this->setting->getToken());

        $producer = array_values(array_filter($producers,function (Producer $el) use(&$stamp){
            return ($el->getId() === $stamp->getProducerId());
        }));
        $producer = array_pop($producer);

        if(!isset($producer)){
            // ce producer n'est pas connu dans la base de données local
            // on doit demander les credentials du producer
            $howisrequest = new WhoIsRequest($stamp->getProducerId());
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
                    ]),
                ]);
            }

            if($envelope->last(CoaWhoIsRequestStamp::class)){
                throw new MessageDecodingFailedException('Already sent whois.req for client: '.$howisrequest->getId());
            }

            throw new \Exception("impossible de traiter ce message 2");
        }

        $token = hash_hmac("sha256",$payload,base64_decode($producer->getToken()));
        if(!hash_equals($token,$stamp->getPayloadToken())){
            // on doit redemander les credentials du producer
            throw new MessageDecodingFailedException('Invalid x-coa-stamp header value');
            //throw new \Exception("impossible de traiter ce message 3");
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

    public function handleBultinMessage(Setting &$setting, Envelope &$envelope){
        /** @var CoaStamp $stamp */
        $stamp = $envelope->last(CoaStamp::class);
        $message = $envelope->getMessage();

        switch ($message->getAction()){
            case "whois.req":

                if($message->getPayload()["id"] != "*"){ // broadcast whois.req
                    if(@$message->getPayload()["id"] != $setting->getId()){
                        throw new MessageDecodingFailedException("Got whois.req it's not me");
                    }
                }

                $envelope = $envelope->with(new CoaWhoIsRequestStamp($stamp->getProducerId(),$setting->getId()));

                $this->bus->dispatch(new DefaulfMessage([
                    "action"=>"whois.echo",
                    "payload"=>["token"=>$setting->getToken(),"id"=>$setting->getId()]
                ]),[
                    new AmqpStamp('whois.echo', AMQP_NOPARAM, [
                        "content_type"=>"application/json",
                        "delivery_mode"=>2,
                    ]),
                ]);
                break;

            case "whois.echo":

                if(!isset($message->getPayload()["token"])){
                    throw new MessageDecodingFailedException("Got whois.echo but does not contain producer credentials");
                }

                // on doit verifier si le client local a effectué une demande auparavant
                $howisrequest = new WhoIsRequest($message->getPayload()["id"]);
                if(!$this->setting->hasWhoIsRequest($howisrequest)){
                    throw new MessageDecodingFailedException("Got whois.echo but local producer did not request whois.req");
                }

                $envelope = $envelope->with(new CoaWhoIsEchoStamp($stamp->getProducerId(),$setting->getId()));

                $this
                    ->setting
                    ->removeWhoIsRequest($howisrequest)
                    ->save($this->db_file,$this->key_file)
                    ;

                $producer = new Producer($stamp->getProducerId(),$message->getPayload()["token"]);
                $setting
                    ->addProducer($producer)
                    ->save($this->db_file,$this->key_file)
                ;
                break;
        }
    }
}


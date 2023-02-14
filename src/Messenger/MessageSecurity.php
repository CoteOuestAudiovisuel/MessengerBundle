<?php
namespace Coa\MessengerBundle\Messenger;

use Coa\MessengerBundle\Messenger\Message\DefaulfMessage;
use Coa\MessengerBundle\Messenger\Stamp\CoaStamp;
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
    }

    /**
     * @param Envelope $envelope
     * @return Envelope
     */
    public function addStamp(Envelope $envelope):Envelope{
        if (null === $envelope->last(CoaStamp::class)) {
            $message = $envelope->getMessage();
            $payload = $this->serializer->serialize($message, 'json');
            $settings = $this->getSettings();
            $producerId = $settings["id"];
            $payloadToken = hash_hmac("sha256",$payload,base64_decode($settings["token"]));
            $envelope = $envelope->with(new CoaStamp($producerId,$payloadToken));
        }
        return $envelope;
    }

    /**
     * @param Envelope $envelope
     * @return bool
     * @throws \Exception
     */
    public function verify(Envelope $envelope): bool{
        if (!($stamp = $envelope->last(CoaStamp::class))) {
            return false;
        }

        $payload = $this->serializer->serialize($envelope->getMessage(), 'json');
        $settings = $this->getSettings();
        $settings["producers"][] = [
            "id"=>  $settings["id"],
            "token"=>  $settings["token"],
        ];
        $producer = array_values(array_filter($settings["producers"],function (array $el) use(&$stamp){
            return ($el["id"] === $stamp->getProducerId());
        }));
        $producer = array_pop($producer);

        if(!isset($producer)){
            // ce producer n'est pas connu dans la base de données local
            // on doit demander les credentials du producer
            $this->bus->dispatch(new DefaulfMessage([
                "action"=>"whois.req",
                "payload"=>$stamp->getProducerId()
            ]),[
                new AmqpStamp('whois.req', AMQP_NOPARAM, [
                    "content_type"=>"application/json",
                    "delivery_mode"=>2,
                ]),
            ]);
            throw new \Exception("impossible de traiter ce message 2");
        }

        $token = hash_hmac("sha256",$payload,base64_decode($producer["token"]));
        if(!hash_equals($token,$stamp->getPayloadToken())){
            // on doit redemander les credentials du producer
            throw new MessageDecodingFailedException('Invalid x-coa-stamp header value');
            //throw new \Exception("impossible de traiter ce message 3");
        }
        return true;
    }

    /**
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function getSettings() :array {
        $filename = $this->container->get('kernel.project_dir')."/applog/broker-messaging.db";
        $key_file = $this->container->get('kernel.project_dir')."/applog/broker-messaging.key";

        if(!file_exists($filename)){

            $key = hash("sha256",openssl_random_pseudo_bytes(16));
            $filename_data = [
                "id"=>base64_encode(openssl_random_pseudo_bytes(16,$ok)),
                "token"=>base64_encode(openssl_random_pseudo_bytes(32,$ok)),
                "producers"=>[]
            ];
            $filename_data = @openssl_encrypt(json_encode($filename_data),"aes-256-cbc",$key,0);
            file_put_contents($filename,$filename_data);
            file_put_contents($key_file,$key);
        }
        $key_data = file_get_contents($key_file);
        $broker_data = file_get_contents($filename);
        $settings = json_decode(openssl_decrypt($broker_data,"aes-256-cbc",$key_data,0),true);
        return $settings;
    }
}


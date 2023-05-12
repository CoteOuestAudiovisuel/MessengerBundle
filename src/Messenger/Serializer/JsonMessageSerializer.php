<?php
namespace Coa\MessengerBundle\Messenger\Serializer;

use Coa\MessengerBundle\Messenger\Message\AwsSqsNativeMessage;
use Coa\MessengerBundle\Messenger\Message\AwsSqsMessage;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;


/**
 * Event is dispatched before a message is sent to the transport.
 *
 * The event is *only* dispatched if the message will actually
 * be sent to at least one transport. If the message is sent
 * to multiple transports, the message is dispatched only one time.
 * This message is only dispatched the first time a message
 * is sent to a transport, not also if it is retried.
 *
 * @author Zacharie Assagou <zacharie.assagou@coteouest.ci>
 */
class JsonMessageSerializer implements SerializerInterface {
    protected Serializer $serializer;

    public function decode(array $encodedEnvelope): Envelope {
        $body = $encodedEnvelope['body'];
        $headers = $encodedEnvelope['headers'];
        $data = json_decode($body, true);
        $stamps = [];

        dump($data);

        $keys = array_keys($data);
        if(count(array_diff($keys,explode(" ",AwsSqsNativeMessage::KEYS))) <= 2){
            $message = new AwsSqsNativeMessage($data);
        }
        else{
            $message = new AwsSqsMessage($data);
        }

        return new Envelope($message, $stamps);
    }

    public function encode(Envelope $envelope): array{
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        $message = $envelope->getMessage();
        $data = $serializer->serialize($message, 'json');
        $allStamps = [];
        $headers = ["type"=>get_class($message)];

        return [
            'body' => $data,
            'headers' => $headers,
        ];
    }
}

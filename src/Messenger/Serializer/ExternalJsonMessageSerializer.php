<?php
namespace Coa\MessengerBundle\Messenger\Serializer;

use Coa\MessengerBundle\Messenger\Message\DefaulfMessage;
use Coa\MessengerBundle\Messenger\Stamp\CoaStamp;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
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
class ExternalJsonMessageSerializer implements SerializerInterface {
    protected Serializer $serializer;

    public function decode(array $encodedEnvelope): Envelope {
        $body = $encodedEnvelope['body'];
        $headers = $encodedEnvelope['headers'];
        $data = json_decode($body, true);
        $stamps = [];

        if(!isset($headers["X-Coa-Stamp"])){
            throw new MessageDecodingFailedException('Missing "X-Coa-Stamp" header');
            //throw new \Exception("impossible de traiter ce message 1");
        }
        $coaStampData = json_decode($headers["X-Coa-Stamp"],true);
        $stamps[] = new CoaStamp($coaStampData["producerId"],$coaStampData["payloadToken"]);

        if(@$headers["type"] && class_exists($headers["type"])){
            $header_type = $headers["type"];
            $message = new $header_type($data);
        }
        else{
            $message = new DefaulfMessage($data);
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

        if(($stamp = $envelope->last(CoaStamp::class))){
            $headers["X-Coa-Stamp"] = $serializer->serialize($stamp, 'json');
        }
        if(($stamp = $envelope->all(BusNameStamp::class))){
            $headers["X-Message-Stamp-Symfony\Component\Messenger\Stamp\BusNameStamp"] = $serializer->serialize($stamp, 'json');
        }

//        foreach ($envelope->all() as $stamps) {
//            $allStamps = array_merge($allStamps, $stamps);
//        }

        return [
            'body' => $data,
            'headers' => $headers,
        ];
    }
}

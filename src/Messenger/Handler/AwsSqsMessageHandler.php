<?php
namespace Coa\MessengerBundle\Messenger\Handler;
use Coa\MessengerBundle\Messenger\Message\AwsSqsMessage;
use Coa\MessengerBundle\Messenger\Message\AwsSqsNativeMessage;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;


/**
 * @author Zacharie Assagou <zacharie.assagou@coteouest.ci>
 */
class AwsSqsMessageHandler implements MessageHandlerInterface{

    private ContainerBagInterface $container;
    private HandlerManager $handlerManager;

    public function __construct(ContainerBagInterface $container, HandlerManager $handlerManager){
        $this->container = $container;
        $this->handlerManager = $handlerManager;
    }

    public function log(AwsSqsMessage $message){
        $encoders = [new JsonEncoder()];
        $normalizers = [new ObjectNormalizer()];
        $serializer = new Serializer($normalizers, $encoders);

        $date = (new \DateTime())->format("Y-m-d");
        $fs = new Filesystem();
        $folder = $this->container->get('kernel.project_dir')."/applog/broker/log";
        if(!$fs->exists($folder)){
            $fs->mkdir($folder);
        }
        $log_file = $folder."/$date.log";
        $data = $serializer->serialize($message, 'json');
        $fs->appendToFile($log_file, $data."\n");
    }

    public function __invoke(AwsSqsMessage $message){
        $this->log($message);

        $payload = $message->getPayload();
        $action = $message->getAction();
        $this->handlerManager->run($action,$payload);
    }
}
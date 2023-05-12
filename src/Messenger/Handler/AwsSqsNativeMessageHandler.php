<?php
namespace Coa\MessengerBundle\Messenger\Handler;
use Coa\MessengerBundle\Messenger\Message\AwsSqsNativeMessage;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;


/**
 * @author Zacharie Assagou <zacharie.assagou@coteouest.ci>
 */
class AwsSqsNativeMessageHandler implements MessageHandlerInterface{

    private ContainerBagInterface $container;
    private EventDispatcherInterface $dispatcher;
    private HandlerManager $handlerManager;

    public function __construct(ContainerBagInterface $container,EventDispatcherInterface $dispatcher,HandlerManager $handlerManager){
        $this->container = $container;
        $this->dispatcher = $dispatcher;
        $this->handlerManager = $handlerManager;
    }

    public function log(AwsSqsNativeMessage $message){
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

    public function __invoke(AwsSqsNativeMessage $message){
        $this->log($message);

        switch ($message->getSource()){
            case "aws.mediaconvert":
                $detail = $message->getDetail();
                $metadata = $detail["userMetadata"];
                if(!isset($metadata["code"]) || !isset($metadata["application"])) return;

                $jobId = $detail["jobId"];
                $code = $metadata["code"];
                $bucket = $metadata["bucket"];
                $fileSize = $metadata["fsize"];
                $originalFilename = $metadata["fname"];
                $region = $metadata["region"];
                $source_key = $metadata["source_key"];

                $detailStatus = strtoupper($detail["status"]);

                $payload = [
                    "jobId"=>$jobId,
                    "code"=>$code,
                    "bucket"=>$bucket,
                    "fileSize"=>$fileSize,
                    "originalFilename"=>$originalFilename,
                    "region"=>$region,
                    "source_key"=>$source_key
                ];

                switch($detailStatus){
                    case "PROGRESSING":
                        $action = "mc.transcoding.submitted";
                    break;

                    case "STATUS_UPDATE":
                        $action = "mc.transcoding.progressing";
                        if($detailStatus == "STATUS_UPDATE"){
                            $jobProgress = $detail["jobProgress"];
                            $payload["jobPercent"] = $jobProgress["jobPercentComplete"];
                            $payload["currentPhase"] = $jobProgress["currentPhase"];
                        }
                    break;

                    case "COMPLETE":
                    case "ERROR":
                    case "CANCELED":
                    case "SUBMITTED":
                        $action = "mc.transcoding." . strtolower($detailStatus);
                    break;
                }
                $this->handlerManager->run($action,$payload);
            break;
        }
    }
}
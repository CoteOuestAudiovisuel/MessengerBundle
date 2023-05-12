<?php
namespace Coa\MessengerBundle\Messenger\Handler;
use Coa\MessengerBundle\Event\IncomingSqsMessageEvent;
use Coa\MessengerBundle\Messenger\Message\AwsSqsNativeMessage;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;



/**
 * @author Zacharie Assagou <zacharie.assagou@coteouest.ci>
 */
class AwsSqsNativeMessageHandler implements MessageHandlerInterface{

    private EventDispatcherInterface $dispatcher;
    private HandlerManager $handlerManager;

    public function __construct(EventDispatcherInterface $dispatcher,HandlerManager $handlerManager){
        $this->dispatcher = $dispatcher;
        $this->handlerManager = $handlerManager;
    }


    public function __invoke(AwsSqsNativeMessage $message){

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
        $event = new IncomingSqsMessageEvent($message);
        $this->dispatcher->dispatch($event);
    }
}
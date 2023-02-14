<?php
namespace Coa\MessengerBundle\Messenger\Handler;
use Coa\MessengerBundle\Messenger\Message\DefaulfMessage;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;


/**
 * @author Zacharie Assagou <zacharie.assagou@coteouest.ci>
 */
class DefaultHandler implements MessageHandlerInterface{

    private ContainerBagInterface $containerBag;
    private HandlerManager $handlerManager;

    public function __construct(ContainerBagInterface $containerBag, HandlerManager $handlerManager){
        $this->containerBag = $containerBag;
        $this->handlerManager = $handlerManager;
    }

    public function __invoke(DefaulfMessage $item){
        $payload = $item->getPayload();
        $action = $item->getAction();
        $filename = $this->containerBag->get('kernel.project_dir')."/applog/broker-log.txt";
        file_put_contents($filename,json_encode(["action"=>$action,"payload"=>$payload])."\n", FILE_APPEND);
        $this->handlerManager->run($action,$payload);
    }
}
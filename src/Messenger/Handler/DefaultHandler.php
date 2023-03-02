<?php
namespace Coa\MessengerBundle\Messenger\Handler;
use Coa\MessengerBundle\Messenger\Message\DefaulfMessage;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;


/**
 * @author Zacharie Assagou <zacharie.assagou@coteouest.ci>
 */
class DefaultHandler implements MessageHandlerInterface{

    private ContainerBagInterface $container;
    private HandlerManager $handlerManager;

    public function __construct(ContainerBagInterface $container, HandlerManager $handlerManager){
        $this->container = $container;
        $this->handlerManager = $handlerManager;
    }

    public function __invoke(DefaulfMessage $item){
        $payload = $item->getPayload();
        $action = $item->getAction();
        $date = (new \DateTime())->format("Y-m-d");
        $fs = new Filesystem();
        $folder = $this->container->get('kernel.project_dir')."/applog/broker/log";
        if(!$fs->exists($folder)){
            $fs->mkdir($folder);
        }
        $log_file = $folder."/$date.log";
        $fs->appendToFile($log_file, json_encode(["action"=>$action,"payload"=>$payload])."\n");
        $this->handlerManager->run($action,$payload);
    }
}
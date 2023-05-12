<?php
namespace Coa\MessengerBundle\Messenger\Handler;
use Coa\MessengerBundle\Event\IncomingSqsMessageEvent;
use Coa\MessengerBundle\Messenger\Message\AwsSqsMessage;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;



/**
 * @author Zacharie Assagou <zacharie.assagou@coteouest.ci>
 */
class AwsSqsMessageHandler implements MessageHandlerInterface{

    private HandlerManager $handlerManager;
    private EventDispatcherInterface $dispatcher;

    public function __construct(HandlerManager $handlerManager, EventDispatcherInterface $dispatcher){
        $this->handlerManager = $handlerManager;
        $this->dispatcher = $dispatcher;
    }

    public function __invoke(AwsSqsMessage $message){

        $payload = $message->getPayload();
        $action = $message->getAction();
        $this->handlerManager->run($action,$payload);

        $event = new IncomingSqsMessageEvent($message);
        $this->dispatcher->dispatch($event);
    }
}
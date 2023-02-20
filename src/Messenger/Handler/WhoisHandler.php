<?php
namespace Coa\MessengerBundle\Messenger\Handler;
use Coa\MessengerBundle\Messenger\Message\DefaulfMessage;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Messenger\Bridge\Amqp\Transport\AmqpStamp;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @author Zacharie Assagou <zacharie.assagou@coteouest.ci>
 */
class WhoisHandler extends Handler {

    private ContainerBagInterface $container;
    private MessageBusInterface $bus;

    /**
     * @param ContainerBagInterface $container
     */
    public function __construct(ContainerBagInterface $container, MessageBusInterface $bus){
        parent::__construct("whois\..+");
        $this->container = $container;
        $this->bus = $bus;
    }

    /**
     * @param array $payload
     * @return mixed
     */
    protected function run(string $bindingKey, array $payload){
        switch ($bindingKey){
            case "whois.req":

                break;

            case "whois.echo":

                break;
        }
    }
}
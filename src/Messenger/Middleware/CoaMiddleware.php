<?php
namespace Coa\MessengerBundle\Messenger\Middleware;
use Coa\MessengerBundle\Messenger\MessageSecurity;
use Coa\MessengerBundle\Messenger\Stamp\CoaDiscardStamp;
use Coa\MessengerBundle\Messenger\Stamp\CoaStamp;
use Coa\MessengerBundle\Messenger\Stamp\CoaWhoIsEchoStamp;
use Coa\MessengerBundle\Messenger\Stamp\CoaWhoIsRequestStamp;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Stamp\SentStamp;


/**
 * @author Zacharie Assagou <zacharie.assagou@coteouest.ci>
 */
class CoaMiddleware implements MiddlewareInterface{

    private MessageSecurity $messageSecurity;

    public function __construct(MessageSecurity $messageSecurity){
        $this->messageSecurity = $messageSecurity;
    }
    public function handle(Envelope $envelope, StackInterface $stack): Envelope{

        if (!$envelope->last(CoaStamp::class)){
            $envelope = $this->messageSecurity->addStamp($envelope);
        }

        // message vient d'etre recu
        // on procede à la verification de l'entête
        if ($envelope->last(ReceivedStamp::class)) {
            //$this->logger->info('[{id}] Received & handling {class}', $context);
            $this->messageSecurity->verify($envelope);
        }
        // il faut jeter ce message à la poubelle
        if($envelope->last(CoaDiscardStamp::class)){
            return $envelope;
        }

        $envelope = $stack->next()->handle($envelope, $stack);

        // message envoyé
        if ($envelope->last(SentStamp::class)) {
            //$this->logger->info('[{id}] Sent {class}', $context);
        }
        // message pas encore envoyé
        else {
            //$this->logger->info('[{id}] Handling or sending {class}', $context);

        }
        return $envelope;
    }
}
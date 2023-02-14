<?php
namespace Coa\MessengerBundle\Messenger\Handler;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

/**
 * @author Zacharie Assagou <zacharie.assagou@coteouest.ci>
 */
class WhoisHandler extends Handler {

    private ContainerBagInterface $container;

    /**
     * @param ContainerBagInterface $container
     */
    public function __construct(ContainerBagInterface $container){
        parent::__construct("whois.*");
        $this->container = $container;
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
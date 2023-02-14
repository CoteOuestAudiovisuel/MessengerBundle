<?php
namespace Coa\MessengerBundle\Messenger\Stamp;
use Symfony\Component\Messenger\Stamp\StampInterface;

/**
 * @author Zacharie Assagou <zacharie.assagou@coteouest.ci>
 */
class CoaStamp implements StampInterface{
    protected string $producerId;
    protected string $payloadToken;

    public function __construct(string $producerId, string $payloadToken){
        $this->producerId = $producerId;
        $this->payloadToken = $payloadToken;
    }

    /**
     * @return string
     */
    public function getProducerId(): string
    {
        return $this->producerId;
    }

    /**
     * @return string
     */
    public function getPayloadToken(): string
    {
        return $this->payloadToken;
    }
}
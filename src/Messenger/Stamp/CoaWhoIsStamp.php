<?php
namespace Coa\MessengerBundle\Messenger\Stamp;
use Symfony\Component\Messenger\Stamp\StampInterface;

/**
 * @author Zacharie Assagou <zacharie.assagou@coteouest.ci>
 */
abstract class CoaWhoIsStamp implements StampInterface{
    private string $sourceProducerId;
    private string $destProducerId;

    public function __construct(string $sourceProducerId, string $destProducerId){
        $this->sourceProducerId = $sourceProducerId;
        $this->destProducerId = $destProducerId;
    }

    /**
     * @return string
     */
    public function getSourceProducerId(): string
    {
        return $this->sourceProducerId;
    }

    /**
     * @return string
     */
    public function getDestProducerId(): string
    {
        return $this->destProducerId;
    }
}
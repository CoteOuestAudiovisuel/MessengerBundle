<?php
namespace Coa\MessengerBundle\Messenger\Stamp;
use Symfony\Component\Messenger\Stamp\StampInterface;

/**
 * @author Zacharie Assagou <zacharie.assagou@coteouest.ci>
 */
class CoaDiscardStamp implements StampInterface{
    protected string $reason;

    public function __construct(string $reason){
        $this->reason = $reason;
    }

    /**
     * @return string
     */
    public function getReason(): string
    {
        return $this->reason;
    }
}
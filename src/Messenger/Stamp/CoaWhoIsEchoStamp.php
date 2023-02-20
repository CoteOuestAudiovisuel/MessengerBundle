<?php
namespace Coa\MessengerBundle\Messenger\Stamp;
use Symfony\Component\Messenger\Stamp\StampInterface;

/**
 * @author Zacharie Assagou <zacharie.assagou@coteouest.ci>
 */
class CoaWhoIsEchoStamp extends CoaWhoIsStamp {
    /**
     * {@inheritdoc }
     */
    public function __construct(string $sourceProducerId, string $destProducerId){
        parent::__construct($sourceProducerId,$destProducerId);
    }
}
<?php
namespace Coa\MessengerBundle\Messenger\Message;
use Coa\MessengerBundle\Messenger\Hydrator;

/**
 * @author Zacharie Assagou <zacharie.assagou@coteouest.ci>
 */
class DefaulfMessage extends Hydrator{
    private string $action;
    private array $payload;

    /**
     * @param array $data
     */
    public function __construct(array $data = []){
        parent::__construct($data);
    }

    /**
     * @return array|null
     */
    public function getPayload(): ?array {
        return $this->payload;
    }

    /**
     * @param array|null $payload
     * @return $this
     */
    public function setPayload(?array $payload = []): self {
        $this->payload = $payload;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getAction(): ?string {
        return $this->action;
    }

    /**
     * @param string|null $action
     * @return $this
     */
    public function setAction(?string $action): self {
        $this->action = $action;
        return $this;
    }
}
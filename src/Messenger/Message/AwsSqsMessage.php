<?php
namespace Coa\MessengerBundle\Messenger\Message;
use Coa\MessengerBundle\Messenger\Hydrator;


/**
 * @author Zacharie Assagou <zacharie.assagou@coteouest.ci>
 */
class AwsSqsMessage extends Hydrator
{
    private string $action = "";
    private array $payload = [];
    private array $detail = [];

    /**
     * @param array $data
     */
    public function __construct(array $data = []){
        parent::__construct($data);
        $this->detail = array_merge(["userMetadata"=>["plateform"=>""]],$this->detail);
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

    /**
     * @return array
     */
    public function getDetail(): array{
        return $this->detail;
    }

    /**
     * @param array $detail
     */
    public function setDetail(array $detail): self{
        $this->detail = $detail;
        return $this;
    }
}
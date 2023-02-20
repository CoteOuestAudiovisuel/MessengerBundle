<?php
namespace Coa\MessengerBundle\Messenger;

class WhoIsRequest{

    private string $id;
    private string $status;
    private int $timestamps;

    public function __construct(string $id, string $status = "pending", ?int $timestamps = null){
        $this->id = $id;
        $this->status = $status;
        $this->timestamps = $timestamps ?? (new \DateTimeImmutable())->getTimestamp();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return int
     */
    public function getTimestamps(): int
    {
        return $this->timestamps;
    }
}
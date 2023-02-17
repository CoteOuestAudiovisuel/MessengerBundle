<?php
namespace Coa\MessengerBundle\Messenger;

class Producer implements SettingInterface{
    private string $id;
    private string $token;

    public function __construct(string $id, string $token){
        $this->id = $id;
        $this->token = $token;
    }

    public function with(string $id, string $token){
        return new self($id,$token);
    }

    /**
     * @return string
     */
    public function getToken(): string{
        return $this->token;
    }

    /**
     * @return string
     */
    public function getId(): string{
        return $this->id;
    }
}
<?php
namespace Coa\MessengerBundle\Event;
use Symfony\Contracts\EventDispatcher\Event;

class IncomingSqsMessageEvent extends Event {
    private $data;

    public function __construct($data){
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getData(){
        return $this->data;
    }

    /**
     * @param string $data
     */
    public function setData($data): void{
        $this->data = $data;
    }
}
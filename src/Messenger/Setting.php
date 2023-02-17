<?php
namespace Coa\MessengerBundle\Messenger;

class Setting implements SettingInterface{
    private string $id;
    private string $token;
    private array $producers;

    /**
     * @param string $id
     * @param string $token
     * @param array $producers
     */
    public function __construct(string $id, string $token, array $producers = []){
        $this->id = $id;
        $this->token = $token;
        $this->producers = [];

        foreach ($producers as $item){
            $this->addProducer($item);
        }
    }

    /**
     * @param Producer $item
     * @return $this
     */
    public function addProducer(Producer $item) : self{
        $this->producers[] = $item;
        return $this;
    }


    /**
     * @param Producer $target
     * @param Producer|null $replaceWith
     * @return $this
     */
    public function removeProducer(Producer $target, ?Producer $replaceWith = null) : self{
        foreach ($this->producers as $i=>$el){
            /** @var Producer $el */
            if($target->getId() == $el->getId()){
                unset($this->producers[$i]);
                if($replaceWith){
                    $this->addProducer($replaceWith);
                }
                break;
            }
        }
        return $this;
    }

    /**
     * @param string $id
     * @param string $token
     * @param array $producers
     * @return Setting
     */
    public function with(string $id, string $token, array $producers = []){
        return new self($id,$token,$producers);
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

    /**
     * @return array
     */
    public function getProducers(): array{
        return $this->producers;
    }
}
<?php
namespace Coa\MessengerBundle\Messenger\Handler;

/**
 * @author Zacharie Assagou <zacharie.assagou@coteouest.ci>
 */
abstract class Handler{
    private string $bindingKey;
    private int $priority;

    /**
     * @param string|null $bindingKey
     */
    public function __construct(?string $bindingKey = "", int $priority = 0){
        $this->bindingKey = $bindingKey;
        $this->priority = $priority;
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * @return string|null
     */
    public function getBindingKey(): ?string
    {
        return $this->bindingKey;
    }

    /**
     * @param string|null $match
     * @return bool
     */
    private function check(?string $bindingKey = ""):bool{
        return preg_match(sprintf("#^%s$#",$this->bindingKey),$bindingKey);
    }

    /**
     * @param string $bindingKey
     * @param array $payload
     * @return mixed|null
     */
    public function start(string $bindingKey, array $payload){
        if($this->check($bindingKey)){
            return $this->run($bindingKey,$payload);
        }
        return null;
    }

    /**
     * retourne un bindingkey utilisable pour les ifcases
     * @return string|null
     */
    public function getUsableBindingKey(){
        $bindingKey = $this->bindingKey;
        $b = explode(".",$bindingKey);
        if(count($b) > 2){
            $sce = array_pop($b);
            $bindingKey = implode(".",$b);
        }
        return $bindingKey;
    }

    /**
     * @param array $payload
     * @return mixed
     */
    abstract protected function run(string $bindingKey,array $payload);


}
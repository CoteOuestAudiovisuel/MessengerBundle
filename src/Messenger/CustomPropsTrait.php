<?php
namespace Coa\MessengerBundle\Messenger;


/**
 * @author Zacharie Assagou <zacharie.assagou@coteouest.ci>
 */
trait CustomPropsTrait{
    private array $customProps = [];

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed|void|null
     */
    public function __call(string $name, array $arguments){
        if(count($arguments) != 0){ // c'est un setter
            if($name == "customProps"){
                $this->customProps = gettype($arguments[0]) == "string" ? json_decode($arguments[0],true): $arguments[0];
            }
            else{
                $this->customProps[$name] = $arguments[0];
            }
        }
        else{ // c'est un getter
            if(isset($this->customProps[$name])){
                return $this->customProps[$name];
            }
            return null;
        }
    }

    /**
     * @return array
     */
    public function getCustomProps(): array {
        return $this->customProps;
    }

}
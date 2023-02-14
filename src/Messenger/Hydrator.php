<?php
namespace Coa\MessengerBundle\Messenger;

/**
 * @author Zacharie Assagou <zacharie.assagou@coteouest.ci>
 */
abstract class Hydrator{

    use CustomPropsTrait;

    /**
     * @param array $data
     */
    public function __construct(array $data){
        $this->hydrate($data);
    }

    /**
     * @param array $data
     */
    protected function hydrate(array $data = []){
        foreach ($data as $k=>$v){
            $getter = "get".$k;
            $setter = "set".$k;
            if(method_exists($this,$setter)){
                $this->$setter($v);
            }
            else{
                $this->$k($v);
            }
        }
    }
}
<?php
namespace Coa\MessengerBundle\Messenger;
use Coa\MessengerBundle\Messenger\CustomPropsTrait;

/**
 * implementation du pattern Hydratation
 * permet l'initialisation des proprietés d'une instance de classe via un vecteur
 * d'initiliation clé valeur
 *
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

            $ee = explode("-",$k);
            #fix for key like: detail-type must be transform to detailType
            if(count($ee) > 1){
                for($i =1, $c=count($ee); $i < $c; $i++){
                    $ee[$i] = ucfirst(strtolower($ee[$i]));
                }
                $k = implode("",$ee);
            }

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
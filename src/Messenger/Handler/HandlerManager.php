<?php
namespace Coa\MessengerBundle\Messenger\Handler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;

/**
 * @author Zacharie Assagou <zacharie.assagou@coteouest.ci>
 */
class HandlerManager{
    private array $handlers;

    /**
     * @param array $handlers
     */
    public function __construct(array $handlers = []){
        $this->handlers = $handlers;
        $this->sort();
    }

    /**
     * @param Handler $handler
     * @return bool
     */
    public function has(Handler &$handler): bool{
        return count(array_values(array_filter($this->handlers,function (Handler $el) use(&$handler){
            return ($el == $handler);
        }))) > 0;
    }

    /**
     * @param Handler $handler
     * @return $this
     */
    public function add(Handler &$handler): self{
        if(!$this->has($handler)){
            $this->handlers[] = $handler;
            $this->sort();
        }
        return $this;
    }

    /**
     * @param Handler $handler
     * @return $this
     */
    public function remove(Handler &$handler): self{
        foreach ($this->handlers as $i=>$v){
            if($handler === $v){
                unset($this->handlers[$i]);
                break;
            }
        }
        $this->sort();
        return $this;
    }

    /**
     * @param string $match
     * @param array $payload
     * @return null
     */
    public function run(string $match, array $payload){
        $this->sort();

        foreach ($this->handlers as $handler){
            if(($rst = $handler->start($match,$payload))){
                return $rst;
            }
        }
        return null;
    }

    /**
     *
     */
    private function sort(){
        usort($this->handlers,function (Handler $a,Handler $b){
            if($a->getPriority() == $b->getPriority()) return 0;
            if($a->getPriority() < $b->getPriority()) return -1;
            else return 1;
        });
    }
}
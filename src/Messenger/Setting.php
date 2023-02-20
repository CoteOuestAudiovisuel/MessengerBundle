<?php
namespace Coa\MessengerBundle\Messenger;

use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;


/**
 * {@inheritdoc }
 */
class Setting implements SettingInterface{
    private string $id;
    private string $token;
    private array $producers;
    private array $whoisRequests;

    /**
     * @param string $id
     * @param string $token
     * @param array $producers
     */
    public function __construct(string $id, string $token, array $producers = []){
        $this->id = $id;
        $this->token = $token;
        $this->producers = $producers;
        $this->whoisRequests = [];

        foreach ($producers as $item){
            $this->addProducer($item);
        }
    }

    /**
     * {@inheritdoc }
     * @throws \Exception
     */
    public static function checkIntegrity(string $db_file, string $key_file) {
        if(!file_exists($db_file) || !file_exists($key_file) ){
            throw new \Exception("base de données coa_messenger inexistante");
        }
        $s = static::loadData($db_file,$key_file);
        try {
            if(!$s->getId() || !$s->getToken()){
                throw new \Exception("base de données coa_messenger corrompu");
            }
        }
        catch (\Exception $e){
            throw new \Exception("base de données coa_messenger corrompu");
        }
    }

    /**
     * {@inheritdoc }
     */
    public static function create(string $db_file, string $key_file): ?Setting{
        if(file_exists($db_file) || file_exists($key_file)){
            return static::loadData($db_file,$key_file);
        }

        $key = openssl_random_pseudo_bytes(16);
        file_put_contents($key_file,$key);

        $id = base64_encode(openssl_random_pseudo_bytes(16,$ok));
        $token = base64_encode(openssl_random_pseudo_bytes(32,$ok));
        $s = new self($id,$token,[]);
        $s->save($db_file,$key_file);
        return $s;
    }

    /**
     * {@inheritdoc }
     */
    public static function loadData(string $db_file, string $key_file): ?Setting{
        if(!file_exists($db_file)) {
            return null;
        }

        $payload = file_get_contents($db_file);
        $key = file_get_contents($key_file);

        $s = json_decode(openssl_decrypt($payload,"aes-256-cbc",$key,true),true);
        return new self($s["id"],$s["token"],$s["producers"]);
    }

    /**
     * {@inheritdoc }
     */
    public function save(string $db_file, string $key_file) : self{
        if(file_exists($key_file)){
            $encoders = [new JsonEncoder()];
            $normalizers = [new ObjectNormalizer()];
            $serializer = new Serializer($normalizers, $encoders);

            $key = file_get_contents($key_file);
            $payload = $serializer->serialize($this, 'json');
            $data = @openssl_encrypt($payload,"aes-256-cbc",$key,OPENSSL_RAW_DATA);
            file_put_contents($db_file,$data);
        }
        return $this;
    }

    /**
     * {@inheritdoc }
     */
    public function hasProducer(Producer $target): bool{
        foreach ($this->producers as $i=>$el){
            if($target->getId() == $el->getId()){
                return true;
            }
        }
        return false;
    }

    /**
     * {@inheritdoc }
     */
    public function addProducer(Producer $item) : self{
        if(!$this->hasProducer($item)){
            $this->producers[] = $item;
        }
        return $this;
    }


    /**
     * {@inheritdoc }
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
     * {@inheritdoc }
     */
    public function hasWhoIsRequest(WhoIsRequest $target): bool{
        foreach ($this->whoisRequests as $i=>$el){
            if($target->getId() == $el->getId()){
                return true;
            }
        }
        return false;
    }

    /**
     * {@inheritdoc }
     */
    public function addWhoIsRequest(WhoIsRequest $item) : self{
        if(!$this->hasWhoIsRequest($item)){
            $this->whoisRequests[] = $item;
        }
        return $this;
    }


    /**
     * {@inheritdoc }
     */
    public function removeWhoIsRequest(WhoIsRequest $target) : self{
        foreach ($this->whoisRequests as $i=>$el){
            /** @var WhoIsRequest $el */
            if($target->getId() == $el->getId()){
                unset($this->whoisRequests[$i]);
                break;
            }
        }
        return $this;
    }

    /**
     * {@inheritdoc }
     */
    public function getToken(): string{
        return $this->token;
    }

    /**
     * {@inheritdoc }
     */
    public function getId(): string{
        return $this->id;
    }

    /**
     * {@inheritdoc }
     */
    public function getProducers(): array{
        return $this->producers;
    }

    /**
     * {@inheritdoc }
     */
    public function getProducer(string $id): ?Producer{
        foreach ($this->producers as $i=>$el){
            if($id === $el->getId()){
                return $el;
            }
        }
        return null;
    }
}
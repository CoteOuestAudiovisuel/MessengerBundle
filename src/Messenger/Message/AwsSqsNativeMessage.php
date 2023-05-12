<?php
namespace Coa\MessengerBundle\Messenger\Message;
use Coa\MessengerBundle\Messenger\Hydrator;


/**
 * @author Zacharie Assagou <zacharie.assagou@coteouest.ci>
 */
class AwsSqsNativeMessage extends Hydrator
{
    CONST KEYS = "version id detail-type source time region resources detail";

    private string $version;
    private string $id;
    private string $detailType;
    private string $source;
    private string $time;
    private string $region;
    private array $resources;
    private array $detail;

    /**
     * @param array $data
     */
    public function __construct(array $data = []){
        parent::__construct($data);
    }

    /**
     * @return string
     */
    public function getVersion(): string{
        return $this->version;
    }

    /**
     * @param string $version
     */
    public function setVersion(string $version): void{
        $this->version = $version;
    }

    /**
     * @return string
     */
    public function getId(): string{
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void{
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getDetailType(): string{
        return $this->detailType;
    }

    /**
     * @param string $detailType
     */
    public function setDetailType(string $detailType): void{
        $this->detailType = $detailType;
    }

    /**
     * @return string
     */
    public function getSource(): string{
        return $this->source;
    }

    /**
     * @param string $source
     */
    public function setSource(string $source): void{
        $this->source = $source;
    }

    /**
     * @return string
     */
    public function getTime(): string{
        return $this->time;
    }

    /**
     * @param string $time
     */
    public function setTime(string $time): void{
        $this->time = $time;
    }

    /**
     * @return string
     */
    public function getRegion(): string{
        return $this->region;
    }

    /**
     * @param string $region
     */
    public function setRegion(string $region): void{
        $this->region = $region;
    }

    /**
     * @return string
     */
    public function getResources(): array{
        return $this->resources;
    }

    /**
     * @param string $resources
     */
    public function setResources(array $resources): void{
        $this->resources = $resources;
    }

    /**
     * @return string
     */
    public function getDetail(): array{
        return $this->detail;
    }

    /**
     * @param string $detail
     */
    public function setDetail(array $detail): void{
        $this->detail = $detail;
    }
}
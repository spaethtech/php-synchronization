<?php
declare(strict_types=1);

namespace SpaethTech\Synchronization;




final class SyncMap implements \JsonSerializable
{
    private const DEFAULT_JSON_OPTIONS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;

    private const DEFAULT_DATE_TIME_FORMAT = "c";

    /** @var string $file */
    protected $file;

    /** @var \DateTime $timestamp */
    protected $timestamp;

    /** @var array $map*/
    protected $map;

    /** @var SyncChanges $sourceChanges*/
    protected $sourceChanges;

    /** @var SyncChanges $destinationChanges*/
    protected $destinationChanges;



    public function __construct(string $file = "")
    {
        $this->file = $file;
    }

    public function getTimeStamp(): \DateTime
    {
        return $this->timestamp;
    }

    public function setTimeStamp(\DateTime $timestamp)
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    public function getMap(): array
    {
        return $this->map;
    }

    public function setMap(array $map)
    {
        $this->map = $map;
        return $this;
    }

    public function getSourceChanges(): SyncChanges
    {
        return $this->sourceChanges;
    }

    public function setSourceChanges(SyncChanges $changes)
    {
        $this->sourceChanges = $changes;
        return $this;
    }

    public function getDestinationChanges(): SyncChanges
    {
        return $this->destinationChanges;
    }

    public function setDestinationChanges(SyncChanges $changes)
    {
        $this->destinationChanges = $changes;
        return $this;
    }



    public function clear(): SyncMap
    {
        if(file_exists($this->file))
            unlink($this->file);

        return $this;
    }

    public static function load(string $file): SyncMap
    {
        $map = new SyncMap($file);

        $array = json_decode(file_get_contents($file), true);

        $map->timestamp = $array["timestamp"] ?: new \DateTime();
        $map->map = $array["map"] ?: [];
        $map->changes = $array["changes"] ?: new SyncChanges();

        return $map;
    }

    public function save(): SyncMap
    {
        if($this->file === "")
            return $this;

        $this->timestamp = new \DateTime();

        $json = json_encode($this, self::DEFAULT_JSON_OPTIONS);

        file_put_contents($this->file, $json);

        return $this;
    }


    public function jsonSerialize()
    {
        $array = get_object_vars($this);
        unset($array["file"]);

        return $array;
    }
}

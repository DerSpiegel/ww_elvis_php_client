<?php

namespace DerSpiegel\WoodWingAssetsClient\Request;


/**
 * Class CreateRequest
 *
 * @see https://helpcenter.woodwing.com/hc/en-us/articles/360042268771-Assets-Server-REST-API-create
 * @package DerSpiegel\WoodWingAssetsClient\Request
 */
class CreateRequest extends Request
{
    /** @var resource */
    protected $filedata;

    protected array $metadata = [];

    /** @var string[] */
    protected array $metadataToReturn = ['all'];

    protected bool $parseMetadataModification = false;


    /**
     * @return resource
     */
    public function getFiledata()
    {
        return $this->filedata;
    }


    /**
     * @param resource $fp
     * @return self
     */
    public function setFiledata($fp): self
    {
        $this->filedata = $fp;
        return $this;
    }


    /**
     * @return array
     */
    public function getMetadata(): array
    {
        $metadata = $this->metadata;

        // for some reason, Assets fails to clear up the metadata field if the sent value is an empty array
        foreach ($metadata as &$field) {
            if (is_array($field) && empty($field)) {
                $field = "";
            }
        }

        return $metadata;
    }


    /**
     * @param string[] $metadata
     * @return self
     */
    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }


    /**
     * @return string[]
     */
    public function getMetadataToReturn(): array
    {
        return $this->metadataToReturn;
    }


    /**
     * @param string[] $metadataToReturn
     * @return self
     */
    public function setMetadataToReturn(array $metadataToReturn): self
    {
        $this->metadataToReturn = $metadataToReturn;
        return $this;
    }


    /**
     * @return bool
     */
    public function isParseMetadataModification(): bool
    {
        return $this->parseMetadataModification;
    }


    /**
     * @param bool $parseMetadataModification
     * @return self
     */
    public function setParseMetadataModification(bool $parseMetadataModification): self
    {
        $this->parseMetadataModification = $parseMetadataModification;
        return $this;
    }
}

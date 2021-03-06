<?php

namespace DerSpiegel\WoodWingAssetsClient\Request;


/**
 * Class ProcessResponse
 * @package DerSpiegel\WoodWingAssetsClient\Request
 */
class ProcessResponse extends Response
{
    protected int $processedCount = 0;
    protected int $errorCount = 0;


    /**
     * @param array $json
     * @return self
     */
    public function fromJson(array $json): self
    {
        if (isset($json['processedCount'])) {
            $this->processedCount = intval($json['processedCount']);
        }

        if (isset($json['errorCount'])) {
            $this->errorCount = intval($json['errorCount']);
        }

        return $this;
    }


    /**
     * @return int
     */
    public function getProcessedCount(): int
    {
        return $this->processedCount;
    }


    /**
     * @return int
     */
    public function getErrorCount(): int
    {
        return $this->errorCount;
    }
}

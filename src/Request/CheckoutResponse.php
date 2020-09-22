<?php

namespace DerSpiegel\WoodWingElvisClient\Request;


/**
 * Class CheckoutResponse
 * @see https://helpcenter.woodwing.com/hc/en-us/articles/115002690146-Elvis-6-REST-API-checkout
 * @package DerSpiegel\WoodWingElvisClient\Request
 */
class CheckoutResponse extends Response
{
    protected int $checkedOut;

    protected string $checkedOutBy;

    protected string $checkedOutOnClient;


    /**
     * @param array $json
     * @return self
     */
    public function fromJson(array $json): self
    {
        if (isset($json['checkedOut'])) {
            $this->checkedOut = intval($json['checkedOut']);
        }

        if (isset($json['checkedOutBy'])) {
            $this->checkedOutBy = trim($json['checkedOutBy']);
        }

        if (isset($json['checkedOutOnClient'])) {
            $this->checkedOutOnClient = trim($json['checkedOutOnClient']);
        }

        return $this;
    }


    /**
     * @return int
     */
    public function getCheckedOut(): int
    {
        return intval($this->checkedOut);
    }


    /**
     * @return string
     */
    public function getCheckedOutBy(): string
    {
        return $this->checkedOutBy ?? '';
    }


    /**
     * @return string
     */
    public function getCheckedOutOnClient(): string
    {
        return $this->checkedOutOnClient ?? '';
    }

}
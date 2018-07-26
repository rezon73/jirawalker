<?php

namespace Request;

abstract class Request implements \IRequest
{
    protected $issueKey;

    protected $additionalData = [];

    public function __construct($additionalData = null)
    {
        if (!is_null($additionalData)) {
            $this->additionalData = $additionalData;
        }
    }

    public function setIssueKey(string $issueKey)
    {
        $this->issueKey = $issueKey;

        return $this;
    }

    public function getIssueKey()
    {
        return $this->issueKey;
    }

    public function setAdditionalData(array $data)
    {
        $this->additionalData = array_merge($this->additionalData, $data);

        return $this;
    }
}
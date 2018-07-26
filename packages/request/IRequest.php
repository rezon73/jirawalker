<?php

interface IRequest {
    /**
     * @param string $issueKey
     * @return $this
     */
    public function setIssueKey(string $issueKey);

    /**
     * @return string
     */
    public function getIssueKey();

    /**
     * @param array $data
     * @return $this
     */
    public function setAdditionalData(array $data);

    public function send();
}
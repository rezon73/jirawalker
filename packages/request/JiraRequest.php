<?php

namespace Request;

use chobie\Jira\Api;
use chobie\Jira\Api\Authentication\Basic;
use Config\Config;

class JiraRequest extends Request implements \IRequest
{
    /**
     * @var null|\chobie\Jira\Api
     */
    private $jiraApiCache = null;

    public function send()
    {
        $this->initJiraApi();

        if (empty($this->additionalData['query'])) {
            return;
        }

        $this->jiraApiCache->editIssue(
            $this->getIssueKey(),
            $this->additionalData['query']
        );
    }

    private function initJiraApi()
    {
        if (is_null($this->jiraApiCache)) {
            $this->jiraApiCache = new Api(
                Config::me()->get('jiraUrl'),
                new Basic(Config::me()->get('login'), Config::me()->get('password'))
            );
        }
    }
}
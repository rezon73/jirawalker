<?php

$loader = require_once __DIR__.'/../vendor/autoload.php';

use chobie\Jira\Api;
use chobie\Jira\Api\Authentication\Basic;
use chobie\Jira\Issues\Walker;
use Atlassian\Stash\StashClient;
use Config\Config;
use ProductionChecker\ProductionChecker;

$jiraApi = new Api(
	Config::me()->get('jiraUrl'),
	new Basic(Config::me()->get('login'), Config::me()->get('password'))
);
$walker = new Walker($jiraApi);
$walker->push(
	Config::me()->get('scanningIssueJiraQuery')
);

$stash = new StashClient(Config::me()->get('bitbucketUrl'), Config::me()->get('login'), Config::me()->get('password'));
$stashHttpClient = $stash->getHttpClient();

foreach ($walker as $issue) {
	/** @var chobie\Jira\Issue $issue */
	echo PHP_EOL . PHP_EOL . $issue->getKey() . PHP_EOL;

	$searchBranchResults = $stashHttpClient->get(
		'/rest/api/1.0/projects/PHP/repos/general/branches?orderBy=MODIFICATION&filterText=' . strtolower($issue->getKey())
	)->json();

	if ($searchBranchResults['size'] == 0) {
		continue;
	}

	$issueBranch = $searchBranchResults['values'][0]['displayId'];

	$branchIsReady = ProductionChecker::me()
		->setProjectRepositoryPath(Config::me()->get('projectRepositoryPath'))
		->setBranchName($issueBranch)
		->setProductionBranchName(Config::me()->get('productionBranchNameSource')->getProductionBranchName())
		->run();

	if ($branchIsReady) {
		$jiraApi->editIssue(
			$issue->getKey(),
			Config::me()->get('needMergeProductionJiraRequest')
		);
	}
	else {
		$jiraApi->editIssue(
			$issue->getKey(),
			Config::me()->get('dontNeedMergeProductionJiraRequest')
		);
	}

	echo PHP_EOL . PHP_EOL . $issueBranch . ' ' . (bool) $branchIsReady . PHP_EOL;
}

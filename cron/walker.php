<?php

$loader = require_once __DIR__.'/../vendor/autoload.php';

use chobie\Jira\Api;
use chobie\Jira\Api\Authentication\Basic;
use chobie\Jira\Issues\Walker;
use Atlassian\Stash\StashClient;
use Config\Config;
use ProductionChecker\ProductionChecker;
use TaskLocker\TaskLocker;

$taskLocker = new TaskLocker('check.lock');

if ($taskLocker->isLocked()) {
    return;
}

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

$productionBranchName = Config::me()->get('productionBranchNameSource')->getProductionBranchName();
if (empty($productionBranchName)) {
	exit(0);
}

foreach ($walker as $issue) {
    /** @var chobie\Jira\Issue $issue */
    echo PHP_EOL . PHP_EOL . $issue->getKey() . PHP_EOL;

    $searchBranchResults = $stashHttpClient->get(
		'/rest/api/1.0/projects/PHP/repos/general/branches?orderBy=MODIFICATION&filterText=' . strtolower($issue->getKey())
	)->json();

	if ($searchBranchResults['size'] == 0) {
		$jiraApi->editIssue(
			$issue->getKey(),
			Config::me()->get('dontNeedMergeProductionJiraRequest')
		);

		continue;
	}

	// search opened pull-requests for each branch
    $issueBranch = '';
	foreach($searchBranchResults['values'] as $branchCandidate) {
        $searchPullRequestResults = $stashHttpClient->get(
            '/rest/api/1.0/projects/PHP/repos/general/pull-requests?direction=outgoing&at=' . strtolower($branchCandidate['id'])
        )->json();

        if ($searchPullRequestResults['size'] == 0) {
            continue;
        }

        $issueBranch = $branchCandidate['displayId'];
        break;
    }

    if (empty($issueBranch)) {
        // if declined pull request only exists, developed changes are rollbacked
	    $isExistDeclinedPullRequest = false;

        foreach($searchBranchResults['values'] as $branchCandidate) {
            $searchDeclinedPullRequestResults = $stashHttpClient->get(
                '/rest/api/1.0/projects/PHP/repos/general/pull-requests?direction=outgoing&state=DECLINED&at=' . strtolower($branchCandidate['id'])
            )->json();

            if ($searchDeclinedPullRequestResults['size'] == 0) {
                continue;
            }

            $isExistDeclinedPullRequest = true;
            break;
        }

        if ($isExistDeclinedPullRequest) {
            $jiraApi->editIssue(
                $issue->getKey(),
                Config::me()->get('dontNeedMergeProductionJiraRequest')
            );
        }
        else {
            $jiraApi->editIssue(
                $issue->getKey(),
                Config::me()->get('needMergeProductionJiraRequest')
            );
        }

        continue;
    }

	$branchIsReady = ProductionChecker::me()
		->setProjectRepositoryPath(Config::me()->get('projectRepositoryPath'))
		->setBranchName($issueBranch)
		->setProductionBranchName($productionBranchName)
		->run();

	if ($branchIsReady) {
		$jiraApi->editIssue(
			$issue->getKey(),
			Config::me()->get('dontNeedMergeProductionJiraRequest')
		);
	}
	else {
		$jiraApi->editIssue(
			$issue->getKey(),
			Config::me()->get('needMergeProductionJiraRequest')
		);
	}

	echo PHP_EOL . PHP_EOL . $issueBranch . ' ' . (bool) $branchIsReady . PHP_EOL;
}

$taskLocker->unlock();
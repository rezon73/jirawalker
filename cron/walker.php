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

$stash = new StashClient(Config::me()->get('stashUrl'), Config::me()->get('login'), Config::me()->get('password'));
$stashHttpClient = $stash->getHttpClient();

$productionBranchName = Config::me()->get('productionBranchNameSource')->getProductionBranchName();
if (empty($productionBranchName)) {
	exit(0);
}

$stashRestApiUrlPrefix = '/rest/api/1.0/projects/' . Config::me()->get('stashProject') . '/repos/' . Config::me()->get('stashRepository');

foreach ($walker as $issue) {
    /** @var chobie\Jira\Issue $issue */
    echo PHP_EOL . PHP_EOL . $issue->getKey() . PHP_EOL;

    $searchBranchResults = $stashHttpClient->get(
        $stashRestApiUrlPrefix . '/branches?orderBy=MODIFICATION&filterText=' . strtolower($issue->getKey())
	)->json(['object' => false]);

	if ($searchBranchResults['size'] == 0) {
        /** @var IRequest $request */
        $request = Config::me()->get('dontNeedMergeProductionRequest');
        $request->setIssueKey($issue->getKey());
        $request->send();

		continue;
	}

	$issuePullRequests = [];

	// search opened pull-requests for each branch
    $issueBranch = '';
	foreach($searchBranchResults['values'] as $branchCandidate) {
        $searchPullRequestResults = $stashHttpClient->get(
            $stashRestApiUrlPrefix . '/pull-requests?direction=outgoing&state=OPEN&at=' . strtolower($branchCandidate['id'])
        )->json(['object' => false]);

        if ($searchPullRequestResults['size'] == 0) {
            continue;
        }

        foreach($searchPullRequestResults['values'] as $pullRequest) {
            $issuePullRequests[] = $pullRequest;
        }

        $issueBranch = $branchCandidate['displayId'];
        break;
    }

    if (empty($issueBranch)) {
        // if declined pull request only exists, developed changes are rollbacked, so you skip checking of this task
	    $isExistDeclinedPullRequest = false;

        foreach($searchBranchResults['values'] as $branchCandidate) {
            $searchDeclinedPullRequestResults = $stashHttpClient->get(
                $stashRestApiUrlPrefix . '/pull-requests?direction=outgoing&state=DECLINED&at=' . strtolower($branchCandidate['id'])
            )->json(['object' => false]);

            if ($searchDeclinedPullRequestResults['size'] == 0) {
                continue;
            }

            $isExistDeclinedPullRequest = true;
            break;
        }

        if ($isExistDeclinedPullRequest) {
            /** @var IRequest $request */
            $request = Config::me()->get('dontNeedMergeProductionRequest');
            $request->setIssueKey($issue->getKey());
            $request->send();
        }
        else {
            /** @var IRequest $request */
            $request = Config::me()->get('needMergeProductionRequest');
            $request->setIssueKey($issue->getKey());
            $request->send();
        }

        continue;
    }

	$branchIsReady = ProductionChecker::me()
		->setProjectRepositoryPath(Config::me()->get('projectRepositoryPath'))
		->setBranchName($issueBranch)
		->setProductionBranchName($productionBranchName)
		->run();

	if ($branchIsReady) {
	    /** @var IRequest $request */
        $request = Config::me()->get('dontNeedMergeProductionRequest');
        $request->setIssueKey($issue->getKey());
        $request->setAdditionalData([
            'pullRequests' => $issuePullRequests,
        ]);
        $request->send();
	}
	else {
        $request = Config::me()->get('needMergeProductionRequest');
        $request->setIssueKey($issue->getKey());
        $request->setAdditionalData([
            'pullRequests' => $issuePullRequests,
        ]);
        $request->send();
	}

	echo PHP_EOL . PHP_EOL . $issueBranch . ' ' . (bool) $branchIsReady . PHP_EOL;
}

$taskLocker->unlock();
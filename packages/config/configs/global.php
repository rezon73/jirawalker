<?php

return [
	'login'                      => 'test',
	'password'                   => 'test',
	'jiraUrl'                    => 'https://jira.example.com',
	'stashUrl'                   => 'https://stash.example.com',

	'scanningIssueJiraQuery'     => 'status = Resolved',

	'productionBranchNameSource' => new \ProductionBranchSource\SimpleProductionBranchSource('master'),
	'projectRepositoryPath'      => 'projectRepository/',
    'stashProject'               => 'PHP',
    'stashRepository'            => 'general',

    'pushBranchAfterMerge'       => false,

	'needMergeProductionRequest' => new \Request\JiraRequest([
		'query' => [
		    'update' => [
		        'labels' => [
		            ['add' => 'need_merge_production'],
                ]
            ]
        ]
	]),

	'dontNeedMergeProductionRequest' => new \Request\JiraRequest([
        'query' => [
            'update' => [
                'labels' => [
                    ['delete' => 'need_merge_production'],
                ]
            ]
        ]
    ]),
];
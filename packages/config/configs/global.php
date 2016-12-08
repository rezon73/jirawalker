<?php

return [
	'login'                      => 'test',
	'password'                   => 'test',
	'jiraUrl'                    => 'https://jira.example.com',
	'bitbucketUrl'               => 'https://stash.example.com',

	'scanningIssueJiraQuery'     => 'status = Resolved',

	'productionBranchNameSource' => new \ProductionBranchSource\SimpleProductionBranchSource(),
	'projectRepositoryPath'      => 'projectRepository/',

	'needMergeProductionJiraRequest' => [
		'update' => [
			'labels' => [
				['add' => 'need_merge_production'],
			]
		]
	],
	'dontNeedMergeProductionJiraRequest' => [
		'update' => [
			'labels' => [
				['delete' => 'need_merge_production'],
			]
		]
	],
];
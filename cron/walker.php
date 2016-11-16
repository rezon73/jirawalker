<?php

use chobie\Jira\Api;
use chobie\Jira\Api\Authentication\Basic;
use chobie\Jira\Issues\Walker;

$api = new Api(
	'https://your-jira-project.net',
	new Basic('yourname', 'password')
);

$walker = new Walker($api);
$walker->push(
	'project = "YOURPROJECT" AND (status != "closed" AND status != "resolved") ORDER BY priority DESC'
);

foreach ( $walker as $issue ) {
	var_dump($issue);
	// Send custom notification here.
}
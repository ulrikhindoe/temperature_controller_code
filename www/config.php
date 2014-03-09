<?php
/*
 * Common configuration for the cronjob and www
 */

$config = [
	'serverType' => 'regulatorRemotelyControlled', // one of 'regulatorLocallyControlled' | 'regulatorRemotelyControlled' | 'externalController'

	'database' => [
		'host'         => 'localhost',
		'username'     => 'ulrik',
		'password'     => 'mysqlUserPassword_CHANGE_THIS',
		'databaseName' => 'temperature_controller',
	],

	// Is required if serverType is regulatorLocallyControlled or externalController
	'web' => [
		// Will not be used directly. Must be placed in .htpasswd. Password must be hashed
		'username'     => 'websiteUsername_CHANGE_THIS',
		'password'     => 'websitePassword_CHANGE_THIS',
	],

	// Only relevant if serverType is regulatorLocallyControlled || regulatorRemotelyControlled
	'cronjob' => [
		'remoteController' => [
			'root'     => 'http://externalControllerWebsite_CHANGE_THIS.com',
			'username' => 'externalControllerWebsiteUsername_CHANGE_THIS',
			'password' => 'externalControllerWebsitePassword_CHANGE_THIS',
		],
	]
];


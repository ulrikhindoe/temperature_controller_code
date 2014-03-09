<?php
require_once 'config.php';
require_once 'common_www.php';

$mysqli = initDb($config);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
	if ($config['serverType'] == 'regulatorRemotelyControlled') {
		print "error";
		exit;
	}
	validateParameters($_POST);
	updateParametersInDb($_POST, $mysqli);
	print 'ok';
} else {
	print json_encode(getParametersFromDb($mysqli));
}

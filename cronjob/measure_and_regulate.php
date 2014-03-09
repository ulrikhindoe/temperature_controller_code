<?php
require_once 'common_cronjob.php';

$mysqli = initDb($config);


if ($config['serverType'] == 'regulatorRemotelyControlled') {
	updateParametersInDbWithValuesFromRemoteSite($config, $mysqli);
}

$measuredAt = date('Y-m-d H:i:s');
$temperature = measureTemperature();

$newHeatOn = regulateTemperature($temperature, $mysqli);

writeTimeseriesDataToDb($measuredAt, $temperature, $newHeatOn, $mysqli);
if ($config['serverType'] == 'regulatorRemotelyControlled') {
	postTimeseriesData($measuredAt, $temperature, $newHeatOn, $config);
}









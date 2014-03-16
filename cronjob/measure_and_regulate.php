<?php
require_once 'common_cronjob.php';

$mysqli = initDb($config);


if ($config['serverType'] == 'regulatorRemotelyControlled') {
	updateParametersInDbWithValuesFromRemoteSite($config, $mysqli);
}

$parameters = getParametersFromDb($mysqli);

$measuredAt = date('Y-m-d H:i:s');
$temperature = measureTemperature();


$minimumTemperature = $parameters['heat_on_if_temp_lower_than'];
$outsideTemperature = loadOutsideTemperatureFromDmi();

$newHeatOn = regulateTemperature($temperature, $mysqli);

writeTimeseriesDataToDb($measuredAt, $temperature, $minimumTemperature, $outsideTemperature, $newHeatOn, $mysqli);
if ($config['serverType'] == 'regulatorRemotelyControlled') {
	postTimeseriesData($measuredAt, $temperature, $minimumTemperature, $outsideTemperature, $newHeatOn, $config);
}









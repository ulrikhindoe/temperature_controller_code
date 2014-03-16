<?php
require_once __DIR__ . '/../www/common.php';

function updateParametersInDbWithValuesFromRemoteSite($config, $mysqli) {
	$parameters = getParametersFromRemoteSite($config);
	updateParametersInDb($parameters, $mysqli);
}

function getParametersFromRemoteSite($config) {
	$ch = curl_init();
	curl_setopt_array($ch, [
		CURLOPT_URL => $config['cronjob']['remoteController']['root'] . '/parameters.php',
		CURLOPT_USERPWD =>   $config['cronjob']['remoteController']['username'] . ':' . $config['cronjob']['remoteController']['password'],
		CURLOPT_RETURNTRANSFER => true,
	]);
	$response = curl_exec($ch);
	$parameters = json_decode($response, true);
	if (count($parameters) != 3) {
		throw new Exception("Only expects 3 parameters from remote site. Got " . count($parameters));
	}
	validateParameters($parameters);
	return $parameters;
}

function measureTemperature() {
	$devicesDir = '/sys/bus/w1/devices';
	$deviceId = false;
	foreach (scandir($devicesDir) as $dir) {
		if (preg_match('/^[0-9a-f]{2}-[0-9a-f]{12}$/', $dir)) {
			$deviceId = $dir;
			break;
		}
	}
	if (!$deviceId) {
		return false;
	}

	$wqSlave     = file_get_contents("$devicesDir/{$deviceId}/w1_slave");
	$temperature = false;
	if (preg_match('/ t=(\d+)/', $wqSlave, $matches)) {
		$temperature = $matches[1] / 1000;
	}
	return $temperature;
}

function regulateTemperature($temperature, $mysqli) {
	$currentHeatOn = getCurrentHeatOnFromDb($mysqli);
	$parameters = getParametersFromDb($mysqli);
	$newHeatOn = $temperature < $parameters['heat_on_if_temp_lower_than'] ? 1 : 0;
	if ($newHeatOn != $currentHeatOn) {
		$secondsSinceLatestChangeInHeatOnValueFromDb = getSecondsSinceLatestChangeInHeatOnValueFromDb($mysqli);
		if ($secondsSinceLatestChangeInHeatOnValueFromDb < $parameters['min_seconds_between_heat_on_off']) {
			$newHeatOn = $currentHeatOn;
		}
	}
	setHeatOn($newHeatOn);
	return $newHeatOn;
}

function getSecondsSinceLatestChangeInHeatOnValueFromDb($mysqli) {
	$seconds = 99999;
	$currentHeatOn = getCurrentHeatOnFromDb($mysqli);
	if ($currentHeatOn) {
		$sql = "
			SELECT
				UNIX_TIMESTAMP(NOW()) -  UNIX_TIMESTAMP(measured_at)
			FROM time_series
			WHERE heat_on <> $currentHeatOn
			ORDER BY measured_at
			DESC LIMIT 0, 1
		";
		$res = $mysqli->query($sql) or die($mysqli->error);
		$row = $res->fetch_row();
		if ($row) {
			$seconds = $row[0];
		}
	}
	return $seconds;
}
function getCurrentHeatOnFromDb($mysqli) {
	$heatOn = false;
	$sql = 'SELECT heat_on FROM time_series ORDER BY measured_at DESC LIMIT 0, 1';
	$res = $mysqli->query($sql);
	$row = $res->fetch_row();
	if ($row) {
		$heatOn = $row[0];
	}
	return $heatOn;
}

function setHeatOn($value) {
	// diode på board lyser ved "on"
	$fp = fopen('/sys/class/gpio/gpio17/value', 'w');
	fwrite($fp, $value ? '0' : '1');
	fclose($fp);
}

function postTimeseriesData($measuredAt, $temperature, $heatOn, $config) {
	$ch = curl_init();
	curl_setopt_array($ch, [
		CURLOPT_URL => $config['cronjob']['remoteController']['root'] . '/timeSeriesData.php',
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => http_build_query([
												   'measuredAt'  => $measuredAt,
												   'temperature' => $temperature,
												   'heatOn'      => $heatOn
											   ]),
		CURLOPT_USERPWD =>   $config['cronjob']['remoteController']['username'] . ':' . $config['cronjob']['remoteController']['password'],
		CURLOPT_RETURNTRANSFER => true,
	]);
	$response = curl_exec($ch);
}

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
	if (preg_match('/ t=(\d{5})/', $wqSlave, $matches)) {
		$temperature = $matches[1] / 1000;
	}
	print "temperature: $temperature\n";
	return $temperature;
}

function regulateTemperature($temperature, $mysqli) {
	$currentHeatOn = getCurrentHeatOnFromDb($mysqli);
	$parameters = getParametersFromDb($mysqli);
	$newHeatOn = 0;
	if ($temperature > $parameters['heat_off_if_temp_higher_than']) {
		$newHeatOn = 0;
	} elseif ($temperature < $parameters['heat_on_if_temp_lower_than']) {
		$newHeatOn = 1;
	}
	if ($newHeatOn != $currentHeatOn) {
		$secondsSinceLatestChangeInHeatOnValueFromDb = getSecondsSinceLatestChangeInHeatOnValueFromDb($mysqli);
		print "secondsSinceLatestChangeInHeatOnValueFromDb: $secondsSinceLatestChangeInHeatOnValueFromDb\n";
		if ($secondsSinceLatestChangeInHeatOnValueFromDb < $parameters['min_seconds_between_heat_on_off']) {
			$newHeatOn = $currentHeatOn;
		}
	}
	setHeatOn($newHeatOn);
	return $newHeatOn;
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
	// diode p� board lyser ved "on"
	print "heatOn to relay: $value\n";
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
	print "response: $response\n";
}
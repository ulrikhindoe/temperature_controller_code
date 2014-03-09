<?php
/*
 * Common stuff for the cronjob and www
 */

require_once __DIR__ . '/config.php';

function initDb($config) {
	$mysqli = new mysqli(
		$config['database']['host'],
		$config['database']['username'],
		$config['database']['password'],
		$config['database']['databaseName']
	);
	return $mysqli;
}

function writeTimeseriesDataToDb($measuredAt, $temperature, $heatOn, $mysqli) {
	$sql = "INSERT INTO time_series (measured_at, temperature, heat_on) VALUES ('$measuredAt', $temperature, $heatOn)";
	$mysqli->query($sql) or die($mysqli->error);

	// Keep only measurements for the latest 30 days
	$sql = "DELETE FROM time_series where measured_at < DATE_SUB(NOW(), INTERVAL 30 DAY)";
	$mysqli->query($sql) or die($mysqli->error);
}

function validateParameters($parameters) {
	if (count($parameters) != 3) {
		throw new Exception("Only expects 3 parameters from remote site. Got " . count($parameters));
	}

	if (!preg_match('/^-?\d+(\.\d+)?$/', $parameters['heat_on_if_temp_lower_than'])) {
		throw new Exception('parameter heat_on_if_temp_lower_than');
	}
	if (!preg_match('/^-?\d+(\.\d+)?$/', $parameters['heat_off_if_temp_higher_than'])) {
		throw new Exception('parameter heat_off_if_temp_higher_than');
	}
	if (!preg_match('/^\d+$/', $parameters['min_seconds_between_heat_on_off'])) {
		throw new Exception('parameter min_seconds_between_heat_on_off');
	}
}

function getParametersFromDb($mysqli) {
	$res = $mysqli->query('SELECT * FROM parameters');
	$parameters = [];
	while ($row = $res->fetch_assoc()) {
		$parameters[$row['name']] = $row['value'];
	}
	return $parameters;
}



function updateParametersInDb($parameters, $mysqli) {
	foreach ($parameters as $name => $value) {
		$nameEscaped  = $mysqli->escape_string($name);
		$valueEscaped = $mysqli->escape_string($value);
		$mysqli->query("REPLACE INTO parameters (name, value) VALUES ('$nameEscaped', '$valueEscaped')") or die($mysqli->error);
	}
}

function getXyData($from, $column, $mysqli) {
	$sql = "SELECT measured_at, $column FROM time_series WHERE measured_at > '$from' ORDER BY measured_at";
	$res = $mysqli->query($sql) or die($mysqli->error);
	$data = [];
	while ($row = $res->fetch_row()) {
		$y = (float)$row[1];
		if ($column == 'heat_on') {
			$y *= 10;
		}
		$data[] = ['x'=>strtotime($row[0]), 'y'=>$y];
	}
	return $data;
}



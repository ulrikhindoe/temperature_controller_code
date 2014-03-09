<?php
require_once 'config.php';
require_once 'common_www.php';

$mysqli = initDb($config);

try {
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {

		$measuredAt  = $_POST['measuredAt'];
		$temperature = $_POST['temperature'];
		$heatOn      = $_POST['heatOn'];

		validateTimeSeriesData($measuredAt, $temperature, $heatOn);

		writeTimeseriesDataToDb($measuredAt, $temperature, $heatOn, $mysqli);
		print "OK";
	} else {
		$from = $_GET['from'];
		if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $from)) {
			throw new Exception("from has wrong format. $from");
		}
		$data = [
			[
				'name' => 'temperature',
				'data' => getXyData($from, 'temperature', $mysqli),
				'color' => 'blue',
			],
			[
				'name' => 'heat_on',
				'data' => getXyData($from, 'heat_on', $mysqli),
				'color' => 'red',
			],
		];

		header('Content-Type: application/json');
		print json_encode($data);
	}
} catch (Exception $e) {
	//print "Ups...";
	throw $e;
}

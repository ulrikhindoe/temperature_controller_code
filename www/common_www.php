<?php
require_once 'common.php';

function validateTimeSeriesData($measuredAt, $temperature, $heatOn) {
	if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $measuredAt)) {
		throw new Exception('measuredAt has invalid format');
	}
	if (!preg_match('/^-?\d+(\.\d+)?$/', $temperature)) {
		throw new Exception("temperature has invalid formatx. temperature=$temperature");
	}
	if (!preg_match('/^(0|1)$/', $heatOn)) {
		throw new Exception('heatOn has invalid format');
	}
}


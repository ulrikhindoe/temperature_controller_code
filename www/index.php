<?php
require_once 'config.php';
require_once 'common_www.php';

?><!doctype>
<html>
<head>
	<link rel="stylesheet" href="css/rickshaw.min.css">
	<link rel="stylesheet" href="css/temperature_controller.css">
	<script src="http://code.jquery.com/jquery-1.11.0.min.js"></script>
	<script src="js/d3.min.js"></script>
	<script src="js/d3.layout.min.js"></script>
	<script src="js/rickshaw.min.js"></script>
	<script src="js/underscore-min.js"></script>
	<script src="js/moment.min.js"></script>
	<script src="js/temperature_controller.js"></script>
	<script>
		var serverType = '<?= $config['serverType']?>';
	</script>
</head>
<body>
<h1>Temperature controller</h1>

<div id="chart">
	<img src="gfx/loader.gif"/>
</div>

<div id="legend">
</div>



<div id="message">Parameters were saved</div>
<div id="parameters">
	<form class="parameters">
		<fieldset>
			<legend>Parameters</legend>
			<ol>
				<li>
					<label>Heat on if lower</label>
					<input name="heat_on_if_temp_lower_than" type="number" placeholder="temperature in deg. Celcius" required>
				</li>
				<li>
					<label>Min on/off duration</label>
					<input name="min_seconds_between_heat_on_off" type="number" placeholder="seconds" required>
				</li>
			</ol>
		</fieldset>
		<fieldset>
			<button type=button>Save</button>
		</fieldset>
	</form>
</div>



</body>
</html>

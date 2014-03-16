var graph;
var graphData = [];


$(function () {
	setFormDisabled(true);
	if (serverType === 'regulatorRemotelyControlled') {
		$('form.parameters button').remove();
	} else {
		$('form.parameters button').on('click', function () {
			event.preventDefault();
			messageOn('Saving parameters...', 'ongoing');
			$.ajax({
				url: 'parameters.php',
				type: 'POST',
				data:  $('form.parameters').serialize(),
				success: function (response) {
					if (response != 'ok') {
						messageOnOff('Error saving parameters', 'failure');
						setFormDisabled(false);
						return;
					}
					setFormDisabled(false);
					$('form.parameters input[type=number]').val('');
					getParameters(function () {
						messageOnOff('Parameters saved', 'success');
					});
				}
			});
			setFormDisabled(true);
		});
	}

	getParameters();

	updateGraph();
	setInterval(function () {
		console.log('update graph');
		updateGraph();
	}, 60000);
});

$(window).resize(updateGraph);


function getParameters(callback) {
	messageOn('Loading parameters...', 'ongoing');
	$.ajax({
		url: 'parameters.php',
		dataType: 'json',
		success: function (data) {
			messageOnOff('Parameters loaded', 'success');
			for (name in data) {
				if (data.hasOwnProperty(name)) {
					$('input[name=' + name + ']').val(data[name]);
				}
			}
			if (serverType === 'regulatorRemotelyControlled') {
				setFormDisabled(true);
			} else {
				setFormDisabled(false);
			}
			if (callback) {
				callback();
			}
		}
	});
}

function setFormDisabled(disabled) {
	$('form.parameters *').prop( "disabled", disabled);
}

/**
 * @param message
 * @param type success, ongoing or failure
 */
function messageOn(message, type) {
	$("#message")
		.text(message)
		.removeClass("success ongoing failure")
		.addClass(type)
		.css('opacity', 1);
}

function messageOff() {
	$("#message")
		.delay(1000)
		.animate({opacity: 0}, 2000);
}

function messageOnOff(message, type) {
	messageOn(message, type);
	messageOff();
}

function updateGraph() {
	var fromTime = moment().subtract('hours', 24).format('YYYY-MM-DD HH:mm:ss');
	$.ajax({
		'url': 'timeSeriesData.php?from=' + encodeURIComponent(fromTime),
		error: function (e) {
			console.log('error ', e);
		},
		success: function (data) {
			$('#chart').empty();
			var height = _.max([100, Math.floor($(document.body).width()/5)]);
			if (window.devicePixelRatio > 1) {
				height *= 2;
			}

    
            graph = new Rickshaw.Graph({
				element: document.querySelector("#chart"),
				width: $(document.body).width() - 40,
				height: height,
				renderer: 'line',
				series: data
			});

            $('#legend').empty();
            var legend = new Rickshaw.Graph.Legend({
                graph: graph,
                element: document.getElementById('legend')
            });


			var timeUnitSeconds;
			switch (true) {
				case $(document.body).width() < 600:
					timeUnitSeconds = 3600 * 3;
					break;
				case $(document.body).width() < 1000:
					timeUnitSeconds = 3600 * 2;
					break;
				default:
					timeUnitSeconds = 3600;
					break;
			}

			var detail = new Rickshaw.Graph.HoverDetail({ graph: graph });
			var axes = new Rickshaw.Graph.Axis.Time({
				graph: graph,
				timeUnit: {
					seconds: timeUnitSeconds,
					formatter: function(d) {return moment(d).format('H:mm');}
				}
			});
			var y_axis = new Rickshaw.Graph.Axis.Y({
				graph: graph,
				orientation: 'right',
				tickFormat: Rickshaw.Fixtures.Number.formatKMBT,
				element: document.getElementById('y_axis')
			});

			graph.render();
		}
	});
}


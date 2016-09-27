<?php

function NextStop($code) {
	global $sites;

	$data = dibi::query('SELECT switch FROM [switches] WHERE position=%s', $code, 'ORDER BY time DESC LIMIT 1');
	if (count($data) > 0) $switch = $data->fetchSingle();
	else $switch = -1;

	if (($switch >= 0) && ($switch < count($sites[$code]["edges"]))) {
		$next = $sites[$code]["edges"][$switch];
		if ($next[0] == '*') $next = substr($next, 1);

	} else {
		// Choose randomly from right answers
		$right_answers = array();
		foreach ($sites[$code]["edges"] as $edge) {
			if ($edge[0] == '*') array_push($right_answers, substr($edge, 1));
		}
		$next = $right_answers[array_rand($right_answers)];
	}
	return array($next, $switch);
}

function DrawSwitchArrows() {
	global $sites;

	$switches = array();
	foreach($sites as $code => $site) $switches[$code] = -1;

	$data = dibi::query('SELECT position, switch FROM [switches] ORDER BY time');
	while ($row = $data->fetch()) $switches[$row["position"]] = $row["switch"];

	// Draw arrows
	echo "arrowSymbol = L.Symbol.arrowHead({pixelSize: 8, polygon: false, pathOptions: {stroke: true, className: 'directionArrow'}});\n";
	foreach ($switches as $code => $switch) {
		if (!array_key_exists($code, $sites)) continue;
		$site = $sites[$code];
		$edges = array();
		if (($switch >= 0) && ($switch < count($site["edges"]))) {
			$edge = $site["edges"][$switch];
			if ($edge[0] == '*') $edge = substr($edge, 1);
			$edges = [$edge];
		} else {
			foreach ($site["edges"] as $edge) {
				if ($edge[0] == '*') array_push($edges, substr($edge, 1));
			}
		}

		foreach ($edges as $edge) {
			$startPos = $site["position"];
			$targetPos = [
				$site["position"][0] + ($sites[$edge]["position"][0] - $site["position"][0])*0.1,
				$site["position"][1] + ($sites[$edge]["position"][1] - $site["position"][1])*0.1
			];
			echo "arrowHeadEntity = L.polylineDecorator(
				L.polyline(".json_encode([$startPos, $targetPos]).", {className: 'directionArrow'}).addTo(markersLayer)
			).addTo(markersLayer).setPatterns([{repeat: 10, symbol: arrowSymbol}]);\n";
		}
	}
}

function CheckTrain() {
	global $trainConfig;
	global $sites;
	global $reload;

	// Test if train is already started
	if (!isset($trainConfig["started"]) || !$trainConfig["started"]) return;

	// Get last entry from planned route
	$data = dibi::query('SELECT position, UNIX_TIMESTAMP(time) as time FROM [train_positions] ORDER BY time DESC LIMIT 2');

	// If train isn't started yet
	if (count($data) < 2) {
		// Start the train
		dibi::query('INSERT INTO [train_positions]',[
			"position" => $trainConfig["startFrom"],
			"time%t" => time() - 10
		]);
		$current = dibi::fetch('SELECT position, UNIX_TIMESTAMP(time) as time FROM [train_positions] ORDER BY time DESC LIMIT 1');
	} else {
		$current = $data->fetch();
		$last = $data->fetch();
	}

	// Check if the train already arrives into its current destination
	while ($current["time"] <= time()) {
		list ($next, $oldSwitch) = NextStop($current["position"]);
		if ($oldSwitch != -1) {
			dibi::query('INSERT INTO [switches]',[
				"position" => $current["position"],
				"switch" => -1
			]);
		}
		dibi::query('INSERT INTO [train_positions]',[
			"position" => $next,
			"time%t" => $current["time"] + $trainConfig["hopTime"]
		]);
		$last = $current;
		$current = dibi::fetch('SELECT position, UNIX_TIMESTAMP(time) as time FROM [train_positions] ORDER BY time DESC LIMIT 1');
	}

	// Do javascript animation
	$startTime = $last["time"];
	$targetTime = $current["time"];
	$duration = $targetTime - $startTime;
	$now = time();
	$realDuration = $targetTime - $now;
	$pos = ($now - $startTime) / $duration;

	$startPos = $sites[$last["position"]]["position"];
	$targetPos = $sites[$current["position"]]["position"];
	$currentPos = [$startPos[0]+($targetPos[0]-$startPos[0])*$pos, $startPos[1]+($targetPos[1]-$startPos[1])*$pos];

	echo "arrowSymbol = L.Symbol.arrowHead({pixelSize: 15, polygon: false, pathOptions: {stroke: true, className: 'entityPath'}});\n";
	echo "arrowHeadEntity = L.polylineDecorator(
				L.polyline(".json_encode([$startPos, $targetPos]).", {className: 'entityPath'}).addTo(markersLayer)
	).addTo(markersLayer);\n";
	// Animation:
	echo "arrowOffsetEntity= 0;
	if (typeof entityInterval !== 'undefined') {
		window.clearInterval(entityInterval);
	}
	entityInterval = window.setInterval(function() {
		arrowHeadEntity.setPatterns([{offset: (arrowOffsetEntity++ % 100), repeat: 100, symbol: arrowSymbol}])
	}, 100);\n";
	echo "L.Marker.movingMarker(".json_encode([$currentPos, $targetPos]).", [".($realDuration*1000)."], {autostart: true, icon: entityIcon}).addTo(markersLayer);\n\n";
	$reload = min($reload, $targetTime);
}

?>

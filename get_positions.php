<?php
include("config.php");
$reload = 15; // seconds

$positions = [];

$logs = dibi::query("SELECT A.* FROM (
	SELECT team, MAX(time) AS latest FROM [gps_logs] GROUP BY team
) AS B INNER JOIN [gps_logs] AS A ON A.team=B.team AND A.time=B.latest");

foreach ($logs as $log) {
	$positions[$log["team"]] = [$log["lat"], $log["lon"]];
}

echo "function addPositions() {
positions = L.layerGroup();\n";

foreach ($showPositions as $type => $elements) {
	foreach ($elements as $elem) {
		if (array_key_exists($elem, $positions)) {
			$title = "";
			$icon = "";
			switch ($type) {
			case "monsters":
				$title = "Monstrum";
				$icon = "iconMonster";
				break;
			case "targets":
				$title = "Cíl";
				$icon = "iconTarget";
				break;
			case "teams":
				$title = "Tým ".$elem;
				$icon = "iconTeam";
				break;
			}
			echo "L.marker(".json_encode($positions[$elem]).",{icon: ".$icon.", title: '".$title."'}).addTo(positions);\n";
		}
	}
}

echo "positions.addTo(map);
setTimeout(function() {
	reloadPositions();
}, ".($reload*1000).");
}\n";

//L.marker([49.9395375,13.3638904],{icon: iconTarget}).addTo(map);
//L.marker([49.9395375,13.3738904],{icon: iconMonster}).addTo(map);
?>

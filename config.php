<?php
include("dibi-connect.php");

//define('ORG_VERSION', true);
define('SHOW_POSTGAME', true);

$map_config = [
	"center" => [49.9395375, 13.3838904],
	"zoom" => 14,
	"minZoom" => 13,
	"maxZoom" => 17,
	"maxBounds" => [[49.9807839, 13.2913650],[49.8983656, 13.4722961]],
	"maxBoundsViscosity" => 1.0,
	"fullscreenControl" => true,
	"fullscreenControlOptions" => [
		"position" => 'topleft'
	]
];

$enteredCodes = dibi::query("SELECT code, UNIX_TIMESTAMP(time) AS time FROM [entered_codes]")->fetchAssoc("code");

/////////////////////////////////////////
$json = file_get_contents("config.json");
$database = json_decode($json, true);

$game_start = $database["gameStart"];
$game_start_timestamp = strtotime($game_start);

$showPositions = $database["showPositions"];
$sites = $database["sites"];

$trainConfig = $database["train"];

foreach($sites as $code => $site) {
	if(!isset($sites[$code]["edges"])) $sites[$code]["edges"] = [];
	$sites[$code]["edges_from"] = array();
}

foreach($sites as $code => $site) {
	foreach($site["edges"] as $edge) {
		if ($edge[0] == '*') $edge = substr($edge, 1);
		array_push($sites[$edge]["edges_from"], $code);
	}
}

function getGPX($team) {
	$gpx = '<?xml version="1.0" encoding="UTF-8"?>
	<gpx version="1.0">
		<name>SKSP2016 - velká noční hra</name>
		<trk><name>Trasa týmu '.$team.'</name><number>1</number><trkseg>'."\n";

		$data = dibi::query("SELECT *, UNIX_TIMESTAMP(time) AS timestamp FROM [gps_logs] WHERE team=%s",$team,'ORDER BY time');
		while ($row = $data->fetch()) {
			$gpx .= '<trkpt lat="'.$row["lat"].'" lon="'.$row["lon"].'"><ele>'.$row["id"].'</ele><time>'.date(DATE_ATOM, $row['timestamp'])."</time></trkpt>\n";
		}

	$gpx .= "	</trkseg></trk>
	</gpx>\n";

	return $gpx;
}

?>

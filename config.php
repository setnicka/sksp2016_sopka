<?php
include("dibi-connect.php");

$map_config = [
	"center" => [49.9395375, 13.3838904],
	"zoom" => 14,
	"minZoom" => 12,
	"maxZoom" => 17,
	"maxBounds" => [[49.9807839, 13.2913650],[49.8983656, 13.4722961]],
	"maxBoundsViscosity" => 1.0,
	"fullscreenControl" => true,
	"fullscreenControlOptions" => [
		"position" => 'topleft'
	]
];

$game_start = "2016-09-10 11:30";
$game_start_timestamp = strtotime($game_start);

$enteredCodes = dibi::query("SELECT code, UNIX_TIMESTAMP(time) AS time FROM [entered_codes]")->fetchAssoc("code");

$json = file_get_contents("config.json");
$database = json_decode($json, true);

$showPositions = $database["showPositions"];
$sites = $database["sites"];
?>

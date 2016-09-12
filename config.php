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

class types {const
	INFO = 0,
	BONUS = 1,
	SERIE_START = 2,
	SERIE = 3,
	MEGATASK = 4
;}

$enteredCodes = dibi::query("SELECT code, UNIX_TIMESTAMP(time) AS time FROM [entered_codes]")->fetchAssoc("code");
//$enteredCodes = [
//	"abrak" => $game_start_timestamp + 200,
//	"kombajn" => $game_start_timestamp + 2130
//];

////////////////////////////////////////////////////////////////////////////////

$showPositions = [
	"monsters" => ["B"],
	"targets" => ["C"],
	"teams" => ["D","A"]
];

$sites = [
"konik" => [
	"name" => "První stanoviště",
	"type" => types::INFO,
	"position"=> [49.9395375,13.3838904],
	"edges" => ["kombajn", "pejsek"],
	"info" => "Za třetím kmenem vlevo",
	"secret" => "Něco tajného viditelného po zadání kódu"
],
"kombajn" => [
	"name" => "Šikmá palma",
	"type" => types::SERIE_START,
	"position"=> [49.9495375,13.3838904],
	"edges" => ["konik", "pejsek"],
	"info" => "Zespoda lavičky",
	"secret" => "Něco tajného viditelného po zadání kódu",
	"visible_from_minute" => 5,
	"time_limit" => 10
],
"pejsek" => [
	"name" => "U baru",
	"type" => types::SERIE,
	"position"=> [49.9495375,13.3638904],
	"edges" => [],
	"info" => "Zespoda lavičky",
	"secret" => "Něco tajného viditelného po zadání kódu",
	"visible_after" => ["kombajn"],
	"time_limit" => 10
],
"task001" => [
	"name" => "První úkol",
	"type" => types::MEGATASK,
	"position"=> [49.9595375,13.3838904],
	"edges" => [],
	"info" => "Za třetím kmenem vlevo",
	"secret" => "XYZ",
	"time_limit" => 150
],
"bonus" => [
	"name" => "Bonus",
	"type" => types::BONUS,
	"position"=> [49.9595375,13.3838904],
	"edges" => [],
	"info" => "Za třetím kmenem vlevo",
	"secret" => "XYZ",
	"visible_after" => ["task001", "pejsek"],
],
];

?>

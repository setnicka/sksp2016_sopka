<?php
include("dibi-connect.php");

// Modified example from https://github.com/herverenault/Self-Hosted-GPS-Tracker/blob/master/server-side/gps.php

if (isset($_GET["lat"]) && preg_match("/^-?\d+\.\d+$/", $_GET["lat"])
 && isset($_GET["lon"]) && preg_match("/^-?\d+\.\d+$/", $_GET["lon"])
 && isset($_GET["team"]) && preg_match("/^\w+$/", $_GET["team"])
) {
	$args = [
		"team" => strtolower($_GET["team"]),
		"lat" => $_GET["lat"],
		"lon" => $_GET["lon"]
	];
	dibi::query("INSERT INTO [gps_logs]", $args);

	echo "OK";
} else {
	header('HTTP/1.0 400 Bad Request');
	//echo 'Please type this URL in the <a href="https://play.google.com/store/apps/details?id=fr.herverenault.selfhostedgpstracker">Self-Hosted GPS Tracker</a> Android app on your phone.';
}

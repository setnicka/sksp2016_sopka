<?php
header('Content-Type: application/javascript');
include("config.php");
include("train.php");

function IsVisible(&$site) {
	global $enteredCodes, $game_start_timestamp;

	if (defined("SHOW_POSTGAME")) return true;

	$visible_from = $game_start_timestamp;
	if (array_key_exists("visible_from_minute", $site)) {
		$site["__visible_from_timestamp"] = $game_start_timestamp + 60*$site["visible_from_minute"];
		if (defined('ORG_VERSION')) return true;
		return ($site["__visible_from_timestamp"] <= time());
//	} else if (array_key_exists("visible_after", $site)) {
//		foreach ($site["visible_after"] as $code) {
//			if (!array_key_exists($code, $enteredCodes)) return false;
//			$visible_from = max($visible_from, $enteredCodes[$code]["time"]);
//		}
//		$site["__visible_from_timestamp"] = $visible_from;
//		return true;
	} else {
		// Default: If there is edge from some visited (completed) vertex
		$reachable = false;
		foreach ($site["edges_from"] as $code) {
			if (array_key_exists($code, $enteredCodes)) {
				$reachable = true;
				$visible_from = min($visible_from, $enteredCodes[$code]["time"]);
				$site["__visible_from_timestamp"] = $visible_from;
			}
		}
		if (defined('ORG_VERSION')) return true;
		return $reachable;
	}
	if (defined('ORG_VERSION')) return true;
}

function drawArrowsFrom($site, $codeIndex) {
	global $sites;

	foreach ($site["edges"] as $i => $edge) {
		if ($edge[0] == '*') $edge = substr($edge, 1);
		$arrowIndex = $codeIndex."i".$i;
		foreach (["edge", "edgeHighlighted edgeFrom".$codeIndex] as $edgeClass) {
			echo "arrowSymbol = L.Symbol.arrowHead({pixelSize: 15, polygon: false, pathOptions: {stroke: true, className: '".$edgeClass." edgeArrow'}});\n";
			echo "arrowHead['".$arrowIndex."'] = L.polylineDecorator(
				L.polyline(".json_encode([$site["position"], $sites[$edge]["position"]]).", {className: '".$edgeClass." edgeFrom".$codeIndex."'}).addTo(markersLayer)
			).addTo(markersLayer);\n";
			echo "arrowHead['".$arrowIndex."'].setPatterns([{offset: '50%', symbol: arrowSymbol}]);\n";

			// Animation:
			//echo "arrowOffset['".$arrowIndex."'] = 0;
			//window.setInterval(function() {
			//	arrowHead['".$arrowIndex."'].setPatterns([{offset: (arrowOffset['".$arrowIndex."']++ % 100)+'%', repeat: 0, symbol: arrowSymbol}])
			//}, 100);";
		}
	}
}

$typeIconsDefault = [
	//"extraClasses" => "fa-spin",
	"shape" => "penta",
	"prefix" => "fa"
];

$typeIcons = [
	"BASIC" => array_merge($typeIconsDefault, [
		"typeName" => "Stanoviště",
		"icon" => "fa-info-circle",
		"markerColor" => "orange"
	]),
	"BONUS" => array_merge($typeIconsDefault, [
		"typeName" => "Žolík",
		"icon" => "fa-bolt",
		"markerColor" => "yellow"
	]),
	"SERIE_START" => array_merge($typeIconsDefault, [
		"typeName" => "Start série",
		"icon" => "fa-clock-o",
		"markerColor" => "blue"
	]),
	"SERIE" => array_merge($typeIconsDefault, [
		"typeName" => "Pokračování série",
		"icon" => "fa-circle-o-notch",
		"markerColor" => "blue"
	]),
	"MEGATASK" => array_merge($typeIconsDefault, [
		"typeName" => "Úkol",
		"icon" => "fa-arrows-alt",
		"markerColor" => "orange"
	]),
	"NOT_READY" => array_merge($typeIconsDefault, [
		'typeName' => "Zatím neaktivní",
		"icon" => "fa-question-circle-o",
		"markerColor" => "black"
	])
];

echo "function addMarkers() {
markersLayer = L.layerGroup();\n";

// Default reload time: 30 seconds
$reload = time() + 30;
$codeIndex = 0;

foreach($sites as $code => $site) {
	// Is visible?
	if (IsVisible($site)) {
		if (!isset($site["type"])) $site["type"] = "BASIC";
		$params = $typeIcons[$site["type"]];
		$title = $params["typeName"].": ".$site["name"];
		$info = "<h4>".$site["name"]."</h4><b>Typ stanoviště:</b>".$params["typeName"]."<br>\n<b>Pozice:</b> N".$site["position"][0].", E".$site["position"][1]."\n";
		if (isset($site["info"])) $info .= "<br><br>".$site["info"];
		$script = "map.on('popupclose', function() { $('.edgeHighlighted:visible').hide(); openedMarker=''; });\n";
		$opacity = 1.0;

		// Determine state
		if ((array_key_exists($code, $enteredCodes)) or defined("SHOW_POSTGAME")) {
			$params["extraClasses"] = "completed";
			$params["markerColor"] = "green";
			if (defined("SHOW_POSTGAME")) {
				$ii = $site['index'];
				$info .= "<br><br><b>Zobrazit stanoviště:</b>
				<a href='sopka/stanoviste/".($ii < 10 ? "0$ii" : $ii).".pdf'>[PDF]</a>\n";
			} else {
				$info .= "<br><br><b>Sebráno v:</b> ".strftime("%H:%M:%S", $enteredCodes[$code]["time"]);
			}
			$opacity = 1.0;
			if (array_key_exists("secret", $site)) {
				$info .= "<br><br><b>Tajné:</b> ".$site["secret"];
			}
		} else if (array_key_exists("time_limit", $site)) {
			if (time() - $site["__visible_from_timestamp"] <= 60*$site["time_limit"]) {
				echo "L.marker(".json_encode($site["position"]).",{icon: pulsingIcon}).addTo(markersLayer);\n";
				$reload = min($reload, $site["__visible_from_timestamp"] + 60*$site["time_limit"]);
				$to_microseconds = ($site["__visible_from_timestamp"] + 60*$site["time_limit"]) * 1000;
				$info .= "<br><b>Zbývající čas:</b> <script>alert(1);</script> <span id='countdown".$codeIndex."'></span>";
				$script .= 'map.on("popupopen", function() {$("#countdown'.$codeIndex.'").countdown({until: new Date('.$to_microseconds."), layout: '{mnn}:{snn}'}); })\n\n";
			} else {
				$params["extraClasses"] = "losed";
				$params["markerColor"] = "black";
				$opacity = 0.5;
				$info .= "<br><br><b>Čas vypršel v:</b> ".strftime("%H:%M:%S", $site["__visible_from_timestamp"] + 60*$site["time_limit"]);
			}
		}

		echo "info = ".json_encode($info).";\n";
		echo "marker[".$codeIndex."] = L.marker(".json_encode($site["position"]).",{
			title: '".$title."', opacity: ".$opacity.", icon: L.ExtraMarkers.icon(\n\t\t\t".json_encode($params)."\n)
		}).addTo(markersLayer);\n";
		echo "marker[".$codeIndex."].bindPopup(info);\n";
		echo "marker[".$codeIndex."].on('click', function() { openedMarker = ".$codeIndex."; $('.edgeFrom".$codeIndex."').show(); });";

		if (defined('ORG_VERSION') || array_key_exists($code, $enteredCodes))
			drawArrowsFrom($site, $codeIndex);

		echo "\n\n";
		echo $script;

	}
	$codeIndex++;
}
CheckTrain();
if (defined('ORG_VERSION')) DrawSwitchArrows();

echo "setTimeout(function() {
	if (openedMarker != '') { marker[openedMarker].fireEvent('click'); }
}, 500);\n";

echo "markersLayer.addTo(map);
setTimeout(function() {
	reloadMarkers();
}, ".(($reload - time())*1000).");
}\n";
?>

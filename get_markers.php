<?php
header('Content-Type: application/javascript');
include("config.php");

function IsVisible(&$site) {
	global $enteredCodes, $game_start_timestamp;

	if (array_key_exists("visible_from_minute", $site)) {
		$site["__visible_from_timestamp"] = $game_start_timestamp + 60*$site["visible_from_minute"];
		return ($site["__visible_from_timestamp"] <= time());
	} else if (array_key_exists("visible_after", $site)) {
		$visible_from = $game_start_timestamp;
		foreach ($site["visible_after"] as $code) {
			if (!array_key_exists($code, $enteredCodes)) return false;
			$visible_from = max($visible_from, $enteredCodes[$code]["time"]);
		}
		$site["__visible_from_timestamp"] = $visible_from;
		return true;
	} else {
		$site["__visible_from_timestamp"] = $game_start_timestamp;
		return true;
	}
}

$typeIconsDefault = [
	"extraClasses" => "fa-spin",
	"shape" => "penta",
	"prefix" => "fa"
];

$typeIcons = [
	"INFO" => array_merge($typeIconsDefault, [
		"typeName" => "Informace",
		"icon" => "fa-info-circle",
		"markerColor" => "blue"
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
		"typeName" => "Megaúkol",
		"icon" => "fa-arrows-alt",
		"markerColor" => "blue"
	])
];

echo "function addMarkers() {
markersLayer = L.layerGroup();\n";

// Default reload time: 1 minute
$reload = time() + 60;
$codeIndex = 0;
foreach($sites as $code => $site) {
	// Is visible?
	if (IsVisible($site)) {
		$params = $typeIcons[$site["type"]];
		$title = $params["typeName"].": ".$site["name"];
		$info = "<h4>".$site["name"]."</h4><b>Typ stanoviště:</b>".$params["typeName"]."<br><br>".$site["info"];
		$script = "map.on('popupclose', function() { $('.edgeHighlighted').hide(); openedMarker=''; });\n";
		$opacity = 1.0;

		// Determine state
		if (array_key_exists($code, $enteredCodes)) {
			$params["extraClasses"] = "completed";
			$params["markerColor"] = "green";
			$info .= "<br><br><b>Sebráno v:</b> ".strftime("%H:%M:%S", $enteredCodes[$code]["time"]);
			$opacity = 0.5;
			if (array_key_exists("secret", $site)) {
				$info .= "<br><br><b>Tajné:</b> ".$site["secret"];
			}
		} else if (array_key_exists("time_limit", $site)) {
			if (time() - $site["__visible_from_timestamp"] <= 60*$site["time_limit"]) {
				echo "L.marker(".json_encode($site["position"]).",{icon: pulsingIcon}).addTo(markersLayer);\n";
				$reload = min($reload, $site["__visible_from_timestamp"] + 60*$site["time_limit"]);
				$to_microseconds = ($site["__visible_from_timestamp"] + 60*$site["time_limit"]) * 1000;
				$info .= "<br><b>Zbývající čas:</b> <script>alert(1);</script> <span id='countdown".$index."'></span>";
				$script .= 'map.on("popupopen", function() {$("#countdown'.$index.'").countdown({until: new Date('.$to_microseconds."), layout: '{mnn}:{snn}'}); })\n\n";
			} else {
				$params["extraClasses"] = "losed";
				$params["markerColor"] = "black";
				$opacity = 0.5;
				$info .= "<br><br><b>Čas vypršel v:</b> ".strftime("%H:%M:%S", $site["__visible_from_timestamp"] + 60*$site["time_limit"]);
			}
		}

		echo "info = ".json_encode($info).";\n";
		echo "marker[".$codeIndex."] = L.marker(".json_encode($site["position"]).",{
			title: '".$title."', opacity: ".$opacity.", icon: L.ExtraMarkers.icon(\n".json_encode($params)."\n)
		}).addTo(markersLayer);\n";
		echo "marker[".$codeIndex."].bindPopup(info);\n";
		echo "marker[".$codeIndex."].on('click', function() { openedMarker = ".$codeIndex."; $('.edgeFrom".$codeIndex."').show(); });";

		foreach ($site["edges"] as $i => $edge) {
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

		echo "\n\n";
		echo $script;

	}
	$codeIndex++;
}

echo "setTimeout(function() {
	if (openedMarker != '') { marker[openedMarker].fireEvent('click'); }
}, 200);\n";

echo "markersLayer.addTo(map);
setTimeout(function() {
	reloadMarkers();
}, ".(($reload - time())*1000).");
}\n";
?>

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
	types::INFO => array_merge($typeIconsDefault, [
		"typeName" => "Informace",
		"icon" => "fa-info-circle",
		"markerColor" => "blue"
	]),
	types::BONUS => array_merge($typeIconsDefault, [
		"typeName" => "Žolík",
		"icon" => "fa-bolt",
		"markerColor" => "yellow"
	]),
	types::SERIE_START => array_merge($typeIconsDefault, [
		"typeName" => "Start série",
		"icon" => "fa-clock-o",
		"markerColor" => "blue"
	]),
	types::SERIE => array_merge($typeIconsDefault, [
		"typeName" => "Pokračování série",
		"icon" => "fa-circle-o-notch",
		"markerColor" => "blue"
	]),
	types::MEGATASK => array_merge($typeIconsDefault, [
		"typeName" => "Megaúkol",
		"icon" => "fa-arrows-alt",
		"markerColor" => "blue"
	])
];

echo "function addMarkers() {
markers = L.layerGroup();

arrowSymbol = L.Symbol.arrowHead({pixelSize: 15, polygon: false, pathOptions: {stroke: true, className: 'edge'}});\n";

// Default reload time: 1 minute
$reload = time() + 60;
foreach($sites as $code => $site) {
	// Is visible?
	if (IsVisible($site)) {
		$params = $typeIcons[$site["type"]];
		$title = $params["typeName"].": ".$site["name"];
		$info = "<h4>".$site["name"]."</h4><b>Typ stanoviště:</b>".$params["typeName"]."<br><br>".$site["info"];
		$script = "";
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
				echo "L.marker(".json_encode($site["position"]).",{icon: pulsingIcon}).addTo(markers);\n";
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
		echo "L.marker(".json_encode($site["position"]).",{title: '".$title."', opacity: ".$opacity.", icon: L.ExtraMarkers.icon(\n".json_encode($params)."\n)}).addTo(markers).bindPopup(info);\n\n";

		foreach ($site["edges"] as $i => $edge) {
			echo "var arrowHead".$code.$i." = L.polylineDecorator(
				L.polyline(".json_encode([$site["position"], $sites[$edge]["position"]]).", {className: 'edge'}).addTo(markers)
			).addTo(markers);\n";
			echo "arrowHead".$code.$i.".setPatterns([{offset: '50%', symbol: arrowSymbol}]);\n";


			echo "var arrowOffset".$code.$i." = 0;
			window.setInterval(function() {
			arrowHead".$code.$i.".setPatterns([
			    {offset: arrowOffset".$code.$i."+'%', repeat: 0, symbol: arrowSymbol}
			])
			if(++arrowOffset".$code.$i." > 100)
			    arrowOffset".$code.$i." = 0;
			}, 100);";

		}

		echo "\n\n";
		echo $script;
	}
}

echo "markers.addTo(map);
setTimeout(function() {
	reloadMarkers();
}, ".(($reload - time())*1000).");
}\n";
?>

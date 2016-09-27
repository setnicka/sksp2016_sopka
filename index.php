<?php
session_start();

if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])
|| $_SERVER['PHP_AUTH_USER'] != "ksp" || $_SERVER['PHP_AUTH_PW'] != "aloha") {
	header('WWW-Authenticate: Basic realm="Přístup jen pro centrálu"');
	header('HTTP/1.0 401 Unauthorized');
	echo 'Přístupné jen pro centrálu.';
	exit;
}

	include("config.php");

	if (isset($_POST["code"]) && preg_match("/^\w+$/", $_POST["code"])) {
		$row = dibi::fetch("SELECT code, UNIX_TIMESTAMP(time) AS time FROM [entered_codes] WHERE code=%s", $_POST["code"]);
		if ($row != NULL) {
			$_SESSION["msgtype"]='danger';
			$_SESSION["msg"]='Tento kód byl již zadán v '.strftime("%H:%M:%S", $row['time']);
		} else {
			if (array_key_exists($_POST["code"], $sites)) {
				dibi::query("INSERT IGNORE INTO [entered_codes]", [
					"code" => $_POST["code"]
				]);
				$_SESSION["msgtype"]='success';
				$_SESSION["msg"]='Kód přijat';
			} else {
				$_SESSION["msgtype"]='danger';
				$_SESSION["msg"]='Neznámý kód';
			}
		}
		header('Location: ./');
		exit;
	}
?>
<!DOCTYPE html>
<html lang="cs">
<head>
	<meta charset="utf-8">
	<title>HawaiiMap</title>

	<!-- jQuery -->
	<script src="js/jquery.js"></script>
	<script src="js/jquery.plugin.min.js"></script>
	<script src="js/jquery.countdown.min.js"></script>

	<!-- Bootstrap CSS + JavaScript -->
	<script src="js/bootstrap.min.js"></script>
	<link rel="stylesheet" href="css/bootstrap.min.css">

	<!-- Leaflet with plugins -->
	<script src="js/leaflet.js"></script>
	<link rel="stylesheet" href="css/leaflet.css" />

	<script src="js/L.Icon.Pulse.js"></script>
	<link rel="stylesheet" href="css/L.Icon.Pulse.css" />

	<link rel="stylesheet" href="css/font-awesome.min.css">
	<link rel="stylesheet" href="css/leaflet.extra-markers.min.css">
	<script src="js/leaflet.extra-markers.min.js"></script>
	<link rel="stylesheet" href="css/Control.FullScreen.css" />
	<script src="js/Control.FullScreen.js"></script>
	<script src="js/leaflet.polylineDecorator.js"></script>
	<script type="text/javascript" src="js/MovingMarker.js"></script>

	<script src="js/gpx.js" type="text/javascript"></script>

	<link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php
	if (isset($_GET['team'])) {
		echo "Statistiky pro <b>tým ".$_GET['team']."</b>:<ul>
			<li>Vzdálenost: <span id='stat_distance'></span>m
			<li>Celkový čas: <span id='stat_total_time'></span> minut
			<li>Průměrná rychlost: <span id='stat_speed'></span> km/h
		</ul>\n";
	} else {
		echo "<h1 class='text-center'>HawaiiMap</h1>\n";
	}

	if (isset($_SESSION["msgtype"])) {
		echo "<div class='alertbox'><div class='alert alert-".$_SESSION["msgtype"]." fade in'>
		<a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a><strong>".$_SESSION["msg"]."</strong>
		</div></div>\n";
		unset($_SESSION["msgtype"]);
	}


//	<div id="communicator">
//		<h3>Odeslat kód</h3>
//		<form method="post">
//			<input size="14" type="text" name="code"><br>
//			<input type="submit" value="Odešli">
//		</form>
//	</div>
?>
	<div id="mapa" style="height: 600px;"></div>

	<script  type="text/javascript">
		var map = L.map('mapa', <?php echo json_encode($map_config) ?>);

		L.tileLayer('http://m{s}.mapserver.mapy.cz/turist_trail_bike-m/{z}-{x}-{y}', {
			attribution: "<img src='http://mapy.cz/img/logo-small.svg' /> © Seznam.cz,a.s, © Přispěvatelé <a href='http://www.openstreetmap.org/copyright'>OpenStreetMap</a>, © NASA",
			subdomains: "1234",
		}).addTo(map);

		var iconMonster = L.icon.pulse({
			iconSize:[10,10],
			color:'red',
			heartbeat: 0.4
		});
		var iconTarget = L.icon.pulse({
			iconSize:[10,10],
			color:'purple',
			heartbeat: 0.4
		});
		var iconTeam = L.icon.pulse({
			iconSize:[10,10],
			color:'blue',
			heartbeat: 0.6
		});

		var pulsingIcon = L.icon.pulse({
			iconSize:[20,10],
			color:'#444444',
			heartbeat: 2
		});

		var entityIcon = L.ExtraMarkers.icon({
			icon: 'fa-eye',
			shape: 'circle',
			prefix: 'fa'
		});

		var openedMarker = "";
		var marker = [];
		var arrowHead = [];
		var arrowOffset = [];
		markersLayer = L.layerGroup();
		function reloadMarkers() {
			$.ajax({
				url: 'get_markers.php',
				dataType: 'script',
				success: function() {
					map.removeLayer(markersLayer)
					addMarkers();
				}
			});
		}
		reloadMarkers();

<?php
if (isset($_GET['team'])) {
	$gpx = getGPX($_GET['team']);
	echo "gpx=".json_encode($gpx)."\n";

	echo "new L.GPX(gpx, {async: true}).on('loaded', function(e) {
		jQuery('#stat_distance').html(Math.round(e.target.get_distance()));
		jQuery('#stat_total_time').html(Math.round(e.target.get_total_time()/1000/60));
		jQuery('#stat_speed').html(Math.round(e.target.get_total_speed()));
	}).addTo(map);";
} else {
echo "		positions = L.layerGroup();
		function reloadPositions() {
			$.ajax({
				url: 'get_positions.php',
				dataType: 'script',
				success: function() {
					map.removeLayer(positions)
					addPositions();
				}
			});
		}
		reloadPositions();\n";
}
?>


	</script>
</body>
</html>

<?php
include("config.php");

$code="";
$validCode = false;
if (isset($_REQUEST["code"]) && preg_match("/^\w+$/", $_REQUEST["code"])) {
	$code = $_REQUEST["code"];

	// HACK
	if ($code == "palice") $code = "selmy";
	else if ($code =="selmy") $code = "palice";


	$row = dibi::fetch("SELECT code, UNIX_TIMESTAMP(time) AS time FROM [entered_codes] WHERE code=%s", $code);
	if ($row != NULL) {
		$message = 'Toto stanoviště bylo objeveno a zaznamenáno v '.strftime("%H:%M:%S", $row['time']).'.';
		$validCode = true;
	} else {
		if (array_key_exists($code, $sites)) {
			dibi::query("INSERT IGNORE INTO [entered_codes]", [
				"code" => $code
			]);
			$message = 'Kód přijat.';
			$validCode = true;
		} else {
			$message = 'Neznámý kód.';
		}
	}

	if ($trainConfig["started"]) {

		if (isset($_POST["switch"]) && preg_match("/^\d+$/", $_POST["switch"])) {
			$switch = intval($_POST["switch"]);
			dibi::query('INSERT INTO [switches]',[
				"position" => $code,
				"switch" => $switch
			]);
			$target = $sites[$code]["edges"][$switch];
			if ($target[0] == '*') $target = substr($target, 1);
			$message = "Výhybka na chviličku nastavena na směr <b>".$sites[$target]["name"]."</b>.";
		}
	}
}

?>
<!DOCTYPE html>
<html lang="cs">
<head>
	<meta charset="utf-8">
	<title>HawaiiMap - zadávač kódů</title>
</head>
<body>
<?php
	if ($validCode) {
		echo "<b>Stanoviště:</b> ".$sites[$code]["name"]."<br>\n".$message;
	} else if ($code != "") {
		echo "<b>Kód:</b> ".$code." - ".$message;
	} else {
		echo "<form method='post'>Kód:<input type='text' name='code'><input type='submit' value='Odeslat'></form>\n";
	}

	if ($validCode && $trainConfig["started"]) {
		echo "<hr>Zvolte nasměrování výhybky:<br><form method='post'><input type='hidden' name='code' value='".$code."'>\n";
		foreach($sites[$code]["edges"] as $i => $target) {
			if ($target[0] == '*') $target = substr($target, 1);
			echo "<input type='radio' name='switch' value='".$i."'>".$sites[$target]["name"]."<br>\n";
		}
		echo "<input type='submit' value='Nastav'></form>\n";
	}
?>
</body>
</html>

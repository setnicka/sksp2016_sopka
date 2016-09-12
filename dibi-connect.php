<?php
date_default_timezone_set("Europe/Prague");

require_once ('libs/dibi.min.php');

$whitelist = array('localhost', '127.0.0.1');

if(in_array($_SERVER['HTTP_HOST'], $whitelist)){
	dibi::connect(array(
		'driver'   => 'mysqli',
		'host'     => 'localhost',
		'username' => 'dbuser',
		'password' => '',
		'database' => 'sksp2016_nocni_hra',
		'charset'  => 'utf8',
	));
}
else
{
	dibi::connect(array(
		'driver'   => 'mysqli',
		'host'     => '127.0.0.1',
		'username' => 'dbuser',
		'password' => '',
		'database' => 'sksp2016_nocni_hra',
		'charset'  => 'utf8',
	));
}
?>

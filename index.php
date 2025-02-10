<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require __DIR__ . '/vendor/autoload.php'; // Charge l'autoload de Composer
use VSC\API\ViruscampAPI;
$ViruscampAPI = new ViruscampAPI();
$ViruscampAPI->getUserInfo();
	echo "hello jhoddn";
?>


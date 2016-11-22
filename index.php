<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \app\models\User.php as User;

require '../vendor/autoload.php';

function connect_db() {
	$server = 'localhost';
	$user = 'root';
	$pass = '';
	$database = 'testdb2';
	$connection = new mysqli($server, $user, $pass, $database);

	return $connection;
}

$app = new \Slim\App;

$app->run();

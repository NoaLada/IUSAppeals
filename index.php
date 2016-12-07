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

$app->add(new \Slim\Middleware\SessionCookie(array(
    'expires' => '20 minutes',
    'path' => '/',
    'domain' => null,
    'secure' => false,
    'httponly' => false,
    'name' => 'slim_session',
    'secret' => 'azreczaidec',
    'cipher' => MCRYPT_RIJNDAEL_256,
    'cipher_mode' => MCRYPT_MODE_CBC
)));

$app->run();

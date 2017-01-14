<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use \Slim\App;

require '../vendor/autoload.php';
require_once('fpdf.php');
require_once('fpdi.php');

function connect_db() {
	$server = 'localhost';
	$user = 'root';
	$pass = '';
	$database = 'testdb2';
	$connection = new mysqli($server, $user, $pass, $database);

	return $connection;
}

$app = new App();

$app->add(new \Slim\Middleware\SessionCookie(array(
    'expires' => '10 minutes',
    'path' => '/',
    'domain' => null,
    'secure' => false,
    'httponly' => false,
    'name' => 'session_storage',
    'secret' => 'azreczaidec',
    'cipher' => MCRYPT_RIJNDAEL_256,
    'cipher_mode' => MCRYPT_MODE_CBC
)));

$app->run();

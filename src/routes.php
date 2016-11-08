<?php

use src\Models\User;
use src\Controllers\UserController;

$app->get('/', function ($request, $response, $args) {
    return $this->renderer->render($response, 'index.phtml', $args);
});

$app->post('/api/authenticate', function ($request, $response, $args) {
    $json = json_decode($request->getBody(), true);

    $username = (string) $json['username'];
    $typedpassword = (string) $json['password'];
    $password = md5($typedpassword);

    $db = connect_db();
    $sql = 'SELECT * FROM user WHERE password = "'.$password.'" AND (id = "'.$username.'" OR email = "'.$username.'")';
    
    $result = $db->query($sql);
    
    $success = false;
    if ($result === false) {
        echo "MYSQL Error: ".mysqli_error($db);
    } else {
        $success = mysqli_num_rows($result) != 0;
    }
    
    $final = array('success' => $success, 'json' => $json);

    return $response->withJson($final);
});

$app->get('/login', function ($request, $response, $args) {
    return $this->renderer->render($response, 'login.phtml', $args);
});

function connect_db() {
    $server = 'localhost:3306';
    $user = 'root';
    $pass = '';
    $database = 'testdb';
    $connection = new mysqli($server, $user, $pass, $database);

    return $connection;
}
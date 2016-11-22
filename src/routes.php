<?php
use src\Models\User;
use src\Controllers\UserController;

$app->get('/', function ($request, $response, $args) {
    return $this->renderer->render($response, 'index.phtml', $args);
});

$app->post('/api/authenticate', function ($request, $response, $args) {
    $json = json_decode($request->getBody(), true);
    $success = false;
    $error = "";

    if (isset($json['username']) && isset($json['password'])) {
        $username = (string) $json['username'];
        $typedpassword = (string) $json['password'];

        $db = connect_db();

        if ($db != NULL) {
            $sql = 'SELECT salt FROM user WHERE id = "'.$username.'" OR email = "'.$username.'"';
            $result = $db->query($sql);

            if ($result->num_rows === 0) {
                $error = "User does not exist in database.";
            } else {
                $user = $result->fetch_assoc();
                $salt = $user['salt'];

                if ($salt === '1') {
                    $salt = generateSalt();
                    $error = $salt."</br>".generateHash($typedpassword, $salt);
                } else {
                    $password = generateHash($typedpassword, $salt);

                    $sql = 'SELECT * FROM user WHERE password = "'.$password.'" AND (id = "'.$username.'" OR email = "'.$username.'")';

                    $result = $db->query($sql);

                    if ($result === false) {
                        echo "MYSQL Error: ".mysqli_error($db);
                    } else if ($result->num_rows === 0) {
                        $error = "Wrong username/password combination.";
                    } else {
                        $success = true;
                    }
                }
            }
        } else {
            $error = "Server database is offline.";
        }
    } else {
        $error = "Information missing.";
    }

    return $response->withJson(array('success' => $success, 'message' => $error));
});

$app->post('/api/appeals', function ($request, $response, $args) {
    $json = json_decode($request->getBody(), true);
    $success = false;
    $error = "";

    if (isset($json['text']) && isset($json['user'])) {
        $id = $json['user'];
        $text = $json['text'];

        $sql = "INSERT INTO appeals (user_id, appeal_id, time, text) VALUES ('.$id.', NULL, CURRENT_TIMESTAMP, '".$text."');";
        $db = connect_db();
        $result = $db->query($sql);

        if ($result === false) {
            $error = "Could not add appeal to the database.";
        } else {
            $success = true;
        }
    } else {
        $error = "Missing information";
    }

    return $response->withJson(array('success' => $success, 'message' => $error));
});

$app->delete('/api/appeals', function ($request, $response, $args) {
    $json = json_decode($request->getBody(), true);
    $success = false;
    $error = "";

    if (isset($json['appeal_id'])) {
        $id = $json['appeal_id'];

        $sql = "DELETE FROM appeals WHERE appeal_id = ".$id.";";
        $db = connect_db();
        $result = $db->query($sql);

        if ($result === false) {
            $error = "Could not delete appeal from the database.";
        } else {
            $success = true;
        }
    } else {
        $error = "Missing information";
    }

    return $response->withJson(array('success' => $success, 'message' => $error));
});

$app->get('/api/appeals/user/{id}', function ($request, $response, $args) {
    return getAppealsInJSON($args['id'], 0, $response);
});

$app->get('/api/appeals/appeal/{id}', function ($request, $response, $args) {
    return getAppealsInJSON(0, $args['id'], $response);
});

$app->get('/login', function ($request, $response, $args) {
    return $this->renderer->render($response, 'login.phtml', $args);
});

function connect_db() {
    $server = 'localhost:3306';
    $user = 'root';
    $pass = '';
    $database = 'testdb2';
    $connection = new mysqli($server, $user, $pass, $database);

    return $connection;
}

function generateSalt() {
    return substr(uniqid(mt_rand(), true), 0, 12);
}

function generateHash($password, $salt) {
    return hash("sha256", $password.$salt);
}

function getAppealsInJSON($user_id, $appeal_id, $response) {
    $error = "";
    $data = "";

    if ($user_id != 0 || $appeal_id != 0) {
        if ($user_id != 0) {
            $sql = "SELECT * FROM appeals WHERE user_id = '".$user_id."'";
        } else {
            $sql = "SELECT * FROM appeals WHERE appeal_id = '".$appeal_id."'";
        }

        $db = connect_db();
        $result = $db->query($sql);

        if ($result === false) {
            $error = "Could not get appeal from database.";
        } else if ($result->num_rows === 0) {
            $error = "No appeals found.";
        } else {
            $data = array();
            while($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }

            return $response->withJson($data);
        }
    } else {
        $error = "Missing information";
    }

    return $response->withJson(array('success' => false, 'message' => $error));
}

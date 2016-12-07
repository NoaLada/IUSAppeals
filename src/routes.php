<?php
use src\Models\User;
use src\Controllers\UserController;

const ADMIN = 1;
const STANDARD = 0;

$app->get('/', function ($request, $response, $args) {
    return $this->renderer->render($response, 'index.phtml', $args);
});

$app->post('/api/authenticate', function ($request, $response, $args) {
    $json = json_decode($request->getBody(), true);

    $success = false;
    $error = "";
    $user_type = -1;
    $security_key = "";

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

                        $datasql = $result->fetch_assoc();
                        $user_type = $datasql['isAdmin'];

                        $security_key = "k".generateSalt();

                        $_SESSION[$security_key] = $user_type;
                    }
                }
            }
        } else {
            $error = "Server database is offline.";
        }
    } else {
        $error = "Information missing.";
    }

    return $response->withJson(array('success' => $success, 'message' => $error, 'user_type' => $user_type, 
        'security_key' => $_SESSION));
});

$app->get('/api/user/{id}', function ($request, $response, $args) {
    if (!check_key($request, STANDARD)) {
        return $response->withJson(array('success' => false, 'message' => "Access denied!", 'keys' => $_SESSION));
    }

    $sql = "SELECT * FROM user WHERE id = '".$args['id']."'";

    $db = connect_db();
    $result = $db->query($sql);

    if ($result === false) {
        $error = "Could not get user from database.";
    } else if ($result->num_rows === 0) {
        $error = "No users found.";
    } else {
        $data = array();
        while($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        return $response->withJson(array('success' => true, 'data' => $data[0]));
    }

    return $response->withJson(array('success' => false, 'message' => $error));
});


$app->post('/api/appeals', function ($request, $response, $args) {
    if (!check_key($request, STANDARD)) {
        return $response->withJson(array('success' => false, 'message' => "Access denied!"));
    }

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

$app->get('/api/appeals', function ($request, $response, $args) {
    if (!check_key($request, ADMIN)) {
        return $response->withJson(array('success' => false, 'message' => "Access denied!", 'keys' => $_SESSION));
    }

    $sql = "SELECT * FROM appeals";

    $db = connect_db();
    $result = $db->query($sql);

    if ($result === false) {
        $error = "Could not get appeals from database.";
    } else if ($result->num_rows === 0) {
        $error = "No appeals found.";
    } else {
        $data = array();
        while($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }

        return $response->withJson($data);
    }

    return $response->withJson(array('success' => false, 'message' => $error));
});

$app->delete('/api/appeals', function ($request, $response, $args) {
    if (!check_key($request, ADMIN)) {
        return $response->withJson(array('success' => false, 'message' => "Access denied!"));
    }

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
    if (!check_key($request, ADMIN)) {
        return $response->withJson(array('success' => false, 'message' => "Access denied!"));
    }

    return getAppealsInJSON($args['id'], 0, $response);
});

$app->get('/api/appeals/appeal/{id}', function ($request, $response, $args) {
    if (!check_key($request, STANDARD)) {
        return $response->withJson(array('success' => false, 'message' => "Access denied!"));
    }

    return getAppealsInJSON(0, $args['id'], $response);
});

$app->get('/login', function ($request, $response, $args) {
    return $this->renderer->render($response, 'login.phtml', $args);
});

function check_key($request, $type) {
    return true; // TODO TEMP

    // Key not provided
    if (!$request->hasHeader('key')) {
        return false;
    }

    $key = $request->getHeader('key')[0];

    // Key does not exist
    if (!isset($_SESSION[$key])) {
        return false;
    }

    // Admin has all privileges
    if ($_SESSION[$key] == ADMIN) {
        return true;
    }

    // If looking for standard route and the user is standard
    if ($type == STANDARD && $_SESSION[$key] == STANDARD) {
        return true;
    }

    return false;
}

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

<?php
use src\Models\User;
use src\Controllers\UserController;
const ADMIN = 1;
const STANDARD = 0;

$app->get('/download.pdf', function($request, $response, $args) {
    $file = 'Thesis.pdf';

    $response = $response   ->withHeader('Content-Type', 'application/pdf')
                            ->withHeader('Content-Disposition', 'inline; filename="' .basename("$file") . '"')
                            ->withHeader('Content-Transfer-Encoding', 'binary')
                            ->withHeader('Expires', '0')
                            ->withHeader('Cache-Control', 'must-revalidate')
                            ->withHeader('Pragma', 'public')
                            ->withHeader('Content-Length', filesize($file));

    readfile($file);
    return $response;
});

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
                        $_SESSION[$security_key."u"] = $username;
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
        'security_key' => $security_key, 'test' => $_SESSION));
});

$app->get('/api/user/{id}/{key}', function ($request, $response, $args) {
    if (!isset($args['key']) || $_SESSION[$args['key']."u"] != $args['id']) {
        return $response->withJson(array('success' => false, 'message' => "Access denied!", 'test' => $args['key']));
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
    $json = json_decode($request->getBody(), true);
    if (!check_key($request, STANDARD)) {
        return $response->withJson(array('success' => false, 'message' => "Access denied!", 'test' => $json['key']));
    }

    $success = false;
    $error = "";
    if (isset($json['text']) && isset($json['user'])) {
        $id = $json['user'];
        $text = json_encode($json['text']); // $text is JSON, but I have to convert it to String
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
        return $response->withJson(array('success' => false, 'message' => "Access denied!"));
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
    if ($request->hasHeader('key') &&
        $_SESSION[$request->getHeader('key')[0]."u"] == $args['id']) {
        return getAppealsInJSON($args['id'], 0, $response);
    }

    if (check_key($request, ADMIN)) {
        return getAppealsInJSON($args['id'], 0, $response);
    }
    return $response->withJson(array('success' => false, 'message' => "Access denied!"));
});

$app->get('/api/appeals/appeal/{id}', function ($request, $response, $args) {
    if ($request->hasHeader('key') &&
        $_SESSION[$request->getHeader('key')[0]."u"] == $args['id']) {
        return getAppealsInJSON($args['id'], 0, $response);
    }
    if (check_key($request, ADMIN)) {
        return getAppealsInJSON($args['id'], 0, $response);
    }
    return $response->withJson(array('success' => false, 'message' => "Access denied!"));
});

$app->get('/api/appeals/pdf/{id}', function ($request, $response, $args) {
    /*if (!($request->hasHeader('key') &&
        $_SESSION[$request->getHeader('key')[0]."u"] == $args['id']) && !check_key($request, ADMIN)) {
        return $response->withJson(array('success' => false, 'message' => "Access denied!"));
    }*/

    if (!isset($args['id'])) {
        return $response->withJson(array('success' => false, 'message' => "Missing information!")); 
    }

    $sql = "SELECT * FROM appeals WHERE appeal_id = '".$args['id']."'";
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

        $appeal = $data[0];
        $adata = json_decode($appeal['text'], true);
        $pdf = new FPDI();      
        if ($appeal['type'] > 0 && $appeal['type'] < 6) {
            $pdf->setSourceFile("appeals/appeal".$appeal['type'].".pdf");
        }

        $page = $pdf->importPage(1, '/MediaBox');
        $pdf->addPage();
        $pdf->useTemplate($page, 0, 0, 0, 0, true); 
        $pdf->SetFont('Arial');
        $pdf->SetTextColor(0, 0, 0);

        insert_data($pdf, $adata, $appeal['type']);

        ob_end_clean();
        $pdf->Output();
    }
    
});

function insert_data($pdf, $adata, $type) {
    if ($type == 1) {
        $pdf->SetXY(68, 81);
        $pdf->Write(0, $adata['firstname'].' '.$adata['lastname']);
        $pdf->SetXY(25, 112);
        $pdf->MultiCell(160, 124, $adata['text'], 'T', 'L', false);
        $pdf->SetXY(68, 95);
        $pdf->Write(0, $adata['department']."/".$adata['faculty']);
        $pdf->SetXY(163, 95);
        $pdf->Write(0, $adata['CGPA']);
        $pdf->SetXY(146, 258);
        $pdf->Write(0, $adata['telephone']);
    } else if (type == 2) {
        // TODO
    } else if (type == 3) {
        // TODO
    } else if (type == 4) {
        // TODO
    } else if (type == 5) {
        // TODO
    }    
}

$app->get('/login', function ($request, $response, $args) {
    return $this->renderer->render($response, 'login.phtml', $args);
});

$app->get('/api/conf', function ($request, $response, $args) {
    if ($request->hasHeader('key') && $request->hasHeader('id')) {
        $success = false;
        $id = $request->hasHeader('id')[0];
        $key = $request->hasHeader('key')[0];

        $sql = "SELECT user_id, appeal_id, sign_person_id, salt FROM signatures,user WHERE signature_id=".$id." AND user.id = signatures.user_id";
        $db = connect_db();
        $result = $db->query($sql);

        if ($result->num_rows === 0) {
            $error = "No such appeal.";
        } else {
            $data = array();
            while($row = mysqli_fetch_assoc($result)) {
                $data[] = $row;
            }

            $user_id = $data['user_id'];
            $appeal_id = $data['appeal_id'];
            $sperson_id = $data['sign_person_id'];
            $user_salt = $data['salt'];

            $hash = generate_confirmation_hash($id, $user_id, $appeal_id, $sperson_id, $user_salt);

            if ($hash == $key) {
                $sql = "UPDATE signatures SET has_signed=1 WHERE signature_id=".$id.";";
                $db = connect_db();
                $result = $db->query($sql);
                if ($result === false) {
                    $error = "Could not confirm the link.";
                } else {
                    $success = true;
                }
            } else {
                $error = "Wrong confirmation link.";
            }
        }

        return $response->withJson(array('success' => $success, 'message' => $error));
    }

    return $response->withJson(array('success' => false, 'message' => "Missing information!"));
});

function check_key($request, $type) {
    $json = json_decode($request->getBody(), true);

    // Key not provided
    if (!isset($json['key'])) {
        return false;
    }
    $key = $json['key'];
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

function generate_confirmation_link($signature_id, $user_id, $appeal_id, $sperson_id, $user_salt) {
    $hash = generate_confirmation_hash($signature_id, $user_id, $appeal_id, $sperson_id, $user_salt);
   
    return "http://localhost/api/conf/?id=".$signature_id."&key=".$hash;
}

function generate_confirmation_hash($signature_id, $user_id, $appeal_id, $sperson_id, $user_salt) {
    return hash("sha256", $signature_id.":)".$user_id."<3".$appeal_id.":)".$sperson_id."<3".$user_salt);
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

function injectPDF($file, $response) {
    $response = $response   ->withHeader('Content-Type', 'application/pdf')
                            ->withHeader('Content-Disposition', 'inline; filename="' .basename("$file") . '"')
                            ->withHeader('Content-Transfer-Encoding', 'binary')
                            ->withHeader('Expires', '0')
                            ->withHeader('Cache-Control', 'must-revalidate')
                            ->withHeader('Pragma', 'public')
                            ->withHeader('Content-Length', filesize($file));

    readfile($file);
    return $response;
}
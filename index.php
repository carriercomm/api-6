<?php

    //error_reporting(E_ERROR);
    error_reporting(E_ERROR);
    ini_set('display_errors', '1');

    require_once "config.php";
    require_once "lib/Static.data.php";
    require_once "lib/Compat.php";

    function parseUri($uri) {
        $parts = explode("/", $uri);
        $parts = array_filter($parts);

        if (count($parts) < 1)
            return false;

        $parts = $params = array_values($parts);

        array_shift($params);
        array_shift($params);
        return array(
            "module" => (!empty($parts[0])) ? $parts[0] : false,
            "action" => (!empty($parts[1])) ? $parts[1] : false,
            "params" => $params
        );
    }

    function outputError($error, $code = false) {
        if (empty($code)) {
            $code = "200 OK";
        }
        header('HTTP/1.1 ' . $code);
        header('Content-type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: X-Auth-Token, Content-Type, Accept');
        return json_encode(array("error" => $error));
    }

    $p = parseUri($_SERVER['REQUEST_URI']);
    if (!$p) {
        die(outputError("404 Not Found", "Invalid Request"));
    }

    //$base = new base();
    //$base->outputHeaders();

    $headers = getallheaders();
    require_once "lib/Token.class.php";
    $valid = $Token->validate($headers);
    if (!$valid) {
        if ($p['module'] != "auth" && $p['action'] != "getToken") {
            die(outputError("Invalid Token"));
        }
    }

    $_POST = json_decode(file_get_contents('php://input'));

    require_once "classes/base.php";

    if ($p['module'] == "auth") {
        require_once "classes/auth.php";
        $auth = new auth();
        switch($p['action']) {
            case "getToken":
                $token = $auth->getToken($p['params'], $_POST);
                if (!$token) {
                    die(outputError("Token generation failed"));
                } else {
                    die(json_encode(array("token" => $token)));
                }
                break;
        }
    } else {
        if (file_exists("classes/" . $p['module'] . ".php") ){
            require_once "classes/" . $p['module'] . ".php";
            if (!$p['module'] || !class_exists($p['module'])) {
                die(outputError("Class '" . $p['module'] . "' does not exist."));
            }

            $module = new $p['module']($Token);

            if (!$p['action'] || !method_exists($module, $p['action'])) {
                if (!method_exists($module, "index")) {
                    die(outputError("Action '" . $p['action'] . "' does not exist and there is no 'index' action in the '" . $p['module'] . "' module."));
                }
            }

            $module->{$p['action']}($p['params'], $_POST);
        } else {
            die(outputError("Module '" . $p['module'] . "' does not exist."));
        }
    }

?>

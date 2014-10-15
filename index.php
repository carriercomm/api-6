<?php

    error_reporting(E_ALL);
    ini_set('display_errors', '1');

    require_once "config.php";
    require_once "lib/Util.class.php";
    require_once "lib/Static.data.php";
    require_once "lib/Compat.php";
    require_once "lib/Encryption.class.php";

    $p = $Util->parseUri($_SERVER['REQUEST_URI']);
    if (!$p) {
        $Util->output(array("error" => "Invalid Request", "code" => "404 Not Found"));
    }

    $callback = (!empty($_REQUEST['callback'])) ? $_REQUEST['callback'] : false;
    $_POST = $_REQUEST;

    require_once "lib/Token.class.php";
    $valid = $Token->validate($_POST);
    if (!$valid) {
        if ($p['module'] != "auth" || ($p['module'] == "auth" && $p['action'] != "getToken" && $p['action'] != "getTokenData")) {
            $Util->output(array("error" => "Invalid Token"));
        }
    } else {
        $eqemu_db_info = $Token->getDBInfo($_POST);
    }

    require_once "classes/base.php";

    if ($p['module'] == "auth") {
        require_once "classes/auth.php";
        $auth = new auth();
        switch($p['action']) {
            case "getToken":
                $token = $auth->getToken($p['params'], $_POST);
                if (!$token) {
                    $Util->output(array("error" => "Token generation failed"));
                } else {
                    $Util->output(array("data" => array("token" => $token)));
                }
                break;
            case "getTokenData":
                $data = $auth->getTokenData($_POST['tokens']);
                if (!$data) {
                    $Util->output(array("error" => "Failed to retrieve database details"));
                } else {
                    $Util->output(array("data" => $data));
                }
                break;
            case "verifytoken":
                $Util->output(array());
            case "login":
                // needs eqemu db info to access peq_admin table
                $auth->setEqemuDb($eqemu_db_info);
                $results = $auth->validateLogin($_POST);
                if ($results && $results['logged_in']) {
                    $Util->output(array("data" => array("admin" => $results['admin'])));
                } else {
                    $Util->output(array("error" => "Login failed, re-check credentials and try again"));
                }
                break;
        }
    } else {
        if (file_exists("classes/" . $p['module'] . ".php") ){
            require_once "classes/" . $p['module'] . ".php";

            if (!$p['module'] || !class_exists($p['module'])) {
                $Util->output(array("error" => "Class '" . $p['module'] . "' does not exist."));
            }

            $module = new $p['module']($Token, $callback, $_POST);

            if (!$p['action'] || !method_exists($module, $p['action'])) {
                if (!method_exists($module, "index")) {
                    $Util->output(array("error" => "Action '" . $p['action'] . "' does not exist and there is no 'index' action in the '" . $p['module'] . "' module."));
                }
            }

            $module->{$p['action']}($p['params'], $_POST);
        } else {
            $Util->output(array("error" => "Module '" . $p['module'] . "' does not exist."));
        }
    }

?>

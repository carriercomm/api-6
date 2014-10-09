<?php

    //error_reporting(E_ERROR);
    error_reporting(E_ERROR);
    ini_set('display_errors', '1');

    require_once "lib/Static.data.php";
    require_once "classes/base.php";

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

    $p = parseUri($_SERVER['REQUEST_URI']);
    if (!$p) {
        header('HTTP/1.1 404 Not Found');
        die(json_encode(array("error" => "Invalid Request")));
    }

    $_POST = json_decode(file_get_contents('php://input'));

    if (file_exists("classes/" . $p['module'] . ".php") ){
        require_once "classes/" . $p['module'] . ".php";
        if (!$p['module'] || !class_exists($p['module'])) {
            die(json_encode(array("error" => "Class '" . $p['module'] . "' does not exist.")));
        }
        $module = new $p['module']();
        if (!$p['action'] || !method_exists($module, $p['action'])) {
            if (!method_exists($module, "index")) {
                die(json_encode(array("error" => "Action '" . $p['action'] . "' does not exist and there is no 'index' action in the '" . $p['module'] . "' module.")));
            }
        }

        $module->{$p['action']}($p['params'], $_POST);
    } else {
        die(json_encode(array("error" => "Module '" . $p['module'] . "' does not exist.")));
    }

?>

<?
    error_reporting(E_ALL);
    ini_set('display_errors', '1');

    class Util {
        function Util() {
            //
        }

        function output($options) {
            if (!empty($_REQUEST['callback'])) {
                if (!empty($options['error'])) {
                    die($_REQUEST['callback'] . "(" . json_encode(array("success" => false, "error" => $options['error'])) . ");");
                } else {
                    die($_REQUEST['callback'] . "(" . json_encode(array("success" => true, "data" => $options['data'])) . ");");
                }
            } else {
                if (!empty($options['code'])) {
                    $this->outputHeaders($options['code']);
                } else {
                    $this->outputHeaders();
                }
                if (!empty($options['error'])) {
                    die(json_encode(array("success" => false, "error" => $options['error'])));
                } else {
                    die(json_encode(array("success" => true, "data" => $options['data'])));
                }
            }
        }

        function outputHeaders($code = false) {
            if (empty($code)) {
                $code = "200 OK";
            }
            header('HTTP/1.1 ' . $code);
            header('Content-type: application/json');
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: X-Auth-Token, Content-Type, Accept');
        }

        function parseUri($uri) {
            $parts = explode("?", $uri);
            $parts = explode("/", $parts[0]);
            $parts = array_filter($parts);

            if (count($parts) < 1)
                return false;

            $parts = $params = array_values($parts);

            array_shift($params);
            array_shift($params);

            $params = array_merge($params, $_REQUEST);
            return array(
                "module" => (!empty($parts[0])) ? $parts[0] : false,
                "action" => (!empty($parts[1])) ? $parts[1] : false,
                "params" => $params
            );
        }
    }

    $Util = new Util();
?>
<?php

    error_reporting(E_ERROR);
    ini_set('display_errors', '1');

    class auth {
        var $db;
        var $eqemu_db;
        function auth() {
            require_once "lib/Mysql.class.php";
            $this->db = new MySQL(array('host' => API_DB_SERVER, 'username' => API_DB_USER, 'password' => API_DB_PASSWORD, 'database' => API_DB_DATABASE));
        }

        function setEqemuDb($data) {
            require_once "lib/Mysql.class.php";
            $this->eqemu_db = new MySQL(array('host' => $data['server'], 'username' => $data['username'], 'password' => $data['password'], 'database' => $data['database']));
        }

        function getToken($params, $post) {
            $server = (!empty($post['server'])) ? $post['server']: false;
            $username = (!empty($post['username'])) ? $post['username']: false;
            $password = (!empty($post['password'])) ? $post['password']: false;
            $database = (!empty($post['database'])) ? $post['database']: false;
            
            if ($server && $username && $password && $database) {
                $token = md5($server . "|" . $username . "|" . $password . "|" . $database . "|" . date("c", strtotime("now")));
                $this->db->Insert("db_tokens", array(
                    "token" => $token,
                    "db_server" => $server,
                    "db_username" => $username,
                    "db_password" => Encryption::Encrypt(ENCRYPT_DEFAULT_ALGORITHM, $password),
                    "db_database" => $database,
                    "row_insert_dt" => "NOW()",
                    "expires" => "DATE_ADD(NOW(), INTERVAL 30 DAY)"
                ));
                return $token;
            }
            return false;
        }

        function validateLogin($post) {
            $user = (!empty($post['user'])) ? $post['user'] : false;
            $password = (!empty($post['password'])) ? $post['password'] : false;

            $user = $this->eqemu_db->QueryFetchRow("SELECT password, administrator FROM peq_admin WHERE login = :login", array("login" => $user));
            if ($user) {
                if ($user['password'] == md5($password)) {
                    return array("logged_in" => true, "admin" => $user['administrator']);
                } else {
                    return array("logged_in" => false);
                }
            } else {
                return array("logged_in" => false);
            }
        }
    }

?>
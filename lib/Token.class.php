<?
    error_reporting(E_ALL);
    ini_set('display_errors', '1');

    class Token {
        var $db;
        var $token;
        function Token() {
            require_once "lib/Mysql.class.php";
            $this->db = new MySQL(array('host' => API_DB_SERVER, 'username' => API_DB_USER, 'password' => API_DB_PASSWORD, 'database' => API_DB_DATABASE));
            $this->verifyTableStructure();
        }

        //
        //  Executes all the needed SQL from the /sql folder if it's required
        //
        function verifyTableStructure() {
            $this->db->_query("SHOW TABLES LIKE 'db_tokens'");
            if ($this->db->GetNumberOfRows() < 1) {
                $sql = file_get_contents("sql/tokens.sql");
                $this->db->BatchQuery($sql);
            }
        }

        function validate($post) {
            $token = (!empty($post['token'])) ? $post['token'] : false;
            if (!$token) {
                return false;
            } else {
                $valid = $this->db->QueryFetchSingleValue("SELECT token FROM db_tokens WHERE token = :token AND NOW() < expires", array("token" => $token));
                if ($valid) {
                    $this->token = $token;
                } else {
                    return false;
                }
            }
            return true;
        }

        function getDBInfo($post) {
            $token = (!empty($post['token'])) ? $post['token'] : false;
            $data = $this->db->QueryFetchRow("SELECT * FROM db_tokens WHERE token = :token", array("token" => $token));
            if ($data) {
                return array(
                    "server" => $data['db_server'],
                    "username" => $data['db_username'],
                    "password" => Encryption::Decrypt(ENCRYPT_DEFAULT_ALGORITHM, $data['db_password']),
                    "database" => $data['db_database']
                );
            } else {
                return false;
            }
        }
    }

    $Token = new Token();
?>
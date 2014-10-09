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
            $this->db->_query("SHOW TABLES LIKE 'login_tokens'");
            if ($this->db->GetNumberOfRows() < 1) {
                $sql = file_get_contents("sql/tokens.sql");
                $this->db->BatchQuery($sql);
            }
        }

        function validate($headers) {
            $token = (!empty($headers['X-Auth-Token'])) ? $headers['X-Auth-Token'] : false;
            $token = "11111111111111";
            if (!$token) {
                return false;
            } else {
                $this->token = $token;
            }
            return true;
        }

        function getDBInfo() {
            // db query to get database info from $this->token
            return array(
                "server" => "localhost",
                "username" => "root",
                "password" => "fake10",
                "database" => "eq"
            );
        }
    }

    $Token = new Token();
?>
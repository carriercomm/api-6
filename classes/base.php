<?
	error_reporting(E_ALL);
    ini_set('display_errors', '1');

	class base {
		var $db;
	    function base() {
	        require_once "lib/Mysql.class.php";
	        $this->db = new MySQL(array('host' => 'localhost', 'username' => 'root', 'password' => 'fake10', 'database' => 'eq'));
	    }

	    function findInvalidColumns($columns, $table) {
	    	$invalid = array();
	    	$valid = $this->getColumns($table);
	    	foreach ($columns as $column) {
	    		if (strpos($column, ".") === false) {
	    			if (!in_array($column, $valid)) {
	    				$invalid[] = $column;
	    			}
	    		}
	    	}
	    	return $invalid;
	    }

	    function getColumns($table) {
	    	$cols = array();
	    	$columns = $this->db->QueryFetchAssoc("SHOW COLUMNS FROM `:table`", array("table" => $table));
	    	foreach ($columns as $column) {
	    		$cols[] = $column['Field'];
	    	}
	    	return $cols;
	    }

	    function outputHeaders() {
	    	header('HTTP/1.1 200 OK');
            header('Content-type: application/json');
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: PUT, GET, POST, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: X-Auth-Token, Content-Type, Accept');
	    }
	}

?>
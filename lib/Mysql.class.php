<?php

/**
 * MySQL database abstraction class
 * @author  Kevin Holland
 */
class MySQL {
    var $link;
    var $db_link;
    var $query;
    var $results;
    var $row;

    function MySQL($config) {
        $this->host = $config['host'];
        $this->database = $config['database'];
        $this->username = $config['username'];
        $this->password = $config['password'];
        $this->type = 'mysql';
        $this->Connect();
        $this->SelectDatabase();
        $this->_query('SET @@SESSION.sql_mode = \'NO_BACKSLASH_ESCAPES\'');

        return true;
    }

    function SetConnectionInfo($config) {
        $this->host = $config['host'];
        $this->database = $config['database'];
        $this->username = $config['username'];
        $this->password = $config['password'];
        return true;
    }

    function Connect() {
        $this->link = @mysql_connect($this->host, $this->username, $this->password) or die (mysql_error());
        return $this->link;
    }

    function Disconnect() {
        if ($this->link) {
            @mysql_close($this->link);
        }
    }

    function SelectDatabase($database = null) {
        if ($this->link) {
            $database = (null === $database) ? $this->database : $database;
            $this->db_link = @mysql_select_db($this->database) or die (mysql_error());
        }
        
        return $this->db_link;
    }

    function StartTransaction() {
        $this->_query('START TRANSACTION');
    }

    function RollbackTransaction() {
        $this->_query('ROLLBACK');
    }

    function CommitTransaction() {
        $this->_query('COMMIT');
    }

    function _query($sql) {
        if ($this->link) {
            $this->query = @mysql_query($sql, $this->link) or die (mysql_error());
        }
        return $this->query;
    }

    function _parameterize($sql, $params) {
        if (is_array($params) && count($params) > 0) {
            foreach ($params as $key => $value) {
                if (!is_numeric($value))
                    $value = $this->_quote($value);
                
                $sql = preg_replace('/\%:' . $key . '([,\s\)\%]|$)/', '%' . str_replace("'", "", $value) . '$1', $sql);
                $sql = preg_replace('/\`:' . $key . '([,\s\)\`]|$)/', '`' . str_replace("'", "", $value) . '$1', $sql);
                $sql = preg_replace('/[^\%]:' . $key . '([,\s\)\%]|$)/', $value . '$1', $sql);
            }
        }
        return $sql;
    }

    function _quote($param) {
        if (null === $param || '' == $param)
            return 'NULL';
        
        return '\'' . mysql_real_escape_string($param) . '\'';
    }

    function _matchFunctions($value) {
        $functions = array(
            'ABS', 'ACOS', 'ADDDATE', 'ADDTIME', 'AES_DECRYPT', 'AES_ENCRYPT', 'ASCII', 'ASIN', 'ATAN', 'ATAN2', 'AVG', 'BENCHMARK', 'BIN', 'BINARY', 'CAST', 'CEIL', 'CEILING',
            'CHAR_LENGTH', 'CHAR', 'CHARACTER_LENGTH', 'CHARSET', 'COALESCE', 'COERCIBILITY', 'COLLATION', 'COMPRESS', 'CONCAT_WS', 'CONCAT', 'CONNECTION_ID', 'CONV', 'CONVERT_TZ',
            'CONVERT', 'COS', 'COT', 'COUNT', 'CRC32', 'CURDATE', 'CURRENT_DATE', 'CURRENT_TIME', 'CURRENT_TIMESTAMP', 'CURRENT_USER', 'CURTIME', 'DATABASE', 'DATE_ADD', 'DATE_FORMAT',
            'DATE_SUB', 'DATE', 'DATEDIFF', 'DAY', 'DAYNAME', 'DAYOFMONTH', 'DAYOFWEEK', 'DAYOFYEAR', 'DECODE', 'DEFAULT', 'DEGREES', 'DES_DECRYPT', 'DES_ENCRYPT', 'ELT',
            'ENCODE', 'ENCRYPT', 'EXP', 'EXPORT_SET', 'EXTRACT', 'FIELD', 'FIND_IN_SET', 'FLOOR', 'FORMAT', 'FOUND_ROWS', 'FROM_DAYS', 'FROM_UNIXTIME', 'GET_FORMAT', 'GET_LOCK',
            'GREATEST', 'GROUP_CONCAT', 'HEX', 'HOUR', 'IF', 'IFNULL', 'IN', 'INET_ATON', 'INET_NTOA', 'INSERT', 'INSTR', 'INTERVAL', 'IS_FREE_LOCK', 'IS_USED_LOCK', 'ISNULL', 
            'LAST_INSERT_ID', 'LCASE', 'LEAST', 'LEFT', 'LENGTH', 'LN', 'LOAD_FILE', 'LOCALTIME', 'LOCALTIMESTAMP', 'LOCATE', 'LOG10', 'LOG2', 'LOG', 'LOWER', 'LPAD', 'LTRIM', 
            'MAKE_SET', 'MAKEDATE', 'MASTER_POS_WAIT', 'MAX', 'MD5', 'MICROSECOND', 'MID', 'MIN', 'MINUTE', 'MOD', 'MONTH', 'MONTHNAME', 'NAME_CONST', 'NOT_IN', 'NOW', 'NULLIF', 
            'OCT', 'OCTET_LENGTH', 'OLD_PASSWORD', 'ORD', 'PASSWORD', 'PERIOD_ADD', 'PERIOD_DIFF', 'PI', 'POSITION', 'POW', 'POWER', 'PROCEDURE_ANALYSE', 'QUARTER', 'QUOTE', 'RADIANS', 
            'RAND', 'RELEASE_LOCK', 'REPEAT', 'REPLACE', 'REVERSE', 'RIGHT', 'ROUND', 'ROW_COUNT', 'RPAD', 'RTRIM', 'SCHEMA', 'SEC_TO_TIME', 'SECOND', 'SESSION_USER', 'SHA1', 'SHA', 
            'SIGN', 'SIN', 'SLEEP', 'SOUNDEX', 'SPACE', 'SQRT', 'STD', 'STDDEV_POP', 'STDDEV_SAMP', 'STDDEV', 'STR_TO_DATE', 'STRCMP', 'SUBDATE', 'SUBSTR', 'SUBSTRING_INDEX', 
            'SUBSTRING', 'SUBTIME', 'SUM', 'SYSDATE', 'SYSTEM_USER', 'TAN', 'TIME_FORMAT', 'TIME_TO_SEC', 'TIME', 'TIMEDIFF', 'TIMESTAMP', 'TIMESTAMPADD', 'TIMESTAMPDIFF', 'TO_DAYS', 
            'TRIM', 'TRUNCATE', 'UCASE', 'UNCOMPRESS', 'UNCOMPRESSED_LENGTH', 'UNHEX', 'UNIX_TIMESTAMP', 'UPPER', 'USER', 'UTC_DATE', 'UTC_TIME', 'UTC_TIMESTAMP', 'UUID', 'VALUES', 
            'VAR_POP', 'VAR_SAMP', 'VARIANCE', 'VERSION', 'WEEK', 'WEEKDAY', 'WEEKOFYEAR', 'YEAR', 'YEARWEEK'
        );
        $matches = false;
        foreach ($functions as $function) {
            if (preg_match('/^' . $function . '\(.*\)$/', $value)) {
                $matches = true;
                continue;
            }
        }
        return $matches;
    }

    function GetLastInsertId() {
        return mysql_insert_id();
    }

    function GetNumberOfRows() {
        return @mysql_num_rows($this->query);
    }
    
    /**
     * [Insert] Inserts data into a database table
     *     [Example1] Insert("client", array('firstname' => 'jeff', 'lastname' => 'smith'));
     *     [Example2] Insert("client", "firstname = 'jeff', lastname = 'smith'");
     *         *NOTE* if you bypass the array method (by sending a string in) you will need to sanitize each field yourself before hand
     *     [Example3] Insert("client", array('firstname' => 'jeff', 'lastname' => '%MD5(\'smith\')'))
     *         You can force the raw content of any property to be fed in, instead of being string escaped by starting the value with %. 
     *         Native mysql functions are detected, you'd use this if you wanted some complex logic
     * 
     * @param String $table      name of the table
     * @param Array or String $properties properties to be inserted
     */
    function Insert($table, $properties) {
        if (isset($table) && isset($properties)) {
            if (!is_array($properties)) {
                $properties_formatted = $properties;
            } else {
                $props = array();
                foreach ($properties as $key => $value) {
                    if (!is_numeric($value)) {
                        if (strpos($value, "%") === 0) {
                            $value = substr($value, 1, strlen($value));
                        } elseif (!$this->_matchFunctions($value)) {
                            $value = $this->_quote($value);
                        }
                    }

                    $props[] = $key . " = " . $value;
                }
                $properties_formatted = implode(", \n", $props);
            }

            $sql = "INSERT INTO $table SET $properties_formatted";
            $this->_query($sql);
            return $this->GetLastInsertId();
        }
    }

    /**
     * [Update] Updates a record in a database table
     *     [Example1] Update("client", array('firstname' => 'jeff', 'lastname' => 'smith'), array('clientid' => 12345));
     *     [Example2] Update("client", array('firstname' => 'jeff', 'lastname' => 'smith'), array('clientid' => 12345, 'firstname' => 'todd'), 'and');
     *
     *     The same rules apply for both $properties and $where as the insert method. You can begin the string with % to send the raw data through.
     * 
     * @param String $table name of the table
     * @param Array or String $properties properties to be updated
     * @param Array or String $where properties for where clause
     * @param String $andor "and" or "or" if ommited defaults to and used for where clause properties
     */
    function Update($table, $properties, $where, $andor = "AND") {
        if (isset($table) && isset($properties) && isset($where)) {
            $andor = (strtoupper($andor) == "AND" || strtoupper($andor) == "OR") ? strtoupper($andor) : "AND";

            if (!is_array($properties)) {
                $properties_formatted = $properties;
            } else {
                $props = array();
                foreach ($properties as $key => $value) {
                    if (!is_numeric($value)) {
                        if (strpos($value, "%") === 0) {
                            $value = substr($value, 1, strlen($value));
                        } elseif (!$this->_matchFunctions($value)) {
                            $value = $this->_quote($value);
                        }
                    }

                    $props[] = $key . " = " . $value;
                }
                $properties_formatted = implode(", \n", $props);
            }

            if (!is_array($where)) {
                $where_formatted = $where;
            } else {
                $w = array();
                foreach ($where as $key => $value) {
                    if (!is_numeric($value)) {
                        if (strpos($value, "%") === 0) {
                            $value = substr($value, 1, strlen($value));
                        } elseif (!$this->_matchFunctions($value)) {
                            $value = $this->_quote($value);
                        }
                    }

                    $w[] = $key . " = " . $value;
                }
                $where_formatted = implode("\n$andor ", $w);
            }
            
            $sql = "UPDATE $table SET $properties_formatted WHERE $where_formatted";
            $this->_query($sql);
        }
    }

    /**
     * [Replace] Replaces into a record in a database table
     *     [Example1] Replace("client", array('firstname' => 'jeff', 'lastname' => 'smith'));
     *
     *     The same rules apply for both $properties and $where as the insert method. You can begin the string with % to send the raw data through.
     * 
     * @param String $table name of the table
     * @param Array or String $properties properties to be updated
     */
    function Replace($table, $properties) {
        if (isset($table) && isset($properties)) {
            if (!is_array($properties)) {
                $properties_formatted = $properties;
            } else {
                $props = array();
                foreach ($properties as $key => $value) {
                    if (!is_numeric($value)) {
                        if (strpos($value, "%") === 0) {
                            $value = substr($value, 1, strlen($value));
                        } elseif (!$this->_matchFunctions($value)) {
                            $value = $this->_quote($value);
                        }
                    }

                    $props[] = $key . " = " . $value;
                }
                $properties_formatted = implode(", \n", $props);
            }
            
            $sql = "REPLACE INTO $table SET $properties_formatted";
            $this->_query($sql);
        }
    }

    function Query($sql, $params = array()) {
        if (!$params) {
            $params = array();
        }
        $this->_query($this->_parameterize($sql, $params));
    }

    function BatchQuery($sql) {
        if (!is_array($sql)) {
            $sql = str_replace(array("\r", "\n"), "", $sql);
            $sql = str_replace("    ", " ", $sql);
            $sql = explode(";", $sql);
        }
        foreach($sql as $q) {
            if (!empty($q)) {
                $this->_query($q, array());
            }
        }
    }

    /**
     * [QueryFetchIterator] 
     *     [Example] $iterator = QueryFetchIterator("SELECT clientid FROM client");
     *         This would return an iterator object you could then use in a while loop like...
     *     [Example] while($row = $iterator->next()) {
     *         The purpose of this method is for large datasets where you don't want the data to stay in memory all at once
     * @param [type] $sql    sql to run
     * @param [type] $params params to replace into sql string
     */
    function QueryFetchIterator($sql, $params = null) {
        $this->_query($this->_parameterize($sql, $params));
        return new MySQLIterator($this->query);
    }

    /**
     * [QueryFetchSingleValue] 
     *     [Example] QueryFetchSingleValue("SELECT clientid FROM client WHERE clientid = 1");
     *         This would return a single value from the query (first value returned in the case that you specify more than one column in query)
     * @param [type] $sql    sql to run
     * @param [type] $params params to replace into sql string
     */
    function QueryFetchSingleValue($sql, $params = null) {
        $value = null;
        $this->_query($this->_parameterize($sql, $params));
        while ($record = @mysql_fetch_row($this->query)) {
            $value = reset($record);
            continue;
        }
        return $value;
    }

    /**
     * [QueryFetchColumn] used when you want to fetch a single dimension array containing the values of one column from the database
     *     [Example] QueryFetchColumn("SELECT clientid FROM client WHERE firstname LIKE '%ted%'");
     *         This would return an array containing all the clientids of clients with a firstname containing ted.
     * @param [type] $sql    sql to run
     * @param [type] $params params to replace into sql string
     */
    function QueryFetchColumn($sql, $params = null) {
        $data = array();
        $this->_query($this->_parameterize($sql, $params));
        while ($record = @mysql_fetch_row($this->query)) {
            $data[] = reset($record);
        }
        return $data;
    }

    /**
     * [QueryFetchRow] used when you want to fetch a single record from the database, all of the columns are returned in an associative array
     *     [Example] QueryFetchRow("SELECT * FROM client WHERE clientid = 1");
     *         This would return an associative array containing all the fields for the first row returned from the query
     * @param [type] $sql    sql to run
     * @param [type] $params params to replace into sql string
     */
    function QueryFetchRow($sql, $params = null) {
        $results = $this->QueryFetchAssoc($sql, $params);
        return reset($results);
    }

    /**
     * [QueryFetchObject] used when you want to fetch an array of objects from the database
     *     [Example] QueryFetchObject("SELECT * FROM client WHERE firstname LIKE '%ted%'");
     *         This would return an array containing multiple objects, one for each record returned
     * @param [type] $sql    sql to run
     * @param [type] $params params to replace into sql string
     */
    function QueryFetchObject($sql, $params = null) {
        $data = array();
        $this->_query($this->_parameterize($sql, $params));
        while ($rows = @mysql_fetch_object($this->query)) {
            $data[] = $rows;
        }
        $this->results = $data;
    }
    
    /**
     * [QueryFetchArray] Uses mysql_fetch_array, I generally just use assoc though. You can do specific types with this
     *     [Example] QueryFetchArray("SELECT * FROM client WHERE firstname LIKE '%ted%'");
     *         
     * @param [type] $sql    sql to run
     * @param [type] $params params to replace into sql string
     */
    function QueryFetchArray($sql, $params = null) {
        $data = array();
        $this->_query($this->_parameterize($sql, $params));
        while ($rows = @mysql_fetch_array($this->query)) {
            $data[] = $rows;
        }
        $this->results = $data;
        return $this->results;
    }
    
    /**
     * [QueryFetchAssoc] used when you want to fetch an array of associative arrays from the database
     *     [Example] QueryFetchAssoc("SELECT * FROM client WHERE firstname LIKE '%ted%'");
     *         This would return an array containing multiple associative arrays, one for each record returned
     * @param [type] $sql    sql to run
     * @param [type] $params params to replace into sql string
     */
    function QueryFetchAssoc($sql, $params = null) {
        $data = array();
        $this->_query($this->_parameterize($sql, $params));
        while ($rows = @mysql_fetch_assoc($this->query)) {
            $data[] = $rows;
        }
        $this->results = $data;
        return $this->results;
    }

    function QueryReturnInsertId($sql, $params) {
        $this->_query($this->_parameterize($sql, $params));
        return $this->GetLastInsertId();
    }

    function QueryNumberOfRows($sql, $params) {
        $this->_query($this->_parameterize($sql, $params));
        $rows = $this->GetNumberOfRows();
        return $rows;
    }
}

class MySQLIterator {
    var $row;
    var $query;

    function MySQLIterator($query) {
        if (is_string($query)) {
            $this->query = mysql_query($query);
        } elseif (is_resource($query)) {
            $this->query = $query;
        }
    }

    function current() {
        if ($this->row != null)
            return $this->row;
    }

    function next() {
        $this->row = mysql_fetch_assoc($this->query);
        return $this->row;
    }

    function rewind() {
        $this->row = mysql_data_seek($this->query, 0);
        return $this->row;
    }
}

?>
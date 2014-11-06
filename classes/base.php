<?
    //error_reporting(E_ERROR);
    //ini_set('display_errors', '1');

    class base {
        var $db;
        var $callback;
        var $post;
        function base($Token, $callback, $post) {
            $this->callback = $callback;
            $this->post = $post;
            $db = $Token->getDBInfo($this->post);
            require_once "lib/Mysql.class.php";
            $this->db = new MySQL(array('host' => $db['server'], 'username' => $db['username'], 'password' => $db['password'], 'database' => $db['database']));
            $this->verifyTableStructure();
        }

        //
        //  Executes all the needed SQL from the /sql folder if it's required
        //
        function verifyTableStructure() {
            // expansion column does not exists on zone table, add it and execute expansion.sql
            $this->db->_query("SELECT column_name FROM information_schema.columns WHERE table_name = 'zone' and table_schema = 'eq' and column_name = 'expansion'");
            if ($this->db->GetNumberOfRows() < 1) {
                $this->db->Query("ALTER TABLE zone ADD COLUMN expansion tinyint(3) NOT NULL default 0");
                $sql = file_get_contents("sql/expansion.sql");
                $this->db->BatchQuery($sql);
            }

            // execute schema.sql if the table does not exist
            $this->db->_query("SHOW TABLES LIKE 'peq_admin'");
            if ($this->db->GetNumberOfRows() < 1) {
                $sql = file_get_contents("sql/schema.sql");
                $this->db->BatchQuery($sql);
            }
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

        public function paginate($limit, $page) {
            $limit = (!empty($limit)) ? $limit : 100;
            $start = ($page == 1) ? (($page - 1) * $limit) : ((empty($page)) ? 0 : (($page - 1) * $limit));
            return "LIMIT " . $start . ", " . $limit;
        }

        public function sort($sort, $default, $prefix, $replace) {
            $sort = (array)reset(json_decode($sort));
            if (!empty($sort)) {
                if (strpos($sort['property'], ".") === false) {
                    $sort['property'] = $prefix . "." . $sort['property'];
                }
                if (!empty($replace)) {
                    foreach ($replace as $key => $value) {
                        if ($sort['property'] == $key) {
                            $sort['property'] = $value;
                        }
                    }
                }
            }

            if (!empty($sort)) {
                return " ORDER BY " . $sort['property'] . " " . $sort['direction'] . " ";
            } else {
                return " ORDER BY " . $default['property'] . " " . $default['direction'] . " ";
            }
        }

        public function group($group) {
            if (!empty($group)) {
                return " GROUP BY " . $group . " ";
            }
        }

        public function filter($filters, $prefix) {
            if (!empty($filters)) {
                $filters = (array)json_decode($filters);
            }

            foreach($filters as $key => $filter) {
                $filters[$key] = (array)$filters[$key];
                $filter = (array)$filter;
                if (strpos($filter['field'], ".") === false) {
                    $filters[$key]['field'] = $prefix . "." . $filters[$key]['field'];
                }
            }

            $operators = array(
                "gt" => ">",
                "gte" => ">=",
                "lt" => "<",
                "lte" => "<=",
                "eq" => "=",
                "neq" => "!=",
                "lk" => "LIKE",
                "nlk" => "NOT LIKE"
            );

            $string_operators = array('eq', 'neq', 'lk', 'nlk');

            $f = array();
            foreach($filters as $filter) {
                $filter = (array)$filter;
                if (in_array($filter['operator'], $string_operators)) {
                    switch($filter['operator']) {
                        case 'eq':
                        case 'neq':
                            if (!is_numeric($filter['value'])) {
                                $f[] = $filter['field'] . " " . $operators[$filter['operator']] . " '" . $filter['value'] . "'";
                            } else {
                                $f[] = $filter['field'] . " " . $operators[$filter['operator']] . " " . $filter['value'];
                            }
                            break;
                        default:
                            $f[] = $filter['field'] . " " . $operators[$filter['operator']] . " '%" . $filter['value'] . "%'";
                            break;
                    }
                } else {
                    $f[] = $filter['field'] . " " . $operators[$filter['operator']] . " " . $filter['value'];
                }
            }
            return $f;
        }

        public function find($search, $fields, $static) {
            if ((!empty($fields)) || !empty($static)) {
                $where = "WHERE (";
                if (count($fields) > 0) {
                    $conditions = array();
                    if (!empty($search)) {
                        foreach ($fields as $field) {
                            $conditions[] = $field . " LIKE '%" . mysql_real_escape_string($search) . "%'";
                        }
                        $where .= implode(" OR ", $conditions) . ") ";
                    }
                    if (!empty($static) && count($static) > 0) {
                        $wconditions = array();
                        foreach ($static as $value) {
                            $wconditions[] = $value;
                        }
                        if (!empty($search)) {
                            $where .= "AND (" . implode(" AND ", $wconditions) . ") ";
                        } else {
                            $where .= implode(" AND ", $wconditions) . ") ";
                        }
                    }
                    if (empty($search) && ((empty($static) || count($static) < 1))) {
                        $where = "";
                    }
                    return $where;
                } else {
                    if (!empty($static) && count($static) > 0) {
                        $wconditions = array();
                        foreach ($static as $value) {
                            $wconditions[] = $value;
                        }
                        $where .= implode(" AND ", $wconditions) . ") ";
                    }
                    return $where;
                }
            }
            return "";
        }
    }

?>
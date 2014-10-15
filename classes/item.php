<?php

    error_reporting(E_ERROR);
    ini_set('display_errors', 1);

    class item extends base {
        var $db;
        var $callback;
        function item($Token, $callback, $post) {
            $this->callback = $callback;
            parent::base($Token, $callback, $post);
        }

        //
        // Searches item table with minimal info returned
        //
        function search($params = null, $options = null) {
            // options will contain params such as limit and columns
            $limit = (property_exists($options, 'limit') && $options->limit > 0) ? " LIMIT " . $options->limit : ((property_exists($options, 'limit') && $options->limit === 0) ? "" : " LIMIT 175");
            $columns = (!empty($options->columns)) ? $options->columns : array('i.*');
            if ($columns) {
                foreach($columns as $key => $column) {
                    if (strpos($column, ".") === false) {
                        $columns[$key] = "i." . $column;
                    }
                }
            }

            if (!empty($params[0])) {
                $search = str_replace(" ", "%", urldecode(reset($params)));
            }
            
            if (is_numeric($search)) {
                // numeric, search by id
                $items = array($this->getItemById($search, false));
                $count = count($items);
            } elseif (!empty($search)) {
                // search by name
                $params = array("name" => strtolower($search));
                $count = $this->db->QueryFetchSingleValue("SELECT COUNT(i.id) FROM items i WHERE LOWER(name) LIKE '%:name%' ORDER BY name ASC", $params);
                $items = $this->db->QueryFetchAssoc("SELECT " . implode(",", $columns) . " FROM items i WHERE LOWER(name) LIKE '%:name%' ORDER BY name ASC" . $limit, $params);
            } else {
                $count = $this->db->QueryFetchSingleValue("SELECT COUNT(i.id) FROM items i ORDER BY name ASC");
                $items = $this->db->QueryFetchAssoc("SELECT " . implode(",", $columns) . " FROM items i ORDER BY name ASC" . $limit);
            }

            $items = $this->processForApi($items);

            $this->outputHeaders();
            echo $this->callback . "(" . json_encode(array("total" => $count, "limit" => intval(str_replace(" LIMIT ", "", $limit)), "data" => $items)) . ");";
        }

        //
        // TODO: Add new item
        //
        function add() {

        }

        //
        // TODO: Duplicate an item
        //
        function duplicate($params) {
            $itemId = reset($params);
        }

        //
        // TODO: Update item
        //
        function update($params) {
            $itemId = reset($params);

        }

        //
        // TODO: Delete item
        //
        function delete($params) {
            $itemId = reset($params);
            
        }

        //
        // Get item by id, used by both search methods
        //
        function getItemById($id, $verbose = null) {
            if ($verbose) {
                return $this->db->QueryFetchRow("SELECT i.* FROM items i WHERE i.id = :id LIMIT 1", array("id" => $id));
            } else {
                return $this->db->QueryFetchRow("SELECT i.id, i.name, i.icon, i.itemtype, i.nodrop, i.norent, i.weight FROM items i WHERE i.id = :id LIMIT 1", array("id" => $id));
            }
        }

        function processForApi($items) {
            global $itemtypes;

            foreach ($items as $key => $item) {
                $items[$key]['typeName'] = $itemtypes[$item['itemtype']];
            }

            return $items;
        }
    }

?>
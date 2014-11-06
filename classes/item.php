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
        // Searches items table
        //
        function search($params = null, $options = null) {
            $limit = $this->paginate($options['limit'], $options['page']);
            
            $default_sort = array(
                "property" => "Name", 
                "direction" => "ASC"
            );

            $sort = $this->sort($options['sort'], $default_sort, 'i', null);
            $columns = $this->determineColumns($options['columns'], 'i');
            $filters = $this->filter($options['filter'], 'i');
            $search = $this->determineSearch($options['query'], $params);

            if (is_numeric($search)) {
                // numeric, search by id
                $items = array($this->getItemById($search, $columns));
                $count = 1;
            } else {
                $where = $this->find(strtolower($search), array("LOWER(Name)"), $filters);
                $count = $this->db->QueryFetchSingleValue("SELECT COUNT(i.id) FROM items i " . $where . $sort);
                $items = $this->db->QueryFetchAssoc("SELECT " . implode(",", $columns) . " FROM items i " . $where . $sort . $limit);
            }

            $items = $this->processForApi($items);

            $this->outputHeaders();
            echo $this->callback . "(" . json_encode(array("totalCount" => $count, "limit" => $options['limit'], "data" => $items)) . ");";
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
        function getItemById($id, $columns) {
            return $this->db->QueryFetchRow("SELECT " . implode(",", $columns) . " FROM items i WHERE i.id = :id LIMIT 1", array("id" => $id));
        }

        function processForApi($items) {
            return $items;
        }
    }

?>
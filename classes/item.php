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
            $limit = $this->paginate($options['limit'], $options['page']);
            $default_sort = array(
                "property" => "itemtype", 
                "direction" => "ASC"
            );
            $sort = $this->sort((array)reset(json_decode($options['sort'])), $default_sort);
            $columns = (!empty($options->columns)) ? $options->columns : array('i.*');
            if ($columns) {
                foreach($columns as $key => $column) {
                    if (strpos($column, ".") === false) {
                        $columns[$key] = "i." . $column;
                    }
                }
            }
            
            if (!empty(trim($options['query']))) {
                $search = $options['query'];
            } else {
                if (!empty($params[0])) {
                    $search = str_replace(" ", "%", urldecode(reset($params)));
                }
            }
            
            if (is_numeric($search)) {
                // numeric, search by id
                $items = array($this->getItemById($search, $columns, false));
                $count = count($items);
            } elseif (!empty($search)) {
                // search by name
                $params = array("name" => strtolower($search));
                $count = $this->db->QueryFetchSingleValue("SELECT COUNT(i.id) FROM items i WHERE LOWER(name) LIKE '%:name%'" . $sort, $params);
                $items = $this->db->QueryFetchAssoc("SELECT " . implode(",", $columns) . " FROM items i WHERE LOWER(name) LIKE '%:name%'" . $sort . $limit, $params);
            } else {
                $count = $this->db->QueryFetchSingleValue("SELECT COUNT(i.id) FROM items i" . $sort);
                $items = $this->db->QueryFetchAssoc("SELECT " . implode(",", $columns) . " FROM items i" . $sort . $limit);
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
        function getItemById($id, $columns, $verbose = null) {
            if ($verbose) {
                return $this->db->QueryFetchRow("SELECT " . implode(",", $columns) . " FROM items i WHERE i.id = :id LIMIT 1", array("id" => $id));
            } else {
                return $this->db->QueryFetchRow("SELECT " . implode(",", $columns) . " FROM items i WHERE i.id = :id LIMIT 1", array("id" => $id));
            }
        }

        function processForApi($items) {
            global $itemtypes, $world_containers;

            foreach ($items as $key => $item) {
                // icon
                if (!empty($item['icon'])) {
                    $items[$key]['iconUrl'] = "http://everquest.allakhazam.com/pgfx/item_" . $item['icon'] . ".png";
                }

                if ($item['bagsize'] > 0 && $item['bagslots'] > 0) {
                    $items[$key]['container'] = 1;
                } else {
                    $items[$key]['container'] = 0;
                }
            }

            return $items;
        }
    }

?>
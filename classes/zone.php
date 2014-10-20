<?php

    error_reporting(E_ERROR);
    ini_set('display_errors', '1');

    class zone extends base {
        var $db;
        var $callback;
        function zone($Token, $callback, $post) {
            $this->callback = $callback;
            parent::base($Token, $callback, $post);
        }

        //
        // Searches zone table with minimal info returned
        //
        function search($params = null, $options = null) {
            $limit = $this->paginate($options['limit'], $options['page']);
            $default_sort = array(
                "property" => "long_name", 
                "direction" => "ASC"
            );
            $sort = $this->sort((array)reset(json_decode($options['sort'])), $default_sort);
            $columns = (!empty($options->columns)) ? $options->columns : array('z.*');
            /*$invalid = $this->findInvalidColumns($columns, 'zone');
            if (count($invalid) > 0) {
                $this->outputHeaders();
                echo json_encode(array("error" => "The following are invalid columns: " . implode(", ", $invalid)));
                die();
            }*/

            if ($columns) {
                foreach($columns as $key => $column) {
                    if (strpos($column, ".") === false) {
                        $columns[$key] = "z." . $column;
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
                $zones = $this->getZonesById($search, $columns);
                $count = count($zones);
            } elseif (!empty($search)) {
                // search by name
                $where = $this->find(strtolower($search), array("LOWER(z.short_name)", "LOWER(z.long_name)"));

                $count = $this->db->QueryFetchSingleValue("SELECT COUNT(z.id) FROM zone z " . $where . $sort);
                $zones = $this->db->QueryFetchAssoc("SELECT " . implode(",", $columns) . " FROM zone z " . $where . $sort . $limit);
            } else {
                $count = $this->db->QueryFetchSingleValue("SELECT COUNT(z.id) FROM zone z" . $sort);
                $zones = $this->db->QueryFetchAssoc("SELECT " . implode(",", $columns) . " FROM zone z" . $sort . $limit);
            }

            // Process query data before returning
            $zones = $this->processForApi($zones);

            $this->outputHeaders();
            echo $this->callback . "(" . json_encode(array("totalCount" => $count, "limit" => $options['limit'], "data" => $zones)) . ");";
        }

        function npcs($params = null) {
            $zoneidnumber = urldecode(reset($params));
            $npcs = $this->db->QueryFetchAssoc("SELECT id, name FROM npc_types WHERE id LIKE \"" . $zoneidnumber . "___" . "\" GROUP BY id ORDER BY name ASC");

            $this->outputHeaders();
            echo json_encode($npcs);
        }

        //
        // TODO: Update zone data
        //
        function update($params) {
            $zoneId = reset($params);

        }

        //
        // TODO: Duplicate zone
        //
        function duplicate($params) {
            $zoneId = reset($params);

        }

        //
        // TODO: Delete zone
        //
        function delete($params) {
            $zoneId = reset($params);

        }

        //
        // TODO: Get graveyards for zone
        //
        function graveyards($params) {
            $zoneId = reset($params);
            var_export($zoneId);
            $graveyards = $this->db->QueryFetchAssoc("SELECT * FROM graveyard WHERE zone_id = :zoneId", array("zoneId" => $zoneId));
            echo json_encode($graveyards);
        }

        //
        // TODO: Add graveyard
        //
        function addgy($params) {
            $zoneId = reset($params);
            
        }

        //
        // TODO: Update graveyard
        //
        function updategy($params) {
            $zoneId = reset($params);
            $gyId = end($params);
            
        }

        //
        // TODO: Delete graveyard
        //
        function deletegy($params) {
            $zoneId = reset($params);
            $gyId = end($params);

        }

        //
        // TODO: Get zone connections for zone
        //
        function zonepoints($params) {
            $zoneId = reset($params);

        }

        //
        // TODO: Add zonepoint
        //
        function addzpoint($params) {
            $zoneId = reset($params);
            
        }

        //
        // TODO: Update zonepoint
        //
        function updatezpoint($params) {
            $zoneId = reset($params);
            $zpointId = end($params);
            
        }

        //
        // TODO: Delete zonepoint
        //
        function deletezpoint($params) {
            $zoneId = reset($params);
            $zpointId = end($params);

        }

        //
        // TODO: Get blocked spells for zone
        //
        function blockedspells($params) {
            $zoneId = reset($params);

        }

        //
        // TODO: Add blockedspell
        //
        function addblocked($params) {
            $zoneId = reset($params);
            
        }

        //
        // TODO: Update blockedspell
        //
        function updateblocked($params) {
            $zoneId = reset($params);
            $blockedId = end($params);
            
        }

        //
        // TODO: Delete blockedspell data
        //
        function deleteblocked($params) {
            $zoneId = reset($params);
            $blockedId = end($params);

        }

        //
        // Get zone by id, used by both search methods
        //
        function getZonesById($id, $columns) {
            return $this->db->QueryFetchAssoc("SELECT " . implode(",", $columns) . " FROM zone z WHERE z.id = :id OR z.zoneidnumber = :id", array("id" => $id));
        }

        function processForApi($zones) {
            foreach ($zones as $key => $zone) {
                $zp = $this->db->QueryFetchColumn("SELECT z.short_name FROM zone_points zp LEFT JOIN zone z ON (z.zoneidnumber = zp.target_zone_id) WHERE zp.zone = :short_name", array("short_name" => $zone['short_name']));
                $zp = array_filter($zp);
                $zp = array_values($zp);
                $zones[$key]['connecting_zones'] = $zp;
            }
            return $zones;
        }
    }

?>
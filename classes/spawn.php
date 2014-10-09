<?php

    error_reporting(E_ERROR);
    ini_set('display_errors', '1');

    class spawn extends base {
        var $db;
        function spawn() {
            parent::base();
        }

        //
        // Searches spawngroup table
        //
        function search($params = null, $options) {

            // Options for columns will not be allowed here due to the table joins
            $limit = (property_exists($options, 'limit') && $options->limit > 0) ? " LIMIT " . $options->limit : ((property_exists($options, 'limit') && $options->limit === 0) ? "" : " LIMIT 75");
            $columns = (!empty($options->columns)) ? $options->columns : array('sg.*');
            $invalid = $this->findInvalidColumns($columns, 'spawngroup');

            if (count($invalid) > 0) {
                $this->outputHeaders();
                echo json_encode(array("error" => "The following are invalid columns: " . implode(", ", $invalid)));
                die();
            }

            if ($columns) {
                foreach($columns as $key => $column) {
                    if (strpos($column, ".") === false) {
                        $columns[$key] = "sg." . $column;
                    }
                }
            }

            $search = str_replace(" ", "%", urldecode(reset($params)));
            if (is_numeric($search)) {
                // numeric, search by id
                $spawngroups = $this->getSpawngroupsByNpcId($search, $columns);
                //var_export($spawngroups);die();
                $count = count($spawngroups);
            } elseif (!empty($search)) {
                // search by name
                $params = array("name" => strtolower($search));
                $count = $this->db->QueryFetchAssoc("SELECT sg.id FROM spawngroup sg LEFT JOIN spawnentry se ON (se.spawngroupID = sg.id) LEFT JOIN npc_types n ON (n.id = se.npcID) WHERE LOWER(n.name) LIKE '%:name%' GROUP BY sg.id ORDER BY LOWER(sg.name) ASC", $params);
                $count = count($count);
                $spawngroups = $this->db->QueryFetchAssoc("SELECT " . implode(", ", $columns) . " FROM spawngroup sg LEFT JOIN spawnentry se ON (se.spawngroupID = sg.id) LEFT JOIN npc_types n ON (n.id = se.npcID) WHERE LOWER(n.name) LIKE '%:name%' GROUP BY sg.id ORDER BY LOWER(sg.name) ASC" . $limit, $params);
            } else {
                $count = $this->db->QueryFetchAssoc("SELECT " . implode(", ", $columns) . " FROM spawngroup sg LEFT JOIN spawnentry se ON (se.spawngroupID = sg.id) LEFT JOIN npc_types n ON (n.id = se.npcID) GROUP BY sg.id ORDER BY LOWER(sg.name) ASC");
                $count = count($count);
                $spawngroups = $this->db->QueryFetchAssoc("SELECT " . implode(", ", $columns) . " FROM spawngroup sg LEFT JOIN spawnentry se ON (se.spawngroupID = sg.id) LEFT JOIN npc_types n ON (n.id = se.npcID) GROUP BY sg.id ORDER BY LOWER(sg.name) ASC" . $limit);
            }

            $spawngroups = $this->processForApi($spawngroups, $search);

            $this->outputHeaders();
            echo json_encode(array("total" => $count, "limit" => intval(str_replace(" LIMIT ", "", $limit)), "results" => $spawngroups));
        }

        //
        // Get Spawngroups by NPC id
        //
        function getSpawngroupsByNpcId($id, $columns) {
            return $this->db->QueryFetchAssoc("SELECT " . implode(", ", $columns) . " FROM spawngroup sg LEFT JOIN spawnentry se ON (se.spawngroupID = sg.id) LEFT JOIN npc_types n ON (n.id = se.npcID) WHERE n.id = :id GROUP BY sg.id ORDER BY LOWER(sg.name) ASC", array("id" => $id));
        }

        function processForApi($spawngroups, $search) {
            foreach($spawngroups as $key => $spawngroup) {
                if (is_numeric($search)) {
                    $spawngroups[$key]['matched_npcs'] = preg_replace("/\d+$/", "", trim(str_replace("#", "", str_replace("_", " ", $this->db->QueryFetchSingleValue("SELECT n.name FROM npc_types n WHERE n.id = :search", array("search" => $search))))));
                } else {
                    $spawns = $this->db->QueryFetchColumn("SELECT n.name FROM spawnentry se LEFT JOIN npc_types n ON (n.id = se.npcID) WHERE se.spawngroupID = :spawngroupid AND LOWER(n.name) LIKE '%:search%' GROUP BY n.id ORDER BY LOWER(n.name) ASC", array("search" => $search, "spawngroupid" => $spawngroup['id']));
                    foreach ($spawns as $key2 => $name) {
                        $spawns[$key2] = preg_replace("/\d+$/", "", trim(str_replace("#", "", str_replace("_", " ", $name))));
                    }
                    $spawngroups[$key]['matched_npcs'] = (empty($spawns)) ? "None" : implode(", ", $spawns);
                }

                $spawns = $this->db->QueryFetchAssoc("SELECT n.name FROM spawnentry se LEFT JOIN npc_types n ON (n.id = se.npcID) WHERE se.spawngroupID = :spawngroupid GROUP BY n.id ORDER BY LOWER(n.name) ASC", array("spawngroupid" => $spawngroup['id']));
                $spawngroups[$key]['numNpcs'] = count($spawns);

                $spawnpoints = $this->db->QueryFetchSingleValue("SELECT COUNT(id) FROM spawn2 WHERE spawngroupID = :spawngroupID", array("spawngroupID" => $spawngroup['id']));
                $spawngroups[$key]['numSpawnpoints'] = $spawnpoints;

                $despawn = array(
                    0 => "No Repop or Depop, No Timer",
                    1 => "Depop + Repop Immediately, use Spawn2 for timer",
                    2 => "Depop + Repop Immediately, use Despawn Timer",
                    3 => "Depop, Respawn after Spawn2 timer is up, uses Spawn2 for timer",
                    4 => "Depop, Respawn after Spawn2 timer is up, uses Despawn Timer"
                );
                $spawngroups[$key]['despawn'] = $despawn[$spawngroups[$key]['despawn']];
            }

            return $spawngroups;
        }
    }
?>
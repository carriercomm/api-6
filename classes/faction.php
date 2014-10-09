<?php

    error_reporting(E_ERROR);
    ini_set('display_errors', '1');

    class faction extends base {
        var $db;
        function faction() {
            parent::Base();
        }

        //
        // Searches faction table
        //
        function search($params = null, $options) {
            // options will contain params such as limit and columns
            $limit = (property_exists($options, 'limit') && $options->limit > 0) ? " LIMIT " . $options->limit : ((property_exists($options, 'limit') && $options->limit === 0) ? "" : " LIMIT 75");
            $columns = (!empty($options->columns)) ? $options->columns : array('f.*');
            $invalid = $this->findInvalidColumns($columns, 'faction_list');

            if (count($invalid) > 0) {
                $this->outputHeaders();
                echo json_encode(array("error" => "The following are invalid columns: " . implode(", ", $invalid)));
                die();
            }

            if ($columns) {
                foreach($columns as $key => $column) {
                    if (strpos($column, ".") === false) {
                        $columns[$key] = "f." . $column;
                    }
                }
            }

            $search = str_replace(" ", "%", urldecode(reset($params)));
            if (is_numeric($search)) {
                // numeric, search by id
                $factions = array($this->getFactionById($search));
                $count = count($factions);
            } elseif (!empty($search)) {
                // search by name
                $params = array("name" => strtolower($search));
                $count = $this->db->QueryFetchSingleValue("SELECT COUNT(f.id) FROM faction_list f WHERE LOWER(f.name) LIKE '%:name%'", $params);
                $factions = $this->db->QueryFetchAssoc("SELECT " . implode(",", $columns) . " FROM faction_list f WHERE LOWER(f.name) LIKE '%:name%' ORDER BY LOWER(f.name) ASC" . $limit, $params);
            } else {
                $count = $this->db->QueryFetchSingleValue("SELECT COUNT(f.id) FROM faction_list f ORDER BY f.name ASC");
                $factions = $this->db->QueryFetchAssoc("SELECT " . implode(",", $columns) . " FROM faction_list f ORDER BY LOWER(f.name) ASC" . $limit);
            }
            $factions = $this->populateFactionMods($factions);

            $this->outputHeaders();
            echo json_encode(array("total" => $count, "limit" => intval(str_replace(" LIMIT ", "", $limit)), "results" => $factions));
        }

        function searchplayerfactions($params = null, $options) {
            // options will contain params such as limit and columns
            $limit = (property_exists($options, 'limit') && $options->limit > 0) ? " LIMIT " . $options->limit : ((property_exists($options, 'limit') && $options->limit === 0) ? "" : " LIMIT 75");
            $columns = (!empty($options->columns)) ? $options->columns : array('fv.*');
            $invalid = $this->findInvalidColumns($columns, 'faction_values');

            if (count($invalid) > 0) {
                $this->outputHeaders();
                echo json_encode(array("error" => "The following are invalid columns: " . implode(", ", $invalid)));
                die();
            }

            if ($columns) {
                foreach($columns as $column) {
                    if (strpos($column, ".") === false) {
                        $column = "fv." . $column;
                    }
                }
            }

            $search = str_replace(" ", "%", urldecode(reset($params)));
            if (is_numeric($search)) {
                // numeric, search by id
                $factions = array($this->getFactionById($search));
                $count = count($factions);
            } elseif (!empty($search)) {
                // search by name
                $params = array("name" => strtolower($search));
                $count = $this->db->QueryFetchSingleValue("SELECT COUNT(c.id) FROM faction_values fv LEFT JOIN character_ c ON (c.id = fv.char_id) LEFT JOIN faction_list fl ON (fl.id = fv.faction_id) WHERE LOWER(c.name) LIKE '%:name%'", $params);
                $factions = $this->db->QueryFetchAssoc("SELECT " . implode(",", $columns) . " FROM faction_values fv LEFT JOIN character_ c ON (c.id = fv.char_id) LEFT JOIN faction_list fl ON (fl.id = fv.faction_id) WHERE LOWER(c.name) LIKE '%:name%' ORDER BY LOWER(fl.name) ASC" . $limit, $params);
            } else {
                $count = $this->db->QueryFetchSingleValue("SELECT COUNT(c.id) FROM faction_values fv LEFT JOIN character_ c ON (c.id = fv.char_id) LEFT JOIN faction_list fl ON (fl.id = fv.faction_id)");
                $factions = $this->db->QueryFetchAssoc("SELECT " . implode(",", $columns) . " FROM faction_values fv LEFT JOIN character_ c ON (c.id = fv.char_id) LEFT JOIN faction_list fl ON (fl.id = fv.faction_id) ORDER BY LOWER(fl.name) ASC" . $limit);
            }
            //$factions = $this->populateFactionMods($factions);

            $this->outputHeaders();
            echo json_encode(array("total" => $count, "limit" => intval(str_replace(" LIMIT ", "", $limit)), "results" => $factions));
        }

        function factionsbycharacter($params = null, $options) {
            // options will contain params such as limit and columns
            $limit = (property_exists($options, 'limit') && $options->limit > 0) ? " LIMIT " . $options->limit : ((property_exists($options, 'limit') && $options->limit === 0) ? "" : " LIMIT 75");
            $columns = (!empty($options->columns)) ? $options->columns : array('fv.*');
            $invalid = $this->findInvalidColumns($columns, 'faction_values');

            if (count($invalid) > 0) {
                $this->outputHeaders();
                echo json_encode(array("error" => "The following are invalid columns: " . implode(", ", $invalid)));
                die();
            }

            if ($columns) {
                foreach($columns as $column) {
                    if (strpos($column, ".") === false) {
                        $column = "fv." . $column;
                    }
                }
            }

            $characterId = reset($params);

            // search by name
            $params = array("characterId" => strtolower($characterId));
            $count = $this->db->QueryFetchSingleValue("SELECT COUNT(c.id) FROM faction_values fv LEFT JOIN character_ c ON (c.id = fv.char_id) LEFT JOIN faction_list fl ON (fl.id = fv.faction_id) WHERE c.id = :characterId", $params);
            $factions = $this->db->QueryFetchAssoc("SELECT " . implode(",", $columns) . " FROM faction_values fv LEFT JOIN character_ c ON (c.id = fv.char_id) LEFT JOIN faction_list fl ON (fl.id = fv.faction_id) WHERE c.id = :characterId ORDER BY LOWER(fl.name) ASC" . $limit, $params);

            $this->outputHeaders();
            echo json_encode(array("total" => $count, "limit" => intval(str_replace(" LIMIT ", "", $limit)), "results" => $factions));
        }

        //
        // TODO: Add faction
        //
        function add($params) {

        }

        //
        // TODO: Update faction
        //
        function update($params) {
            $factionId = reset($params);

        }

        //
        // TODO: Duplicate faction
        //
        function duplicate($params) {
            $factionId = reset($params);

        }

        //
        // TODO: Delete faction
        //
        function delete($params) {
            $factionId = reset($params);

        }

        //
        // Get faction by id, used by both search methods
        //
        function getFactionById($id, $verbose = null) {
            return $this->db->QueryFetchRow("SELECT f.* FROM faction_list f WHERE f.id = :id LIMIT 1", array("id" => $id));
        }

        // Get faction mods by faction id
        function getModsByFactionId($id) {
            return $this->db->QueryFetchAssoc("SELECT * FROM faction_list_mod WHERE faction_id = :faction_id", array("faction_id" => $id));
        }

        // Get faction mods and attach to factions objects
        function populateFactionMods($factions) {
            foreach ($factions as $key => $faction) {
                $mods = $this->getModsByFactionId($faction['id']);
                foreach($mods as $mod) {
                    $factions[$key][$mod['mod_name']] = $mod['mod'];
                }
            }
            return $factions;
        }

        function processForApi($factions) {
            global $skilltypes, $sp_spellgroups, $sp_targets;
            foreach ($spells as $key => $spell) {
                //$chars[$key]['classId'] = $char['class'];
                $spells[$key]['categoryName'] = $sp_spellgroups[$spell['spell_category']];
                $spells[$key]['skillName'] = $skilltypes[$spell['skill']];
                $spells[$key]['targetType'] = $sp_targets[$spell['targettype']];
            }
            return $spells;
        }
    }

?>
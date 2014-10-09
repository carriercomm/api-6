<?php

    error_reporting(E_ERROR);
    ini_set('display_errors', '1');

    class spell extends base {
        var $db;
        function spell() {
            parent::Base();
        }

        //
        // Searches spell table with minimal info returned
        //
        function search($params = null, $options) {
            // options will contain params such as limit and columns
            $limit = (property_exists($options, 'limit') && $options->limit > 0) ? " LIMIT " . $options->limit : ((property_exists($options, 'limit') && $options->limit === 0) ? "" : " LIMIT 75");
            $columns = (!empty($options->columns)) ? $options->columns : array('s.*');
            $invalid = $this->findInvalidColumns($columns, 'spells_new');

            if (count($invalid) > 0) {
                $this->outputHeaders();
                echo json_encode(array("error" => "The following are invalid columns: " . implode(", ", $invalid)));
                die();
            }

            if ($columns) {
                foreach($columns as $key => $column) {
                    if (strpos($column, ".") === false) {
                        $columns[$key] = "s." . $column;
                    }
                }
            }

            $search = str_replace(" ", "%", urldecode(reset($params)));
            if (is_numeric($search)) {
                // numeric, search by id
                $spells = array($this->getSpellById($search, false));
                $count = count($spells);
            } elseif (!empty($search)) {
                // search by name
                $params = array("name" => strtolower($search));
                $count = $this->db->QueryFetchSingleValue("SELECT COUNT(s.id) FROM spells_new s WHERE LOWER(s.name) LIKE '%:name%'", $params);
                $spells = $this->db->QueryFetchAssoc("SELECT " . implode(",", $columns) . " FROM spells_new s WHERE LOWER(s.name) LIKE '%:name%' ORDER BY s.name ASC" . $limit, $params);
            } else {
                $count = $this->db->QueryFetchSingleValue("SELECT COUNT(s.id) FROM spells_new s WHERE s.name != ''");
                $spells = $this->db->QueryFetchAssoc("SELECT " . implode(",", $columns) . " FROM spells_new s WHERE s.name != '' ORDER BY s.name ASC" . $limit);
            }

            $spells = $this->processForApi($spells);

            $this->outputHeaders();
            echo json_encode(array("total" => $count, "limit" => intval(str_replace(" LIMIT ", "", $limit)), "results" => $spells));
        }

        //
        // Searches spell sets
        //
        function searchspellsets($params = null, $options) {
            $this->outputHeaders();
            // options will contain params such as limit and columns
            $limit = (property_exists($options, 'limit') && $options->limit > 0) ? " LIMIT " . $options->limit : ((property_exists($options, 'limit') && $options->limit === 0) ? "" : " LIMIT 75");
            $columns = (!empty($options->columns)) ? $options->columns : array('ns.*');

            if ($columns) {
                foreach($columns as $key => $column) {
                    if (strpos($column, ".") === false) {
                        $columns[$key] = "ns." . $column;
                    }
                }
            }

            $search = str_replace(" ", "%", urldecode(reset($params)));
            if (!empty($search)) {
                // search by name
                $params = array("name" => strtolower($search));
                $count = $this->db->QueryFetchSingleValue("SELECT COUNT(nse.id) FROM npc_spells ns LEFT JOIN npc_spells_entries nse ON (nse.npc_spells_id = ns.id) LEFT JOIN spells_new s ON (nse.spellid = s.id) WHERE LOWER(s.name) LIKE '%:name%'", $params);
                $spellsets = $this->db->QueryFetchAssoc("SELECT " . implode(",", $columns) . " FROM npc_spells ns LEFT JOIN npc_spells_entries nse ON (nse.npc_spells_id = ns.id) LEFT JOIN spells_new s ON (nse.spellid = s.id) WHERE LOWER(s.name) LIKE '%:name%' GROUP BY ns.id ORDER BY ns.name ASC" . $limit, $params);
            } else {
                $count = $this->db->QueryFetchSingleValue("SELECT COUNT(nse.id) FROM npc_spells ns LEFT JOIN npc_spells_entries nse ON (nse.npc_spells_id = ns.id) LEFT JOIN spells_new s ON (nse.spellid = s.id)");
                $spellsets = $this->db->QueryFetchAssoc("SELECT " . implode(",", $columns) . " FROM npc_spells ns LEFT JOIN npc_spells_entries nse ON (nse.npc_spells_id = ns.id) LEFT JOIN spells_new s ON (nse.spellid = s.id) GROUP BY ns.id ORDER BY ns.name ASC" . $limit, $params);
            }

            $spellsets = $this->processSpellsetsForApi($spellsets, $search);

            $this->outputHeaders();
            echo json_encode(array("total" => $count, "limit" => intval(str_replace(" LIMIT ", "", $limit)), "results" => $spellsets));
        }

        //
        // TODO: Update spell
        //
        function update($params) {
            $spellId = reset($params);

        }

        //
        // TODO: Duplicate spell
        //
        function duplicate($params) {
            $spellId = reset($params);

        }

        //
        // TODO: Delete spell
        //
        function delete($params) {
            $spellId = reset($params);

        }

        //
        // Get spell by id, used by both search methods
        //
        function getSpellById($id, $verbose = null) {
            if ($verbose) {
                return $this->db->QueryFetchRow("SELECT s.* FROM spells_new s WHERE s.id = :id LIMIT 1", array("id" => $id));
            } else {
                return $this->db->QueryFetchRow("SELECT s.id, s.name, s.range, s.cast_time, s.mana, s.icon, s.new_icon, s.memicon, s.skill, s.zonetype, s.nodispell, s.spellgroup FROM spells_new s WHERE s.id = :id LIMIT 1", array("id" => $id));
            }
        }

        function processForApi($spells) {
            global $skilltypes, $sp_spellgroups, $sp_targets;
            foreach ($spells as $key => $spell) {
                //$chars[$key]['classId'] = $char['class'];
                $spells[$key]['categoryName'] = $sp_spellgroups[$spell['spell_category']];
                $spells[$key]['skillName'] = $skilltypes[$spell['skill']];
                $spells[$key]['targetType'] = $sp_targets[$spell['targettype']];
            }
            return $spells;
        }

        function processSpellsetsForApi($spellsets, $search) {
            foreach ($spellsets as $key => $spellset) {
                $spells = $this->db->QueryFetchColumn("SELECT s.name FROM npc_spells_entries nse LEFT JOIN spells_new s ON (nse.spellid = s.id) WHERE LOWER(s.name) LIKE '%:name%' AND nse.npc_spells_id = :spellset_id", array("name" => $search, "spellset_id" => $spellset['id']));
                $spellsets[$key]['spells'] = implode(", ", $spells);
            }
            return $spellsets;
        }
    }

?>
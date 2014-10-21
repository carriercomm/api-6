<?php

    error_reporting(E_ERROR);
    ini_set('display_errors', '1');

    class spell extends base {
        var $db;
        var $callback;
        function spell($Token, $callback, $post) {
            $this->callback = $callback;
            parent::base($Token, $callback, $post);
        }

        //
        // Searches spell table with minimal info returned
        //
        function search($params = null, $options = null) {
            $limit = $this->paginate($options['limit'], $options['page']);
            $default_sort = array(
                "property" => "name", 
                "direction" => "ASC"
            );
            $sort = $this->sort((array)reset(json_decode($options['sort'])), $default_sort);
            $columns = (!empty($options->columns)) ? $options->columns : array('s.*');
            /*$invalid = $this->findInvalidColumns($columns, 'spells_new');
            if (count($invalid) > 0) {
                $this->outputHeaders();
                echo json_encode(array("error" => "The following are invalid columns: " . implode(", ", $invalid)));
                die();
            }*/

            if ($columns) {
                foreach($columns as $key => $column) {
                    if (strpos($column, ".") === false) {
                        $columns[$key] = "s." . $column;
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
                $spells = array($this->getSpellById($search, false));
                $count = count($spells);
            } elseif (!empty($search)) {
                // search by name
                $where = $this->find(strtolower($search), array("LOWER(s.name)"));

                $count = $this->db->QueryFetchSingleValue("SELECT COUNT(s.id) FROM spells_new s " . $where . $sort);
                $spells = $this->db->QueryFetchAssoc("SELECT " . implode(",", $columns) . " FROM spells_new s " . $where . $sort . $limit);
            } else {
                $count = $this->db->QueryFetchSingleValue("SELECT COUNT(s.id) FROM spells_new s WHERE s.name != ''" . $sort);
                $spells = $this->db->QueryFetchAssoc("SELECT " . implode(",", $columns) . " FROM spells_new s WHERE s.name != ''" . $sort . $limit);
            }

            $spells = $this->processForApi($spells);

            $this->outputHeaders();
            echo $this->callback . "(" . json_encode(array("totalCount" => $count, "limit" => $options['limit'], "data" => $spells)) . ");";
        }

        //
        // Searches spell sets
        //
        function searchspellsets($params = null, $options = null) {
            $limit = $this->paginate($options['limit'], $options['page']);
            $default_sort = array(
                "property" => "ns.name", 
                "direction" => "DESC"
            );
            if (strpos($options['sort']['property'], ".") === false) {
                $options['sort']['property'] = "ns." . $options['sort']['property'];
            }
            $sort = $this->sort((array)reset(json_decode($options['sort'])), $default_sort);
            $group = $this->group("ns.id");
            $columns = (!empty($options->columns)) ? $options->columns : array('ns.*');

            if ($columns) {
                foreach($columns as $key => $column) {
                    if (strpos($column, ".") === false) {
                        $columns[$key] = "ns." . $column;
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

            if (!empty($search)) {
                // search by name
                $where = $this->find(strtolower($search), array("LOWER(s.name)", "LOWER(ns.name)"));

                $count = $this->db->QueryFetchSingleValue("SELECT COUNT(nse.id) FROM npc_spells ns LEFT JOIN npc_spells_entries nse ON (nse.npc_spells_id = ns.id) LEFT JOIN spells_new s ON (nse.spellid = s.id) " . $where . $sort);
                $spellsets = $this->db->QueryFetchAssoc("SELECT " . implode(",", $columns) . " FROM npc_spells ns LEFT JOIN npc_spells_entries nse ON (nse.npc_spells_id = ns.id) LEFT JOIN spells_new s ON (nse.spellid = s.id) " . $where . $group . $sort . $limit);
            } else {
                $count = $this->db->QueryFetchSingleValue("SELECT COUNT(nse.id) FROM npc_spells ns LEFT JOIN npc_spells_entries nse ON (nse.npc_spells_id = ns.id) LEFT JOIN spells_new s ON (nse.spellid = s.id)" . $sort);
                $spellsets = $this->db->QueryFetchAssoc("SELECT " . implode(",", $columns) . " FROM npc_spells ns LEFT JOIN npc_spells_entries nse ON (nse.npc_spells_id = ns.id) LEFT JOIN spells_new s ON (nse.spellid = s.id)" . $group . $sort . $limit);
            }

            $spellsets = $this->processSpellsetsForApi($spellsets, $search);

            $this->outputHeaders();
            echo $this->callback . "(" . json_encode(array("totalCount" => $count, "limit" => $options['limit'], "data" => $spellsets)) . ");";
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
        function getSpellById($id, $columns) {
            return $this->db->QueryFetchRow("SELECT " . implode(",", $columns) . " FROM spells_new s WHERE s.id = :id LIMIT 1", array("id" => $id));
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
            if (!empty($search)) {
                foreach ($spellsets as $key => $spellset) {
                    $spells = $this->db->QueryFetchColumn("SELECT s.name FROM npc_spells_entries nse LEFT JOIN spells_new s ON (nse.spellid = s.id) WHERE LOWER(s.name) LIKE '%:name%' AND nse.npc_spells_id = :spellset_id", array("name" => $search, "spellset_id" => $spellset['id']));
                    $spellsets[$key]['spells'] = implode(", ", $spells);
                }
            } else {
                foreach ($spellsets as $key => $spellset) {
                    $spells = $this->db->QueryFetchColumn("SELECT s.name FROM npc_spells_entries nse LEFT JOIN spells_new s ON (nse.spellid = s.id) WHERE nse.npc_spells_id = :spellset_id", array("spellset_id" => $spellset['id']));
                    $spellsets[$key]['spells'] = implode(", ", $spells);
                }
            }
            
            return $spellsets;
        }
    }

?>
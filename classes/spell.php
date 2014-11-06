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

            $options['sort'] = (array)reset(json_decode($options['sort']));
            if (strpos($options['sort']['property'], ".") === false) {
                $options['sort']['property'] = "s." . $options['sort']['property'];
            }
            
            $sort = $this->sort($options['sort'], $default_sort, 's', null);
            $columns = (!empty($options->columns)) ? $options->columns : array('s.*');

            if ($columns) {
                foreach($columns as $key => $column) {
                    if (strpos($column, ".") === false) {
                        $columns[$key] = "s." . $column;
                    }
                }
            }

            if (!empty($options['filter'])) {
                $options['filter'] = (array)json_decode($options['filter']);
            }

            $filters = $this->filter($options['filter'], 's');

            if (!empty(trim($options['query']))) {
                $search = $options['query'];
            } else {
                if (!empty($params[0])) {
                    $search = str_replace(" ", "%", urldecode(reset($params)));
                }
            }
            
            $search = (!empty($search)) ? $search : "";

            if (is_numeric($search)) {
                // numeric, search by id
                $spells = array($this->getSpellById($search, false));
                $count = count($spells);
            } else {
                $where = $this->find(strtolower($search), array("LOWER(s.name)"), $filters);
                $count = $this->db->QueryFetchSingleValue("SELECT COUNT(s.id) FROM spells_new s " . $where . $sort);
                $spells = $this->db->QueryFetchAssoc("SELECT " . implode(",", $columns) . " FROM spells_new s " . $where . $sort . $limit);
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
            
            $group = $this->group("ns.id");
            
            $sort = $this->sort($options['sort'], $default_sort, 'ns', null);
            $columns = (!empty($options->columns)) ? $options->columns : array('ns.*');
            
            if ($columns) {
                foreach($columns as $key => $column) {
                    if (strpos($column, ".") === false) {
                        $columns[$key] = "ns." . $column;
                    }
                }
            }

            $filters = $this->filter($options['filter'], 'ns');

            if (!empty(trim($options['query']))) {
                $search = $options['query'];
            } else {
                if (!empty($params[0])) {
                    $search = str_replace(" ", "%", urldecode(reset($params)));
                }
            }

            $search = (!empty($search)) ? $search : "";

            $where = $this->find(strtolower($search), array("LOWER(s.name)", "LOWER(ns.name)"), $filters);
            $count = $this->db->QueryFetchSingleValue("SELECT COUNT(nse.id) FROM npc_spells ns LEFT JOIN npc_spells_entries nse ON (nse.npc_spells_id = ns.id) LEFT JOIN spells_new s ON (nse.spellid = s.id) " . $where . $sort);
            $spellsets = $this->db->QueryFetchAssoc("SELECT " . implode(",", $columns) . " FROM npc_spells ns LEFT JOIN npc_spells_entries nse ON (nse.npc_spells_id = ns.id) LEFT JOIN spells_new s ON (nse.spellid = s.id) " . $where . $group . $sort . $limit);

            $spellsets = $this->processSpellsetsForApi($spellsets);

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
            return $spells;
        }

        function processSpellsetsForApi($spellsets) {
            foreach ($spellsets as $key => $spellset) {
                $spells = $this->db->QueryFetchColumn("SELECT s.name FROM npc_spells_entries nse LEFT JOIN spells_new s ON (nse.spellid = s.id) WHERE nse.npc_spells_id = :spellset_id", array("spellset_id" => $spellset['id']));
                $spellsets[$key]['spells'] = implode(", ", $spells);

                if ((int)$spellset['attack_proc'] > 0) {
                    $spellName = $this->db->QueryFetchSingleValue("SELECT name FROM spells_new WHERE id = :id", array("id" => $spellset['attack_proc']));
                    if ($spellName) {
                        $spellsets[$key]['attackProcSpell'] = $spellName;
                    }
                } else {
                    $spellsets[$key]['attackProcSpell'] = "None";
                }

                if ((int)$spellset['range_proc'] > 0) {
                    $spellName = $this->db->QueryFetchSingleValue("SELECT name FROM spells_new WHERE id = :id", array("id" => $spellset['range_proc']));
                    if ($spellName) {
                        $spellsets[$key]['rangeProcSpell'] = $spellName;
                    }
                } else {
                    $spellsets[$key]['rangeProcSpell'] = "None";
                }

                if ((int)$spellset['defensive_proc'] > 0) {
                    $spellName = $this->db->QueryFetchSingleValue("SELECT name FROM spells_new WHERE id = :id", array("id" => $spellset['defensive_proc']));
                    if ($spellName) {
                        $spellsets[$key]['defensiveProcSpell'] = $spellName;
                    }
                } else {
                    $spellsets[$key]['defensiveProcSpell'] = "None";
                }
            }
            
            return $spellsets;
        }
    }

?>
<?php

    error_reporting(E_ERROR);
    ini_set('display_errors', '1');

    class npc extends base {
        var $db;
        var $callback;
        function npc($Token, $callback, $post) {
            $this->callback = $callback;
            parent::base($Token, $callback, $post);
        }

        //
        // Searches npc_types table
        //
        function search($params = null, $options = null) {
            $limit = $this->paginate($options['limit'], $options['page']);
            
            $default_sort = array(
                "property" => "LOWER(REPLACE(q.name, '#', ''))", 
                "direction" => "ASC"
            );

            $sort = $this->sort($options['sort'], $default_sort, 'q', array("q.name" => "LOWER(REPLACE(q.name, '#', ''))"));
            $columns = (!empty($options->columns)) ? $options->columns : array('n.*');

            if ($columns) {
                foreach($columns as $key => $column) {
                    if (strpos($column, ".") === false) {
                        $columns[$key] = "n." . $column;
                    }
                }
            }

            $filters = $this->filter($options['filter'], 'q');

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
                $npcs = array($this->getNpcById($search));
                $count = count($npcs);
            } else {
                $where = $this->find(strtolower($search), array("LOWER(q.name)", "LOWER(q.lastname)"), $filters);

                $sql = "
                SELECT * FROM (
                    SELECT 
                        " . implode(",", $columns) . ",
                        nf.primaryfaction, 
                        nf.ignore_primary_assist, 
                        z.zones AS zones,
                        z.count AS numZones,
                        (SELECT COUNT(DISTINCT nfe.faction_id) FROM npc_faction_entries nfe LEFT JOIN faction_list fl ON (fl.id = nfe.faction_id) WHERE nfe.npc_faction_id = n.npc_faction_id GROUP BY nfe.npc_faction_id) AS numFactionHits,
                        (SELECT GROUP_CONCAT(CONCAT('[', nfe.faction_id, '] ', fl.name, ' : ', nfe.value)) FROM npc_faction_entries nfe LEFT JOIN faction_list fl ON (fl.id = nfe.faction_id) WHERE nfe.npc_faction_id = n.npc_faction_id GROUP BY nfe.npc_faction_id) AS faction_hits
                    FROM
                        npc_types n 
                        LEFT JOIN npc_faction nf ON (nf.id = n.npc_faction_id) 
                        INNER JOIN (
                            SELECT 
                                se.npcID AS znpcid, 
                                count(DISTINCT s2.zone) as count,
                                GROUP_CONCAT(DISTINCT s2.zone ORDER BY s2.zone) AS zones
                            FROM
                                spawn2 s2
                            LEFT JOIN
                                spawnentry se ON (se.spawngroupID = s2.spawngroupID)
                            GROUP BY
                                se.npcID
                        ) z ON z.znpcid = n.id 
                ) q " . $where . $sort;

                $count = $this->db->QueryNumberOfRows($sql);
                $npcs = $this->db->QueryFetchAssoc($sql . $limit);
            }
            
            $npcs = $this->processForApi($npcs);
            if ($group) {
                $npcs = $this->groupData($npcs);
            }

            $this->outputHeaders();
            echo $this->callback . "(" . json_encode(array("totalCount" => $count, "limit" => $options['limit'], "data" => $npcs)) . ");";
        }

        function searchmerchants($params = null, $options = null) {
            $limit = $this->paginate($options['limit'], $options['page']);
            
            $default_sort = array(
                "property" => "LOWER(REPLACE(q.name, '#', ''))", 
                "direction" => "ASC"
            );
            $options['sort'] = (array)reset(json_decode($options['sort']));
            if (!empty($options['sort'])) {
                if (strpos($options['sort']['property'], ".") === false) {
                    $options['sort']['property'] = "q." . $options['sort']['property'];
                }
                if ($options['sort']['property'] == 'q.name') {
                    $options['sort']['property'] = "LOWER(REPLACE(q.name, '#', ''))";
                }
            }
            
            $sort = $this->sort($options['sort'], $default_sort);
            $columns = (!empty($options->columns)) ? $options->columns : array('n.*');

            if ($columns) {
                foreach($columns as $key => $column) {
                    if (strpos($column, ".") === false) {
                        $columns[$key] = "n." . $column;
                    }
                }
            }

            //$columns[] = "(SELECT COUNT(*) FROM merchantlist ml WHERE ml.merchantid = n.merchant_id) AS numItems";
            //$columns[] = "(SELECT COUNT(*) FROM merchantlist_temp mlt WHERE mlt.npcid = n.id) AS numTempItems";

            if (!empty($options['filter'])) {
                $options['filter'] = (array)json_decode($options['filter']);
            }

            foreach($options['filter'] as $key => $filter) {
                $options['filter'][$key] = (array)$options['filter'][$key];
                $filter = (array)$filter;
                if (strpos($filter['field'], ".") === false) {
                    $options['filter'][$key]['field'] = "q." . $options['filter'][$key]['field'];
                }
            }

            $filters = $this->filter($options['filter']);

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
                $npcs = array($this->getNpcById($search));
                $count = count($npcs);
            } else {
                $filters[] = "q.merchant_id > 0";
                $filters[] = "q.merchant_id IS NOT NULL";
                $where = $this->find(strtolower($search), array("LOWER(q.name)", "LOWER(q.lastname)", "LOWER(q.items)"), $filters);

                $sql = "
                SELECT * FROM (
                    SELECT 
                        " . implode(",", $columns) . ",
                        nf.primaryfaction, 
                        nf.ignore_primary_assist,
                        z.zones AS zones,
                        z.count AS numZones,
                        (SELECT COUNT(DISTINCT i.name) FROM merchantlist ml LEFT JOIN items i ON (i.id = ml.item) WHERE ml.merchantid = n.merchant_id) as numItems, 
                        (SELECT GROUP_CONCAT(DISTINCT i.name ORDER BY i.name) FROM merchantlist ml LEFT JOIN items i ON (i.id = ml.item) WHERE ml.merchantid = n.merchant_id) as items
                    FROM
                        npc_types n 
                        LEFT JOIN 
                            npc_faction nf ON (nf.id = n.npc_faction_id) 
                        INNER JOIN (
                            SELECT 
                                se.npcID AS znpcid, 
                                count(DISTINCT s2.zone) as count,
                                GROUP_CONCAT(DISTINCT s2.zone ORDER BY s2.zone) AS zones
                            FROM
                                spawn2 s2
                            LEFT JOIN
                                spawnentry se ON (se.spawngroupID = s2.spawngroupID)
                            GROUP BY
                                se.npcID
                        ) z ON z.znpcid = n.id 
                ) q " . $where . $sort;

                $count = $this->db->QueryNumberOfRows($sql);
                $npcs = $this->db->QueryFetchAssoc($sql . $limit);
            }
            
            $npcs = $this->merchantProcessForApi($npcs, $search);
            $npcs = $this->processForApi($npcs);

            $this->outputHeaders();
            echo $this->callback . "(" . json_encode(array("totalCount" => $count, "limit" => $options['limit'], "data" => $npcs)) . ");";
        }

        function searchhorses($params = null, $options) {
            global $races;

            // POST will contain params such as limit and columns
            $limit = (property_exists($options, 'limit') && $options->limit > 0) ? " LIMIT " . $options->limit : ((property_exists($options, 'limit') && $options->limit === 0) ? "" : " LIMIT 75");
            $columns = (!empty($options->columns)) ? $options->columns : array('h.*');
            $invalid = $this->findInvalidColumns($columns, 'horses');

            if (count($invalid) > 0) {
                $this->outputHeaders();
                echo json_encode(array("error" => "The following are invalid columns: " . implode(", ", $invalid)));
                die();
            }

            if ($columns) {
                foreach($columns as $column) {
                    if (strpos($column, ".") === false) {
                        $column = "h." . $column;
                    }
                }
            }

            $search = str_replace(" ", "%", urldecode(reset($params)));
            if (!empty($search)) {
                // search by name
                $params = array("name" => strtolower($search));
                $count = $this->db->QueryFetchSingleValue("SELECT COUNT(h.filename) FROM horses h WHERE LOWER(h.filename) LIKE '%:name%' OR LOWER(h.notes) LIKE '%:name%'", $params);
                $horses = $this->db->QueryFetchAssoc("SELECT " . implode(",", $columns) . " FROM horses h WHERE LOWER(h.filename) LIKE '%:name%' OR LOWER(h.notes) LIKE '%:name%' ORDER BY LOWER(h.notes) ASC" . $limit, $params);
            } else {
                $count = $this->db->QueryFetchSingleValue("SELECT COUNT(h.filename) FROM horses h");
                $horses = $this->db->QueryFetchAssoc("SELECT " . implode(",", $columns) . " FROM horses h ORDER BY LOWER(h.notes) ASC" . $limit);
            }
            
            foreach ($horses as $key => $horse) {
                $horses[$key]['raceName'] = $races[$horse['race']];
            }

            $this->outputHeaders();
            echo json_encode(array("total" => $count, "limit" => intval(str_replace(" LIMIT ", "", $limit)), "results" => $horses));
        }

        //
        // TODO: Add npc
        //
        function add($params) {

        }

        //
        // TODO: Update npc
        //
        function update($params) {
            $npcId = reset($params);

        }

        //
        // TODO: Duplicate npc
        //
        function duplicate($params) {
            $npcId = reset($params);

        }

        //
        // TODO: Delete npc
        //
        function delete($params) {
            $npcId = reset($params);

        }

        //
        // Get npc by id, used by both search methods
        //
        function getNpcById($id) {
            return $this->db->QueryFetchRow("SELECT " . implode(",", $columns) . " FROM npc_types n WHERE n.id = :id LIMIT 1", array("id" => $id));
        }

        function processForApi($npcs) {
            foreach ($npcs as $key => $npc) {
                // Cleanup last name and first name to be human readable
                $npcs[$key]['lastname'] = preg_replace("/\d+$/", "", trim(str_replace("#", "", str_replace("_", " ", $npc['lastname']))));
                $npc_name = preg_replace("/\d+$/", "", trim(str_replace("#", "", str_replace("_", " ", $npc['name']))));
                $npcs[$key]['firstname'] = preg_replace("/\d+$/", "", trim(str_replace("#", "", str_replace("_", " ", $npc['name']))));
                if (empty($npc_name)) {
                    $npcs[$key]['name'] = "[No Name]";
                } else {
                    $npcs[$key]['name'] = preg_replace("/\d+$/", "", trim(str_replace("#", "", str_replace("_", " ", $npc['name']))));
                }
            }
            return $npcs;
        }

        function merchantProcessForApi($npcs, $search) {
            foreach ($npcs as $key => $npc) {
                $npcs[$key]['lastname'] = preg_replace("/\d+$/", "", trim(str_replace("#", "", str_replace("_", " ", $npc['lastname']))));
                $npc_name = preg_replace("/\d+$/", "", trim(str_replace("#", "", str_replace("_", " ", $npc['name']))));
                $npcs[$key]['firstname'] = preg_replace("/\d+$/", "", trim(str_replace("#", "", str_replace("_", " ", $npc['name']))));
                if (empty($npc_name)) {
                    $npcs[$key]['name'] = "[No Name]";
                } else {
                    $npcs[$key]['name'] = preg_replace("/\d+$/", "", trim(str_replace("#", "", str_replace("_", " ", $npc['name']))));
                }
            }
            return $npcs;
        }

        function groupData($npcs) {
            $new_npcs = array();
            foreach ($npcs as $npc) {
                $new = array(
                    'id' => $npc['id'],
                    'name' => $npc['name'],
                    'firstname' => $npc['firstname'],
                    'lastname' => $npc['lastname'],
                    'level' => $npc['level'],
                    'race' => $npc['race'],
                    'raceName' => $npc['raceName'],
                    'class' => $npc['class'],
                    'className' => $npc['className'],
                    'raceClass' => $npc['raceClass'],
                    'bodytype' => $npc['bodytype'],
                    'bodytypeName' => $npc['bodytypeName'],
                    'zones' => $npc['zones']
                );

                if (!empty($npc['faction'])) {
                    $new['faction'] = $npc['faction'];
                }

                // Vitals
                $vitals['ac'] = $npc['AC'];
                $vitals['hp'] = $npc['hp'];
                $vitals['mana'] = $npc['mana'];
                $vitals['run_speed'] = $npc['runspeed'];
                $vitals['accuracy'] = $npc['Accuracy'];
                $vitals['attack'] = $npc['ATK'];
                $vitals['see_invis'] = $npc['see_invis'];
                $vitals['see_invis_undead'] = $npc['see_invis_undead'];
                $vitals['see_hide'] = $npc['see_hide'];
                $vitals['see_improved_hide'] = $npc['see_improved_hide'];
                $vitals['scale_rate'] = $npc['scalerate'];

                $new['vitals'] = $vitals;

                // Stats
                $stats['str'] = $npc['STR'];
                $stats['sta'] = $npc['STA'];
                $stats['dex'] = $npc['DEX'];
                $stats['agi'] = $npc['AGI'];
                $stats['int'] = $npc['_INT'];
                $stats['wis'] = $npc['WIS'];
                $stats['cha'] = $npc['CHA'];

                $new['stats'] = $stats;

                // Resists
                $resists['mr'] = $npc['MR'];
                $resists['cr'] = $npc['CR'];
                $resists['fr'] = $npc['FR'];
                $resists['pr'] = $npc['PR'];
                $resists['dr'] = $npc['DR'];
                $resists['corrup'] = $npc['Corrup'];

                $new['resists'] = $resists;

                // Combat
                $combat['min_dmg'] = $npc['mindmg'];
                $combat['max_dmg'] = $npc['maxdmg'];
                $combat['attack_count'] = $npc['attack_count'];
                $combat['loottable_id'] = $npc['loottable_id'];
                $combat['hp_regen_rate'] = $npc['hp_regen_rate'];
                $combat['mana_regen_rate'] = $npc['mana_regen_rate'];
                $combat['aggro_radius'] = $npc['aggro_radius'];
                $combat['attack_speed'] = $npc['attack_speed'];
                $combat['special_attacks'] = $npc['npcspecialattks'];
                $combat['slow_mitigation'] = $npc['slow_mitigation'];
                $combat['npc_aggro'] = $npc['npc_aggro'];
                $combat['npc_spells_id'] = $npc['npc_spells_id'];
                $combat['npc_faction_id'] = $npc['npc_faction_id'];
                $combat['spellscale'] = $npc['spellscale'];
                $combat['healscale'] = $npc['healscale'];

                $new['combat'] = $combat;

                // Appearance
                $appearance['gender'] = $npc['gender'];
                $appearance['size'] = $npc['size'];
                $appearance['texture'] = $npc['texture'];
                $appearance['face'] = $npc['face'];
                $appearance['helm'] = $npc['helmtexture'];
                $appearance['hair_style'] = $npc['luclin_hairstyle'];
                $appearance['hair_color'] = $npc['luclin_haircolor'];
                $appearance['eye_color'] = $npc['luclin_eyecolor'];
                $appearance['eye_color2'] = $npc['luclin_eyecolor2'];
                $appearance['beard'] = $npc['luclin_beard'];
                $appearance['beard_color'] = $npc['luclin_beardcolor'];
                $appearance['drakkin_heritage'] = $npc['drakkin_heritage'];
                $appearance['drakkin_tattoo'] = $npc['drakkin_tattoo'];
                $appearance['drakkin_details'] = $npc['drakkin_details'];
                $appearance['armortint_red'] = $npc['armortint_red'];
                $appearance['armortint_green'] = $npc['armortint_green'];
                $appearance['armortint_blue'] = $npc['armortint_blue'];
                $appearance['melee_texture1'] = $npc['d_meele_texture1'];
                $appearance['melee_texture2'] = $npc['d_meele_texture2'];
                $appearance['melee_type1'] = $npc['prim_melee_type'];
                $appearance['melee_type2'] = $npc['sec_melee_type'];

                $new['appearance'] = $appearance;

                // Misc
                $misc['qglobal'] = $npc['qglobal'];
                $misc['is_findable'] = $npc['findable'];
                $misc['is_trackable'] = $npc['trackable'];
                $misc['spawn_limit'] = $npc['spawn_limit'];
                $misc['unique_spawn'] = $npc['unique_spawn_by_name'];
                $misc['underwater'] = $npc['underwater'];
                
                $pet = $this->db->QueryFetchSingleValue("SELECT COUNT(*) FROM pets WHERE npcID = :npcId", array("npcId" => $npc['id']));
                $misc['is_pet'] = ($pet) ? true : false;
                
                $misc['private_corpse'] = $npc['private_corpse'];
                $misc['version'] = $npc['version'];

                $new['misc'] = $misc;

                // Attach new npc object to new_npcs
                $new_npcs[] = $new;
            }

            return $new_npcs;
        }
    }

?>
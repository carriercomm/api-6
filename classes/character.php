<?php

    error_reporting(E_ERROR);
    ini_set('display_errors', '1');

    class character extends base {
        var $db;
        function character($Token) {
            parent::base($Token);
        }

        //
        // Searches character table
        //
        function search($params = null, $options) {
            // Options for columns will not be allowed here due to the table joins
            $profile = (property_exists($options, 'profile') && $options->profile) ? true : false;
            $limit = (property_exists($options, 'limit') && $options->limit > 0) ? " LIMIT " . $options->limit : ((property_exists($options, 'limit') && $options->limit === 0) ? "" : " LIMIT 75");
            $columns = (!empty($options->columns)) ? $options->columns : array('c.*');
            $invalid = $this->findInvalidColumns($columns, 'character_data');

            if (count($invalid) > 0) {
                $this->outputHeaders();
                echo json_encode(array("error" => "The following are invalid columns: " . implode(", ", $invalid)));
                die();
            }

            if ($columns) {
                foreach($columns as $key => $column) {
                    if (strpos($column, ".") === false) {
                        $columns[$key] = "c." . $column;
                    }
                }
            }

            $search = str_replace(" ", "%", urldecode(reset($params)));
            if (is_numeric($search)) {
                // numeric, search by id
                $chars = array($this->getCharacterById($search, true));
                $count = count($chars);
            } elseif (!empty($search)) {
                // search by name
                $params = array("name" => strtolower($search));
                $count = $this->db->QueryFetchSingleValue("SELECT COUNT(c.id) FROM character_data c LEFT JOIN account a ON (a.id = c.account_id) WHERE LOWER(c.name) LIKE '%:name%'", $params);
                $chars = $this->db->QueryFetchAssoc("SELECT " . implode(", ", $columns) . " FROM character_data c LEFT JOIN account a ON (a.id = c.account_id) WHERE LOWER(c.name) LIKE '%:name%' ORDER BY c.name ASC" . $limit, $params);
            } else {
                $count = $this->db->QueryFetchSingleValue("SELECT COUNT(c.id) FROM character_data c LEFT JOIN account a ON (a.id = c.account_id)", $params);
                $chars = $this->db->QueryFetchAssoc("SELECT " . implode(", ", $columns) . " FROM character_data c LEFT JOIN account a ON (a.id = c.account_id) ORDER BY c.name ASC" . $limit);
            }

            $chars = $this->processForApi($chars);
            if ($profile) {
                $chars = $this->parseProfile($chars);
            }

            $this->outputHeaders();
            echo json_encode(array("total" => $count, "limit" => intval(str_replace(" LIMIT ", "", $limit)), "results" => $chars));
        }

        //
        // TODO: Update character (only stuff not inside blob is updatable)
        //
        function update($params) {
            $charId = reset($params);

        }

        //
        // TODO: Delete character
        //
        function delete($params) {
            $charId = reset($params);
            
        }

        //
        // Give item to character, it will attempt to place it into the first free inventory slot
        //
        function giveitem($params) {
            $charId = reset($params);
            $itemId = end($params);

        }

        //
        // Get character by id, used by both search methods
        //
        function getCharacterById($id, $verbose = null) {
            if ($verbose) {
                return $this->db->QueryFetchRow("SELECT c.*, a.id as account_id, a.name as account_name FROM character_data c LEFT JOIN account a ON (a.id = c.account_id) WHERE c.id = :id LIMIT 1", array("id" => $id));
            } else {
                return $this->db->QueryFetchRow("SELECT c.id, c.name, c.level, c.class, c.timelaston, c.x, c.y, c.z, c.zonename, c.zoneid, c.instanceid, c.groupid, c.lfp, c.lfg, a.id as account_id, a.name as account_name FROM character_data c LEFT JOIN account a ON (a.id = c.account_id) WHERE c.id = :id LIMIT 1", array("id" => $id));
            }
        }

        //
        // Parse the profile blob of any number of character result objects
        //
        function parseProfile($chars) {
            $struct =  "Lchecksum/a64name/a32last_name/Lgender/Lrace/Lclass/Lunknown0112/Llevel/";

            $binds = 5;
            for ($x = 0; $x < $binds; $x++) {
                $struct .= "Lbind" . ($x + 1) . "_zone/";
                $struct .= "fbind" . ($x + 1) . "_x/";
                $struct .= "fbind" . ($x + 1) . "_y/";
                $struct .= "fbind" . ($x + 1) . "_z/";
                $struct .= "fbind" . ($x + 1) . "_h/";
            }

            $struct .= "Ldeity/Lguild_id/Lbirthday/Llastzone/Ltimeplayed/cpvp/clevel2/canon/cgm/";
            $struct .= "cguildrank/cguildbanker/c6unknown0246/Ldrunk/L9spellslotrefresh/";
            $struct .= "Labilityslotrefresh/chaircolor/cbeardcolor/clefteye/crighteye/";
            $struct .= "chairstyle/cbeard/cability_time_seconds/cability_number/";
            $struct .= "cability_time_minutes/cability_time_hours/c6unknown0306_/";
            $struct .= "L9item_material/c44unknown0348_/";

            $colors = 9;
            for ($x = 0; $x < $colors; $x++) {
                $struct .= "citemtint" . ($x + 1) . "_blue/";
                $struct .= "citemtint" . ($x + 1) . "_green/";
                $struct .= "citemtint" . ($x + 1) . "_red/";
                $struct .= "citemtint" . ($x + 1) . "_use_tint/";
            }

            $maxaa = 240;
            for ($x = 0; $x < $maxaa; $x++) {
                $struct .= "Laa" . ($x + 1) . "_id/";
                $struct .= "Laa" . ($x + 1) . "_value/";
            }

            $struct .= "funknown2348/a32servername/a32title/a32suffix/Lguildid2/Lexp/Lunknown2456/";
            $struct .= "Lpractice/Lmana/Lhp/Lunknown2472/LSTR/LSTA/LCHA/LDEX/L_INT/LAGI/LWIS/cface/";
            $struct .= "c47unknown2505_/c28languages/c4unknown2580_/l480spellbook/c128unknown4504_/";
            $struct .= "l9mem_spells/c32unknown4668_/fy/fx/fz/fheading/c4unknown4716_/lplatinum/";
            $struct .= "lgold/lsilver/lcopper/lplatinum_bank/lgold_bank/lsilver_bank/lcopper_bank/";
            $struct .= "lplatinum_hand/lgold_hand/lsilver_hand/lcopper_hand/lplatinum_shared/";
            $struct .= "c24unknown4772_/L75skills/c284unknown5096_/lpvp2/lunknown5384/lpvptype/";
            $struct .= "lunknown5392/Lability_down/c8unknown5400_/Lautosplit/c8unknown5412_/";
            $struct .= "lzone_change_count/c16unknown5424_/Ldrakkin_heritage/Ldrakkin_tattoo/";
            $struct .= "Ldrakkin_details/lexpansions/ltoxicity/c16unknown5460_/lhunger/lthirst/";
            $struct .= "lability_up/c16unknown5488_/Szone_id/Sinstanceid/";

            $maxbuff = 25;
            for ($x = 0; $x < $maxbuff; $x++) {
                $struct .= "ceffect" . ($x + 1) . "slotid/";
                $struct .= "ceffect" . ($x + 1) . "level/";
                $struct .= "ceffect" . ($x + 1) . "bard_mod/";
                $struct .= "ceffect" . ($x + 1) . "effect/";
                $struct .= "leffect" . ($x + 1) . "spellid/";
                $struct .= "leffect" . ($x + 1) . "duration/";
                $struct .= "seffect" . ($x + 1) . "ds_remaining/";
                $struct .= "ceffect" . ($x + 1) . "persistent_buff/";
                $struct .= "ceffect" . ($x + 1) . "reserved/";
                $struct .= "leffect" . ($x + 1) . "playerid/";
            }

            $struct .= "a64groupmember1/a64groupmember2/a64groupmember3/a64groupmember4/a64groupmember5/";
            $struct .= "a64groupmember6/c656unknown6392_/Lentityid/Lleader_aa_active/Lunknown7056/";
            $struct .= "lguk_points/lmir_points/lmmc_points/lruj_points/ltak_points/lavail_points/";
            $struct .= "lguk_wins/lmir_wins/lmmc_wins/lruj_wins/ltak_wins/lguk_losses/lmir_losses/";
            $struct .= "lmmc_losses/lruj_losses/ltak_losses/c72unknown7124_/Ltribute_timer/Lshowhelm/";
            $struct .= "Ltribute_total/Lunknown7208/Ltribute_points/Lunknown7216/Ltribute_active/";

            $tributes = 5;
            for ($x = 0; $x < $tributes; $x++) {
                $struct .= "Ltribute" . ($x + 1) . "/";
                $struct .= "Ltribute" . ($x + 1) . "tier/";
            }

            $struct .= "L100disciplines/L20recast_timer/c160unknown7744_/Lendurance/Lgroup_exp/Lraid_exp/";
            $struct .= "Lgroup_points/Lraid_points/L32leader_ability/c132unknown8052_/Lair/Lpvp_kills/";
            $struct .= "Lpvp_deaths/Lpvp_points/Lpvp_total/Lpvp_killstreak_max/Lpvp_deathstreak_max/";
            $struct .= "Lpvp_killstreak_now/a64pvplastkill_name/Lpvplastkill_level/Lpvplastkill_race/";
            $struct .= "Lpvplastkill_class/Lpvplastkill_zone/Lpvplastkill_time/Lpvplastkill_points/";
            $struct .= "a64pvplastdeath_name/Lpvplastdeath_level/Lpvplastdeath_race/Lpvplastdeath_class/";
            $struct .= "Lpvplastdeath_zone/Lpvplastdeath_time/Lpvplastdeath_points/Lpvp_kills_today/";

            for ($x = 0; $x < 50; $x++) {
                $struct .= "a64pvprecentkill" . ($x + 1) . "_name/";
                $struct .= "Lpvprecentkill" . ($x + 1) . "_level/";
                $struct .= "Lpvprecentkill" . ($x + 1) . "_race/";
                $struct .= "Lpvprecentkill" . ($x + 1) . "_class/";
                $struct .= "Lpvprecentkill" . ($x + 1) . "_zone/";
                $struct .= "Lpvprecentkill" . ($x + 1) . "_time/";
                $struct .= "Lpvprecentkill" . ($x + 1) . "_points/";
            }

            $struct .= "Laa_spent/Laa_exp/Laa_points/c36unknown12808_/";

            //Bandolier
            for ($x = 0; $x < 4; $x++) {
                $struct .= "a32bandolier" . ($x + 1) . "_name/";
                for ($y = 0; $y < 4; $y++) {
                    $struct .= "Lbandolier" . ($x + 1) . "_item" . ($y + 1) . "_id/";
                    $struct .= "Lbandolier" . ($x + 1) . "_item" . ($y + 1) . "_icon/";
                    $struct .= "a64bandolier" . ($x + 1) . "_item" . ($y + 1) . "_name/";
                }
            }

            $struct .= "c4506unknown14124_/Ssm_spellid/Lsm_hp/Lsm_mana/";
            
            for ($x = 0; $x < $maxbuff; $x++) {
                $struct .= "csmbuffs" . ($x + 1) . "_slotid/";
                $struct .= "csmbuffs" . ($x + 1) . "_level/";
                $struct .= "csmbuffs" . ($x + 1) . "_bard_mod/";
                $struct .= "csmbuffs" . ($x + 1) . "_effect/";
                $struct .= "lsmbuffs" . ($x + 1) . "_spellid/";
                $struct .= "lsmbuffs" . ($x + 1) . "_duration/";
                $struct .= "ssmbuffs" . ($x + 1) . "_ds_remaining/";
                $struct .= "csmbuffs" . ($x + 1) . "_persistent_buff/";
                $struct .= "csmbuffs" . ($x + 1) . "_reserved/";
                $struct .= "lsmbuffs" . ($x + 1) . "_playerid/";
            }

            $struct .= "L9sm_item/a64sm_name/Ltimeonaccount/";

            //potion belt
            for ($x = 0; $x < 4; $x++) {
                $struct .= "Lpotion" . ($x + 1) . "_item_id/";
                $struct .= "Lpotion" . ($x + 1) . "_icon/";
                $struct .= "a64potion" . ($x + 1) . "_name/";
            }

            $struct .= "c8unknown19532_/Lradiant_crystals/Lradiant_total/Lebon_crystals/Lebon_total/";
            $struct .= "cgroup_consent/craid_consent/cguild_consent/c5unknown19559_/Lresttimer";

            foreach ($chars as $key => $char) {
                $chars[$key]['profile'] = unpack($struct, $char['profile']);
                unset($chars[$key]['extprofile']);
            }

            return $chars;
        }

        function processForApi($chars) {
            global $classes;
            foreach ($chars as $key => $char) {
                $chars[$key]['classId'] = $char['class'];
                $chars[$key]['className'] = $classes[$char['class']];
                unset($chars[$key]['class']);
                $chars[$key]['loc'] = $char['x'] . ", " . $char['y'] . ", " . $char['z'];
                $chars[$key]['zoneId'] = $char['zoneid'];
                $chars[$key]['zoneName'] = $char['zonename'];
                unset($chars[$key]['zoneid']);
                unset($chars[$key]['zonename']);
            }
            return $chars;
        }
    }

?>
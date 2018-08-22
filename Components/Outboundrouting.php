<?php
namespace FreePBX\modules\Core\Components;
use PDO;
use Exception;

class Outboundrouting extends ComponentBase{

    public function add($name, $outcid, $outcid_mode, $password, $emergency_route, $intracompany_route, $mohclass, $time_group_id, $patterns, $trunks, $seq = 'new', $dest = '', $time_mode = '', $timezone = '', $calendar_id = '', $calendar_group_id = ''){
        $sql = "INSERT INTO `outbound_routes` (`name`, `outcid`, `outcid_mode`, `password`, `emergency_route`, `intracompany_route`, `mohclass`, `time_group_id`, `dest`, `time_mode`, `timezone`)
	VALUES (:name, :outcid, :outcid_mode, :password, :emergency_route,  :intracompany_route,  :mohclass, :time_group_id, :dest, :time_mode, :timezone)";

        $sth = $this->Database->prepare($sql);
        $sth->execute(array(
            ":name" => $name,
            ":outcid" => $outcid,
            ":outcid_mode" => trim($outcid) == '' ? '' : $outcid_mode,
            ":password" => $password,
            ":emergency_route" => strtoupper($emergency_route),
            ":intracompany_route" => strtoupper($intracompany_route),
            ":mohclass" => $mohclass,
            ":time_group_id" => $time_group_id,
            ":dest" => $dest,
            ":time_mode" => $time_mode,
            ":timezone" => $timezone
        ));

        $route_id = $this->Database->lastInsertId();
        
        $this->updatePatterns($route_id, $patterns)
            ->updateTrunks($route_id, $trunks)
            ->setRouteOrder($route_id, $seq);

        return $route_id;
    }

    public function addWithId($id, $name, $outcid, $outcid_mode, $password, $emergency_route, $intracompany_route, $mohclass, $time_group_id, $patterns, $trunks, $seq = 'new', $dest = '', $time_mode = '', $timezone = '', $calendar_id = '', $calendar_group_id = ''){
        $sql = "REPLACE INTO `outbound_routes` (`route_id`,`name`, `outcid`, `outcid_mode`, `password`, `emergency_route`, `intracompany_route`, `mohclass`, `time_group_id`, `dest`, `time_mode`, `timezone`)
    VALUES (:route_id, :name, :outcid, :outcid_mode, :password, :emergency_route,  :intracompany_route,  :mohclass, :time_group_id, :dest, :time_mode, :timezone)";
        $sth = $this->Database->prepare($sql);
        $sth->execute(array(
            ":route_id" => $id,
            ":name" => $name,
            ":outcid" => $outcid,
            ":outcid_mode" => trim($outcid) == '' ? '' : $outcid_mode,
            ":password" => $password,
            ":emergency_route" => strtoupper($emergency_route),
            ":intracompany_route" => strtoupper($intracompany_route),
            ":mohclass" => $mohclass,
            ":time_group_id" => $time_group_id,
            ":dest" => $dest,
            ":time_mode" => $time_mode,
            ":timezone" => $timezone
        ));

        $route_id = $this->Database->lastInsertId();
        
        $this->updatePatterns($route_id, $patterns)
            ->updateTrunks($route_id, $trunks)
            ->setRouteOrder($route_id, $seq);

        return $route_id;
    }

    public function deleteById($route_id){
        $sql = 'DELETE FROM outbound_routes WHERE route_id = ?';
        $sth = $this->Database->prepare($sql);
        $sth->execute(array($route_id));
        $sql = 'DELETE FROM outbound_route_patterns WHERE route_id = ?';
        $sth = $this->Database->prepare($sql);
        $sth->execute(array($route_id));
        $sql = 'DELETE FROM outbound_route_trunks WHERE route_id = ?';
        $this->Database->prepare($sql);
        $sth->execute(array($route_id));
        $sql = 'DELETE FROM outbound_route_sequence WHERE route_id = ?';
        $sth = $this->Database->prepare($sql);
        $sth->execute(array($route_id));
        return $this;
    }

    public function setRouteOrder($route_id, $seq){
        $sql = "SELECT `route_id` FROM `outbound_route_sequence` ORDER BY `seq`";
        $sequence = $this->Database->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        if($sequence === false){
            $sequence = [];
        }
        if ($seq != 'new') {
            $key = array_search($route_id, $sequence);
            if ($key === false) {
                return (false);
            }
        }
        switch ("$seq") {
            case 'up':
                if (!isset($sequence[$key - 1])){
                    break;
                }
                $previous = $sequence[$key - 1];
                $sequence[$key - 1] = $route_id;
                $sequence[$key] = $previous;
                break;
            case 'down':
                if (!isset($sequence[$key + 1])){
                    break;
                }
                $previous = $sequence[$key + 1];
                $sequence[$key + 1] = $route_id;
                $sequence[$key] = $previous;
                break;
            case 'top':
                unset($sequence[$key]);
                array_unshift($sequence, $route_id);
                break;
            case 'bottom':
                unset($sequence[$key]);
            case 'new':
		// fallthrough, no break
                $sequence[$route_id] = $route_id;
                break;
            case '0':
                unset($sequence[$key]);
                array_unshift($sequence, $route_id);
                break;
            default:
                if (!ctype_digit($seq)) {
                    return false;
                }
                if ($seq > count($sequence) - 1) {
                    unset($sequence[$key]);
                    $sequence[] = $route_id;
                    break;
                }
                if ($sequence[$seq] == $route_id) {
                    break;
                }
                $sequence[$key] = "bookmark";
                $remainder = array_slice($sequence, $seq);
                array_unshift($remainder, $route_id);
                $sequence = array_merge(array_slice($sequence, 0, $seq), $remainder);
                unset($sequence[array_search("bookmark", $sequence)]);
                break;
        }
        $seq = 0;
        $final_seq = false;
        sql('DELETE FROM `outbound_route_sequence` WHERE 1');
        $stmt = $this->Database->prepare('INSERT INTO `outbound_route_sequence` (`route_id`, `seq`) VALUES (?,?)');
        $sequence = is_array($sequence)?$sequence:[];
	foreach ($sequence as $rid) {
            $stmt->execute([$rid,$seq]);
            if ($rid === $route_id) {
                $final_seq = $seq;
            }
            $seq++;
        }
        return $final_seq;
    }
    
    public function listAll(){
        $sql = "SELECT a.*, b.seq FROM `outbound_routes` a JOIN `outbound_route_sequence` b ON a.route_id = b.route_id ORDER BY `seq`";
        $stmt = $this->Database->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get($id){
        $sql = 'SELECT a.*, b.seq FROM `outbound_routes` a JOIN `outbound_route_sequence` b ON a.route_id = b.route_id WHERE a.route_id=?';
        $sth = $this->Database->prepare($sql);
        $sth->execute(array($id));
        return $sth->fetch(PDO::FETCH_ASSOC);
    }

    public function updateTrunks($route_id, &$trunks, $delete = false){
        if ($delete) {
            $this->deleteTrunkRouteById($route_id);
        }
        $stmt = $this->Database->prepare('INSERT INTO `outbound_route_trunks` (`route_id`, `trunk_id`, `seq`) VALUES (?,?,?)');
        $seq = 0;
        foreach ($trunks as $trunk) {
            $stmt->execute([$route_id, $trunk, $seq]);
            ++$seq;
        }
        return $this;
    }

    public function allUnique(array $array, $column){
        $arraySize = count($array);
        $uniqueColumnSize = count(array_unique(array_column($array,$column)));
        return $arraySize === $uniqueColumnSize;
    }
    
    public function updatePatterns($route_id, &$patterns, $delete = false){
        $filter = '/[^0-9\*\#\+\-\.\[\]xXnNzZ]/';
        $insert_pattern = [];
        /** 
         * This was a todo in functions inc. Throwing an exception here may be to big of a functional change
         * For now we log this and later we can make it do magic. ¯\_(シ)_/¯
         **/
        if(!$this->allUnique($patterns,'prepend_digits')){
            dbug(sprintf(_("All the patterns for route id %s were NOT unique which can cause unexpected behavior This may be unallowed in the future."),$route_id));
        }
        foreach ($patterns as $pattern) {
            $match_pattern_prefix = preg_replace($filter, '', strtoupper(trim($pattern['match_pattern_prefix'])));
            $match_pattern_pass = preg_replace($filter, '', strtoupper(trim($pattern['match_pattern_pass'])));
            $match_cid = preg_replace($filter, '', strtoupper(trim($pattern['match_cid'])));
            $prepend_digits = preg_replace($filter, '', strtoupper(trim($pattern['prepend_digits'])));

            if ($match_pattern_prefix . $match_pattern_pass . $match_cid == '') {
                continue;
            }

            $hash_index = md5($match_pattern_prefix . $match_pattern_pass . $match_cid);
            if (!isset($insert_pattern[$hash_index])) {
                $insert_pattern[$hash_index] = array(':prefix' => $match_pattern_prefix, ':pass' => $match_pattern_pass, ':cid' => $match_cid,  ':digits' => $prepend_digits);
            }
        }

        if ($delete) {
            $this->deletePatternsById($route_id);
        }
        $stmt = $this->Database->prepare('REPLACE INTO `outbound_route_patterns` (`route_id`, `match_pattern_prefix`, `match_pattern_pass`, `match_cid`, `prepend_digits`) VALUES(:route_id, :prefix, :pass, :cid, :digits)');
        foreach ($insert_pattern as $pattern) {
            $pattern[':route_id'] = $route_id;
            $stmt->execute($pattern);
        }
        return $this;
    }
    
    public function deletePatternsById($id){
        $sql = 'DELETE FROM `outbound_route_patterns` WHERE `route_id`= :id';
        $stmt = $this->Database->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $this;
    }

    public function deleteTrunkRouteById($id){
        $sql = "DELETE FROM `outbound_route_trunks` WHERE `route_id`= :id";
        $stmt = $this->Database->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $this;
    }

    public function getRouteTrunksById($id){
        $sql = "SELECT `trunk_id` FROM `outbound_route_trunks` WHERE `route_id` = ? ORDER BY `seq`";
        $sth = $this->Database->prepare($sql);
        $sth->execute(array($id));
        return $sth->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getRoutePatternsByID($route_id){
        $sql = "SELECT * FROM `outbound_route_patterns` WHERE `route_id` = ? ORDER BY `match_pattern_prefix`, `match_pattern_pass`";
        $sth = $this->Database->prepare($sql);
        $sth->execute(array($route_id));
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllRoutePatterns(){
        $sql = "SELECT * FROM `outbound_route_patterns` ORDER BY `match_pattern_prefix`, `match_pattern_pass`";
        $sth = $this->Database->prepare($sql);
        $sth->execute();
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }
}

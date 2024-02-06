<?php
namespace FreePBX\modules\Core\Components;
use PDO;
use Exception;

#[\AllowDynamicProperties]
class Outboundrouting extends ComponentBase{
	public function add($name, $outcid, $outcid_mode, $password, $emergency_route, $intracompany_route, $mohclass, $time_group_id, $patterns, $trunks, $seq = 'new', $dest = '', $time_mode = '', $timezone = '', $calendar_id = '', $calendar_group_id = '', $notification_on = '', $emailfrom = '', $emailto = '', $emailsubject = '', $emailbody = ''){
		$sql = "INSERT INTO `outbound_routes` (`name`, `outcid`, `outcid_mode`, `password`, `emergency_route`, `intracompany_route`, `mohclass`, `time_group_id`, `dest`, `time_mode`, `timezone`, `notification_on`)
		VALUES (:name, :outcid, :outcid_mode, :password, :emergency_route,  :intracompany_route,  :mohclass, :time_group_id, :dest, :time_mode, :timezone, :notification_on)";

		$sth = $this->Database->prepare($sql);
		$sth->execute(array(
			":name" => $name,
			":outcid" => $outcid,
			":outcid_mode" => trim($outcid) == '' ? '' : $outcid_mode,
			":password" => $password,
			":emergency_route" => strtoupper($emergency_route),
			":intracompany_route" => strtoupper($intracompany_route),
			":mohclass" => $mohclass,
			":time_group_id" => $time_group_id !== '' ? $time_group_id : 0,
			":dest" => $dest,
			":time_mode" => $time_mode,
			":timezone" => $timezone,
			":notification_on" => $notification_on

		));

		$route_id = $this->Database->lastInsertId();

		$this->updatePatterns($route_id, $patterns);
		$this->updateTrunks($route_id, $trunks);
		$this->setOrder($route_id, $seq);
		$this->setOutboundRouteEmail($route_id, $emailfrom, $emailto, $emailsubject, $emailbody);

		return $route_id;
	}

	public function editById($route_id, $name, $outcid, $outcid_mode, $password, $emergency_route, $intracompany_route, $mohclass, $time_group_id, $patterns, $trunks, $seq = 'new', $dest = '', $time_mode = '', $timezone = '', $calendar_id = '', $calendar_group_id = '', $notification_on = '', $emailfrom = '', $emailto = '', $emailsubject = '', $emailbody = ''){
		$sql = "REPLACE INTO `outbound_routes` (`route_id`,`name`, `outcid`, `outcid_mode`, `password`, `emergency_route`, `intracompany_route`, `mohclass`, `time_group_id`, `dest`, `time_mode`, `timezone`, `notification_on`)
		VALUES (:route_id, :name, :outcid, :outcid_mode, :password, :emergency_route,  :intracompany_route,  :mohclass, :time_group_id, :dest, :time_mode, :timezone, :notification_on)";
		$sth = $this->Database->prepare($sql);
		$sth->execute(array(
			":route_id" => $route_id,
			":name" => $name,
			":outcid" => $outcid,
			":outcid_mode" => trim($outcid) == '' ? '' : $outcid_mode,
			":password" => $password,
			":emergency_route" => strtoupper($emergency_route),
			":intracompany_route" => strtoupper($intracompany_route),
			":mohclass" => $mohclass,
			":time_group_id" => (is_numeric($time_group_id) ? $time_group_id : 0),
			":dest" => $dest,
			":time_mode" => $time_mode,
			":timezone" => $timezone,
			":notification_on" => $notification_on

		));
		$this->updatePatterns($route_id, $patterns,true);
		$this->updateTrunks($route_id, $trunks,true);
		$this->setOrder($route_id, $seq);
		$this->setOutboundRouteEmail($route_id, $emailfrom, $emailto, $emailsubject, $emailbody, true);

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
		$sth = $this->Database->prepare($sql);
		$sth->execute(array($route_id));
		$sql = 'DELETE FROM outbound_route_sequence WHERE route_id = ?';
		$sth = $this->Database->prepare($sql);
		$sth->execute(array($route_id));
		$sql = 'DELETE FROM outbound_route_email WHERE route_id = ?';
		$sth = $this->Database->prepare($sql);
		$sth->execute(array($route_id));
		return $this;
	}

	public function setOrder($route_id, $order){
		$sql = "SELECT `seq`,`route_id` FROM `outbound_route_sequence` ORDER BY `seq`";
		$sequence = $this->Database->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR);
		if(!is_array($sequence)){
			$sequence = [];
		}
		if ($order === 'new') {
			array_unshift($sequence,$route_id);
		} elseif(is_numeric($order) && (empty($sequence[$order]) || $sequence[$order] !== $route_id)) {
			$key = array_search($route_id,$sequence);
			if($key !== false) {
				unset($sequence[$key]);
				$sequence = array_values($sequence);
			}
			array_splice($sequence, $order, 0, $route_id);
			$sequence = array_values($sequence); //jic
		} elseif(!is_numeric($order) && in_array($order,['top','bottom'])) {
			if($order === 'bottom') {
				$sequence[] = $route_id;
			} else {
				array_unshift($sequence, $route_id);
			}
			$sequence = array_values($sequence); //jic
		} elseif(!is_numeric($order)) {
			throw new \Exception("Dont know what to do with $order");
		} else {
			//order didnt change
			return;
		}

		$this->Database->query('DELETE FROM `outbound_route_sequence` WHERE 1');
		$stmt = $this->Database->prepare('INSERT INTO `outbound_route_sequence` (`seq`, `route_id`) VALUES (?,?)');
		$sequence = array_unique($sequence);
		$sequence = array_values($sequence);
		foreach ($sequence as $k => $v) {
			$stmt->execute([$k,$v]);
			if ($v == $route_id) {
				$final_seq = $k;
			}
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

	public function updateTrunks($route_id, $trunks, $delete = false){
		if ($delete) {
			$this->deleteTrunkRouteById($route_id);
		}
		$stmt = $this->Database->prepare('REPLACE INTO `outbound_route_trunks` (`route_id`, `trunk_id`, `seq`) VALUES (?,?,?)');
		$seq = 0;
		foreach ($trunks as $trunk) {
			$stmt->execute([$route_id, $trunk, $seq]);
			++$seq;
		}
		return [];
	}

	public function areAllPaternsUnique(array $array, $column){
		$arraySize = count($array);
		$uniqueColumnSize = count(array_unique(array_column($array,$column)));
		return $arraySize === $uniqueColumnSize;
	}

	public function updatePatterns($id, $patterns, $delete = false){
		$filter = '/[^0-9\*\#\+\-\.\[\]xXnNzZ]/';
		$insert_pattern = [];
		/**
		* This was a todo in functions inc. Throwing an exception here may be to big of a functional change
		* For now we log this and later we can make it do magic. ¯\_(シ)_/¯
		**/
		if(!$this->areAllPaternsUnique($patterns,'prepend_digits')){
			dbug(sprintf(_("All the patterns for route id %s were NOT unique which can cause unexpected behavior This may be unallowed in the future."),$id));
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
			$this->deletePatternsById($id);
		}
		$stmt = $this->Database->prepare('REPLACE INTO `outbound_route_patterns` (`route_id`, `match_pattern_prefix`, `match_pattern_pass`, `match_cid`, `prepend_digits`) VALUES(:route_id, :prefix, :pass, :cid, :digits)');
		foreach ($insert_pattern as $pattern) {
			$pattern[':route_id'] = $id;
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

	public function deleteOutboundRouteTrunksByTrunkId($id) {
		$sql = "DELETE FROM `outbound_route_trunks` WHERE `trunk_id`= :id";
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

	public function getRoutePatternsById($id){
		$sql = "SELECT * FROM `outbound_route_patterns` WHERE `route_id` = ? ORDER BY `match_pattern_prefix`, `match_pattern_pass`";
		$sth = $this->Database->prepare($sql);
		$sth->execute(array($id));
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

	public function getAllRoutePatterns(){
		$sql = "SELECT * FROM `outbound_route_patterns` ORDER BY `match_pattern_prefix`, `match_pattern_pass`";
		$sth = $this->Database->prepare($sql);
		$sth->execute();
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}

    public function getRouteEmailByID($route_id) {
        $sql = "SELECT * FROM `outbound_route_email` WHERE `route_id` = ?";
        $sth = $this->Database->prepare($sql);
        $sth->execute(array($route_id));
        return $sth->fetch(\PDO::FETCH_ASSOC);
    }

	public function setOutboundRouteEmail($route_id, $emailfrom, $emailto, $emailsubject, $emailbody, $delete = false) {
		if ($delete) {
			sql('DELETE FROM `outbound_route_email` WHERE `route_id`='.q($route_id));
		}

		$emailfrom = trim($emailfrom);
		$emailto = trim($emailto);

		$sql = "INSERT INTO `outbound_route_email`
		(`route_id`, `emailfrom`, `emailto`, `emailsubject`, `emailbody`)
		VALUES (?,?,?,?,?)";
		$sth = $this->Database->prepare($sql);
		$sth->execute(array(
			$route_id,
			$emailfrom,
			$emailto,
			$emailsubject,
			$emailbody
		));

		return true;
	}
}

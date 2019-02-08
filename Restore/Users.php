<?php

namespace FreePBX\modules\Core\Restore;

class Users extends Corebase{

	public function setConfigs($configs){
		$this->updateUsers($configs['users']);
		return $this;
	}
	public function setFiles(){
		return $this;
	}
	public function setDirs(){
		return $this;
	}
	public function processLegacy($pdo, $data, $tables, $tmpfiledir) {
		if (!in_array('users', $tables)) {
			return $this;
		}
		$users = $pdo->query("SELECT * FROM users")->fetchAll(\PDO::FETCH_ASSOC);
		$this->updateUsers($users);
		return $this;
	}

	private function updateUsers($users) {
		$sth = $this->FreePBX->Database->prepare("INSERT INTO users (`extension`, `password`, `name`, `voicemail`, `ringtimer`, `noanswer`, `recording`, `outboundcid`, `sipname`, `noanswer_cid`, `busy_cid`, `chanunavail_cid`, `noanswer_dest`, `busy_dest`, `chanunavail_dest`, `mohclass`) VALUES (:extension, :password, :name, :voicemail, :ringtimer, :noanswer, :recording, :outboundcid, :sipname, :noanswer_cid, :busy_cid, :chanunavail_cid, :noanswer_dest, :busy_dest, :chanunavail_dest, :mohclass)");
		foreach($users as $user) {
			$sth->execute($user);
		}
		$this->FreePBX->Core->users2astdb();
	}
}

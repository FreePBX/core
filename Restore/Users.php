<?php

namespace FreePBX\modules\Core\Restore;

class Users extends Corebase{

	public function setConfigs($configs){
		$this->updateUsers($configs['users']);
		$this->FreePBX->Core->users2astdb();
		if( isset($configs['astdbConfigs'])){
			$this->updateAstdbConfigs($configs['astdbConfigs']);
		}
		return $this;
	}

	private function updateUsers($users) {
		$sth = $this->FreePBX->Database->prepare("INSERT INTO users (`extension`, `password`, `name`, `voicemail`, `ringtimer`, `noanswer`, `recording`, `outboundcid`, `sipname`, `noanswer_cid`, `busy_cid`, `chanunavail_cid`, `noanswer_dest`, `busy_dest`, `chanunavail_dest`, `mohclass`) VALUES (:extension, :password, :name, :voicemail, :ringtimer, :noanswer, :recording, :outboundcid, :sipname, :noanswer_cid, :busy_cid, :chanunavail_cid, :noanswer_dest, :busy_dest, :chanunavail_dest, :mohclass)");
		foreach($users as $user) {
			$sth->execute($user);
		}
	}

	private function updateAstdbConfigs($confs) {
		$this->FreePBX->Core->putAstdbConfigs($confs);
	}
}

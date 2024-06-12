<?php

namespace FreePBX\modules\Core\Backup;

class Users extends Corebase{
	public function getConfigs(){
		$users = $this->FreePBX->Database->query("SELECT * FROM users")->fetchAll(\PDO::FETCH_ASSOC);
		$astdbConfigs = [];
		foreach ($users as $user) {
			$astdbConfigs[$user['extension']] = $this->FreePBX->Core->getAstdbConfigs($user['extension']);
		}
		return [
			"users" => $users,
			"astdbConfigs" => $astdbConfigs
		];
	}
	public function getFiles(){
	return [];
	}
	public function getDirs(){
	return [];
	}
}

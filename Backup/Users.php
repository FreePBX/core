<?php

namespace FreePBX\modules\Core\Backup;

class Users extends Corebase{
	public function getConfigs(){
		return [
			"users" => $this->FreePBX->Database->query("SELECT * FROM users")->fetchAll(\PDO::FETCH_ASSOC)
		];
	}
	public function getFiles(){
	return [];
	}
	public function getDirs(){
	return [];
	}
}

<?php

namespace FreePBX\modules\Core\Backup;

class Trunks extends Corebase{
	public function getConfigs(){
		return [
			"trunks" => $this->FreePBX->Database->query("SELECT * FROM trunks")->fetchAll(\PDO::FETCH_ASSOC),
			"techTables" => [
				"pjsip" => $this->FreePBX->Database->query("SELECT * FROM pjsip")->fetchAll(\PDO::FETCH_ASSOC),
			],
			"dialpatterns" => $this->FreePBX->Database->query("SELECT * FROM trunk_dialpatterns")->fetchAll(\PDO::FETCH_ASSOC)
		];
	}
	public function getFiles(){
	return [];
	}
	public function getDirs(){
	return [];
	}
}

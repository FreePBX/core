<?php

namespace FreePBX\modules\Core\Backup;

class Devices extends Corebase{
	public function getConfigs(){
		return [
			"devices" => $this->FreePBX->Database->query("SELECT * FROM devices")->fetchAll(\PDO::FETCH_ASSOC),
			"techTables" => [
				//pjsip uses sip table
				"sip" => $this->FreePBX->Database->query("SELECT s.* FROM sip s, devices d WHERE s.id = d.id")->fetchAll(\PDO::FETCH_ASSOC),
				"dahdi" => $this->FreePBX->Database->query("SELECT dh.* FROM dahdi dh, devices d WHERE dh.id = d.id")->fetchAll(\PDO::FETCH_ASSOC),
				"iax" => $this->FreePBX->Database->query("SELECT i.* FROM iax i, devices d WHERE i.id = d.id")->fetchAll(\PDO::FETCH_ASSOC)
			]
		];
	}
	public function getFiles(){
	return [];
	}
	public function getDirs(){
	return [];
	}
}

<?php

namespace FreePBX\modules\Core\Backup;

class Trunks extends Corebase{
	public function getConfigs(){
		return [
			"trunks" => $this->FreePBX->Database->query("SELECT * FROM trunks")->fetchAll(\PDO::FETCH_ASSOC),
			"techTables" => [
				"sip" => $this->FreePBX->Database->query("SELECT s.* FROM sip s, trunks t WHERE s.id in (CONCAT('tr-peer-', t.trunkid), CONCAT('tr-reg-', t.trunkid), CONCAT('tr-user-', t.trunkid))")->fetchAll(\PDO::FETCH_ASSOC),
				"pjsip" => $this->FreePBX->Database->query("SELECT * FROM pjsip")->fetchAll(\PDO::FETCH_ASSOC),
				"iax" => $this->FreePBX->Database->query("SELECT i.* FROM iax i, trunks t WHERE i.id in (CONCAT('tr-peer-', t.trunkid), CONCAT('tr-reg-', t.trunkid), CONCAT('tr-user-', t.trunkid))")->fetchAll(\PDO::FETCH_ASSOC)
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

<?php

namespace FreePBX\modules\Core\Restore;

class Trunks extends Corebase{
	public function setConfigs($configs){
		$this->updateTrunks($configs);
		return $this;
	}
	public function setFiles(){
		return $this;
	}
	public function setDirs(){
		return $this;
	}
	public function processLegacy($pdo, $data, $tables, $tmpfiledir) {
		if (!in_array('trunks', $tables)) {
			return $this;
		}

		$configs = [
			"trunks" => $pdo->query("SELECT * FROM trunks")->fetchAll(\PDO::FETCH_ASSOC),
			"techTables" => [
				"sip" => $pdo->query("SELECT s.* FROM sip s, trunks t WHERE s.id in (CONCAT('tr-peer-', t.trunkid), CONCAT('tr-reg-', t.trunkid), CONCAT('tr-user-', t.trunkid))")->fetchAll(\PDO::FETCH_ASSOC),
				"pjsip" => $pdo->query("SELECT * FROM pjsip")->fetchAll(\PDO::FETCH_ASSOC),
				"iax" => $pdo->query("SELECT i.* FROM iax i, trunks t WHERE i.id in (CONCAT('tr-peer-', t.trunkid), CONCAT('tr-reg-', t.trunkid), CONCAT('tr-user-', t.trunkid))")->fetchAll(\PDO::FETCH_ASSOC)
			]
		];

		$this->updateTrunks($configs);
		return $this;
	}

	private function updateTrunks($trunks) {
		$sth = $this->FreePBX->Database->prepare("INSERT INTO trunks (`trunkid`, `tech`, `channelid`, `name`, `outcid`, `keepcid`, `maxchans`, `failscript`, `dialoutprefix`, `usercontext`, `provider`, `disabled`, `continue`) VALUES (:trunkid, :tech, :channelid, :name, :outcid, :keepcid, :maxchans, :failscript, :dialoutprefix, :usercontext, :provider, :disabled, :continue)");
		foreach($trunks['trunks'] as $trunk) {
			$sth->execute($trunk);
		}
		foreach($trunks['techTables'] as $tech => $rows) {
			$sth = $this->FreePBX->Database->prepare("INSERT INTO $tech (`id`, `keyword`, `data`, `flags`) VALUES (:id, :keyword, :data, :flags)");
			foreach($rows as $row) {
				$sth->execute($row);
			}
		}
	}
}
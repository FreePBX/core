<?php

namespace FreePBX\modules\Core\Restore;

class Devices extends Corebase{
	public function setConfigs($configs){
		$this->updateDevices($configs);
	return $this;
	}
	public function setFiles(){
	return $this;
	}
	public function setDirs(){
	return $this;
	}
	public function processLegacy($pdo, $data, $tables, $tmpfiledir) {
		if (!in_array('devices', $tables)) {
			return $this;
		}

		$configs = [
			"devices" => $pdo->query("SELECT * FROM devices")->fetchAll(\PDO::FETCH_ASSOC),
			"techTables" => [
				"sip" => $pdo->query("SELECT s.* FROM sip s, devices d WHERE s.id = d.id")->fetchAll(\PDO::FETCH_ASSOC),
				"dahdi" => $pdo->query("SELECT dh.* FROM dahdi dh, devices d WHERE dh.id = d.id")->fetchAll(\PDO::FETCH_ASSOC),
				"iax" => $pdo->query("SELECT i.* FROM iax i, devices d WHERE i.id = d.id")->fetchAll(\PDO::FETCH_ASSOC)
			]
			];

		$this->updateDevices($configs);
		return $this;
	}

	private function updateDevices($devices) {
		$sth = $this->FreePBX->Database->prepare("INSERT INTO devices (`id`, `tech`, `dial`, `devicetype`, `user`, `description`, `emergency_cid`, `hint_override`) VALUES (:id, :tech, :dial, :devicetype, :user, :description, :emergency_cid, :hint_override)");
		foreach($devices['devices'] as $device) {
			$sth->execute($device);
		}
		foreach($devices['techTables'] as $tech => $rows) {
			$sth = $this->FreePBX->Database->prepare("INSERT INTO $tech (`id`, `keyword`, `data`, `flags`) VALUES (:id, :keyword, :data, :flags)");
			foreach($rows as $row) {
				$sth->execute($row);
			}
		}
		$this->FreePBX->Core->devices2astdb();
	}
}

<?php

namespace FreePBX\modules\Core\Restore;
use Symfony\Component\Process\Process;
class Devices extends Corebase{
	public function setConfigs($configs){
		if(count($configs) > 0){
			$this->updateDevices($configs);
		}
		return $this;
	}

	public function setspecialFiles($tmppath,$files){
		//working with dump sql file
		if(empty($files[0])) {
			return false;
		}
		$dump = $files[0];
		$dumpfile = $tmppath . '/files/' . ltrim($dump->getPathTo(), '/') . '/' . $dump->getFilename();
		if (!file_exists($dumpfile)) {
			return;
		}
		global $amp_conf;
		$dbuser = $amp_conf['AMPDBUSER'];
		$dbpass = $amp_conf['AMPDBPASS'];
		$dbname = $amp_conf['AMPDBNAME'];
		$mysql = fpbx_which('mysql');
		$restore = "{$mysql} -u{$dbuser} -p{$dbpass} {$dbname} < {$dumpfile}";
		$sql = new Process($restore);
		$sql->mustRun();
		$this->FreePBX->Core->devices2astdb();
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

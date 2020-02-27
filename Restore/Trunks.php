<?php

namespace FreePBX\modules\Core\Restore;

class Trunks extends Corebase{
	protected $backupinfo = [];

	public function setbackupinfo($backupinfo){
		$this->backupinfo = $backupinfo;
		return $this;
	}
	public function setConfigs($configs){
		$this->updateTrunks($configs);
		$backupinfo = $this->backupinfo;
		if (($backupinfo['warmspareenabled'] == 'yes' && $backupinfo['warmspare_remotetrunks'] == 'yes') || (isset($backupinfo['core_disabletrunks']) && $backupinfo['core_disabletrunks'] == 'yes')) {
			core_trunks_disable('reg', true);
		}
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

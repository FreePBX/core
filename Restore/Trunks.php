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
		$items = json_decode($backupinfo["backup_items"]);
		if(isset($backupinfo['core_disabletrunks'])){
			$disable_trunk = $backupinfo['core_disabletrunks']=='yes'?'yes':'no';
		}else {
			$disable_trunk = "no";
			if(isset($items) && is_array($items)) {
				foreach($items as $index => $data){
					foreach($data as $item => $val){
						if($item == "settings"){
							foreach((array) $val as $result){
								if($result->name == "core_disabletrunks" && ($result->value == "yes" || $result->value == "no")){
									$disable_trunk = $result->value;
								}
							}
						}
					}
				}
			}
		}			
		if ((isset($backupinfo['warmspareenabled']) && isset($backupinfo['warmspare_remotetrunks']) && $backupinfo['warmspareenabled'] == 'yes' && $backupinfo['warmspare_remotetrunks'] == 'yes')) {
			core_trunks_disable('reg', true);
		}
		if((!empty($disable_trunk) && $disable_trunk == 'yes')) {
			core_trunks_disable('*', true);
		}
		return $this;
	}

	private function updateTrunks($trunks) {
		$sth = $this->FreePBX->Database->prepare("INSERT INTO trunks (`trunkid`, `tech`, `channelid`, `name`, `outcid`, `keepcid`, `maxchans`, `failscript`, `dialoutprefix`, `usercontext`, `provider`, `disabled`, `continue`,`routedisplay`) VALUES (:trunkid, :tech, :channelid, :name, :outcid, :keepcid, :maxchans, :failscript, :dialoutprefix, :usercontext, :provider, :disabled, :continue,:routedisplay)");
		foreach($trunks['trunks'] as $trunk) {
			if (!array_key_exists('routedisplay', $trunk)) {
				$trunk['routedisplay'] = 'on';
			}
			$sth->execute($trunk);
		}
		foreach($trunks['techTables'] as $tech => $rows) {
			$sth = $this->FreePBX->Database->prepare("INSERT INTO $tech (`id`, `keyword`, `data`, `flags`) VALUES (:id, :keyword, :data, :flags)");
			foreach($rows as $row) {
				$sth->execute($row);
			}
		}
		$sth = $this->FreePBX->Database->prepare("INSERT INTO trunk_dialpatterns (`trunkid`, `match_pattern_prefix`, `match_pattern_pass`, `prepend_digits`, `seq`) VALUES (:trunkid, :match_pattern_prefix, :match_pattern_pass, :prepend_digits, :seq)");
		foreach($trunks['dialpatterns'] as $pattern) {
			$sth->execute($pattern);
		}
	}
}

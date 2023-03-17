<?php

namespace FreePBX\modules\Core\Backup;
class Devices extends Corebase{
	public function getConfigs(){
		return [];
	}
	public function getFiles(){
	return [];
	}
	public function getSpecialTables()
	{
		$tables = array('devices', 'sip', 'dahdi', 'iax');
		return implode(' ', $tables);
	}
	public function getDirs(){
	return [];
	}
}

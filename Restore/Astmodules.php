<?php

namespace FreePBX\modules\Core\Restore;

class Astmodules extends Corebase{
	public function setConfigs($configs){
		if(empty($configs)){
			return $this;
		}
		file_put_contents($this->FreePBX->Config->get('ASTETCDIR').'/modules.conf','');
		$this->FreePBX->ConfigFile('modules.conf')->addEntry('modules', array('autoload = '.$configs['modules']['autoload']));
		unset($configs['modules']['autoload']);
		foreach($configs['modules'] as $key => $section){
			foreach ($section as $value) {
				$this->FreePBX->ConfigFile('modules.conf')->addEntry('modules', array( $key .' = ' . $value));
			}
		}
		return $this;
	}
}
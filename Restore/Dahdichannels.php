<?php

namespace FreePBX\modules\Core\Restore;

class Dahdichannels extends Corebase{
	public function setConfigs($configs){
		$dahdichannels = new \FreePBX\modules\Core\Components\Dahdichannels($this->FreePBX->Database);
		foreach ($configs as $config) {
			$dahdichannels->delete($config['channel']);
			$dahdichannels->add($config['description'], $config['channel'], $config['did']);
		}
		return $this;
	}
}
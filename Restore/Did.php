<?php

namespace FreePBX\modules\Core\Restore;

class Did extends Corebase{
	public function setConfigs($configs){
		foreach ($configs as $did) {
			$this->FreePBX->Core->delDID($did['extension'], $did['cidnum']);
			$this->FreePBX->Core->addDID($did);
		}
		return $this;
	}
}
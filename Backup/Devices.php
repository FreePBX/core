<?php

namespace FreePBX\modules\Core\Backup;

class Devices extends Corebase{
    public function getConfigs(){
        return $this->FreePBX->Core->getAllDevicesByType();
    }
    public function getFiles(){
	return [];
    }
    public function getDirs(){
	return [];
    }
}

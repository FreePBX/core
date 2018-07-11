<?php

namespace FreePBX\modules\Core\Backup;

class Users extends Corebase{
    public function getConfigs(){
        return $this->FreePBX->Core->getAllUsersByDeviceType();
    }
    public function getFiles(){
	return [];
    }
    public function getDirs(){
	return [];
    }
}

<?php

namespace FreePBX\modules\Core\Backup;

class Did extends Corebase{
    public function getConfigs(){
        return $this->FreePBX->Core->getAllDIDs();
    }
    public function getFiles(){
	return[];
    }
    public function getDirs(){
	return [];
    }
}

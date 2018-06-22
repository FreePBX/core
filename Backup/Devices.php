<?php

namespace FreePBX\modules\Core\Backup;

class Devices extends Corebase{
    public function setConfigs(){
        return $this->FreePBX->Core->getAllDevicesByType();
    }
    public function setFiles(){
    }
    public function setDirs(){
    }
}
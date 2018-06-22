<?php

namespace FreePBX\modules\Core\Backup;

class Users extends Corebase{
    public function setConfigs(){
        return $this->FreePBX->Core->getAllUsersByDeviceType();
    }
    public function setFiles(){
    }
    public function setDirs(){
    }
}
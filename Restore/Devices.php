<?php

namespace FreePBX\modules\Core\Restore;

class Devices extends Corebase{
    public function setConfigs($configs){
        foreach ($configs as $device) {
            $this->FreePBX->Core->addDevice($device['id'], $device['tech'], $device, true);
        }
    }
    public function setFiles(){
    }
    public function setDirs(){
    }
}
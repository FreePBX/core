<?php

namespace FreePBX\modules\Core\Restore;

class Devices extends Corebase{
    public function setConfigs($configs){
        foreach ($configs as $device) {
            $settings = [];
            foreach ($device as $key => $value) {
                $settings[$key]['value'] = $value;
                $settings[$key]['flag'] = 0;

            }
            $this->FreePBX->Core->addDevice($device['id'], $device['tech'], $settings, true);
        }
	return $this;
    }
    public function setFiles(){
	return $this;
    }
    public function setDirs(){
	return $this;
    }
}

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
            $this->FreePBX->Core->delDevice($device['id'], true);
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
    public function processLegacy($pdo, $data, $tables, $tmpfiledir)
    {
        if (!in_array('devices', $tables)) {
            return $this;
        }
        $core = $this->FreePBX->Core;
        $core->setDatabase($pdo);
        $configs = $core->getAllDevicesByType();
        $core->resetDatabase();
        foreach ($configs as $device) {
            $settings = [];
            foreach ($device as $key => $value) {
                $settings[$key]['value'] = $value;
                $settings[$key]['flag'] = 0;

            }
            $core->delDevice($device['id'], true);
            $core->addDevice($device['id'], $device['tech'], $settings, true);
        }
        return $this;
    }
}

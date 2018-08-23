<?php

namespace FreePBX\modules\Core\Restore;

class Users extends Corebase{
  
    public function setConfigs($configs){
        foreach ($configs as $settings) {
            $this->FreePBX->Core->delUser($settings['extension'], true);
            $this->FreePBX->Core->addUser($settings['extension'], $settings, true);
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
        if (!in_array('users', $tables)) {
            return $this;
        }
        $core = $this->FreePBX->Core;
        $core->setDatabase($pdo);
        $configs = $core->getAllUsersByDeviceType();
        $core->resetDatabase();
        foreach ($configs as $settings) {
            $core->delUser($settings['extension'], true);
            $core->addUser($settings['extension'], $settings, true);
        }
        return $this;
    }
}

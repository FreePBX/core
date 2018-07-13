<?php

namespace FreePBX\modules\Core\Restore;

class Users extends Corebase{
  
    public function setConfigs($configs){
        foreach ($configs as $settings) {
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
}

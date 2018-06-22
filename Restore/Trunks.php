<?php

namespace FreePBX\modules\Core\Restore;

class Trunks extends Corebase{
    public function setConfigs($configs){
        foreach ($configs as $trunk) {
            $this->FreePBX->Core->addTrunk($trunk['name'], $trunk['tech'], $trunk, true);
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
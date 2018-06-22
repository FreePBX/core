<?php

namespace FreePBX\modules\Core\Backup;

class Trunks extends Corebase{
    public function getConfigs(){
        return $this->FreePBX->Core->listTrunks();
    }
    public function getFiles(){
    }
    public function getDirs(){
    }
}
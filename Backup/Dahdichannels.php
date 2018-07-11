<?php

namespace FreePBX\modules\Core\Backup;

class Dahdichannels extends Corebase{
    public function getConfigs(){
        $dahdichannels = new \FreePBX\modules\Core\Components\Dahdichannels($this->FreePBX->Database);
        return $dahdichannels->listChannels();
    }
    public function getFiles(){
        return [];
    }
    public function getDirs(){
        return [];
    }
}

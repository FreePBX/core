<?php

namespace FreePBX\modules\Core\Backup;

class Astmodules extends Corebase{
    public function getConfigs(){
        return $this->FreePBX->ConfigFile('modules.conf')->config->ProcessedConfig;
    }
    public function getFiles(){
	return [];
    }
    public function getDirs(){
	return [];
    }
}

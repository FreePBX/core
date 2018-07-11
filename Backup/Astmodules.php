<?php

namespace FreePBX\modules\Core\Backup;

class Astmodules extends Corebase{
    public function getConfigs(){
        $this->FreePBX->ConfigFile('modules.conf')->config->ProcessedConfig;
    }
    public function getFiles(){
	return [];
    }
    public function getDirs(){
	return [];
    }
}

<?php

namespace FreePBX\modules\Core\Backup;
/** 
 * TODO: This may not be needed with userman
 */
class Ampusers extends Corebase{
    public function getConfigs(){
        return $this->FreePBX->Core->listAMPUsers('assoc',true);
    }
    public function getFiles(){
	    return [];
    }
    public function getDirs(){
	    return [];
    }

}

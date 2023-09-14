<?php
namespace FreePBX\modules\Core\Backup;

#[\AllowDynamicProperties]
class Corebase {
    public function __construct($freepbx, $id, $transaction){
        $this->className = substr(strrchr(get_class($this), '\\'), 1);
        $this->FreePBX = $freepbx;
        $this->backupID = $id;
        $this->transaction = $transaction;
    }
    public function getDeps(){
	return [];
   }
}

<?php
namespace FreePBX\modules\Core\Backup;

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
   public function getspecialFiles(){
    return [];
    }
}

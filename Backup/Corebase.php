<?php
namespace FreePBX\modules\Core\Backup;

class Corebase {
    public function __construct($freepbx, $id, $transaction){
        $this->className = substr(strrchr(get_class(__class__), '\\'), 1);
        $this->FreePBX = $freepbx;
        $this->backupID = $id;
        $this->transaction = $transaction;
    }
}
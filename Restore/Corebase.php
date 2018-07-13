<?php
namespace FreePBX\modules\Core\Restore;

class Corebase {
    public function __construct($freepbx,$transaction){
        $this->className = substr(strrchr(get_class($this), '\\'), 1);
        $this->transaction = $transaction;
        $this->FreePBX = $freepbx;
    }
}

<?php

namespace FreePBX\modules\Core\Restore;

class Did extends Corebase{
    public function setConfigs($configs){
        foreach ($configs as $did) {
            $this->FreePBX->Core->delDID($did['extension'], $did['cidnum']);
            $this->FreePBX->Core->addDID($did);
        }
        return $this;
    }
    public function setFiles(){
        return $this;
    }
    public function setDirs(){
        return $this;
    }
    public function processLegacy($pdo, $data, $tables, $tmpfiledir){
        if(!in_array('incoming', $tables)){
            return $this;
        }
        $core = $this->FreePBX->Core;
        $core->setDatabase($pdo);
        $configs = $core->getAllDIDs();
        $core->resetDatabase();
        foreach ($configs as $did) {
            $core->delDID($did['extension'], $did['cidnum']);
            $core->addDID($did);
        }
        return $this;
    }
}
<?php

namespace FreePBX\modules\Core\Restore;
/** 
 * TODO: This may not be needed with userman
 */

class Ampusers extends Corebase{
    public function setConfigs($configs){
        foreach ($configs as $user) {
            $this->FreePBX->Core->addAMPUser($user['username'], $user['password'], $user['extension_low'], $user['extension_high'], $user['deptname'], explode(';',$user['sections']));
        }
        return $this;
    }
    public function setFiles(){
        return $this;
    }
    public function setDirs(){
        return $this;
    }
    
    public function processLegacy ($pdo, $data, $tables, $tmpfiledir){
        $core = $this->FreePBX->Core;
        $core->setDatabase($pdo);
        $configs = $core->listAMPUsers('assoc', true);
        $core->resetDatabase();
        foreach ($configs as $user) {
            $core->addAMPUser($user['username'], $user['password'], $user['extension_low'], $user['extension_high'], $user['deptname'], explode(';', $user['sections']));
        }
        return $this;
    }
}
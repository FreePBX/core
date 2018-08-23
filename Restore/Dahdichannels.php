<?php

namespace FreePBX\modules\Core\Restore;

class Dahdichannels extends Corebase{
    public function setConfigs($configs){
        $dahdichannels = new \FreePBX\modules\Core\Components\Dahdichannels($this->FreePBX->Database);
        foreach ($configs as $config) {
            $dahdichannels->delete($config['channel']);
            $dahdichannels->add($config['description'], $config['channel'], $config['did']);
        }
        return $this;
    }
    public function setFiles($files){
        return $this;
    }
    public function setDirs($dirs){
        return $this;
    }
    public function processLegacy($pdo, $data, $tables, $tmpfiledir)
    {
        if (!in_array('ampusers', $tables)) {
            return $this;
        }
        $dahdichannels = new \FreePBX\modules\Core\Components\Dahdichannels($pdo);
        $configs = $dahdichannels->listChannels();
        unset($dahdichannels);
        $dahdichannels = new \FreePBX\modules\Core\Components\Dahdichannels($this->FreePBX->Database);
        foreach ($configs as $config) {
            $dahdichannels->delete($config['channel']);
            $dahdichannels->add($config['description'], $config['channel'], $config['did']);
        }
        return $this;
    }
}
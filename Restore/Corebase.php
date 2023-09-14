<?php
namespace FreePBX\modules\Core\Restore;

#[\AllowDynamicProperties]
class Corebase {
	public function __construct($freepbx,$transaction){
		$this->className = substr(strrchr(get_class($this), '\\'), 1);
		$this->transaction = $transaction;
		$this->FreePBX = $freepbx;
	}

	public function setConfigs($configs){
		return $this;
	}
	public function setFiles(){
		return $this;
	}
	public function setDirs(){
		return $this;
	}
	public function setbackupinfo($backupinfo){
		return $this;
	}
}

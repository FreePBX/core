<?php
namespace FreePBX\modules\Core;
use FreePBX\modules\Backup as Base;
use SplObjectStorage;
use DirectoryIterator;

class Restore Extends Base\RestoreBase{
  public function runRestore($jobid){
    $configs = $this->getConfigs();
    $configs = reset($configs);
    $files = $this->getFiles();
    $dirs = $this->getDirs();
    foreach ($this->getClasses($jobid) as $class) {
	if(empty($class)){
		continue;
	}
        $class->setDirs($dirs)
            ->setFiles($files)
            ->setConfigs($configs[$class->className]);
    }
  }
    public function getClasses($transaction){
        $classList = new DirectoryIterator(__DIR__ . '/Restore');
        $classes = new SplObjectStorage();
        foreach ($classList as $classItem) {
            if ($classItem->isDot() || $classItem->getExtension() !== 'php') {
                continue;
            }
            $classname = $classItem->getBasename('.php');
            if ($classname === 'Corebase') {
                continue;
            }
	    $classname = '\\FreePBX\\modules\\Core\\Restore\\'.$classname;
            $classes->attach(new $classname($this->FreePBX, $transaction));
        }
        return $classes;
    }
}

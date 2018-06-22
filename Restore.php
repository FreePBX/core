<?php
namespace FreePBX\modules\__MODULENAME__;
use FreePBX\modules\Backup as Base;
class Restore Extends Base\RestoreBase{
  public function runRestore($jobid){
    $configs = $this->getConfigs();
    $files = $this->getFiles();
    $dirs = $this->getDirs();
    foreach ($this->getClasses($jobid) as $module) {
        $class->setDirs($dirs)
            ->setFiles($files)
            ->setConfigs($configs[$class->className]);
    }
  }
    public function getClasses($transaction){
        $classList = new DirectoryIterator(__DIR__ . '/Restore');
        $classes = new SplObjectStorage();
        foreach ($classlist as $classItem) {
            if ($classItem . isDot() || $classItem . getExtension() !== 'php') {
                continue;
            }
            $classname = $classItem->getBasename('.php');
            if ($classname === 'Corebase') {
                continue;
            }
            $classes->attach(new $classname($this->FreePBX, $transaction));
        }
        return $classes;
    }
}
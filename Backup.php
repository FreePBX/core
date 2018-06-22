<?php
namespace FreePBX\modules\__MODULENAME__;
use FreePBX\modules\Backup as Base;
use DirectoryIterator;
use SplObjectStorage;

class Backup Extends Base\BackupBase{
  public function runBackup($id,$transaction){
    $files = [];
    $dirs = [];
    $configs = [];
    foreach ($this->getClasses($id,$transaction) as $module) {
        $dirs[$module->className] = $class->getDirs();
        $configs[$module->className] = $class->getConfigs();
        foreach ($class->getFiles() as $file ){
            $this->addFile($file['basename'], $file['path'], $file['basevar'], $module->className);
        }

        foreach($module->getDeps() as $dependency){
            $this->addDependency($dependency);
        }
    }
    $this->addDirectories($dirs);
    $this->addConfigs($configs);
  }
  
  public function getClasses($id,$transaction){
        $classList = new DirectoryIterator(__DIR__ . '/Backup');
        $classes = new SplObjectStorage();
        foreach ($classlist as $classItem) {
            if($classItem.isDot() || $classItem.getExtension() !== 'php'){
                continue;
            }
            $classname = $classItem->getBasename('.php');
            if($classname === 'Corebase'){
                continue;
            }
            $classes->attach(new $classname($this->FreePBX, $id, $transaction));
        }
        return $classes;
  }
}

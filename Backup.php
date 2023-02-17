<?php
namespace FreePBX\modules\Core;
use DirectoryIterator;
use SplObjectStorage;
use FilesystemIterator;
use FreePBX\modules\Backup\BackupBase;

class Backup Extends BackupBase{
	public function runBackup($id,$transaction){
		$files = [];
		$dirs = [];
		$configs = [];
		foreach ($this->getClasses($id,$transaction) as $module) {
			$dirs[$module->className] = $module->getDirs();
			$configs[$module->className] = $module->getConfigs();
			foreach ($module->getFiles() as $file ){
				$this->addFile($file['basename'], $file['path'], $file['basevar'], $module->className);
			}

			foreach($module->getDeps() as $dependency){
				$this->addDependency($dependency);
			}
		}

		$configs['features'] = $this->dumpFeatureCodes();
		$configs['settings'] = $this->dumpAdvancedSettings();
		$configs['kvstore'] = $this->dumpKVStore();
		$configs['backup'] = $this->FreePBX->Core->getAll('backup_'.$id);
		$this->addDirectories($dirs);
		$this->addConfigs($configs);
	}

	public function getClasses($id,$transaction){
		$classList = new DirectoryIterator(__DIR__ . '/Backup');
		$classes = new SplObjectStorage();
		foreach ($classList as $classItem) {
			if($classItem->isDir()){
				continue;
			}
			if($classItem->getExtension() !== 'php'){
				continue;
			}
			if($classItem->getBasename() === 'Corebase.php'){
				continue;
			}
			$classname = 'FreePBX\\modules\\Core\\Backup\\'.$classItem->getBasename('.php');
			$classes->attach(new $classname($this->FreePBX, $id, $transaction));
		}
		return $classes;
	}
}

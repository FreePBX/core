<?php
namespace FreePBX\modules\Core;
use FreePBX\modules\Backup as Base;
use SplObjectStorage;
use DirectoryIterator;

class Restore Extends Base\RestoreBase{
	public function runRestore(){
		$configs = $this->getConfigs();
		$files = $this->getFiles();
		$dirs = $this->getDirs();
		foreach ($this->getClasses($this->transactionId) as $class) {
			if(empty($class)){
				continue;
			}
			$this->log(sprintf(_("Processing %s"),$class->className));
			$class->setDirs($dirs)
					->setFiles($files)
					->setConfigs($configs[$class->className]);
		}
		$this->importKVStore($configs['kvstore']);
		$this->importFeatureCodes($configs['features']);
		$this->importAdvancedSettings($configs['settings']);
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

	public function processLegacy($pdo, $data, $tables, $unknownTables) {
		$this->restoreLegacyAll($pdo);
		$this->FreePBX->Core->users2astdb();
		$this->FreePBX->Core->devices2astdb();
	}
}

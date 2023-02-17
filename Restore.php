<?php
namespace FreePBX\modules\Core;
use FreePBX\modules\Backup as Base;
use SplObjectStorage;
use DirectoryIterator;

class Restore Extends Base\RestoreBase{
	public function runRestore(){
		global $astman;
		$astman->database_deltree("AMPUSER");
		$astman->database_deltree("AMPDEV");
		$astman->database_deltree("CW");
		$skipoptions = $this->getCliarguments();
		$configs = $this->getConfigs();
		$files = $this->getFiles();
		$backupinfo = $this->getBackupInfo();
		$dirs = $this->getDirs();
		$excludetrunks = false;
		if((isset($skipoptions['skiptrunksandroutes']) && $skipoptions['skiptrunksandroutes']) || ($backupinfo['warmspareenabled'] == 'yes' && $backupinfo['warmspare_excludetrunks'] == 'yes')) {
			$excludetrunks = true;
			$trunkConfig = $this->getTrunksconfig();
			$outbound_routes = $this->getRouteConfigs();
			$modulefunction = \module_functions::create();
			$uninstall = $modulefunction->uninstall('core', 'true');
			if(is_array($uninstall)) {
				throw new \Exception(sprintf(_('Error uninstalling core reason(s): %s'),implode(",",$uninstall)));
			}
			$install = $modulefunction->install('core', 'true');
			if(is_array($install)) {
				throw new \Exception(sprintf(_('Error installing core reason(s): %s'),implode(",",$install)));
			}
		}
		foreach ($this->getClasses($this->transactionId) as $class) {
			if(empty($class)){
				continue;
			}
			$this->log(sprintf(_("Processing %s"),$class->className));
			if( $class->className == 'Trunks' && $excludetrunks) {
				$class->setDirs($dirs)
                                        ->setbackupinfo($backupinfo)
                                        ->setFiles($files)
                                        ->setConfigs($trunkConfig);
			}elseif( $class->className == 'Routing' && $excludetrunks){
				$class->setDirs($dirs)
                                        ->setbackupinfo($backupinfo)
                                        ->setFiles($files)
                                        ->setConfigs($outbound_routes);
			}
			else {
			$class->setDirs($dirs)
					->setbackupinfo($backupinfo)
					->setFiles($files)
					->setConfigs($configs[$class->className]);
			}
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
		global $astman;
		$this->log("Processing legacy backup");
		$skipoptions = $this->getCliarguments();
		$excludetrunks = false;
		if((isset($skipoptions['skiptrunksandroutes']) && $skipoptions['skiptrunksandroutes']) ) {
			$excludetrunks = true;
			$this->log("skiptrunksandroutes option passed ");
			$trunkConfig = $this->getTrunksconfig();
			$outbound_routes = $this->getRouteConfigs();
			$this->log("preserve the trunk and outbound_routes");
			$modulefunction = \module_functions::create();
			$uninstall = $modulefunction->uninstall('core', 'true');
			if(is_array($uninstall)) {
				throw new \Exception(sprintf(_('Error uninstalling core reason(s): %s'),implode(",",$uninstall)));
			}
			$install = $modulefunction->install('core', 'true');
			if(is_array($install)) {
				throw new \Exception(sprintf(_('Error installing core reason(s): %s'),implode(",",$install)));
			}
		}
		if($excludetrunks){
			$this->log("skiping Trunks and outboundroutes  From backup");
			$ignoretables = ['pjsip','iax','trunks','outbound_route_patterns','outbound_route_sequence','outbound_route_trunks','outbound_routes','outbound_route_email','trunk_dialpatterns'];
			$this->restoreLegacyDatabase($pdo,[],$ignoretables);
			// remove the sip trunks from sip
			$this->FreePBX->Database->query("Delete from sip where `id` like 'tr-peer-%'");
		}else {
			$this->restoreLegacyDatabase($pdo);
		}
		$this->restoreLegacyKvstore($pdo);
		$this->restoreLegacyFeatureCodes($pdo);
		$this->restoreLegacySettings($pdo);
		$this->FreePBX->Core->devices2astdb();
		if(isset($data['astdb']['AMPUSER'])) {
			$ampuser = array();
            foreach($data['astdb']['AMPUSER'] as $key => $value) {
                $ampuser[] = ['AMPUSER' => [ $key => $value ]];
            }
        }
        $astman->database_deltree("AMPUSER");
        $this->importAstDB($ampuser);
		if($excludetrunks){
			foreach ($this->getClasses($this->transactionId) as $class) {
				if(empty($class)){
					continue;
				}
				$this->log(sprintf(_("Processing %s"),$class->className));
				if( $class->className == 'Trunks' && $excludetrunks) {
					$class->setConfigs($trunkConfig);
				}elseif( $class->className == 'Routing' && $excludetrunks){
					$class->setConfigs($outbound_routes);
				}
			}
		}

	}

	private function getTrunksconfig(){
                return [
                        "trunks" => $this->FreePBX->Database->query("SELECT * FROM trunks")->fetchAll(\PDO::FETCH_ASSOC),
                        "techTables" => [
                                "sip" => $this->FreePBX->Database->query("SELECT s.* FROM sip s, trunks t WHERE s.id in (CONCAT('tr-peer-', t.trunkid), CONCAT('tr-reg-', t.trunkid), CONCAT('tr-user-', t.trunkid))")->fetchAll(\PDO::FETCH_ASSOC),
                                "pjsip" => $this->FreePBX->Database->query("SELECT * FROM pjsip")->fetchAll(\PDO::FETCH_ASSOC),
                                "iax" => $this->FreePBX->Database->query("SELECT i.* FROM iax i, trunks t WHERE i.id in (CONCAT('tr-peer-', t.trunkid), CONCAT('tr-reg-', t.trunkid), CONCAT('tr-user-', t.trunkid))")->fetchAll(\PDO::FETCH_ASSOC)
                        ],
			"dialpatterns" => $this->FreePBX->Database->query("SELECT * FROM trunk_dialpatterns")->fetchAll(\PDO::FETCH_ASSOC)
                ];
        }
	public function getRouteConfigs(){
        $final = [];
        $routing = new \FreePBX\modules\Core\Components\Outboundrouting($this->FreePBX->Database);
        $routes = $routing->listAll();
        foreach($routes as $route){
            $route['patterns'] = $routing->getRoutePatternsByID($route['route_id']);
            $route['trunks'] = $routing->getRouteTrunksById($route['route_id']);
            $final[] = $route;
        }
        return $final;
    }

	public function getResetInfo() {
		$backupinfo = $this->getBackupInfo();
		$skipoptions = $this->getCliarguments();
		if ((isset($skipoptions['skiptrunksandroutes']) && $skipoptions['skiptrunksandroutes']) || ($backupinfo['warmspareenabled'] == 'yes' && $backupinfo['warmspare_excludetrunks'] == 'yes')) {
			return true;
		}
		return false;
	}
}

<?php
namespace FreePBX\modules\Core;
abstract class Driver {
	protected $freepbx;
	public function __construct($freepbx, $drivers) {
		$this->freepbx = $freepbx;
	}

	public function getInfo() {
		return array(
			"rawName" => null,
			"prettyName" > null,
			"asteriskSupport" => null
		);
	}

	public function getDisplay($display, $deviceInfo, $currentcomponent) {
		return "";
	}

	public function genConfig() {
		return array();
	}

	public function writeConfig($conf) {
		return $conf;
	}
}

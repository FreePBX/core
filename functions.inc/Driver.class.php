<?php
namespace FreePBX\modules\Core;
abstract class Driver {
	protected $freepbx;
	protected $database;

	public function __construct($freepbx, $drivers) {
		$this->freepbx = $freepbx;
		$this->database = $freepbx->Database;
	}

	public function getInfo() {
		return array(
			"rawName" => null,
			"prettyName" > null,
			"asteriskSupport" => null
		);
	}

	public function addDevice($id, $settings) {
		return false;
	}

	public function delDevice($id) {
		return true;
	}

	public function getDevice($id) {
		return array();
	}

	public function getDefaultDeviceSettings($id, $displayname, &$flag) {
		return false;
	}

	public function getDeviceDisplay($display, $deviceInfo, $currentcomponent, $primarySection) {
		return "";
	}

	public function genConfig() {
		return array();
	}

	public function writeConfig($conf) {
		return $conf;
	}
}

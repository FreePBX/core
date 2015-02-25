<?php
/**
 * Base Core Driver class. Should be referenced by any extension driver
 */
namespace FreePBX\modules\Core;
abstract class Driver {
	protected $freepbx;
	protected $database;

	public function __construct($freepbx) {
		$this->freepbx = $freepbx;
		$this->database = $freepbx->Database;
	}

	/**
	 * Get Information about Driver
	 */
	public function getInfo() {
		return array(
			"rawName" => null,
			"prettyName" > null,
			"asteriskSupport" => null
		);
	}

	/**
	 * Add a Device
	 * @param int $id       The extension/device number
	 * @param array $settings [description]
	 */
	public function addDevice($id, $settings) {
		return false;
	}

	/**
	 * Delete a device
	 * @param int $id The extension/device number
	 */
	public function delDevice($id) {
		return true;
	}

	/**
	 * Get a device
	 * @param int $id The extension/device number
	 */
	public function getDevice($id) {
		return array();
	}

	/**
	 * Get default device settings
	 * @param int $id          The extension/device number
	 * @param string $displayname The display name
	 * @param int $flag        Auto incrementing field id
	 */
	public function getDefaultDeviceSettings($id, $displayname, &$flag) {
		return false;
	}

	/**
	 * Get Device Display
	 *
	 * Get all the options for display
	 *
	 * @param string $display          The display
	 * @param array $deviceInfo       The Device Info
	 * @param object $currentcomponent Current Component Object
	 * @param string $primarySection   The primary section ("Add Extension" or "Edit Extension")
	 */
	public function getDeviceDisplay($display, $deviceInfo, $currentcomponent, $primarySection) {
		return "";
	}

	/**
	 * Used for writing configurations out for each driver if needed
	 */
	public function genConfig() {
		return array();
	}

	/**
	 * Used for modifing configurations before final write
	 * @param array $conf An array of configurations
	 */
	public function writeConfig($conf) {
		return $conf;
	}
}

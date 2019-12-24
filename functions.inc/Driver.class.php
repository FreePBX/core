<?php
/**
 * Base Core Driver class. Should be referenced by any extension driver
 */
namespace FreePBX\modules\Core;
abstract class Driver {
	protected $freepbx;
	protected $database;
	protected static $devicesGetUserMappings = array();
	protected static $removeMailboxSetting = null;

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

	protected function filterValidCodecs($codecs) {
		return $this->freepbx->Core->filterValidCodecs($codecs);
	}

	// get a mapping of the devices to user description and vmcontext
	// used for fixed devices when generating tech.conf files to
	// override some of the mailbox options or remove them if novm
	//
	public static function devicesGetUserMappings() {
		if (!empty(static::$devicesGetUserMappings)) {
			return static::$devicesGetUserMappings;
		}
		$device_list = \FreePBX::Core()->getAllDevicesByType();;
		foreach ($device_list as $device) {
			$devices[$device['id']] = $device;
		}
		$user_list = \FreePBX::Core()->listUsers(true);
		foreach ($user_list as $user) {
			$users[$user[0]]['description'] = $user[1];
			$users[$user[0]]['vmcontext'] = $user[2];
		}
		foreach ($devices as $id => $device) {
			if ($device['devicetype'] == 'fixed' && !empty($users[$device['user']])) {
				$devices[$id]['vmcontext'] = $users[$device['user']]['vmcontext'];
				$devices[$id]['description'] = $users[$device['user']]['description'];
			}
		}
		static::$devicesGetUserMappings = $devices;
		return static::$devicesGetUserMappings;
	}

	// map the actual vmcontext and user devicename if the device is fixed
	public static function map_dev_user($account, $keyword, $data) {
		$dev_user_map = static::devicesGetUserMappings();
		if(is_null(static::$removeMailboxSetting)) {
			static::$removeMailboxSetting = \FreePBX::Config()->get('DEVICE_REMOVE_MAILBOX');
		}

		if (!empty($dev_user_map[$account]) && $dev_user_map[$account]['devicetype'] == 'fixed') {
			switch (strtolower($keyword)) {
				case 'callerid':
					$user_option = $dev_user_map[$account]['description'] . ' <' . $account . '>';
				break;
				case 'mailboxes':
				case 'mailbox':
					if ((empty($dev_user_map[$account]['vmcontext']) || $dev_user_map[$account]['vmcontext'] == 'novm')
						&& strtolower($data) == "$account" . "@device"
						&& static::$removeMailboxSetting
					) {
						// they have no vm so don't put a mailbox=line
						$user_option = "";
					} elseif (strtolower($data) == "$account" . "@device"
						&& !empty($dev_user_map[$account]['vmcontext'])
						&& $dev_user_map[$account]['vmcontext'] != 'novm'
					) {
						$user_option = $dev_user_map[$account]['user'] . "@" . $dev_user_map[$account]['vmcontext'];
					} else {
						$user_option = $data;
					}
			}
			$output = $keyword . "=" . $user_option . "\n";
		} else {
			$output = $keyword . "=" . $data . "\n";
		}
		return $output;
	}
}

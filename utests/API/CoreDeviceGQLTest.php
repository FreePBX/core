<?php
namespace FreepPBX\Core\utests;

require_once('../api/utests/ApiBaseTestCase.php');

use FreePBX\modules\Core;
use Exception;
use FreePBX\modules\Api\utests\ApiBaseTestCase;

class CoreDeviceGQLTest extends ApiBaseTestCase {
	protected static $core;
	protected static $minTestExtension = 979000;
	protected static $maxTestExtension = 979999;

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		self::$core = self::$freepbx->Core;
	}

	public static function tearDownAfterClass() {
		parent::tearDownAfterClass();
		foreach(self::$core->getAllDevicesByType() as $device) {
			if ($device['id'] >= self::$minTestExtension && $device['id'] <= self::$maxTestExtension) {
				self::$core->delDevice($device['id']);
			}
		}
	}

	public function test_coreDeviceQuery_whenAllIsWell_shouldReturnDevice() {
		$testExtension = 979898;

		// clean up previous test
		self::$core->delDevice($testExtension);

		// create test device
		$settings = self::$core->generateDefaultDeviceSettings(
			'pjsip', 
			$testExtension,
			'pjsip test'
		);
		self::$core->addDevice($testExtension, 'pjsip', $settings);

		$response = $this->request("query { 
			coreDevice(device_id: \"{$testExtension}\") { 
				deviceId,
				description,
				devicetype,
				dial,
				emergencyCid
			}
		}");

		$json = json_decode((string)$response->getBody(), true);
		$this->assertEquals(array(
			'data' => array(
				'coreDevice' => array(
					'deviceId' => $testExtension,
					'description' => 'pjsip test',
					'devicetype' => 'fixed',
					'dial' => "PJSIP/{$testExtension}",
					'emergencyCid' => ''
				)
			)
		), $json);
	}
}

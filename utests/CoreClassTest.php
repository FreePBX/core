<?php
namespace FreepPBX\Core\utests;
use FreePBX\modules\Core;
use PHPUnit_Framework_TestCase;
use Exception;

class CoreClassTest extends PHPUnit_Framework_TestCase {
	protected static $freepbx;
	protected static $faker;	
	protected static $app;

	public static function setUpBeforeClass()
	{
		self::$freepbx = \FreePBX::create();

		self::$faker = \Faker\Factory::create();
		self::$app = self::$freepbx->Core;
	}

	public function testCore_changeDeviceTech_whenChangingSipDeviceToPjsip_shouldBeSuccessful() {
		$testExtension = '969800';

		// Cleaning up any previous tests
		self::$app->delDevice($testExtension);
		$settings = self::$app->generateDefaultDeviceSettings(
			'sip', 
			$testExtension,
			'sip change test'
		);
		self::$app->addDevice($testExtension, "sip", $settings);

		self::$app->changeDeviceTech($testExtension, 'pjsip');

		$device = self::$app->getDevice($testExtension);

		$this->assertEquals("969800", $device['id']);
		$this->assertEquals("pjsip", $device['tech']);
		$this->assertEquals("PJSIP/969800", $device['dial']);
		$this->assertEquals("fixed", $device['devicetype']);
		$this->assertEquals("sip change test", $device['description']);
		$this->assertEquals("969800", $device['account']);
		$this->assertEquals("sip change test <969800>", $device['callerid']);
		$this->assertEquals("969800@device", $device['mailbox']);
		$this->assertEquals("chan_pjsip", $device['sipdriver']);
		$this->assertNull(null, $device['emergency_cid']);
	}

	public function testCore_changeDeviceTech_whenChangingPjsipDeviceToSip_shouldBeSuccessful() {
		$testExtension = '969800';

		// Cleaning up any previous tests
		self::$app->delDevice($testExtension);
		$settings = self::$app->generateDefaultDeviceSettings(
			'pjsip', 
			$testExtension,
			'pjsip change test'
		);
		self::$app->addDevice($testExtension, "pjsip", $settings);

		self::$app->changeDeviceTech($testExtension, 'sip');

		$device = self::$app->getDevice($testExtension);

		$this->assertEquals("969800", $device['id']);
		$this->assertEquals("sip", $device['tech']);
		$this->assertEquals("SIP/969800", $device['dial']);
		$this->assertEquals("fixed", $device['devicetype']);
		$this->assertEquals("pjsip change test", $device['description']);
		$this->assertEquals("969800", $device['account']);
		$this->assertEquals("pjsip change test <969800>", $device['callerid']);
		$this->assertEquals("969800@device", $device['mailbox']);
		$this->assertEquals("chan_sip", $device['sipdriver']);
		$this->assertNull(null, $device['emergency_cid']);
	}

	public function testCore_changeDeviceTech_whenDeviceDoesntExist_shouldThrowError() {
		$testExtension = '969800';

		// Cleaning up any previous tests
		self::$app->delDevice($testExtension);

		try {
			self::$app->changeDeviceTech($testExtension, 'pjsip');
			$this->fail('Expected exception not thrown');
		} catch(Exception $e) {
			$this->assertEquals(
				'Unable to change device driver. Unable to fetch the device',
				$e->getMessage()
			);
		}
	}

	public function testCore_changeDeviceTech_whenChangingDeviceToPjsip_andDeviceAlreadyPjsip_shouldThrowError() {
		$testExtension = '969800';

		// Cleaning up any previous tests
		self::$app->delDevice($testExtension);
		$settings = self::$app->generateDefaultDeviceSettings(
			'pjsip', 
			$testExtension,
			'sip change test'
		);
		self::$app->addDevice($testExtension, "pjsip", $settings);

		try {
			self::$app->changeDeviceTech($testExtension, 'pjsip');
			$this->fail('Expected exception not thrown');
		} catch(Exception $e) {
			$this->assertEquals(
				'Unable to change device driver. The device is already set to the specified driver',
				$e->getMessage()
			);
		}
	}
}
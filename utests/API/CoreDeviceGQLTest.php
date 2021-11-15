<?php 
namespace FreepPBX\Core\utests;

require_once('../api/utests/ApiBaseTestCase.php');

use FreePBX\modules\Core;
use Exception;
use FreePBX\modules\Api\utests\ApiBaseTestCase;

class CoreDeviceGQLTest extends ApiBaseTestCase {
	protected static $core;
        
    /**
     * setUpBeforeClass
     *
     * @return void
     */
    public static function setUpBeforeClass() {
      parent::setUpBeforeClass();
      self::$core = self::$freepbx->core;
    }
        
    /**
     * tearDownAfterClass
     *
     * @return void
     */
    public static function tearDownAfterClass() {
      parent::tearDownAfterClass();
    }

	public function test_addCoreDevice_whenAllIsWell_shouldReturnTrue()
	{
		$testExtension = 979898;
		
		 // clear old test extension
		self::$core->delDevice($testExtension);

		$tech = "pjsip";
		$description = "Lorem Ipsum";
		$dial="12345";	
		$devicetype = "Lorem";
		$user = "123";
		$emergencyCid = "12333";

		$response = $this->request("mutation {
										addCoreDevice(input: {
											id:\"{$testExtension}\"
											tech:\"{$tech}\"
											description:\"{$description}\"
											dial:\"{$dial}\"
											devicetype:\"{$devicetype}\"
											user:\"{$user}\"
											emergency_cid:\"{$emergencyCid}\"
										}){
											message
											status
										}
									}");

		$json = (string)$response->getBody();

		$this->assertEquals('{"data":{"addCoreDevice":{"message":"Core device has been created successfully","status":true}}}', $json);

		// //status 200 success check
		$this->assertEquals(200, $response->getStatusCode());
	}

	public function test_addCoreDevice_whenDeviceAlreadyExists_shouldReturnFalse()
	{
		$testExtension = 979898;
		
		 // clear old test extension
		self::$core->delDevice($testExtension);

		$tech = "pjsip";
		$description = "Lorem Ipsum";
		$dial="12345";
		$devicetype = "Lorem";
		$user = "123";
		$emergencyCid = "12333";

		$deviceSettings = self::$core->generateDefaultDeviceSettings($tech, $testExtension, $description);
		self::$core->addDevice($testExtension, $tech, $deviceSettings);

		$response = $this->request("mutation {
										addCoreDevice(input: {
											id:\"{$testExtension}\"
											tech:\"{$tech}\"
											description:\"{$description}\"
											dial:\"{$dial}\"
											devicetype:\"{$devicetype}\"
											user:\"{$user}\"
											emergency_cid:\"{$emergencyCid}\"
										}){
											message
											status
										}
									}");

		$json = (string)$response->getBody();

		$this->assertEquals('{"errors":[{"message":"This device id is already in use","status":false}]}', $json);

		// //status 400 failure check
		$this->assertEquals(400, $response->getStatusCode());
	}

	public function test_addCoreDevice_withoutRequiredField_shouldReturnFalse()
	{
		$testExtension = 979898;
		
		 // clear old test extension
		self::$core->delDevice($testExtension);

		$tech = "pjsip";
		$description = "Lorem Ipsum";
		$devicetype = "Lorem";
		$user = "123";
		$emergencyCid = "12333";

		$deviceSettings = self::$core->generateDefaultDeviceSettings($tech, $testExtension, $description);
		self::$core->addDevice($testExtension, $tech, $deviceSettings);

		$response = $this->request("mutation {
										addCoreDevice(input: {
											id:\"{$testExtension}\"
											tech:\"{$tech}\"
											description:\"{$description}\"
											devicetype:\"{$devicetype}\"
											user:\"{$user}\"
											emergency_cid:\"{$emergencyCid}\"
										}){
											message
											status
										}
									}");

		$json = (string)$response->getBody();

		$this->assertEquals('{"errors":[{"message":"Field addCoreDeviceInput.dial of required type String! was not provided.","status":false}]}', $json);

		// //status 400 failure check
		$this->assertEquals(400, $response->getStatusCode());
	}
	
	public function test_updateCoreDevice_whenAllIsWell_shouldReturnTrue()
	{
		$testExtension = 979898;
		$tech = "pjsip";
		$description = "Lorem Ipsum";
		$dial="12345";
		$devicetype = "Lorem";
		$user = "123";
		$emergencyCid = "12333";
		$outboundId = '12345678901';

		// clear old test extension
		self::$core->delDevice($testExtension);
		self::$core->delUser($testExtension);

		//adding new extension
		$deviceSettings = self::$core->generateDefaultDeviceSettings($tech, $testExtension, $description);
		self::$core->addDevice($testExtension, $tech, $deviceSettings);

		$userSettings = self::$core->generateDefaultUserSettings($testExtension,$description);
		$userSettings['outboundcid'] = $outboundId;
		self::$core->addUser($testExtension,$userSettings);

		$response = $this->request("mutation {
										updateCoreDevice(input: {
											id:\"{$testExtension}\"
											tech:\"{$tech}\"
											description:\"Lorem Ipsum Updated\"
											dial:\"{$dial}\"
											devicetype:\"{$devicetype}\"
											user:\"{$user}\"
											emergency_cid:\"{$emergencyCid}\"
										}){
											message
											status
										}
									}");

		$json = (string)$response->getBody();

		$this->assertEquals('{"data":{"updateCoreDevice":{"message":"Core device has been updated successfully","status":true}}}', $json);

		// //status 200 success check
		$this->assertEquals(200, $response->getStatusCode());
	}

	public function test_updateCoreDevice_whenDeviceNotExists_shouldReturnFals()
	{
		$testExtension = 979898;
		$tech = "pjsip";
		$description = "Lorem Ipsum";
		$dial="12345";
		$devicetype = "Lorem";
		$user = "123";
		$emergencyCid = "12333";

		// clear old test extension
		self::$core->delDevice($testExtension);
		self::$core->delUser($testExtension);

		$response = $this->request("mutation {
										updateCoreDevice(input: {
											id:\"{$testExtension}\"
											tech:\"{$tech}\"
											description:\"{$description}\"
											dial:\"{$dial}\"
											devicetype:\"{$devicetype}\"
											user:\"{$user}\"
											emergency_cid:\"{$emergencyCid}\"
										}){
											message
											status
										}
									}");

		$json = (string)$response->getBody();

		$this->assertEquals('{"errors":[{"message":"Core device does not exists","status":false}]}', $json);

		// //status 200 success check
		$this->assertEquals(400, $response->getStatusCode());
	}
	
	public function test_deleteCoreDevice_whenAllIsWell_shouldReturnTrue()
	{
		$testExtension = 979898;
		$tech = "pjsip";
		$description = "Lorem Ipsum";
		$outboundId = '12345678901';

		// clear old test extension
		self::$core->delDevice($testExtension);
		self::$core->delUser($testExtension);

		//adding new extension
		$deviceSettings = self::$core->generateDefaultDeviceSettings($tech, $testExtension, $description);
		self::$core->addDevice($testExtension, $tech, $deviceSettings);

		$userSettings = self::$core->generateDefaultUserSettings($testExtension,$description);
		$userSettings['outboundcid'] = $outboundId;
		self::$core->addUser($testExtension,$userSettings);

		$response = $this->request("mutation {
										deleteCoreDevice(input: {
											id:\"{$testExtension}\"
										}){
											deletedId
											message
											status
										}
									}");

		$json = (string)$response->getBody();

		$this->assertEquals('{"data":{"deleteCoreDevice":{"deletedId":"979898","message":"Core device has been deleted successfully","status":true}}}', $json);

		// //status 200 success check
		$this->assertEquals(200, $response->getStatusCode());
	}
	
	public function test_fetchCoreDeviceQuery_whenAllIsWell_shouldReturnDevice() {
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
			fetchCoreDevice(device_id: \"{$testExtension}\") { 
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
				'fetchCoreDevice' => array(
					'deviceId' => $testExtension,
					'description' => 'pjsip test',
					'devicetype' => 'fixed',
					'dial' => "PJSIP/{$testExtension}",
					'emergencyCid' => ''
				)
			)
		), $json);
	}

	public function test_fetchCoreDeviceQuery_withInvalidQueryParam_shouldReturnfalse() {
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
			fetchCoreDevice(device_id: \"{$testExtension}\") { 
				deviceId,
				description,
				lorem,
				dial,
				emergencyCid
			}
		}");

		$json = (string)$response->getBody();

		$this->assertEquals('{"errors":[{"message":"Cannot query field \"lorem\" on type \"coredevice\".","status":false}]}', $json);

	}
	
	public function test_fetchAllCoreDeviceQuery_whenAllIsWell_shouldReturnAllDevice() {
		$mockcore = $this->getMockBuilder(\FreePBX\modules\core\Core::class)
			->disableOriginalConstructor()
			->disableOriginalClone()
			->setMethods(array('getAllDevicesByType','getUser'))
			->getMock();
		
		$mockcore->method('getAllDevicesByType')
			->willReturn(array(
							array(
								"id" => 99912,
								"tech" => "pjsip",
								"dial" => "PJSIP/99912",
								"devicetype" => "fixed",
								"user" => "99912",
								"description" => "99912  APi Test2",
								"emergency_cid" => "1221333331",
								"hint_override" => ""
							)
						));
		
		$mockcore->method('getUser')
			->willReturn(array());
		
		self::$freepbx->Core = $mockcore; 

		$response = $this->request("
									query{
										fetchAllCoreDevices {
											coreDevice{
												deviceId
												tech
												dial
												devicetype
												description
												emergencyCid
											}
											totalCount
											status
											message
										}
									}");


		$json = (string)$response->getBody();

		$this->assertEquals('{"data":{"fetchAllCoreDevices":{"coreDevice":[{"deviceId":"99912","tech":"pjsip","dial":"PJSIP\/99912","devicetype":"fixed","description":"99912  APi Test2","emergencyCid":"1221333331"}],"totalCount":1,"status":true,"message":"Core Device\'s found successfully"}}}', $json);

		// //status 200 success check
		$this->assertEquals(200, $response->getStatusCode());
	}
}

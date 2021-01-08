<?php 
namespace FreepPBX\Core\utests;

require_once('../api/utests/ApiBaseTestCase.php');

use FreePBX\modules\Core;
use Exception;
use FreePBX\modules\Api\utests\ApiBaseTestCase;

class CoreExtensionGQLTest extends ApiBaseTestCase {
    protected static $core;
	protected static $minTestExtension = 9090090211;
    protected static $maxTestExtension = 9090091211; 
    
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

	public function testExtentions(){

		$testExtension = "909000140";
		$name = 'api test';
		$tech = 'pjsip';
		$outboundId = '12345678901';

        // clear old test extension
		self::$core->delDevice($testExtension);
		self::$core->delUser($testExtension);

		//adding new extension
		$deviceSettings = self::$core->generateDefaultDeviceSettings($tech, $testExtension, $name);
		self::$core->addDevice($testExtension, $tech, $deviceSettings);

		$userSettings = self::$core->generateDefaultUserSettings($testExtension,$name);
		$userSettings['outboundcid'] = $outboundId;
		self::$core->addUser($testExtension,$userSettings);

		//fetch extension for the created record 
		$response = $this->request("
		  { 
			fetchExtension(extensionId: \"{$testExtension}\") { extensionId,user{name,outboundCid}}
		  }
		");

		$json = (string)$response->getBody();

		//validate the resoponse
		$this->assertEquals('{"data":{"fetchExtension":{"extensionId":"'.$testExtension.'","user":{"name":"api test","outboundCid":"'.$outboundId.'"}}}}',$json);
		  
		//status 200 success check
      $this->assertEquals(200, $response->getStatusCode());
	}
	
	public function testAddExtension(){

		$testExtension = "1112222";
		$name = 'api test';
		$email = "xyz@xyz.com";

        // clear old test extension
		self::$core->delDevice($testExtension);
		self::$core->delUser($testExtension);

		$tech = "pjsip";
		$clientMutationId = "test1231";

		$response = $this->request("mutation {
		 addExtension ( input: {
				extensionId: \"{$testExtension}\",
				name: \"{$name}\"
				tech : \"{$tech}\"
				email : \"{$email}\"
				clientMutationId : \"{$clientMutationId}\"
			  })
			  { clientMutationId status message }
			}
		");

		$json = (string)$response->getBody();

		$this->assertEquals(json_encode(array(
				'data' => array(
					'addExtension' => array(
						'clientMutationId' => $clientMutationId,
						'status' => true,
						'message'=>'Extension has been created Successfully'
					)
				)
			)
		),$json);

		//status 200 success check
      $this->assertEquals(200, $response->getStatusCode());
	}

	public function testAddExtensionFailureExtensionAlreadyExists(){

		$testExtension = "9090096111";
		$name = 'api test';
		$channelName = "channelName";
		$clientMutationId = "test1231";
		$email = "xyz@xyz.com";
		$tech = 'pjsip';

		$deviceSettings = self::$core->generateDefaultDeviceSettings($tech, $testExtension, $name);
		self::$core->addDevice($testExtension, $tech, $deviceSettings);

		$response = $this->request("mutation {
		 addExtension ( input: {
				extensionId: \"{$testExtension}\",
				name: \"{$name}\"
				channelName : \"{$channelName}\"
				email : \"{$email}\"
				clientMutationId : \"{$clientMutationId}\"
			  })
			  { clientMutationId message status }
			}
		");

		$json = (string)$response->getBody();

		$this->assertEquals(json_encode(array(
				'errors' => array(
					array('message' => "This device id is already in use" ,
					'status' => false)
					))
				),$json);
		
		//status 400 failure check
      $this->assertEquals(400, $response->getStatusCode());
	}

	public function testAddExtensionWithoutRequiredField(){

		$testExtension = "9090096999";
		$name = 'api test';
		$channelName = "channelName";
		$clientMutationId = "test1231";

        // clear old test extension
		self::$core->delDevice($testExtension);
		self::$core->delUser($testExtension);

		$response = $this->request("mutation {
		 addExtension ( input: {
				extensionId: \"{$testExtension}\",
				channelName : \"{$channelName}\"
				clientMutationId : \"{$clientMutationId}\"
			  })
			  { clientMutationId status }
			}
		");

		$json = (string)$response->getBody();

		$this->assertEquals(json_encode(array(
				'errors' => array(array(
					'message' => "Field addExtensionInput.name of required type String! was not provided." ,
					'status' => false
					)
				))
			),$json
		);

		//status 400 failure check
      $this->assertEquals(400, $response->getStatusCode());
	}

	public function testAddExtensionwithoutAnyField(){

		$testExtension = 909000899;
		$name = 'api test';
		$channelName = "channelName";
		$clientMutationId = "test1231";

		$response = $this->request("mutation {
		 addExtension ( input: {
			
			  })
			  { clientMutationId status }
			}
		");

		$json = (string)$response->getBody();

		$this->assertEquals(json_encode(array(
				'errors' => array(array(
					'message' => "Field addExtensionInput.extensionId of required type ID! was not provided." ,
					'status' => false
					)
				))),$json
		);

		//status 400 failure check
      $this->assertEquals(400, $response->getStatusCode());
	}

	public function testUpdateExtension()
	{
		$testExtension = "907070";
		$name = 'api test';
		$tech = 'pjsip';
		$outboundId = '12345678901';
		$channelName = "channelName";
		$clientMutationId = "test1231";

        // clear old test extension
		self::$core->delDevice($testExtension);
		self::$core->delUser($testExtension);

		//adding new extension
		$deviceSettings = self::$core->generateDefaultDeviceSettings($tech, $testExtension, $name);
		self::$core->addDevice($testExtension, $tech, $deviceSettings);

		$userSettings = self::$core->generateDefaultUserSettings($testExtension,$name);
		$userSettings['outboundcid'] = $outboundId;
		self::$core->addUser($testExtension,$userSettings);

		//updated the new extension created 
		$response = $this->request("mutation {
		 updateExtension ( input: {
				extensionId: \"{$testExtension}\",
				name: \"{$name}\"
				channelName : \"{$channelName}\"
				clientMutationId : \"{$clientMutationId}\"
			  })
			  { message status}
			}
		");

		$json = (string)$response->getBody();

		$this->assertEquals(json_encode(array(
				'data' => array(
				'updateExtension' => array('message' => "Extension has been updated",
					'status' => true)
				))
			),$json);
		
		//status 200 success check
      $this->assertEquals(200, $response->getStatusCode());
	}
	
	public function testUpdateExtensionWhichDoesNotExists()
	{
		$testExtension = 909000140;
		$name = 'api test';
		$channelName = "channelName";
		$clientMutationId = "test1231";

        // clear old test extension
		self::$core->delDevice($testExtension);
		self::$core->delUser($testExtension);

		//updated the new extension created 
		$response = $this->request("mutation {
		 updateExtension ( input: {
				extensionId: \"{$testExtension}\",
				name: \"{$name}\"
				channelName : \"{$channelName}\"
				clientMutationId : \"{$clientMutationId}\"
				
			  })
			  { message status }
			}
		");

		$json = (string)$response->getBody();

		$this->assertEquals(json_encode(array(
				'errors' => array(array(
						'message' => "Extension does not exists.",
						"status" => false
				)
			  ))
		),$json);

		//status 400 failure check
      $this->assertEquals(400, $response->getStatusCode());
	}
	
	public function testDeleteExtension(){

		$testExtension = 909000140;
		$name = 'api test';
		$tech = 'pjsip';
		$outboundId = '12345678901';
		$channelName = "channelName";
		$clientMutationId = "test1231";

        // clear old test extension
		self::$core->delDevice($testExtension);
		self::$core->delUser($testExtension);

		//adding new extension
		$deviceSettings = self::$core->generateDefaultDeviceSettings($tech, $testExtension, $name);
		self::$core->addDevice($testExtension, $tech, $deviceSettings);

		//delete the new extension created 
		$response = $this->request("mutation {
		 deleteExtension ( input: {
				extensionId: $testExtension
				 clientMutationId: \"{$clientMutationId}\"
			  })
			  { clientMutationId status }
			}
		");

		$json = (string)$response->getBody();

		$this->assertEquals(json_encode(array(
				'data' => array(
					'deleteExtension' => array(
						'clientMutationId' => $clientMutationId,
						'status' => true
					)
				)
			)
		),$json);

		//status 200 success check
      $this->assertEquals(200, $response->getStatusCode());	
	}

	public function testDeleteExtensionWhichDoesNotExists(){

		$testExtension = 909000140;
		$clientMutationId = "test1231";

		//delete the new extension created 
		$response = $this->request("mutation {
		 deleteExtension ( input: {
				extensionId: $testExtension
				 clientMutationId: \"{$clientMutationId}\"
			  })
			  { message status }
			}
		");

		$json = (string)$response->getBody();

		$this->assertEquals(json_encode(array(
				'errors' => array(
					array(
						'message' => "Extension does not exists.",
						'status' => false
					)
				)
			)
		),$json);

		//status 400 failure check
      $this->assertEquals(400, $response->getStatusCode());
	}

	public function testAddExtensionWhenBooleanOptionsAreSetToTrue(){

		$testExtension = "9090096111";
		$name = 'api test';

        // clear old test extension
		self::$core->delDevice($testExtension);
		self::$core->delUser($testExtension);

		$channelName = "channelName";
		$clientMutationId = "test1231";

		$response = $this->request("mutation {
		 addExtension ( input: {
				extensionId: \"{$testExtension}\",
				name: \"{$name}\"
				channelName : \"{$channelName}\"
				tech:\"pjsip\"
			   outboundCid:\"123456\"
			   email:\"test@gamil.com\"
			   umGroups:\"1\"
			   umEnable:true
			   vmPassword: \"abcdefgh\"
			   vmEnable:true
			   callerID: \"1234567\"
			   emergencyCid:\"112233445566\"
				clientMutationId : \"{$clientMutationId}\"
			  })
			  { clientMutationId status }
			}
		");

		$json = (string)$response->getBody();

		$this->assertEquals(json_encode(array(
				'data' => array(
					'addExtension' => array(
						'clientMutationId' => $clientMutationId,
						'status' => true
					)
				)
			)
		),$json);

		//status 200 success check
      $this->assertEquals(200, $response->getStatusCode());
	}

	public function testAddExtensionWhenBooleanOptionsAreSetToFalse(){

		$testExtension = 9090096111;
		$name = 'api test';

        // clear old test extension
		self::$core->delDevice($testExtension);
		self::$core->delUser($testExtension);

		$testExtension = 9090096111;
		$name = 'api test';
		$channelName = "channelName";
		$clientMutationId = "test1231";

		$response = $this->request("mutation {
		 addExtension ( input: {
				extensionId: \"{$testExtension}\",
				name: \"{$name}\"
				channelName : \"{$channelName}\"
				tech:\"pjsip\"
			   outboundCid:\"123456\"
			   email:\"test@gamil.com\"
			   umGroups:\"1\"
			   umEnable:false
			   vmPassword: \"abcdefgh\"
			   vmEnable:false
			   callerID: \"1234567\"
			   emergencyCid:\"112233445566\"
				clientMutationId : \"{$clientMutationId}\"
			  })
			  { clientMutationId status }
			}
		");

		$json = (string)$response->getBody();

		$this->assertEquals(json_encode(array(
				'data' => array(
					'addExtension' => array(
						'clientMutationId' => $clientMutationId,
						'status' => true
					)
				)
			)
		),$json);

		//status 200 success check
      $this->assertEquals(200, $response->getStatusCode());
	}

	public function testAddExtensionWhenBooleanOptionsAreSetToFalseAndTrue(){

		$testExtension = 9090096111;
		$name = 'api test';

        // clear old test extension
		self::$core->delDevice($testExtension);
		self::$core->delUser($testExtension);

		$testExtension = 9090096111;
		$name = 'api test';
		$channelName = "channelName";
		$clientMutationId = "test1231";

		$response = $this->request("mutation {
		 addExtension ( input: {
				extensionId: \"{$testExtension}\",
				name: \"{$name}\"
				channelName : \"{$channelName}\"
				tech:\"pjsip\"
			   outboundCid:\"123456\"
			   email:\"test@gamil.com\"
			   umGroups:\"1\"
			   umEnable:false
			   vmPassword: \"abcdefgh\"
			   vmEnable:true
			   callerID: \"1234567\"
			   emergencyCid:\"112233445566\"
				clientMutationId : \"{$clientMutationId}\"
			  })
			  { clientMutationId status }
			}
		");

		$json = (string)$response->getBody();

		$this->assertEquals(json_encode(array(
				'data' => array(
					'addExtension' => array(
						'clientMutationId' => $clientMutationId,
						'status' => true
					)
				)
			)
		),$json);

		//status 200 success check
      $this->assertEquals(200, $response->getStatusCode());
	}

	public function testAddExtensionWhenSendingSameFiledsTwoTimes(){
		//sending channel name fied 2 time should give an error
		$testExtension = "9090096111";
		$name = 'api test';

        // clear old test extension
		self::$core->delDevice($testExtension);
		self::$core->delUser($testExtension);

		$channelName = "channelName";
		$clientMutationId = "test1231";

		$response = $this->request("mutation {
		 addExtension ( input: {
				extensionId: \"{$testExtension}\",
				name: \"{$name}\"
				channelName : \"{$channelName}\"
				channelName : \"{$channelName}\"
				tech:\"pjsip\"
			   outboundCid:\"123456\"
			   email:\"test@gamil.com\"
			   umGroups:\"1\"
			   umEnable:true
			   vmPassword: \"abcdefgh\"
			   vmEnable:true
			   callerID: \"1234567\"
			   emergencyCid:\"112233445566\"
				clientMutationId : \"{$clientMutationId}\"
			  })
			  { clientMutationId status }
			}
		");

		$json = (string)$response->getBody();

		$this->assertEquals('{"errors":[{"message":"There can be only one input field named \"channelName\".","status":false}]}',$json);
		
		//status 400 failure check
      $this->assertEquals(400, $response->getStatusCode());
	}
}
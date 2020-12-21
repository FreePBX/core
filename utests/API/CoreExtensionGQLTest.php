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

		$testExtension = 909000140;
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
			fetchExtension(id: \"{$testExtension}\") { extension,user{name,outboundcid}}
		  }
		");

		$json = (string)$response->getBody();

		//validate the resoponse
		$this->assertEquals($json,'{"data":{"fetchExtension":{"extension":"'.$testExtension.'","user":{"name":"api test","outboundcid":"'.$outboundId.'"}}}}');
	}
	
	public function testAddExtension(){

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
				extension: \"{$testExtension}\",
				name: \"{$name}\"
				channelName : \"{$channelName}\"
				clientMutationId : \"{$clientMutationId}\"
			  })
			  { clientMutationId status }
			}
		");

		$json = (string)$response->getBody();

		$this->assertEquals($json,json_encode(array(
				'data' => array(
					'addExtension' => array(
						'clientMutationId' => $clientMutationId,
						'status' => "true"
					)
				)
			)
		));
	}

	public function testAddExtensionFailureExtensionAlreadyExists(){

		$testExtension = 9090096111;
		$name = 'api test';
		$channelName = "channelName";
		$clientMutationId = "test1231";

		$response = $this->request("mutation {
		 addExtension ( input: {
				extension: \"{$testExtension}\",
				name: \"{$name}\"
				channelName : \"{$channelName}\"
				clientMutationId : \"{$clientMutationId}\"
			  })
			  { clientMutationId message status }
			}
		");

		$json = (string)$response->getBody();

		$this->assertEquals($json,json_encode(array(
				'errors' => array(
					array('message' => "This device id is already in use" ,
					'status' => false)
					)
				)
			)
		);
	}

	public function testAddExtensionWithoutRequiredField(){

		$testExtension = 9090096999;
		$name = 'api test';
		$channelName = "channelName";
		$clientMutationId = "test1231";

        // clear old test extension
		self::$core->delDevice($testExtension);
		self::$core->delUser($testExtension);

		$response = $this->request("mutation {
		 addExtension ( input: {
				extension: \"{$testExtension}\",
				channelName : \"{$channelName}\"
				clientMutationId : \"{$clientMutationId}\"
			  })
			  { clientMutationId status }
			}
		");

		$json = (string)$response->getBody();

		$this->assertEquals($json,json_encode(array(
				'errors' => array(array(
					'message' => "Field addExtensionInput.name of required type String! was not provided." ,
					'status' => false
					)
				)
			  )
			)
		);
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

		$this->assertEquals($json,json_encode(array(
				'errors' => array(array(
					'message' => "Field addExtensionInput.extension of required type ID! was not provided." ,
					'status' => false
					)
				)
			  )
			)
		);
	}

	public function testUpdateExtension()
	{
		$testExtension = 909000899;
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

		//updated the new extension created 
		$response = $this->request("mutation {
		 updateExtension ( input: {
				extension: \"{$testExtension}\",
				name: \"{$name}\"
				channelName : \"{$channelName}\"
				clientMutationId : \"{$clientMutationId}\"
			  })
			  { message status}
			}
		");

		$json = (string)$response->getBody();

		$this->assertEquals($json,json_encode(array(
				'data' => array(
				'updateExtension' => array('message' => "Extension has been updated",
					'status' => "true")
				)
			)
		));
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
				extension: \"{$testExtension}\",
				name: \"{$name}\"
				channelName : \"{$channelName}\"
				clientMutationId : \"{$clientMutationId}\"
			  })
			  { message status }
			}
		");

		$json = (string)$response->getBody();

		$this->assertEquals($json,json_encode(array(
				'errors' => array(array(
						'message' => "Extension does not exists.",
						"status" => "false"
				)
			  )
			)
		));
	}
	

	public function testDeleteExtension()
	{
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
				extension: $testExtension
				 clientMutationId: \"{$clientMutationId}\"
			  })
			  { clientMutationId status }
			}
		");

		$json = (string)$response->getBody();

		$this->assertEquals($json,json_encode(array(
				'data' => array(
					'deleteExtension' => array(
						'clientMutationId' => $clientMutationId,
						'status' => "true"
					)
				)
			)
		));
	}

	public function testDeleteExtensionWhichDoesNotExists()
	{
		$testExtension = 909000140;
		$clientMutationId = "test1231";

		//delete the new extension created 
		$response = $this->request("mutation {
		 deleteExtension ( input: {
				extension: $testExtension
				 clientMutationId: \"{$clientMutationId}\"
			  })
			  { message status }
			}
		");

		$json = (string)$response->getBody();

		$this->assertEquals($json,json_encode(array(
				'errors' => array(
					array(
						'message' => "Extension does not exists.",
						'status' => "false"
					)
				)
			)
		));
	}

	public function testAddExtensionWhenBooleanOptionsAreSetToTrue(){

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
				extension: \"{$testExtension}\",
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

		$this->assertEquals($json,json_encode(array(
				'data' => array(
					'addExtension' => array(
						'clientMutationId' => $clientMutationId,
						'status' => "true"
					)
				)
			)
		));
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
				extension: \"{$testExtension}\",
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

		$this->assertEquals($json,json_encode(array(
				'data' => array(
					'addExtension' => array(
						'clientMutationId' => $clientMutationId,
						'status' => "true"
					)
				)
			)
		));
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
				extension: \"{$testExtension}\",
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

		$this->assertEquals($json,json_encode(array(
				'data' => array(
					'addExtension' => array(
						'clientMutationId' => $clientMutationId,
						'status' => "true"
					)
				)
			)
		));
	}

	public function testAddExtensionWhenSendingSameFiledsTwoTimes(){
		//sending channel name fied 2 time should give an error
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
				extension: \"{$testExtension}\",
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
	}
}
<?php 
namespace FreepPBX\Core\utests;

require_once('../api/utests/ApiBaseTestCase.php');

use FreePBX\modules\Core;
use Exception;
use FreePBX\modules\Api\utests\ApiBaseTestCase;

class CoreDeviceGQLTest extends ApiBaseTestCase {
	protected static $config;
        
    /**
     * setUpBeforeClass
     *
     * @return void
     */
    public static function setUpBeforeClass() {
      parent::setUpBeforeClass();
      self::$config = self::$freepbx->config;
    }
        
    /**
     * tearDownAfterClass
     *
     * @return void
     */
    public static function tearDownAfterClass() {
      parent::tearDownAfterClass();
    }

	public static function allConfigSettings(){
		return array(
					'AS_DISPLAY_FRIENDLY_NAME' => array(
															"keyword" => "AS_DISPLAY_FRIENDLY_NAME",
															"value" => 0,
															"name" => "Display Friendly Name",
															"level" => "0",
															"description" => "Normally the friendly names will be displayed on this page and the internal freepbx_conf configuration names are shown in the tooltip. If you prefer to view the configuration variables, and the friendly name in the tooltip, set this to false..",
															"type" => "bool",
															"options" => "",
															"defaultval" => "1",
															"readonly" => "0",
															"hidden" => "0",
															"category" => "Advanced Settings Details",
															"module" => "",
															"emptyok" => "0",
															"sortorder" => "0",
															"modified" => false
														)
					);
	}

	public static function configSettings(){
		return array(
						"keyword" => "AS_DISPLAY_FRIENDLY_NAME",
						"value" => 0,
						"name" => "Display Friendly Name",
						"level" => "0",
						"description" => "Normally the friendly names will be displayed on this page and the internal freepbx_conf configuration names are shown in the tooltip. If you prefer to view the configuration variables, and the friendly name in the tooltip, set this to false..",
						"type" => "bool",
						"options" => "",
						"defaultval" => "1",
						"readonly" => "0",
						"hidden" => "0",
						"category" => "Advanced Settings Details",
						"module" => "",
						"emptyok" => "0",
						"sortorder" => "0",
						"modified" => false
					);
	}

	public static function configSettingsWhichHasIntValue(){
		return ['HTTPBINDPORT' => array(
						"keyword"=>  "HTTPBINDPORT",
						"value"=>  8088,
						"name"=>  "HTTP Bind Port",
						"level"=>  "2",
						"description"=>  "Port to bind to. Default is 8088",
						"type"=>  "int",
						"options"=>  "10,65536",
						"defaultval"=>  "8088",
						"readonly"=>  "0",
						"hidden"=>  "0",
						"category"=>  "Asterisk Builtin mini-HTTP server",
						"module"=>  "",
						"emptyok"=>  "0",
						"sortorder"=>  "0",
						"modified"=>  false
					)];
	}

	public static function configSettingsWhichHasBooleanValue(){
		return array(
					'AS_DISPLAY_FRIENDLY_NAME' => array(
															"keyword" => "AS_DISPLAY_FRIENDLY_NAME",
															"value" => 0,
															"name" => "Display Friendly Name",
															"level" => "0",
															"description" => "Normally the friendly names will be displayed on this page and the internal freepbx_conf configuration names are shown in the tooltip. If you prefer to view the configuration variables, and the friendly name in the tooltip, set this to false..",
															"type" => "bool",
															"options" => "",
															"defaultval" => "1",
															"readonly" => "0",
															"hidden" => "0",
															"category" => "Advanced Settings Details",
															"module" => "",
															"emptyok" => "0",
															"sortorder" => "0",
															"modified" => false
														)
					);
	}
	
	public static function configSettingsWhichHasReadOnlyField(){
		return array(
					'HTTPWEBSOCKETMODE' => array(
															"keyword"=> "HTTPWEBSOCKETMODE",
															"value"=> "auto",
															"name"=> "Force WebSocket Mode",
															"level"=> "5",
															"description"=> "If set to anything other than auto Asterisk will be forced to use the technology set here for all websocket communication. Asterisk can not work with both drivers at the same time!",
															"type"=> "select",
															"options"=> "auto,sip,pjsip",
															"defaultval"=> "auto",
															"readonly"=> "1",
															"hidden"=> "0",
															"category"=> "Asterisk Builtin mini-HTTP server",
															"module"=> "",
															"emptyok"=> "0",
															"sortorder"=> "0",
															"modified"=> false
														)
					);
	}

	public static function configSettingsWhichHasDirectoryValue(){
		return array(
					'AMPLOCALBIN' => array(
															"keyword"=> "AMPLOCALBIN",
															"value"=> "",
															"name"=> "AMPLOCALBIN Dir for retrieve_conf",
															"level"=> "2",
															"description"=> "If this directory is defined, retrieve_conf will check for a file called <i>retrieve_conf_post_custom</i> and if that file exists, it will be included after other processing thus having full access to the current environment for additional customization.",
															"type"=> "dir",
															"options"=> "",
															"defaultval"=> "",
															"readonly"=> "1",
															"hidden"=> "0",
															"category"=> "Developer and Customization",
															"module"=> "",
															"emptyok"=> "1",
															"sortorder"=> "0",
															"modified"=> false
														)
					);
	}
	
	public static function configSettingsWhichHasSelectValueField(){
		return array(
					'DEVICE_SIP_CANREINVITE' => array(
															"keyword"=> "DEVICE_SIP_CANREINVITE",
															"value"=> "no",
															"name"=> "SIP canrenivite (directmedia)",
															"level"=> "0",
															"description"=> "Default setting for (new Extension) SIP canreinvite (same as directmedia). See Asterisk documentation for details.",
															"type"=> "select",
															"options"=> "no,yes,nonat,update",
															"defaultval"=> "no",
															"readonly"=> "0",
															"hidden"=> "0",
															"category"=> "Device Settings",
															"module"=> "",
															"emptyok"=> "0",
															"sortorder"=> "20",
															"modified"=> false
														)
					);
	}

	public function test_fetchAllAdvanceSettings_whenAllIsWell_shouldReturnTrue()
	{
		$mockconfig = $this->getMockBuilder(\FreePBX\Config::class)
			->disableOriginalConstructor()
			->disableOriginalClone()
			->disableArgumentCloning()
			->disallowMockingUnknownTypes()
			->setMethods(array('get_conf_settings'))
			->getMock();
		
		$mockconfig->method('get_conf_settings')
			->willReturn($this->allConfigSettings());
		
		self::$freepbx->config = $mockconfig; 

		$response = $this->request("query{
										fetchAllAdvanceSettings {
											settings{
												keyword
												value
												name
												category
												description
											}
											status
											message
										}
									}");

		$json = (string)$response->getBody();
		$this->assertEquals('{"data":{"fetchAllAdvanceSettings":{"settings":[{"keyword":"AS_DISPLAY_FRIENDLY_NAME","value":"0","name":"Display Friendly Name","category":"Advanced Settings Details","description":"Normally the friendly names will be displayed on this page and the internal freepbx_conf configuration names are shown in the tooltip. If you prefer to view the configuration variables, and the friendly name in the tooltip, set this to false.."}],"status":true,"message":"Setting\'s found successfully"}}}', $json);

		$this->assertEquals(200, $response->getStatusCode());
	}
	
	public function test_fetchAllAdvanceSettings_whenInvalidQueryIsGiven_shouldReturnFalse()
	{
		
		$mockconfig = $this->getMockBuilder(\FreePBX\Config::class)
			->disableOriginalConstructor()
			->disableOriginalClone()
			->disableArgumentCloning()
			->disallowMockingUnknownTypes()
			->setMethods(array('get_conf_settings'))
			->getMock();
		
		$mockconfig->method('get_conf_settings')
			->willReturn($this->allConfigSettings());
		
		self::$freepbx->config = $mockconfig; 

		$response = $this->request("query{
										fetchAllAdvanceSettings {
											settings{
												keyword
												lorem
												name
												category
												description
											}
											status
											message
										}
									}");

		$json = (string)$response->getBody();

		$this->assertEquals('{"errors":[{"message":"Cannot query field \"lorem\" on type \"advacesettings\".","status":false}]}', $json);

		$this->assertEquals(400, $response->getStatusCode());
	}
	
	public function test_fetchAdvanceSetting_whenAllIsWell_shouldReturnTrue()
	{
		$mockconfig = $this->getMockBuilder(\FreePBX\Config::class)
			->disableOriginalConstructor()
			->disableOriginalClone()
			->disableArgumentCloning()
			->disallowMockingUnknownTypes()
			->setMethods(array('conf_setting','get_conf_settings'))
			->getMock();
		
		$mockconfig->method('conf_setting')
			->willReturn($this->configSettings());

		$mockconfig->method('get_conf_settings')
			->willReturn($this->allConfigSettings());
		
		self::$freepbx->config = $mockconfig; 

		$response = $this->request("
									query{
										fetchAdvanceSetting(keyword:\"AS_DISPLAY_FRIENDLY_NAME\") {
											keyword
											value
											name
											category
											description
											status
											message
										}
									}");

		$json = (string)$response->getBody();
		$this->assertEquals('{"data":{"fetchAdvanceSetting":{"keyword":"AS_DISPLAY_FRIENDLY_NAME","value":"0","name":"Display Friendly Name","category":"Advanced Settings Details","description":"Normally the friendly names will be displayed on this page and the internal freepbx_conf configuration names are shown in the tooltip. If you prefer to view the configuration variables, and the friendly name in the tooltip, set this to false..","status":true,"message":"Setting\'s found successfully"}}}', $json);

		$this->assertEquals(200, $response->getStatusCode());
	}

	public function test_fetchAdvanceSetting_whenSettingsNotFound_shouldReturnFalse()
	{
		$mockconfig = $this->getMockBuilder(\FreePBX\Config::class)
			->disableOriginalConstructor()
			->disableOriginalClone()
			->disableArgumentCloning()
			->disallowMockingUnknownTypes()
			->setMethods(array('conf_setting','get_conf_settings'))
			->getMock();
			
		$mockconfig->method('get_conf_settings')
			->willReturn($this->allConfigSettings());
		
		$mockconfig->method('conf_setting')
			->willReturn(array());
		
		self::$freepbx->config = $mockconfig; 

		$response = $this->request("
									query{
									fetchAdvanceSetting(keyword:\"AS_DISPLAY_FRIENDLY_NAME\") {
										keyword
										value
										name
										category
										description
										status
										message
									}
									}");

		$json = (string)$response->getBody();
		$this->assertEquals('{"errors":[{"message":"Sorry, unable to find settings","status":false}]}', $json);

		$this->assertEquals(400, $response->getStatusCode());
	}

	public function test_updateAdvanceSettings_whenAllIsWell_shouldReturnTrue()
	{
		$mockconfig = $this->getMockBuilder(\FreePBX\Config::class)
			->disableOriginalConstructor()
			->disableOriginalClone()
			->disableArgumentCloning()
			->disallowMockingUnknownTypes()
			->setMethods(array('conf_setting','get_conf_settings','set_conf_values'))
			->getMock();
			
		$mockconfig->method('get_conf_settings')
			->willReturn($this->allConfigSettings());
		
		$mockconfig->method('conf_setting')
			->willReturn($this->configSettings());
		
		$mockconfig->method('set_conf_values')
			->willReturn(true);
		
		self::$freepbx->config = $mockconfig; 

		$response = $this->request("mutation {
										updateAdvanceSettings(input: {
											keyword:AS_DISPLAY_FRIENDLY_NAME
											value:\"0\"
										}){
											message
											status
										}
									}");

		$json = (string)$response->getBody();
		$this->assertEquals('{"data":{"updateAdvanceSettings":{"message":"Setting\'s updated successfully","status":true}}}', $json);

		$this->assertEquals(200, $response->getStatusCode());
	}

	public function test_updateAdvanceSettings_whenNotAbleToUpdate_shouldReturnFalse()
	{
		$mockconfig = $this->getMockBuilder(\FreePBX\Config::class)
			->disableOriginalConstructor()
			->disableOriginalClone()
			->disableArgumentCloning()
			->disallowMockingUnknownTypes()
			->setMethods(array('conf_setting','get_conf_settings','set_conf_values'))
			->getMock();
			
		$mockconfig->method('get_conf_settings')
			->willReturn($this->allConfigSettings());
		
		$mockconfig->method('conf_setting')
			->willReturn($this->configSettings());
		
		$mockconfig->method('set_conf_values')
			->willReturn(false);
		
		self::$freepbx->config = $mockconfig; 

		$response = $this->request("mutation {
										updateAdvanceSettings(input: {
											keyword:AS_DISPLAY_FRIENDLY_NAME
											value:\"0\"
										}){
											message
											status
										}
									}");

		$json = (string)$response->getBody();
		$this->assertEquals('{"errors":[{"message":"Sorry, unable to update settings","status":false}]}', $json);

		$this->assertEquals(400, $response->getStatusCode());
	}

	public function test_updateAdvanceSettings_whenInCorrectValueIsGiven_forIntFieldType_shouldReturnFalse()
	{
		$mockconfig = $this->getMockBuilder(\FreePBX\Config::class)
			->disableOriginalConstructor()
			->disableOriginalClone()
			->disableArgumentCloning()
			->disallowMockingUnknownTypes()
			->setMethods(array('conf_setting','get_conf_settings','set_conf_values'))
			->getMock();
			
		$mockconfig->method('get_conf_settings')
			->willReturn($this->configSettingsWhichHasBooleanValue());
		
		$mockconfig->method('conf_setting')
			->willReturn($this->configSettingsWhichHasBooleanValue()['AS_DISPLAY_FRIENDLY_NAME']);
		
		$mockconfig->method('set_conf_values')
			->willReturn(false);
		
		self::$freepbx->config = $mockconfig; 

		$response = $this->request("mutation {
										updateAdvanceSettings(input: {
											keyword:AS_DISPLAY_FRIENDLY_NAME
											value:\"lorem\"
										}){
											message
											status
										}
									}");

		$json = (string)$response->getBody();
		$this->assertEquals('{"errors":[{"message":"Value of AS_DISPLAY_FRIENDLY_NAME should be boolean. Possible values can be 1 or 0","status":false}]}', $json);

		$this->assertEquals(400, $response->getStatusCode());
	}
	
	public function test_updateAdvanceSettings_whenTryingToUpdateReadOnlyField_shouldReturnFalse()
	{
		$mockconfig = $this->getMockBuilder(\FreePBX\Config::class)
			->disableOriginalConstructor()
			->disableOriginalClone()
			->disableArgumentCloning()
			->disallowMockingUnknownTypes()
			->setMethods(array('conf_setting','get_conf_settings','set_conf_values'))
			->getMock();
			
		$mockconfig->method('get_conf_settings')
			->willReturn($this->configSettingsWhichHasReadOnlyField());
		
		$mockconfig->method('conf_setting')
			->willReturn($this->configSettingsWhichHasReadOnlyField()['HTTPWEBSOCKETMODE']);
		
		$mockconfig->method('set_conf_values')
			->willReturn(false);
		
		self::$freepbx->config = $mockconfig; 

		$response = $this->request("mutation {
										updateAdvanceSettings(input: {
											keyword:HTTPWEBSOCKETMODE
											value:\"lorem\"
										}){
											message
											status
										}
									}");

		$json = (string)$response->getBody();
		$this->assertEquals('{"errors":[{"message":"Settings can not be updated. Permission denied","status":false}]}', $json);

		$this->assertEquals(400, $response->getStatusCode());
	}

	public function test_updateAdvanceSettings_whenInCorrectValueIsGiven_forSelectFieldFieldType_shouldReturnFalse()
	{
		$mockconfig = $this->getMockBuilder(\FreePBX\Config::class)
			->disableOriginalConstructor()
			->disableOriginalClone()
			->disableArgumentCloning()
			->disallowMockingUnknownTypes()
			->setMethods(array('conf_setting','get_conf_settings','set_conf_values'))
			->getMock();
			
		$mockconfig->method('get_conf_settings')
			->willReturn($this->configSettingsWhichHasSelectValueField());
		
		$mockconfig->method('conf_setting')
			->willReturn($this->configSettingsWhichHasSelectValueField()['DEVICE_SIP_CANREINVITE']);
		
		$mockconfig->method('set_conf_values')
			->willReturn(false);
		
		self::$freepbx->config = $mockconfig; 

		$response = $this->request("mutation {
										updateAdvanceSettings(input: {
											keyword:DEVICE_SIP_CANREINVITE
											value:\"lorem\"
										}){
											message
											status
										}
									}");

		$json = (string)$response->getBody();
		$this->assertEquals('{"errors":[{"message":"Invalid settings values, Possible values for this settings are no,yes,nonat,update","status":false}]}', $json);

		$this->assertEquals(400, $response->getStatusCode());
	}
}

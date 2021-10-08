<?php

namespace FreePBX\modules\Core\Api\Gql;

use GraphQLRelay\Relay;
use GraphQL\Type\Definition\Type;
use FreePBX\modules\Api\Gql\Base;
use \GraphQL\Error\FormattedError;

class Extensions extends Base {
	protected $module = 'core';

	public static function getScopes() {
		return [
			'read:extension' => [
				'description' => _('Read Extensions'),
			],
			'write:extension' => [
				'description' => _('Write Extensions'),
			]
		];
	}

	public function mutationCallback() {
			if($this->checkAllWriteScope()) {
			return function() {
				return [
					'addExtension' => Relay::mutationWithClientMutationId([
						'name' => 'addExtension',
						'description' => _('Add a new Extension to Core'),
						'inputFields' => $this->getMutationFields(),
						'outputFields' => $this->getOutputFields(),
						'mutateAndGetPayload' => function ($input) {
							$input = $this->resolveNames($input);
							//check license count 
							$res = $this->freepbx->core->checkExtensionLicenseCount();
							if(!$res){
								return ['message' => _('Can not add extension beyond the extensions license limit'), 'status' => false];
							}
							if((isset($input['umEnable']) && $input['umEnable'] == true) && !isset($input['umPassword'])  ){
								return ['message' => _('User management is enabled please provide password'), 'status' => false];
							}
							if(!is_numeric($input['extension'])){
								return ['message' => _("Please enter only numeric values"),'status' => false];
							}
							$res = $this->freepbx->Core->getDevice($input['extension']);
							if(!empty($res)){
								return ['message' => _("This device id is already in use"),'status' => false];
							}
							try{
								$status = $this->freepbx->Core->processQuickCreate($input['tech'],$input['extension'],$input);
							}catch(\Exception $ex){
								//extracting exception message and updating with response message
								FormattedError::setInternalErrorMessage($ex->getMessage());
							}
							if($status == True){
								return ['message' => _("Extension has been created Successfully"),'status' => true];
							}else{
								return ['message' => _("Failed to create extension"),'status' => false];
							}
						}
					]),
					'updateExtension' => Relay::mutationWithClientMutationId([
						'name' => 'updateExtension',
						'description' => _('Update an Extension in Core'),
						'inputFields' => $this->getMutationFieldsUpdate(),
						'outputFields' => $this->getOutputFields(),
						'mutateAndGetPayload' => function ($input) {
						    try {
								$input = $this->resolveNames($input);
								if((isset($input['umEnable']) && $input['umEnable'] == true) && !isset($input['umPassword'])  ){
									return ['message' => _('User management is enabled please provide password'), 'status' => false];
								}
								if(!is_numeric($input['extension'])){
									return ['message' => _("Please enter only numeric values"),'status' => false];
								}
								$extensionExists = $this->freepbx->Core->getDevice($input['extension']);
								if (empty($extensionExists)) {
									return array("status" => false, "message" => _("Extension does not exists."));
								}
								$users = $this->freepbx->Core->getUser($input['extension']);
								$userman = $this->freepbx->userman->getUserByUsername($input['extension']);
								$input = $this->getUpdatedValues($extensionExists,$users,$userman,$input);

								$this->freepbx->Core->delDevice($input['extension'], true);
								$this->freepbx->Core->delUser($input['extension']);	
								if(!empty($userman))
							   	$this->freepbx->userman->deleteUserByID($userman['id']);
								$status = $this->freepbx->Core->processQuickCreate($input['tech'] ,$input['extension'],$input);
								if($status == True){
									return array("status" => true ,"message"=> _("Extension has been updated"));
								}else{
									return array("status" => false ,"message"=> _("Sorry could not update the extension"));
								}
							}catch(\Exception $ex ){
								FormattedError::setInternalErrorMessage($ex->getMessage());
							}
						}
					]),
					'deleteExtension' => Relay::mutationWithClientMutationId([
						'name' => 'deleteExtension',
						'description' => _('Delete an Extension in Core'),
						'inputFields' => ['extensionId' => ['type' => Type::nonNull(Type::id()),'description' => _('Extension Number to be deleted')]],
						'outputFields' => $this->getOutputFields(),
						'mutateAndGetPayload' => function ($input) {
							try {
								$input['extension'] = $input['extensionId'];
								if(!is_numeric($input['extension'])){
									return ['message' => _("Please enter only numeric values"),'status' => false];
								}
								$extensionExists = $this->freepbx->Core->getDevice($input['extension']);
								if (empty($extensionExists)) {
									return array("status" => false, "message" => _("Extension does not exists."));
								}
								$this->freepbx->Core->delDevice($input['extension'], true);
								$this->freepbx->Core->delUser($input['extension']);
								return array("status" => true ,"message"=> _("Extension has been deleted"));
							}catch(Exception $ex){
								FormattedError::setInternalErrorMessage($ex->getMessage());
							}
						}
					]),
					'createRangeofExtension' => Relay::mutationWithClientMutationId([
						'name' => 'CreateRangeofExtension',
						'description' => _('Create a Range of Extensions'),
						'inputFields' => $this->getMutationFieldsRange(),
						'outputFields' => $this->getOutputFields(),
						'mutateAndGetPayload' => function ($input) {
							//check license count 
							$res = $this->freepbx->core->checkExtensionLicenseCount();
							if(!$res){
								return ['message' => _('Can not add extension beyond the extensions license limit'), 'status' => false];
							}
							$count= 0;
							$max =$input['startExtension'] + $input['numberOfExtensions'];
							$name = $input['name'];
							for($i =$input['startExtension'];$i< $max; $i++){
								$input['name'] = $i.'  '.$name;
								$input['extension'] = $i;
								$input = $this->resolveNames($input);
								try{
									$re = $this->freepbx->Core->processQuickCreate($input['tech'],$i,$input);
								}catch(\Exception $ex){
									FormattedError::setInternalErrorMessage($ex->getMessage());
								}
								if($re['status'] == true){
									$count ++;
								}
							}
							$item['status'] = $count;
							if(!empty($item)){
								return ['message' => _("Extension's has been created Successfully"),'status' => true];
							}else{
								return ['message' => _("This device id is already in use"),'status' => false];
							}
						}
					]),
				];
			};
		}
	}

	public function queryCallback() {
		if($this->checkAllReadScope()) {
			return function() {
				return [
					'fetchAllExtensions' => [
						'type' => $this->typeContainer->get('extension')->getConnectionType(),
						'description' => '',
						'args' => Relay::connectionArgs(),
						'resolve' => function($root, $args) {
							$list = Relay::connectionFromArray($this->freepbx->Core->getAllDevicesByType(), $args);
							if(isset($list) && $list != null){
								return ['response'=> $list,'status'=>true, 'message'=> _("Extension's found successfully")];
							}else{
								return ['message'=> _("Sorry, unable to find any extensions"),'status' => false];
							}
						},
					],
					'fetchExtension' => [
						'type' => $this->typeContainer->get('extension')->getObject(),
						'description' => '',
						'args' => [
							'extensionId' => [
								'type' => Type::id(),
								'description' => _('The ExtensionId to fetch'),
							]
						],
						'resolve' => function($root, $args) {
							$res = $this->freepbx->Core->getDevice($args['extensionId']);
							try{
								if(!empty($res)){
									return ['response' => $res, 'status' => true, 'message' => _('Extension found successfully')];
								}else{
									return ['status' => false, 'message' => _('Extension does not exists')];
								}
							}catch(Exception $ex){
								FormattedError::setInternalErrorMessage($ex->getMessage());
							}		
						}
					]
				];
			};
		}
	}

	public function initializeTypes() {
		$user = $this->typeContainer->create('extension');
		$user->setDescription('');

		$user->addInterfaceCallback(function() {
			return [$this->getNodeDefinition()['nodeInterface']];
		});

		$user->setGetNodeCallback(function($id) {
			return $this->getSingleData($id);
		});

		$user->addFieldCallback(function() {
			return [
				'id' => Relay::globalIdField('extension', function($row) {
					return isset($row['response']) ? $row['response']['id'] : null;
				}),
				'extensionId' => [
					'type' => Type::id(),
					'description' => _('Give your device a unique integer ID. The device will use this ID to authenticate to the system'),
					'resolve' => function($row) {
						if(isset($row['id'])){
							return $row['id'];
						}elseif(isset($row['response'])){
							return  $row['response']['id'];
						}
						return null;
					}
				],
				'tech' => [
					'type' => Type::string(),
					'description' => _('Device tech default is pjsip'),
					'resolve' => function($row) {
						if(isset($row['tech'])){
							return $row['tech'];
						}elseif(isset($row['response'])){
							return  $row['response']['tech'];
						}
						return null;
					}
				],
				'user' => [
					'type' => $this->typeContainer->get('coreuser')->getObject(),
					'description' => _('Fixed devices will always mapped to this user. Adhoc devices will be mapped to this user by default.'),
					'resolve' => function($row) {
						if(isset($row['response']) && $row['response']['devicetype'] !== 'fixed') {
							return null;
						}else if(isset($row['devicetype']) && $row['devicetype'] !== 'fixed'){
							return null;
						}
						$data = array();
						if(isset($row['response']['user'])){
							$data = $this->freepbx->Core->getUser($row['response']['user']);
						}elseif(isset($row['user'])){
							$data = $this->freepbx->Core->getUser($row['user']);
						}
						if (isset($data['extension'])) {
							$sipDetails = $this->freepbx->Core->getSipSecret($data['extension']);
							if (isset($sipDetails)) {
								foreach ($sipDetails as $sipData) {
									$data['extPassword'] = $sipData['data'];
								}
								return $data;
							}
						} else {
							return $data;
						}
						return null;	
					}
				],
				'coreDevice' => [
					'type' => $this->typeContainer->get('coredevice')->getObject(),
					'description' => _('Fixed devices will always mapped to this user. Adhoc devices will be mapped to this user by default.'),
					'resolve' => function($row) {
						if(isset($row['response'])){
							return $row['response'];
						}elseif(isset($row)){
							return  $row;
						}
						return null;
					}
				],
				'status' => [
					'type' => Type::boolean(),
					'resolve' => function ($payload) {
						return $payload['status'];
					}
				],
				'message' => [
					'type' => Type::string(),
					'resolve' => function ($payload) {
						return $payload['message'];
					}
				],
			];
		});

		$user->setConnectionResolveNode(function ($edge) {
			return $edge['node'];
		});

		$user->setConnectionFields(function() {
			return [
				'totalCount' => [
					'type' => Type::int(),
					'resolve' => function($value) {
						return count($this->freepbx->Core->getAllDevicesByType());
					}
				],
				'extension' => [
					'type' => Type::listOf($this->typeContainer->get('extension')->getObject()),
					'resolve' => function($root, $args) {
						$data = array_map(function($row){
							return $row['node'];
						},$root['response']['edges']);
						return $data;
					}
				],
				'status' => [
					'type' => Type::boolean(),
					'resolve' => function ($payload) {
						return $payload['status'];
					}
				],
				'message' => [
					'type' => Type::string(),
					'resolve' => function ($payload) {
						return $payload['message'];
					}
				],
			];
		});
	}
	private function getMutationFieldsRange() {
		return [
			'startExtension' => [
				'type' => Type::nonNull(Type::id()),
				'description' => _("Give your Extension Starting Number")
			],
			'name' => [
				'type' => Type::nonNull(Type::string()),
				'description' => _("The CallerID name , a name which you want to append along with the extension number.")
			],
			'tech' => [
				'type' => Type::string(),
				'description' => _("Technology driver type")
			],
			'numberOfExtensions' => [
				'type' => Type::nonNull(Type::id()),
				'description' => _("Number of extensions you want to create.")
			],
			'umEnable' => [
				'type' => Type::boolean(),
				'description' => _("Usermanagment enable yes/no.")
			],
			'outboundCid' => [
				'type' => Type::string(),
				'description' => _("Overrides the CallerID when dialing out a trunk. Any setting here will override the common outbound CallerID set in the Trunks admin.
									Format: \"caller name\" <#######>")
			],
			'umGroups' => [
				'type' => Type::string(),
				'description' => _("Usermanagment groupid. (comma seperated)")
			],
			'emergencyCid' => [
				'type' => Type::string(),
				'description' => _("This CallerID will always be set when dialing out an Outbound Route flagged as Emergency. The Emergency CID overrides all other CallerID settings.")
			],
			'email' => [
				'type' => Type::nonNull(Type::string()),
				'description' => _("Email address to use for services such as Voicemail, User Management and Fax.")
			],
			'vmEnable' => [
				'type' => Type::boolean(),
				'description' => _("Voicemail enable yes/no.")
			],
			'vmPassword' => [
				'type' => Type::string(),
				'description' => _("Voicemail password")
			],
			'callerID' => [
				'type' => Type::string(),
				'description' => _("User caller ID")
			],
			'channelName' => [
				'type' => Type::string(),
				'description' => _("Channel Name incase if you are using tech DAHDi.")
			],
		];
	}
		
	private function getMutationFields() {
		return [
			'extensionId' => [
				'type' => Type::nonNull(Type::id()),
				'description' => _("Give your Extension a unique integer ID. The Extension will use this ID to create Extension in the system")
			],
			'tech' => [
				'type' => Type::string(),
				'description' =>_("Technology driver type")
			],
			'channelName' => [
				'type' => Type::string(),
				'description' => _("Channel Name incase if you are using tech DAHDi.")
				],
			'name' => [
				'type' => Type::nonNull(Type::string()),
				'description' => _("The CallerID name for calls from this user will be set to this name. Only enter the name, NOT the number.")
			],
			'outboundCid' => [
				'type' => Type::string(),
				'description' => _("Overrides the CallerID when dialing out a trunk. Any setting here will override the common outbound CallerID set in the Trunks admin.
									Format: \"caller name\" <#######>")
			],
			'emergencyCid' => [
				'type' => Type::string(),
				'description' => _("This CallerID will always be set when dialing out an Outbound Route flagged as Emergency. The Emergency CID overrides all other CallerID settings.")
			],
			'email' => [
				'type' => Type::nonNull(Type::string()),
				'description' => _("Email address to use for services such as Voicemail, User Management and Fax.")
			],
			'umEnable' => [
				'type' => Type::boolean(),
				'description' => _("Usermanagment enable yes/no.")
			],
			'umGroups' => [
				'type' => Type::string(),
				'description' => _("Usermanagment groupid. (comma seperated)")
			],
			'vmEnable' => [
				'type' => Type::boolean(),
				'description' => _("Voicemail enable yes/no.")
			],
			'vmPassword' => [
				'type' => Type::string(),
				'description' => _("Voicemail password")
			],
			'callerID' => [
				'type' => Type::string(),
				'description' => _("User caller ID")
			],
			'umPassword' => [
				'type' => Type::string(),
				'description' => _("The user's password")
			],
			 'maxContacts' => [
                'type' => Type::string(),
                 'description' => _("Max Contacts will set the maximum concurrent contacts for a PJSIP extension")
            ],
		];
	}
	
		private function getMutationFieldsupdate() {
		return [
			'extensionId' => [
				'type' => Type::nonNull(Type::id()),
				'description' => _("Give your Extension a unique integer ID. The Extension will use this ID to create Extension in the system")
			],
			'tech' => [
				'type' => Type::string(),
				'description' =>_("Technology driver type")
			],
			'channelName' => [
				'type' => Type::string(),
				'description' => _("Channel Name incase if you are using tech DAHDi.")
				],
			'name' => [
				'type' => Type::string(),
				'description' => _("The CallerID name for calls from this user will be set to this name. Only enter the name, NOT the number.")
			],
			'outboundCid' => [
				'type' => Type::string(),
				'description' => _("Overrides the CallerID when dialing out a trunk. Any setting here will override the common outbound CallerID set in the Trunks admin.
									Format: \"caller name\" <#######>")
			],
			'emergencyCid' => [
				'type' => Type::string(),
				'description' => _("This CallerID will always be set when dialing out an Outbound Route flagged as Emergency. The Emergency CID overrides all other CallerID settings.")
			],
			'email' => [
				'type' => Type::string(),
				'description' => _("Email address to use for services such as Voicemail, User Management and Fax.")
			],
			'umEnable' => [
				'type' => Type::boolean(),
				'description' => _("Usermanagment enable yes/no.")
			],
			'umGroups' => [
				'type' => Type::string(),
				'description' => _("Usermanagment groupid. (comma seperated)")
			],
			'vmEnable' => [
				'type' => Type::boolean(),
				'description' => _("Voicemail enable yes/no.")
			],
			'vmPassword' => [
				'type' => Type::string(),
				'description' => _("Voicemail password")
			],
			'callerID' => [
				'type' => Type::string(),
				'description' => _("User caller ID")
			],
			'extPassword' => [
				'type' => Type::string(),
				'description' => _("Password (secret) configured for the device. Should be alphanumeric with at least 2 letters and numbers to keep secure. [secret]")
			],
			'umPassword' => [
				'type' => Type::string(),
				'description' => _("The user's password")
			],
          	'maxContacts' => [
                'type' => Type::string(),
                'description' => _("Max Contacts will set the maximum concurrent contacts for a PJSIP extension")
            ],
		];
	}
	/**
	 * resolveNames
	 *
	 * @param  mixed $input
	 * @return void
	 */
	private function resolveNames($input){
		$input['um-groups'] = isset($input['umGroups']) ? explode(',',$input['umGroups']) : array('1');
		if(isset($input['vmEnable']) && $input['vmEnable'] == false){
			$input['vm']  = 'no';
		}else{
			$input['vm'] = 'yes';
		}
		if(isset($input['umEnable']) && $input['umEnable'] == false){
			$input['um']  = 'no';
		}else{
			$input['um']  = 'yes';
		}
		$input['vmpwd'] = isset($input['vmPassword']) ? $input['vmPassword'] : '';
		$input['tech']= isset($input['tech']) ? $input['tech'] : "pjsip";
		$input['outboundcid'] = isset($input['outboundCid']) ? $input['outboundCid'] : '';

		$input['tech']= isset($input['tech']) ? $input['tech'] : "pjsip";
		$input['emergency_cid'] = isset($input['emergencyCid']) ? $input['emergencyCid'] : '';
		$input['callerid'] = isset($input['callerID']) ? $input['callerID'] : $input['emergency_cid'] ;
		$input['channel'] = isset($input['channelName']) ? $input['channelName'] : '';
		$input['calleridname']['value'] = $input['name'];	
		$input['password'] = isset($input['umPassword']) ? $input['umPassword'] : '';
		$input['secret'] = isset($input['extPassword']) ? $input['extPassword'] : ''; 

		if(isset($input['extensionId'])){
			$input['extension'] = $input['extensionId'];
		}
      
		if($input['tech'] == "pjsip"){
			if(isset($input['maxContacts']) && !empty($input['maxContacts'])){
				if($input['maxContacts'] > 100){
					$input['max_contacts'] = 100;
				}else{
					$input['max_contacts'] = $input['maxContacts'];
				}
			}else{
				$input['max_contacts'] = 1 ;
			}
		}
      
		
		return $input;
	}
	
	/**
	 * getUpdatedValues
	 *
	 * @param  mixed $extensionExists
	 * @param  mixed $users
	 * @param  mixed $userman
	 * @param  mixed $input
	 * @return void
	 */
	private function getUpdatedValues($extensionExists,$users,$userman,$input){
		$voicemail = $this->freepbx->LoadConfig->getConfig("voicemail.conf");

		$input['tech']= !empty($input['tech']) ? $input['tech'] : $extensionExists['tech'];
		$input['channel']= !empty($input['channel']) ? $input['channel'] : $extensionExists['description'];
		$input['emergency_cid']= !empty($input['emergency_cid']) ? $input['emergency_cid'] : $extensionExists['emergency_cid'];
		$input['callerid']= !empty($input['callerid']) ? $input['callerid'] : (isset($extensionExists['callerid']) ? $extensionExists['callerid'] : '');
		$input['name']= !empty($input['name']) ? $input['name'] : (isset($users['name']) ? $users['name'] : '');
		$input['outboundcid']= !empty($input['outboundcid']) ? $input['outboundcid'] : (isset($users['outboundcid']) ? $users['outboundcid'] : '');
		$input['email']= !empty($input['email']) ? $input['email'] : $userman['email'];

		$vm = isset($voicemail['default'][$input['extension']]) ? ($voicemail['default'][$input['extension']]) : '';

		if(!empty($vm)){
			$options = explode(",",$vm);
			$input['vmpwd'] = !empty($input['vmpwd']) ? $input['vmpwd'] : (!empty($options[0]) ? $options[0] : '');
			$input['vm'] = !empty($input['vm']) ? $input['vm'] : 'yes';
		}else{
			$input['vmpwd'] = !empty($input['vmpwd']) ? $input['vmpwd'] :  '';
		}
      
		if($input['tech'] == "pjsip"){
			if(isset($input['maxContacts']) && !empty($input['maxContacts'])){
				if($input['maxContacts'] > 100){
					$input['max_contacts'] = 100;
				}else{
					$input['max_contacts'] = $input['maxContacts'];
				}
			}else{
				$input['max_contacts'] = $extensionExists['max_contacts'] ;
			}
        }
					
		return $input;
	}
	
	/**
	 * getoutputFields
	 *
	 * @return void
	 */
	public function getoutputFields(){
		return [
			'status' => [
			'type' => Type::boolean(),
		],
		   'message' => [
			'type' => Type::string(),
			]
		];
	}
}

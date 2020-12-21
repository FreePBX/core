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
						'outputFields' => [
							'status' => [
								'type' => Type::nonNull(Type::string()),
								'resolve' => function ($payload) {
									return $payload['status'];
								}
							],
							'message' => [
								'type' => Type::nonNull(Type::string()),
								'resolve' => function ($payload) {
									return $payload['message'];
								}
							]
						],
						'mutateAndGetPayload' => function ($input) {
							$input = $this->resolveNames($input);
							try{
								$input['tech']= isset($input['tech']) ? $input['tech'] : "pjsip";
								$input['outboundcid'] = isset($input['outboundCid']) ? $input['outboundCid'] : '';
								$status = $this->freepbx->Core->processQuickCreate($input['tech'],$input['extension'],$input);
							}catch(\Exception $ex){
								//extracting exception message and updating with response message
								FormattedError::setInternalErrorMessage($ex->getMessage());
							}
							return !empty($status) ? $status : [];
						}
					]),
					'updateExtension' => Relay::mutationWithClientMutationId([
						'name' => 'updateExtension',
						'description' => _('Update an Extension in Core'),
						'inputFields' => $this->getMutationFields(),
						'outputFields' => [
							'status' => [
								'type' => Type::nonNull(Type::string()),
								'resolve' => function ($payload) {
									return $payload['status'];
								}
							],
							'message' => [
								'type' => Type::string(),
								'resolve' => function ($payload) {
									return $payload['message'];
								}
							]
						],
						'mutateAndGetPayload' => function ($input) {
						    try {
								$extensionExists = $this->freepbx->Core->getDevice($input['extension']);
								if (empty($extensionExists)) {
									return array("status" => false, "message" => _("Extension does not exists."));
								}
								$this->freepbx->Core->delDevice($input['extension'], true);
								$this->freepbx->Core->delUser($input['extension']);
								$input = $this->resolveNames($input);
								$status = $this->freepbx->Core->processQuickCreate($input['tech'] ,$input['extension'],$input);
								if($status['status'] == True){
									return array("status" => true ,"message"=> "Extension has been updated");
								}else{
									return array("status" => false ,"message"=> "Sorry could not update the extension");
								}
							}catch(\Exception $ex ){
								FormattedError::setInternalErrorMessage($ex->getMessage());
							}
							return !empty($status) ? $status: [];
						}
					]),
					'deleteExtension' => Relay::mutationWithClientMutationId([
						'name' => 'deleteExtension',
						'description' => _('Delete an Extension in Core'),
						'inputFields' => ['extension' => ['type' => Type::nonNull(Type::id()),'description' => 'Extension Number to be deleted']],
						'outputFields' => [
							'status' => [
								'type' => Type::nonNull(Type::string()),
								'resolve' => function ($payload) {
									return $payload['status'];
								}
							],
							'message' => [
								'type' => Type::string(),
								'resolve' => function ($payload) {
									return $payload['message'];
								}
							]
						],
						'mutateAndGetPayload' => function ($input) {
							try {
								$extensionExists = $this->freepbx->Core->getDevice($input['extension']);
								if (empty($extensionExists)) {
									return array("status" => false, "message" => _("Extension does not exists."));
								}
								$this->freepbx->Core->delDevice($input['extension'], true);
								$this->freepbx->Core->delUser($input['extension']);
								$item = array("status" => true ,"message"=> "Extension has been deleted");
							}catch(Exception $ex){
								FormattedError::setInternalErrorMessage($ex->getMessage());
							}
						
							return !empty($item) ? $item : [];
						}
					]),
					'createRangeofExtension' => Relay::mutationWithClientMutationId([
						'name' => 'CreateRangeofExtension',
						'description' => _('Create a Range of Extensions'),
						'inputFields' => $this->getMutationFieldsRange(),
						'outputFields' => [
							'status' => [
								'type' => Type::nonNull(Type::string()),
								'resolve' => function ($payload) {
									return $payload['status'];
								}
							],
							'message' => [
								'type' => Type::string(),
								'resolve' => function ($payload) {
									return $payload['message'];
								}
							]
						],
						'mutateAndGetPayload' => function ($input) {
							$count= 0;
							$max =$input['startExtension'] + $input['numberOfExtensions'];
							for($i =$input['startExtension'];$i< $max; $i++){
								$input['name'] = $i.'  '.$input['name'];
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
							return !empty($item) ? $item : [];
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
							return Relay::connectionFromArray($this->freepbx->Core->getAllDevicesByType(), $args);
						},
					],
					'fetchExtension' => [
						'type' => $this->typeContainer->get('extension')->getObject(),
						'description' => '',
						'args' => [
							'id' => [
								'type' => Type::id(),
								'description' => 'The ID',
							]
						],
						'resolve' => function($root, $args) {
							try{
								return $this->freepbx->Core->getDevice($args['id']);
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
					return isset($row['id']) ? $row['id'] : null;
				}),
				'extension' => [
					'type' => Type::nonNull(Type::string()),
					'description' => _('Give your device a unique integer ID. The device will use this ID to authenticate to the system'),
					'resolve' => function($row) {
						return isset($row['id']) ? $row['id'] : null;
					}
				],
				'user' => [
					'type' => $this->typeContainer->get('coreuser')->getObject(),
					'description' => _('Fixed devices will always mapped to this user. Adhoc devices will be mapped to this user by default.'),
					'resolve' => function($row) {
						if($row['devicetype'] !== 'fixed') {
							return null;
						}
						$item = $this->freepbx->Core->getUser($row['user']);
						return isset($item) ? $item : null;
					}
				],
				'device' => [
					'type' => $this->typeContainer->get('coredevice')->getObject(),
					'description' => _('Fixed devices will always mapped to this user. Adhoc devices will be mapped to this user by default.'),
					'resolve' => function($row) {
						return $row;
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
						return $this->getTotal();
					}
				],
				'extension' => [
					'type' => Type::listOf($this->typeContainer->get('extension')->getObject()),
					'resolve' => function($root, $args) {
						$data = array_map(function($row){
							return $row['node'];
						},$root['edges']);
						return $data;
					}
				]
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
				'type' => Type::string(),
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
			'extension' => [
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
		];
	}

	private function resolveNames($input){
		$input['um-groups'] = isset($input['umGroups']) ? explode(',',$input['umGroups']) : 1;
		if(isset($input['vmEnable']) && $input['vmEnable'] == true){
			$input['vm']  = 'yes';
		}elseif(isset($input['vmEnable']) && $input['vmEnable'] == false){
			$input['vm']  = 'no';
		}else{
			$input['vm'] = 'yes';
		}
		if(isset($input['umEnable']) && $input['umEnable'] == true){
			$input['umEnable']  = 'yes';
		}elseif(isset($input['umEnable']) && $input['umEnable'] == false){
			$input['umEnable']  = 'no';
		}
		$input['vmpwd'] = isset($input['vmPassword']) ? $input['vmPassword'] : '';
		$input['tech']= isset($input['tech']) ? $input['tech'] : "pjsip";
		$input['outboundcid'] = isset($input['outboundCid']) ? $input['outboundCid'] : '';
		return $input;
	}
}

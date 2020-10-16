<?php

namespace FreePBX\modules\Core\Api\Gql;

use GraphQLRelay\Relay;
use GraphQL\Type\Definition\Type;
use FreePBX\modules\Api\Gql\Base;

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
					'addCoreExten' => Relay::mutationWithClientMutationId([
						'name' => 'addCoreExten',
						'description' => _('Add a new Extension to Core'),
						'inputFields' => $this->getMutationFields(),
						'outputFields' => [
							'status' => [
								'type' => Type::nonNull(Type::string()),
								'resolve' => function ($payload) {
									return $payload['status'];
								}
							]
						],
						'mutateAndGetPayload' => function ($input) {
							$input['um-groups'] = implode(',',$input['umgroups']);
							$status = $this->freepbx->Core->processQuickCreate($input['tech'],$input['extension'],$input);
							return !empty($status) ? $status : [];
						}
					]),
					'updateCoreExten' => Relay::mutationWithClientMutationId([
						'name' => 'updateCoreExten',
						'description' => _('Update an Extension in Core'),
						'inputFields' => $this->getMutationFields(),
						'outputFields' => [
							'status' => [
								'type' => Type::nonNull(Type::string()),
								'resolve' => function ($payload) {
									return $payload['status'];
								}
							]
						],
						'mutateAndGetPayload' => function ($input) {
							$this->freepbx->Core->delDevice($input['extension'], true);
							$this->freepbx->Core->delUser($input['extension']);
							$input['um-groups'] = explode(',',$input['umgroups']);
							$status = $this->freepbx->Core->processQuickCreate($input['tech'],$input['extension'],$input);
							return !empty($status) ? $status: [];
						}
					]),
					'deleteCoreExten' => Relay::mutationWithClientMutationId([
						'name' => 'deleteCoreExten',
						'description' => _('Delete an Extension in Core'),
						'inputFields' => ['extension' => ['type' => Type::nonNull(Type::id()),'description' => 'Extension Number to be deleted']],
						'outputFields' => [
							'status' => [
								'type' => Type::nonNull(Type::string()),
								'resolve' => function ($payload) {
									return $payload['status'];
								}
							]
						],
						'mutateAndGetPayload' => function ($input) {
							$this->freepbx->Core->delDevice($input['extension'], true);
							$this->freepbx->Core->delUser($input['extension']);
							$item = array("status" => true);;
							return !empty($item) ? $item : [];
						}
					]),
					'CreateRangeofExten' => Relay::mutationWithClientMutationId([
						'name' => 'CreateRangeofExten',
						'description' => _('Create a Range of Extensions'),
						'inputFields' => $this->getMutationFieldsRange(),
						'outputFields' => [
							'status' => [
								'type' => Type::nonNull(Type::string()),
								'resolve' => function ($payload) {
									return $payload['status'];
								}
							]
						],
						'mutateAndGetPayload' => function ($input) {
							$name = $input['name'];
							$count= 0;
							$max =$input['start'] + $input['nuberofexten'];
							for($i =$input['start'];$i< $max; $i++){
								$input['name'] = $i.'  '.$name;
								$input['extension'] = $i;
								$input['um-groups'] = explode(',',$input['umgroups']);
								$re = $this->freepbx->Core->processQuickCreate($input['tech'],$i,$input);
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
					'allExtensions' => [
						'type' => $this->typeContainer->get('extension')->getConnectionType(),
						'description' => '',
						'args' => Relay::connectionArgs(),
						'resolve' => function($root, $args) {
							return Relay::connectionFromArray($this->freepbx->Core->getAllDevicesByType(), $args);
						},
					],
					'extension' => [
						'type' => $this->typeContainer->get('extension')->getObject(),
						'description' => '',
						'args' => [
							'id' => [
								'type' => Type::id(),
								'description' => 'The ID',
							]
						],
						'resolve' => function($root, $args) {
							return $this->freepbx->Core->getDevice($args['id']);
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
			'start' => [
				'type' => Type::nonNull(Type::id()),
				'description' => _("Give your Extension Starting Number")
			],
			'name' => [
				'type' => Type::nonNull(Type::string()),
				'description' => _("The CallerID name , a name which you want to append along with the extension number.")
			],
			'tech' => [
				'type' => Type::nonNull(Type::string()),
				'description' => _("Technology driver type")
			],
			'nuberofexten' => [
				'type' => Type::nonNull(Type::id()),
				'description' => _("Number of extensions you want to create.")
			],
			'um' => [
				'type' => Type::string(),
				'description' => _("Usermanagment enable yes/no.")
			],
			'outboundcid' => [
				'type' => Type::string(),
				'description' => _("Overrides the CallerID when dialing out a trunk. Any setting here will override the common outbound CallerID set in the Trunks admin.
									Format: \"caller name\" <#######>")
			],
			'umgroups' => [
				'type' => Type::string(),
				'description' => _("Usermanagment groupid. (comma seperated)")
			],
			'emergency_cid' => [
				'type' => Type::string(),
				'description' => _("This CallerID will always be set when dialing out an Outbound Route flagged as Emergency. The Emergency CID overrides all other CallerID settings.")
			],
			'email' => [
				'type' => Type::string(),
				'description' => _("Email address to use for services such as Voicemail, User Management and Fax.")
			],
			'vm' => [
				'type' => Type::string(),
				'description' => _("Voicemail enable yes/no.")
			],
			'vmpwd' => [
				'type' => Type::string(),
				'description' => _("Voicemail password")
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
				'type' => Type::nonNull(Type::string()),
				'description' =>_("Technology driver type")
			],
			'channel' => [
				'type' => Type::string(),
				'description' => _("Channel Name incase if you are using tech DAHDi.")
				],
			'name' => [
				'type' => Type::nonNull(Type::string()),
				'description' => _("The CallerID name for calls from this user will be set to this name. Only enter the name, NOT the number.")
			],
			'outboundcid' => [
				'type' => Type::string(),
				'description' => _("Overrides the CallerID when dialing out a trunk. Any setting here will override the common outbound CallerID set in the Trunks admin.
									Format: \"caller name\" <#######>")
			],
			'emergency_cid' => [
				'type' => Type::string(),
				'description' => _("This CallerID will always be set when dialing out an Outbound Route flagged as Emergency. The Emergency CID overrides all other CallerID settings.")
			],
			'email' => [
				'type' => Type::string(),
				'description' => _("Email address to use for services such as Voicemail, User Management and Fax.")
			],
			'um' => [
				'type' => Type::string(),
				'description' => _("Usermanagment enable yes/no.")
			],
			'umgroups' => [
				'type' => Type::string(),
				'description' => _("Usermanagment groupid. (comma seperated)")
			],
			'vm' => [
				'type' => Type::string(),
				'description' => _("Voicemail enable yes/no.")
			],
			'vmpwd' => [
				'type' => Type::string(),
				'description' => _("Voicemail password")
			],
		];
	}

}

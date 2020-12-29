<?php

namespace FreePBX\modules\Core\Api\Gql;

use GraphQLRelay\Relay;
use GraphQL\Type\Definition\Type;
use FreePBX\modules\Api\Gql\Base;

class Devices extends Base {
	protected $module = 'core';

	public static function getScopes() {
		return [
			'read:device' => [
				'description' => _('Read Devices'),
			],
			'write:devices' => [
				'description' => _('Write Devices'),
			]
		];
	}

	public function mutationCallback() {
		if($this->checkAllWriteScope()) {
			return function() {
				return [
					'addCoreDevice' => Relay::mutationWithClientMutationId([
						'name' => 'addCoreDevice',
						'description' => 'Add a new entry to Core',
						'inputFields' => $this->getMutationFields(),
						'outputFields' => [
							'coredevice' => [
								'type' => $this->typeContainer->get('coredevice')->getObject(),
								'resolve' => function ($payload) {
									return count($payload) > 1 ? $payload : null;
								}
							]
						],
						'mutateAndGetPayload' => function ($input) {
							$output = $this->getMutationExecuteArray($input);
							$defaults = $this->freepbx->Core->generateDefaultDeviceSettings($output['tech'], $output['id'],$output['description']);
							$this->freepbx->Core->addDevice($input['id'],$input['tech'],$defaults);
							$item = $this->freepbx->Core->getDevice($input['id']);
							return !empty($item) ? $item : [];
						}
					]),
					'updateCoreDevice' => Relay::mutationWithClientMutationId([
						'name' => 'updateCoreDevice',
						'description' => 'Update an entry in Core',
						'inputFields' => $this->getMutationFields(),
						'outputFields' => [
							'coredevice' => [
								'type' => $this->typeContainer->get('coredevice')->getObject(),
								'resolve' => function ($payload) {
									return count($payload) > 1 ? $payload : null;
								}
							]
						],
						'mutateAndGetPayload' => function ($input) {
							$output = $this->getMutationExecuteArray($input);
							$this->freepbx->Core->delDevice($extension, true);
							$this->freepbx->Core->addDevice($input['id'],$input['tech'],$output);
							$item = $this->freepbx->Core->getDevice($input['extension']);
							return !empty($item) ? $item : [];
						}
					]),
					'removeCoreDevice' => Relay::mutationWithClientMutationId([
						'name' => 'removeCoreDevice',
						'description' => 'Remove an entry from Core',
						'inputFields' => [
							'id' => [
								'type' => Type::nonNull(Type::id())
							]
						],
						'outputFields' => [
							'deletedId' => [
								'type' => Type::nonNull(Type::id()),
								'resolve' => function ($payload) {
									return $payload['id'];
								}
							]
						],
						'mutateAndGetPayload' => function ($input) {
							$this->freepbx->Core->delDevice($input['id']);
							return ['id' => $input['id']];
						}
					])
				];
			};
		}
	}

	public function queryCallback() {
		if($this->checkAllReadScope()) {
			return function() {
				return [
					'allCoreDevices' => [
						'type' => $this->typeContainer->get('coredevice')->getConnectionType(),
						'description' => '',
						'args' => Relay::connectionArgs(),
						'resolve' => function($root, $args) {
							return Relay::connectionFromArray($this->freepbx->Core->getAllDevicesByType(), $args);
						},
					],
					'coreDevice' => [
						'type' => $this->typeContainer->get('coredevice')->getObject(),
						'description' => '',
						'args' => [
							'device_id' => [
								'type' => Type::id(),
								'description' => 'The Device ID',
							]
						],
						'resolve' => function($root, $args) {
							return $this->freepbx->Core->getDevice($args['device_id']);
						}
					]
				];
			};
		}
	}

	public function initializeTypes() {
		$user = $this->typeContainer->create('coredevice');
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
				'device_id' => [
					'type' => Type::nonNull(Type::string()),
					'description' => 'Give your device a unique integer ID. The device will use this ID to authenticate to the system',
					'resolve' => function($row) {
						return isset($row['id']) ? $row['id'] : null;
					}
				],
				'tech' => [
					'type' => $this->typeContainer->get('coretech')->getObject(),
					'description' => 'Technology driver type',
					'resolve' => function($row) {
						return $row;
					}
				],
				'dial' => [
					'type' => Type::nonNull(Type::string()),
					'description' => 'How to dial this device, this should not be changed unless you know what you are doing.',
				],
				'devicetype' => [
					'type' => Type::nonNull(Type::string()),
					'description' => 'Devices can be fixed or adhoc. Fixed devices are always associated to the same extension/user. Adhoc devices can be logged into and logged out of by users.',

				],
				'user' => [
					'type' => $this->typeContainer->get('coreuser')->getObject(),
					'description' => 'Fixed devices will always mapped to this user. Adhoc devices will be mapped to this user by default.',
					'resolve' => function($row) {
						$item = $this->freepbx->Core->getUser($row['user']);
						return isset($item) ? $item : null;
					}
				],
				'description' => [
					'type' => Type::string(),
					'description' => 'The CallerID name for this device will be set to this description until it is logged into.'
				],
				'emergency_cid' => [
					'type' => Type::string(),
					'description' => 'This CallerID will always be set when dialing out an Outbound Route flagged as Emergency. The Emergency CID overrides all other CallerID settings.',

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
				'coreDevice' => [
					'type' => Type::listOf($this->typeContainer->get('coredevice')->getObject()),
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

	private function getMutationFields() {
		return [
			'id' => [
				'type' => Type::nonNull(Type::id()),
				'description' => 'Give your device a unique integer ID. The device will use this ID to authenticate to the system'
			],
			'tech' => [
				'type' => Type::nonNull(Type::string()),
				'description' => 'Technology driver type'
			],
			'dial' => [
				'type' => Type::nonNull(Type::string()),
				'description' => 'How to dial this device, this should not be changed unless you know what you are doing.'
			],
			'devicetype' => [
				'type' => Type::nonNull(Type::string()),
				'description' => 'Devices can be fixed or adhoc. Fixed devices are always associated to the same extension/user. Adhoc devices can be logged into and logged out of by users.'
			],
			'user' => [
				'type' => Type::string(),
				'description' => 'Fixed devices will always mapped to this user. Adhoc devices will be mapped to this user by default.'
			],
			'description' => [
				'type' => Type::string(),
				'description' => 'The CallerID name for this device will be set to this description until it is logged into.'
			],
			'emergency_cid' => [
				'type' => Type::string(),
				'description' => 'This CallerID will always be set when dialing out an Outbound Route flagged as Emergency. The Emergency CID overrides all other CallerID settings.'
			],

		];
	}

	private function getMutationExecuteArray($input) {
		return [
			"id" => isset($input['id']) ? $input['id'] : '',
			"tech" => isset($input['tech']) ? $input['tech'] : '',
			"dial" => isset($input['dial']) ? $input['dial'] : '',
			"devicetype" => isset($input['devicetype']) ? $input['devicetype'] : '',
			"user" => isset($input['user']) ? $input['user'] : null,
			"description" => isset($input['description']) ? $input['description'] : null,
			"emergency_cid" => isset($input['emergency_cid']) ? $input['emergency_cid'] : null,
		];
	}
}

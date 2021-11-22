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
						'description' => _('Add a new entry to Core'),
						'inputFields' => $this->getMutationFields(),
						'outputFields' => $this->getOutputFields(),
						'mutateAndGetPayload' => function ($input) {
							$item = $this->freepbx->Core->getDevice($input['id']);
							if (!empty($item)) {
								return ['message' => _("This device id is already in use"), 'status' => false];
							} 
							$output = $this->getMutationExecuteArray($input);
							$defaults = $this->freepbx->Core->generateDefaultDeviceSettings($output['tech'], $output['id'],$output['description']);
							$defaults['emergency_cid']['value'] = isset($input['emergency_cid']) ? $input['emergency_cid'] : "";
							$defaults['devicetype']['value'] = isset($input['devicetype']) ? $input['devicetype'] : "";
							$defaults['user']['value'] = isset($input['user']) ? $input['user'] : "";
							$this->freepbx->Core->addDevice($input['id'],$input['tech'],$defaults);
							$item = $this->freepbx->Core->getDevice($input['id']);
							if (!empty($item)) {
								return ['message' => _("Core device has been created successfully"), 'status' => true, 'response' => $item];
							} else {
								return ['message' => _("Failed to create core device"), 'status' => false, 'response' => []];
							}
						}
					]),
					'updateCoreDevice' => Relay::mutationWithClientMutationId([
						'name' => 'updateCoreDevice',
						'description' => _('Update an entry in Core'),
						'inputFields' => $this->getMutationFields(),
						'outputFields' => $this->getOutputFields(),
						'mutateAndGetPayload' => function ($input) {
							$item = $this->freepbx->Core->getDevice($input['id']);
							if (empty($item)) {
								return ['message' => _("Core device does not exists"), 'status' => false];
							} 
							$output = $this->getMutationExecuteArray($input);
							$this->freepbx->Core->delDevice($input['id'], true);
							$defaults = $this->freepbx->Core->generateDefaultDeviceSettings($output['tech'], $output['id'], $output['description']);
							$defaults['emergency_cid']['value'] = isset($input['emergency_cid']) ? $input['emergency_cid'] : "";
							$defaults['devicetype']['value'] = isset($input['devicetype']) ? $input['devicetype'] : "";
							$defaults['user']['value'] = isset($input['user']) ? $input['user'] : "";
							$this->freepbx->Core->addDevice($input['id'], $input['tech'], $defaults);
							$item = $this->freepbx->Core->getDevice($input['id']);
							if (!empty($item)) {
								return ['message' => _("Core device has been updated successfully"), 'status' => true, 'response' => $item];
							} else {
								return ['message' => _("Failed to updated core device"), 'status' => false, 'response' => []];
							}
						}
					]),
					'deleteCoreDevice' => Relay::mutationWithClientMutationId([
						'name' => 'deleteCoreDevice',
						'description' => _('Remove an entry from Core'),
						'inputFields' => [
							'id' => [
								'type' => Type::nonNull(Type::id())
							]
						],
						'outputFields' => $this->deleteCoreDeviceOutputFields(),
						'mutateAndGetPayload' => function ($input) {
							$this->freepbx->Core->delDevice($input['id']);
							return ['message' => _("Core device has been deleted successfully"), 'status' => true, 'id' => $input['id']];
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
					'fetchAllCoreDevices' => [
						'type' => $this->typeContainer->get('coredevice')->getConnectionType(),
						'description' => _('Fetchs all core devices'),
						'args' => Relay::connectionArgs(),
						'resolve' => function($root, $args) {
							$list = Relay::connectionFromArray($this->freepbx->Core->getAllDevicesByType(), $args);
							if (isset($list) && $list != null) {
								return ['response' => $list, 'status' => true, 'message' => _("Core Device's found successfully")];
							} else {
								return ['message' => _("Sorry, unable to find any core devices"), 'status' => false];
							}
						},
					],
					'fetchCoreDevice' => [
						'type' => $this->typeContainer->get('coredevice')->getObject(),
						'description' => _('Fetchs particular core device'),
						'args' => [
							'device_id' => [
								'type' => Type::id(),
								'description' => _('The Device ID'),
							]
						],
						'resolve' => function ($root, $args) {
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
					if (isset($row['id'])) {
						return $row['id'];
					} elseif (isset($row['response'])) {
						return  $row['response']['id'];
					}
					return null;
				}),
				'deviceId' => [
					'type' => Type::nonNull(Type::string()),
					'description' => _('Give your device a unique integer ID. The device will use this ID to authenticate to the system'),
					'resolve' => function($row) {
						if (isset($row['id'])) {
							return $row['id'];
						} elseif (isset($row['response'])) {
							return  $row['response']['id'];
						}
						return null;
					}
				],
				'tech' => [
					'type' => Type::nonNull(Type::string()),
					'description' => _('Technology driver type'),
					'resolve' => function ($row) {
						if (isset($row['tech'])) {
							return $row['tech'];
						} elseif (isset($row['response'])) {
							return  $row['response']['tech'];
						}
						return null;
					}
				],
				'dial' => [
					'type' => Type::nonNull(Type::string()),
					'description' => _('How to dial this device, this should not be changed unless you know what you are doing.'),
					'resolve' => function ($row) {
						if (isset($row['dial'])) {
							return $row['dial'];
						} elseif (isset($row['response'])) {
							return  $row['response']['dial'];
						}
						return null;
					}
				],
				'devicetype' => [
					'type' => Type::nonNull(Type::string()),
					'description' => _('Devices can be fixed or adhoc. Fixed devices are always associated to the same extension/user. Adhoc devices can be logged into and logged out of by users.'),
					'resolve' => function ($row) {
						if (isset($row['devicetype'])) {
							return $row['devicetype'];
						} elseif (isset($row['response'])) {
							return  $row['response']['devicetype'];
						}
						return null;
					}
				],
				'user' => [
					'type' => $this->typeContainer->get('coreuser')->getObject(),
					'description' => _('Fixed devices will always mapped to this user. Adhoc devices will be mapped to this user by default.'),
					'resolve' => function ($row) {
						$item = $this->freepbx->Core->getUser($row['user']);
						return isset($item) ? $item : null;
					}
				],
				'description' => [
					'type' => Type::string(),
					'description' => _('The CallerID name for this device will be set to this description until it is logged into.'),
					'resolve' => function ($row) {
						if (isset($row['description'])) {
							return $row['description'];
						} elseif (isset($row['response'])) {
							return  $row['response']['description'];
						}
						return null;
					}
				],
				'emergencyCid' => [
					'type' => Type::string(),
					'description' => _('This CallerID will always be set when dialing out an Outbound Route flagged as Emergency. The Emergency CID overrides all other CallerID settings.'),
					'resolve' => function ($row) {
						if (isset($row['emergency_cid'])) {
							return $row['emergency_cid'];
						} elseif (isset($row['response'])) {
							return  $row['response']['emergency_cid'];
						}
						return null;
					}
				],
				'coreDevice' => [
					'type' => Type::listOf($this->typeContainer->get('coredevice')->getObject()),
					'resolve' => function ($root, $args) {
						$data = array_map(function ($row) {
							return $row['node'];
						}, $root['response']['edges']);
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
				'coreDevice' => [
					'type' => Type::listOf($this->typeContainer->get('coredevice')->getObject()),
					'resolve' => function ($root, $args) {
						$data = array_map(function ($row) {
							return $row['node'];
						}, $root['response']['edges']);
						return $data;
					}
				],
				'status' => [
					'type' => Type::boolean(),
					'description' => _('Status of the request'),
					'resolve' => function ($payload) {
						return $payload['status'];
					}
				],
				'message' => [
					'type' => Type::string(),
					'description' => _('Message of the request'),
					'resolve' => function ($payload) {
						return $payload['message'];
					}
				],
			];
		});
	}

	private function getMutationFields() {
		return [
			'id' => [
				'type' => Type::nonNull(Type::id()),
				'description' => _('Give your device a unique integer ID. The device will use this ID to authenticate to the system')
			],
			'tech' => [
				'type' => Type::nonNull(Type::string()),
				'description' => _('Technology driver type')
			],
			'dial' => [
				'type' => Type::nonNull(Type::string()),
				'description' => _('How to dial this device, this should not be changed unless you know what you are doing.')
			],
			'devicetype' => [
				'type' => Type::nonNull(Type::string()),
				'description' => _('Devices can be fixed or adhoc. Fixed devices are always associated to the same extension/user. Adhoc devices can be logged into and logged out of by users.')
			],
			'user' => [
				'type' => Type::string(),
				'description' => _('Fixed devices will always mapped to this user. Adhoc devices will be mapped to this user by default.')
			],
			'description' => [
				'type' => Type::string(),
				'description' => _('The CallerID name for this device will be set to this description until it is logged into.')
			],
			'emergency_cid' => [
				'type' => Type::string(),
				'description' => _('This CallerID will always be set when dialing out an Outbound Route flagged as Emergency. The Emergency CID overrides all other CallerID settings.')
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
	
	/**
	 * getOutputFields
	 *
	 * @return void
	 */
	public function getOutputFields(){
		return [
			'status' => [
				'type' => Type::boolean(),
				'description' => _('Status of the request'),
			],
			'message' => [
				'type' => Type::string(),
				'description' => _('Message of the request'),
			],
			'coreDevice' => [
				'type' => $this->typeContainer->get('coredevice')->getObject(),
				'resolve' => function ($row) {
					if (isset($row['response'])) {
						return $row['response'];
					} elseif (isset($row)) {
						return  $row;
					}
					return null;
				}
			]
		];
	}

	public function deleteCoreDeviceOutputFields(){
		return [
			'deletedId' => [
				'type' => Type::nonNull(Type::id()),
				'resolve' => function ($payload) {
					return $payload['id'];
				}
			],
			'status' => [
				'type' => Type::boolean(),
				'description' => _('Status of the request'),
			],
			'message' => [
				'type' => Type::string(),
				'description' => _('Message of the request'),
			],
		];
	}
}

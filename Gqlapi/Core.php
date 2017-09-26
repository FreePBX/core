<?php

namespace FreePBX\modules\Core\Gqlapi;

use GraphQL\Type\Definition\Type;
use FreePBX\modules\Gqlapi\includes\Base;

class Core extends Base {
	public function constructQuery() {
		/*
		$deviceTypeEdge = new ObjectType([
			'name' => 'Edge',
			'fields' => [
				'node' => [
					'type' => $deviceType,
					'resolve' => function($nodes) {
						return $nodes;
					}
				],
				'cursor' => [
					'type' => Type::string(),
					'resolve' => function($node) {
						return $node['cursor'];
					}
				]
			]
		]);

		$pageInfo = new ObjectType([
			'name' => 'PageInfo',
			'fields' => [
				'startCursor' => [
					'type' => Type::string(),
					'resolve' => function($nodes) {
						return base64_encode('cursor'.(count($nodes)-1));
					}
				],
				'endCursor' => [
					'type' => Type::string(),
					'resolve' => function($nodes) {
						return base64_encode('cursor0');
					}
				],
				'hasNextPage' => [
					'type' => Type::boolean(),
					'resolve' => function($nodes) {
						return false;
					}
				]
			]
		]);

		$Connection = new ObjectType([
			'name' => 'Connection',
			'fields' => [
				'edges' => [
					'type' => Type::listOf($deviceTypeEdge),
					'resolve' => function($nodes) {
						return $nodes;
					}
				],
				'totalCount' => [
					'type' => Type::int(),
					'resolve' => function($nodes) {
						return count($nodes);
					}
				],
				'pageInfo' => [
					'type' => $pageInfo,
					'resolve' => function($devices) {
						return $devices;
					}
				]
			]
		]);
		*/


		$user = $this->typeContainer->get('device');
		$user->addField('user',[
			'type' => 'objectReference-user',
			'resolve' => function($device) {
				return $this->freepbx->Core->getUser($device['user']);
			}
		]);

		return [
			'devices' => [
				'type' => $this->typeContainer->get('device')->getListReference(),
				'resolve' => function($root, $args) {
					return $this->freepbx->Core->getAllDevicesByType();
				}
			],
			'device' => [
				'type' => $this->typeContainer->get('device')->getReference(),
				'args' => [
					'id' => [
						'type' => Type::int(),
						'description' => 'Device ID',
					]
				],
				'resolve' => function($root, $args) {
					return $this->freepbx->Core->getDevice($args['id']);
				}
			],
			'users' => [
				'type' => $this->typeContainer->get('user')->getListReference(),
				'resolve' => function($root, $args) {
					return $this->freepbx->Core->getAllUsers();
				}
			],
			'user' => [
				'type' => $this->typeContainer->get('user')->getReference(),
				'args' => [
					'id' => [
						'type' => Type::int(),
						'description' => 'Device ID',
					]
				],
				'resolve' => function($root, $args) {
					return $this->freepbx->Core->getUser($args['id']);
				}
			],
			'extensions' => [
				'type' => $this->typeContainer->get('extension')->getListReference(),
				'resolve' => function($root, $args) {
					return $this->freepbx->Core->getAllUsers();
				}
			],
			'extension' => [
				'type' => $this->typeContainer->get('extension')->getReference(),
				'args' => [
					'id' => [
						'type' => Type::int(),
						'description' => 'Device ID',
					]
				],
				'resolve' => function($root, $args) {
					return $this->freepbx->Core->getUser($args['id']);
				}
			],
			/*
			'devicesConnection' => [
				'type' => $Connection,
				'args' => [
					'id' => [
						'type' => Type::int(),
						'description' => 'Device ID',
					]
				],
				'resolve' => function($root, $args) {
					$devices = $this->freepbx->Core->getAllDevicesByType();
					$final = [];
					foreach($devices as $k => $d) {
						$final[$k] = $d;
						$final[$k]['cursor'] = base64_encode('cursor'.$k);
					}
					return $final;
				}
			]
			*/
		];
	}

	public function postInitReferences() {
		$user = $this->typeContainer->get('user');
		$user->addFields([
			'noanswer_dest' => [
				'type' => $this->typeContainer->get('destination')->getReference()
			],
			'busy_dest' => [
				'type' => $this->typeContainer->get('destination')->getReference()
			],
			'chanunavail_dest' => [
				'type' => $this->typeContainer->get('destination')->getReference()
			]
		]);
	}

	public function initReferences() {
		$this->getUserType();
		$this->getDeviceType();
		$this->getExtensionType();
	}

	private function getUserType() {
		$user = $this->typeContainer->get('user');
		$user->addFields([
			'extension' => [
				'type' => Type::id()
			],
			'password' => [
				'type' => Type::string()
			],
			'name' => [
				'type' => Type::string()
			],
			'voicemail' => [
				'type' => Type::string()
			],
			'ringtimer' => [
				'type' => Type::int()
			],
			'noanswer' => [
				'type' => Type::string()
			],
			'recording' => [
				'type' => Type::string()
			],
			'outboundcid' => [
				'type' => Type::string()
			],
			'sipname' => [
				'type' => Type::string()
			],
			'noanswer_cid' => [
				'type' => Type::string()
			],
			'busy_cid' => [
				'type' => Type::string()
			],
			'chanunavail_cid' => [
				'type' => Type::string()
			],
			'mohclass' => [
				'type' => Type::string()
			]
		]);
	}

	private function getDeviceType() {
		$device = $this->typeContainer->get('device');
		$device->addFields([
				'id' => [
					'type' => Type::id()
				],
				'tech' => [
					'type' => Type::string()
				],
				'dial' => [
					'type' => Type::string()
				],
				'type' => [
					'type' => Type::string()
				],
				'description' => [
					'type' => Type::string()
				],
				'emergency_cid' => [
					'type' => Type::string()
				]
			]);
	}

	private function getExtensionType() {
		$user = $this->typeContainer->get('extension');
		$user->addFields([
			'device' => [
				'type' => $this->typeContainer->get('device')->getReference(),
				'resolve' => function($value) {
					return $this->freepbx->Core->getDevice($value['extension']);;
				}
			],
			'user' => [
				'type' => $this->typeContainer->get('user')->getReference(),
				'resolve' => function($value) {
					return $this->freepbx->Core->getUser($value['extension']);
				}
			],
		]);
	}
}

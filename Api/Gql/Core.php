<?php

namespace FreePBX\modules\Core\Api\Gql;

use GraphQL\Type\Definition\Type;
use FreePBX\modules\Api\Gql\Base;

class Core extends Base {
	/*
	protected $module = 'core';
	public static function getScopes() {
		return [
			'read:user' => [
				'description' => _('Read User (User/Device Mode) Information'),
			],
			'read:device' => [
				'description' => _('Read Device (User/Device Mode) Information'),
			],
			'read:extension' => [
				'description' => _('Read Extension Information'),
			],
			'read:advancedsettings' => [
				'description' => _('Read Advanced Settings'),
			],
			'read:asteriskmodules' => [
				'description' => _('Read Asterisk Modules'),
			],
			'read:did' => [
				'description' => _('Read Inbound Route Information'),
			],
			'read:routing' => [
				'description' => _('Read Outbound Route Information'),
			]
		];
	}

	public function constructMutation() {
		$mutation = [];
		if($this->checkWriteScope("device")) {
			$mutation = [
				'createDevice' => [
					'type' => $this->typeContainer->get('device')->getReference(),
					'args' => [
						'id' => [
							'type' => Type::int()
						]
					],
					'resolve' => function ($root, $args) {
						return $this->freepbx->Core->getDevice($args['id']);
					},
				],
			];
		}
		return $mutation;
	}

	public function constructQuery() {
		$query = [];
		if($this->checkReadScope("user")) {
			$user = $this->typeContainer->get('device');
			$user->addField('user',[
				'type' => $this->typeContainer->get('user')->getReference(),
				'resolve' => function($device) {
					return $this->freepbx->Core->getUser($device['user']);
				}
			]);

			$query['users'] = [
				'type' => $this->typeContainer->get('user')->getListReference(),
				'resolve' => function($root, $args) {
					return $this->freepbx->Core->getAllUsers();
				}
			];
			$query['user'] = [
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
			];
		}

		if($this->checkReadScope("device")) {
			$query['devices'] = [
				'type' => $this->typeContainer->get('device')->getListReference(),
				'resolve' => function($root, $args) {
					return $this->freepbx->Core->getAllDevicesByType();
				}
			];
			$query['device'] = [
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
			];
		}

		if($this->checkReadScope("extension")) {
			$query['extensions'] = [
				'type' => $this->typeContainer->get('extension')->getListReference(),
				'resolve' => function($root, $args) {
					return $this->freepbx->Core->getAllUsers();
				}
			];
			$query['extension'] = [
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
			];
		}

		return $query;
	}

	public function postInitTypes() {
		$destinations = $this->typeContainer->get('destination');
		$destinations->addType($this->typeContainer->get('user')->getReference());
		//$destinations->addType($this->typeContainer->get('device')->getReference());
		//$destinations->addType($this->typeContainer->get('extension')->getReference());
	}

	public function initTypes() {
		$this->getUserType();
		$this->getDeviceType();
		$this->getExtensionType();
		$this->getDeviceDrivers();
	}

	private function getUserType() {
		$user = $this->typeContainer->create('user');
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
			],
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

	private function getDeviceType() {
		$device = $this->typeContainer->create('device');
		$device->addFields([
				'id' => [
					'type' => Type::id()
				],
				'tech' => [
					'type' => $this->typeContainer->get('tech_driver')->getReference(),
					'resolve' => function($row) {
						$dc = $this->freepbx->Core->getDriver($row['tech']);
						$out = $dc->getDevice($row['id']);
						$out['tech'] = $row['tech'];
						return $out;
					}
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
		$user = $this->typeContainer->create('extension');
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

	private function getDeviceDrivers() {
		$drivers = $this->freepbx->Core->getAllDriversInfo();
		$dr = $this->typeContainer->get('tech_driver');
		$dr->addField('id', [
			'type' => Type::id(),
			'resolve' => function($row) {
				return $row['tech'];
			}
		]);
		$settings = [];
		foreach($drivers as $driver) {
			$dc = $this->freepbx->Core->getDriver($driver['rawName']);
			$flag = 1;
			$settings = $dc->getDefaultDeviceSettings(0, '', $flag);
			if(is_array($settings)) {
				foreach($settings['settings'] as $keyword => $setting) {
					$dr->addField($keyword, [
						'type' => Type::string()
					]);
				}
			}
		}
	}
	*/
}

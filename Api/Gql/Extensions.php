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
					'description' => 'Give your device a unique integer ID. The device will use this ID to authenticate to the system',
					'resolve' => function($row) {
						return isset($row['id']) ? $row['id'] : null;
					}
				],
				'user' => [
					'type' => $this->typeContainer->get('coreuser')->getObject(),
					'description' => 'Fixed devices will always mapped to this user. Adhoc devices will be mapped to this user by default.',
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
					'description' => 'Fixed devices will always mapped to this user. Adhoc devices will be mapped to this user by default.',
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
}

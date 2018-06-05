<?php

namespace FreePBX\modules\Core\Api\Gql;

use GraphQL\Type\Definition\Type;
use FreePBX\modules\Api\Gql\Base;

class Trunks extends Base {
	/*
	protected $module = 'core';
	public static function getScopes() {
		return [
			'read:trunk' => [
				'description' => _('Read Trunk Information'),
			]
		];
	}

	public function constructQuery() {
		$query = [];
		if($this->checkReadScope("trunk")) {
			$trunk = $this->typeContainer->get('trunk');
			$query['trunks'] = [
				'type' => $this->typeContainer->get('trunk')->getListReference(),
				'resolve' => function($root, $args) {
					return $this->freepbx->Core->listTrunks();
				}
			];
			$query['trunk'] = [
				'type' => $this->typeContainer->get('trunk')->getReference(),
				'args' => [
					'id' => [
						'type' => Type::int(),
						'description' => 'Trunk ID',
					]
				],
				'resolve' => function($root, $args) {
					return $this->freepbx->Core->getTrunkByID($args['id']);
				}
			];
		}
		return $query;
	}

	public function postInitTypes() {
		$destinations = $this->typeContainer->get('destination');
		$destinations->addType($this->typeContainer->get('trunk')->getReference());
	}

	public function initTypes() {
		$trunk = $this->typeContainer->create('trunk');
		$trunk->setDescription('Where you control connectivity to the PSTN and your VoIP provider(s)');
		$trunk->addFields([
			'id' => [
				'type' => Type::id(),
				'resolve' => function($row) {
					return isset($row['trunkid']) ? $row['trunkid'] : null;
				}
			],
			'name' => [
				'type' => Type::string()
			],
			'tech' => [
				'type' => Type::string()
			],
			'outcid' => [
				'type' => Type::string()
			],
			'maxchans' => [
				'type' => Type::string()
			],
			'failscript' => [
				'type' => Type::string()
			],
			'dialoutprefix' => [
				'type' => Type::string()
			],
			'usercontext' => [
				'type' => Type::string()
			],
			'provider' => [
				'type' => Type::string()
			],
			'keepcid' => [
				'type' => Type::boolean(),
				'resolve' => function($row) {
					return $row['keepcid'] === 'on';
				}
			],
			'disabled' => [
				'type' => Type::boolean(),
				'resolve' => function($row) {
					return $row['disabled'] === 'on';
				}
			],
			'continue' => [
				'type' => Type::boolean(),
				'resolve' => function($row) {
					return $row['continue'] === 'on';
				}
			],
		]);
	}
	*/
}

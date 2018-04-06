<?php

namespace FreePBX\modules\Core\Api\Gql;

use GraphQL\Type\Definition\Type;
use FreePBX\modules\Api\Gql\Base;

class Dids extends Base {
	protected $module = 'core';
	public static function getScopes() {
		return [
			'read:did' => [
				'description' => _('Read Inbound Routes'),
			]
		];
	}

	public function constructQuery() {
		$query = [];
		if($this->checkReadScope("did")) {
			$did = $this->typeContainer->get('did');
			$query['dids'] = [
				'type' => $this->typeContainer->get('did')->getListReference(),
				'resolve' => function($root, $args) {
					return $this->freepbx->Core->getAllDIDs();
				}
			];
			$query['did'] = [
				'type' => $this->typeContainer->get('did')->getReference(),
				'args' => [
					'id' => [
						'type' => Type::int(),
						'description' => 'DID ID',
					]
				],
				'resolve' => function($root, $args) {
					return $this->freepbx->Core->getTrunkByID($args['id']);
				}
			];
		}
		return $query;
	}

	public function initReferences() {
		$did = $this->typeContainer->get('did');
		$did->addFields([
			'id' => [
				'type' => Type::id(),
				'resolve' => function($row) {
					return $row['extension']."/".$row['cidnum'];
				}
			],
			'cidnum' => [
				'type' => Type::string()
			],
			'extension' => [
				'type' => Type::string()
			],
			'destination' => [
				'type' => $this->typeContainer->get('destination')->getReference()
			],
			'privacyman' => [
				'type' => Type::boolean(),
				'resolve' => function($row) {
					return $row['privacyman'] === '1';
				}
			],
			'alertinfo' => [
				'type' => Type::string()
			],
			'ringing' => [
				'type' => Type::boolean(),
				'resolve' => function($row) {
					return $row['ringing'] === 'CHECKED';
				}
			],
			'mohclass' => [
				'type' => $this->typeContainer->get('music')->getReference()
			],
			'description' => [
				'type' => Type::string(),
				'description' => 'Provide a meaningful description of what this incoming route is'
			],
			'delay_answer' => [
				'type' => Type::int()
			],
			'pricid' => [
				'type' => Type::boolean(),
				'resolve' => function($row) {
					return $row['pricid'] === 'CHECKED';
				}
			],
			'pmmaxretries' => [
				'type' => Type::int()
			],
			'pmminlength' => [
				'type' => Type::int()
			],
			'reversal' => [
				'type' => Type::boolean(),
				'resolve' => function($row) {
					return $row['reversal'] === 'CHECKED';
				}
			],
			'rvolume' => [
				'type' => Type::int()
			]
		]);
	}
}

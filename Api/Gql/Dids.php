<?php

namespace FreePBX\modules\Core\Api\Gql;

use GraphQLRelay\Relay;
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
		if($this->checkReadScope("did")) {
			return [
				'allInboundRoutes' => [
					'type' => $this->typeContainer->get('inboundRoute')->getConnectionReference(),
					'description' => 'Inbound Routes',
					'args' => Relay::connectionArgs(),
					'resolve' => function($root, $args) {
						return Relay::connectionFromArray($this->freepbx->Core->getAllDIDs(), $args);
					},
				],
				'inboundRoute' => [
					'type' => $this->typeContainer->get('inboundRoute')->getReference(),
					'args' => [
						'id' => [
							'type' => Type::int(),
							'description' => 'DID ID',
						]
					],
					'resolve' => function($root, $args) {
						return $this->freepbx->Core->getTrunkByID($args['id']);
					}
				]
			];
		}
	}

	public function postInitTypes() {
		$destinations = $this->typeContainer->get('destination');
		$destinations->addType($this->typeContainer->get('inboundRoute')->getReference());
	}

	public function initTypes() {
		$did = $this->typeContainer->create('inboundRoute');
		$did->setDescription('Inbound Routes');

		$did->addInterfaceCallback(function() {
			return [$this->getNodeDefinition()['nodeInterface']];
		});

		$did->setGetNodeCallback(function($id) {
			$list = $this->freepbx->Blacklist->getBlacklist();
			$item = array_search($id, array_column($list, 'number'));
			return isset($list[$item]) ? $list[$item] : null;
		});

		$did->addFieldCallback(function() {
			return [
				'id' => Relay::globalIdField('inboundRoutes', function($row) {
					return $row['extension']."/".$row['cidnum'];
				}),
				'cidnum' => [
					'type' => Type::string(),
					"description" => "The CallerID Number to be matched on incoming calls"
				],
				'extension' => [
					'type' => Type::string(),
					"description" => 'The expected DID Number if your trunk passes DID on incoming calls'
				],
				'destination' => [
					'type' => $this->typeContainer->get('destination')->getObject(),
					'resolve' => function($row) {
						$info = $this->freepbx->Destinations->getDestination($row['destination']);
						return !empty($info['data']) ? $info['data'] : ['gqltype' => 'invaliddestination', 'id' => $row['destination'], 'description' => ''];
					},
					'description' => 'Destination for route'
				],
				'privacyman' => [
					'type' => Type::boolean(),
					'resolve' => function($row) {
						return $row['privacyman'] === '1';
					},
					'description' => 'If no CallerID has been received, Privacy Manager will ask the caller to enter their phone number. If an user/extension has Call Screening enabled, the incoming caller will be be prompted to say their name when the call reaches the user/extension'
				],
				'alertinfo' => [
					'type' => Type::string(),
					'description' => 'Alert Info can be used for distinctive ring with SIP devices'
				],
				'ringing' => [
					'type' => Type::boolean(),
					'resolve' => function($row) {
						return $row['ringing'] === 'CHECKED';
					},
					'description' => "Some devices or providers require RINGING to be sent before ANSWER. You'll notice this happening if you can send calls directly to a phone, but if you send it to an IVR, it won't connect the call"
				],
				'mohclass' => [
					'type' => $this->typeContainer->get('musiconhold')->getObject(),
					'description' => 'MoH class that will be used for calls that come in on this route'
				],
				'description' => [
					'type' => Type::string(),
					'description' => 'A meaningful description of what this incoming route is'
				],
				'delay_answer' => [
					'type' => Type::int(),
					'An optional delay to wait before processing this route. Setting this value will delay the channel from answering the call. This may be handy if external fax equipment or security systems are installed in parallel and you would like them to be able to seize the line'
				],
				'pricid' => [
					'type' => Type::boolean(),
					'resolve' => function($row) {
						return $row['pricid'] === 'CHECKED';
					},
					'description' => 'This effects CID ONLY routes where no DID is specified. If checked, calls with this CID will be routed to this route, even if there is a route to the DID that was called. Normal behavior is for the DID route to take the calls. If there is a specific DID/CID route for this CID, that route will still take the call when that DID is called'
				],
				'pmmaxretries' => [
					'type' => Type::int(),
					'description' => 'Number of attempts the caller has to enter a valid CallerID'
				],
				'pmminlength' => [
					'type' => Type::int(),
					'description' => 'Minimum amount of digits CallerID needs to contain in order to be considered valid'
				],
				'reversal' => [
					'type' => Type::boolean(),
					'resolve' => function($row) {
						return $row['reversal'] === 'CHECKED';
					},
					'description' => 'On PRI channels the carrier will send a signal if the caller indicates a billing reversal. When checked this route will reject calls that indicate a billing reversal if supported'
				],
				'rvolume' => [
					'type' => Type::int(),
					'description' => 'Override the ringer volume. Note: This is only valid for Sangoma phones at this time'
				],
				'fwanswer' => [
					'type' => Type::boolean(),
					'resolve' => function($row) {
						return $row['reversal'] === 'CHECKED';
					},
					'description' => 'Set to Yes to force the call to be answered at this time'
				]
			];
		});

		$did->setConnectionResolveNode(function ($edge) {
			return $edge['node'];
		});

		$did->setConnectionFields(function() {
			return [
				'totalCount' => [
					'type' => Type::int(),
					'resolve' => function($value) {
						return count($this->freepbx->Core->getAllDIDs());
					}
				],
				'inboundRoutes' => [
					'type' => Type::listOf($this->typeContainer->get('inboundRoute')->getObject()),
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

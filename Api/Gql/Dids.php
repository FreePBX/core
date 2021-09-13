<?php

namespace FreePBX\modules\Core\Api\Gql;

use GraphQLRelay\Relay;
use GraphQL\Type\Definition\Type;
use FreePBX\modules\Api\Gql\Base;

class Dids extends Base {
	protected $module = 'core';
	protected $description = 'Used to tell your PBX where to route inbound calls based on the phone number or DID dialed';

	public static function getScopes() {
		return [
			'read:did' => [
				'description' => _('Read Inbound Routes'),
			],
			'write:did' => [
				'description' => _('Write Inbound Routes'),
			]
		];
	}

	public function mutationCallback() {
		if($this->checkWriteScope("did")) {
			return function() {
				return [
					'addInboundRoute' => Relay::mutationWithClientMutationId([
						'name' => 'addInboundRoute',
						'description' => 'Add a new inbound route to the system',
						'inputFields' => [
							'extension' => [
								'type' => Type::nonNull(Type::string()),
								'description' => 'Define the expected DID Number if your trunk passes DID on incoming calls.'
							],
							'cidnum' => [
								'type' => Type::string(),
								'description' => 'Define the CallerID Number to be matched on incoming calls.'
							],
							'description' => [
								'type' => Type::string(),
								'description' => 'Provide a meaningful description of what this incoming route is'
							],
							'privacyman' => [
								'type' => Type::boolean(),
								'description' => 'If no CallerID has been received, Privacy Manager will ask the caller to enter their phone number. If an user/extension has Call Screening enabled, the incoming caller will be prompted to say their name when the call reaches the user/extension.'
							],
							'alertinfo' => [
								'type' => Type::string(),
								'description' => 'ALERT_INFO can be used for distinctive ring with SIP devices.'
							],
							'ringing' => [
								'type' => Type::boolean(),
								'description' => "Some devices or providers require RINGING to be sent before ANSWER. You'll notice this happening if you can send calls directly to a phone, but if you send it to an IVR, it won't connect the call."
							],
							'mohclass' => [
								'type' => Type::string(),
								'description' => 'Set the MoH class that will be used for calls that come in on this route. For example, choose a type appropriate for routes coming in from a country which may have announcements in their language.'
							],
							'grppre' => [
								'type' => Type::string(),
								'description' => 'CID name prefix'
							],
							'delay_answer' => [
								'type' => Type::int(),
								'description' => 'An optional delay to wait before processing this route. Setting this value will delay the channel from answering the call. This may be handy if external fax equipment or security systems are installed in parallel and you would like them to be able to seize the line.'
							],
							'pricid' => [
								'type' => Type::boolean(),
								'description' => 'This effects CID ONLY routes where no DID is specified. If checked, calls with this CID will be routed to this route, even if there is a route to the DID that was called. Normal behavior is for the DID route to take the calls. If there is a specific DID/CID route for this CID, that route will still take the call when that DID is called.'
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
								'description' => 'On PRI channels the carrier will send a signal if the caller indicates a billing reversal. When checked this route will reject calls that indicate a billing reversal if supported'
							],
							'rvolume' => [
								'type' => Type::int(),
								'description' => 'Override the ringer volume. Note: This is only valid for Sangoma phones at this time'
							],
							'fanswer' => [
								'type' => Type::boolean(),
								'description' => 'Set to Yes to force the call to be answered at this time'
							],
							'destination' => [
								'type' => Type::nonNull(Type::string()),
								'description' => 'Destination for route'
							]
						],
						'outputFields' => [
							'inboundRoute' => [
								'type' => $this->typeContainer->get('did')->getObject(),
								'resolve' => function ($payload) {
									return $payload;
								}
							]
						],
						'mutateAndGetPayload' => function ($input) {
							$defaults = [];
							$this->freepbx->Core->addDIDDefaults($defaults);
							foreach($defaults as $key => $value) {
								if(!isset($input[$key])) {
									$input[$key] = $value;
								}
							}
							$output = array_merge($defaults, $input);
							$id = $this->freepbx->Core->addDID($output);
							return $this->freepbx->Core->getDID($input['extension'], $input['cidnum']);
						}
					]),
					'updateInboundRoute' => Relay::mutationWithClientMutationId([
						'name' => 'updateInboundRoute',
						'description' => 'Update an inbound route on the system',
						'inputFields' => [
							'extension' => [
								'type' => Type::nonNull(Type::string()),
								'description' => 'Define the expected DID Number if your trunk passes DID on incoming calls.'
							],
							'cidnum' => [
								'type' => Type::string(),
								'description' => 'Define the CallerID Number to be matched on incoming calls.'
							],
							'description' => [
								'type' => Type::string(),
								'description' => 'Provide a meaningful description of what this incoming route is'
							],
							'privacyman' => [
								'type' => Type::boolean(),
								'description' => 'If no CallerID has been received, Privacy Manager will ask the caller to enter their phone number. If an user/extension has Call Screening enabled, the incoming caller will be prompted to say their name when the call reaches the user/extension.'
							],
							'alertinfo' => [
								'type' => Type::string(),
								'description' => 'ALERT_INFO can be used for distinctive ring with SIP devices.'
							],
							'ringing' => [
								'type' => Type::boolean(),
								'description' => "Some devices or providers require RINGING to be sent before ANSWER. You'll notice this happening if you can send calls directly to a phone, but if you send it to an IVR, it won't connect the call."
							],
							'mohclass' => [
								'type' => Type::string(),
								'description' => 'Set the MoH class that will be used for calls that come in on this route. For example, choose a type appropriate for routes coming in from a country which may have announcements in their language.'
							],
							'grppre' => [
								'type' => Type::string(),
								'description' => 'CID name prefix'
							],
							'delay_answer' => [
								'type' => Type::int(),
								'description' => 'An optional delay to wait before processing this route. Setting this value will delay the channel from answering the call. This may be handy if external fax equipment or security systems are installed in parallel and you would like them to be able to seize the line.'
							],
							'pricid' => [
								'type' => Type::boolean(),
								'description' => 'This effects CID ONLY routes where no DID is specified. If checked, calls with this CID will be routed to this route, even if there is a route to the DID that was called. Normal behavior is for the DID route to take the calls. If there is a specific DID/CID route for this CID, that route will still take the call when that DID is called.'
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
								'description' => 'On PRI channels the carrier will send a signal if the caller indicates a billing reversal. When checked this route will reject calls that indicate a billing reversal if supported'
							],
							'rvolume' => [
								'type' => Type::int(),
								'description' => 'Override the ringer volume. Note: This is only valid for Sangoma phones at this time'
							],
							'fanswer' => [
								'type' => Type::boolean(),
								'description' => 'Set to Yes to force the call to be answered at this time'
							],
							'destination' => [
								'type' => Type::nonNull(Type::string()),
								'description' => 'Destination for route'
							]
						],
						'outputFields' => [
							'inboundRoute' => [
								'type' => $this->typeContainer->get('did')->getObject(),
								'resolve' => function ($payload) {
									return $payload;
								}
							]
						],
						'mutateAndGetPayload' => function ($input) {
							$defaults = [];
							$this->freepbx->Core->addDIDDefaults($defaults);
							foreach($defaults as $key => $value) {
								if(!isset($input[$key])) {
									$input[$key] = $value;
								}
							}

							$this->freepbx->Core->editDID($input['extension'], $input['cidnum'], $input);
							return $this->freepbx->Core->getDID($input['extension'], $input['cidnum']);
						}
					]),
					'removeInboundRoute' => Relay::mutationWithClientMutationId([
						'name' => 'removeInboundRoute',
						'description' => 'Remove an inbound route from the system',
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
							],
							'status' =>[
								'type' => Type::boolean(),
								'description' => _('Status of the request'),
							],
							'message' =>[
								'type' => Type::String(),
								'description' => _('Message for the request')
							],
						],
						'mutateAndGetPayload' => function ($input) {
							$parts = explode("/",$input['id']);
							$extension = $parts[0];
							$cidnum = isset($parts[1]) ? $parts[1] : '';
							$didInfo = $this->freepbx->Core->getDID($extension, $cidnum);
							if($didInfo){
								$this->freepbx->Core->delDID($extension,$cidnum);
								return ['id' => $input['id'],'message' => _("Inbound Route deleted successfully"), 'status' => true];
							}else{
								return ['id' => $input['id'],'message' => _("Inbound Route not found"), 'status' => false];
							}
						}
					])
				];
			};
		}
	}

	public function queryCallback() {
		if($this->checkReadScope("did")) {
			return function() {
				return [
					'allInboundRoutes' => [
						'type' => $this->typeContainer->get('did')->getConnectionType(),
						'description' => $this->description,
						'args' => Relay::connectionArgs(),
						'resolve' => function($root, $args) {
							return Relay::connectionFromArray($this->freepbx->Core->getAllDIDs(), $args);
						},
					],
					'inboundRoute' => [
						'type' => $this->typeContainer->get('did')->getObject(),
						'description' => $this->description,
						'args' => [
							'id' => [
								'type' => Type::nonNull(Type::id()),
								'description' => 'Inbound Route ID',
							]
						],
						'resolve' => function($root, $args) {
							$parts = explode("/",$args['id']);
							$extension = $parts[0];
							$cidnum = isset($parts[1]) ? $parts[1] : '';
							return $this->freepbx->Core->getDID($extension, $cidnum);
						}
					]
				];
			};
		}
	}

	public function postInitializeTypes() {
		$destinations = $this->typeContainer->get('destination');
		$destinations->addTypeCallback(function() {
			return [
				$this->typeContainer->get('did')->getObject()
			];
		});

		$destinations->addResolveTypeCallback(function($value, $context, $info) {
			if (is_array($value) && $value['graphqlType'] == 'did') {
				return $this->typeContainer->get('did')->getObject();
			}
		});

		$destinations->addResolveValueCallback(function($value) {
			if (substr(trim($value),0,10) == 'from-trunk') {
				$exten = explode(',',$value);
				$out = $this->freepbx->Core->getDID($exten[1], '');
				if(!empty($out)) {
					return array_merge($out,['graphqlType' => 'did']);
				}
			}
		});
	}

	public function initializeTypes() {
		$user = $this->typeContainer->create('did');
		$user->setDescription($this->description);

		$user->addInterfaceCallback(function() {
			return [$this->getNodeDefinition()['nodeInterface']];
		});

		$user->setGetNodeCallback(function($id) {
			$parts = explode("/",$id);
			$extension = $parts[0];
			$cidnum = isset($parts[1]) ? $parts[1] : '';
			$item = $this->freepbx->Core->getDID($extension, $cidnum);
			return !empty($item) ? $item : null;
		});

		$user->addFieldCallback(function() {
			return [
				'id' => [
					'type' => Type::nonNull(Type::id()),
					'description' => 'Define ID of inbound route',
					'resolve' => function($row) {
						return $row['extension']."/".$row['cidnum'];
					}
				],
				'extension' => [
					'type' => Type::nonNull(Type::string()),
					'description' => 'Define the expected DID Number if your trunk passes DID on incoming calls.'
				],
				'cidnum' => [
					'type' => Type::string(),
					'description' => 'Define the CallerID Number to be matched on incoming calls.'
				],
				'description' => [
					'type' => Type::string(),
					'description' => 'Provide a meaningful description of what this incoming route is'
				],
				'privacyman' => [
					'type' => Type::boolean(),
					'description' => 'If no CallerID has been received, Privacy Manager will ask the caller to enter their phone number. If an user/extension has Call Screening enabled, the incoming caller will be prompted to say their name when the call reaches the user/extension.'
				],
				'alertinfo' => [
					'type' => Type::string(),
					'description' => 'ALERT_INFO can be used for distinctive ring with SIP devices.'
				],
				'ringing' => [
					'type' => Type::boolean(),
					'description' => "Some devices or providers require RINGING to be sent before ANSWER. You'll notice this happening if you can send calls directly to a phone, but if you send it to an IVR, it won't connect the call."
				],
				'mohclass' => [
					'type' => Type::string(),
					'description' => 'Set the MoH class that will be used for calls that come in on this route. For example, choose a type appropriate for routes coming in from a country which may have announcements in their language.'
				],
				'grppre' => [
					'type' => Type::string(),
					'description' => 'CID name prefix'
				],
				'delay_answer' => [
					'type' => Type::int(),
					'description' => 'An optional delay to wait before processing this route. Setting this value will delay the channel from answering the call. This may be handy if external fax equipment or security systems are installed in parallel and you would like them to be able to seize the line.'
				],
				'pricid' => [
					'type' => Type::boolean(),
					'description' => 'This effects CID ONLY routes where no DID is specified. If checked, calls with this CID will be routed to this route, even if there is a route to the DID that was called. Normal behavior is for the DID route to take the calls. If there is a specific DID/CID route for this CID, that route will still take the call when that DID is called.',
					'resolve' => function($row) {
						return ($row['pricid'] === 'CHECKED');
					}
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
					'description' => 'On PRI channels the carrier will send a signal if the caller indicates a billing reversal. When checked this route will reject calls that indicate a billing reversal if supported',
					'resolve' => function($row) {
						return ($row['reversal'] === 'CHECKED');
					}
				],
				'rvolume' => [
					'type' => Type::int(),
					'description' => 'Override the ringer volume. Note: This is only valid for Sangoma phones at this time'
				],
				'fanswer' => [
					'type' => Type::boolean(),
					'description' => 'Set to Yes to force the call to be answered at this time',
					'resolve' => function($row) {
						return ($row['fanswer'] === 'CHECKED');
					}
				],
				'destinationConnection' => [
					'type' => $this->typeContainer->get('destination')->getObject(),
					'description' => 'Destination for route',
					'resolve' => function($row) {
						return $this->typeContainer->get('destination')->resolveValue($row['destination']);
					}
				]
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
						return count($this->freepbx->core->getAllDIDs());
					}
				],
				'inboundRoutes' => [
					'type' => Type::listOf($this->typeContainer->get('did')->getObject()),
					'description' => $this->description,
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

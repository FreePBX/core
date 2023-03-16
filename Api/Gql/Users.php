<?php

namespace FreePBX\modules\Core\Api\Gql;

use GraphQLRelay\Relay;
use GraphQL\Type\Definition\Type;
use FreePBX\modules\Api\Gql\Base;

class Users extends Base {
	protected $module = 'core';

	public function mutationCallback() {
		if($this->checkAllWriteScope()) {
			return function() {
				return [
					'addCoreUser' => Relay::mutationWithClientMutationId([
						'name' => 'addCoreUser',
						'description' => 'Add a new entry to Core',
						'inputFields' => $this->getMutationFields(),
						'outputFields' => [
							'coreUser' => [
								'type' => $this->typeContainer->get('coreuser')->getObject(),
								'resolve' => function ($payload) {
									return count($payload) > 1 ? $payload : null;
								}
							]
						],
						'mutateAndGetPayload' => function ($input) {
							$output = $this->getMutationExecuteArray($input);
							$this->freepbx->Core->addUser($input['extension'], $output);
							$item = $this->freepbx->Core->getUser($input['extension']);
							return !empty($item) ? $item : [];
						}
					]),
					'updateCoreUser' => Relay::mutationWithClientMutationId([
						'name' => 'updateCoreUser',
						'description' => 'Update an entry in Core',
						'inputFields' => $this->getMutationFields(),
						'outputFields' => [
							'coreuser' => [
								'type' => $this->typeContainer->get('coreuser')->getObject(),
								'resolve' => function ($payload) {
									return count($payload) > 1 ? $payload : null;
								}
							]
						],
						'mutateAndGetPayload' => function ($input) {
							$output = $this->getMutationExecuteArray($input);
							$this->freepbx->Core->delUser($input['extension'], true);
							$this->freepbx->Core->addUser($input['extension'], $output, true);
							$item = $this->freepbx->Core->getUser($input['extension']);
							return !empty($item) ? $item : [];
						}
					]),
					'removeCoreUser' => Relay::mutationWithClientMutationId([
						'name' => 'removeCoreUser',
						'description' => 'Remove an entry from Core',
						'inputFields' => [
							'extension' => [
								'type' => Type::nonNull(Type::id())
							]
						],
						'outputFields' => [
							'deletedId' => [
								'type' => Type::nonNull(Type::id()),
								'resolve' => function ($payload) {
									return $payload['extension'];
								}
							]
						],
						'mutateAndGetPayload' => function ($input) {
							$this->freepbx->Core->delUser($input['extension']);
							return ['extension' => $input['extension']];
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
					'allCoreUsers' => [
						'type' => $this->typeContainer->get('coreuser')->getConnectionType(),
						'description' => '',
						'args' => Relay::connectionArgs(),
						'resolve' => function($root, $args) {
							return Relay::connectionFromArray($this->freepbx->Core->getAllUsers(), $args);
						},
					],
					'coreUser' => [
						'type' => $this->typeContainer->get('coreuser')->getObject(),
						'description' => '',
						'args' => [
							'id' => [
								'type' => Type::id(),
								'description' => 'The ID',
							]
						],
						'resolve' => function($root, $args) {
							return $this->getUser($args['id']);
						}
					]
				];
			};
		}
	}

	public function initializeTypes() {
		$user = $this->typeContainer->create('coreuser');
		$user->setDescription('');

		$user->addInterfaceCallback(function() {
			return [$this->getNodeDefinition()['nodeInterface']];
		});

		$user->setGetNodeCallback(function($id) {
			return $this->getSingleData($id);
		});

		$user->addFieldCallback(function() {
			return [
				'id' => Relay::globalIdField('coreuser', function($row) {
					return isset($row['extension']) ? $row['extension'] : null;
				}),
				'extension' => [
					'type' => Type::nonNull(Type::string()),
					'description' => 'The extension number to dial to reach this user.'
				],
				'password' => [
					'type' => Type::string(),
					'description' => 'A user will enter this password when logging onto a device',

				],
				'name' => [
					'type' => Type::string(),
					'description' => 'The CallerID name for calls from this user will be set to this name. Only enter the name, NOT the number.',

				],
				'voicemail' => [
					'type' => Type::string(),
					'description' => 'Voicemail context to use, \"novom\" is disabled',

				],
				'ringtimer' => [
					'type' => Type::int(),
					'description' => 'Number of seconds to ring prior to going to voicemail. Default will use the value set in Advanced Settings. If no voicemail is configured this will be ignored.',

				],
				'noanswer' => [
					'type' => Type::string(),
					'description' => 'Optional destination call is routed to when the call is not answered on an otherwise idle phone. If the phone is in use and the call is simply ignored, then the busy destination will be used'
				],
				'recording' => [
					'type' => Type::string(),
					'description' => '',

				],
				'outboundCid' => [
					'type' => Type::string(),
					'description' => _('Overrides the CallerID when dialing out a trunk. Any setting here will override the common outbound CallerID set in the Trunks admin.'),
					'resolve' => function($row) {
						return isset($row['outboundcid']) ? $row['outboundcid'] : null;
					}
				],
				'sipname' => [
					'type' => Type::string(),
					'description' => '',
				],
				'extPassword' =>  [
					'type' => Type::string(),
					'description' => _('Extension password'),
				],
				'noanswerCid' => [
					'type' => Type::string(),
					'description' => _('Optional CID Prefix to add before sending to this no answer destination'),
					'resolve' => function($row) {
						return isset($row['noanswer_cid']) ? $row['noanswer_cid'] : null;
					}
				],
				'busyCid' => [
					'type' =>Type::string(),
					'description' => _('Optional CID Prefix to add before sending to this busy destination.'),
					'resolve' => function($row) {
						return isset($row['busy_cid']) ? $row['busy_cid'] : null;
					}
				],
				'chanunavailCid' => [
					'type' => Type::string(),
					'description' => _('Optional CID Prefix to add before sending to this not reachable destination.'),
					'resolve' => function($row) {
						return isset($row['chanunavail_cid']) ? $row['chanunavail_cid'] : null;
					}
				],
				'noanswerDestination' => [
					'type' => Type::string(),
					'description' => _('Optional destination call is routed to when the call is not answered on an otherwise idle phone. If the phone is in use and the call is simply ignored, then the busy destination will be used'),
					'resolve' => function($row) {
						return isset($row['noanswer_dest']) ? $row['noanswer_dest'] : null;
					}
				],
				'busyDestination' => [
					'type' => Type::string(),
					'description' => _('Optional destination the call is routed to when the phone is busy or the call is rejected by the user. This destination is also used on an unanswered call if the phone is in use and the user chooses not to pickup the second call.'),
					'resolve' => function($row) {
						return isset($row['busy_dest']) ? $row['busy_dest'] : null;
					}
				],
				'chanunavailDestination' => [
					'type' => Type::string(),
					'description' => _('Optional destination the call is routed to when the phone is offline, such as a softphone currently off or a phone unplugged.'),
					'resolve' => function($row) {
						return isset($row['chanunavail_dest']) ? $row['chanunavail_dest'] : null;
					}
				],
				'mohclass' => [
					'type' => Type::string(),
					'description' => '',

				],
				'callwaiting' => [
					'type' => Type::string(),
					'description' => _('Call Waiting state for this user\'s extension')
				],
				'recording_in_external' => [
					'type' => Type::string(),
					'description' => _('Recording of inbound calls from external sources')
				],
				'recording_out_external' => [
					'type' => Type::string(),
					'description' => _('Recording of outbound calls to external sources')
				],
				'recording_in_internal' => [
					'type' => Type::string(),
					'description' => _('Recording of calls received from other extensions on the system')
				],
				'recording_out_internal' => [
					'type' => Type::string(),
					'description' => _('Recording of calls made to other extensions on the system')
				],
				'recording_ondemand' => [
					'type' => Type::string(),
					'description' => _('Enable or disable the ability to do on demand (one-touch) recording. The overall calling policy rules still apply and if calls are already being recorded by \'Force\' or \'Never\', they can not be paused unless \'Override\' is selected.')
				],
				'recording_priority' => [
					'type' => Type::int(),
					'description' => _('Call recording policy priority relative to other extensions when there is a conflict between an extension wanting recording and the other not wanting it. The higher of the two determines the policy, on a tie the global policy (caller or callee) determines the policy')
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
				'coreUser' => [
					'type' => Type::listOf($this->typeContainer->get('coreuser')->getObject()),
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
			'extension' => [
				'type' => Type::nonNull(Type::id()),
				'description' => 'The extension number to dial to reach this user.'
			],
			'password' => [
				'type' => Type::string(),
				'description' => 'A user will enter this password when logging onto a device'
			],
			'name' => [
				'type' => Type::string(),
				'description' => 'The CallerID name for calls from this user will be set to this name. Only enter the name, NOT the number.'
			],
			'voicemail' => [
				'type' => Type::string(),
				'description' => 'Voicemail context to use, "novom" is disabled'
			],
			'ringtimer' => [
				'type' => Type::int(),
				'description' => 'Number of seconds to ring prior to going to voicemail. Default will use the value set in Advanced Settings. If no voicemail is configured this will be ignored.'
			],
			'noanswer' => [
				'type' => Type::string(),
				'description' => 'Optional destination call is routed to when the call is not answered on an otherwise idle phone. If the phone is in use and the call is simply ignored, then the busy destination will be used'
			],
			'recording' => [
				'type' => Type::string(),
				'description' => ''
			],
			'outboundcid' => [
				'type' => Type::string(),
				'description' => 'Overrides the CallerID when dialing out a trunk. Any setting here will override the common outbound CallerID set in the Trunks admin.'
			],
			'sipname' => [
				'type' => Type::string(),
				'description' => ''
			],
			'noanswer_cid' => [
				'type' => Type::nonNull(Type::string()),
				'description' => 'Optional CID Prefix to add before sending to this no answer destination'
			],
			'busy_cid' => [
				'type' => Type::nonNull(Type::string()),
				'description' => 'Optional CID Prefix to add before sending to this busy destination.'
			],
			'chanunavail_cid' => [
				'type' => Type::nonNull(Type::string()),
				'description' => 'Optional CID Prefix to add before sending to this not reachable destination.'
			],
			'noanswer_dest' => [
				'type' => Type::nonNull(Type::string()),
				'description' => 'Optional destination call is routed to when the call is not answered on an otherwise idle phone. If the phone is in use and the call is simply ignored, then the busy destination will be used'
			],
			'busy_dest' => [
				'type' => Type::nonNull(Type::string()),
				'description' => 'Optional destination the call is routed to when the phone is busy or the call is rejected by the user. This destination is also used on an unanswered call if the phone is in use and the user chooses not to pickup the second call.'
			],
			'chanunavail_dest' => [
				'type' => Type::nonNull(Type::string()),
				'description' => 'Optional destination the call is routed to when the phone is offline, such as a softphone currently off or a phone unplugged.'
			],
			'mohclass' => [
				'type' => Type::string(),
				'description' => ''
			],
			'callwaiting' => [
				'type' => Type::string(),
				'description' => "Call Waiting option. default is 'enabled'"
			],			
			'recording_in_external' => [
				'type' => Type::string(),
				'description' => "Inbound External Calls Recording option 'force', 'yes', 'no', 'never' or 'dontcare'. default is 'dontcare'"
			],
			'recording_out_external' => [
				'type' => Type::string(),
				'description' => "Outbound External Calls Recording option 'force', 'yes', 'no', 'never' or 'dontcare'. default is 'dontcare'"
			],
			'recording_in_internal' => [
				'type' => Type::string(),
				'description' => "Inbound Internal Calls Recording option 'force', 'yes', 'no', 'never' or 'dontcare'. default is 'dontcare'"
			],
			'recording_out_internal' => [
				'type' => Type::string(),
				'description' => "Outbound Internal Calls Recording option 'force', 'yes', 'no', 'never' or 'dontcare'. default is 'dontcare'"
			],
			'recording_ondemand' => [
				'type' => Type::string(),
				'description' => "On Demand Recording option 'enabled', 'disabled' or 'override'. default is 'disabled'"
			],
			'recording_priority' => [
				'type' => Type::int(),
				'description' => "Record Priority Policy option. default is 10"
			],
		];
	}

	private function getMutationExecuteArray($input) {
		return [
			"extension" => isset($input['id']) ? $input['id'] : '',
			"password" => isset($input['password']) ? $input['password'] : null,
			"name" => isset($input['name']) ? $input['name'] : null,
			"voicemail" => isset($input['voicemail']) ? $input['voicemail'] : null,
			"ringtimer" => isset($input['ringtimer']) ? $input['ringtimer'] : null,
			"noanswer" => isset($input['noanswer']) ? $input['noanswer'] : null,
			"recording" => isset($input['recording']) ? $input['recording'] : null,
			"outboundcid" => isset($input['outboundcid']) ? $input['outboundcid'] : null,
			"sipname" => isset($input['sipname']) ? $input['sipname'] : null,
			"noanswer_cid" => isset($input['noanswer_cid']) ? $input['noanswer_cid'] : '',
			"busy_cid" => isset($input['busy_cid']) ? $input['busy_cid'] : '',
			"chanunavail_cid" => isset($input['chanunavail_cid']) ? $input['chanunavail_cid'] : '',
			"noanswer_dest" => isset($input['noanswer_dest']) ? $input['noanswer_dest'] : '',
			"busy_dest" => isset($input['busy_dest']) ? $input['busy_dest'] : '',
			"chanunavail_dest" => isset($input['chanunavail_dest']) ? $input['chanunavail_dest'] : '',
			"mohclass" => isset($input['mohclass']) ? $input['mohclass'] : null,
			"callwaiting" => isset($input['callwaiting']) ? $input['callwaiting'] : 'enabled',
			"pinless" => isset($input['pinless']) ? $input['pinless'] : 'disabled',
			"recording_in_external" => isset($input['recording_in_external']) ? $input['recording_in_external'] : 'dontcare',
			"recording_out_external" => isset($input['recording_out_external']) ? $input['recording_out_external'] : 'dontcare',
			"recording_in_internal" => isset($input['recording_in_internal']) ? $input['recording_in_internal'] : 'dontcare',
			"recording_out_internal" => isset($input['recording_out_internal']) ? $input['recording_out_internal'] : 'dontcare',
            "recording_ondemand" => isset($input['recording_ondemand']) ? $input['recording_ondemand'] : 'disabled',
			"recording_priority" => isset($input['recording_priority']) ? $input['recording_priority'] : null,
		];
	}
}

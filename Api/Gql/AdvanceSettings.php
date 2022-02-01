<?php

namespace FreePBX\modules\Core\Api\Gql;

use GraphQLRelay\Relay;
use GraphQL\Type\Definition\Type;
use FreePBX\modules\Api\Gql\Base;
use \GraphQL\Error\FormattedError;
use GraphQL\Type\Definition\EnumType;

class Advancesettings extends Base {
	protected $module = 'core';

	public static function getScopes() {
		return [
			'read:extension' => [
				'description' => _('Read Advance Settings'),
			],
			'write:extension' => [
				'description' => _('Write Advance Settings'),
			]
		];
	}

	public function mutationCallback() {
			if($this->checkAllWriteScope()) {
			return function() {
				return [
					'updateAdvanceSettings' => Relay::mutationWithClientMutationId([
						'name' => 'updateSettings',
						'description' => _('Updates Advance Settings Field'),
						'inputFields' => $this->getMutationFields(),
						'outputFields' => $this->getOutputFields(),
						'mutateAndGetPayload' => function ($input) {
							$settings = $this->freepbx->config->conf_setting($input['keyword']);
							$validationResult = $this->validateInput($settings,$input);
							if($validationResult['status']){
								$value = $validationResult['value'];
								$keyword = $input['keyword'];
								$alreadyExistingInfo = $this->freepbx->config->conf_setting($keyword);
								$res = $this->freepbx->config->set_conf_values(array($keyword => $value),true);
								if($res || ($alreadyExistingInfo['keyword'] == $keyword && $alreadyExistingInfo['value'] == $value )){
									return ['status' => true, 'message'=> _("Setting's updated successfully")];
								}else{
									return ['message'=> _("Sorry, unable to update settings"),'status' => false];
								}
							}else{
								return $validationResult;
							}
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
					'fetchAllAdvanceSettings' => [
						'type' => $this->typeContainer->get('advacesettings')->getConnectionType(),
						'description' => _('Returns all the advance setting\'s details '),
						'args' => Relay::connectionArgs(),
						'resolve' => function($root, $args) {
							$settings = $this->freepbx->config->get_conf_settings();
							$list = [];
							foreach($settings as $key => $setting){
								if((isset($setting['hidden']) && $setting['hidden'] == 0)){
									$list[] = $setting;
								}
							}
							if(isset($list) && $list != null){
								return ['response'=> $list,'status'=>true, 'message'=> _("Setting's found successfully")];
							}else{
								return ['message'=> _("Sorry, unable to find settings"),'status' => false];
							}
						},
					],
					'fetchAdvanceSetting' => [
						'type' => $this->typeContainer->get('advacesettings')->getObject(),
						'description' => _('Returns particular the advance setting\'s details'),
						'args' => [
							'keyword' => [
								'type' => Type::nonNull(Type::String()),
								'description' => _('Settings Keyword'),
							]
						],
						'resolve' => function($root, $args) {
							$settings = $this->freepbx->config->conf_setting($args['keyword']);
							if(isset($settings) && !empty($settings)){
								$list['keyword']= isset($settings['keyword']) ? $settings['keyword'] : "";
								$list['value']= isset($settings['value']) ? $settings['value'] : "";
								$list['name']= isset($settings['name']) ? $settings['name'] : "";
								$list['category']= isset($settings['category']) ? $settings['category'] : "";
								$list['description']= isset($settings['description']) ? $settings['description'] : "";
								return ['response'=> $list,'status'=>true, 'message'=> _("Setting's found successfully")];
							}else{
								return ['message'=> _("Sorry, unable to find settings"),'status' => false];
							}
						},
					]
				];
			};
		}
	}

	public function initializeTypes() {
		$user = $this->typeContainer->create('advacesettings');

		$user->addInterfaceCallback(function() {
			return [$this->getNodeDefinition()['nodeInterface']];
		});

		$user->addFieldCallback(function() {
			return [
                'id' => Relay::globalIdField('id', function($row) {
					if (isset($row['id'])) {
						return $row['id'];
					} elseif (isset($row['response'])) {
						return  $row['response']['id'];
					}
					return null;
				}),
				'keyword' => [
					'type' => Type::string(),
					'description' => _('keyword of the setting'),
					'resolve' => function($row) {
						if(isset($row['keyword'])){
							return $row['keyword'];
						}elseif(isset($row['response'])){
							return  $row['response']['keyword'];
						}
						return null;
					}
				],
				'value' => [
					'type' => Type::string(),
					'description' => _('value of the setting'),
					'resolve' => function($row) {
						if(isset($row['value'])){
							return $row['value'];
						}elseif(isset($row['response'])){
							return  $row['response']['value'];
						}
						return null;
					}
				],
				'name' => [
					'type' => Type::string(),
					'description' => _('name of the setting'),
					'resolve' => function($row) {
						if(isset($row['name'])){
							return $row['name'];
						}elseif(isset($row['response'])){
							return  $row['response']['name'];
						}
						return null;
					}
				],
				'category' => [
					'type' => Type::string(),
					'category' => _('category of the setting'),
					'resolve' => function($row) {
						if(isset($row['category'])){
							return $row['category'];
						}elseif(isset($row['response'])){
							return  $row['response']['category'];
						}
						return null;
					}
				],
				'description' => [
					'type' => Type::string(),
					'description' => _('description of the setting'),
					'resolve' => function($row) {
						if(isset($row['description'])){
							return $row['description'];
						}elseif(isset($row['response'])){
							return  $row['response']['description'];
						}
						return null;
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

		$user->setConnectionResolveNode(function ($edge) {
			return $edge['node'];
		});

		$user->setConnectionFields(function() {
			return [
				'settings' => [
					'type' => Type::listOf($this->typeContainer->get('advacesettings')->getObject()),
					'resolve' => function($payload) {
						return $payload['response'];
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
        $keywords = [];
        $settings = $this->freepbx->config->get_conf_settings();
        foreach($settings as $setting){
            if((isset($setting['hidden']) && $setting['hidden'] == 0 && isset($setting['keyword']))){
                $keywords[$setting['keyword']]= array('value' => $setting['keyword']);
            }
        }
		return [
			'keyword' => [
                'type' => new EnumType([
										'name' => 'keywordsList',
										'description' => _(''),
										'values' => $keywords
									]),
                'description' => _(''),
            ],
			'value' => [
				'type' => Type::string(),
				'description' => _("value")
			]
		];
	}
	
	/**
	 * getoutputFields
	 *
	 * @return void
	 */
	public function getoutputFields(){
		return [
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

	private function validateInput($settings,$input){
		$keyword = $input['keyword'];

		if(count($settings) > 0){
		
			if(!$settings['readonly']){
		
				if(isset($settings['type'])){
		
					switch($settings['type']){
						
						case 'int':
							if (!preg_match("/^[0-9]+$/", $input['value'])) {
								return ['message' => _("Value of $keyword should be numbers"),'status' => false];
							}else{
								$value = (int)$input['value'];
								return ['status' => true,'value' => $value];
							}
						case 'bool':
							if(($input['value'] != "1") && ($input['value'] != "0") && (strtolower($input['value']) != "true") && (strtolower($input['value']) != "false")){
								return ['message' => _("Value of $keyword should be boolean. Possible values can be 1 or 0 or true or false"),'status' => false];
							}else{
								if(strtolower($input['value']) == "true"){
									$value = 1;
								}else if(strtolower($input['value']) == "false"){
									$value = 0;
								}else{
									$value = (int)$input['value'];
								}
								return ['status' => true,'value' => $value];
							}
		
						case 'text':
							if(gettype($input['value']) != "string"){
								return ['message' => _("Value of $keyword should be string"),'status' => false];
							}else{
								$value = $input['value'];
								return ['status' => true,'value' => $value];
							}
		
						case 'select':
						case 'cselect':
							$options = explode(",", $settings['options']);
							if (!in_array($input['value'], $options)) {
								$options = implode(',',$options);
								return ['message' => _("Invalid settings values, Possible values for this settings are $options"),'status' => false];
							}else{
								$value = $input['value'];
								return ['status' => true,'value' => $value];
							}
		
						case 'fselect':
							$options = array_keys(unserialize($settings['options']));
							if (!in_array($input['value'], $options)) {
								$options = implode(',',$options);
								return ['message' => _("Invalid settings values, Possible values for this settings are $options"),'status' => false];
							}else{
								$value = $input['value'];
								return ['status' => true,'value' => $value];
							}
						case 'dir':
							if(preg_match('#^(\w+/){1,2}\w+\.\w+$#',$input['value'])) {
								return ['message' => _("Invalid path value"),'status' => false];
							}else{
								$value = $input['value'];
								return ['status' => true,'value' => $value];
							}
						case 'default':
							return ['message' => _("Keyword type could not found"),'status' => false];
					}
				}else{
					return ['message' => _("Settings not found"),'status' => false];
				}
			}else{
				return ['message' => _("Settings can not be updated. Permission denied"),'status' => false];
			}
		}else{
			return ['message' => _("Settings not found"),'status' => false];
		}
	}
}

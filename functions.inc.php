<?php
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
// vim: set ai ts=4 sw=4 ft=php:
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2006-2014 Schmooze Com Inc.
//

class core_conf {
	var $_sip_general    = array();
	var $_sip_additional = array();
	var $_sip_additionalsection = array();
	var $_sip_notify     = array();
	var $_iax_general    = array();
	var $_iax_additional = array();
	var $_dahdi_additional = array();
	var $_featuregeneral = array();
	var $_featuregeneralsection = array();
	var $_featuremap     = array();
	var $_applicationmap = array();
	var $_res_odbc       = array();
	var $dev_user_map;

	// Static Object used for self-referencing.
	private static $obj;

	public function __construct() {
		// Ensure the local object is available
		self::$obj = $this;
	}

	public static function create() {
		if (!isset(self::$obj))
		self::$obj = new FreePBX();

		return self::$obj;
	}

	// map the actual vmcontext and user devicename if the device is fixed
	private function map_dev_user($account, $keyword, $data) {
		global $amp_conf;

		if (!isset($this->dev_user_map)) {
			$this->dev_user_map = core_devices_get_user_mappings();
		}

		if (!empty($this->dev_user_map[$account]) && $this->dev_user_map[$account]['devicetype'] == 'fixed') {
			switch (strtolower($keyword)) {
				case 'callerid':
				$user_option = $this->dev_user_map[$account]['description'] . ' <' . $account . '>';
				break;
				case 'mailbox':
				if ((empty($this->dev_user_map[$account]['vmcontext']) || $this->dev_user_map[$account]['vmcontext'] == 'novm')
				&& strtolower($data) == "$account" . "@device" && $amp_conf['DEVICE_REMOVE_MAILBOX']) {
					// they have no vm so don't put a mailbox=line
					return "";
				} elseif (strtolower($data) == "$account" . "@device"
				&& !empty($this->dev_user_map[$account]['vmcontext']) &&
				$this->dev_user_map[$account]['vmcontext'] != 'novm') {
					$user_option = $this->dev_user_map[$account]['user'] . "@" . $this->dev_user_map[$account]['vmcontext'];
				} else {
					$user_option = $data;
				}
			}
			$output = $keyword . "=" . $user_option . "\n";
		} else {
			$output = $keyword . "=" . $data . "\n";
		}
		return $output;
	}

	// return an array of filenames to write
	function get_filename() {
		global $chan_dahdi;

		$files = array(
			'sip_additional.conf',
			'sip_registrations.conf',
			'iax_additional.conf',
			'iax_registrations.conf',
			'sip_general_additional.conf',
			'iax_general_additional.conf',
			'features_general_additional.conf',
			'features_applicationmap_additional.conf',
			'features_featuremap_additional.conf',
			'localprefixes.conf',
			'sip_notify_additional.conf',
			'res_odbc_additional.conf',
			'chan_dahdi_additional.conf',
			'http_additional.conf'
		);
		return $files;
	}

	// return the output that goes in each of the files
	function generateConf($file) {
		global $version;
		global $amp_conf;

		switch ($file) {
			case 'sip_general_additional.conf':
			return $this->generate_sip_general_additional($version);
			break;
			case 'sip_additional.conf':
			return $this->generate_sip_additional($version);
			break;
			case 'sip_registrations.conf':
			return $this->generate_sip_registrations($version);
			break;
			case 'sip_notify_additional.conf':
			return $this->generate_sip_notify_additional($version);
			break;
			case 'iax_general_additional.conf':
			return $this->generate_iax_general_additional($version);
			break;
			case 'iax_additional.conf':
			return $this->generate_iax_additional($version);
			break;
			case 'iax_registrations.conf':
			return $this->generate_iax_registrations($version);
			break;
			case 'chan_dahdi_additional.conf':
			return $this->generate_zapata_additional($version, 'dahdi').$this->generate_zapata_additional($version);
			break;
			case 'zapata_additional.conf':
			return $this->generate_zapata_additional($version);
			break;
			case 'features_general_additional.conf':
			return $this->generate_featuregeneral_additional($version);
			break;
			case 'features_applicationmap_additional.conf':
			return $this->generate_applicationmap_additional($version);
			break;
			case 'features_featuremap_additional.conf':
			return $this->generate_featuremap_additional($version);
			break;
			case 'res_odbc_additional.conf':
			return $this->generate_res_odbc_additional($version);
			break;
			case 'http_additional.conf':
			return $this->generate_http_additional($version);
			break;
		}
	}

	function addSipNotify($section,$entries) {
		$this->_sip_notify[] = array('section' => $section, 'entries' => $entries);
	}

	function generate_sip_notify_additional($ast_version) {
		$output = '';
		if (isset($this->_sip_notify) && is_array($this->_sip_notify)) {
			foreach ($this->_sip_notify as $section) {
				$output .= "[".$section['section']."]\n";
				foreach ($section['entries'] as $key => $value) {
					if (strtolower($key) == 'content-length') {
						continue;
					}
					$output .= "$key=>$value\n";
				}
				$output .= "\n";
			}
		}
		return $output;
	}

	function addResOdbc($section,$entries) {
		$this->_res_odbc[$section][] = $entries;
	}

	function generate_res_odbc_additional($ast_version) {
		$output = '';
		if (!empty($this->_res_odbc)) {
			foreach ($this->_res_odbc as $section => $entries) {
				$output .= "[".$section."]\n";
				foreach ($entries as $key => $entry) {
					foreach ($entry as $key => $value) {
						$output .= "$key=>$value\n";
					}
				}
				$output .= "\n";
			}
		}
		return $output;
	}

	function generate_http_additional($ast_version) {
		$freepbx_conf =& freepbx_conf::create();

		$output = "[general]\n";
		$output .= "enabled=".($freepbx_conf->get_conf_setting('HTTPENABLED') ? 'yes' : 'no')."\n";
		$output .= "enablestatic=".($freepbx_conf->get_conf_setting('HTTPENABLESTATIC') ? 'yes' : 'no')."\n";
		$output .= "bindaddr=".$freepbx_conf->get_conf_setting('HTTPBINDADDRESS')."\n";
		$output .= "bindport=".$freepbx_conf->get_conf_setting('HTTPBINDPORT')."\n";
		$output .= "prefix=".$freepbx_conf->get_conf_setting('HTTPPREFIX')."\n";
		$output .= "sessionlimit=".$freepbx_conf->get_conf_setting('HTTPSESSIONLIMIT')."\n";
		$output .= "session_inactivity=".$freepbx_conf->get_conf_setting('HTTPSESSIONINACTIVITY')."\n";
		$output .= "session_keep_alive=".$freepbx_conf->get_conf_setting('HTTPSESSIONKEEPALIVE')."\n";
		$tls = $freepbx_conf->get_conf_setting('HTTPTLSENABLE');
		if ($tls) {
			$output .= "tlsenable=yes\n";
			// Is this an IPv6 address? If so, it needs brackets around it.
			$bindaddr = $freepbx_conf->get_conf_setting('HTTPTLSBINDADDRESS');
			if (filter_var($bindaddr, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV6)) {
				$bindaddr = "[$bindaddr]";
			}
			$output .= "tlsbindaddr=$bindaddr:".$freepbx_conf->get_conf_setting('HTTPTLSBINDPORT')."\n";
			$output .= "tlscertfile=".$freepbx_conf->get_conf_setting('HTTPTLSCERTFILE')."\n";
			$output .= "tlsprivatekey=".$freepbx_conf->get_conf_setting('HTTPTLSPRIVATEKEY')."\n";
		}
		return $output;
	}

	function addSipAdditional($section, $key, $value) {
		$this->_sip_additional[$section][] = array('key' => $key, 'value' => $value);
	}

	function addSipAdditionalSection($section) {
		$this->_sip_additionalsection[] = $section;
	}

	function addSipGeneral($key, $value) {
		$this->_sip_general[] = array('key' => $key, 'value' => $value);
	}

	function generate_sip_general_additional($ast_version) {
		$output = '';

		if (isset($this->_sip_general) && is_array($this->_sip_general)) {
			foreach ($this->_sip_general as $values) {
				$output .= $values['key']."=".$values['value']."\n";
			}
		}
		return $output;
	}

	function addIaxGeneral($key, $value) {
		$this->_iax_general[] = array('key' => $key, 'value' => $value);
	}

	function generate_iax_general_additional($ast_version) {
		$output = '';

		if (isset($this->_iax_general) && is_array($this->_iax_general)) {
			foreach ($this->_iax_general as $values) {
				$output .= $values['key']."=".$values['value']."\n";
			}
		}
		return $output;
	}

	function addFeatureGeneral($key, $value) {
		$this->_featuregeneral[] = array('key' => $key, 'value' => $value);
	}

	function addFeatureGeneralSection($section, $key, $value) {
		$this->_featuregeneralsection[$section][] = array('key' => $key, 'value' => $value);
	}

	function generate_featuregeneral_additional($ast_version) {
		$output = '';

		if (isset($this->_featuregeneral) && is_array($this->_featuregeneral)) {
			foreach ($this->_featuregeneral as $values) {
				$output .= $values['key']."=".$values['value']."\n";
			}
		}
		foreach ($this->_featuregeneralsection as $section => $values) {
			$output .= "\n[$section]\n";
			foreach ($values as $value) {
				$output .= $value['key'] . "=" . $value['value'] . "\n";
			}
		}
		return $output;
	}

	function addFeatureMap($key, $value) {
		$this->_featuremap[] = array('key' => $key, 'value' => $value);
	}

	function generate_featuremap_additional($ast_version) {
		$output = '';

		if (isset($this->_featuremap) && is_array($this->_featuremap)) {
			foreach ($this->_featuremap as $values) {
				$output .= $values['key']."=".$values['value']."\n";
			}
		}
		return $output;
	}

	function addApplicationMap($key, $value, $add_to_dynamic_features=false) {
		global $ext;
		$this->_applicationmap[] = array('key' => $key, 'value' => $value);
		//
		// Now add it to the DYNAMIC_FEATURES
		// TODO: one caveat, if we ever want to make such an application conditional, we will have to change
		// this as for now it makes it for everyone.
		//
		if ($add_to_dynamic_features) {
			$ext->_globals['DYNAMIC_FEATURES'] = empty($ext->_globals['DYNAMIC_FEATURES']) ? $key : $ext->_globals['DYNAMIC_FEATURES'] . ',' . $key;
		}
	}

	function generate_applicationmap_additional($ast_version) {
		$output = '';

		if (isset($this->_applicationmap) && is_array($this->_applicationmap)) {
			foreach ($this->_applicationmap as $values) {
				$output .= $values['key']."=>".$values['value']."\n";
			}
		}
		return $output;
	}

	function generate_sip_additional($ast_version) {
		global $db;

		$table_name = "sip";
		$additional = "";
		$finaloutput = "";

		// Asterisk 1.4 requires call-limit be set for hints to work properly
		//
		if (version_compare($ast_version, "1.6.1", "ge")) {
			$call_limit = "callcounter=yes\n";
			$ver12 = false;
		} else if (version_compare($ast_version, "1.4", "ge")) {
			$call_limit = "call-limit=50\n";
			$ver12 = false;
		} else {
			$call_limit = "";
			$ver12 = true;
		}
		if (version_compare($ast_version, "1.6", "ge")) {
			$faxdetect = "faxdetect=no\n";
			$ver16 = true;
		} else {
			$faxdetect = "";
			$ver16 = false;
		}
		if (version_compare($ast_version, "1.8", "ge")) {
			$ver18 = true;
		} else {
			$ver18 = false;
		}
		if (version_compare($ast_version, "10", "ge")) {
			$ver100 = true;
		} else {
			$ver100 = false;
		}
		if (version_compare($ast_version, "11.5", "ge")) {
			$ver115 = true;
		} else {
			$ver115 = false;
		}
		// TODO: Temporary Kludge until CCSS is fixed
		//
		if (function_exists('campon_get_config') && version_compare($ast_version, "1.8", "ge")) {
			$cc_monitor_policy = "cc_monitor_policy=generic\n";
		} else {
			$cc_monitor_policy = "";
		}

		$sql = "SELECT keyword,data from $table_name where id=-1 and keyword <> 'account' and flags <> 1";
		$results = $db->getAll($sql, DB_FETCHMODE_ASSOC);
		if(DB::IsError($results)) {
			die($results->getMessage());
		}
		foreach ($results as $result) {
			if ($ver12) {
				$additional .= $result['keyword']."=".$result['data']."\n";
			} else {
				$option = $result['data'];
				switch (strtolower($result['keyword'])) {
					case 'insecure':
					if ($option == 'very')
					$additional .= "insecure=port,invite\n";
					else if ($option == 'yes')
					$additional .= "insecure=port\n";
					else
					$additional .= $result['keyword']."=$option\n";
					break;
					case 'allow':
					case 'disallow':
					case 'accountcode':
					if ($option != '')
					$additional .= $result['keyword']."=$option\n";
					break;
					default:
					$additional .= $result['keyword']."=$option\n";
				}
			}
		}

		$sql = "SELECT data,id from $table_name where keyword='account' and flags <> 1 group by data";
		$results = $db->getAll($sql, DB_FETCHMODE_ASSOC);
		if(DB::IsError($results)) {
			die($results->getMessage());
		}

		$usedAccounts = array();
		foreach ($results as $result) {
			$output = "";
			$account = $result['data'];
			$usedAccounts[] = $account;
			$id = $result['id'];

			$sql = "SELECT keyword,data from $table_name where id='$id' and keyword <> 'account' and flags <> 1 order by flags, keyword DESC";
			$results2_pre = $db->getAll($sql, DB_FETCHMODE_ASSOC);
			if(DB::IsError($results2_pre)) {
				die($results2->getMessage());
			}

			// Move all 'disallow=all' and 'deny' to the top to avoid errors
			//
			$results2 = array();
			foreach ($results2_pre as $element) {
				if($element['keyword'] == 'sipdriver' && $element['data'] == 'chan_pjsip'){
					continue(2);
				}
				if($element['keyword'] == 'sipdriver'){
					continue;
				}
				if (strtolower(trim($element['keyword'])) != 'secret') {
					$options = explode("&", $element['data']);
					foreach ($options as $option) {
						if (($element['keyword'] == 'disallow' && $option == 'all') | ($element['keyword'] == 'deny')) {
							array_unshift($results2,array('keyword'=>$element['keyword'],'data'=>$option));
						} else {
							$results2[] = array('keyword'=>$element['keyword'],'data'=>$option);
						}
					}
				} else {
					$results2[] = array('keyword'=>$element['keyword'],'data'=>str_replace(';','\;',$element['data']));
				}
			}
			unset($results2_pre);

			$output .= "[$account]\n";
			$context='';
			foreach ($results2 as $result2) {
				$option = strtolower($result2['data']);
				if ($ver12) {
					switch (strtolower($result2['keyword'])) {
						case 'context':
						$context = $option;
						//fall-through
						default:
						$output .= $result2['keyword']."=".$result2['data']."\n";
					}
				} else {
					switch (strtolower($result2['keyword'])) {
						case 'sessiontimers':
							$output .= "session-timers=".$result2['data']."\n";
						break;
						case 'videosupport':
							if($result2['data'] != 'inherit') {
								$output .= "videosupport=".$result2['data']."\n";
							}
						break;
						case 'insecure':
						if ($option == 'very')
						$output .= "insecure=port,invite\n";
						else if ($option == 'yes')
						$output .= "insecure=port\n";
						else
						$output .= $result2['keyword']."=".$result2['data']."\n";
						break;
						case 'allow':
						case 'disallow':
						case 'accountcode':
						if ($option != '')
						$output .= $result2['keyword']."=".$result2['data']."\n";
						break;
						case 'callerid':
						case 'mailbox':
						$output .= $this->map_dev_user($account, $result2['keyword'], $result2['data']);
						break;
						case 'secret_origional':
						//stupidness coming through
						break;
						case 'username':
						//http://issues.freepbx.org/browse/FREEPBX-7715
						if($ver18) {
							$output .= "defaultuser=".$result2['data']."\n";
						} else {
							$output .= "username=".$result2['data']."\n";
						}
						break;
						case 'nat':
						//http://issues.freepbx.org/browse/FREEPBX-6518
						if($ver115) {
							$newval = "";
							switch($result2['data']) {
								case 'yes':
								$newval = "force_rport,comedia";
								break;
								case 'route':
								$newval = "force_rport";
								break;
								case 'never':
								$newval = "no";
								break;
								default:
								$newval = $result2['data'];
								break;
							}
							$output .= $result2['keyword']."=".$newval."\n";
						} elseif($ver100) {
							$newval = "";
							switch($result2['data']) {
								case 'route':
								$newval = "force_rport";
								break;
								case 'never':
								$newval = "no";
								break;
								default:
								$newval = $result2['data'];
								break;
							}
							$output .= $result2['keyword']."=".$newval."\n";
						} elseif($ver18) {
							$newval = "";
							switch($result2['data']) {
								case 'route':
								$newval = "force_rport";
								break;
								case 'never':
								$newval = "no";
								break;
								default:
								$newval = $result2['data'];
								break;
							}
							$output .= $result2['keyword']."=".$newval."\n";
						} else {
							$output .= $result2['keyword']."=".$result2['data']."\n";
						}
						break;
						case 'context':
						$context = $result2['data'];
						//fall-through
						default:
						$output .= $result2['keyword']."=".$result2['data']."\n";
					}
				}
			}
			switch (substr($id,0,8)) {
				case 'tr-peer-':
				if ($context == '') {
					$output .= "context=from-trunk-sip-$account\n";
				}
				break;
				case 'tr-user-':
				if ($context == '') {
					$tn = substr($id, 8);
					// this is a 'user' trunk, we need to get the name of the corresponding 'peer'
					// trunk so we can set the context appropriately for the group count
					//
					$td = core_trunks_getDetails($tn);
					if (isset($td['channelid'])) {
						$output .= "context=from-trunk-sip-".$td['channelid']."\n";
					}
				}
				break;
				default:
				if ($call_limit) {
					$output .= $call_limit;
				}
				if ($faxdetect) {
					$output .= $faxdetect;
				}
				if ($cc_monitor_policy) {
					$output .= $cc_monitor_policy;
				}
			}
			if (isset($this->_sip_additional[$account])) {
				foreach ($this->_sip_additional[$account] as $asetting) {
					$output .= $asetting['key'] . "=" . $asetting['value'] . "\n";
				}
			}
			$output .= $additional."\n";
			$finaloutput .= $output;
		}
		foreach($this->_sip_additionalsection as $section) {
			if(!in_array($section,$usedAccounts) && !empty($this->_sip_additional[$section])) {
				$output = "[".$section."]\n";
				foreach ($this->_sip_additional[$section] as $asetting) {
					$output .= $asetting['key'] . "=" . $asetting['value'] . "\n";
				}
				$finaloutput .= $output;
			} elseif(in_array($section,$usedAccounts)) {
				throw new \Exception(sprintf(_("%s is already in use sip.conf. Can not continue"),$section));
			}
		}
		return $finaloutput;
	}

	function generate_sip_registrations($ast_version) {
		global $db;

		$table_name = "sip";
		$output = "";

		$sql = "SELECT keyword,data FROM $table_name WHERE `id` LIKE 'tr-reg-%' AND keyword <> 'account' AND flags <> 1";
		$results = $db->getAll($sql, DB_FETCHMODE_ASSOC);
		if(DB::IsError($results)) {
			die($results->getMessage());
		}

		foreach ($results as $result) {
			$output .= $result['keyword']."=".$result['data']."\n";
		}

		return $output;
	}

	function addIaxAdditional($section, $key, $value) {
		$this->_iax_additional[$section][] = array('key' => $key, 'value' => $value);
	}

	function generate_iax_additional($ast_version) {
		global $db;

		$table_name = "iax";
		$additional = "";
		$output = "";

		$ver12 = version_compare($ast_version, '1.4', 'lt');

		$sql = "SELECT keyword,data from $table_name where id=-1 and keyword <> 'account' and flags <> 1";
		$results = $db->getAll($sql, DB_FETCHMODE_ASSOC);
		if(DB::IsError($results)) {
			die($results->getMessage());
		}
		foreach ($results as $result) {
			if ($ver12) {
				$additional .= $result['keyword']."=".$result['data']."\n";
			} else {
				$option = $result['data'];
				switch ($result['keyword']) {
					case 'notransfer':
					if (strtolower($option) == 'yes') {
						$additional .= "transfer=no\n";
					} else if (strtolower($option) == 'no') {
						$additional .= "transfer=yes\n";
					} else if (strtolower($option) == 'mediaonly') {
						$additional .= "transfer=mediaonly\n";
					} else {
						$additional .= $result['keyword']."=$option\n";
					}
					break;
					case 'allow':
					case 'disallow':
					case 'accountcode':
					if ($option != '')
					$additional .= $result['keyword']."=$option\n";
					break;
					case 'requirecalltoken':
					if ($option != '')
					$additional .= $result['keyword']."=$option\n";
					break;
					default:
					$additional .= $result['keyword']."=$option\n";
				}
			}
		}

		$sql = "SELECT data,id from $table_name where keyword='account' and flags <> 1 group by data";
		$results = $db->getAll($sql, DB_FETCHMODE_ASSOC);
		if(DB::IsError($results)) {
			die($results->getMessage());
		}

		foreach ($results as $result) {
			$account = $result['data'];
			$id = $result['id'];
			$output .= "[$account]\n";

			$sql = "SELECT keyword,data from $table_name where id='$id' and keyword <> 'account' and flags <> 1 order by flags, keyword DESC";
			$results2_pre = $db->getAll($sql, DB_FETCHMODE_ASSOC);
			if(DB::IsError($results2_pre)) {
				die($results2_pre->getMessage());
			}

			// Move all 'disallow=all' and 'deny=' to the top to avoid errors
			//
			$results2 = array();
			foreach ($results2_pre as $element) {
				if (strtolower(trim($element['keyword'])) != 'secret') {
					$options = explode("&", $element['data']);
					foreach ($options as $option) {
						if (($element['keyword'] == 'disallow' && $option == 'all') | ($element['keyword'] == 'deny')) {
							array_unshift($results2,array('keyword'=>$element['keyword'],'data'=>$option));
						} else {
							$results2[] = array('keyword'=>$element['keyword'],'data'=>$option);
						}
					}
				} else {
					$results2[] = array('keyword'=>$element['keyword'],'data'=>str_replace(';','\;',$element['data']));
				}
			}
			unset($results2_pre);

			$context='';
			foreach ($results2 as $result2) {
				$option = strtolower($result2['data']);
				if ($ver12) {
					switch (strtolower($result2['keyword'])) {
						case 'context':
						$context = $result2['data'];
						//fall-through
						default:
						$output .= $result2['keyword']."=".$result2['data']."\n";
					}
				} else {
					switch ($result2['keyword']) {
						case 'notransfer':
						if (strtolower($option) == 'yes') {
							$output .= "transfer=no\n";
						} else if (strtolower($option) == 'no') {
							$output .= "transfer=yes\n";
						} else if (strtolower($option) == 'mediaonly') {
							$output .= "transfer=mediaonly\n";
						} else {
							$output .= $result2['keyword']."=".$result2['data']."\n";
						}
						break;
						case 'allow':
						case 'disallow':
						case 'accountcode':
						if ($option != '')
						$output .= $result2['keyword']."=".$result2['data']."\n";
						break;
						case 'requirecalltoken':
						if ($option != '')
						$output .= $result2['keyword']."=".$result2['data']."\n";
						break;
						case 'callerid':
						case 'mailbox':
						$output .= $this->map_dev_user($account, $result2['keyword'], $result2['data']);
						break;
						case 'context':
						$context = $option;
						//fall-through
						default:
						$output .= $result2['keyword']."=".$result2['data']."\n";
					}
				}
			}
			switch (substr($id,0,8)) {
				case 'tr-peer-':
				if ($context == '') {
					$output .= "context=from-trunk-iax2-$account\n";
				}
				break;
				case 'tr-user-':
				if ($context == '') {
					$tn = substr($id, 8);
					// this is a 'user' trunk, we need to get the name of the corresponding 'peer'
					// trunk so we can set the context appropriately for the group count
					//
					$td = core_trunks_getDetails($tn);
					if (isset($td['channelid'])) {
						$output .= "context=from-trunk-iax2-".$td['channelid']."\n";
					}
				}
				break;
				default:
			}
			if (isset($this->_iax_additional[$account])) {
				foreach ($this->_iax_additional[$account] as $asetting) {
					$output .= $asetting['key'] . "=" . $asetting['value'] . "\n";
				}
			}
			$output .= $additional."\n";
		}
		return $output;
	}

	function generate_iax_registrations($ast_version) {
		global $db;

		$table_name = "iax";
		$output = "";

		$sql = "SELECT keyword,data FROM $table_name WHERE `id` LIKE 'tr-reg-%' AND keyword <> 'account' AND flags <> 1";
		$results = $db->getAll($sql, DB_FETCHMODE_ASSOC);
		if(DB::IsError($results)) {
			die($results->getMessage());
		}

		foreach ($results as $result) {
			$output .= $result['keyword']."=".$result['data']."\n";
		}

		return $output;
	}

	function addDahdiAdditional($section, $key, $value) {
		$this->_dahdi_additional[$section][] = array('key' => $key, 'value' => $value);
	}

	function generate_zapata_additional($ast_version, $table_name = 'zap') {
		global $db;

		$additional = "";
		$output = '';

		$sql = "SELECT keyword,data from $table_name where id=-1 and keyword <> 'account' and flags <> 1";
		$results = $db->getAll($sql, DB_FETCHMODE_ASSOC);
		if(DB::IsError($results)) {
			if($table_name == 'zap') {
				return '';
			} else {
				die($results->getMessage());
			}
		}
		foreach ($results as $result) {
			$additional .= $result['keyword']."=".$result['data']."\n";
		}

		$sql = "SELECT data,id from $table_name where keyword='account' and flags <> 1 group by data";
		$results = $db->getAll($sql, DB_FETCHMODE_ASSOC);
		if(DB::IsError($results)) {
			die($results->getMessage());
		}

		foreach ($results as $result) {
			$account = $result['data'];
			$id = $result['id'];
			$output .= ";;;;;;[$account]\n";

			$sql = "SELECT keyword,data from $table_name where id=$id and keyword <> 'account' and flags <> 1 order by keyword DESC";
			$results2 = $db->getAll($sql, DB_FETCHMODE_ASSOC);
			if(DB::IsError($results2)) {
				die($results2->getMessage());
			}
			$zapchannel="";
			foreach ($results2 as $result2) {
				switch ($result2['keyword']) {
					case 'channel':
					$zapchannel = $result2['data'];
					break;

					// These are not zapata.conf variables so keep out of file
					case 'dial':
					break;
					case 'callerid':
					case 'mailbox':
					$output .= $this->map_dev_user($account, $result2['keyword'], $result2['data']);
					break;
					default:
					$output .= $result2['keyword']."=".$result2['data']."\n";
				}
			}
			if (isset($this->_dahdi_additional[$account])) {
				foreach ($this->_dahdi_additional[$account] as $asetting) {
					$output .= $asetting['key'] . "=" . $asetting['value'] . "\n";
				}
			}
			$output .= $additional ? $additional."\n" : '';
			$output .= "channel=>$zapchannel\n";
		}
		return $output;
	}
}

function core_destination_popovers() {
	global $amp_conf;
	if ($amp_conf['AMPEXTENSIONS'] == "deviceanduser") {
		$ret['users'] = 'Users';
	} else {
		$ret['extensions'] = 'Extensions';
	}
	return $ret;
}

// The destinations this module provides
// returns a associative arrays with keys 'destination' and 'description'
function core_destinations() {
	global $amp_conf;
	//static destinations
	$extens = array();
	$category = _("Terminate Call");
	$ds_id = 'blackhole';
	$extens[] = array('destination' => 'app-blackhole,hangup,1', 'description' => _("Hangup"), 'category' => $category, 'id' => $ds_id);
	$extens[] = array('destination' => 'app-blackhole,congestion,1', 'description' => _("Congestion"), 'category' => $category, 'id' => $ds_id);
	$extens[] = array('destination' => 'app-blackhole,busy,1', 'description' => _("Busy"), 'category' => $category, 'id' => $ds_id);
	$extens[] = array('destination' => 'app-blackhole,zapateller,1', 'description' => _("Play SIT Tone (Zapateller)"), 'category' => $category, 'id' => $ds_id);
	$extens[] = array('destination' => 'app-blackhole,musiconhold,1', 'description' => _("Put caller on hold forever"), 'category' => $category, 'id' => $ds_id);
	$extens[] = array('destination' => 'app-blackhole,ring,1', 'description' => _("Play ringtones to caller until they hangup"), 'category' => $category, 'id' => $ds_id);
	$extens[] = array('destination' => 'app-blackhole,no-service,1', 'description' => _("Play no service message"), 'category' => $category, 'id' => $ds_id);

	//get the list of meetmes
	$results = core_users_list();

	$vmboxes = array();
	if (isset($results) && function_exists('voicemail_getVoicemail')) {
		//get voicemail
		$uservm = voicemail_getVoicemail();
		$vmcontexts = array_keys($uservm);
		foreach ($results as $thisext) {
			$extnum = $thisext[0];
			// search vm contexts for this extensions mailbox
			foreach ($vmcontexts as $vmcontext) {
				if(isset($uservm[$vmcontext][$extnum])){
					//$vmname = $uservm[$vmcontext][$extnum]['name'];
					//$vmboxes[$extnum] = array($extnum, '"' . $vmname . '" <' . $extnum . '>');
					$vmboxes[$extnum] = true;
				}
			}
		}
	}

	// return an associative array with destination and description
	// core provides both users and voicemail boxes as destinations
	if (isset($results)) {
		$cat_id = ($amp_conf['AMPEXTENSIONS'] == "deviceanduser")?'users':'extensions';
		$cat    = ($amp_conf['AMPEXTENSIONS'] == "deviceanduser")?'Users':'Extensions';
		foreach($results as $result) {
			$extens[] = array('destination' => 'from-did-direct,'.$result['0'].',1', 'description' => ' '.$result['0'].' '.$result['1'], 'category' => $cat, 'id' => $cat_id);
			if(isset($vmboxes[$result['0']])) {
				$extens[] = array('destination' => 'ext-local,vmb'.$result['0'].',1', 'description' => $result[0].' '.$result[1].' (Busy Message)', 'category' => 'Voicemail', 'id' => 'voicemail');
				$extens[] = array('destination' => 'ext-local,vmu'.$result['0'].',1', 'description' => $result[0].' '.$result[1].' (Unavailable Message)', 'category' => 'Voicemail', 'id' => 'voicemail');
				$extens[] = array('destination' => 'ext-local,vms'.$result['0'].',1', 'description' => $result[0].' '.$result[1].' (No Message)', 'category' => 'Voicemail', 'id' => 'voicemail');
				$extens[] = array('destination' => 'ext-local,vmi'.$result['0'].',1', 'description' => $result[0].' '.$result[1].' (Instructions Only)', 'category' => 'Voicemail', 'id' => 'voicemail');
			}
		}
	}

	$trunklist = core_trunks_listbyid();
	if (is_array($trunklist)) foreach ($trunklist as $trunk) {
		switch($trunk['tech']) {
			case 'enum':
			break;
			default:
			$extens[] = array('destination' => 'ext-trunk,'.$trunk['trunkid'].',1', 'description' => $trunk['name'].' ('.$trunk['tech'].')', 'category' => 'Trunks', 'id' => 'trunks');
			break;
		}
	}

	return $extens;
}

function core_getdest($exten) {
	$dests[] = 'from-did-direct,'.$exten.',1';
	$dests[] = 'ext-trunk,'.$exten.',1';
	if (!function_exists('voicemail_mailbox_get')) {
		return $dests;
	}
	$box = voicemail_mailbox_get($exten);
	if ($box == null) {
		return $dests;
	}
	$dests[] = 'ext-local,vmb'.$exten.',1';
	$dests[] = 'ext-local,vmu'.$exten.',1';
	$dests[] = 'ext-local,vms'.$exten.',1';
	$dests[] = 'ext-local,vmi'.$exten.',1';

	return $dests;
}

function core_getdestinfo($dest) {
	global $amp_conf;
	global $active_modules;

	// Check for Extension Number Destinations
	//
	$users = \FreePBX::Core()->getAllUsers();
	if (substr(trim($dest),0,16) == 'from-did-direct,') {
		$exten = explode(',',$dest);
		$exten = $exten[1];
		if (!isset($users[$exten])) {
			return array();
		} else {
			$name = isset($users[$exten]['name'])?$users[$exten]['name']:'';
			$display = ($amp_conf['AMPEXTENSIONS'] == "deviceanduser")?'users':'extensions';
			return array('description' => sprintf(_("User Extension %s: %s"),$exten,$name),
			'edit_url' => "config.php?type=setup&display=$display&extdisplay=".urlencode($exten)."&skip=0");
		}
	} else if (substr(trim($dest),0,10) == 'ext-trunk,') {
		$exten = explode(',',$dest);
		$exten = $exten[1];
		$thisexten = core_trunks_getDetails($exten);
		if (empty($thisexten)) {
			return array();
		} else {
			$display = 'trunks';
			$name = isset($thisexten['name']) && $thisexten['name'] ? $thisexten['name'] : '';
			return array('description' => sprintf(_('Trunk: %s (%s)'),$name,$thisexten['tech']),
			'edit_url' => "config.php?type=setup&display=$display&extdisplay=OUT_".urlencode($exten));
		}

	// Check for voicemail box destinations
	//
	} else if (substr(trim($dest),0,12) == 'ext-local,vm') {
		$exten = explode(',',$dest);
		$exten = substr($exten[1],3);
		if (!function_exists('voicemail_mailbox_get')) {
			return array();
		}
		if (!isset($users[$exten])) {
			return array();
		}
		$box = voicemail_mailbox_get($exten);
		if ($box == null) {
			return array();
		}
		$description = sprintf(_("User Extension %s: %s"),$exten, $box['name']);
		$display = ($amp_conf['AMPEXTENSIONS'] == "deviceanduser")?'users':'extensions';
		return array('description' => $description,
		'edit_url' => "config.php?type=setup&display=$display&extdisplay=".urlencode($exten)."&skip=0");

	// Check for blackhole Termination Destinations
	//
	} else if (substr(trim($dest),0,14) == 'app-blackhole,') {
		$exten = explode(',',$dest);
		$exten = $exten[1];

		switch ($exten) {
			case 'hangup':
			$description = 'Hangup';
			break;
			case 'congestion':
			$description = 'Congestion';
			break;
			case 'busy':
			$description = 'Busy';
			break;
			case 'zapateller':
			$description = 'Play SIT Tone (Zapateller)';
			break;
			case 'musiconhold':
			$description = 'Put caller on hold forever';
			break;
			case 'ring':
			$description = 'Play ringtones to caller';
			break;
			case 'no-service':
			$description = 'Play no service message';
			break;
			default:
			$description = false;
		}
		if ($description) {
			return array('description' => 'Core: '.$description,
			'edit_url' => false);
		} else {
			return array();
		}
	// None of the above, so not one of ours
	} else {
		return false;
	}
}
/* 	Generates dialplan for "core" components (extensions & inbound routing)
We call this with retrieve_conf
*/
function core_do_get_config($engine) {
	global $ext;  // is this the best way to pass this?
	global $version;  // this is not the best way to pass this, this should be passetd together with $engine
	global $engineinfo;
	global $amp_conf;
	global $core_conf;
	global $chan_dahdi;
	global $chan_dahdi_loaded;
	global $astman;

	$modulename = "core";

	$callrecording = 'callrecording';
	$callrecording_uid = 'MISSING_CALLRECORDINGS';
	$getCallRecordingModInfo = module_getinfo($callrecording, MODULE_STATUS_ENABLED);
	$nt = notifications::create();
	if (!isset($getCallRecordingModInfo[$callrecording]) || ($getCallRecordingModInfo[$callrecording]['status'] !== MODULE_STATUS_ENABLED)) {
		if(!$nt->exists($modulename, $callrecording_uid)) {
			$nt->add_notice($modulename, $callrecording_uid, _('Call Recording Module Not Enabled'), _('The Call Recording module is not enabled. Since this feature is required for call recording you may not be able to record calls until the module is installed and enabled.'), '', true, true);
		}
	} else {
		if($nt->exists($modulename, $callrecording_uid)) {
			$nt->delete($modulename, $callrecording_uid);
		}
	}

	switch($engine) {
		case "asterisk":

		$ast_ge_14 = version_compare($version, '1.4', 'ge');
		$ast_lt_16 = version_compare($version, '1.6', 'lt');
		$ast_lt_161 = version_compare($version, '1.6.1', 'lt');
		$ast_ge_162 = version_compare($version, '1.6.2', 'ge');
		$ast_ge_10 = version_compare($version, '10', 'ge');

		// Now add to sip_general_addtional.conf
		//

		if (isset($core_conf) && is_a($core_conf, "core_conf")) {
			$core_conf->addSipGeneral('disallow','all');
			$core_conf->addSipGeneral('allow','ulaw');
			$core_conf->addSipGeneral('allow','alaw');
			$core_conf->addSipGeneral('context','from-sip-external');
			$core_conf->addSipGeneral('callerid','Unknown');
			$core_conf->addSipGeneral('notifyringing','yes');
			if ($ast_ge_14) {
				$core_conf->addSipGeneral('notifyhold','yes');
				$core_conf->addSipGeneral('tos_sip','cs3');    // Recommended setting from doc/ip-tos.txt
				$core_conf->addSipGeneral('tos_audio','ef');   // Recommended setting from doc/ip-tos.txt
				$core_conf->addSipGeneral('tos_video','af41'); // Recommended setting from doc/ip-tos.txt
				$core_conf->addSipGeneral('alwaysauthreject','yes');
				if ($ast_lt_161) {
					$core_conf->addSipGeneral('limitonpeers','yes');
				}
			} else {
				$core_conf->addSipGeneral('tos','0x68'); // This really doesn't do anything with astersk not running as root
			}
			$useragent = $amp_conf['SIPUSERAGENT'] . '-' . getversion() . "($version)";
			$core_conf->addSipGeneral('useragent',$useragent);
			$core_conf->addIaxGeneral('disallow','all');
			$core_conf->addIaxGeneral('allow','ulaw');
			$core_conf->addIaxGeneral('allow','alaw');
			$core_conf->addIaxGeneral('allow','gsm');
			$core_conf->addIaxGeneral('mailboxdetail','yes');
			if ($ast_ge_14) {
				$core_conf->addIaxGeneral('tos','ef'); // Recommended setting from doc/ip-tos.txt
			}

			$fcc = new featurecode($modulename, 'blindxfer');
			$code = $fcc->getCodeActive();
			unset($fcc);
			$core_conf->addFeatureMap('blindxfer',$code);

			$fcc = new featurecode($modulename, 'atxfer');
			$code = $fcc->getCodeActive();
			unset($fcc);
			$core_conf->addFeatureMap('atxfer',$code);

			$fcc = new featurecode($modulename, 'automon');
			$code = $fcc->getCodeActive();
			unset($fcc);
			if ($code != '') {
				$core_conf->addFeatureMap('automon',$code); //references app record
				$core_conf->addApplicationMap('apprecord', $code . ',caller,Macro,one-touch-record', true);

				/* At this point we are not using hints since we have not found a good way to be always
				* consistent on both sides of the channel
				*
				* $ext->addInclude('from-internal-additional', 'device-hints');
				* $device_list = core_devices_list("all", 'full', true);
				* foreach ($device_list as $device) {
				* 	if ($device['tech'] == 'sip' || $device['tech'] == 'iax2') {
				*    $ext->add('device-hints', $code.$device['id'], '', new ext_noop("AutoMixMon Hint for: ".$device['id']));
				*    $ext->addHint('device-hints', $code.$device['id'], "Custom:RECORDING".$device['id']);
				*   }
				* }
				*/
			}

			$fcc = new featurecode($modulename, 'disconnect');
			$code = $fcc->getCodeActive();
			unset($fcc);
			$core_conf->addFeatureMap('disconnect',$code);

			$fcc = new featurecode($modulename, 'pickupexten');
			$code = $fcc->getCodeActive();
			unset($fcc);
			$core_conf->addFeatureGeneral('pickupexten',$code);
		}

		// FeatureCodes
		$fcc = new featurecode($modulename, 'userlogon');
		$fc_userlogon = $fcc->getCodeActive();
		unset($fcc);

		$fcc = new featurecode($modulename, 'userlogoff');
		$fc_userlogoff = $fcc->getCodeActive();
		unset($fcc);

		global $version;
		if(version_compare($version, "12.5", "<")) {
			$fcc = new featurecode($modulename, 'zapbarge');
			$fc_zapbarge = $fcc->getCodeActive();
			unset($fcc);
		}

		$fcc = new featurecode($modulename, 'chanspy');
		$fc_chanspy = $fcc->getCodeActive();
		unset($fcc);

		$fcc = new featurecode($modulename, 'simu_pstn');
		$fc_simu_pstn = $fcc->getCodeActive();
		unset($fcc);

		$fcc = new featurecode($modulename, 'pickup');
		$fc_pickup = $fcc->getCodeActive();
		unset($fcc);

		// Log on / off -- all in one context
		if ($fc_userlogoff != '' || $fc_userlogon != '') {
			$ext->addInclude('from-internal-additional', 'app-userlogonoff'); // Add the include from from-internal

			if ($fc_userlogoff != '') {
				$ext->add('app-userlogonoff', $fc_userlogoff, '', new ext_macro('user-logoff'));
				$ext->add('app-userlogonoff', $fc_userlogoff, 'hook_off', new ext_hangup(''));
			}

			if ($fc_userlogon != '') {
				$ext->add('app-userlogonoff', $fc_userlogon, '', new ext_macro('user-logon'));
				$ext->add('app-userlogonoff', $fc_userlogon, 'hook_on_1', new ext_hangup(''));

				$clen = strlen($fc_userlogon);
				$fc_userlogon = "_$fc_userlogon.";
				$ext->add('app-userlogonoff', $fc_userlogon, '', new ext_macro('user-logon,${EXTEN:'.$clen.'}'));
				$ext->add('app-userlogonoff', $fc_userlogon, 'hook_on_2', new ext_hangup(''));
			}
		}

		// FREEPBX-7280 - macro-dial
		include 'functions.inc/macro-dial.php';

		/* This needs to be before outbound-routes since they can have a wild-card in them
		*
		;------------------------------------------------------------------------
		; [ext-local-confirm]
		;------------------------------------------------------------------------
		; If call confirm is being used in a ringgroup, then calls that do not require confirmation are sent
		; to this extension instead of straight to the device.
		;
		; The sole purpose of sending them here is to make sure we run Macro(auto-confirm) if this
		; extension answers the line. This takes care of clearing the database key that is used to inform
		; other potential late comers that the extension has been answered by someone else.
		;
		; ALERT_INFO is deprecated in Asterisk 1.4 but still used throughout the FreePBX dialplan and
		; usually set by dialparties.agi. This allows inheritance. Since no dialparties.agi here, set the
		; header if it is set.
		;
		;------------------------------------------------------------------------
		*/
		$context = 'ext-local-confirm';
		$ext->addInclude('from-internal-additional', $context); // Add the include from from-internal
		$exten = '_LC-.';
		$ext->add($context, $exten, '', new ext_noop_trace('IN '.$context.' with - RT: ${RT}, RG_IDX: ${RG_IDX}'));
		//dont allow inbound callers to transfer around inside the system
		$ext->add($context, $exten, '', new ext_execif('$["${DIRECTION}" = "INBOUND"]', 'Set', 'DIAL_OPTIONS=${STRREPLACE(DIAL_OPTIONS,T)}I'));
		$ext->add($context, $exten, '', new ext_dial('${DB(DEVICE/${EXTEN:3}/dial)}', '${RT},${DIAL_OPTIONS}M(auto-confirm^${RG_IDX})b(func-apply-sipheaders^s^1)'));

		/* This needs to be before outbound-routes since they can have a wild-card in them
		*
		;------------------------------------------------------------------------
		; [findmefollow-ringallv2]
		;------------------------------------------------------------------------
		; This context, to be included in from-internal, implements the PreRing part of findmefollow
		; as well as the GroupRing part. It also communicates between the two so that if DND is set
		; on the primary extension, and mastermode is enabled, then the other extensions will not ring
		;
		;------------------------------------------------------------------------
		*/
		$context = 'findmefollow-ringallv2';
		$ext->addInclude('from-internal-additional', $context); // Add the include from from-internal
		$exten = '_FMPR-.';

		$fm_dnd = $amp_conf['AST_FUNC_SHARED'] ? 'SHARED(FM_DND,${FMUNIQUE})' : 'DB(FM/DND/${FMGRP}/${FMUNIQUE})';

		$ext->add($context, $exten, '', new ext_nocdr(''));
		$ext->add($context, $exten, '', new ext_noop_trace('In FMPR ${FMGRP} with ${EXTEN:5}'));
		$ext->add($context, $exten, '', new ext_set('RingGroupMethod',''));
		$ext->add($context, $exten, '', new ext_set('USE_CONFIRMATION',''));
		$ext->add($context, $exten, '', new ext_set('RINGGROUP_INDEX',''));
		$ext->add($context, $exten, '', new ext_macro('simple-dial','${EXTEN:5},${FMREALPRERING}'));
		$ext->add($context, $exten, '', new ext_execif('$["${DIALSTATUS}" = "BUSY"]', 'Set', "$fm_dnd=DND"));
		$ext->add($context, $exten, '', new ext_noop_trace('Ending FMPR ${FMGRP} with ${EXTEN:5} and dialstatus ${DIALSTATUS}'));
		$ext->add($context, $exten, '', new ext_hangup(''));

		$exten = '_FMGL-.';
		$ext->add($context, $exten, '', new ext_nocdr(''));
		$ext->add($context, $exten, '', new ext_noop_trace('In FMGL ${FMGRP} with ${EXTEN:5}'));

		$ext->add($context, $exten, '', new ext_set('ENDLOOP', '$[${EPOCH} + ${FMPRERING} + 2]'));
		$ext->add($context, $exten, 'start', new ext_gotoif('$["${' .$fm_dnd. '}" = "DND"]','dodnd'));
		$ext->add($context, $exten, '', new ext_wait('1'));
		$ext->add($context, $exten, '', new ext_noop_trace('FMGL wait loop: ${EPOCH} / ${ENDLOOP}', 6));
		$ext->add($context, $exten, '', new ext_gotoif('$[${EPOCH} < ${ENDLOOP}]','start'));
		if ($amp_conf['AST_FUNC_SHARED']) {
			$ext->add($context, $exten, '', new ext_set($fm_dnd, ''));
		} else {
			$ext->add($context, $exten, '', new ext_dbdel($fm_dnd));
		}
		$ext->add($context, $exten, 'dodial', new ext_macro('dial','${FMGRPTIME},${DIAL_OPTIONS},${EXTEN:5}'));
		$ext->add($context, $exten, '', new ext_noop_trace('Ending FMGL ${FMGRP} with ${EXTEN:5} and dialstatus ${DIALSTATUS}'));
		$ext->add($context, $exten, '', new ext_hangup(''));
		// n+10(dodnd):
		if ($amp_conf['AST_FUNC_SHARED']) {
			$ext->add($context, $exten, 'dodnd', new ext_set($fm_dnd, ''), 'n', 10);
		} else {
			$ext->add($context, $exten, 'dodnd', new ext_dbdel($fm_dnd), 'n', 10);
		}
		$ext->add($context, $exten, '', new ext_gotoif('$["${FMPRIME}" = "FALSE"]','dodial'));
		$ext->add($context, $exten, '', new ext_noop_trace('Got DND in FMGL ${FMGRP} with ${EXTEN:5} in ${RingGroupMethod} mode, aborting'));
		$ext->add($context, $exten, '', new ext_hangup(''));


		// Call pickup using app_pickup - Note that '**xtn' is hard-coded into the GXPs and SNOMs as a number to dial
		// when a user pushes a flashing BLF.
		//
		// We need to add ringgroups to this so that if an extension is part of a ringgroup, we can try to pickup that
		// extension by trying the ringgoup which is what the pickup application is going to respond to.
		//
		// NOTICE: this may be confusing, we check if this is a BRI build of Asterisk and use dpickup instead of pickup
		//         if it is. So we simply assign the variable $ext_pickup which one it is, and use that variable when
		//         creating all the extensions below. So those are "$ext_pickup" on purpose!
		//
		if ($fc_pickup != '' && $ast_ge_14) {
			$ext->addInclude('from-internal-additional', 'app-pickup');
			$fclen = strlen($fc_pickup);
			$ext_pickup = (strstr($engineinfo['raw'], 'BRI')) ? 'ext_dpickup' : 'ext_pickup';

			$fcc = new featurecode('paging', 'intercom-prefix');
			$intercom_code = $fcc->getCodeActive();
			unset($fcc);

			$picklist = '${EXTEN:'.$fclen.'}';
			$picklist .= '&${EXTEN:'.$fclen.'}@PICKUPMARK';
			$ext->add('app-pickup', "_$fc_pickup.", '', new ext_macro('user-callerid'));
			$ext->add('app-pickup', "_$fc_pickup.", '', new ext_set('PICKUP_EXTEN','${AMPUSER}'));
			$ext->add('app-pickup', "_$fc_pickup.", '', new $ext_pickup($picklist));
			$ext->add('app-pickup', "_$fc_pickup.", '', new ext_hangup(''));

			if ($intercom_code != '') {
				$len = strlen($fc_pickup.$intercom_code);
				$picklist  = '${EXTEN:'.$len.'}';
				$picklist .= '&${EXTEN:'.$len.'}@PICKUPMARK';
				$ext->add('app-pickup', "_{$fc_pickup}{$intercom_code}.", '', new ext_macro('user-callerid'));
				$ext->add('app-pickup', "_{$fc_pickup}{$intercom_code}.", '', new ext_set('PICKUP_EXTEN','${AMPUSER}'));
				$ext->add('app-pickup', "_{$fc_pickup}{$intercom_code}.", '', new $ext_pickup($picklist));
				$ext->add('app-pickup', "_{$fc_pickup}{$intercom_code}.", '', new ext_hangup(''));
			}
			// In order to do call pickup in ringgroups, we will need to try the ringgoup number
			// when doing call pickup for that ringgoup so we must see who is a member of what ringgroup
			// and then generate the dialplan
			//
			$rg_members = array();
			if (function_exists('ringgroups_list')) {
				$rg_list = ringgroups_list(true);
				foreach ($rg_list as $item) {
					$thisgrp = ringgroups_get($item['grpnum']);
					$grpliststr = $thisgrp['grplist'];
					$grplist = explode("-", $grpliststr);
					foreach ($grplist as $exten) {
						if (strpos($exten,"#") === false) {
							$rg_members[$exten][] = $item['grpnum'];
						}
					}
				}
			}
			// Now we have a hash of extensions and what ringgoups they are members of
			// so we need to generate the callpickup dialplan for these specific extensions
			// to try the ringgoup.
			foreach ($rg_members as $exten => $grps) {
				$picklist  = $exten;
				$picklist .= '&'.$exten.'@PICKUPMARK';

				foreach ($grps as $grp) {
					$picklist .= '&'.$grp.'@from-internal';
					$picklist .= '&'.$grp.'@from-internal-xfer';
					$picklist .= '&'.$grp.'@ext-group';
				}
				$ext->add('app-pickup', "$fc_pickup".$exten, '', new ext_macro('user-callerid'));
				$ext->add('app-pickup', "$fc_pickup".$exten, '', new ext_set('PICKUP_EXTEN','${AMPUSER}'));
				$ext->add('app-pickup', "$fc_pickup".$exten, '', new $ext_pickup($picklist));
				$ext->add('app-pickup', "$fc_pickup".$exten, '', new ext_hangup(''));
				if ($intercom_code != '') {
					$ext->add('app-pickup', "$fc_pickup".$intercom_code.$exten, '', new ext_macro('user-callerid'));
					$ext->add('app-pickup', "$fc_pickup".$intercom_code.$exten, '', new ext_set('PICKUP_EXTEN','${AMPUSER}'));
					$ext->add('app-pickup', "$fc_pickup".$intercom_code.$exten, '', new $ext_pickup($picklist));
					$ext->add('app-pickup', "$fc_pickup".$intercom_code.$exten, '', new ext_hangup(''));
				}
			}
		} elseif ($fc_pickup != '') {
			$ext->addInclude('from-internal-additional', 'app-pickup');
			$fclen = strlen($fc_pickup);
			$ext_pickup = (strstr($engineinfo['raw'], 'BRI')) ? 'ext_dpickup' : 'ext_pickup';

			$fcc = new featurecode('paging', 'intercom-prefix');
			$intercom_code = $fcc->getCodeActive();
			unset($fcc);


			$ext->add('app-pickup', "_$fc_pickup.", '', new ext_NoOp('Attempt to Pickup ${EXTEN:'.$fclen.'} by ${CALLERID(num)}'));
			$ext->add('app-pickup', "_$fc_pickup.", '', new $ext_pickup('${EXTEN:'.$fclen.'}'));
			$ext->add('app-pickup', "_$fc_pickup.", '', new $ext_pickup('${EXTEN:'.$fclen.'}@ext-local'));
			$ext->add('app-pickup', "_$fc_pickup.", '', new $ext_pickup('${EXTEN:'.$fclen.'}@from-internal'));
			$ext->add('app-pickup', "_$fc_pickup.", '', new $ext_pickup('${EXTEN:'.$fclen.'}@from-internal-xfer'));
			$ext->add('app-pickup', "_$fc_pickup.", '', new $ext_pickup('${EXTEN:'.$fclen.'}@from-did-direct'));
			$ext->add('app-pickup', "_$fc_pickup.", '', new $ext_pickup('FMPR-${EXTEN:'.$fclen.'}'));
			$ext->add('app-pickup', "_$fc_pickup.", '', new $ext_pickup('LC-${EXTEN:'.$fclen.'}@from-internal'));
			$ext->add('app-pickup', "_$fc_pickup.", '', new $ext_pickup('LC-${EXTEN:'.$fclen.'}@from-internal-xfer'));
			$ext->add('app-pickup', "_$fc_pickup.", '', new $ext_pickup('FMPR-${EXTEN:'.$fclen.'}@from-internal'));
			$ext->add('app-pickup', "_$fc_pickup.", '', new $ext_pickup('FMPR-${EXTEN:'.$fclen.'}@from-internal-xfer'));
			$ext->add('app-pickup', "_$fc_pickup.", '', new $ext_pickup('FMPR-${EXTEN:'.$fclen.'}@from-did-direct'));
			if ($intercom_code != '') {
				$ext->add('app-pickup', "_{$fc_pickup}{$intercom_code}.", '', new $ext_pickup('${EXTEN:'.strlen($fc_pickup.$intercom_code).'}'));
				$ext->add('app-pickup', "_{$fc_pickup}{$intercom_code}.", '', new $ext_pickup('${EXTEN:'.strlen($fc_pickup.$intercom_code).'}@from-internal'));
				$ext->add('app-pickup', "_{$fc_pickup}{$intercom_code}.", '', new $ext_pickup('${EXTEN:'.strlen($fc_pickup.$intercom_code).'}@from-internal-xfer'));
				$ext->add('app-pickup', "_{$fc_pickup}{$intercom_code}.", '', new $ext_pickup('${EXTEN:'.strlen($fc_pickup.$intercom_code).'}@from-did-direct'));
				$ext->add('app-pickup', "_{$fc_pickup}{$intercom_code}.", '', new $ext_pickup('FMPR-${EXTEN:'.strlen($fc_pickup.$intercom_code).'}'));
				$ext->add('app-pickup', "_{$fc_pickup}{$intercom_code}.", '', new $ext_pickup('FMPR-${EXTEN:'.strlen($fc_pickup.$intercom_code).'}@from-internal'));
				$ext->add('app-pickup', "_{$fc_pickup}{$intercom_code}.", '', new $ext_pickup('FMPR-${EXTEN:'.strlen($fc_pickup.$intercom_code).'}@from-internal-xfer'));
				$ext->add('app-pickup', "_{$fc_pickup}{$intercom_code}.", '', new $ext_pickup('FMPR-${EXTEN:'.strlen($fc_pickup.$intercom_code).'}@from-did-direct'));
			}
			$ext->add('app-pickup', "_$fc_pickup.", '', new ext_hangup(''));
			// In order to do call pickup in ringgroups, we will need to try the ringgoup number
			// when doing call pickup for that ringgoup so we must see who is a member of what ringgroup
			// and then generate the dialplan
			//
			$rg_members = array();
			if (function_exists('ringgroups_list')) {
				$rg_list = ringgroups_list(true);
				foreach ($rg_list as $item) {
					$thisgrp = ringgroups_get($item['grpnum']);
					$grpliststr = $thisgrp['grplist'];
					$grplist = explode("-", $grpliststr);
					foreach ($grplist as $exten) {
						if (strpos($exten,"#") === false) {
							$rg_members[$exten][] = $item['grpnum'];
						}
					}
				}
			}
			// Now we have a hash of extensions and what ringgoups they are members of
			// so we need to generate the callpickup dialplan for these specific extensions
			// to try the ringgoup.
			foreach ($rg_members as $exten => $grps) {
				$ext->add('app-pickup', "$fc_pickup".$exten, '', new $ext_pickup($exten));
				$ext->add('app-pickup', "$fc_pickup".$exten, '', new $ext_pickup($exten.'@ext-local'));
				$ext->add('app-pickup', "$fc_pickup".$exten, '', new $ext_pickup($exten.'@from-internal'));
				$ext->add('app-pickup', "$fc_pickup".$exten, '', new $ext_pickup($exten.'@from-internal-xfer'));
				$ext->add('app-pickup', "$fc_pickup".$exten, '', new $ext_pickup($exten.'@from-did-direct'));
				$ext->add('app-pickup', "$fc_pickup".$exten, '', new $ext_pickup('LC-'.$exten.'@from-internal'));
				$ext->add('app-pickup', "$fc_pickup".$exten, '', new $ext_pickup('LC-'.$exten.'@from-internal-xfer'));
				$ext->add('app-pickup', "$fc_pickup".$exten, '', new $ext_pickup('FMPR-'.$exten));
				$ext->add('app-pickup', "$fc_pickup".$exten, '', new $ext_pickup('FMPR-'.$exten.'@from-internal'));
				$ext->add('app-pickup', "$fc_pickup".$exten, '', new $ext_pickup('FMPR-'.$exten.'@from-internal-xfer'));
				$ext->add('app-pickup', "$fc_pickup".$exten, '', new $ext_pickup('FMPR-'.$exten.'@from-did-direct'));
				foreach ($grps as $grp) {
					$ext->add('app-pickup', "$fc_pickup".$exten, '', new $ext_pickup($grp.'@from-internal'));
					$ext->add('app-pickup', "$fc_pickup".$exten, '', new $ext_pickup($grp.'@from-internal-xfer'));
					$ext->add('app-pickup', "$fc_pickup".$exten, '', new $ext_pickup($grp.'@ext-group'));
				}
				$ext->add('app-pickup', "$fc_pickup".$exten, '', new ext_hangup(''));
			}
		}


		// zap barge
		global $version;
		if (version_compare($version, "12.5", "<") && $fc_zapbarge != '') {
			$ext->addInclude('from-internal-additional', 'app-zapbarge'); // Add the include from from-internal

			$ext->add('app-zapbarge', $fc_zapbarge, '', new ext_macro('user-callerid'));
			$ext->add('app-zapbarge', $fc_zapbarge, '', new ext_setvar('GROUP()','${CALLERID(number)}'));
			$ext->add('app-zapbarge', $fc_zapbarge, '', new ext_answer(''));
			$ext->add('app-zapbarge', $fc_zapbarge, '', new ext_wait(1));
			$ext->add('app-zapbarge', $fc_zapbarge, '', new ext_zapbarge(''));
			$ext->add('app-zapbarge', $fc_zapbarge, '', new ext_hangup(''));
		}

		// chan spy
		if ($fc_chanspy != '') {
			$ext->addInclude('from-internal-additional', 'app-chanspy'); // Add the include from from-internal
			$ext->add('app-chanspy', $fc_chanspy, '', new ext_macro('user-callerid'));
			$ext->add('app-chanspy', $fc_chanspy, '', new ext_answer(''));
			$ext->add('app-chanspy', $fc_chanspy, '', new ext_wait(1));
			$ext->add('app-chanspy', $fc_chanspy, '', new ext_chanspy(''));
			$ext->add('app-chanspy', $fc_chanspy, '', new ext_hangup(''));
		}

		// Simulate Inbound call
		if ($fc_simu_pstn != '') {
			$ext->addInclude('from-internal-additional', 'ext-test'); // Add the include from from-internal
			$ext->add('ext-test', $fc_simu_pstn, '', new ext_macro('user-callerid'));
			if (ctype_digit($fc_simu_pstn)) {
				$ext->add('ext-test', $fc_simu_pstn, '', new ext_goto('1', '${EXTEN}', 'from-pstn'));
			} else {
				$ext->add('ext-test', $fc_simu_pstn, '', new ext_goto('1', 's', 'from-pstn'));
			}
			$ext->add('ext-test', 'h', '', new ext_macro('hangupcall'));
		}

		$ext->addInclude('ext-did', 'ext-did-0001'); // Add the include from from-internal
		$ext->addInclude('ext-did', 'ext-did-0002'); // Add the include from from-internal
		$ext->add('ext-did', 'foo','', new ext_noop('bar'));

		/* inbound routing extensions */
		$didlist = \FreePBX::Core()->getAllDIDs();
		if(is_array($didlist)){
			$catchall = false;
			$catchall_context='ext-did-catchall';
			foreach($didlist as $item) {
				if (trim($item['destination']) == '') {
					continue;
				}
				$exten = trim($item['extension']);
				$cidnum = trim($item['cidnum']);

				// If the user put in just a cid number for routing, we add _. pattern to catch
				// all DIDs with that CID number. Asterisk will complain about _. being dangerous
				// but we don't want to limit this to just numberic as someone may be trying to
				// route a non-numeric did
				//
				$cidroute = false;
				if ($cidnum != '' && $exten == '') {
					$exten = '_.';
					$pricid = ($item['pricid']) ? true:false;
					$cidroute = true;
				} else if (($cidnum != '' && $exten != '') || ($cidnum == '' && $exten == '')) {
					$pricid = true;
				} else {
					$pricid = false;
				}
				$context = ($pricid) ? "ext-did-0001":"ext-did-0002";

				$exten = (($exten == "")?"s":$exten);
				$exten = $exten.(($cidnum == "")?"":"/".$cidnum); //if a CID num is defined, add it
				$ext->add($context, $exten, '', new ext_setvar('__DIRECTION',($amp_conf['INBOUND_NOTRANS'] ? 'INBOUND' : '')));
				if ($cidroute) {
					$ext->add($context, $exten, '', new ext_setvar('__FROM_DID','${EXTEN}'));
					$ext->add($context, $exten, '', new ext_goto('1','s'));
					$exten = "s/$cidnum";
					$ext->add($context, $exten, '', new ext_execif('$["${FROM_DID}" = ""]','Set','__FROM_DID=${EXTEN}'));
				} else {
					if ($exten == 's'){
						$ext->add($context, $exten, '', new ext_execif('$["${FROM_DID}" = ""]','Set','__FROM_DID=${EXTEN}'));
					}else{
						$ext->add($context, $exten, '', new ext_setvar('__FROM_DID','${EXTEN}'));
					}
				}
				// always set CallerID name
				$ext->add($context, $exten, '', new ext_set('CDR(did)','${FROM_DID}'));
				$ext->add($context, $exten, '', new ext_execif('$[ "${CALLERID(name)}" = "" ] ','Set','CALLERID(name)=${CALLERID(num)}'));

				// if VQA present and configured call it
				if ($amp_conf['AST_APP_VQA'] && $amp_conf['DITECH_VQA_INBOUND']) {
					$ext->add($context, $exten, '', new ext_vqa($amp_conf['DITECH_VQA_INBOUND']));
				}

				// Always set __MOHCLASS and moh.
				if (empty($item['mohclass'])) {
					// Should never happen
					$item['mohclass'] = "default";
				}
				if($item['mohclass'] != "default") {
					$ext->add($context, $exten, '', new ext_setmusiconhold($item['mohclass']));
					$ext->add($context, $exten, '', new ext_setvar('__MOHCLASS',$item['mohclass']));
				} else {
					$ext->add($context, $exten, '', new ext_setvar('__MOHCLASS',""));
				}

				// If we require RINGING, signal it as soon as we enter.
				if ($item['ringing'] === "CHECKED") {
					$ext->add($context, $exten, '', new ext_ringing(''));
					$ext->add($context, $exten, '', new ext_setvar('__RINGINGSENT','TRUE'));
				}

				//Block collect Calls
				if ($item['reversal'] === "CHECKED") {
					$ext->add($context, $exten, '', new ext_setvar('__REVERSAL_REJECT','TRUE'));
				}else{
					$ext->add($context, $exten, '', new ext_setvar('__REVERSAL_REJECT','FALSE'));
				}
				$ext->add($context, $exten, '', new ext_gotoif('$["${REVERSAL_REJECT}"!="TRUE"]','post-reverse-charge'));
				$ext->add($context, $exten, '', new ext_gotoif('$["${CHANNEL(reversecharge)}"="1"]','macro-hangupcall'));
				$ext->add($context, $exten, 'post-reverse-charge', new ext_noop());

				if ($item['delay_answer']) {
					$ext->add($context, $exten, '', new ext_wait($item['delay_answer']));
				}

				if ($exten == "s") {
					//if the exten is s, then also make a catchall for undefined DIDs
					$catchaccount = "_.".(empty($cidnum)?"":"/".$cidnum);
					if ($catchaccount =="_." && ! $catchall) {
						$catchall = true;
						$ext->add($catchall_context, $catchaccount, '', new ext_NoOp('Catch-All DID Match - Found ${EXTEN} - You probably want a DID for this.'));
						$ext->add($catchall_context, $catchaccount, '', new ext_log('WARNING', 'Friendly Scanner from ${CUT(CUT(SIP_HEADER(Via), ,2),:,1)}'));
						$ext->add($catchall_context, $catchaccount, '', new ext_set('__FROM_DID', '${EXTEN}'));
						$ext->add($catchall_context, $catchaccount, '', new ext_goto('1','s','ext-did'));
					}
				}
				if ($item['privacyman'] == "1") {
					$ext->add($context, $exten, '', new ext_macro('privacy-mgr',$item['pmmaxretries'].','.$item['pmminlength']));
				} else {
					// if privacymanager is used, this is not necessary as it will not let blocked/anonymous calls through
					// otherwise, we need to save the caller presence to set it properly if we forward the call back out the pbx
					// note - the indirect table could go away as of 1.4.20 where it is fixed so that SetCallerPres can take
					// the raw format.
					//
					$ext->add($context, $exten, '', new ext_setvar('__CALLINGNAMEPRES_SV','${CALLERID(name-pres)}'));
					$ext->add($context, $exten, '', new ext_setvar('__CALLINGNUMPRES_SV','${CALLERID(num-pres)}'));
					$ext->add($context, $exten, '', new ext_setcallernamepres('allowed_not_screened'));
					$ext->add($context, $exten, '', new ext_setcallernumpres('allowed_not_screened'));
				}
				if (!empty($item['alertinfo'])) {
					$ext->add($context, $exten, '', new ext_setvar("__ALERT_INFO", str_replace(';', '\;', $item['alertinfo'])));
				}
				$ext->add($context,$exten,'did-cid-hook', new ext_noop('CallerID Entry Point'));
				if (!empty($item['grppre'])) {
					$ext->add($context, $exten, '', new ext_macro('prepend-cid', $item['grppre']));
				}

				//the goto destination
				// destination field in 'incoming' database is backwards from what ext_goto expects
				$goto_context = strtok($item['destination'],',');
				$goto_exten = strtok(',');
				$goto_pri = strtok(',');
				$ext->add($context, $exten, 'dest-ext', new ext_goto($goto_pri,$goto_exten,$goto_context));

			}
			// If there's not a catchall, make one with an error message
			if (!$catchall) {
				$ext->add($catchall_context, 's', '', new ext_noop("No DID or CID Match"));
				$ext->add($catchall_context, 's', 'a2', new ext_answer(''));
				$ext->add($catchall_context, 's', '', new ext_log('WARNING', 'Friendly Scanner from ${CUT(CUT(SIP_HEADER(Via), ,2),:,1)}'));
				$ext->add($catchall_context, 's', '', new ext_wait('2'));
				$ext->add($catchall_context, 's', '', new ext_playback('ss-noservice'));
				$ext->add($catchall_context, 's', '', new ext_sayalpha('${FROM_DID}'));
				$ext->add($catchall_context, 's', '', new ext_hangup(''));
				$ext->add($catchall_context, '_.', '', new ext_setvar('__FROM_DID', '${EXTEN}'));
				$ext->add($catchall_context, '_.', '', new ext_noop('Received an unknown call with DID set to ${EXTEN}'));
				$ext->add($catchall_context, '_.', '', new ext_goto('a2','s'));
				$ext->add($catchall_context, 'h', '', new ext_hangup(''));
			}

		}

		// Now create macro-from-zaptel-nnn or macro-from-dahdi-nnn for each defined channel to route it to the DID routing
		// Send it to from-trunk so it is handled as other dids would be handled.
		//
		// to this point we have both zap and dahdi configuration options. At generation though they can't co-exists. If compatibility
		// mode then it's still from-zaptel, otherwise it is which ever is present. We cant use ast_with_dahdi() (chan_dadi) because
		// it is for detection with compatibility mode. We need to actually determine if chan_dahdi is present or not at this point
		//
		if (!isset($chan_dahdi_loaded)) {
			if (isset($astman) && $astman->connected()) {
				$chan_dahdi_loaded = $astman->mod_loaded('chan_dahdi');
			}
		}
		foreach (core_dahdichandids_list() as $row) {
			$channel = $row['channel'];
			$did     = $row['did'];

			$this_context = "macro-from-dahdi-$channel";
			$ext->add($this_context, 's', '', new ext_noop('Entering '.$this_context.' with DID = ${DID} and setting to: '.$did));
			$ext->add($this_context, 's', '', new ext_setvar('__FROM_DID',$did));
			$ext->add($this_context, 's', '', new ext_goto('1',$did,'from-trunk'));
		}

		/* user extensions */
		$ext->addInclude('from-internal-additional','ext-local');

		// If running in Dynamic mode, this will insert the hints through an Asterisk #exec call.
		// which require "execincludes=yes" to be set in the [options] section of asterisk.conf
		//

		$fcc = new featurecode('paging', 'intercom-prefix');
		$intercom_code = $fcc->getCodeActive();
		unset($fcc);

		$intercom_code = ($intercom_code == '') ? 'nointercom' : $intercom_code;

		$fcc = new featurecode('campon', 'toggle');
		$campon_toggle = $fcc->getCodeActive();
		unset($fcc);

		$campon_toggle = ($campon_toggle == '') ? 'nocampon' : $campon_toggle;

		// Pass the code so agi scripts like user_login_logout know to generate hints
		//
		$ext->addGlobal('INTERCOMCODE',$intercom_code);

		if ($amp_conf['DYNAMICHINTS']) {
			if ($amp_conf['USEDEVSTATE'] && function_exists('donotdisturb_get_config')) {
				$add_dnd = 'dnd';
			} else {
				$add_dnd = '';
			}
			$ext->addExec('ext-local',$amp_conf['AMPBIN'].'/generate_hints.php '.$intercom_code.' '.$campon_toggle .' '.$add_dnd);
		}
		$userlist = core_users_list();
		if (is_array($userlist)) {
			foreach($userlist as $item) {
				$exten = \FreePBX::Core()->getUser($item[0]);
				$vm = ((($exten['voicemail'] == "novm") || ($exten['voicemail'] == "disabled") || ($exten['voicemail'] == "")) ? "novm" : $exten['extension']);

				$ext->add('ext-local', $exten['extension'], '', new ext_set('__RINGTIMER', '${IF($["${DB(AMPUSER/'.$exten['extension'].'/ringtimer)}" > "0"]?${DB(AMPUSER/'.$exten['extension'].'/ringtimer)}:${RINGTIMER_DEFAULT})}'));

				$dest_args = ','.($exten['noanswer_dest']==''?'0':'1').','.($exten['busy_dest']==''?'0':'1').','.($exten['chanunavail_dest']==''?'0':'1');
				$ext->add('ext-local', $exten['extension'], '', new ext_macro('exten-vm',$vm.",".$exten['extension'].$dest_args));
				$ext->add('ext-local', $exten['extension'], 'dest', new ext_set('__PICKUPMARK',''));
				if ($exten['noanswer_dest']) {
					if ($exten['noanswer_cid'] != '') {
						$ext->add('ext-local', $exten['extension'], '', new ext_execif('$["${DIALSTATUS}"="NOANSWER"]','Set','CALLERID(name)='.$exten['noanswer_cid'].'${CALLERID(name)}'));
					}
					$ext->add('ext-local', $exten['extension'], '', new ext_gotoif('$["${DIALSTATUS}"="NOANSWER"]',$exten['noanswer_dest']));
				}
				if ($exten['busy_dest']) {
					if ($exten['busy_cid'] != '') {
						$ext->add('ext-local', $exten['extension'], '', new ext_execif('$["${DIALSTATUS}"="BUSY"]','Set','CALLERID(name)='.$exten['busy_cid'].'${CALLERID(name)}'));
					}
					$ext->add('ext-local', $exten['extension'], '', new ext_gotoif('$["${DIALSTATUS}"="BUSY"]',$exten['busy_dest']));
				}
				if ($exten['chanunavail_dest']) {
					if ($exten['chanunavail_cid'] != '') {
						$ext->add('ext-local', $exten['extension'], '', new ext_execif('$["${DIALSTATUS}"="CHANUNAVAIL"]','Set','CALLERID(name)='.$exten['chanunavail_cid'].'${CALLERID(name)}'));
					}
					$ext->add('ext-local', $exten['extension'], '', new ext_gotoif('$["${DIALSTATUS}"="CHANUNAVAIL"]',$exten['chanunavail_dest']));
				}

				if($vm != "novm") {
					// This usually gets called from macro-exten-vm but if follow-me destination need to go this route
					$ext->add('ext-local', $exten['extension'], '', new ext_macro('vm',$vm.',${DIALSTATUS},${IVR_RETVM}'));
					$ext->add('ext-local', $exten['extension'], '', new ext_goto('1','vmret'));
					$ext->add('ext-local', 'vmb'.$exten['extension'], '', new ext_macro('vm',$vm.',BUSY,${IVR_RETVM}'));
					$ext->add('ext-local', 'vmb'.$exten['extension'], '', new ext_goto('1','vmret'));
					$ext->add('ext-local', 'vmu'.$exten['extension'], '', new ext_macro('vm',$vm.',NOANSWER,${IVR_RETVM}'));
					$ext->add('ext-local', 'vmu'.$exten['extension'], '', new ext_goto('1','vmret'));
					$ext->add('ext-local', 'vms'.$exten['extension'], '', new ext_macro('vm',$vm.',NOMESSAGE,${IVR_RETVM}'));
					$ext->add('ext-local', 'vms'.$exten['extension'], '', new ext_goto('1','vmret'));
					$ext->add('ext-local', 'vmi'.$exten['extension'], '', new ext_macro('vm',$vm.',INSTRUCT,${IVR_RETVM}'));
					$ext->add('ext-local', 'vmi'.$exten['extension'], '', new ext_goto('1','vmret'));
				} else {
					// If we return from teh macro, it means we are suppose to return to the IVR
					//
					$ext->add('ext-local', $exten['extension'], '', new ext_goto('1','return','${IVR_CONTEXT}'));
				}

				// Create the hints if running in normal mode
				//
				if (!$amp_conf['DYNAMICHINTS']) {
					$hint = core_hint_get($exten['extension']);
					$dnd_string = ($amp_conf['USEDEVSTATE'] && function_exists('donotdisturb_get_config')) ? "&Custom:DND".$exten['extension'] : '';
					$presence_string = $amp_conf['AST_FUNC_PRESENCE_STATE'] ? ",CustomPresence:".$exten['extension'] : '';
					$hint_string = (!empty($hint) ? $hint : '') . $dnd_string . $presence_string;
					$astman->database_put("AMPUSER/".$exten['extension'],"hint",$hint_string);
					if ($hint_string) {
						//TODO: Lots of hints here. Can this be dynamic? No I dont think so
						$ext->addHint('ext-local', $exten['extension'], $hint_string);
					}
				}

				if ($exten['sipname']) {
					$ext->add('ext-local', $exten['sipname'], '', new ext_goto('1',$item[0],'from-internal'));
				}
			}

			// Now make a special context for the IVR inclusions of local extension dialing so that
			// when people use the Queues breakout ability, and break out to someone's extensions, voicemail
			// works.
			//
			$ivr_context = 'from-did-direct-ivr';
			$sql = "SELECT LENGTH(extension) as len FROM users GROUP BY len";
			$sth = FreePBX::Database()->prepare($sql);
			$sth->execute();
			$rows = $sth->fetchAll(\PDO::FETCH_ASSOC);
			foreach($rows as $row) {
				$ext->add($ivr_context, '_'.str_repeat('X',$row['len']),'', new ext_macro('blkvm-clr'));
				$ext->add($ivr_context, '_'.str_repeat('X',$row['len']),'', new ext_setvar('__NODEST', ''));
				$ext->add($ivr_context, '_'.str_repeat('X',$row['len']),'', new ext_goto('1','${EXTEN}','from-did-direct'));
			}

			$ext->add('ext-local', 'vmret', '', new ext_gotoif('$["${IVR_RETVM}" = "RETURN" & "${IVR_CONTEXT}" != ""]','playret'));
			$ext->add('ext-local', 'vmret', '', new ext_hangup(''));
			$ext->add('ext-local', 'vmret', 'playret', new ext_playback('exited-vm-will-be-transfered&silence/1'));
			$ext->add('ext-local', 'vmret', '', new ext_goto('1','return','${IVR_CONTEXT}'));

			$ext->add('ext-local', 'h', '', new ext_macro('hangupcall'));
		}

		//$ext->addHint('ext-local', "_X.", $hint_string);
		if ($intercom_code != '') {
			$ext->addHint('ext-local', "_".$intercom_code."X.", '${DB(AMPUSER/${EXTEN:'.strlen($intercom_code).'}/hint)}');
		}

		/* Create the from-trunk-tech-chanelid context that can be used for inbound group counting
		* Create the DUNDI macros for DUNDI trunks
		* Create the ext-trunk context for direct trunk dialing TODO: should this be its own module?
		*/
		$trunklist = core_trunks_listbyid();
		if (is_array($trunklist) && count($trunklist)) {

			$tcontext = 'ext-trunk';
			$texten = 'tdial';
			$tcustom = 'tcustom';
			$generate_texten = false;
			$generate_tcustom = false;

			foreach ($trunklist as $trunkprops) {
				if (trim($trunkprops['disabled']) == 'on') {
					continue;
				}
				$trunkgroup = 'OUT_'.$trunkprops['trunkid'];
				switch ($trunkprops['tech']) {
					case 'dundi':
					$macro_name = 'macro-dundi-'.$trunkprops['trunkid'];
					$ext->addSwitch($macro_name,'DUNDI/'.$trunkprops['channelid']);
					$ext->add($macro_name, 's', '', new ext_goto('1','${ARG1}'));

					$trunkcontext  = "from-trunk-".$trunkprops['tech']."-".$trunkprops['channelid'];
					$ext->add($trunkcontext, '_.', '', new ext_set('GROUP()',$trunkgroup));
					$ext->add($trunkcontext, '_.', '', new ext_goto('1','${EXTEN}','from-trunk'));

					$ext->add($tcontext,$trunkprops['trunkid'],'',new ext_set('OUTBOUND_GROUP', $trunkgroup));
					$ext->add($tcontext,$trunkprops['trunkid'],'',new ext_gotoif('$["${OUTMAXCHANS_'.$trunkprops['trunkid'].'}" = ""]', 'nomax'));
					$ext->add($tcontext,$trunkprops['trunkid'],'',new ext_gotoif('$[${GROUP_COUNT('.$trunkgroup.')} >= ${OUTMAXCHANS_${DIAL_TRUNK}}]', 'hangit'));
					$ext->add($tcontext,$trunkprops['trunkid'],'nomax',new ext_execif('$["${CALLINGNAMEPRES_SV}" != ""]', 'Set', 'CALLERPRES(name-pres)=${CALLINGNAMEPRES_SV}'));
					$ext->add($tcontext,$trunkprops['trunkid'],'',new ext_execif('$["${CALLINGNUMPRES_SV}" != ""]', 'Set', 'CALLERPRES(num-pres)=${CALLINGNUMPRES_SV}'));
					$ext->add($tcontext,$trunkprops['trunkid'],'',new ext_set('DIAL_NUMBER','${FROM_DID}'));
					$ext->add($tcontext,$trunkprops['trunkid'],'',new ext_gosubif('$["${PREFIX_TRUNK_'.$trunkprops['trunkid'].'}" != ""]','sub-flp-'.$trunkprops['trunkid'].',s,1'));
					$ext->add($tcontext,$trunkprops['trunkid'],'',new ext_set('OUTNUM', '${OUTPREFIX_${DIAL_TRUNK}}${DIAL_NUMBER}'));  // OUTNUM is the final dial number
					$ext->add($tcontext,$trunkprops['trunkid'],'',new ext_macro('dundi-${DIAL_TRUNK}','${OUTNUM}'));
					$ext->add($tcontext,$trunkprops['trunkid'],'hangit',new ext_hangup());
					break;

					case 'iax':
					$trunkprops['tech'] = 'iax2';
					// fall-through
					case 'pjsip':
					case 'iax2':
					case 'sip':
					$trunkcontext  = "from-trunk-".$trunkprops['tech']."-".$trunkprops['channelid'];
					$ext->add($trunkcontext, '_.', '', new ext_set('GROUP()',$trunkgroup));
					$ext->add($trunkcontext, '_.', '', new ext_goto('1','${EXTEN}','from-trunk'));
					// fall-through
					case 'zap':
					case 'dahdi':
					// PJSip hack. This needs to be re-written, I think.
					if ($trunkprops['tech'] == "pjsip") {
						$ext->add($tcontext, $trunkprops['trunkid'], '', new ext_set('TDIAL_SUFFIX',"@".$trunkprops['channelid']));
						$ext->add($tcontext, $trunkprops['trunkid'], '', new ext_set('TDIAL_STRING',strtoupper($trunkprops['tech'])));
					} else {
						$ext->add($tcontext, $trunkprops['trunkid'], '', new ext_set('TDIAL_STRING',strtoupper($trunkprops['tech']).'/'.$trunkprops['channelid']));
					}
					$trunkcontext  = "from-trunk-".$trunkprops['tech']."-".$trunkprops['channelid'];
					$ext->add($tcontext, $trunkprops['trunkid'], '', new ext_set('DIAL_TRUNK',$trunkprops['trunkid'] ));
					$ext->add($tcontext, $trunkprops['trunkid'], '', new ext_goto('1',$texten,'ext-trunk'));
					$generate_texten = true;
					break;

					// TODO we don't have the OUTNUM until later so fix this...
					case 'custom':
					$dial_string = str_replace('$OUTNUM$','${SS}{OUTNUM}',$trunkprops['channelid']);
					$ext->add($tcontext, $trunkprops['trunkid'], '', new ext_set('SS','$'));
					$ext->add($tcontext, $trunkprops['trunkid'], '', new ext_set('TDIAL_STRING',$dial_string));
					$ext->add($tcontext, $trunkprops['trunkid'], '', new ext_set('DIAL_TRUNK',$trunkprops['trunkid'] ));
					$ext->add($tcontext, $trunkprops['trunkid'], '', new ext_goto('1',$tcustom,'ext-trunk'));
					$generate_tcustom = true;
					break;

					case 'enum':
					// Not Supported
					break;
					default:
				}
			}

			if ($generate_tcustom) {
				$ext->add($tcontext,$tcustom,'',new ext_set('OUTBOUND_GROUP', 'OUT_${DIAL_TRUNK}'));
				$ext->add($tcontext,$tcustom,'',new ext_gotoif('$["${OUTMAXCHANS_${DIAL_TRUNK}}" = ""]', 'nomax'));
				$ext->add($tcontext,$tcustom,'',new ext_gotoif('$[${GROUP_COUNT(OUT_${DIAL_TRUNK})} >= ${OUTMAXCHANS_${DIAL_TRUNK}}]', 'hangit'));
				$ext->add($tcontext,$tcustom,'nomax',new ext_execif('$["${CALLINGNAMEPRES_SV}" != ""]', 'Set', 'CALLERPRES(name-pres)=${CALLINGNAMEPRES_SV}'));
				$ext->add($tcontext,$tcustom,'',new ext_execif('$["${CALLINGNUMPRES_SV}" != ""]', 'Set', 'CALLERPRES(num-pres)=${CALLINGNUMPRES_SV}'));
				$ext->add($tcontext,$tcustom,'',new ext_set('DIAL_NUMBER','${FROM_DID}'));
				$ext->add($tcontext,$tcustom,'',new ext_gosubif('$["${PREFIX_TRUNK_${DIAL_TRUNK}}" != ""]','sub-flp-${DIAL_TRUNK},s,1'));
				$ext->add($tcontext,$tcustom,'',new ext_set('OUTNUM', '${OUTPREFIX_${DIAL_TRUNK}}${DIAL_NUMBER}'));  // OUTNUM is the final dial number

				// Address Security Vulnerability in many earlier versions of Asterisk from an external source tranmitting a
				// malicious CID that can cause overflows in the Asterisk code.
				//
				$ext->add($tcontext, $tcustom, '', new ext_set('CALLERID(number)','${CALLERID(number):0:40}'));
				$ext->add($tcontext, $tcustom, '', new ext_set('CALLERID(name)','${CALLERID(name):0:40}'));

				$ext->add($tcontext,$tcustom,'',new ext_set('DIAL_TRUNK_OPTIONS', '${IF($["${DB_EXISTS(TRUNK/${DIAL_TRUNK}/dialopts)}" = "1"]?${DB_RESULT}:${TRUNK_OPTIONS})}'));
				$ext->add($tcontext,$tcustom,'',new ext_dial('${EVAL(${TDIAL_STRING})}', '${TRUNK_RING_TIMER},${DIAL_TRUNK_OPTIONS}'));
				$ext->add($tcontext,$tcustom,'hangit',new ext_hangup());
			}

			if ($generate_texten) {
				$ext->add($tcontext,$texten,'',new ext_set('OUTBOUND_GROUP', 'OUT_${DIAL_TRUNK}'));
				$ext->add($tcontext,$texten,'',new ext_gotoif('$["${OUTMAXCHANS_${DIAL_TRUNK}}" = ""]', 'nomax'));
				$ext->add($tcontext,$texten,'',new ext_gotoif('$[${GROUP_COUNT(OUT_${DIAL_TRUNK})} >= ${OUTMAXCHANS_${DIAL_TRUNK}}]', 'hangit'));
				$ext->add($tcontext,$texten,'nomax',new ext_execif('$["${CALLINGNAMEPRES_SV}" != ""]', 'Set', 'CALLERPRES(name-pres)=${CALLINGNAMEPRES_SV}'));
				$ext->add($tcontext,$texten,'',new ext_execif('$["${CALLINGNUMPRES_SV}" != ""]', 'Set', 'CALLERPRES(num-pres)=${CALLINGNUMPRES_SV}'));
				$ext->add($tcontext,$texten,'',new ext_set('DIAL_NUMBER','${FROM_DID}'));
				$ext->add($tcontext,$texten,'',new ext_gosubif('$["${PREFIX_TRUNK_${DIAL_TRUNK}}" != ""]','sub-flp-${DIAL_TRUNK},s,1'));
				$ext->add($tcontext,$texten,'',new ext_set('OUTNUM', '${OUTPREFIX_${DIAL_TRUNK}}${DIAL_NUMBER}'));  // OUTNUM is the final dial number

				$ext->add($tcontext,$texten,'',new ext_set('DIAL_TRUNK_OPTIONS', '${IF($["${DB_EXISTS(TRUNK/${DIAL_TRUNK}/dialopts)}" = "1"]?${DB_RESULT}:${TRUNK_OPTIONS})}'));
				$ext->add($tcontext,$texten,'',new ext_dial('${TDIAL_STRING}/${OUTNUM}${TDIAL_SUFFIX}', '${TRUNK_RING_TIMER},${DIAL_TRUNK_OPTIONS}'));
				// Address Security Vulnerability in many earlier versions of Asterisk from an external source tranmitting a
				// malicious CID that can cause overflows in the Asterisk code.
				//
				$ext->add($tcontext, $texten, '', new ext_set('CALLERID(number)','${CALLERID(number):0:40}'));
				$ext->add($tcontext, $texten, '', new ext_set('CALLERID(name)','${CALLERID(name):0:40}'));

				$ext->add($tcontext,$texten,'hangit',new ext_hangup());
			}
		}


		/* dialplan globals */
		// modules should NOT use the globals table to store anything!
		// modules should use $ext->addGlobal("testvar","testval"); in their module_get_config() function instead
		// I'm cheating for core functionality - do as I say, not as I do ;-)

		// Auto add these globals to give access to agi scripts and other needs, unless defined in the global table.
		//
		$amp_conf_globals = array(
			"ASTETCDIR",
			"ASTMODDIR",
			"ASTVARLIBDIR",
			"ASTAGIDIR",
			"ASTSPOOLDIR",
			"ASTRUNDIR",
			"ASTLOGDIR",
			"CWINUSEBUSY",
			"AMPMGRUSER",
			"AMPMGRPASS",
			"ASTMANAGERHOST",
			// Before you get upset about these being exposed to the dialplan,
			// they are ALREADY readable from /etc/amportal.conf - adding them
			// here means that AGI's and Dialplan can connect to the database
			// without resorting to re-parsing amportal.conf.
			"AMPDBENGINE",
			"AMPDBHOST",
			"AMPDBNAME",
			"AMPDBUSER",
			"AMPDBPASS",
			"AMPDBFILE",

			// Used to be globals migrated to freepbx_conf
			"VMX_CONTEXT",
			"VMX_PRI",
			"VMX_TIMEDEST_CONTEXT",
			"VMX_TIMEDEST_EXT",
			"VMX_TIMEDEST_PRI",
			"VMX_LOOPDEST_CONTEXT",
			"VMX_LOOPDEST_EXT",
			"VMX_LOOPDEST_PRI",
		);

		$sql = "SELECT * FROM globals";
		$globals = sql($sql,"getAll",DB_FETCHMODE_ASSOC);
		foreach($globals as $global) {
			$value = $global['value'];

			// Ticket # 5477 Create a default value that can't be polluted
			if ($global['variable'] == 'RINGTIMER') {
				$ext->addGlobal('RINGTIMER_DEFAULT', $value);
				continue;
			}
			$ext->addGlobal($global['variable'],$value);

			// now if for some reason we have a variable in the global table
			// that is in our $amp_conf_globals list, then remove it so we
			// don't duplicate, the sql table will take precedence
			//
			if (array_key_exists($global['variable'],$amp_conf_globals)) {
				$rm_keys = array_keys($amp_conf_globals,$global['variable']);
				foreach ($rm_keys as $index) {
					unset($amp_conf_globals[$index]);
				}
			}
		}
		foreach ($amp_conf_globals as $global) {
			if (isset($amp_conf[$global])) {
				$value = $amp_conf[$global];
				if ($value === true || $value === false) {
					$value = ($value) ? 'true':'false';
				}
				$ext->addGlobal($global, $value);
			}
		}

		// Put the MIXMON_DIR, it needs a trailing / so is special cased here
		$mixmon_dir = $amp_conf['MIXMON_DIR'] != '' ? $amp_conf['MIXMON_DIR'].'/' : '';
		$ext->addGlobal('MIXMON_DIR', $mixmon_dir);
		//out("Added to globals: MIXMON_DIR = $mixmon_dir");

		// Add some globals that are used by the dialplan
		//
		$add_globals = array(
			'MIXMON_POST' => 'MIXMON_POST',
			'DIAL_OPTIONS' => 'DIAL_OPTIONS',
			'TRUNK_OPTIONS' => 'TRUNK_OPTIONS',
			'TRUNK_RING_TIMER' => 'TRUNK_RING_TIMER',
			'MIXMON_FORMAT' => 'MIXMON_FORMAT',
			'REC_POLICY' => 'REC_POLICY',
			'RINGTIMER' => 'RINGTIMER_DEFAULT',
			'TRANSFER_CONTEXT' => 'TRANSFER_CONTEXT',
		);
		foreach ($add_globals as $g => $v) {
			$ext->addGlobal($v, $amp_conf[$g]);
			//out("Added to globals: $v = ".$amp_conf[$g]);
		}
		unset($add_globals);

		// Put the asterisk version in a global for agi etc.
		$ext->addGlobal('ASTVERSION', $version);
		// Put the use of chan_dahdi in a global for dialparties
		$ext->addGlobal('ASTCHANDAHDI', $chan_dahdi ? '1' : '0');
		// Create constant NULL in globals
		$ext->addGlobal('NULL', '""');

		// Now let's create the required globals for the trunks so outbound routes work. These used to
		// be stored in the globals table but are not generated by retrieve conf and pulled from the
		// trunks table
		//
		$sqlstr = "
		SELECT `trunkid`, `tech`, `outcid`, `keepcid`, `maxchans`, `failscript`, `dialoutprefix`, `channelid`, `disabled`
		FROM `trunks` ORDER BY `trunkid`
		";
		$trunks = sql($sqlstr,"getAll",DB_FETCHMODE_ASSOC);
		$trunk_hash = core_trunks_list_dialrules();

		// $has_keepcid_cnum is used when macro-outbound-callerid is generated to determine if we need to insert the
		// final execif() statement so it is important to be set before then and here
		//
		$has_keepcid_cnum = false;
		foreach ($trunks as $trunk) {
			$tid = $trunk['trunkid'];
			$tech = strtoupper($trunk['tech']);
			if ($tech == 'IAX') {
				$tech = 'IAX2';
			} elseif ($tech == 'ZAP' && $chan_dahdi) {
				$tech = 'DAHDI';
			}
			if ($tech == 'CUSTOM') {
				$ext->addGlobal('OUT_'.$tid, 'AMP:'.trim($trunk['channelid']));
			} elseif ($tech != 'PJSIP') {
				$ext->addGlobal('OUT_'.$tid, $tech."/".$trunk['channelid']);
			} else {
				$ext->addGlobal('OUT_'.$tid, $tech);
				$ext->addGlobal('OUT_'.$tid.'_SUFFIX', '@'.$trunk['channelid']);
			}

			$ext->addGlobal('OUTCID_'.$tid,      $trunk['outcid']);
			$ext->addGlobal('OUTMAXCHANS_'.$tid, $trunk['maxchans']);
			$ext->addGlobal('OUTFAIL_'.$tid,     $trunk['failscript']);
			$ext->addGlobal('OUTPREFIX_'.$tid,   $trunk['dialoutprefix']);
			$ext->addGlobal('OUTDISABLE_'.$tid,  $trunk['disabled']);
			$ext->addGlobal('OUTKEEPCID_'.$tid,  $trunk['keepcid']);
			$ext->addGlobal('FORCEDOUTCID_'.$tid, ($trunk['keepcid'] == 'all' ? $trunk['outcid'] : ""));
			if ($trunk['keepcid'] == 'cnum') {
				$has_keepcid_cnum = true;
			}

			// Generate PREFIX_TRUNK_$tid even if 0 since globals will persist and cause crashes
			if (isset($trunk_hash[$tid]) && count($trunk_hash)) {
				$patterns = $trunk_hash[$tid];
				// First, generate the global referencing how many there are
				$ext->addGlobal("PREFIX_TRUNK_$tid",count($patterns));

				$context = 'sub-flp-'.$tid;
				$target = 'TARGET_FLP_'.$tid;
				$exten = 's';
				foreach ($patterns as $pattern) {
					$prepend = $pattern['prepend_digits'];
					$offset =  strlen(preg_replace('/(\[[^\]]*\])/','X',$pattern['match_pattern_prefix']));

					$regex_base = $pattern['match_pattern_prefix'].$pattern['match_pattern_pass'];

					// convert asterisk pattern matching into perl regular expression
					//  - two steps, use $ in place of +
					//  - next replace $ with +
					// if you don't do this, the str_replace() walks over itself
					$regex_intermediate = str_replace(
					array(
						'X',
						'Z',
						'N',
						'.',
						'*',
						'+',
					),
					array(
						'[0-9]',
						'[1-9]',
						'[2-9]',
						'[0-9#*\\\$]$',
						'\\\*',
						'\\\$',
					),
					$pattern['match_pattern_prefix'].$pattern['match_pattern_pass']
				);
				$regex = strtr($regex_intermediate,"$","+");

				if ($pattern['prepend_digits'] == '' && $offset == 0) {
					$ext->add($context, $exten, '', new ext_execif('$[${REGEX("^'.$regex.'$" ${DIAL_NUMBER})} = 1]','Return'));
				} else {
					$offset = $offset?':'.$offset:'';
					$ext->add($context, $exten, '', new ext_execif('$[${REGEX("^'.$regex.'$" ${DIAL_NUMBER})} = 1]','Set',$target.'='.$pattern['prepend_digits'].'${DIAL_NUMBER'.$offset.'}'));
					$ext->add($context, $exten, '', new ext_gotoif('$[${LEN(${'.$target.'})} != 0]', 'match'));
				}

			}
			$ext->add($context, $exten, '', new ext_return(''));
			$ext->add($context, $exten, 'match', new ext_set('DIAL_NUMBER','${'.$target.'}'));
			$ext->add($context, $exten, '', new ext_return(''));

		} else {
			$ext->addGlobal("PREFIX_TRUNK_$tid",'');
		}
	}

	/* macro-prepend-cid */
	// prepend a cid and if set to replace previous prepends, do so, otherwise stack them
	//
	$mcontext = 'macro-prepend-cid';
	$exten = 's';

	if ($amp_conf['CID_PREPEND_REPLACE']) {
		$ext->add($mcontext, $exten, '', new ext_gotoif('$["${RGPREFIX}" = ""]', 'REPCID'));
		$ext->add($mcontext, $exten, '', new ext_gotoif('$["${RGPREFIX}" != "${CALLERID(name):0:${LEN(${RGPREFIX})}}"]', 'REPCID'));
		$ext->add($mcontext, $exten, '', new ext_noop_trace('Current RGPREFIX is ${RGPREFIX}....stripping from CallerID'));
		$ext->add($mcontext, $exten, '', new ext_set('CALLERID(name)', '${CALLERID(name):${LEN(${RGPREFIX})}}'));
		$ext->add($mcontext, $exten, '', new ext_set('_RGPREFIX', ''));
	}
	$ext->add($mcontext, $exten, 'REPCID', new ext_set('_RGPREFIX', '${ARG1}'));
	$ext->add($mcontext, $exten, '', new ext_set('CALLERID(name)','${RGPREFIX}${CALLERID(name)}'));



	/* outbound routes */

	$ext->addInclude('from-internal-additional','outbound-allroutes');
	//$ext->add('outbound-allroutes', '_!', '', new ext_macro('user-callerid,SKIPTTL'));
	$ext->add('outbound-allroutes', 'foo', '', new ext_noop('bar'));
	$routes = \FreePBX::Core()->getAllRoutes();
	$trunk_table = core_trunks_listbyid();
	$trunk_type_needed = array(); // track which macros need to be generated
	$delim = $ast_lt_16 ? '|' : ',';
	foreach ($routes as $route) {
		$add_extra_pri1 = array();
		$context = 'outrt-'.$route['route_id'];
		$comment = $route['name'];
		$ext->addSectionComment($context, $comment);

		if (function_exists('timeconditions_timegroups_get_times') && $route['time_group_id'] !== null) {
			$times = timeconditions_timegroups_get_times($route['time_group_id'],true);
			if (is_array($times) && count($times)) {
				foreach ($times as $time) {
					$ext->addInclude('outbound-allroutes',$context.$delim.$time[1],$comment);
				}
			} else {
				$ext->addInclude('outbound-allroutes',$context,$comment);
			}
		} else {
			$ext->addInclude('outbound-allroutes',$context,$comment);
		}

		$patterns = core_routing_getroutepatternsbyid($route['route_id']);
		$trunks = core_routing_getroutetrunksbyid($route['route_id']);

		foreach ($patterns as $pattern) {
			// returns:
			// array('prepend_digits' => $pattern['prepend_digits'], 'dial_pattern' => $exten, 'offset' => $pos);
			//
			$fpattern = core_routing_formatpattern($pattern);
			$exten = $fpattern['dial_pattern'];
			$offset = $fpattern['offset'] == 0 ? '':':'.$fpattern['offset'];

			// This will not get called, but it fixes some things like custom-context or other possible custom uses of these
			// generated contexts that don't have an 'outbound-allroutes' wrapper around them, of course in those cases the
			// CID part of the dialplan will not get executed
			if (!isset($add_extra_pri1[$fpattern['base_pattern']])) {
				$ext->add($context, $fpattern['base_pattern'], '', new ext_macro('user-callerid,LIMIT,EXTERNAL'));
				$add_extra_pri1[$fpattern['base_pattern']] = true;
			}
			if ($fpattern['base_pattern'] != $exten) {
				$ext->add($context, $exten, '', new ext_macro('user-callerid,LIMIT,EXTERNAL'));
			}
			$ext->add($context, $exten, '', new ext_noop_trace(sprintf(_('Calling Out Route: %s'),'${SET(OUTBOUND_ROUTE_NAME='.$route['name'].')}'),1));
			if ($route['dest']) {
				$ext->add($context, $exten, '', new ext_set("ROUTE_CIDSAVE",'${CALLERID(all)}'));
			}

			// Conditionally Add Divesion Header if the call was diverted
			if ($amp_conf['DIVERSIONHEADER']) {
				$ext->add($context, $exten, '', new ext_gosubif('$[${LEN(${FROM_DID})}>0 & "${FROM_DID}"!="s"]','sub-diversion-header,s,1'));
			}

			// if VQA present and configured call it
			if ($amp_conf['AST_APP_VQA'] && $amp_conf['DITECH_VQA_OUTBOUND']) {
				$ext->add($context, $exten, '', new ext_vqa($amp_conf['DITECH_VQA_OUTBOUND']));
			}

			if ($route['emergency_route'] != '') {
				$ext->add($context, $exten, '', new ext_set("EMERGENCYROUTE",$route['emergency_route']));
			}
			if ($route['intracompany_route'] != '') {
				$ext->add($context, $exten, '', new ext_set("INTRACOMPANYROUTE",$route['intracompany_route']));
			}
			if ($route['mohclass'] != '') {
				$ext->add($context, $exten, '', new ext_set("MOHCLASS", '${IF($["${MOHCLASS}"=""]?'.$route['mohclass'].':${MOHCLASS})}' ));
			}
			if ($route['outcid'] != '') {
				if ($route['outcid_mode'] != '') {
					$ext->add($context, $exten, '', new ext_execif('$["${KEEPCID}"!="TRUE" & ${LEN(${TRUNKCIDOVERRIDE})}=0]','Set','TRUNKCIDOVERRIDE='.$route['outcid']));
				} else {
					$ext->add($context, $exten, '', new ext_execif('$["${KEEPCID}"!="TRUE" & ${LEN(${DB(AMPUSER/${AMPUSER}/outboundcid)})}=0 & ${LEN(${TRUNKCIDOVERRIDE})}=0]','Set','TRUNKCIDOVERRIDE='.$route['outcid']));
				}
			}
			$ext->add($context, $exten, '', new ext_set("_NODEST",""));

			$password = $route['password'];
			foreach ($trunks as $trunk_id) {
				if (isset($trunk_table[$trunk_id])) switch(strtolower($trunk_table[$trunk_id]['tech'])) {
					case 'dundi':
					$trunk_macro = 'dialout-dundi';
					break;
					case 'enum':
					$trunk_macro = 'dialout-enum';
					break;
					default:
					$trunk_macro = 'dialout-trunk';
					break;
				}
				$ext->add($context, $exten, '', new ext_macro(
				$trunk_macro, $trunk_id . ',' . $pattern['prepend_digits'] . '${EXTEN' . $offset . '},' . $password . ',' . $trunk_table[$trunk_id]['continue']));
				$password = '';
				$trunk_type_needed['macro-' . $trunk_macro] = true;
			}
			if ($route['dest']) {
				// Put back the saved CID since each trunk attempt screws with it and set KEEPCID since this is
				// a form of forwarding at this point. We could use REALCALLERIDNUM but that doesn't preserve CNAM
				// which may be wiped out and we may want it.
				//
				$ext->add($context, $exten, '', new ext_noop_trace('All trunks failed calling ${EXTEN}, going to destination'));
				$ext->add($context, $exten, '', new ext_set('CALLERID(all)','${ROUTE_CIDSAVE}'));
				$ext->add($context, $exten, '', new ext_set('_KEEPCID','TRUE'));
				$ext->add($context, $exten, '', new ext_goto($route['dest']));
			} else {
				$ext->add($context, $exten, '', new ext_noop_trace('All trunks failed calling ${EXTEN}, playing default congestion'));
				$ext->add($context, $exten, '', new ext_macro("outisbusy"));
			}
		}
		unset($add_extra_pri1);
	}

	general_generate_indications();

	// "blackhole" destinations
	$ext->add('app-blackhole', 'hangup', '', new ext_noop('Blackhole Dest: Hangup'));
	$ext->add('app-blackhole', 'hangup', '', new ext_hangup());

	$ext->add('app-blackhole', 'zapateller', '', new ext_noop('Blackhole Dest: Play SIT Tone'));
	$ext->add('app-blackhole', 'zapateller', '', new ext_answer());
	$ext->add('app-blackhole', 'zapateller', '', new ext_zapateller());
	// Should hangup ?
	// $ext->add('app-blackhole', 'zapateller', '', new ext_hangup());

	$ext->add('app-blackhole', 'musiconhold', '', new ext_noop('Blackhole Dest: Put caller on hold forever'));
	$ext->add('app-blackhole', 'musiconhold', '', new ext_answer());
	$ext->add('app-blackhole', 'musiconhold', '', new ext_musiconhold());

	$ext->add('app-blackhole', 'congestion', '', new ext_noop('Blackhole Dest: Congestion'));
	$ext->add('app-blackhole', 'congestion', '', new ext_progress());
	$ext->add('app-blackhole', 'congestion', '', new ext_playtones('congestion'));
	$ext->add('app-blackhole', 'congestion', '', new ext_congestion());
	$ext->add('app-blackhole', 'congestion', '', new ext_hangup());

	$ext->add('app-blackhole', 'busy', '', new ext_noop('Blackhole Dest: Busy'));
	$ext->add('app-blackhole', 'busy', '', new ext_progress());
	$ext->add('app-blackhole', 'busy', '', new ext_busy());
	$ext->add('app-blackhole', 'busy', '', new ext_hangup());

	$ext->add('app-blackhole', 'ring', '', new ext_noop('Blackhole Dest: Ring'));
	$ext->add('app-blackhole', 'ring', '', new ext_answer());
	$ext->add('app-blackhole', 'ring', '', new ext_playtones('ring'));
	$ext->add('app-blackhole', 'ring', '', new ext_wait(300));
	$ext->add('app-blackhole', 'ring', '', new ext_hangup());

	$ext->add('app-blackhole', 'no-service', '', new ext_noop('Blackhole Dest: No service'));
	$ext->add('app-blackhole', 'no-service', '', new ext_answer());
	$ext->add('app-blackhole', 'no-service', '', new ext_wait('1'));
	$ext->add('app-blackhole', 'no-service', '', new ext_zapateller());
	$ext->add('app-blackhole', 'no-service', '', new ext_playback('ss-noservice'));
	$ext->add('app-blackhole', 'no-service', '', new ext_hangup());

	if ($amp_conf['AMPBADNUMBER']) {
		$context = 'bad-number';
		$exten = '_X.';
		$ext->add($context, $exten, '', new extension('ResetCDR()'));
		$ext->add($context, $exten, '', new extension('NoCDR()'));
		$ext->add($context, $exten, '', new ext_progress());
		$ext->add($context, $exten, '', new ext_wait('1'));
		$ext->add($context, $exten, '', new ext_playback('silence/1&cannot-complete-as-dialed&check-number-dial-again,noanswer'));
		$ext->add($context, $exten, '', new ext_wait('1'));
		$ext->add($context, $exten, '', new ext_congestion('20'));
		$ext->add($context, $exten, '', new ext_hangup());
	}

	if ($amp_conf['AST_FUNC_PRESENCE_STATE']) {
		$states = array(
			'available' => 'Available',
			'chat' => 'Chatty',
			'away' => 'Away',
			'dnd' => 'DND',
			'xa' => 'Extended Away',
			'unavailable' => 'Unavailable'
		);

		$context = 'sub-presencestate-display';

		$exten = 's';
		$ext->add($context, $exten, '', new ext_goto(1, 'state-${TOLOWER(${PRESENCE_STATE(CustomPresence:${ARG1},value)})}'));

		foreach ($states as $state => $display) {
			$exten = 'state-' . $state;
			$ext->add($context, $exten, '', new ext_setvar('PRESENCESTATE_DISPLAY', '(' . $display . ')'));
			$ext->add($context, $exten, '', new ext_return(''));
		}

		// Don't display anything if presencestate is unknown (Coding bug)
		$exten = '_state-.';
		$ext->add($context, $exten, '', new ext_setvar('PRESENCESTATE_DISPLAY', ''));
		$ext->add($context, $exten, '', new ext_return(''));

		// Don't display anything if presencestate is empty (not set).
		$exten = 'state-';
		$ext->add($context, $exten, '', new ext_setvar('PRESENCESTATE_DISPLAY', ''));
		$ext->add($context, $exten, '', new ext_return(''));
	}

	/*
	;------------------------------------------------------------------------
	; [macro-confirm]
	;------------------------------------------------------------------------
	; CONTEXT:      macro-confirm
	; PURPOSE:      added default message if none supplied
	;
	; Follom-Me and Ringgroups provide an option to supply a message to be
	; played as part of the confirmation. These changes have added a default
	; message if none is supplied.
	;
	;------------------------------------------------------------------------
	*/
	$context = 'macro-confirm';
	$exten = 's';

	$ext->add($context, $exten, '', new ext_setvar('LOOPCOUNT','0'));
	$ext->add($context, $exten, '', new ext_setvar('__MACRO_RESULT','ABORT'));
	$ext->add($context, $exten, '', new ext_setvar('MSG1','${IF($["${ARG1}${ALT_CONFIRM_MSG}"=""]?incoming-call-1-accept-2-decline:${IF($[${LEN(${ALT_CONFIRM_MSG})}>0]?${ALT_CONFIRM_MSG}:${ARG1})})}'));
	if ($ast_ge_14) {
		$ext->add($context, $exten, 'start', new ext_background('${MSG1},m,${CHANNEL(language)},macro-confirm'));
	} else {
		$ext->add($context, $exten, 'start', new ext_background('${MSG1},m,${LANGUAGE},macro-confirm'));
	}
	$ext->add($context, $exten, '', new ext_read('INPUT', '', 1, '', '', 4));
	$ext->add($context, $exten, '', new ext_gotoif('$[${LEN(${INPUT})} > 0]', '${INPUT},1', 't,1'));

	$exten = '1';
	if ($amp_conf['AST_FUNC_SHARED']) {
		$ext->add($context, $exten, '', new ext_gotoif('$["${DB_EXISTS(RG/${ARG3}/${UNIQCHAN})}"="0" & "${SHARED(ANSWER_STATUS,${FORCE_CONFIRM})}"=""]', 'toolate,1'));
	} else {
		$ext->add($context, $exten, '', new ext_gotoif('$["${FORCE_CONFIRM}" != ""]', 'skip'));
		$ext->add($context, $exten, '', new ext_gotoif('$["${DB_EXISTS(RG/${ARG3}/${UNIQCHAN})}"="0"]', 'toolate,1'));
	}
	$ext->add($context, $exten, '', new ext_dbdel('RG/${ARG3}/${UNIQCHAN}'));
	$ext->add($context, $exten, '', new ext_macro('blkvm-clr'));
	if ($amp_conf['AST_FUNC_SHARED']) {
		$ext->add($context, $exten, '', new ext_setvar('SHARED(ANSWER_STATUS,${FORCE_CONFIRM})',''));
	}
	$ext->add($context, $exten, 'skip', new ext_setvar('__MACRO_RESULT',''));
	$ext->add($context, $exten, '', new ext_execif('$[("${MOHCLASS}"!="default") & ("${MOHCLASS}"!="")]', 'Set', 'CHANNEL(musicclass)=${MOHCLASS}'));
	$ext->add($context, $exten, 'exitopt1', new ext_macroexit());

	$exten = '2';
	$ext->add($context, $exten, '', new ext_goto(1, 'noanswer'));

	$exten = '3';
	$ext->add($context, $exten, '', new ext_saydigits('${CALLCONFIRMCID}'));
	if ($amp_conf['AST_FUNC_SHARED']) {
		$ext->add($context, $exten, '', new ext_gotoif('$["${DB_EXISTS(RG/${ARG3}/${UNIQCHAN})}"="0" & "${SHARED(ANSWER_STATUS,${FORCE_CONFIRM})}"=""]', 'toolate,1','s,start'));
	} else {
		$ext->add($context, $exten, '', new ext_gotoif('$["${DB_EXISTS(RG/${ARG3}/${UNIQCHAN})}"="0" & "${FORCE_CONFIRM}"=""]', 'toolate,1','s,start'));
	}

	$exten = 't';
	if ($amp_conf['AST_FUNC_SHARED']) {
		$ext->add($context, $exten, '', new ext_gotoif('$["${DB_EXISTS(RG/${ARG3}/${UNIQCHAN})}"="0" & "${SHARED(ANSWER_STATUS,${FORCE_CONFIRM})}"=""]', 'toolate,1'));
	} else {
		$ext->add($context, $exten, '', new ext_gotoif('$["${DB_EXISTS(RG/${ARG3}/${UNIQCHAN})}"="0" & "${FORCE_CONFIRM}"=""]', 'toolate,1'));
	}
	$ext->add($context, $exten, '', new ext_setvar('LOOPCOUNT','$[ ${LOOPCOUNT} + 1 ]'));
	$ext->add($context, $exten, '', new ext_gotoif('$[ ${LOOPCOUNT} < 5 ]', 's,start','noanswer,1'));

	$exten = '_X';
	if ($ast_ge_14) {
		$ext->add($context, $exten, '', new ext_background('invalid,m,${CHANNEL(language)},macro-confirm'));
	} else {
		$ext->add($context, $exten, '', new ext_background('invalid,m,${LANGUAGE},macro-confirm'));
	}
	if ($amp_conf['AST_FUNC_SHARED']) {
		$ext->add($context, $exten, '', new ext_gotoif('$["${DB_EXISTS(RG/${ARG3}/${UNIQCHAN})}"="0" | "${SHARED(ANSWER_STATUS,${FORCE_CONFIRM})}"=""]', 'toolate,1'));
	} else {
		$ext->add($context, $exten, '', new ext_gotoif('$["${DB_EXISTS(RG/${ARG3}/${UNIQCHAN})}"="0" & "${FORCE_CONFIRM}"=""]', 'toolate,1'));
	}
	$ext->add($context, $exten, '', new ext_setvar('LOOPCOUNT','$[ ${LOOPCOUNT} + 1 ]'));
	$ext->add($context, $exten, '', new ext_gotoif('$[ ${LOOPCOUNT} < 5 ]', 's,start','noanswer,1'));

	$exten = 'noanswer';
	$ext->add($context, $exten, '', new ext_setvar('__MACRO_RESULT','ABORT'));
	$ext->add($context, $exten, 'exitnoanswer', new ext_macroexit());

	$exten = 'toolate';
	$ext->add($context, $exten, '', new ext_setvar('MSG2','${IF($["foo${ARG2}" != "foo"]?${ARG2}:"incoming-call-no-longer-avail")}'));
	$ext->add($context, $exten, '', new ext_playback('${MSG2}'));
	$ext->add($context, $exten, '', new ext_setvar('__MACRO_RESULT','ABORT'));
	$ext->add($context, $exten, 'exittoolate', new ext_macroexit());

	$exten = 'h';
	$ext->add($context, $exten, '', new ext_macro('hangupcall'));

	/*
	;------------------------------------------------------------------------
	; [macro-auto-confirm]
	;------------------------------------------------------------------------
	; This macro is called from ext-local-confirm to auto-confirm a call so that other extensions
	; are aware that the call has been answered.
	;
	;------------------------------------------------------------------------
	*/
	$context = 'macro-auto-confirm';
	$exten = 's';
	$ext->add($context, $exten, '', new ext_setvar('__MACRO_RESULT',''));
	$ext->add($context, $exten, '', new ext_set('CFIGNORE',''));
	$ext->add($context, $exten, '', new ext_set('MASTER_CHANNEL(CFIGNORE)',''));
	$ext->add($context, $exten, '', new ext_set('FORWARD_CONTEXT','from-internal'));
	$ext->add($context, $exten, '', new ext_set('MASTER_CHANNEL(FORWARD_CONTEXT)','from-internal'));
	$ext->add($context, $exten, '', new ext_macro('blkvm-clr'));
	$ext->add($context, $exten, '', new ext_dbdel('RG/${ARG1}/${UNIQCHAN}'));
	$ext->add($context, $exten, '', new ext_noop_trace('DIALEDPEERNUMBER: ${DIALEDPEERNUMBER} CID: ${CALLERID(all)}'));
	if ($amp_conf['AST_FUNC_MASTER_CHANNEL'] && $amp_conf['AST_FUNC_CONNECTEDLINE']) {
		// Check that it is numeric so we don't pollute it with odd dialplan stuff like FMGL-blah from followme
		$ext->add($context, $exten, '', new ext_execif('$[!${REGEX("[^0-9]" ${DIALEDPEERNUMBER})} && "${DB(AMPUSER/${AMPUSER}/cidname)}" != ""]', 'Set', 'MASTER_CHANNEL(CONNECTEDLINE(num))=${DIALEDPEERNUMBER}'));
		$ext->add($context, $exten, '', new ext_execif('$[!${REGEX("[^0-9]" ${DIALEDPEERNUMBER})} && "${DB(AMPUSER/${AMPUSER}/cidname)}" != ""]', 'Set', 'MASTER_CHANNEL(CONNECTEDLINE(name))=${DB(AMPUSER/${DIALEDPEERNUMBER}/cidname)}'));
	}

	/*
	;------------------------------------------------------------------------
	; [macro-auto-blkvm]
	;------------------------------------------------------------------------
	; This macro is called for any extension dialed form a queue, ringgroup
	; or followme, so that the answering extension can clear the voicemail block
	; override allow subsequent transfers to properly operate.
	;
	;------------------------------------------------------------------------
	*/
	$context = 'macro-auto-blkvm';
	$exten = 's';
	$ext->add($context, $exten, '', new ext_setvar('__MACRO_RESULT',''));
	$ext->add($context, $exten, '', new ext_set('CFIGNORE',''));
	$ext->add($context, $exten, '', new ext_set('MASTER_CHANNEL(CFIGNORE)',''));
	$ext->add($context, $exten, '', new ext_set('FORWARD_CONTEXT','from-internal'));
	$ext->add($context, $exten, '', new ext_set('MASTER_CHANNEL(FORWARD_CONTEXT)','from-internal'));
	$ext->add($context, $exten, '', new ext_macro('blkvm-clr'));
	$ext->add($context, $exten, '', new ext_noop_trace('DIALEDPEERNUMBER: ${DIALEDPEERNUMBER} CID: ${CALLERID(all)}'));
	if ($amp_conf['AST_FUNC_MASTER_CHANNEL'] && $amp_conf['AST_FUNC_CONNECTEDLINE']) {
		// Check that it is numeric so we don't pollute it with odd dialplan stuff like FMGL-blah from followme
		$ext->add($context, $exten, '', new ext_execif('$[!${REGEX("[^0-9]" ${DIALEDPEERNUMBER})} && "${DB(AMPUSER/${AMPUSER}/cidname)}" != ""]', 'Set', 'MASTER_CHANNEL(CONNECTEDLINE(num))=${DIALEDPEERNUMBER}'));
		$ext->add($context, $exten, '', new ext_execif('$[!${REGEX("[^0-9]" ${DIALEDPEERNUMBER})} && "${DB(AMPUSER/${AMPUSER}/cidname)}" != ""]', 'Set', 'MASTER_CHANNEL(CONNECTEDLINE(name))=${DB(AMPUSER/${DIALEDPEERNUMBER}/cidname)}'));
	}

	/*
	;------------------------------------------------------------------------
	; [sub-pincheck]
	;------------------------------------------------------------------------
	; This subroutine checks the pincode and then resets the CDR from that point
	; if the pincode passes. This way the billsec and duration fields are set
	; properly for pin dialing.
	;
	; ${ARG3} is the pincode if this was called, used by dialout-trunk, dialout-enum
	; and dialout-dundi
	;
	;------------------------------------------------------------------------
	*/
	$context = 'sub-pincheck';
	$exten = 's';
	$ext->add($context, $exten, '', new ext_authenticate('${ARG3}'));
	$ext->add($context, $exten, '', new ext_resetcdr(''));
	$ext->add($context, $exten, '', new ext_return(''));

	// Subroutine to add diversion header with reason code "no-answer" unless provided differently elsewhere in the dialplan to indicate
	// the reason for the diversion (e.g. CFB could set it to busy)
	//
	if ($amp_conf['DIVERSIONHEADER']) {
		$context = 'sub-diversion-header';
		$exten = 's';
		$ext->add($context, $exten, '', new ext_set('DIVERSION_REASON', '${IF($[${LEN(${DIVERSION_REASON})}=0]?no-answer:${DIVERSION_REASON})}'));
		$ext->add($context, $exten, '', new ext_sipaddheader('Diversion', '<tel:${FROM_DID}>\;reason=${DIVERSION_REASON}\;screen=no\;privacy=off'));
		$ext->add($context, $exten, '', new ext_return(''));
	}

	/*
	* dialout using a trunk, using pattern matching (don't strip any prefix)
	* arg1 = trunk number, arg2 = number, arg3 = route password
	*
	* MODIFIED (PL)
	*
	* Modified both Dial() commands to include the new TRUNK_OPTIONS from the general
	* screen of AMP
	*/
	if (function_exists('outroutemsg_get')) {
		$trunkreportmsg_ids = outroutemsg_get();
	} else {
		if (!defined('DEFAULT_MSG')) define('DEFAULT_MSG', -1);
		if (!defined('CONGESTION_TONE')) define('CONGESTION_TONE', -2);
		$trunkreportmsg_ids = array('no_answer_msg_id' => -1, 'invalidnmbr_msg_id' => -1);
	}

	// Since rarely used only generate this dialplan if are using this feature
	//
	$generate_trunk_monitor_failure = false;
	foreach ($trunk_table as $tid => $tdetails) {
		// assign and if true no need to continue
		if ($generate_trunk_monitor_failure = $generate_trunk_monitor_failure || $tdetails['failscript']) {
			break;
		}
	}

	$context = 'macro-dialout-trunk';
	if (!empty($trunk_type_needed[$context])) {
		$exten = 's';
		$ext->add($context, $exten, '', new ext_set('DIAL_TRUNK', '${ARG1}'));
		$ext->add($context, $exten, '', new ext_gosubif('$[$["${ARG3}" != ""] & $["${DB(AMPUSER/${AMPUSER}/pinless)}" != "NOPASSWD"]]','sub-pincheck,s,1'));
		$ext->add($context, $exten, '', new ext_gotoif('$["x${OUTDISABLE_${DIAL_TRUNK}}" = "xon"]', 'disabletrunk,1'));
		$ext->add($context, $exten, '', new ext_set('DIAL_NUMBER', '${ARG2}')); // fixlocalprefix depends on this
		$ext->add($context, $exten, '', new ext_set('DIAL_TRUNK_OPTIONS', '${DIAL_OPTIONS}')); // will be reset to TRUNK_OPTIONS if not intra-company
		$ext->add($context, $exten, '', new ext_set('OUTBOUND_GROUP', 'OUT_${DIAL_TRUNK}'));
		$ext->add($context, $exten, '', new ext_gotoif('$["${OUTMAXCHANS_${DIAL_TRUNK}}foo" = "foo"]', 'nomax'));
		$ext->add($context, $exten, '', new ext_gotoif('$[ ${GROUP_COUNT(OUT_${DIAL_TRUNK})} >= ${OUTMAXCHANS_${DIAL_TRUNK}} ]', 'chanfull'));
		$ext->add($context, $exten, 'nomax', new ext_gotoif('$["${INTRACOMPANYROUTE}" = "YES"]', 'skipoutcid'));  // Set to YES if treated like internal
		$ext->add($context, $exten, '', new ext_set('DIAL_TRUNK_OPTIONS', '${IF($["${DB_EXISTS(TRUNK/${DIAL_TRUNK}/dialopts)}" = "1"]?${DB_RESULT}:${TRUNK_OPTIONS})}'));
		$ext->add($context, $exten, '', new ext_macro('outbound-callerid', '${DIAL_TRUNK}'));
		$ext->add($context, $exten, 'skipoutcid', new ext_gosubif('$["${PREFIX_TRUNK_${DIAL_TRUNK}}" != ""]','sub-flp-${DIAL_TRUNK},s,1'));  // this sets DIAL_NUMBER to the proper dial string for this trunk
		$ext->add($context, $exten, '', new ext_set('OUTNUM', '${OUTPREFIX_${DIAL_TRUNK}}${DIAL_NUMBER}'));  // OUTNUM is the final dial number
		$ext->add($context, $exten, '', new ext_set('custom', '${CUT(OUT_${DIAL_TRUNK},:,1)}'));  // Custom trunks are prefixed with "AMP:"

		// Back to normal processing, whether intracompany or not.
		// But add the macro-setmusic if we don't want music on this outbound call
		// if FORCE_CONFIRM then that macro will set any necessary MOHCLASS, and we will also call the confirm macro
		$ext->add($context, $exten, '', new ext_execif('$["${MOHCLASS}"!="default" & "${MOHCLASS}"!="" & "${FORCE_CONFIRM}"="" ]', 'Set', 'DIAL_TRUNK_OPTIONS=M(setmusic^${MOHCLASS})${DIAL_TRUNK_OPTIONS}'));
		$ext->add($context, $exten, '', new ext_execif('$["${FORCE_CONFIRM}"!="" ]', 'Set', 'DIAL_TRUNK_OPTIONS=${DIAL_TRUNK_OPTIONS}M(confirm)'));

		// This macro call will always be blank and is provided as a hook for customization required prior to making a call
		// such as adding SIP header information or other requirements. All the channel variables from above are present

		$ext->add($context, $exten, 'gocall', new ext_macro('dialout-trunk-predial-hook'));
		$ext->add($context, $exten, '', new ext_gotoif('$["${PREDIAL_HOOK_RET}" = "BYPASS"]', 'bypass,1'));

		if ($amp_conf['AST_FUNC_CONNECTEDLINE'] && $amp_conf['OUTBOUND_DIAL_UPDATE']) {
			$ext->add($context, $exten, '', new ext_execif('$["${DB(AMPUSER/${AMPUSER}/cidname)}" != ""]','Set','CONNECTEDLINE(num,i)=${DIAL_NUMBER}'));
		}
		if ($amp_conf['AST_FUNC_CONNECTEDLINE'] && $amp_conf['OUTBOUND_CID_UPDATE']) {
			$ext->add($context, $exten, '', new ext_execif('$[$["${DB(AMPUSER/${AMPUSER}/cidname)}" != ""] & $["${CALLERID(name)}"!="hidden"]]','Set','CONNECTEDLINE(name,i)=CID:${CALLERID(number)}'));
			$ext->add($context, $exten, '', new ext_execif('$[$["${DB(AMPUSER/${AMPUSER}/cidname)}" != ""] & $["${CALLERID(name)}"="hidden"]]', 'Set', 'CONNECTEDLINE(name,i)=CID:(Hidden)${CALLERID(number)}'));
		}

		$ext->add($context, $exten, '', new ext_gotoif('$["${custom}" = "AMP"]', 'customtrunk'));
		$ext->add($context, $exten, '', new ext_dial('${OUT_${DIAL_TRUNK}}/${OUTNUM}${OUT_${DIAL_TRUNK}_SUFFIX}', '${TRUNK_RING_TIMER},${DIAL_TRUNK_OPTIONS}'));  // Regular Trunk Dial
		$ext->add($context, $exten, '', new ext_noop('Dial failed for some reason with DIALSTATUS = ${DIALSTATUS} and HANGUPCAUSE = ${HANGUPCAUSE}'));
		$ext->add($context, $exten, '', new ext_gotoif('$["${ARG4}" = "on"]','continue,1', 's-${DIALSTATUS},1'));

		$ext->add($context, $exten, 'customtrunk', new ext_set('pre_num', '${CUT(OUT_${DIAL_TRUNK},$,1)}'));
		$ext->add($context, $exten, '', new ext_set('the_num', '${CUT(OUT_${DIAL_TRUNK},$,2)}'));  // this is where we expect to find string OUTNUM
		$ext->add($context, $exten, '', new ext_set('post_num', '${CUT(OUT_${DIAL_TRUNK},$,3)}'));
		$ext->add($context, $exten, '', new ext_gotoif('$["${the_num}" = "OUTNUM"]', 'outnum', 'skipoutnum'));  // if we didn't find "OUTNUM", then skip to Dial
		$ext->add($context, $exten, 'outnum', new ext_set('the_num', '${OUTNUM}'));  // replace "OUTNUM" with the actual number to dial
		$ext->add($context, $exten, 'skipoutnum', new ext_dial('${pre_num:4}${the_num}${post_num}', '${TRUNK_RING_TIMER},${DIAL_TRUNK_OPTIONS}'));
		$ext->add($context, $exten, '', new ext_noop('Dial failed for some reason with DIALSTATUS = ${DIALSTATUS} and HANGUPCAUSE = ${HANGUPCAUSE}'));
		$ext->add($context, $exten, '', new ext_gotoif('$["${ARG4}" = "on"]','continue,1', 's-${DIALSTATUS},1'));
		$ext->add($context, $exten, 'chanfull', new ext_noop('max channels used up'));

		$exten = 's-BUSY';
		/*
		* HANGUPCAUSE 17 = Busy, or SIP 486 Busy everywhere
		*/
		$ext->add($context, $exten, '', new ext_noop('Dial failed due to trunk reporting BUSY - giving up'));
		$ext->add($context, $exten, '', new ext_playtones('busy'));
		$ext->add($context, $exten, '', new ext_busy(20));

		/*
		* There are reported bugs in Asterisk Blind Trasfers that result in Dial() returning and continuing
		* execution with a status of ANSWER. So we hangup at this point
		*/
		$exten = 's-ANSWER';
		$ext->add($context, $exten, '', new ext_noop('Call successfully answered - Hanging up now'));
		$ext->add($context, $exten, '', new ext_macro('hangupcall'));

		$exten = 's-NOANSWER';
		/*
		* HANGUPCAUSE 18 = No User Responding, or SIP 408 Request Timeout
		* HANGUPCAUSE 19 = No Answer From The User, or SIP 480 Temporarily unavailable, SIP 483 To many hops
		*/
		$ext->add($context, $exten, '', new ext_noop('Dial failed due to trunk reporting NOANSWER - giving up'));
		$ext->add($context, $exten, '', new ext_progress());
		switch ($trunkreportmsg_ids['no_answer_msg_id']) {
			case DEFAULT_MSG:
			$ext->add($context, $exten, '', new ext_playback('number-not-answering,noanswer'));
			break;
			case CONGESTION_TONE:
			$ext->add($context, $exten, '', new ext_playtones('congestion'));
			break;
			default:
			$message = recordings_get_file($trunkreportmsg_ids['no_answer_msg_id']);
			$message = ($message != "") ? $message : "number-not-answering";
			$ext->add($context, $exten, '', new ext_playback("$message, noanswer"));
		}
		$ext->add($context, $exten, '', new ext_congestion(20));

		$exten = 's-INVALIDNMBR';
		/*
		* HANGUPCAUSE 28 = Address Incomplete, or SIP 484 Address Incomplete
		*/
		$ext->add($context, $exten, '', new ext_noop('Dial failed due to trunk reporting Address Incomplete - giving up'));
		$ext->add($context, $exten, '', new ext_progress());
		switch ($trunkreportmsg_ids['invalidnmbr_msg_id']) {
			case DEFAULT_MSG:
			$ext->add($context, $exten, '', new ext_playback('ss-noservice,noanswer'));
			break;
			case CONGESTION_TONE:
			$ext->add($context, $exten, '', new ext_playtones('congestion'));
			break;
			default:
			$message = recordings_get_file($trunkreportmsg_ids['invalidnmbr_msg_id']);
			$message = ($message != "") ? $message : "ss-noservice";
			$ext->add($context, $exten, '', new ext_playback("$message, noanswer"));
		}
		$ext->add($context, $exten, '', new ext_busy(20));

		$exten = "s-CHANGED";
		$ext->add($context, $exten, '', new ext_noop('Dial failed due to trunk reporting Number Changed - giving up'));
		$ext->add($context, $exten, '', new ext_playtones('busy'));
		$ext->add($context, $exten, '', new ext_busy(20));

		$exten = '_s-.';
		$ext->add($context, $exten, '', new ext_set('RC', '${IF($[${ISNULL(${HANGUPCAUSE})}]?0:${HANGUPCAUSE})}'));
		$ext->add($context, $exten, '', new ext_goto('1','${RC}'));

		$ext->add($context, '17', '', new ext_goto('1','s-BUSY'));
		$ext->add($context, '18', '', new ext_goto('1','s-NOANSWER'));
		$ext->add($context, '22', '', new ext_goto('1','s-CHANGED'));
		$ext->add($context, '23', '', new ext_goto('1','s-CHANGED'));
		$ext->add($context, '1', '', new ext_goto('1','s-INVALIDNMBR'));
		$ext->add($context, '28', '', new ext_goto('1','s-INVALIDNMBR'));
		$ext->add($context, '_X', '', new ext_goto('1','continue'));
		$ext->add($context, '_X.', '', new ext_goto('1','continue'));

		$exten = 'continue';
		if ($generate_trunk_monitor_failure) {
			$ext->add($context, $exten, '', new ext_gotoif('$["${OUTFAIL_${ARG1}}" = ""]', 'noreport'));
			$ext->add($context, $exten, '', new ext_agi('${OUTFAIL_${ARG1}}'));
		}
		$ext->add($context, $exten, 'noreport', new ext_noop('TRUNK Dial failed due to ${DIALSTATUS} HANGUPCAUSE: ${HANGUPCAUSE} - failing through to other trunks'));
		$ext->add($context, $exten, '', new ext_execif('$["${AMPUSER}"!="" ]', 'Set', 'CALLERID(number)=${AMPUSER}'));

		$ext->add($context, 'disabletrunk', '', new ext_noop('TRUNK: ${OUT_${DIAL_TRUNK}} DISABLED - falling through to next trunk'));
		$ext->add($context, 'bypass', '', new ext_noop('TRUNK: ${OUT_${DIAL_TRUNK}} BYPASSING because dialout-trunk-predial-hook'));

		$ext->add($context, 'h', '', new ext_macro('hangupcall'));
	} // if trunk_type_needed


	$context = 'macro-dialout-dundi';
	if (!empty($trunk_type_needed[$context])) {
		$exten = 's';

		/*
		* Dialout Dundi Trunk
		*/
		$ext->add($context, $exten, '', new ext_set('DIAL_TRUNK', '${ARG1}'));
		$ext->add($context, $exten, '', new ext_gosubif('$[$["${ARG3}" != ""] & $["${DB(AMPUSER/${AMPUSER}/pinless)}" != "NOPASSWD"]]','sub-pincheck,s,1'));
		$ext->add($context, $exten, '', new ext_gotoif('$["x${OUTDISABLE_${DIAL_TRUNK}}" = "xon"]', 'disabletrunk,1'));
		$ext->add($context, $exten, '', new ext_set('DIAL_NUMBER', '${ARG2}')); // fixlocalprefix depends on this
		$ext->add($context, $exten, '', new ext_set('DIAL_TRUNK_OPTIONS', '${DIAL_OPTIONS}')); // will be reset to TRUNK_OPTIONS if not intra-company
		$ext->add($context, $exten, '', new ext_set('OUTBOUND_GROUP', 'OUT_${DIAL_TRUNK}'));
		$ext->add($context, $exten, '', new ext_gotoif('$["${OUTMAXCHANS_${DIAL_TRUNK}}foo" = "foo"]', 'nomax'));
		$ext->add($context, $exten, '', new ext_gotoif('$[ ${GROUP_COUNT(OUT_${DIAL_TRUNK})} >= ${OUTMAXCHANS_${DIAL_TRUNK}} ]', 'chanfull'));
		$ext->add($context, $exten, 'nomax', new ext_gotoif('$["${INTRACOMPANYROUTE}" = "YES"]', 'skipoutcid'));  // Set to YES if treated like internal
		$ext->add($context, $exten, '', new ext_set('DIAL_TRUNK_OPTIONS', '${IF($["${DB_EXISTS(TRUNK/${DIAL_TRUNK}/dialopts)}" = "1"]?${DB_RESULT}:${TRUNK_OPTIONS})}'));
		$ext->add($context, $exten, '', new ext_macro('outbound-callerid', '${DIAL_TRUNK}'));
		$ext->add($context, $exten, 'skipoutcid', new ext_gosubif('$["${PREFIX_TRUNK_${DIAL_TRUNK}}" != ""]','sub-flp-${DIAL_TRUNK},s,1'));  // manipulate DIAL_NUMBER
		$ext->add($context, $exten, '', new ext_set('OUTNUM', '${OUTPREFIX_${DIAL_TRUNK}}${DIAL_NUMBER}'));  // OUTNUM is the final dial number

		// Back to normal processing, whether intracompany or not.
		// But add the macro-setmusic if we don't want music on this outbound call
		$ext->add($context, $exten, '', new ext_execif('$["${MOHCLASS}"!="default" & "${MOHCLASS}"!="" & "${FORCE_CONFIRM}"="" ]', 'Set', 'DIAL_TRUNK_OPTIONS=M(setmusic^${MOHCLASS})${DIAL_TRUNK_OPTIONS}'));
		$ext->add($context, $exten, '', new ext_execif('$["${FORCE_CONFIRM}"!="" ]', 'Set', 'DIAL_TRUNK_OPTIONS=${DIAL_TRUNK_OPTIONS}M(confirm)'));

		// This macro call will always be blank and is provided as a hook for customization required prior to making a call
		// such as adding SIP header information or other requirements. All the channel variables from above are present

		$ext->add($context, $exten, 'gocall', new ext_macro('dialout-dundi-predial-hook'));
		$ext->add($context, $exten, '', new ext_gotoif('$["${PREDIAL_HOOK_RET}" = "BYPASS"]', 'bypass,1'));

		if ($amp_conf['AST_FUNC_CONNECTEDLINE'] && $amp_conf['OUTBOUND_DIAL_UPDATE']) {
			$ext->add($context, $exten, '', new ext_execif('$["${DB(AMPUSER/${AMPUSER}/cidname)}" != ""]','Set','CONNECTEDLINE(num,i)=${DIAL_NUMBER}'));
		}
		if ($amp_conf['AST_FUNC_CONNECTEDLINE'] && $amp_conf['OUTBOUND_CID_UPDATE']) {
			$ext->add($context, $exten, '', new ext_execif('$["${DB(AMPUSER/${AMPUSER}/cidname)}" != ""]','Set','CONNECTEDLINE(name,i)=CID:${CALLERID(number)}'));
		}

		$ext->add($context, $exten, '', new ext_macro('dundi-${DIAL_TRUNK}','${OUTNUM}'));
		$ext->add($context, $exten, '', new ext_gotoif('$["${ARG4}" = "on"]','continue,1', 's-${DIALSTATUS},1'));
		$ext->add($context, $exten, 'chanfull', new ext_noop('max channels used up'));

		$exten = 's-BUSY';
		/*
		* HANGUPCAUSE 17 = Busy, or SIP 486 Busy everywhere
		*/
		$ext->add($context, $exten, '', new ext_noop('Dial failed due to trunk reporting BUSY - giving up'));
		$ext->add($context, $exten, '', new ext_playtones('busy'));
		$ext->add($context, $exten, '', new ext_busy(20));

		/*
		* There are reported bugs in Asterisk Blind Trasfers that result in Dial() returning and continuing
		* execution with a status of ANSWER. So we hangup at this point
		*/
		$exten = 's-ANSWER';
		$ext->add($context, $exten, '', new ext_noop('Call successfully answered - Hanging up now'));
		$ext->add($context, $exten, '', new ext_macro('hangupcall'));

		$exten = 's-NOANSWER';
		/*
		* HANGUPCAUSE 18 = No User Responding, or SIP 408 Request Timeout
		* HANGUPCAUSE 19 = No Answer From The User, or SIP 480 Temporarily unavailable, SIP 483 To many hops
		*/
		$ext->add($context, $exten, '', new ext_noop('Dial failed due to trunk reporting NOANSWER - giving up'));
		$ext->add($context, $exten, '', new ext_progress());
		switch ($trunkreportmsg_ids['no_answer_msg_id']) {
			case DEFAULT_MSG:
			$ext->add($context, $exten, '', new ext_playback('number-not-answering,noanswer'));
			break;
			case CONGESTION_TONE:
			$ext->add($context, $exten, '', new ext_playtones('congestion'));
			break;
			default:
			$message = recordings_get_file($trunkreportmsg_ids['no_answer_msg_id']);
			$message = ($message != "") ? $message : "number-not-answering";
			$ext->add($context, $exten, '', new ext_playback("$message, noanswer"));
		}
		$ext->add($context, $exten, '', new ext_congestion(20));

		$exten = 's-INVALIDNMBR';
		/*
		* HANGUPCAUSE 28 = Address Incomplete, or SIP 484 Address Incomplete
		* HANGUPCAUSE 1 = Unallocated (unassigned) number
		*/
		$ext->add($context, $exten, '', new ext_noop('Dial failed due to trunk reporting Address Incomplete - giving up'));
		$ext->add($context, $exten, '', new ext_progress());
		switch ($trunkreportmsg_ids['invalidnmbr_msg_id']) {
			case DEFAULT_MSG:
			$ext->add($context, $exten, '', new ext_playback('ss-noservice,noanswer'));
			break;
			case CONGESTION_TONE:
			$ext->add($context, $exten, '', new ext_playtones('congestion'));
			break;
			default:
			$message = recordings_get_file($trunkreportmsg_ids['invalidnmbr_msg_id']);
			$message = ($message != "") ? $message : "ss-noservice";
			$ext->add($context, $exten, '', new ext_playback("$message, noanswer"));
		}
		$ext->add($context, $exten, '', new ext_busy(20));

		$exten = "s-CHANGED";
		$ext->add($context, $exten, '', new ext_noop('Dial failed due to trunk reporting Number Changed - giving up'));
		$ext->add($context, $exten, '', new ext_playtones('busy'));
		$ext->add($context, $exten, '', new ext_busy(20));

		$exten = '_s-.';
		$ext->add($context, $exten, '', new ext_set('RC', '${IF($[${ISNULL(${HANGUPCAUSE})}]?0:${HANGUPCAUSE})}'));
		$ext->add($context, $exten, '', new ext_goto('1','${RC}'));

		$ext->add($context, '17', '', new ext_goto('1','s-BUSY'));
		$ext->add($context, '18', '', new ext_goto('1','s-NOANSWER'));
		$ext->add($context, '22', '', new ext_goto('1','s-CHANGED'));
		$ext->add($context, '23', '', new ext_goto('1','s-CHANGED'));
		$ext->add($context, '28', '', new ext_goto('1','s-INVALIDNMBR'));
		$ext->add($context, '_X', '', new ext_goto('1','continue'));
		$ext->add($context, '_X.', '', new ext_goto('1','continue'));

		$exten = 'continue';
		if ($generate_trunk_monitor_failure) {
			$ext->add($context, $exten, '', new ext_gotoif('$["${OUTFAIL_${ARG1}}" = ""]', 'noreport'));
			$ext->add($context, $exten, '', new ext_agi('${OUTFAIL_${ARG1}}'));
		}
		$ext->add($context, $exten, 'noreport', new ext_noop('TRUNK Dial failed due to ${DIALSTATUS} HANGUPCAUSE: ${HANGUPCAUSE} - failing through to other trunks'));
		$ext->add($context, $exten, '', new ext_execif('$["${AMPUSER}"!="" ]', 'Set', 'CALLERID(number)=${AMPUSER}'));


		$ext->add($context, 'disabletrunk', '', new ext_noop('TRUNK: ${OUT_${DIAL_TRUNK}} DISABLED - falling through to next trunk'));
		$ext->add($context, 'bypass', '', new ext_noop('TRUNK: ${OUT_${DIAL_TRUNK}} BYPASSING because dialout-dundi-predial-hook'));

		$ext->add($context, 'h', '', new ext_macro('hangupcall'));
	} // if trunk_type_needed


	/*
	;-------------------------------------------------------------------------------
	; macro-privacy-mgr:
	;
	; Privacy Manager Macro makes sure that any calls that don't pass the privacy manager are presented
	; with congestion since there have been observed cases of the call continuing if not stopped with a
	; congestion, and this provides a slightly more friendly 'sorry' message in case the user is
	; legitimately trying to be cooperative.
	;
	; Note: the following options are configurable in privacy.conf:
	;
	;	maxretries = 3 ; default value, number of retries before failing
	;	minlength = 10 ; default value, number of digits to be accepted as valid CID
	;
	;-------------------------------------------------------------------------------
	*/
	$context = 'macro-privacy-mgr';
	$exten = 's';

	$ext->add($context, $exten, '', new ext_set('KEEPCID', '${CALLERID(num)}'));
	$ext->add($context, $exten, '', new ext_set('TESTCID', '${IF($["${CALLERID(num):0:1}"="+"]?${MATH(1+${CALLERID(num):1})}:${MATH(1+${CALLERID(num)})})}'));
	$ext->add($context, $exten, '', new ext_execif('$[${LEN(${TESTCID})}=0]', 'Set', 'CALLERID(num)='));
	$ext->add($context, $exten, '', new ext_privacymanager('${ARG1},${ARG2}'));
	$ext->add($context, $exten, '', new ext_gotoif('$["${PRIVACYMGRSTATUS}"="FAILED"]', 'fail'));
	$ext->add($context, $exten, '', new ext_gosubif('$["${CALLED_BLACKLIST}"="1"]','app-blacklist-check,s,1'));
	$ext->add($context, $exten, '', new ext_set('CALLERID(num-pres)', 'allowed_passed_screen'));
	$ext->add($context, $exten, '', new ext_macroexit());

	$ext->add($context, $exten, 'fail', new ext_noop('STATUS: ${PRIVACYMGRSTATUS} CID: ${CALLERID(num)} ${CALLERID(name)} CALLPRES: ${CALLLINGPRES}'));
	$ext->add($context, $exten, '', new ext_playback('sorry-youre-having-problems&goodbye'));
	$ext->add($context, $exten, '', new ext_playtones('congestion'));
	$ext->add($context, $exten, '', new ext_congestion(20));
	$ext->add($context, 'h', '', new ext_hangup());

	/*
	* sets the CallerID of the device to that of the logged in user
	*
	* ${AMPUSER} is set upon return to the real user despite any aliasing that may
	* have been set as a result of the AMPUSER/<nnn>/cidnum field. This is used by
	* features like DND, CF, etc. to set the proper structure on aliased instructions
	*/
	$context = 'macro-user-callerid';
	$exten = 's';

	//$ext->add($context, $exten, '', new ext_noop('user-callerid: ${CALLERID(name)} ${CALLERID(number)}'));

	// for i18n playback in multiple languages
	$ext->add($context, 'lang-playback', '', new ext_gosubif('$[${DIALPLAN_EXISTS('.$context.',${CHANNEL(language)})}]', $context.',${CHANNEL(language)},${ARG1}', $context.',en,${ARG1}'));
	$ext->add($context, 'lang-playback', '', new ext_return());

	$ext->add($context, $exten, '', new ext_set('TOUCH_MONITOR','${UNIQUEID}'));
	// make sure AMPUSER is set if it doesn't get set below
	$ext->add($context, $exten, '', new ext_set('AMPUSER', '${IF($["${AMPUSER}" = ""]?${CALLERID(number)}:${AMPUSER})}'));
	$ext->add($context, $exten, '', new ext_gotoif('$["${CUT(CHANNEL,@,2):5:5}"="queue" | ${LEN(${AMPUSERCIDNAME})}]', 'report'));
	$ext->add($context, $exten, '', new ext_execif('$["${REALCALLERIDNUM:1:2}" = ""]', 'Set', 'REALCALLERIDNUM=${CALLERID(number)}'));
	$ext->add($context, $exten, '', new ext_set('AMPUSER', '${DB(DEVICE/${REALCALLERIDNUM}/user)}'));

	// Device & User: If they're not signed in, then they can't do anything.
	$ext->add($context, $exten, '', new ext_gotoif('$["${AMPUSER}" = "none"]', 'limit'));

	$ext->add($context, $exten, '', new ext_set('AMPUSERCIDNAME', '${DB(AMPUSER/${AMPUSER}/cidname)}'));
	$ext->add($context, $exten, '', new ext_gotoif('$["${AMPUSERCIDNAME:1:2}" = ""]', 'report'));

	// user may masquerade as a different user internally, so set the internal cid as indicated
	// but keep the REALCALLERID which is used to determine their true identify and lookup info
	// during outbound calls.
	$ext->add($context, $exten, '', new ext_set('AMPUSERCID', '${IF($["${ARG2}" != "EXTERNAL" & "${DB_EXISTS(AMPUSER/${AMPUSER}/cidnum)}" = "1"]?${DB_RESULT}:${AMPUSER})}'));

	// If there is a defined dialopts then use it, otherwise use the global default
	//
	$ext->add($context, $exten, '', new ext_set('__DIAL_OPTIONS', '${IF($["${DB_EXISTS(AMPUSER/${AMPUSER}/dialopts)}" = "1"]?${DB_RESULT}:${DIAL_OPTIONS})}'));

	$ext->add($context, $exten, '', new ext_set('CALLERID(all)', '"${AMPUSERCIDNAME}" <${AMPUSERCID}>'));

	$ext->add($context, $exten, '', new ext_noop_trace('Current Concurrency Count for ${AMPUSER}: ${GROUP_COUNT(${AMPUSER}@concurrency_limit)}, User Limit: ${DB(AMPUSER/${AMPUSER}/concurrency_limit)}'));
	$ext->add($context, $exten, '', new ext_gotoif('$["${ARG1}"="LIMIT" & ${LEN(${AMPUSER})} & ${DB_EXISTS(AMPUSER/${AMPUSER}/concurrency_limit)} & ${DB(AMPUSER/${AMPUSER}/concurrency_limit)}>0 & ${GROUP_COUNT(${AMPUSER}@concurrency_limit)}>=${DB(AMPUSER/${AMPUSER}/concurrency_limit)}]', 'limit'));
	$ext->add($context, $exten, '', new ext_execif('$["${ARG1}"="LIMIT" & ${LEN(${AMPUSER})}]', 'Set', 'GROUP(concurrency_limit)=${AMPUSER}'));
	/*
	* This is where to splice in things like setting the language based on a user's astdb setting,
	* or where you might set the CID account code based on a user instead of the device settings.
	*/

	$ext->add($context, $exten, 'report', new ext_gotoif('$[ "${ARG1}" = "SKIPTTL" | "${ARG1}" = "LIMIT" ]', 'continue'));
	$ext->add($context, $exten, 'report2', new ext_set('__TTL', '${IF($["foo${TTL}" = "foo"]?64:$[ ${TTL} - 1 ])}'));
	$ext->add($context, $exten, '', new ext_gotoif('$[ ${TTL} > 0 ]', 'continue'));
	$ext->add($context, $exten, '', new ext_wait('${RINGTIMER}'));  // wait for a while, to give it a chance to be picked up by voicemail
	$ext->add($context, $exten, '', new ext_answer());
	$ext->add($context, $exten, '', new ext_wait('1'));
	$ext->add($context, $exten, '', new ext_gosub('1', 'lang-playback', $context, 'hook_0'));
	$ext->add($context, $exten, '', new ext_macro('hangupcall'));
	$ext->add($context, $exten, 'limit', new ext_answer());
	$ext->add($context, $exten, '', new ext_wait('1'));
	$ext->add($context, $exten, '', new ext_gosub('1', 'lang-playback', $context, 'hook_1'));
	$ext->add($context, $exten, '', new ext_macro('hangupcall'));
	$ext->add($context, $exten, '', new ext_congestion(20));

	// Address Security Vulnerability in many earlier versions of Asterisk from an external source tranmitting a
	// malicious CID that can cause overflows in the Asterisk code.
	//
	$ext->add($context, $exten, 'continue', new ext_set('CALLERID(number)','${CALLERID(number):0:40}'));
	$ext->add($context, $exten, '', new ext_set('CALLERID(name)','${CALLERID(name):0:40}'));
	$ext->add($context, $exten, '', new ext_set('CDR(cnum)','${CALLERID(num)}'));
	$ext->add($context, $exten, '', new ext_set('CDR(cnam)','${CALLERID(name)}'));
	// CHANNEL(language) does not get inherited (which seems like an Asterisk bug as musicclass does)
	// so if whe have MASTER_CHANNEL() available to us let's rectify that
	//
	if ($amp_conf['AST_FUNC_MASTER_CHANNEL']) {
		$ext->add($context, $exten, '', new ext_set('CHANNEL(language)', '${MASTER_CHANNEL(CHANNEL(language))}'));
	}
	$ext->add($context, $exten, '', new ext_noop_trace('Using CallerID ${CALLERID(all)}'));
	$ext->add($context, 'h', '', new ext_macro('hangupcall'));

	$lang = 'en'; //English
	$ext->add($context, $lang, 'hook_0', new ext_playback('im-sorry&an-error-has-occurred&with&call-forwarding'));
	$ext->add($context, $lang, '', new ext_return());
	$ext->add($context, $lang, 'hook_1', new ext_playback('beep&im-sorry&your&simul-call-limit-reached&goodbye'));
	$ext->add($context, $lang, '', new ext_return());
	$lang = 'ja'; //Japanese
	$ext->add($context, $lang, 'hook_0', new ext_playback('im-sorry&call-forwarding&jp-no&an-error-has-occured'));
	$ext->add($context, $lang, '', new ext_return());
	$ext->add($context, $lang, 'hook_1', new ext_playback('beep&im-sorry&simul-call-limit-reached'));
	$ext->add($context, $lang, '', new ext_return());

	/*
	* arg1 = trunk number, arg2 = number
	*
	* Re-written to use enumlookup.agi
	*/

	// Is this the best place to put it in?
	// Check if we are using Google DNS for ENUM-lookups,
	// enable it as a global variable so we can use it in the agi
	if($amp_conf['USEGOOGLEDNSFORENUM']) {
		$ext->addGlobal('ENUMUSEGOOGLEDNS', 'TRUE');
	}

	$context = 'macro-dialout-enum';
	if (!empty($trunk_type_needed[$context])) {
		$exten = 's';

		$ext->add($context, $exten, '', new ext_gosubif('$[$["${ARG3}" != ""] & $["${DB(AMPUSER/${AMPUSER}/pinless)}" != "NOPASSWD"]]','sub-pincheck,s,1'));
		$ext->add($context, $exten, '', new ext_gotoif('$["x${OUTDISABLE_${DIAL_TRUNK}}" = "xon"]', 'disabletrunk,1'));
		$ext->add($context, $exten, '', new ext_set('DIAL_TRUNK_OPTIONS', '${IF($["${DB_EXISTS(TRUNK/${DIAL_TRUNK}/dialopts)}" = "1"]?${DB_RESULT}:${TRUNK_OPTIONS})}'));
		$ext->add($context, $exten, '', new ext_set('OUTBOUND_GROUP', 'OUT_${ARG1}'));
		$ext->add($context, $exten, '', new ext_gotoif('$["${OUTMAXCHANS_${ARG1}}foo" = "foo"]', 'nomax'));
		$ext->add($context, $exten, '', new ext_gotoif('$[ ${GROUP_COUNT(OUT_${ARG1})} >= ${OUTMAXCHANS_${ARG1}} ]', 'nochans'));
		$ext->add($context, $exten, 'nomax', new ext_set('DIAL_NUMBER', '${ARG2}'));
		$ext->add($context, $exten, '', new ext_set('DIAL_TRUNK', '${ARG1}'));
		$ext->add($context, $exten, '', new ext_gotoif('$["${INTRACOMPANYROUTE}" = "YES"]', 'skipoutcid'));  // Set to YES if treated like internal
		$ext->add($context, $exten, '', new ext_set('DIAL_TRUNK_OPTIONS', '${DIAL_OPTIONS}')); // will be reset to TRUNK_OPTIONS if not intra-company
		$ext->add($context, $exten, '', new ext_macro('outbound-callerid', '${DIAL_TRUNK}'));
		$ext->add($context, $exten, 'skipoutcid', new ext_gosubif('$["${PREFIX_TRUNK_${DIAL_TRUNK}}" != ""]','sub-flp-${DIAL_TRUNK},s,1'));  // manimpulate DIAL_NUMBER
		//  Replacement for asterisk's ENUMLOOKUP function
		$ext->add($context, $exten, '', new ext_agi('enumlookup.agi'));

		if ($amp_conf['AST_FUNC_CONNECTEDLINE'] && $amp_conf['OUTBOUND_DIAL_UPDATE']) {
			$ext->add($context, $exten, '', new ext_execif('$["${DB(AMPUSER/${AMPUSER}/cidname)}" != ""]','Set','CONNECTEDLINE(num,i)=${DIAL_NUMBER}'));
		}
		if ($amp_conf['AST_FUNC_CONNECTEDLINE'] && $amp_conf['OUTBOUND_CID_UPDATE']) {
			$ext->add($context, $exten, '', new ext_execif('$["${DB(AMPUSER/${AMPUSER}/cidname)}" != ""]','Set','CONNECTEDLINE(name,i)=CID:${CALLERID(number)}'));
		}

		// Now we have the variable DIALARR set to a list of URI's that can be called, in order of priority
		// Loop through them trying them in order.
		$ext->add($context, $exten, 'dialloop', new ext_gotoif('$["foo${DIALARR}"="foo"]', 's-${DIALSTATUS},1'));
		$ext->add($context, $exten, '', new ext_execif('$["${MOHCLASS}"!="default" & "${MOHCLASS}"!="" & "${FORCE_CONFIRM}"="" ]', 'Set', 'DIAL_TRUNK_OPTIONS=M(setmusic^${MOHCLASS})${DIAL_TRUNK_OPTIONS}'));
		$ext->add($context, $exten, '', new ext_execif('$["${FORCE_CONFIRM}"!="" ]', 'Set', 'DIAL_TRUNK_OPTIONS=M(confirm)${DIAL_TRUNK_OPTIONS}'));
		$ext->add($context, $exten, '', new ext_set('TRYDIAL', '${CUT(DIALARR,%,1)}'));
		$ext->add($context, $exten, '', new ext_set('DIALARR', '${CUT(DIALARR,%,2-)}'));
		$ext->add($context, $exten, '', new ext_dial('${TRYDIAL}', '${TRUNK_RING_TIMER},${DIAL_TRUNK_OPTIONS}'));
		// Now, if we're still here, that means the Dial failed for some reason.
		// If it's CONGESTION or CHANUNAVAIL we want to try again on a different
		// different channel. If there's no more left, the dialloop tag will exit.
		$ext->add($context, $exten, '', new ext_gotoif('$[ $[ "${DIALSTATUS}" = "CHANUNAVAIL" ] | $[ "${DIALSTATUS}" = "CONGESTION" ] ]', 'dialloop'));
		$ext->add($context, $exten, '', new ext_gotoif('$["${ARG4}" = "on"]','continue,1', 's-${DIALSTATUS},1'));
		// Here are the exit points for the macro.
		$ext->add($context, $exten, 'nochans', new ext_noop('max channels used up'));

		$exten = 's-BUSY';
		/*
		* HANGUPCAUSE 17 = Busy, or SIP 486 Busy everywhere
		*/
		$ext->add($context, $exten, '', new ext_noop('Dial failed due to trunk reporting BUSY - giving up'));
		$ext->add($context, $exten, '', new ext_playtones('busy'));
		$ext->add($context, $exten, '', new ext_busy(20));

		/*
		* There are reported bugs in Asterisk Blind Trasfers that result in Dial() returning and continuing
		* execution with a status of ANSWER. So we hangup at this point
		*/
		$exten = 's-ANSWER';
		$ext->add($context, $exten, '', new ext_noop('Call successfully answered - Hanging up now'));
		$ext->add($context, $exten, '', new ext_macro('hangupcall'));

		$exten = 's-NOANSWER';
		/*
		* HANGUPCAUSE 18 = No User Responding, or SIP 408 Request Timeout
		* HANGUPCAUSE 19 = No Answer From The User, or SIP 480 Temporarily unavailable, SIP 483 To many hops
		*/
		$ext->add($context, $exten, '', new ext_noop('Dial failed due to trunk reporting NOANSWER - giving up'));
		$ext->add($context, $exten, '', new ext_progress());
		switch ($trunkreportmsg_ids['no_answer_msg_id']) {
			case DEFAULT_MSG:
			$ext->add($context, $exten, '', new ext_playback('number-not-answering,noanswer'));
			break;
			case CONGESTION_TONE:
			$ext->add($context, $exten, '', new ext_playtones('congestion'));
			break;
			default:
			$message = recordings_get_file($trunkreportmsg_ids['no_answer_msg_id']);
			$message = ($message != "") ? $message : "number-not-answering";
			$ext->add($context, $exten, '', new ext_playback("$message, noanswer"));
		}
		$ext->add($context, $exten, '', new ext_congestion(20));

		$exten = 's-INVALIDNMBR';
		/*
		* HANGUPCAUSE 28 = Address Incomplete, or SIP 484 Address Incomplete
		*/
		$ext->add($context, $exten, '', new ext_noop('Dial failed due to trunk reporting Address Incomplete - giving up'));
		$ext->add($context, $exten, '', new ext_progress());
		switch ($trunkreportmsg_ids['invalidnmbr_msg_id']) {
			case DEFAULT_MSG:
			$ext->add($context, $exten, '', new ext_playback('ss-noservice,noanswer'));
			break;
			case CONGESTION_TONE:
			$ext->add($context, $exten, '', new ext_playtones('congestion'));
			break;
			default:
			$message = recordings_get_file($trunkreportmsg_ids['invalidnmbr_msg_id']);
			$message = ($message != "") ? $message : "ss-noservice";
			$ext->add($context, $exten, '', new ext_playback("$message, noanswer"));
		}
		$ext->add($context, $exten, '', new ext_busy(20));

		$exten = "s-CHANGED";
		$ext->add($context, $exten, '', new ext_noop('Dial failed due to trunk reporting Number Changed - giving up'));
		$ext->add($context, $exten, '', new ext_playtones('busy'));
		$ext->add($context, $exten, '', new ext_busy(20));

		$exten = '_s-.';
		$ext->add($context, $exten, '', new ext_set('RC', '${IF($[${ISNULL(${HANGUPCAUSE})}]?0:${HANGUPCAUSE})}'));
		$ext->add($context, $exten, '', new ext_goto('1','${RC}'));

		$ext->add($context, '17', '', new ext_goto('1','s-BUSY'));
		$ext->add($context, '18', '', new ext_goto('1','s-NOANSWER'));
		$ext->add($context, '22', '', new ext_goto('1','s-CHANGED'));
		$ext->add($context, '23', '', new ext_goto('1','s-CHANGED'));
		$ext->add($context, '28', '', new ext_goto('1','s-INVALIDNMBR'));
		$ext->add($context, '_X', '', new ext_goto('1','continue'));
		$ext->add($context, '_X.', '', new ext_goto('1','continue'));

		$exten = 'continue';
		if ($generate_trunk_monitor_failure) {
			$ext->add($context, $exten, '', new ext_gotoif('$["${OUTFAIL_${ARG1}}" = ""]', 'noreport'));
			$ext->add($context, $exten, '', new ext_agi('${OUTFAIL_${ARG1}}'));
		}
		$ext->add($context, $exten, 'noreport', new ext_noop('TRUNK Dial failed due to ${DIALSTATUS} HANGUPCAUSE: ${HANGUPCAUSE} - failing through to other trunks'));
		$ext->add($context, $exten, '', new ext_execif('$["${AMPUSER}"!="" ]', 'Set', 'CALLERID(number)=${AMPUSER}'));

		$ext->add($context, 'disabletrunk', '', new ext_noop('TRUNK: ${OUT_${DIAL_TRUNK}} DISABLED - falling through to next trunk'));
		$ext->add($context, 'bypass', '', new ext_noop('TRUNK: ${OUT_${DIAL_TRUNK}} BYPASSING because dialout-trunk-predial-hook'));

		$ext->add($context, 'h', '', new ext_macro('hangupcall'));
	} // if trunk_type_needed


	/*
	* overrides CallerID out trunks
	* arg1 is trunk
	* macro-user-callerid should be called _before_ using this macro
	*/

	$context = 'macro-outbound-callerid';
	$exten = 's';

	// If we modified the caller presence, set it back. This allows anonymous calls to be internally prepended but keep
	// their status if forwarded back out. Not doing this can result in the trunk CID being displayed vs. 'blocked call'
	//
	$ext->add($context,$exten,'',new ext_execif('$["${CALLINGNAMEPRES_SV}" != ""]', 'Set', 'CALLERPRES(name-pres)=${CALLINGNAMEPRES_SV}'));
	$ext->add($context,$exten,'',new ext_execif('$["${CALLINGNUMPRES_SV}" != ""]', 'Set', 'CALLERPRES(num-pres)=${CALLINGNUMPRES_SV}'));

	// Keep the original CallerID number, for failover to the next trunk.

	$ext->add($context, $exten, '', new ext_execif('$["${REALCALLERIDNUM:1:2}" = ""]', 'Set', 'REALCALLERIDNUM=${CALLERID(number)}'));
	// If this came through a ringgroup or CF, then we want to retain original CID unless
	// OUTKEEPCID_${trunknum} is set.
	// Save then CIDNAME while it is still intact in case we end up sending out this same CID
	$ext->add($context, $exten, 'start', new ext_gotoif('$[ $["${REALCALLERIDNUM}" = ""] | $["${KEEPCID}" != "TRUE"] | $["${OUTKEEPCID_${ARG1}}" = "on"] ]', 'normcid'));  // Set to TRUE if coming from ringgroups, CF, etc.
	$ext->add($context, $exten, '', new ext_set('USEROUTCID', '${REALCALLERIDNUM}'));
	//$ext->add($context, $exten, '', new ext_set('REALCALLERIDNAME', '${CALLERID(name)}'));

	// We now have to make sure the CID is valid. If we find an AMPUSER with the same CID, we assume it is an internal
	// call (would be quite a conincidence if not) and go through the normal processing to get that CID. If a device
	// is set for this CID, then it must be internal
	// If we end up using USEROUTCID at the end, it may still be the REALCALLERIDNUM we saved above. That is determined
	// if the two are equal, AND there is no CALLERID(name) present since it has been removed by the CALLERID(all)=${USEROUTCID}
	// setting. If this is the case, then we put the orignal name back in to send out. Although the CNAME is not honored by most
	// carriers, there are cases where it is so this preserves that information to be used by those carriers who do honor it.
	$ext->add($context, $exten, '', new ext_gotoif('$["foo${DB(AMPUSER/${REALCALLERIDNUM}/device)}" = "foo"]', 'bypass'));

	$ext->add($context, $exten, 'normcid', new ext_set('USEROUTCID', '${DB(AMPUSER/${AMPUSER}/outboundcid)}'));
	$ext->add($context, $exten, 'bypass', new ext_set('EMERGENCYCID', '${DB(DEVICE/${REALCALLERIDNUM}/emergency_cid)}'));
	$ext->add($context, $exten, '', new ext_set('TRUNKOUTCID', '${OUTCID_${ARG1}}'));
	$ext->add($context, $exten, '', new ext_gotoif('$["${EMERGENCYROUTE:1:2}" = "" | "${EMERGENCYCID:1:2}" = ""]', 'trunkcid'));  // check EMERGENCY ROUTE
	$ext->add($context, $exten, '', new ext_set('CALLERID(all)', '${EMERGENCYCID}'));  // emergency cid for device
	$ext->add($context, $exten, '', new ext_set('CDR(outbound_cnum)','${CALLERID(num)}'));
	$ext->add($context, $exten, '', new ext_set('CDR(outbound_cnam)','${CALLERID(name)}'));
	$ext->add($context, $exten, 'exit', new ext_macroexit());


	$ext->add($context, $exten, 'trunkcid', new ext_execif('$[${LEN(${TRUNKOUTCID})} != 0]', 'Set', 'CALLERID(all)=${TRUNKOUTCID}'));
	$ext->add($context, $exten, 'usercid', new ext_execif('$[${LEN(${USEROUTCID})} != 0]', 'Set', 'CALLERID(all)=${USEROUTCID}'));  // check CID override for extension
	/* TRUNKCIDOVERRIDE is used by followme and can be used by other functions. It forces the specified CID except for the case of
	* an Emergency CID on an Emergency Route
	*/
	$ext->add($context, $exten, '', new ext_execif('$[${LEN(${TRUNKCIDOVERRIDE})} != 0 | ${LEN(${FORCEDOUTCID_${ARG1}})} != 0]', 'Set', 'CALLERID(all)=${IF($[${LEN(${FORCEDOUTCID_${ARG1}})}=0]?${TRUNKCIDOVERRIDE}:${FORCEDOUTCID_${ARG1}})}'));
	$ext->add($context, $exten, 'hidecid', new ext_execif('$["${CALLERID(name)}"="hidden"]', 'Set', 'CALLERPRES(name-pres)=prohib_passed_screen'));
	//We are checking to see if the CallerID name is <hidden> (from freepbx) so we hide both the name and the number. I believe this is correct.
	$ext->add($context, $exten, '', new ext_execif('$["${CALLERID(name)}"="hidden"]', 'Set', 'CALLERPRES(num-pres)=prohib_passed_screen'));
	// $has_keepcid_cnum is checked and set when the globals are being generated above
	//
	if ($has_keepcid_cnum || $amp_conf['BLOCK_OUTBOUND_TRUNK_CNAM']) {
		if ($amp_conf['BLOCK_OUTBOUND_TRUNK_CNAM']) {
			$ext->add($context, $exten, '', new ext_set('CALLERID(name)', ''));
		} else {
			$ext->add($context, $exten, '', new ext_execif('$["${OUTKEEPCID_${ARG1}}" = "cnum"]', 'Set', 'CALLERID(name)='));
		}
	}
	$ext->add($context, $exten, '', new ext_set('CDR(outbound_cnum)','${CALLERID(num)}'));
	$ext->add($context, $exten, '', new ext_set('CDR(outbound_cnam)','${CALLERID(name)}'));


	// Combined from-zpatel / from-dahdi and all macros now from-dahdi-channum
	//
	$ext->addInclude('from-zaptel', 'from-dahdi');
	$ext->add('from-zaptel', 'foo','', new ext_noop('bar'));

	$context = 'from-dahdi';
	$exten = '_X.';

	$ext->add($context, $exten, '', new ext_set('DID', '${EXTEN}'));
	$ext->add($context, $exten, '', new ext_goto(1, 's'));

	$exten = 's';
	$ext->add($context, $exten, '', new ext_noop('Entering from-dahdi with DID == ${DID}'));
	// Some trunks _require_ a RINGING be sent before an Answer.
	$ext->add($context, $exten, '', new ext_ringing());
	// If ($did == "") { $did = "s"; }
	$ext->add($context, $exten, '', new ext_set('DID', '${IF($["${DID}"= ""]?s:${DID})}'));
	$ext->add($context, $exten, '', new ext_noop('DID is now ${DID}'));
	$ext->add($context, $exten, '', new ext_gotoif('$["${CHANNEL:0:5}"="DAHDI"]', 'dahdiok', 'checkzap'));
	$ext->add($context, $exten, 'checkzap', new ext_gotoif('$["${CHANNEL:0:3}"="Zap"]', 'zapok', 'neither'));
	$ext->add($context, $exten, 'neither', new ext_goto('1', '${DID}', 'from-pstn'));
	// If there's no ext-did,s,1, that means there's not a no did/no cid route. Hangup.
	$ext->add($context, $exten, '', new ext_macro('Hangupcall', 'dummy'));

	$ext->add($context, $exten, 'dahdiok', new ext_noop('Is a DAHDi Channel'));
	$ext->add($context, $exten, '', new ext_set('CHAN', '${CHANNEL:6}'));
	$ext->add($context, $exten, '', new ext_set('CHAN', '${CUT(CHAN,-,1)}'));
	$ext->add($context, $exten, '', new ext_macro('from-dahdi-${CHAN}', '${DID},1'));
	// If nothing there, then treat it as a DID
	$ext->add($context, $exten, '', new ext_noop('Returned from Macro from-dahdi-${CHAN}'));
	$ext->add($context, $exten, '', new ext_goto(1, '${DID}', 'from-pstn'));

	$ext->add($context, $exten, 'zapok', new ext_noop('Is a Zaptel Channel'));
	$ext->add($context, $exten, '', new ext_set('CHAN', '${CHANNEL:4}'));
	$ext->add($context, $exten, '', new ext_set('CHAN', '${CUT(CHAN,-,1)}'));
	$ext->add($context, $exten, '', new ext_macro('from-dahdi-${CHAN}', '${DID},1'));
	$ext->add($context, $exten, '', new ext_noop('Returned from Macro from-dahdi-${CHAN}'));
	$ext->add($context, $exten, '', new ext_goto(1, '${DID}', 'from-pstn'));

	/*
	;------------------------------------------------------------------------
	; [macro-dial-confirm]
	;------------------------------------------------------------------------
	; This has now been incorporated into dialparties. It still only works with ringall
	; and ringall-prim strategies. Have not investigated why it doesn't work with
	; hunt and memory hunt.
	;
	;------------------------------------------------------------------------
	[macro-dial-confirm]
	; This was written to make it easy to use macro-dial-confirm instead of macro-dial in generated dialplans.
	; This takes the same parameters, with an additional parameter of the ring group Number
	; ARG1 is the timeout
	; ARG2 is the DIAL_OPTIONS
	; ARG3 is a list of xtns to call - 203-222-240-123123123#-211
	; ARG4 is the ring group number
	*/

	$mcontext = 'macro-dial-confirm';
	$exten = 's';

	// set to ringing so confirm macro can keep from passing the channel during confirmation if
	// someone beat them to it.
	//
	$ext->add($mcontext, $exten, '', new ext_set('DB(RG/${ARG4}/${CHANNEL})','RINGING'));
	$ext->add($mcontext, $exten, '', new ext_set('__UNIQCHAN','${CHANNEL}'));

	// Tell dialparites to place the call through the [grps] context
	//
	$ext->add($mcontext, $exten, '', new ext_set('USE_CONFIRMATION','TRUE'));
	$ext->add($mcontext, $exten, '', new ext_set('RINGGROUP_INDEX','${ARG4}'));

	$ext->add($mcontext, $exten, '', new ext_set('FORCE_CONFIRM',''));
	$ext->add($mcontext, $exten, '', new ext_set('ARG4',''));
	$ext->add($mcontext, $exten, '', new ext_macro('dial','${ARG1},${ARG2},${ARG3}'));
	$ext->add($mcontext, $exten, '', new ext_dbdel('RG/${RINGGROUP_INDEX}/${CHANNEL}'));
	$ext->add($mcontext, $exten, '', new ext_set('USE_CONFIRMATION',''));
	$ext->add($mcontext, $exten, '', new ext_set('RINGGROUP_INDEX',''));

	/*
	;------------------------------------------------------------------------
	; [macro-setmusic]
	;------------------------------------------------------------------------
	; CONTEXT:      macro-setmusic
	; PURPOSE:      to turn off moh on routes where it is not desired
	;
	;------------------------------------------------------------------------
	[macro-setmusic]
	exten => s,1,NoOp(Setting Outbound Route MoH To: ${ARG1})
	exten => s,2,Set(CHANNEL(musicclass)=${ARG1}) ; this won't work in 1.2 anymore, could fix in auto-generate if we wanted...
	;------------------------------------------------------------------------
	*/
	$mcontext = 'macro-setmusic';
	$exten = 's';

	$ext->add($mcontext, $exten, '', new ext_noop_trace('Setting Outbound Route MoH To: ${ARG1}'));
	$ext->add($mcontext, $exten, '', new ext_setmusiconhold('${ARG1}'));


	/*
	;------------------------------------------------------------------------
	; [block-cf]
	;------------------------------------------------------------------------
	; This context is set as a target with FORWARD_CONTEXT when Call Forwarding is set to be
	; ignored in a ringgroup or other features that may take advantage of this. Server side
	; CF is done in dialparties.agi but if a client device forwards a call, it will be caught
	; and blocked here.
	;------------------------------------------------------------------------
	[block-cf]
	exten => _X.,1,Noop(Blocking callforward to ${EXTEN} because CF is blocked)
	exten => _X.,n,Hangup()

	;------------------------------------------------------------------------
	*/
	$context = 'macro-block-cf';
	$exten = '_X.';

	$ext->add($context, $exten, '', new ext_noop_trace('Blocking callforward to ${EXTEN} because CF is blocked'));
	$ext->add($context, $exten, '', new ext_hangup(''));


	/*
	* macro-vm
	*/

	/*
	;------------------------------------------------------------------------
	; [macro-vm]
	;------------------------------------------------------------------------
	; CONTEXT:      macro-vm
	; PURPOSE:      call voicemail system and extend with personal ivr
	;
	; Under normal use, this macro will call the voicemail system with the extension and
	; desired greeting mode of busy, unavailable or as specified with direct voicemail
	; calls (usually unavailable) when entered from destinations.
	;
	; The voicemail system's two greetings have been 'hijacked' as follows to extend the
	; system by giving the option of a private 'ivr' for each voicemail user. The following
	; applies to both the busy and unavailable modes of voicemail and can be applied to one
	; or both, and differently.
	;
	; Global Defaults:
	;
	; The following are default values, used in both busy and unavail modes if no specific
	; values are specified.
	;
	; VMX_REPEAT
	;                                       The number of times to repeat the users message if no option is pressed.
	; VMX_TIMEOUT
	;                                       The timeout to wait after playing message before repeating or giving up.
	; VMX_LOOPS
	;                                       The number of times it should replay the message and check for an option when
	;                                       an invalid option is pressed.
	;
	; VMX_OPTS_DOVM
	;                                       Default voicemail option to use if vm is chosen as an option. No options will
	;                                       cause Allison's generic message, 's' will go straight to beep.
	; VMX_OPTS_TIMEOUT
	;                                       Default voicemail option to use if it times out with no options. No options will
	;                                       cause Allison's generic message, 's' will go straight to beep.
	;                                       IF THE USER PRESSES # - it will look like a timeout as well since no option will
	;                                       be presented. If the user wishes to enable a mode where a caller can press #
	;                                       during their message and it goes straight to voicemail with only a 'beep' then
	;                                       this should be set to 's'.
	; VMX_OPTS_LOOP
	;                                       Default voicemail option to use if to many wrong options occur. No options will
	;                                       cause Allison's generic message, 's' will go straight to beep.
	;
	; VMX_CONTEXT
	;                                       Default context for user destinations if not supplied in the user's settings
	; VMX_PRI
	;                                       Default priority for user destinations if not supplied in the user's settings
	;
	; VMX_TIMEDEST_CONTEXT
	;                                       Default context for timeout destination if not supplied in the user's settings
	; VMX_TIMEDEST_EXT
	;                                       Default extension for timeout destination if not supplied in the user's settings
	; VMX_TIMEDEST_PRI
	;                                       Default priority for timeout destination if not supplied in the user's settings
	;
	; VMX_LOOPDEST_CONTEXT
	;                                       Default context for loops  destination if not supplied in the user's settings
	; VMX_LOOPDEST_EXT
	;                                       Default extension for loops  destination if not supplied in the user's settings
	; VMX_LOOPDEST_PRI
	;                                       Default priority for loops  destination if not supplied in the user's settings
	;
	;
	; The AMPUSER database variable has been extended with a 'vmx' tree (vm-extension). A
	; duplicate set is included for both unavail and busy. You could choose for to have an
	; ivr when unavail is taken, but not with busy - or a different once with busy.
	; The full list is below, each specific entry is futher described:
	;
	; state:                Whether teh current mode is enabled or disabled. Anything but 'enabled' is
	;                                               treated as disabled.
	; repeat:               This is the number of times that the users message should be played after the
	;                                               timeout if the user has not entered anything. It is just a variable to the
	;                                               Read() function which will do the repeating.
	; timeout:      This is how long to wait after the message has been read for a response from
	;                                               the user. A caller can enter a digit any time during the playback.
	; loops:                This is the number of loops that the system will allow a caller to retry if
	;                                               they enter a bad menu choice, before going to the loop failover destination
	; vmxopts:      This is the vm options to send to the voicemail command used when a specific
	;                                               voicemail destination is chosen (inidcated by 'dovm' in the ext field). This is
	;                                               typically either set to 's' or left blank. When set to 's' there will be no
	;                                               message played when entering the voicemail, just a beep. When blank, you will
	;                                               have Allison's generic message played. It is not typical to play the greetings
	;                                               since they have been 'hijacked' for these IVR's and from a caller's perspecitive
	;                                               this system appears interconnected with the voicemail so instructions can be
	;                                               left there.
	; timedest: The three variables: ext, context and pri are the goto destination if the caller
	;                                               enters no options and it timesout. None have to be set and a system default
	;                                               will be used. If just ext is set, then defaults will be used for context and
	;                                               pri, etc.
	; loopdest:     This is identical to timedest but used if the caller exceeds the maximum invalid
	;                                               menu choices.
	; [0-9*]:               The user can specify up to 11 ivr options, all as single digits from 0-9 or *. The
	;                                               # key can not be used since it is used as a terminator key for the Read command
	;                                               and will never be returned. A minimum of the ext must be specified for each valid
	;                                               option and as above, the context and priority can also be specified if the default
	;                                               is not to be used.
	;                                               Option '0' takes on a special meaning. Since a user is able to break out of the
	;                                               voicemail command once entering it with a 0, if specified, the 0 destination will
	;                                               be used.
	;                                               Option '*' can also be used to breakout. It is undecided at this point whether
	;                                               providing that option will be used as well. (probably should).
	;
	;
	; /AMPUSER/<ext>/vmx/[busy|unavail]/state:                                                              enabled|disabled
	; /AMPUSER/<ext>/vmx/[busy|unavail]/repeat:                                                             n (times to repeat message)
	; /AMPUSER/<ext>/vmx/[busy|unavail]/timeout:                                                    n (timeout to wait for digit)
	; /AMPUSER/<ext>/vmx/[busy|unavail]/loops:                                                              n (loop returies for invalid entries)
	; /AMPUSER/<ext>/vmx/[busy|unavail]/vmxopts/dovm:                                       vmoptions (if ext is dovm)
	; /AMPUSER/<ext>/vmx/[busy|unavail]/vmxopts/timeout:                    vmoptions (if timeout)
	; /AMPUSER/<ext>/vmx/[busy|unavail]/vmxopts/loops:                              vmoptions (if loops)
	; /AMPUSER/<ext>/vmx/[busy|unavail]/timedest/ext:                                       extension (if timeout)
	; /AMPUSER/<ext>/vmx/[busy|unavail]/timedest/context:                   context (if timeout)
	; /AMPUSER/<ext>/vmx/[busy|unavail]/timedest/pri:                                       priority (if timeout)
	; /AMPUSER/<ext>/vmx/[busy|unavail]/loopdest/ext:                                       extension (if too many failures)
	; /AMPUSER/<ext>/vmx/[busy|unavail]/loopdest/context:                   context (if too many failures)
	; /AMPUSER/<ext>/vmx/[busy|unavail]/loopdest/pri:                                       priority (if too many failures)
	; /AMPUSER/<ext>/vmx/[busy|unavail]/[0-9*]/ext:                                         extension (dovm for vm access)
	; /AMPUSER/<ext>/vmx/[busy|unavail]/[0-9*]/context:                             context
	; /AMPUSER/<ext>/vmx/[busy|unavail]/[0-9*]/pri:                                         priority
	;------------------------------------------------------------------------

	*/
	// ARG1 - extension
	// ARG2 - DIRECTDIAL/BUSY
	// ARG3 - RETURN makes macro return, otherwise hangup
	//
	$ext->add('macro-vm', 's', '', new ext_macro('user-callerid', 'SKIPTTL'));
	$ext->add('macro-vm','s', '', new ext_setvar("VMGAIN", '${IF($["foo${VM_GAIN}"!="foo"]?"g(${VM_GAIN})": )}'));

	// If blkvm-check is set TRUE, then someone told us to block calls from going to
	// voicemail. This variable is reset by the answering channel so subsequent
	// transfers will properly function.
	//
	$ext->add('macro-vm','s', '', new ext_macro('blkvm-check'));
	$ext->add('macro-vm','s', '', new ext_gotoif('$["${GOSUB_RETVAL}" != "TRUE"]','vmx,1'));

	// we didn't branch so block this from voicemail
	//
	$ext->add('macro-vm','s', '', new ext_noop_trace('CAME FROM: ${NODEST} - Blocking VM macro-blkvm-check returned TRUE'));
	$ext->add('macro-vm','s', '', new ext_hangup(''));

	// If vmx not enabled for the current mode,then jump to normal voicemail behavior
	// also - if not message (no-msg) is requested, straight to voicemail
	//

	$ext->add('macro-vm','vmx', '', new ext_setvar("MEXTEN", '${ARG1}'));
	$ext->add('macro-vm','vmx', '', new ext_setvar("MMODE", '${ARG2}'));
	$ext->add('macro-vm','vmx', '', new ext_setvar("RETVM", '${ARG3}'));
	$ext->add('macro-vm','vmx', '', new ext_setvar("MODE", '${IF($["${MMODE}"="BUSY"]?busy:unavail)}'));
	$ext->add('macro-vm','vmx', '', new ext_macro('get-vmcontext', '${MEXTEN}'));
	$ext->add('macro-vm','vmx', '', new ext_setvar("MODE", '${IF($[(${STAT(f,${ASTSPOOLDIR}/voicemail/${VMCONTEXT}/${MEXTEN}/temp.wav)} = 1) || (${STAT(f,${ASTSPOOLDIR}/voicemail/${VMCONTEXT}/${MEXTEN}/temp.WAV)} = 1)]?temp:${MODE})}'));
	$ext->add('macro-vm','vmx', '', new ext_noop('MODE IS: ${MODE}'));
	// If this use has individual option set for playing standardized message, then override the global option
	// but only if the vmx state is 'enabled'
	//
	$ext->add('macro-vm','vmx', '', new ext_gotoif('$["${DB(AMPUSER/${MEXTEN}/vmx/${MODE}/state)}" != "enabled"]','chknomsg'));
	/* Replaced
	$ext->add('macro-vm','vmx', '', new ext_gotoif('$["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/vmxopts/timeout)}" = "0"]','chknomsg'));
	$ext->add('macro-vm','vmx', '', new ext_setvar("VM_OPTS", '${DB_RESULT}'));
	*/
	$ext->add('macro-vm','vmx', '', new ext_set('VM_OPTS', '${IF($["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/vmxopts/timeout)}" = "1"]?${DB_RESULT}:${VM_OPTS})}'));
	$ext->add('macro-vm','vmx', 'chknomsg', new ext_gotoif('$["${MMODE}"="NOMESSAGE"]','s-${MMODE},1'));
	$ext->add('macro-vm','vmx', '', new ext_gotoif('$["${MMODE}" != "DIRECTDIAL"]','notdirect'));
	$ext->add('macro-vm','vmx', '', new ext_setvar("MODE", '${IF($["${REGEX("[b]" ${VM_DDTYPE})}" = "1"]?busy:${MODE})}'));
	$ext->add('macro-vm','vmx', 'notdirect', new ext_NoOp('Checking if ext ${MEXTEN} is enabled: ${DB(AMPUSER/${MEXTEN}/vmx/${MODE}/state)}'));
	$ext->add('macro-vm','vmx', '', new ext_gotoif('$["${DB(AMPUSER/${MEXTEN}/vmx/${MODE}/state)}" != "enabled"]','s-${MMODE},1'));

	// If the required voicemail file does not exist, then abort and go to normal voicemail behavior
	//
	// If 1.4 or above, use the STAT function to check for the file. Prior to 1.4, use the AGI script since the System() command tried
	// in the past had errors.
	//

	//$ext->add('macro-vm', 'vmx', '', new ext_trysystem('/bin/ls ${ASTSPOOLDIR}/voicemail/${VMCONTEXT}/${MEXTEN}/${MODE}.[wW][aA][vV]'));
	if ($ast_ge_14) {
		$ext->add('macro-vm','vmx', '', new ext_gotoif('$[(${STAT(f,${ASTSPOOLDIR}/voicemail/${VMCONTEXT}/${MEXTEN}/temp.wav)} = 1) || (${STAT(f,${ASTSPOOLDIR}/voicemail/${VMCONTEXT}/${MEXTEN}/temp.WAV)} = 1)]','tmpgreet'));
		$ext->add('macro-vm','vmx', '', new ext_gotoif('$[(${STAT(f,${ASTSPOOLDIR}/voicemail/${VMCONTEXT}/${MEXTEN}/${MODE}.wav)} = 0) && (${STAT(f,${ASTSPOOLDIR}/voicemail/${VMCONTEXT}/${MEXTEN}/${MODE}.WAV)} = 0)]','nofile'));
	} else {
		$ext->add('macro-vm', 'vmx', '',new ext_agi('checksound.agi,${ASTSPOOLDIR}/voicemail/${VMCONTEXT}/${MEXTEN}/temp'));
		$ext->add('macro-vm','vmx', '', new ext_gotoif('$["${SYSTEMSTATUS}" = "SUCCESS"]','tmpgreet'));
		$ext->add('macro-vm', 'vmx', '',new ext_agi('checksound.agi,${ASTSPOOLDIR}/voicemail/${VMCONTEXT}/${MEXTEN}/${MODE}'));
		$ext->add('macro-vm','vmx', '', new ext_gotoif('$["${SYSTEMSTATUS}" != "SUCCESS"]','nofile'));
	}

	$repeat = sql("SELECT `value` FROM `voicemail_admin` WHERE `variable` = 'VMX_REPEAT'", "getOne");
	$to = sql("SELECT `value` FROM `voicemail_admin` WHERE `variable` = 'VMX_TIMEOUT'", "getOne");
	$loops = sql("SELECT `value` FROM `voicemail_admin` WHERE `variable` = 'VMX_LOOPS'", "getOne");
	$ext->add('macro-vm','vmx', '', new ext_set("VMX_TIMEOUT", (isset($to) ? $to : 2)));
	$ext->add('macro-vm','vmx', '', new ext_set("VMX_REPEAT", (isset($repeat) ? $repeat : 1)));
	$ext->add('macro-vm','vmx', '', new ext_set("VMX_LOOPS", (isset($loops) ? $loops : 1)));
	$ext->add('macro-vm','vmx', '', new ext_setvar("LOOPCOUNT", '0'));
	/* Replaced
	$ext->add('macro-vm','vmx', '', new ext_gotoif('$["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/repeat)}" = "0"]','vmxtime'));
	$ext->add('macro-vm','vmx', '', new ext_setvar("VMX_REPEAT", '${DB_RESULT}'));
	*/
	$ext->add('macro-vm','vmx', '', new ext_set('VMX_REPEAT', '${IF($["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/repeat)}" = "1"]?${DB_RESULT}:${VMX_REPEAT})}'));

	/* Replaced
	$ext->add('macro-vm','vmx', 'vmxtime', new ext_gotoif('$["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/timeout)}" = "0"]','vmxloops'));
	$ext->add('macro-vm','vmx', '', new ext_setvar("VMX_TIMEOUT", '${DB_RESULT}'));
	*/
	$ext->add('macro-vm','vmx', 'vmxtime', new ext_set('VMX_TIMEOUT', '${IF($["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/timeout)}" = "1"]?${DB_RESULT}:${VMX_TIMEOUT})}'));

	/* Replaced
	$ext->add('macro-vm','vmx', 'vmxloops', new ext_gotoif('$["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/loops)}" = "0"]','vmxanswer'));
	$ext->add('macro-vm','vmx', '', new ext_setvar("VMX_LOOPS", '${DB_RESULT}'));
	*/
	$ext->add('macro-vm','vmx', 'vmxloops', new ext_set('VMX_LOOPS', '${IF($["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/loops)}" = "1"]?${DB_RESULT}:${VMX_LOOPS})}'));
	$ext->add('macro-vm','vmx','vmxanswer',new ext_answer(''));

	// Now play the users voicemail recording as the basis for their ivr, the Read command will repeat as needed and if it timesout
	// then we go to the timeout. Otherwise handle invalid options by looping until the limit until a valid option is played.
	//
	$ext->add('macro-vm','vmx','loopstart',new ext_read('ACTION', '${ASTSPOOLDIR}/voicemail/${VMCONTEXT}/${MEXTEN}/${MODE}', 1, 'skip', '${VMX_REPEAT}', '${VMX_TIMEOUT}'));
	$ext->add('macro-vm','vmx', '', new ext_gotoif('$["${EXISTS(${ACTION})}" = "1"]','checkopt'));

	// If we are here we timed out, go to the required destination
	//
	$ext->add('macro-vm','vmx', 'noopt', new ext_NoOp('Timeout: going to timeout dest'));
	// this is always set, if not it will default to no options
	$ext->add('macro-vm','vmx', '', new ext_set('VMX_OPTS', '${DB(AMPUSER/${MEXTEN}/vmx/${MODE}/vmxopts/timeout)}'));

	// TODO should we just go do the other sets and skip the complexity, will have to if we remove the globals since they will be gonein dotime
	$ext->add('macro-vm','vmx', 'chktime', new ext_gotoif('$["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/timedest/ext)}" = "0"]','dotime'));
	$ext->add('macro-vm','vmx', '', new ext_setvar("VMX_TIMEDEST_EXT",'${DB_RESULT}'));
	/* this is the alternative if re the above TODO
	$ext->add('macro-vm','vmx', 'chktime', new ext_set('VMX_TIMEDEST_EXT', '${IF($["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/timedest/ext)}" = "1"]?${DB_RESULT}:${VMX_TIMEDEST_EXT})}'));
	*/

	/* Replaced
	$ext->add('macro-vm','vmx', '', new ext_gotoif('$["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/timedest/context)}" = "0"]','timepri'));
	$ext->add('macro-vm','vmx', '', new ext_setvar("VMX_TIMEDEST_CONTEXT",'${DB_RESULT}'));
	*/
	$ext->add('macro-vm','vmx', '', new ext_set('VMX_TIMEDEST_CONTEXT', '${IF($["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/timedest/context)}" = "1"]?${DB_RESULT}:${VMX_TIMEDEST_CONTEXT})}'));

	/* Replaced
	$ext->add('macro-vm','vmx', 'timepri', new ext_gotoif('$["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/timedest/pri)}" = "0"]','dotime'));
	$ext->add('macro-vm','vmx', '', new ext_setvar("VMX_TIMEDEST_PRI",'${DB_RESULT}'));
	*/
	$ext->add('macro-vm','vmx', 'timepri', new ext_set('VMX_TIMEDEST_PRI', '${IF($["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/timedest/pri)}" = "1"]?${DB_RESULT}:${VMX_TIMEDEST_PRI})}'));

	$ext->add('macro-vm','vmx','dotime',new ext_goto('${VMX_TIMEDEST_PRI}', '${VMX_TIMEDEST_EXT}', '${VMX_TIMEDEST_CONTEXT}'));

	// We got an option, check if the option is defined, or one of the system defaults
	//
	$ext->add('macro-vm','vmx', 'checkopt', new ext_gotoif('$["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/${ACTION}/ext)}" = "1"]','doopt'));
	$ext->add('macro-vm','vmx', '', new ext_gotoif('$["${ACTION}" = "0"]','o,1'));
	$ext->add('macro-vm','vmx', '', new ext_gotoif('$["${ACTION}" = "*"]','adef,1'));

	// Got invalid option loop until the max
	//
	$ext->add('macro-vm','vmx', '', new ext_setvar("LOOPCOUNT",'$[${LOOPCOUNT} + 1]'));
	$ext->add('macro-vm','vmx', '', new ext_gotoif('$["${LOOPCOUNT}" > "${VMX_LOOPS}"]','toomany'));
	$ext->add('macro-vm','vmx','',new ext_playback('pm-invalid-option&please-try-again'));
	$ext->add('macro-vm','vmx','',new ext_goto('loopstart'));

	// tomany: to many invalid options, go to the specified destination
	//
	$ext->add('macro-vm','vmx', 'toomany', new ext_NoOp('Too Many invalid entries, got to invalid dest'));
	$ext->add('macro-vm','vmx', '', new ext_setvar("VMX_OPTS",'${VMX_OPTS_LOOP}'));
	/* Replaced
	$ext->add('macro-vm','vmx', '', new ext_gotoif('$["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/vmxopts/loops)}" = "0"]','chkloop'));
	$ext->add('macro-vm','vmx', '', new ext_setvar("VMX_OPTS",'${DB_RESULT}'));
	*/
	$ext->add('macro-vm','vmx', '', new ext_set('VMX_OPTS', '${IF($["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/vmxopts/loops)}" = "1"]?${DB_RESULT}:${VMX_OPTS})}'));

	// TODO: same as above, if we just set them then we don't depend on the globals at doloop
	$ext->add('macro-vm','vmx', 'chkloop', new ext_gotoif('$["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/loopdest/ext)}" = "0"]','doloop'));
	$ext->add('macro-vm','vmx', '', new ext_setvar("VMX_LOOPDEST_EXT",'${DB_RESULT}'));
	/* this would go with the above TODO
	$ext->add('macro-vm','vmx', 'chkloop', new ext_set('VMX_LOOPDEST_EXT', '${IF($["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/loopdest/ext)}" = "1"]?${DB_RESULT}:${VMX_LOOPDEST_EXT})}'));
	*/

	/* Replaced
	$ext->add('macro-vm','vmx', '', new ext_gotoif('$["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/loopdest/context)}" = "0"]','looppri'));
	$ext->add('macro-vm','vmx', '', new ext_setvar("VMX_LOOPDEST_CONTEXT",'${DB_RESULT}'));
	*/
	$ext->add('macro-vm','vmx', '', new ext_set('VMX_LOOPDEST_CONTEXT', '${IF($["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/loopdest/context)}" = "1"]?${DB_RESULT}:${VMX_LOOPDEST_CONTEXT})}'));

	/* Replaced
	$ext->add('macro-vm','vmx', 'looppri', new ext_gotoif('$["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/loopdest/pri)}" = "0"]','doloop'));
	$ext->add('macro-vm','vmx', '', new ext_setvar("VMX_LOOPDEST_PRI",'${DB_RESULT}'));
	*/
	$ext->add('macro-vm','vmx', 'looppri', new ext_set('VMX_LOOPDEST_PRI', '${IF($["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/loopdest/pri)}" = "1"]?${DB_RESULT}:${VMX_LOOPDEST_PRI})}'));

	$ext->add('macro-vm','vmx','doloop',new ext_goto('${VMX_LOOPDEST_PRI}','${VMX_LOOPDEST_EXT}','${VMX_LOOPDEST_CONTEXT}'));

	// doopt: execute the valid option that was chosen
	//
	$ext->add('macro-vm','vmx', 'doopt', new ext_NoOp('Got a valid option: ${DB_RESULT}'));
	$ext->add('macro-vm','vmx', '', new ext_setvar("VMX_EXT",'${DB_RESULT}'));

	// Special case, if this option was to go to voicemail, set options and go
	//
	$ext->add('macro-vm','vmx', '', new ext_gotoif('$["${VMX_EXT}" != "dovm"]','getdest'));
	/* Replaced
	$ext->add('macro-vm','vmx', 'vmxopts', new ext_setvar("VMX_OPTS",'${VMX_OPTS_DOVM}'));
	$ext->add('macro-vm','vmx', '', new ext_gotoif('$["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/vmxopts/dovm)}" = "0"]','vmxdovm'));
	$ext->add('macro-vm','vmx', '', new ext_setvar("VMX_OPTS",'${DB_RESULT}'));
	*/
	$ext->add('macro-vm','vmx', 'vmxopts', new ext_set('VMX_OPTS', '${IF($["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/vmxopts/dovm)}" = "1"]?${DB_RESULT}:${VMX_OPTS_DOVM})}'));

	$ext->add('macro-vm','vmx','vmxdovm',new ext_goto('1','dovm'));

	// General case, setup the goto destination and go there (no error checking, its up to the GUI's to assure
	// reasonable values
	//
	/* Replaced
	$ext->add('macro-vm','vmx', 'getdest', new ext_gotoif('$["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/${ACTION}/context)}" = "0"]','vmxpri'));
	$ext->add('macro-vm','vmx', '', new ext_setvar("VMX_CONTEXT",'${DB_RESULT}'));
	*/
	$ext->add('macro-vm','vmx', 'getdest', new ext_set('VMX_CONTEXT', '${IF($["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/${ACTION}/context)}" = "1"]?${DB_RESULT}:${VMX_CONTEXT})}'));

	/* Replaced
	$ext->add('macro-vm','vmx', 'vmxpri', new ext_gotoif('$["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/${ACTION}/pri)}" = "0"]','vmxgoto'));
	$ext->add('macro-vm','vmx', '', new ext_setvar("VMX_PRI",'${DB_RESULT}'));
	*/
	$ext->add('macro-vm','vmx', 'vmxpri', new ext_set('VMX_PRI', '${IF($["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/${ACTION}/pri)}" = "1"]?${DB_RESULT}:${VMX_PRI})}'));

	$ext->add('macro-vm','vmx','vmxgoto',new ext_goto('${VMX_PRI}','${VMX_EXT}','${VMX_CONTEXT}'));

	// If the required voicemail file is not present, then revert to normal voicemail
	// behavior treating as if it was not set
	//
	$ext->add('macro-vm','vmx', 'nofile', new ext_NoOp('File for mode: ${MODE} does not exist, SYSTEMSTATUS: ${SYSTEMSTATUS}, going to normal voicemail'));
	$ext->add('macro-vm','vmx','',new ext_goto('1','s-${MMODE}'));
	$ext->add('macro-vm','vmx', 'tmpgreet', new ext_NoOp('Temporary Greeting Detected, going to normal voicemail'));
	$ext->add('macro-vm','vmx','',new ext_goto('1','s-${MMODE}'));

	// Drop into voicemail either as a direct destination (in which case VMX_OPTS might be set to something) or
	// if the user timed out or broke out of the loop then VMX_OPTS is always cleared such that an Allison
	// message is played and the caller know's what is going on.
	//
	$ext->add('macro-vm','dovm', '', new ext_NoOp('VMX Timeout - go to voicemail'));
	$ext->add('macro-vm','dovm', '',new ext_vm('${MEXTEN}@${VMCONTEXT},${VMX_OPTS}${VMGAIN}'));
	$ext->add('macro-vm','dovm', '',new ext_goto('1','exit-${VMSTATUS}'));

	$ext->add('macro-vm','s-BUSY','',new ext_NoOp('BUSY voicemail'));
	$ext->add('macro-vm','s-BUSY','',new ext_macro('get-vmcontext','${MEXTEN}'));
	$ext->add('macro-vm','s-BUSY', '',new ext_vm('${MEXTEN}@${VMCONTEXT},${VM_OPTS}b${VMGAIN}'));
	$ext->add('macro-vm','s-BUSY', '',new ext_goto('1','exit-${VMSTATUS}'));

	$ext->add('macro-vm','s-NOMESSAGE','',new ext_NoOp('NOMESSAGE (beep only) voicemail'));
	$ext->add('macro-vm','s-NOMESSAGE','',new ext_macro('get-vmcontext','${MEXTEN}'));
	$ext->add('macro-vm','s-NOMESSAGE','',new ext_vm('${MEXTEN}@${VMCONTEXT},s${VM_OPTS}${VMGAIN}'));
	$ext->add('macro-vm','s-NOMESSAGE','',new ext_goto('1','exit-${VMSTATUS}'));

	$ext->add('macro-vm','s-INSTRUCT','',new ext_NoOp('NOMESSAGE (beeb only) voicemail'));
	$ext->add('macro-vm','s-INSTRUCT','',new ext_macro('get-vmcontext','${MEXTEN}'));
	$ext->add('macro-vm','s-INSTRUCT','',new ext_vm('${MEXTEN}@${VMCONTEXT},${VM_OPTS}${VMGAIN}'));
	$ext->add('macro-vm','s-INSTRUCT','',new ext_goto('1','exit-${VMSTATUS}'));

	$ext->add('macro-vm','s-DIRECTDIAL','',new ext_NoOp('DIRECTDIAL voicemail'));
	$ext->add('macro-vm','s-DIRECTDIAL','',new ext_macro('get-vmcontext','${MEXTEN}'));
	$ext->add('macro-vm','s-DIRECTDIAL','',new ext_vm('${MEXTEN}@${VMCONTEXT},${VM_OPTS}${VM_DDTYPE}${VMGAIN}'));
	$ext->add('macro-vm','s-DIRECTDIAL','',new ext_goto('1','exit-${VMSTATUS}'));

	$ext->add('macro-vm','_s-.','',new ext_macro('get-vmcontext','${MEXTEN}'));
	$ext->add('macro-vm','_s-.','',new ext_vm('${MEXTEN}@${VMCONTEXT},${VM_OPTS}u${VMGAIN}'));
	$ext->add('macro-vm','_s-.','',new ext_goto('1','exit-${VMSTATUS}'));

	// If the user has a 0 option defined, use that for operator zero-out from within voicemail
	// as well to keep it consistant with the menu structure
	//
	$ext->add('macro-vm','o','',new ext_playback('one-moment-please'));
	$ext->add('macro-vm','o','',new ext_gotoif('$["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/0/ext)}" = "0"]','doopdef'));
	$ext->add('macro-vm','o','',new ext_setvar("VMX_OPDEST_EXT",'${DB_RESULT}'));

	/* Replaced
	$ext->add('macro-vm','o','',new ext_gotoif('$["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/0/context)}" = "1"]','opcontext'));
	$ext->add('macro-vm','o','',new ext_setvar("DB_RESULT",'${VMX_CONTEXT}'));
	$ext->add('macro-vm','o','opcontext',new ext_setvar("VMX_OPDEST_CONTEXT",'${DB_RESULT}'));
	*/
	$ext->add('macro-vm','o', 'opcontext', new ext_set('VMX_OPDEST_CONTEXT', '${IF($["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/0/context)}" = "1"]?${DB_RESULT}:${VMX_CONTEXT})}'));

	/* Replaced
	$ext->add('macro-vm','o','',new ext_gotoif('$["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/0/pri)}" = "1"]','oppri'));
	$ext->add('macro-vm','o','',new ext_setvar("DB_RESULT",'${VMX_PRI}'));
	$ext->add('macro-vm','o','oppri',new ext_setvar("VMX_OPDEST_PRI",'${DB_RESULT}'));
	*/
	$ext->add('macro-vm','o', 'oppri', new ext_set('VMX_OPDEST_PRI', '${IF($["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/0/pri)}" = "1"]?${DB_RESULT}:${VMX_PRI})}'));

	$ext->add('macro-vm','o','',new ext_goto('${VMX_OPDEST_PRI}','${VMX_OPDEST_EXT}','${VMX_OPDEST_CONTEXT}'));
	$ext->add('macro-vm','o','doopdef',new ext_gotoif('$["x${OPERATOR_XTN}"="x"]','nooper','from-internal,${OPERATOR_XTN},1'));
	$ext->add('macro-vm','o','nooper',new ext_gotoif('$["x${FROM_DID}"="x"]','nodid'));
	$ext->add('macro-vm','o','',new ext_dial('Local/${FROM_DID}@from-pstn',''));
	$ext->add('macro-vm','o','',new ext_macro('hangup'));
	$ext->add('macro-vm','o','nodid',new ext_dial('Local/s@from-pstn',''));
	$ext->add('macro-vm','o','',new ext_macro('hangup'));

	// If the user has a * option defined, use that for the * out from within voicemail
	// as well to keep it consistant with the menu structure
	//
	$ext->add('macro-vm','a','',new ext_macro('get-vmcontext','${MEXTEN}'));
	//Dont allow (*) to be dialed to hack voicemail
	//http://issues.freepbx.org/browse/FREEPBX-7757
	$ext->add('macro-vm','a','',new ext_gotoif('$["${DB_EXISTS(AMPUSER/${MEXTEN}/novmstar)}" = "1"]','s,1'));

	$ext->add('macro-vm','a','',new ext_gotoif('$["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/*/ext)}" = "0"]','adef,1'));
	$ext->add('macro-vm','a','',new ext_setvar("VMX_ADEST_EXT",'${DB_RESULT}'));

	// Replaced
	//$ext->add('macro-vm','a','',new ext_gotoif('$["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/*/context)}" = "1"]','acontext'));
	//$ext->add('macro-vm','a','',new ext_setvar("DB_RESULT",'${VMX_CONTEXT}'));
	//$ext->add('macro-vm','a','acontext',new ext_setvar("VMX_ADEST_CONTEXT",'${DB_RESULT}'));
	$ext->add('macro-vm','a','acontext', new ext_set('VMX_ADEST_CONTEXT', '${IF($["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/*/context)}" = "1"]?${DB_RESULT}:${VMX_CONTEXT})}'));

	// Replaced
	//$ext->add('macro-vm','a','',new ext_gotoif('$["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/*/pri)}" = "1"]','apri'));
	//$ext->add('macro-vm','a','',new ext_setvar("DB_RESULT",'${VMX_PRI}'));
	//$ext->add('macro-vm','a','apri',new ext_setvar("VMX_ADEST_PRI",'${DB_RESULT}'));
	$ext->add('macro-vm','a','apri', new ext_set('VMX_ADEST_PRI', '${IF($["${DB_EXISTS(AMPUSER/${MEXTEN}/vmx/${MODE}/*/pri)}" = "1"]?${DB_RESULT}:${VMX_PRI})}'));
	$ext->add('macro-vm','a','',new ext_goto('${VMX_ADEST_PRI}','${VMX_ADEST_EXT}','${VMX_ADEST_CONTEXT}'));
	$ext->add('macro-vm','adef','',new ext_vmmain('${MEXTEN}@${VMCONTEXT}'));
	$ext->add('macro-vm','adef','',new ext_gotoif('$["${RETVM}" = "RETURN"]','exit-RETURN,1'));
	$ext->add('macro-vm','adef','',new ext_hangup(''));

	$ext->add('macro-vm','exit-FAILED','',new ext_playback('im-sorry&an-error-has-occurred'));
	$ext->add('macro-vm','exit-FAILED','',new ext_gotoif('$["${RETVM}" = "RETURN"]','exit-RETURN,1'));
	$ext->add('macro-vm','exit-FAILED','',new ext_hangup(''));

	$ext->add('macro-vm','exit-SUCCESS','',new ext_gotoif('$["${RETVM}" = "RETURN"]','exit-RETURN,1'));
	$ext->add('macro-vm','exit-SUCCESS','',new ext_playback('goodbye'));
	$ext->add('macro-vm','exit-SUCCESS','',new ext_hangup(''));

	$ext->add('macro-vm','exit-USEREXIT','',new ext_gotoif('$["${RETVM}" = "RETURN"]','exit-RETURN,1'));
	$ext->add('macro-vm','exit-USEREXIT','',new ext_playback('goodbye'));
	$ext->add('macro-vm','exit-USEREXIT','',new ext_hangup(''));

	$ext->add('macro-vm','exit-RETURN','',new ext_noop('Returning From Voicemail because macro'));
	$ext->add('macro-vm','t','',new ext_hangup(''));

	/* end macro-vm  */

	/*
	* ARG1: VMBOX
	* ARG2: EXTTOCALL
	* ARG3: If NOANSWER dest exists 1, otherwise 0
	* ARG4: If BUSY dest exists 1, otherwise 0
	* ARG5: If CHANUNAVAIL dest exists 1, otherwise 0
	*/
	$mcontext = 'macro-exten-vm';
	$exten = 's';

	$ext->add($mcontext,$exten,'', new ext_macro('user-callerid'));
	$ext->add($mcontext,$exten,'', new ext_set("RingGroupMethod", 'none'));
	$ext->add($mcontext,$exten,'', new ext_set("__EXTTOCALL", '${ARG2}'));
	$ext->add($mcontext,$exten,'', new ext_set("__PICKUPMARK", '${ARG2}'));
	$ext->add($mcontext,$exten,'', new ext_set("RT", '${IF($["${ARG1}"!="novm" | "${DB(CFU/${EXTTOCALL})}"!="" | "${DB(CFB/${EXTTOCALL})}"!="" | "${ARG3}"="1" | "${ARG4}"="1" | "${ARG5}"="1"]?${RINGTIMER}:)}'));
	$ext->add($mcontext,$exten,'checkrecord', new ext_gosub('1','s','sub-record-check','exten,${EXTTOCALL},dontcare'));

	// If paging module is not present, then what happens?
	// TODO: test with no paging module
	$fcc = new featurecode('paging', 'intercom-prefix');
	$intercom_code = $fcc->getCodeActive();
	unset($fcc);

	// I think it is adequate that if AMPUSER is blank, it's not internal (don't think FROM_DID has to be checked though I don't think it hurts)
	$macrodial = 'macrodial';
	if ($intercom_code != '') {
		if ($amp_conf['AST_FUNC_EXTENSION_STATE']) {
			$ext->add($mcontext,$exten,'', new ext_noop_trace('AMPUSER: ${AMPUSER}, FROM_DID: ${FROM_DID}, FROM_QUEUE: $["${CUT(CHANNEL,@,2):5:5}"="queue"], answermode: ${DB(AMPUSER/${EXTTOCALL}/answermode)}, BLINDTXF: ${BLINDTRANSFER}, ATTTXF: ${ATTENDEDTRANSFER}, EXT_STATE: ${EXTENSION_STATE(${EXTTOCALL})}, CC_RECALL: ${CC_RECALL}'));
			if ($amp_conf['FORCE_INTERNAL_AUTO_ANSWER_ALL']) {
				$ext->add($mcontext,$exten,'',new ext_gotoif('$["${CUT(CHANNEL,@,2):5:5}"="queue"|"${AMPUSER}"=""|${LEN(${FROM_DID})}|${LEN(${BLINDTRANSFER})}|"${EXTENSION_STATE(${EXTTOCALL})}"!="NOT_INUSE"|"${CC_RECALL}"!=""]','macrodial'));
			} else {
				$ext->add($mcontext,$exten,'',new ext_gotoif('$["${CUT(CHANNEL,@,2):5:5}"="queue"|"${AMPUSER}"=""|${LEN(${FROM_DID})}|"${DB(AMPUSER/${EXTTOCALL}/answermode)}"!="intercom"|${LEN(${BLINDTRANSFER})}|"${EXTENSION_STATE(${EXTTOCALL})}"!="NOT_INUSE"|"${CC_RECALL}"!=""]','macrodial'));
			}
		} else {
			$ext->add($mcontext,$exten,'', new ext_noop_trace('AMPUSER: ${AMPUSER}, FROM_DID: ${FROM_DID}, FROM_QUEUE: $["${CUT(CHANNEL,@,2):5:5}"="queue"], answermode: ${DB(AMPUSER/${EXTTOCALL}/answermode)}, BLINDTXF: ${BLINDTRANSFER}, , ATTTXF: ${ATTENDEDTRANSFER}, CC_RECALL: ${CC_RECALL}'));
			if ($amp_conf['FORCE_INTERNAL_AUTO_ANSWER_ALL']) {
				$ext->add($mcontext,$exten,'',new ext_gotoif('$["${CUT(CHANNEL,@,2):5:5}"="queue"|"${AMPUSER}"=""|${LEN(${FROM_DID})}|${LEN(${BLINDTRANSFER})}]','macrodial'));
			} else {
				$ext->add($mcontext,$exten,'',new ext_gotoif('$["${CUT(CHANNEL,@,2):5:5}"="queue"|"${AMPUSER}"=""|${LEN(${FROM_DID})}|"${DB(AMPUSER/${EXTTOCALL}/answermode)}"!="intercom"|${LEN(${BLINDTRANSFER})}]','macrodial'));
			}
		}
		$ext->add($mcontext,$exten,'', new ext_set("INTERCOM_EXT_DOPTIONS", '${DIAL_OPTIONS}'));
		$ext->add($mcontext,$exten,'', new ext_set("INTERCOM_RETURN", 'TRUE'));
		$ext->add($mcontext,$exten,'', new ext_gosub('1',$intercom_code.'${EXTTOCALL}','ext-intercom'));
		$ext->add($mcontext,$exten,'', new ext_set("INTERCOM_RETURN", ''));
		$ext->add($mcontext,$exten,'', new ext_set("INTERCOM_EXT_DOPTIONS", ''));

		// If it was a blind transfer and there was a previous auto-answer, then we cleanup all the auto-answer headers left in the channel
		// It won't be from this call because we don't ever intercom in a blind transfer scenario (hmm unless it was blind transfered to a
		// specific intercom code but in that case, they won't have been able to subsequently transfered the call
		//
		$ext->add($mcontext,$exten,$macrodial, new ext_gosubif('$["${INTERCOM_CALL}"="TRUE" & ${LEN(${BLINDTRANSFER})}]','clrheader,1'));
		$macrodial = '';
	}
	if ($amp_conf['AST_FUNC_EXTENSION_STATE']) {
		$ext->add($mcontext,$exten,$macrodial, new ext_macro('dial-one','${RT},${DIAL_OPTIONS},${EXTTOCALL}'));
	} else {
		$ext->add($mcontext,$exten,$macrodial, new ext_macro('dial','${RT},${DIAL_OPTIONS},${EXTTOCALL}'));
	}
	$ext->add($mcontext,$exten,'', new ext_set("SV_DIALSTATUS", '${DIALSTATUS}'));

	$ext->add($mcontext,$exten,'calldocfu', new ext_gosubif('$[("${SV_DIALSTATUS}"="NOANSWER"|"${SV_DIALSTATUS}"="CHANUNAVAIL") & "${DB(CFU/${EXTTOCALL})}"!="" & "${SCREEN}"=""]','docfu,1'));
	$ext->add($mcontext,$exten,'calldocfb', new ext_gosubif('$["${SV_DIALSTATUS}"="BUSY" & "${DB(CFB/${EXTTOCALL})}"!="" & "${SCREEN}"=""]','docfb,1'));
	$ext->add($mcontext,$exten,'', new ext_set("DIALSTATUS", '${SV_DIALSTATUS}'));

	$ext->add($mcontext,$exten,'', new ext_execif('$[("${DIALSTATUS}"="NOANSWER"&"${ARG3}"="1")|("${DIALSTATUS}"="BUSY"&"${ARG4}"="1")|("${DIALSTATUS}"="CHANUNAVAIL"&"${ARG5}"="1")]','MacroExit'));

	$ext->add($mcontext,$exten,'', new ext_noop_trace('Voicemail is \'${ARG1}\'',1));
	$ext->add($mcontext,$exten,'',new ext_gotoif('$["${ARG1}"="novm"]','s-${DIALSTATUS},1'));
	$ext->add($mcontext,$exten,'', new ext_noop_trace('Sending to Voicemail box ${EXTTOCALL}',1));
	$ext->add($mcontext,$exten,'', new ext_macro('vm','${ARG1},${DIALSTATUS},${IVR_RETVM}'));

	$exten = 'docfu';
	if ($amp_conf['DIVERSIONHEADER']) $ext->add($mcontext,$exten,'', new ext_set('__DIVERSION_REASON', 'unavailable'));
	$ext->add($mcontext,$exten,'docfu', new ext_execif('$["${DB(AMPUSER/${EXTTOCALL}/cfringtimer)}"="-1"|("${ARG1}"="novm"&"${ARG3}"="1")]', 'StackPop'));
	$ext->add($mcontext,$exten,'', new ext_gotoif('$["${DB(AMPUSER/${EXTTOCALL}/cfringtimer)}"="-1"|("${ARG1}"="novm"&"${ARG3}"="1")]', 'from-internal,${DB(CFU/${EXTTOCALL})},1'));
	$ext->add($mcontext,$exten,'', new ext_set("RTCF", '${IF($["${DB(AMPUSER/${EXTTOCALL}/cfringtimer)}"="0"]?${RT}:${DB(AMPUSER/${EXTTOCALL}/cfringtimer)})}'));
	$ext->add($mcontext,$exten,'', new ext_execif('$["${DIRECTION}" = "INBOUND"]', 'Set', 'DIAL_OPTIONS=${STRREPLACE(DIAL_OPTIONS,T)}I'));
	$ext->add($mcontext,$exten,'', new ext_dial('Local/${DB(CFU/${EXTTOCALL})}@from-internal/n', '${RTCF},${DIAL_OPTIONS}'));
	if ($amp_conf['DIVERSIONHEADER']) $ext->add($mcontext,$exten,'', new ext_set('__DIVERSION_REASON', ''));
	$ext->add($mcontext,$exten,'', new ext_return(''));

	$exten = 'docfb';
	if ($amp_conf['DIVERSIONHEADER']) $ext->add($mcontext,$exten,'', new ext_set('__DIVERSION_REASON', 'user-busy'));
	$ext->add($mcontext,$exten,'docfu', new ext_execif('$["${DB(AMPUSER/${EXTTOCALL}/cfringtimer)}"="-1"|("${ARG1}"="novm"&"${ARG4}"="1")]', 'StackPop'));
	$ext->add($mcontext,$exten,'', new ext_gotoif('$["${DB(AMPUSER/${EXTTOCALL}/cfringtimer)}"="-1"|("${ARG1}"="novm"&"${ARG4}"="1")]', 'from-internal,${DB(CFB/${EXTTOCALL})},1'));
	$ext->add($mcontext,$exten,'', new ext_set("RTCF", '${IF($["${DB(AMPUSER/${EXTTOCALL}/cfringtimer)}"="0"]?${RT}:${DB(AMPUSER/${EXTTOCALL}/cfringtimer)})}'));
	$ext->add($mcontext,$exten,'', new ext_execif('$["${DIRECTION}" = "INBOUND"]', 'Set', 'DIAL_OPTIONS=${STRREPLACE(DIAL_OPTIONS,T)}I'));
	$ext->add($mcontext,$exten,'', new ext_dial('Local/${DB(CFB/${EXTTOCALL})}@from-internal/n', '${RTCF},${DIAL_OPTIONS}'));
	if ($amp_conf['DIVERSIONHEADER']) $ext->add($mcontext,$exten,'', new ext_set('__DIVERSION_REASON', ''));
	$ext->add($mcontext,$exten,'', new ext_return(''));

	// If we are here it was determined that there had been intercom sip headers left over in the channel. If 1.6.2+ then we can use
	// the SIPRemoveHeader() option to remove the specific headers. We are trying to be careful not to remove similar headers that
	// may be used for 'distinctive ring' type reasons from elsewhere in the dialplan. Thus only if we detected the intercom situation
	// do we do it here.
	//
	// If we are pre 1.6.2 then some experimentation on 1.4.X has shown that we are able to clear the SIPADDHEADERnn channel variables
	// that result from setting the headers so we start from 1 (the first) and iterate up until we find one. In some weird situations
	// if a header had been removed, we could miss out since it is not possible to detect the existence of a blank channel variable
	//
	if ($intercom_code != '') {
		$exten = 'clrheader';
		$ext->add($mcontext, $exten, '', new ext_execif('$[${LEN(${SIPURI})}&"${SIPURI}"="${SIP_URI_OPTIONS}"]', 'Set','SIP_URI_OPTIONS='));
		if ($ast_ge_162) {
			$ext->add($mcontext, $exten, '', new ext_execif('$[${LEN(${ALERTINFO})}]', 'SIPRemoveHeader','${ALERTINFO}'));
			$ext->add($mcontext, $exten, '', new ext_execif('$[${LEN(${CALLINFO})}]', 'SIPRemoveHeader','${CALLINFO}'));
		} else {
			$ext->add($mcontext, $exten, '', new ext_set('SP', '0'));
			$ext->add($mcontext, $exten, '', new ext_set('ITER', '1'));
			$ext->add($mcontext, $exten, 'begin', new ext_execif('$[${ITER} > 9]', 'Set','SP=' ));
			$ext->add($mcontext, $exten, '', new ext_execif('$[${LEN(${SIPADDHEADER${SP}${ITER}})}=0]', 'Return'));
			$ext->add($mcontext, $exten, '', new ext_execif('$["${SIPADDHEADER${SP}${ITER}}"="${ALERTINFO}"|"${SIPADDHEADER${SP}${ITER}}"="${CALLINFO}"]', 'Set','SIPADDHEADER${SP}${ITER}='));
			$ext->add($mcontext, $exten, '', new ext_setvar('ITER', '$[${ITER} + 1]'));
			$ext->add($mcontext, $exten, '', new ext_gotoif('$[${ITER} < 100]', 'begin'));
		}
		$ext->add($mcontext,$exten,'', new ext_return(''));
	}

	$exten = 's-BUSY';
	$ext->add($mcontext,$exten,'', new ext_noop_trace('Extension is reporting BUSY and not passing to Voicemail',1));
	$ext->add($mcontext,$exten,'', new ext_gotoif('$["${IVR_RETVM}"="RETURN" & "${IVR_CONTEXT}"!=""]','exit,1'));
	$ext->add($mcontext,$exten, '', new ext_playtones('busy'));
	$ext->add($mcontext,$exten, '', new ext_busy(20));

	$exten = '_s-!';
	$ext->add($mcontext,$exten,'', new ext_noop_trace('IVR_RETVM: ${IVR_RETVM} IVR_CONTEXT: ${IVR_CONTEXT}',1));
	$ext->add($mcontext,$exten,'', new ext_gotoif('$["${IVR_RETVM}"="RETURN" & "${IVR_CONTEXT}"!=""]','exit,1'));
	$ext->add($mcontext,$exten,'', new ext_playtones('congestion'));
	$ext->add($mcontext,$exten,'', new ext_congestion('10'));

	$exten = 'exit';
	$ext->add($mcontext,$exten,'', new ext_playback('beep&line-busy-transfer-menu&silence/1'));
	$ext->add($mcontext,$exten,'', new ext_macroexit());

	/* macro-exten-vm  */


	/*
	;------------------------------------------------------------------------
	; [macro-simple-dial]
	;------------------------------------------------------------------------
	; This macro was derived from macro-exten-vm, which is what is normally used to
	; ring an extension. It has been simplified and designed to never go to voicemail
	; and always return regardless of the DIALSTATUS for any incomplete call.
	;
	; It's current primary purpose is to allow findmefollow ring an extension prior
	; to trying the follow-me ringgroup that is provided.
	;
	; Ring an extension, if the extension is busy or there is no answer, return
	; ARGS: $EXTENSION, $RINGTIME
	;------------------------------------------------------------------------
	*/
	$mcontext = 'macro-simple-dial';
	$exten = 's';
	$ext->add($mcontext,$exten,'', new ext_set("__EXTTOCALL", '${ARG1}'));
	$ext->add($mcontext,$exten,'', new ext_set("RT", '${ARG2}'));
	$ext->add($mcontext,$exten,'', new ext_set("CFUEXT", '${DB(CFU/${EXTTOCALL})}'));
	$ext->add($mcontext,$exten,'', new ext_set("CFBEXT", '${DB(CFB/${EXTTOCALL})}'));
	$ext->add($mcontext,$exten,'', new ext_set("CWI_TMP", '${CWIGNORE}'));
	if ($amp_conf['AST_FUNC_EXTENSION_STATE']) {
		$ext->add($mcontext,$exten,'macrodial', new ext_macro('dial-one','${RT},${DIAL_OPTIONS},${EXTTOCALL}'));
	} else {
		$ext->add($mcontext,$exten,'macrodial', new ext_macro('dial','${RT},${DIAL_OPTIONS},${EXTTOCALL}'));
	}
	$ext->add($mcontext,$exten,'', new ext_set("__CWIGNORE", '${CWI_TMP}'));
	$ext->add($mcontext,$exten,'', new ext_set("PR_DIALSTATUS", '${DIALSTATUS}'));
	$ext->add($mcontext,$exten,'calldocfu', new ext_gosubif('$["${PR_DIALSTATUS}"="NOANSWER" & "${CFUEXT}"!=""]','docfu,1'));
	$ext->add($mcontext,$exten,'calldocfb', new ext_gosubif('$["${PR_DIALSTATUS}"="BUSY" & "${CFBEXT}"!=""]','docfb,1'));
	$ext->add($mcontext,$exten,'', new ext_set("DIALSTATUS", '${PR_DIALSTATUS}'));
	$ext->add($mcontext,$exten,'',new ext_goto('1','s-${DIALSTATUS}'));

	/*
	; Try the Call Forward on No Answer / Unavailable number.
	; We want to try CFU if set, but we want the same ring timer as was set to our call (or do we want the
	; system ringtimer? - probably not). Then if no answer there (assuming it doesn't drop into their vm or
	; something we return, which will have the net effect of returning to the followme setup.)
	;
	; want to avoid going to other follow-me settings here. So check if the CFUEXT is a user and if it is
	; then direct it straight to ext-local (to avoid getting intercepted by findmefollow) otherwise send it
	; to from-internal since it may be an outside line.
	;
	*/
	$exten = 'docfu';
	$ext->add($mcontext,$exten,'', new ext_gotoif('$["${DB(AMPUSER/${CFUEXT}/device)}" = "" ]','chlocal'));
	$ext->add($mcontext,$exten,'', new ext_dial('Local/${CFUEXT}@ext-local', '${RT},${DIAL_OPTIONS}'));
	$ext->add($mcontext,$exten,'', new ext_return(''));
	$ext->add($mcontext,$exten,'chlocal', new ext_execif('$["${DIRECTION}" = "INBOUND"]', 'Set', 'DIAL_OPTIONS=${STRREPLACE(DIAL_OPTIONS,T)}I'));
	$ext->add($mcontext,$exten,'', new ext_dial('Local/${CFUEXT}@from-internal/n', '${RT},${DIAL_OPTIONS}'));
	$ext->add($mcontext,$exten,'', new ext_return(''));

	$exten = 'docfb';
	$ext->add($mcontext,$exten,'', new ext_gotoif('$["${DB(AMPUSER/${CFBEXT}/device)}" = "" ]','chlocal'));
	$ext->add($mcontext,$exten,'', new ext_dial('Local/${CFBEXT}@ext-local', '${RT},${DIAL_OPTIONS}'));
	$ext->add($mcontext,$exten,'', new ext_return(''));
	$ext->add($mcontext,$exten,'chlocal', new ext_execif('$["${DIRECTION}" = "INBOUND"]', 'Set', 'DIAL_OPTIONS=${STRREPLACE(DIAL_OPTIONS,T)}I'));
	$ext->add($mcontext,$exten,'', new ext_dial('Local/${CFBEXT}@from-internal/n', '${RT},${DIAL_OPTIONS}'));
	$ext->add($mcontext,$exten,'', new ext_return(''));

	/*
	; In all cases of no connection, come here and simply return, since the calling dialplan will
	; decide what to do next
	*/
	$exten = '_s-.';
	$ext->add($mcontext,$exten,'', new ext_noop('Extension is reporting ${EXTEN}'));

	/*
		Originate a call but skip voicemail if the device does not answer
	 */
	$mcontext = 'originate-skipvm';
	$ext->add($mcontext,'_.X','', new ext_macro('blkvm-set'));
	$ext->add($mcontext,'_.X','', new ext_goto('1', '${EXTEN}', 'from-internal'));

	/* macro-simple-dial */


	/* macro-blkvm-setifempty
	* macro-blkvm-set
	* macro-blkvm-clr
	* macro-blkvm-check
	*
	* These macros are used to tell the voicemail system if it should answer a call or kill the call.
	* They are also used by modules like findmefollow and ringgroups to determine if a destination
	* if noanswer should be pursued, or if they should just end because they were called by a higher
	* level module who's destination should be honored. (Thus if vm should be blocked, so should
	* such destinations.
	*
	* In the past, it was necessary to create and track unique AstDB variables to track this since
	* it is necessary for a call that is answered, for example a queue memeber who answers a queue
	* call, to clr the block so that subsequent transfers to voicemail or user extensions which might
	* hit voicemail could succeed and the nature of Asterisk inheritable variable did not allow
	* this. This also meant that these needed to be cleaned up when the master channel who 'started
	* it all' ended, which is attempted in macro-hangupcall. There are still cases where cleanup
	* does not happen which can result in an accumulation of these.
	*
	* With the advent of the SHARED() channel variable starting in 1.6, we can achieve the same
	* thing with such a SHARED() channel variable which should be more efficient since it does not
	* hit the DB, but more importantly, there is no cleanup because the variable will die with the
	* owner channel.
	*
	* We check if the SHARED function is available and if so, we use that in our macro. If not, we
	* fall back to the shared DB variable and keep our cleanup code in hangupcall.
	*
	* Note that we have chosen to use a Macro() in place of a GoSub() because in the legacy DB
	* mode we must have the owning ${EXTEN} to create our unique key. Since GoSub() does not support
	* passing arguments until 1.6 this would not be possible in 1.4 which is still mainstream.
	* We have chosen to use the GOSUB_RETVAL in anticipation of a future point where we move to
	* a GoSub() call which would be slightly more efficient.
	*/

	$exten = 's';
	if ($amp_conf['AST_FUNC_SHARED']) {

		// If it BLKVM_CHANNEL exists, return it's value. If not, then set it and return TRUE
		//
		$mcontext = 'macro-blkvm-setifempty';
		$ext->add($mcontext,$exten,'', new ext_gotoif('$[!${EXISTS(${BLKVM_CHANNEL})}]', 'init'));
		$ext->add($mcontext,$exten,'', new ext_set('GOSUB_RETVAL','${SHARED(BLKVM,${BLKVM_CHANNEL})}'));
		$ext->add($mcontext,$exten,'', new ext_macroexit(''));
		$ext->add($mcontext,$exten,'init', new ext_set('__BLKVM_CHANNEL','${CHANNEL}'));
		$ext->add($mcontext,$exten,'', new ext_set('SHARED(BLKVM,${BLKVM_CHANNEL})','TRUE'));
		$ext->add($mcontext,$exten,'', new ext_set('GOSUB_RETVAL','TRUE'));
		$ext->add($mcontext,$exten,'', new ext_macroexit(''));

		// If BLKVM_CHANNEL not set or 'reset' is passed, then initialize it to this channel then set and retrun TRUE
		//
		$mcontext = 'macro-blkvm-set';
		$ext->add($mcontext,$exten,'', new ext_execif('$[!${EXISTS(${BLKVM_CHANNEL})} | "{ARG1}" = "reset"]', 'Set','__BLKVM_CHANNEL=${CHANNEL}'));
		$ext->add($mcontext,$exten,'', new ext_set('SHARED(BLKVM,${BLKVM_CHANNEL})','TRUE'));
		$ext->add($mcontext,$exten,'', new ext_set('GOSUB_RETVAL','TRUE'));
		$ext->add($mcontext,$exten,'', new ext_macroexit(''));

		// if clearing, BLKVM_CHANNEL should already exist (if not, we clear our channel's copy)
		//
		$mcontext = 'macro-blkvm-clr';
		$ext->add($mcontext,$exten,'', new ext_set('SHARED(BLKVM,${BLKVM_CHANNEL})',''));
		$ext->add($mcontext,$exten,'', new ext_set('GOSUB_RETVAL',''));
		$ext->add($mcontext,$exten,'', new ext_macroexit(''));

		// if checking, BLKVM_CHANNEL should already exist (if not, we check our channel's copy)
		// CC_RECALL was originally used for CallCompletion but is used elsewhere as well for recall automated
		// calls that should therefore not go to voicemail, for example a wakeup call
		//
		$mcontext = 'macro-blkvm-check';
		$ext->add($mcontext,$exten,'', new ext_set('GOSUB_RETVAL','${SHARED(BLKVM,${BLKVM_CHANNEL})}'));
		$ext->add($mcontext,$exten,'', new ext_execif('$["${GOSUB_RETVAL}"="" & "${CC_RECALL}"="1"]', 'Set','GOSUB_RETVAL=TRUE'));
		$ext->add($mcontext,$exten,'', new ext_macroexit(''));

	} else { // NO SHARED()

		// If it BLKVM_OVERRIDE exists, return it's value. If not, then set it and return TRUE
		//
		$mcontext = 'macro-blkvm-setifempty';
		$ext->add($mcontext,$exten,'', new ext_gotoif('$[!${EXISTS(${BLKVM_OVERRIDE})}]', 'init'));
		$ext->add($mcontext,$exten,'', new ext_set('GOSUB_RETVAL','${DB(${BLKVM_OVERRIDE})}'));
		$ext->add($mcontext,$exten,'', new ext_macroexit(''));
		$ext->add($mcontext,$exten,'init', new ext_set('__BLKVM_OVERRIDE','BLKVM/${MACRO_EXTEN}/${CHANNEL}'));
		$ext->add($mcontext,$exten,'', new ext_set('__BLKVM_BASE','${MACRO_EXTEN}'));
		$ext->add($mcontext,$exten,'', new ext_set('DB(${BLKVM_OVERRIDE})','TRUE'));

		$ext->add($mcontext,$exten,'', new ext_set('GOSUB_RETVAL','TRUE'));
		$ext->add($mcontext,$exten,'', new ext_macroexit(''));

		// If BLKVM_OVERRIDE not set or 'reset' is passed, then initialize it to this channel then set and retrun TRUE
		//
		$mcontext = 'macro-blkvm-set';
		$ext->add($mcontext,$exten,'', new ext_execif('$[!${EXISTS(${BLKVM_OVERRIDE})} | "{ARG1}" = "reset"]', 'Set','__BLKVM_BASE=${MACRO_EXTEN}'));
		$ext->add($mcontext,$exten,'', new ext_execif('$[!${EXISTS(${BLKVM_OVERRIDE})} | "{ARG1}" = "reset"]', 'Set','__BLKVM_OVERRIDE=BLKVM/${MACRO_EXTEN}/${CHANNEL}'));
		$ext->add($mcontext,$exten,'', new ext_set('DB(${BLKVM_OVERRIDE})','TRUE'));
		$ext->add($mcontext,$exten,'', new ext_set('GOSUB_RETVAL','TRUE'));
		$ext->add($mcontext,$exten,'', new ext_macroexit(''));

		// if clearing, BLKVM_OVERRIDE should already exist (if not, it's already cleared anyhow)
		//
		$mcontext = 'macro-blkvm-clr';
		$ext->add($mcontext,$exten,'', new ext_gotoif('$[!${EXISTS(${BLKVM_OVERRIDE})}]', 'ret'));
		$ext->add($mcontext,$exten,'', new ext_dbdel('${BLKVM_OVERRIDE}'));
		$ext->add($mcontext,$exten,'ret', new ext_set('GOSUB_RETVAL',''));
		$ext->add($mcontext,$exten,'', new ext_macroexit(''));

		// if checking, BLKVM_OVERRIDE should already exist (if not, '' will be returned)
		//
		$mcontext = 'macro-blkvm-check';
		$ext->add($mcontext,$exten,'', new ext_set('GOSUB_RETVAL','${DB(${BLKVM_OVERRIDE})}'));
		$ext->add($mcontext,$exten,'', new ext_macroexit(''));
	}

	$mcontext = 'macro-hangupcall';
	$exten = 's';
	/*
	; Cleanup any remaining RG flag
	*/
	$skip_label = $amp_conf['AST_FUNC_SHARED'] ? 'theend' : 'skiprg';
	$ext->add($mcontext,$exten,'start', new ext_gotoif('$["${USE_CONFIRMATION}"="" | "${RINGGROUP_INDEX}"="" | "${CHANNEL}"!="${UNIQCHAN}"]',$skip_label));
	$ext->add($mcontext,$exten,'', new ext_noop_trace('Cleaning Up Confirmation Flag: RG/${RINGGROUP_INDEX}/${CHANNEL}'));
	$ext->add($mcontext,$exten,'delrgi', new ext_dbdel('RG/${RINGGROUP_INDEX}/${CHANNEL}'));

	if (!$amp_conf['AST_FUNC_SHARED']) {
		// only clr it if we were the originating channel
		//
		$ext->add($mcontext,$exten,'skiprg', new ext_gotoif('$["${BLKVM_BASE}"="" | "BLKVM/${BLKVM_BASE}/${CHANNEL}"!="${BLKVM_OVERRIDE}"]', 'skipblkvm'));
		$ext->add($mcontext,$exten,'', new ext_noop_trace('Cleaning Up Block VM Flag: ${BLKVM_OVERRIDE}'));
		$ext->add($mcontext,$exten,'', new ext_macro('blkvm-clr'));
		/*
		; Cleanup any remaining FollowMe DND flags
		*/
		$ext->add($mcontext,$exten,'skipblkvm', new ext_gotoif('$["${FMGRP}"="" | "${FMUNIQUE}"="" | "${CHANNEL}"!="${FMUNIQUE}"]','theend'));
		$ext->add($mcontext,$exten,'delfmrgp', new ext_dbdel('FM/DND/${FMGRP}/${CHANNEL}'));

		$skip_label = $next_label;
	}

	// Work around Asterisk issue: https://issues.asterisk.org/jira/browse/ASTERISK-19853
	$ext->add($mcontext, $exten,'theend', new ext_execif('$["${ONETOUCH_RECFILE}"!="" & "${CDR(recordingfile)}"=""]','Set','CDR(recordingfile)=${ONETOUCH_RECFILE}'));

	$ext->add($mcontext, $exten,'', new ext_hangup()); // TODO: once Asterisk issue fixed label as theend
	$ext->add($mcontext, $exten,'', new ext_macroexit(''));
	/*
	$ext->add($mcontext, $exten, 'theend', new ext_gosubif('$["${ONETOUCH_REC}"="RECORDING"]', 'macro-one-touch-record,s,sstate', false, '${FROMEXTEN},NOT_INUSE'));
	$ext->add($mcontext, $exten, '', new ext_gosubif('$["${ONETOUCH_REC}"="RECORDING"&"${MASTER_CHANNEL(CLEAN_DIALEDPEERNUMBER)}"="${CUT(CALLFILENAME,-,2)}"]', 'macro-one-touch-record,s,sstate', false, '${IF($["${EXTTOCALL}"!=""]?${EXTTOCALL}:${CUT(CALLFILENAME,-,2)})},NOT_INUSE'));
	$ext->add($mcontext, $exten, '', new ext_gosubif('$["${ONETOUCH_REC}"="RECORDING"&"${MASTER_CHANNEL(CLEAN_DIALEDPEERNUMBER)}"!="${CUT(CALLFILENAME,-,2)}"]','macro-one-touch-record,s,sstate',false,'${MASTER_CHANNEL(CLEAN_DIALEDPEERNUMBER)},NOT_INUSE'));
	$ext->add($mcontext,$exten,'', new ext_noop_trace('ONETOUCH_REC: ${ONETOUCH_REC}',5));
	*/

	/* Now generate a clean DIALEDPEERNUMBER if ugly followme/ringgroup extensions dialplans were engaged
	* doesn't seem like this is need with some of the NoCDRs() but leave for now and keep an eye on it
	*
	$ext->add($mcontext, $exten, '', new ext_execif('$["${CLEAN_DIALEDPEERNUMBER}"=""]','Set','CLEAN_DIALEDPEERNUMBER=${IF($[${FIELDQTY(DIALEDPEERNUMBER,-)}=1]?${DIALEDPEERNUMBER}:${CUT(CUT(DIALEDPEERNUMBER,-,2),@,1)})}'));
	$ext->add($mcontext, $exten, '', new ext_set('CDR(clean_dst)','${CLEAN_DIALEDPEERNUMBER}'));
	*/


	/* macro-hangupcall */


	include 'functions.inc/macro-dial-one.php';
	include 'functions.inc/func-sipheaders.php';
	break;
	}
}

/* begin page.ampusers.php functions */

function core_ampusers_add($username, $password, $extension_low, $extension_high, $deptname, $sections) {
	_core_backtrace();
	return \FreePBX::Core()->addAMPUser($username, $password, $extension_low, $extension_high, $deptname, $sections);
}

function core_ampusers_del($username) {
	global $db;
	$username = $db->escapeSimple($username);
	$sql = "DELETE FROM ampusers WHERE username = '".$username."'";
	sql($sql,"query");
}

function core_ampusers_list() {
	$sql = "SELECT username FROM ampusers ORDER BY username";
	return sql($sql,"getAll");
}

/* end page.ampusers.php functions */









/* begin page.did.php functions */

function core_did_list($order='extension'){
	_core_backtrace();
	return FreePBX::Core()->getAllDIDs($order);
}

function core_did_get($extension="",$cidnum=""){
	_core_backtrace();
	return FreePBX::Core()->getDID($extension,$cidnum);
}

function core_did_del($extension,$cidnum){
	_core_backtrace();
	return FreePBX::Core()->delDID($extension,$cidnum);
}

function core_did_edit($oldExtension,$oldCidnum, $incoming){
	$incoming['destination'] = isset($incoming[$incoming['goto0'].'0']) ? $incoming[$incoming['goto0'].'0'] : "";
	$res = FreePBX::Core()->editDID($oldExtension,$oldCidnum, $incoming);
	if ($res) {
		return true;
	} else {
		$extension = $incoming['extension'];
		$cidnum = $incoming['cidnum'];
		$existing = FreePBX::Core()->getDID($extension,$cidnum);
		echo "<script>javascript:alert('"._("A route for this DID/CID already exists!")." => ".$existing['extension']."/".$existing['cidnum']."')</script>";
	}
	return false;
}

/* Create a new did with values passed into $did_vars and defaults used otherwise
*/
function core_did_create_update($did_vars) {
	return FreePBX::Core()->createUpdateDID($did_vars);
}


/* Edits the poperties of a did, but not the did or cid nums since those could of course be in conflict
*/
function core_did_edit_properties($did_vars) {
	return FreePBX::Core()->editDIDProperties($did_vars);
}

function core_did_add($incoming,$target=false){

	$incoming['destination'] = ($target) ? $target : (isset($incoming[$incoming['goto0'].'0']) ? $incoming[$incoming['goto0'].'0'] : "");

	// Check to make sure the did is not being used elsewhere
	//
	$res = \FreePBX::Core()->addDID($incoming);
	if ($res) {
		return true;
	} else {
		$existing = \FreePBX::Core()->getDID($incoming['extension'],$incoming['cidnum']);
		echo "<script>javascript:alert('"._("A route for this DID/CID already exists!")." => ".$existing['extension']."/".$existing['cidnum']."')</script>";
		return false;
	}
}

/* end page.did.php functions */


/* begin page.devices.php functions */

//get the existing devices
function core_devices_list($tech="all",$detail=false,$get_all=false) {
	static $extens;
	static $last_tech, $last_detail, $last_get_all;

	if (isset($extens) && $tech == $last_tech && $detail === $last_detail && $get_all === $last_get_all) {
		return $extens;
	}

	if (strtolower($detail) == 'full') {
		$sql = "SELECT * FROM devices";
	} else {
		$sql = "SELECT id,description FROM devices";
	}
	switch (strtoupper($tech)) {
		case "IAX":
			$sql .= " WHERE tech = 'iax2'";
			break;
		case "IAX2":
		case "SIP":
		case "ZAP":
		case "DAHDI":
		case 'CUSTOM':
			$sql .= " WHERE tech = '".strtolower($tech)."'";
			break;
		case "ALL":
		default:
	}
	$sql .= ' ORDER BY id';
	$results = sql($sql,"getAll",DB_FETCHMODE_ASSOC);

	$extens = null;
	foreach ($results as $result) {
		if ($get_all || checkRange($result['id'])) {

			$record = array();
			$record[0] = $result['id'];  // for backwards compatibility
			$record[1] = $result['description'];  // for backwards compatibility
			foreach ($result as $key => $value) {
				$record[$key] = $value;
			}
			$extens[] = $record;
			/*
			$extens[] = array(
				0=>$result[0],  // for backwards compatibility
				1=>$result[1],
				'id'=>$result[0], // FETCHMODE_ASSOC emulation
				'description'=>$result[1],
			);
			*/
		}
	}
	return $extens;
}

// get a mapping of the devices to user description and vmcontext
// used for fixed devices when generating tech.conf files to
// override some of the mailbox options or remove them if novm
//
function core_devices_get_user_mappings() {
	static $devices;

	if (isset($devices)) {
		return $devices;
	}
	$device_list = core_devices_list("all",'full', true);
	$device_list = is_array($device_list)?$device_list:array();
	foreach ($device_list as $device) {
		$devices[$device['id']] = $device;
	}
	$user_list = core_users_list(true);
	$user_list = is_array($user_list)?$user_list:array();
	foreach ($user_list as $user) {
		$users[$user[0]]['description'] = $user[1];
		$users[$user[0]]['vmcontext'] = $user[2];
	}
	foreach ($devices as $id => $device) {
		if ($device['devicetype'] == 'fixed') {
			$devices[$id]['vmcontext'] = $users[$device['user']]['vmcontext'];
			$devices[$id]['description'] = $users[$device['user']]['description'];
		}
	}
	return $devices;
}

function core_devices_add($id,$tech,$dial,$devicetype,$user,$description,$emergency_cid=null,$editmode=false){
	_core_backtrace();
	$flag = 2;
	$fields = FreePBX::Core()->convertRequest2Array($id,$tech,$flag);
	$settings = array(
		"dial" => array("value" => $dial),
		"devicetype" => array("value" => $devicetype),
		"user" => array("value" => $user),
		"description" => array("value" => $description),
		"emergency_cid" => array("value" => $emergency_cid)
	);
	$settings = array_merge($settings,$fields);
	// Asterisk treats no CallerID from an IAX device as 'hide CallerID', and ignores the CallerID
	// set in iax.conf. As we rely on this for pretty much everything, we need to specify the
	// CallerID as a variable which gets picked up in macro-callerid.
	// Ref - http://bugs.digium.com/view.php?id=456
	if($tech == 'iax2') {
		$settings['setvar'] = array("value" => "REALCALLERIDNUM=$account", "flag" => $flag++);
	}
	try {
		return FreePBX::Core()->addDevice($id,$tech,$settings,$editmode);
	} catch(Exception $e) {
		echo "<script>javascript:alert('".$e->getMessage()."');</script>";
	}
}

function core_devices_del($account,$editmode=false){
	_core_backtrace();
	return FreePBX::Core()->delDevice($account,$editmode);
}

function core_devices_get($account){
	_core_backtrace();
	return FreePBX::Core()->getDevice($account);
}

//I dont wanna talk about it.
function core_devices_addpjsip($account) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}

//add to sip table
function core_devices_addsip($account,$tech='SIP') {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}

//add to iax table
function core_devices_addiax2($account) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}

function core_devices_addzap($account) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}

function core_devices_adddahdi($account) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}

function core_devices_deliax2($account) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}

function core_devices_delzap($account) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}

function core_devices_deldahdi($account) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}

function core_devices_delpjsip($account) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}

function core_devices_delsip($account) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}

function core_devices_getiax2($account) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}

function core_devices_getzap($account) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}

function core_devices_getdahdi($account) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}

function core_devices_getpjsip($account) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}

function core_devices_getsip($account) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}
/* end page.devices.php functions */

// this function rebuilds the astdb based on device table contents
// used on devices.php if action=resetall
function core_devices2astdb(){
	global $astman;
	global $amp_conf;

	$sql = "SELECT * FROM devices";
	$devresults = sql($sql,"getAll",DB_FETCHMODE_ASSOC);

	//add details to astdb
	if ($astman) {
		$astman->database_deltree("DEVICE");
		foreach ($devresults as $dev) {
			extract($dev);
			$astman->database_put("DEVICE",$id."/dial",$dial);
			$astman->database_put("DEVICE",$id."/type",$devicetype);
			$astman->database_put("DEVICE",$id."/user",$user);
			$astman->database_put("DEVICE",$id."/default_user",$user);
			if(trim($emergency_cid) != '') {
				$astman->database_put("DEVICE",$id."/emergency_cid",$emergency_cid);
			}
			// If a user is selected, add this device to the user
			if ($user != "none") {
				$existingdevices = $astman->database_get("AMPUSER",$user."/device");
				if (empty($existingdevices)) {
					$astman->database_put("AMPUSER",$user."/device",$id);
				} else {
					$existingdevices_array = explode('&',$existingdevices);
					if (!in_array($id, $existingdevices_array)) {
						$existingdevices_array[]=$id;
						$existingdevices = implode('&',$existingdevices_array);
						$astman->database_put("AMPUSER",$user."/device",$existingdevices);
					}
				}
			}


			// create a voicemail symlink if needed
			$thisUser = \FreePBX::Core()->getUser($user);
			if(isset($thisUser['voicemail']) && ($thisUser['voicemail'] != "novm")) {
				if(empty($thisUser['voicemail']))
				$vmcontext = "default";
				else
				$vmcontext = $thisUser['voicemail'];
				//voicemail symlink
				exec("rm -f /var/spool/asterisk/voicemail/device/".$id);
				exec("/bin/ln -s /var/spool/asterisk/voicemail/".$vmcontext."/".$user."/ /var/spool/asterisk/voicemail/device/".$id);
			}
		}
		return true;
	} else {
		return false;
	}
}

// this function rebuilds the astdb based on users table contents
// used on devices.php if action=resetall
function core_users2astdb(){
	global $amp_conf;
	global $astman;
	global $db;

	$sql = "SELECT * FROM users";
	$userresults = sql($sql,"getAll",DB_FETCHMODE_ASSOC);

	//add details to astdb
	if ($astman) {
		foreach($userresults as $usr) {
			extract($usr);
			$astman->database_put("AMPUSER",$extension."/password",$password);
			$astman->database_put("AMPUSER",$extension."/ringtimer",$ringtimer);
			$astman->database_put("AMPUSER",$extension."/noanswer",$noanswer);
			$astman->database_put("AMPUSER",$extension."/recording",$recording);
			$astman->database_put("AMPUSER",$extension."/outboundcid",$outboundcid);
			$astman->database_put("AMPUSER",$extension."/cidname",$name);
			$astman->database_put("AMPUSER",$extension."/voicemail",$voicemail);
		}
		return true;
	} else {
		return false;
	}

	//	TODO: this was...
	// 	return $astman->disconnect();
	//	is "true" the correct value...?
}

function core_hint_get($account){
	global $astman;
	static $hintCache;

	if (isset($hintCache[$account])) {
		return $hintCache[$account];
	}

	$chan_dahdi = ast_with_dahdi();
	// We should always check the AMPUSER in case they logged into a device
	// but we will fall back to the old methond if $astman not open although
	// I'm pretty sure everything else will puke anyhow if not running
	//
	if ($astman) {
		$device=$astman->database_get("AMPUSER",$account."/device");
		$device_arr = explode('&',$device);
		$sql = "SELECT dial from devices where id in ('".implode("','",$device_arr)."')";
	} else {
		$sql = "SELECT dial from devices where user = '{$account}'";
	}
	$results = sql($sql,"getAll",DB_FETCHMODE_ASSOC);

	//create an array of strings
	if (is_array($results)){
		foreach ($results as $result) {
			if ($chan_dahdi) {
				$dial[] = str_replace('ZAP', 'DAHDI', $result['dial']);
			} else {
				$dial[] = $result['dial'];
			}
		}
	}

	//create a string with & delimiter
	if (isset($dial) && is_array($dial)){
		$hint = implode($dial,"&");
	} else {
		if (isset($results[0]['dial'])) {
			$hint = $results[0]['dial'];
		} else {
			$hint = null;
		}
	}

	$hintCache[$account] = $hint;
	return $hint;
}



/* begin page.users.php functions */

// get the existing extensions
// the returned arrays contain [0]:extension [1]:name
function core_users_list($get_all=false){
	_core_backtrace();
	return FreePBX::Core()->listUsers($get_all);
}

function core_check_extensions($exten=true) {
	global $amp_conf;

	$extenlist = array();
	if (is_array($exten) && empty($exten)) {
		return $extenlist;
	}
	$sql = "SELECT extension, name FROM users ";
	if (is_array($exten)) {
		$sql .= "WHERE extension in ('".implode("','",$exten)."')";
	}
	$sql .= " ORDER BY CAST(extension AS UNSIGNED)";
	$results = sql($sql,"getAll",DB_FETCHMODE_ASSOC);

	$display = ($amp_conf['AMPEXTENSIONS'] == "deviceanduser")?'users':'extensions';
	foreach ($results as $result) {
		$thisexten = $result['extension'];
		$extenlist[$thisexten]['description'] = _("User Extension: ").$result['name'];
		$extenlist[$thisexten]['status'] = 'INUSE';
		$extenlist[$thisexten]['edit_url'] = "config.php?display=$display&extdisplay=".urlencode($thisexten)."&skip=0";
	}
	return $extenlist;
}

function core_check_destinations($dest=true) {
	global $active_modules;
	global $amp_conf;

	$destlist = array();
	if (is_array($dest) && empty($dest)) {
		return $destlist;
	}
	// Check Inbound Routes
	//
	$sql = "SELECT extension, cidnum, description, destination FROM incoming ";
	if ($dest !== true) {
		$sql .= "WHERE destination in ('".implode("','",$dest)."')";
	}
	$sql .= "ORDER BY extension, cidnum";
	$results = sql($sql,"getAll",DB_FETCHMODE_ASSOC);

	//$type = isset($active_modules['announcement']['type'])?$active_modules['announcement']['type']:'setup';

	foreach ($results as $result) {
		$thisdest = $result['destination'];
		$thisid   = $result['extension'].'/'.$result['cidnum'];
		$destlist[] = array(
			'dest' => $thisdest,
			'description' => sprintf(_("Inbound Route: %s (%s)"),$result['description'],$thisid),
			'edit_url' => 'config.php?display=did&view=form&extdisplay='.urlencode($thisid),
		);
	}

	// Check Extension/User Destinations
	//
	$sql = "SELECT extension, name, busy_dest, noanswer_dest, chanunavail_dest FROM users ";
	if ($dest !== true) {
		$sql .= "WHERE (busy_dest in ('".implode("','",$dest)."')) OR (noanswer_dest in ('".implode("','",$dest)."')) OR (chanunavail_dest in ('".implode("','",$dest)."'))";
	}
	$results = sql($sql,"getAll",DB_FETCHMODE_ASSOC);

	$display = ($amp_conf['AMPEXTENSIONS'] == "deviceanduser")?'users':'extensions';
	$label   = ($amp_conf['AMPEXTENSIONS'] == "deviceanduser")?'User':'Exten';
	foreach ($results as $result) {
		$thisdest    = $result['busy_dest'];
		$thisid      = $result['extension'];
		$description = sprintf("%s: %s (%s)",$label,$result['name'],$thisid);
		$thisurl     = 'config.php?display='.$display.'&extdisplay='.urlencode($thisid);
		if (($dest === true && $thisdest != '') || $dest = $thisdest) {
			$destlist[] = array(
				'dest' => $thisdest,
				'description' => $description,
				'edit_url' => $thisurl,
			);
		}
		$thisdest = $result['noanswer_dest'];
		if (($dest === true && $thisdest != '') || $dest = $thisdest) {
			$destlist[] = array(
				'dest' => $thisdest,
				'description' => $description,
				'edit_url' => $thisurl,
			);
		}
		$thisdest = $result['chanunavail_dest'];
		if (($dest === true && $thisdest != '') || $dest = $thisdest) {
			$destlist[] = array(
				'dest' => $thisdest,
				'description' => $description,
				'edit_url' => $thisurl,
			);
		}
	}

	return $destlist;
}

function core_change_destination($old_dest, $new_dest) {
	$sql = 'UPDATE users SET noanswer_dest = "' . $new_dest . '" WHERE noanswer_dest = "' . $old_dest . '"';
	sql($sql, "query");

	$sql = 'UPDATE users SET busy_dest = "' . $new_dest . '" WHERE busy_dest = "' . $old_dest . '"';
	sql($sql, "query");

	$sql = 'UPDATE users SET chanunavail_dest = "' . $new_dest . '" WHERE chanunavail_dest = "' . $old_dest . '"';
	sql($sql, "query");

	$sql = 'UPDATE incoming SET destination = "' . $new_dest . '" WHERE destination = "' . $old_dest . '"';
	sql($sql, "query");
}


function core_sipname_check($sipname, $extension) {
	return FreePBX::Core()->checkSipnameInUse($sipname,$extension);
}

function core_users_add($vars, $editmode=false) {
	_core_backtrace();
	try {
		return FreePBX::Core()->addUser($vars['extension'], $vars, $editmode);
	} catch(Exception $e) {
		echo "<script>javascript:alert('".$e->getMessage()."');</script>";
	}
}

function core_users_get($extension){
	_core_backtrace();
	return FreePBX::Core()->getUser($extension);
}

function core_users_del($extension, $editmode=false){
	_core_backtrace();
	return FreePBX::Core()->delUser($extension,$editmode);
}

function core_users_directdid_get($directdid=""){
	return array();
}

function core_users_cleanastdb($extension) {
	// This is called to remove any ASTDB traces of the user after a deletion. Otherwise,
	// call forwarding, call waiting settings could hang around and bite someone if they
	// recycle an extension. Is called from page.xtns and page.users.
	global $amp_conf;
	global $astman;

	if ($astman) {
		$astman->database_del("CW",$extension);
		$astman->database_del("CF",$extension);
		$astman->database_del("CFB",$extension);
		$astman->database_del("CFU",$extension);

	} else {
		die_freepbx("Cannot connect to Asterisk Manager with ".$amp_conf["AMPMGRUSER"]."/".$amp_conf["AMPMGRPASS"]);
	}
}

function core_users_edit($extension, $vars){
	global $db;
	global $amp_conf;
	global $astman;

	//I we are editing, we need to remember existing user<->device mapping, so we can delete and re-add
	if ($astman) {
		$ud = $astman->database_get("AMPUSER",$extension."/device");
		$current_vmcontext = $astman->database_get("AMPUSER",$extension."/voicemail");
		$new_vmcontext = isset($vars['vmcontext']) ? $vars['vmcontext'] : 'novm';
		$vars['device'] = $ud;
	} else {
		die_freepbx("Cannot connect to Asterisk Manager with ".$amp_conf["AMPMGRUSER"]."/".$amp_conf["AMPMGRPASS"]);
	}

	// clean and check the did to make sure it is not being used by another extension or in did routing
	//
	$newdid_name = isset($newdid_name) ? $db->escapeSimple($newdid_name) : '';
	$newdid = isset($vars['newdid']) ? $vars['newdid'] : '';
	$newdid = preg_replace("/[^0-9._XxNnZz\[\]\-\+]/" ,"", trim($newdid));

	$newdidcid = isset($vars['newdidcid']) ? trim($vars['newdidcid']) : '';
	if (!preg_match('/^priv|^block|^unknown|^restrict|^unavail|^anonym|^withheld/',strtolower($newdidcid))) {
		$newdidcid = preg_replace("/[^0-9._XxNnZz\[\]\-\+]/" ,"", $newdidcid);
	}

	// Well more ugliness since the javascripts are already in here
	if ($newdid != '' || $newdidcid != '') {
		$existing = \FreePBX::Core()->getDID($newdid, $newdidcid);
		if (! empty($existing)) {
			echo "<script>javascript:alert('".sprintf(_("A route with this DID/CID: %s/%s already exists"),$existing['extension'],$existing['cidnum'])."')</script>";
			return false;
		}
	}

	//delete and re-add
	if (core_sipname_check($vars['sipname'],$extension)) {
		\FreePBX::Core()->delUser($extension, true);
		core_users_add($vars, true);

		// If the vmcontext has changed, we need to change all the links. In extension mode, the link
		// to the current fixed device will get changed, but none others will
		//
		if ($current_vmcontext != $new_vmcontext) {
			$user_devices = explode('&',$ud);
			foreach ($user_devices as $user_device) {
				exec("rm -f /var/spool/asterisk/voicemail/device/".$user_device);
				if ($new_vmcontext != 'novm') {
					exec("/bin/ln -s /var/spool/asterisk/voicemail/".$new_vmcontext."/".$extension."/ /var/spool/asterisk/voicemail/device/".$user_device);
				}
			}
		}
	}
	return true;
}

function core_directdid_list(){
	return array();
}

function core_dahdichandids_add($description, $channel, $did) {
	global $db;


	if (!ctype_digit(trim($channel)) || trim($channel) == '') {
		echo "<script>javascript:alert('"._("Invalid Channel Number, must be numeric and not blank")."')</script>";
		return false;
	}
	if (trim($did) == '') {
		echo "<script>javascript:alert('"._("Invalid DID, must be a non-blank DID")."')</script>";
		return false;
	}

	$description = q($description);
	$channel     = q($channel);
	$did         = q($did);

	$sql = "INSERT INTO dahdichandids (channel, description, did) VALUES ($channel, $description, $did)";
	$results = $db->query($sql);
	if (DB::IsError($results)) {
		if ($results->getCode() == DB_ERROR_ALREADY_EXISTS) {
			echo "<script>javascript:alert('"._("Error Duplicate Channel Entry")."')</script>";
			return false;
		} else {
			die_freepbx($results->getMessage()."<br><br>".$sql);
		}
	}
	return true;
}

function core_dahdichandids_edit($description, $channel, $did) {
	global $db;

	$description = q($description);
	$channel     = q($channel);
	$did         = q($did);

	$sql = "UPDATE dahdichandids SET description = $description, did = $did WHERE channel = $channel";
	$results = $db->query($sql);
	if (DB::IsError($results)) {
		die_freepbx($results->getMessage()."<br><br>".$sql);
	}
	return true;
}

function core_dahdichandids_delete($channel) {
	global $db;

	$channel     = q($channel);

	$sql = "DELETE FROM dahdichandids WHERE channel = $channel";
	$results = $db->query($sql);
	if (DB::IsError($results)) {
		die_freepbx($results->getMessage()."<br><br>".$sql);
	}
	return true;
}

function core_dahdichandids_list() {
	global $db;

	$sql = "SELECT * FROM dahdichandids ORDER BY channel";
	return sql($sql,"getAll",DB_FETCHMODE_ASSOC);
}

function core_dahdichandids_get($channel) {
	global $db;

	$channel     = q($channel);

	$sql = "SELECT * FROM dahdichandids WHERE channel = $channel";
	return sql($sql,"getRow",DB_FETCHMODE_ASSOC);
}

/* end page.users.php functions */



/* begin page.trunks.php functions */

/**
* @pram string; can be a trunk id, all or *, or registered/reg for just trunks that are registered
* @pram boolean; true disables trunk, false is enables trunk
*/
function core_trunks_disable($trunk, $switch) {
	switch ($trunk) {
		case 'all':
		case '*':
		$trunks = core_trunks_getDetails();
		break;
		case 'reg':
		case 'registered':
		foreach (core_trunks_getDetails() as $t) {
			if($reg = core_trunks_getTrunkRegister($t['trunkid'])) {
				$trunks[] = $t;
			}
		}
		break;
		case '':
		return false;//cannot call without a trunk
		break;
		default:
		$trunks[] = core_trunks_getDetails($trunk);
		break;
	}

	//return if no trunks!
	if (empty($trunks)) {
		return false;
	}

	foreach ($trunks as $t) {
		$trunk			= core_trunks_getDetails($t['trunkid']);
		$regstring 		= core_trunks_getTrunkRegister($t['trunkid']);
		$userconfig		= core_trunks_getTrunkUserConfig($t['trunkid']);
		$peerdetails	= core_trunks_getTrunkPeerDetails($t['trunkid']);
		$disabled		= $switch ? 'on' : 'off';

		core_trunks_edit(
		$trunk['trunkid'],
		$trunk['channelid'],
		$trunk['dialoutprefix'],
		$trunk['maxchans'],
		$trunk['outcid'],
		$peerdetails,
		$trunk['usercontext'],
		$userconfig,
		$regstring,
		$trunk['keepcid'],
		$trunk['failscript'],
		$disabled,
		$trunk['name'],
		$trunk['provider'],
		$trunk['continue']
	);

}
}

// we're adding ,don't require a $trunknum
function core_trunks_add($tech, $channelid, $dialoutprefix, $maxchans, $outcid, $peerdetails, $usercontext, $userconfig, $register, $keepcid, $failtrunk, $disabletrunk, $name="", $provider="", $continue="off", $dialopts=false) {
	global $db;
	$name = trim($name) == "" ? $channelid : $name;

	// find the next available ID
	$trunknum = 1;

	// This is pretty ugle, will fix when we redo trunks and routes with proper uniqueids.
	// get the list, sort them, then look for a hole and use it, or overflow to the end if
	// not and use that
	//
	$trunk_hash = array();
	foreach(core_trunks_list() as $trunk) {
		$trunknum = ltrim($trunk[0],"OUT_");
		$trunk_hash[] = $trunknum;
	}
	sort($trunk_hash);
	$trunknum = 1;
	foreach ($trunk_hash as $trunk_id) {
		if ($trunk_id != $trunknum) {
			break;
		}
		$trunknum++;
	}

	core_trunks_backendAdd($trunknum, $tech, $channelid, $dialoutprefix, $maxchans, $outcid, $peerdetails, $usercontext, $userconfig, $register, $keepcid, $failtrunk, $disabletrunk, $name, $provider, $continue, $dialopts);
	return $trunknum;
}

function core_trunks_del($trunknum, $tech = null) {
	global $db, $astman;

	if ($tech === null) { // in EditTrunk, we get this info anyways
		$tech = core_trunks_getTrunkTech($trunknum);
	}

	// conditionally, delete from iax or sip
	switch (strtolower($tech)) {
		case "iax2":
		$tech = "iax";
		// fall through
		case "iax":
		case "sip":
		sql("DELETE FROM `$tech` WHERE `id` IN ('tr-peer-$trunknum', 'tr-user-$trunknum', 'tr-reg-$trunknum')");
		break;
		case "pjsip":
		sql("DELETE FROM `pjsip` WHERE `id`='$trunknum'");
		break;
	}
	sql("DELETE FROM `trunks` WHERE `trunkid` = '$trunknum'");
	if ($astman) {
		$astman->database_del("TRUNK", $trunknum . '/dialopts');
	}
}

function core_trunks_edit($trunknum, $channelid, $dialoutprefix, $maxchans, $outcid, $peerdetails, $usercontext, $userconfig, $register, $keepcid, $failtrunk, $disabletrunk, $name="", $provider="", $continue='off', $dialopts = false) {
	global $db;
	$name = trim($name) == "" ? $channelid : $name;

	$tech = core_trunks_getTrunkTech($trunknum);
	if ($tech == "") {
		return false;
	}
	core_trunks_del($trunknum, $tech);
	core_trunks_backendAdd($trunknum, $tech, $channelid, $dialoutprefix, $maxchans, $outcid, $peerdetails, $usercontext, $userconfig, $register, $keepcid, $failtrunk, $disabletrunk, $name, $provider, $continue, $dialopts);
}

// just used internally by addTrunk() and editTrunk()
//obsolete
// This is not obsolete 8-( 2013-12-31.
function core_trunks_backendAdd($trunknum, $tech, $channelid, $dialoutprefix, $maxchans, $outcid, $peerdetails, $usercontext, $userconfig, $register, $keepcid, $failtrunk, $disabletrunk, $name, $provider, $continue, $dialopts=false) {
	global $db, $astman;

	if  (is_null($dialoutprefix)) $dialoutprefix = ""; // can't be NULL

	//echo  "backendAddTrunk($trunknum, $tech, $channelid, $dialoutprefix, $maxchans, $outcid, $peerdetails, $usercontext, $userconfig, $register)";

	// change iax to "iax2" (only spot we actually store iax2, since its used by Dial()..)
	$techtemp = ((strtolower($tech) == "iax") ? "iax2" : $tech);
	$outval = (($techtemp == "custom") ? "AMP:".$channelid : strtoupper($techtemp).'/'.$channelid);
	unset($techtemp);

	$disable_flag = ($disabletrunk == "on")?1:0;

	switch (strtolower($tech)) {
		case "iax":
		case "iax2":
		core_trunks_addSipOrIax($peerdetails,'iax',$channelid,$trunknum,$disable_flag,'peer');
		if ($usercontext != ""){
			core_trunks_addSipOrIax($userconfig,'iax',$usercontext,$trunknum,$disable_flag,'user');
		}
		if ($register != ""){
			core_trunks_addRegister($trunknum,'iax',$register,$disable_flag);
		}
		break;
		case "sip":
		core_trunks_addSipOrIax($peerdetails,'sip',$channelid,$trunknum,$disable_flag,'peer');
		if ($usercontext != ""){
			core_trunks_addSipOrIax($userconfig,'sip',$usercontext,$trunknum,$disable_flag,'user');
		}
		if ($register != ""){
			core_trunks_addRegister($trunknum,'sip',$register,$disable_flag);
		}
		break;
		case "pjsip":
		$pjsip = FreePBX::Core()->getDriver('pjsip');
		if($pjsip !== false) {
			$displayvars = $pjsip->addTrunk($trunknum);
		}
		break;
	}

	$sql = "
	INSERT INTO `trunks`
	(`trunkid`, `name`, `tech`, `outcid`, `keepcid`, `maxchans`, `failscript`, `dialoutprefix`, `channelid`, `usercontext`, `provider`, `disabled`, `continue`)
	VALUES (
	'$trunknum',
	'".$db->escapeSimple($name)."',
	'".$db->escapeSimple($tech)."',
	'".$db->escapeSimple($outcid)."',
	'".$db->escapeSimple($keepcid)."',
	'".$db->escapeSimple($maxchans)."',
	'".$db->escapeSimple($failtrunk)."',
	'".$db->escapeSimple($dialoutprefix)."',
	'".$db->escapeSimple($channelid)."',
	'".$db->escapeSimple($usercontext)."',
	'".$db->escapeSimple($provider)."',
	'".$db->escapeSimple($disabletrunk)."',
	'".$db->escapeSimple($continue)."'
	)";
	sql($sql);

	if ($astman) {
		if ($dialopts !== false) {
			$astman->database_put("TRUNK", $trunknum . '/dialopts',$dialopts);
		} else {
			$astman->database_del("TRUNK", $trunknum . '/dialopts');
		}
	}
}

//TODO: replace with NEW table
//
function core_trunks_getTrunkTech($trunknum) {
	$tech = sql("SELECT `tech` FROM `trunks` WHERE `trunkid` = $trunknum", "getOne");
	if (!$tech) {
		return false;
	}
	$tech = strtolower($tech);
	if ($tech == "iax2") {
		$tech = "iax"; // same thing, here
	}
	return $tech;
}

//add trunk info to sip or iax table
function core_trunks_addSipOrIax($config,$table,$channelid,$trunknum,$disable_flag=0,$type='peer') {
	global $db;
	switch ($type) {
		case 'peer':
		$trunknum = 'tr-peer-'.$trunknum;
		break;
		case 'user':
		$trunknum = 'tr-user-'.$trunknum;
		break;
	}

	$confitem['account'] = $channelid;
	$gimmieabreak = nl2br($config);
	$lines = preg_split('#<br />#',$gimmieabreak);
	foreach ($lines as $line) {
		$line = trim($line);
		if (count(preg_split('/=/',$line)) > 1) {
			$tmp = preg_split('/=/',$line,2);
			$key=trim($tmp[0]);
			$value=trim($tmp[1]);
			if (isset($confitem[$key]) && !empty($confitem[$key]))
			$confitem[$key].="&".$value;
			else
			$confitem[$key]=$value;
		}
	}
	// rember 1=disabled so we start at 2 (1 + the first 1)
	$seq = 1;
	foreach($confitem as $k=>$v) {
		$seq = ($disable_flag == 1) ? 1 : $seq+1;
		$dbconfitem[]=array($db->escapeSimple($k),$db->escapeSimple($v),$seq);
	}
	$compiled = $db->prepare("INSERT INTO $table (id, keyword, data, flags) values ('$trunknum',?,?,?)");
	$result = $db->executeMultiple($compiled,$dbconfitem);
	if(DB::IsError($result)) {
		die_freepbx($result->getMessage()."<br><br>INSERT INTO $table (id, keyword, data, flags) values ('$trunknum',?,?,'$disable_flag')");
	}
}

//get unique trunks
function core_trunks_getDetails($trunkid='') {
	global $db;
	global $amp_conf;
	global $astman;

	if ($trunkid != '') {
		$sql = "SELECT * FROM `trunks` WHERE `trunkid` = ?";
		$trunk = $db->getRow($sql,array($trunkid),DB_FETCHMODE_ASSOC);
		$tech = strtolower($trunk['tech']);
		switch ($tech) {
			case 'iax2':
			$trunk['tech'] = 'iax';
			break;
			default:
			$trunk['tech'] = $tech;
			break;
		}
		if ($astman) {
			$trunk['dialopts'] = $astman->database_get("TRUNK",$trunkid . "/dialopts");
		}
	} else {
		$sql = "SELECT * FROM `trunks` ORDER BY tech, name";
		$trunk = sql($sql,"getAll",DB_FETCHMODE_ASSOC);
		if ($astman) {
			$tops = $astman->database_show('TRUNK');
			foreach ($trunk as $i => $t) {
				if (isset($tops['/TRUNK/' . $t['trunkid'] . '/dialopts'])) {
					$trunk[$i]['dialopts'] = $tops['/TRUNK/' . $t['trunkid'] . '/dialopts'];
				} else {
					$trunk[$i]['dialopts'] = false;
				}
			}
		}
	}
	return $trunk;
}

function core_trunks_listbyid() {
	$result = sql('SELECT * from `trunks` ORDER BY `trunkid`','getAll',DB_FETCHMODE_ASSOC);
	$trunk_list = array();
	foreach ($result as $trunk) {
		if ($trunk['name'] == '') {
			$tech = strtoupper($trunk['tech']);
			switch ($tech) {
				case 'IAX':
				$trunk['name'] = 'IAX2/'.$trunk['channelid'];
				break;
				case 'CUSTOM':
				$trunk['name'] = 'AMP:'.$trunk['channelid'];
				break;
				default:
				$trunk['name'] = $tech.'/'.$trunk['channelid'];
				break;
			}
		}
		$trunk_list[$trunk['trunkid']] = $trunk;
	}
	return $trunk_list;
}

function core_trunks_list($assoc = false) {
	// TODO: $assoc default to true, eventually..

	global $db;
	global $amp_conf;

	$sql = "SELECT `trunkid` , `tech` , `channelid` , `disabled` FROM `trunks` ORDER BY `trunkid`";
	$trunks = sql($sql,"getAll",DB_FETCHMODE_ASSOC);

	$unique_trunks = array();
	foreach ($trunks as $trunk) {
		$trunk_id = "OUT_".$trunk['trunkid'];
		$disabled = $trunk['disabled'];
		$tech = strtoupper($trunk['tech']);
		switch ($tech) {
			case 'IAX':
			$dialstring = 'IAX2/'.$trunk['channelid'];
			break;
			case 'CUSTOM':
			$dialstring = 'AMP:'.$trunk['channelid'];
			break;
			default:
			$dialstring = $tech.'/'.$trunk['channelid'];
			break;
		}
		$unique_trunks[] = array($trunk_id, $dialstring, $disabled);
	}

	if ($assoc) {
		$trunkinfo = array();

		foreach ($unique_trunks as $trunk) {
			list($tech,$name) = explode('/',$trunk[1]);
			$trunkinfo[$trunk[1]] = array(
				'name' => $name,
				'tech' => $tech,
				'globalvar' => $trunk[0], // ick
				'value' => $trunk[2], // ??  no idea what this is.
			);
		}

		return $trunkinfo;
	} else {
		return $unique_trunks;
	}
}

function core_trunks_addRegister($trunknum,$tech,$reg,$disable_flag=0) {
	global $db;
	$reg = $db->escapeSimple(trim($reg));
	sql("INSERT INTO $tech (id, keyword, data, flags) values ('tr-reg-$trunknum','register','$reg','$disable_flag')");
}


function core_trunks_update_dialrules($trunknum, &$patterns, $delete = false) {
	global $db;

	$trunknum =  $db->escapeSimple($trunknum);
	$filter_prepend = '/[^0-9+*#wW]/';
	$filter_prefix = '/[^0-9*#+xnzXNZ\-\[\]]/';
	$filter_match =  '/[^0-9.*#+xnzXNZ\-\[\]]/';

	$insert_pattern = array();
	$seq = 0;
	foreach ($patterns as $pattern) {
		$match_pattern_prefix = $db->escapeSimple(preg_replace($filter_prefix,'',strtoupper(trim($pattern['match_pattern_prefix']))));
		$match_pattern_pass = $db->escapeSimple(preg_replace($filter_match,'',strtoupper(trim($pattern['match_pattern_pass']))));
		$prepend_digits = $db->escapeSimple(str_replace('W', 'w', preg_replace($filter_prepend,'',strtoupper(trim($pattern['prepend_digits'])))));
		if ($match_pattern_prefix.$match_pattern_pass == '') {
			continue;
		}
		// if duplicate prepend, get rid of subsequent since they will never be checked
		$hash_index = md5($match_pattern_prefix.$match_pattern_pass);
		if (!isset($insert_pattern[$hash_index])) {
			$insert_pattern[$hash_index] = array($match_pattern_prefix, $match_pattern_pass, $prepend_digits, $seq);
			$seq++;
		}
	}

	if ($delete) {
		sql('DELETE FROM `trunk_dialpatterns` WHERE `trunkid`='.q($trunknum));
	}
	$compiled = $db->prepare('INSERT INTO `trunk_dialpatterns` (`trunkid`, `match_pattern_prefix`, `match_pattern_pass`, `prepend_digits`, `seq`) VALUES ('.$trunknum.',?,?,?,?)');
	$result = $db->executeMultiple($compiled,$insert_pattern);
	if(DB::IsError($result)) {
		die_freepbx($result->getDebugInfo()."<br><br>".'error updating trunk_dialpatterns');
	}
}

function core_trunks_list_dialrules() {
	$rule_hash = array();

	$patterns = core_trunks_get_dialrules();
	foreach ($patterns as $pattern) {
		//$rule_hash[$pattern['trunkid']][] = $pattern['prepend_digits'].'^'.$pattern['match_pattern_prefix'].'|'.$pattern['match_pattern_pass'];
		$rule_hash[$pattern['trunkid']][] = $pattern;
	}
	return $rule_hash;
}

/* THIS HAS BEEN DEPRECATED BUT WILL REMAIN IN FOR A FEW RELEASES */
function core_trunks_parse_conf($filename, &$conf, &$section) {
	if (is_null($conf)) {
		$conf = array();
	}
	if (is_null($section)) {
		$section = "general";
	}

	if (file_exists($filename)) {
		$fd = fopen($filename, "r");
		while ($line = fgets($fd, 1024)) {
			if (preg_match("/^\s*([a-zA-Z0-9-_]+)\s*=\s*(.*?)\s*([;#].*)?$/",$line,$matches)) {
				// name = value
				// option line
				$conf[$section][ $matches[1] ] = $matches[2];
			} else if (preg_match("/^\s*\[(.+)\]/",$line,$matches)) {
				// section name
				$section = strtolower($matches[1]);
			} else if (preg_match("/^\s*#include\s+(.*)\s*([;#].*)?/",$line,$matches)) {
				// include another file

				if ($matches[1][0] == "/") {
					// absolute path
					$filename = $matches[1];
				} else {
					// relative path
					$filename =  dirname($filename)."/".$matches[1];
				}

				core_trunks_parse_conf($filename, $conf, $section);
			}
		}
	}
}

function core_trunks_getTrunkTrunkName($trunknum) {
	$name = sql("SELECT `name` FROM `trunks` WHERE `trunkid` = $trunknum", "getOne");
	return $name;
}

function core_trunks_getTrunkPeerDetails($trunknum) {
	global $db;

	$tech = core_trunks_getTrunkTech($trunknum);

	if (!core_trunk_has_registrations($tech)) {
		return '';
	}
	$results = sql("SELECT keyword,data FROM $tech WHERE `id` = 'tr-peer-$trunknum' ORDER BY flags, keyword DESC","getAll");

	foreach ($results as $result) {
		if ($result[0] != 'account') {
			if (isset($confdetail))
			$confdetail .= $result[0] .'='. $result[1] . "\n";
			else
			$confdetail = $result[0] .'='. $result[1] . "\n";
		}
	}
	return $confdetail;
}

function core_trunks_getTrunkUserContext($trunknum) {
	$usercontext = sql("SELECT `usercontext` FROM `trunks` WHERE `trunkid` = $trunknum", "getOne");
	return ((isset($usercontext)) ? $usercontext : '');
}

function core_trunks_getTrunkUserConfig($trunknum) {
	global $db;

	$tech = core_trunks_getTrunkTech($trunknum);
	if (!core_trunk_has_registrations($tech)) {
		return '';
	}

	$results = sql("SELECT keyword,data FROM $tech WHERE `id` = 'tr-user-$trunknum' ORDER BY flags, keyword DESC","getAll");

	foreach ($results as $result) {
		if ($result[0] != 'account') {
			if (isset($confdetail))
			$confdetail .= $result[0] .'='. $result[1] . "\n";
			else
			$confdetail = $result[0] .'='. $result[1] . "\n";
		}
	}
	return isset($confdetail)?$confdetail:null;
}

//get trunk account register string
function core_trunks_getTrunkRegister($trunknum) {
	$tech = core_trunks_getTrunkTech($trunknum);
	if (!core_trunk_has_registrations($tech)){
		return '';
	}

	$results = sql("SELECT `keyword`, `data` FROM $tech WHERE `id` = 'tr-reg-$trunknum'","getAll");

	foreach ($results as $result) {
		$register = $result[1];
	}
	return isset($register)?$register:null;
}

function core_trunks_get_dialrules($trunknum = false) {
	global $db;
	if ($trunknum === false) {
		$sql = "SELECT * FROM `trunk_dialpatterns` ORDER BY `trunkid`, `seq`";
	} else {
		$trunknum = q($db->escapeSimple($trunknum));
		$sql = "SELECT * FROM `trunk_dialpatterns` WHERE `trunkid` = $trunknum  ORDER BY `seq`";
	}
	$patterns = sql($sql,"getAll",DB_FETCHMODE_ASSOC);
	return $patterns;
}


//get outbound routes for a given trunk
function core_trunks_gettrunkroutes($trunknum) {
	$sql = 'SELECT a.seq, b.name FROM outbound_route_trunks a JOIN outbound_routes b ON a.route_id = b.route_id WHERE trunk_id = '.$trunknum;
	$results = sql( $sql, "getAll" ,DB_FETCHMODE_ASSOC);

	$routes = array();
	foreach ($results as $entry) {
		$routes[$entry['name']] = $entry['seq'];
	}
	return $routes;
}

function core_trunks_delete_dialrules($trunknum) {
	global $db;
	$trunknum = q($db->escapeSimple($trunknum));
	sql("DELETE FROM `trunk_dialpatterns` WHERE `trunkid` = $trunknum");
}


//-----------------------------------------------------------------------------------------------------------------------------------------
// The following APIs have all been removed and will result in crashes with traceback to obtain calling tree information

function core_trunks_addDialRules($trunknum, $dialrules) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}

function core_trunk_has_registrations($type = ''){
	$types = array(
		'zap',
		'dahdi',
		'custom',
		''
	);
	return !in_array($type, $types);

}

function core_trunks_deleteDialRules($trunknum) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}

function core_trunks_getDialRules($trunknum) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}

function core_trunks_readDialRulesFile() {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}


/* end page.trunks.php functions */


/* begin page.routing.php functions */

//-----------------------------------------------------------------------------------------------------------------------------------------

// function core_routing_getroutepassword($route)
// function core_routing_getrouteemergency($route)
// function core_routing_getrouteintracompany($route)
// function core_routing_getroutemohsilence($route)
// function core_routing_getroutecid($route)
function core_routing_get($route_id) {
	global $db;
	$sql = 'SELECT a.*, b.seq FROM `outbound_routes` a JOIN `outbound_route_sequence` b ON a.route_id = b.route_id WHERE a.route_id='.q($db->escapeSimple($route_id));
	$route = sql($sql,"getRow",DB_FETCHMODE_ASSOC);
	return $route;
}

// function core_routing_getroutenames()
function core_routing_list() {
	_core_backtrace();
	return \FreePBX::Core()->getAllRoutes();
}

// function core_routing_setroutepriority($routepriority, $reporoutedirection, $reporoutekey)
function core_routing_setrouteorder($route_id, $seq) {
	global $db;
	$sql = "SELECT `route_id` FROM `outbound_route_sequence` ORDER BY `seq`";
	$sequence = $db->getCol($sql);
	if(DB::IsError($sequence)) {
		die_freepbx($sequence->getDebugInfo());
	}

	if ($seq != 'new') {
		$key = array_search($route_id,$sequence);
		if ($key === false) {
			return(false);
		}
	}
	switch ("$seq") {
		case 'up':
		if (!isset($sequence[$key-1])) break;
		$previous = $sequence[$key-1];
		$sequence[$key-1] = $route_id;
		$sequence[$key] = $previous;
		break;
		case 'down':
		if (!isset($sequence[$key+1])) break;
		$previous = $sequence[$key+1];
		$sequence[$key+1] = $route_id;
		$sequence[$key] = $previous;
		break;
		case 'top':
		unset($sequence[$key]);
		array_unshift($sequence,$route_id);
		break;
		case 'bottom':
		unset($sequence[$key]);
		case 'new':
		// fallthrough, no break
		$sequence[]=$route_id;
		break;
		case '0':
		unset($sequence[$key]);
		array_unshift($sequence,$route_id);
		break;
		default:
		if (!ctype_digit($seq)) {
			return false;
		}
		if ($seq >= count($sequence)-1) {
			unset($sequence[$key]);
			$sequence[] = $route_id;
			break;
		}
		if ($sequence[$seq] == $route_id) {
			break;
		}
		$sequence[$key] = "bookmark";
		$remainder = array_slice($sequence,$seq);
		array_unshift($remainder,$route_id);
		$sequence = array_merge(array_slice($sequence,0,$seq), $remainder);
		unset($sequence[array_search("bookmark",$sequence)]);
		break;
	}
	$insert_array = array();
	$seq = 0;
	$final_seq = false;
	foreach($sequence as $rid) {
		$insert_array[] = array($rid, $seq);
		if ($rid === $route_id) {
			$final_seq = $seq;
		}
		$seq++;
	}
	sql('DELETE FROM `outbound_route_sequence` WHERE 1');
	$compiled = $db->prepare('INSERT INTO `outbound_route_sequence` (`route_id`, `seq`) VALUES (?,?)');
	$result = $db->executeMultiple($compiled,$insert_array);
	if(DB::IsError($result)) {
		die_freepbx($result->getDebugInfo()."<br><br>".'error reordering outbound_route_sequence');
	}
	return $final_seq;
}

// function core_routing_del($name)
function core_routing_delbyid($route_id) {
	global $db;
	$ret = array();
	$sql = 'DELETE FROM outbound_routes WHERE route_id = ?';
	$sth = $db->prepare($sql);
	$ret[] = $sth->execute(array($route_id));
	$sql = 'DELETE FROM outbound_route_patterns WHERE route_id = ?';
	$sth = $db->prepare($sql);
	$ret[] = $sth->execute(array($route_id));
	$sql = 'DELETE FROM outbound_route_trunks WHERE route_id = ?';
	$sth = $db->prepare($sql);
	$ret[] = $sth->execute(array($route_id));
	$sql = 'DELETE FROM outbound_route_sequence WHERE route_id = ?';
	$sth = $db->prepare($sql);
	$ret[] = $sth->execute(array($route_id));
	return $ret;
}

// function core_routing_trunk_del($trunknum)
function core_routing_trunk_delbyid($trunk_id) {
	global $db;
	$trunk_id = q($db->escapeSimple($trunk_id));
	sql('DELETE FROM `outbound_route_trunks` WHERE `trunk_id` ='.$trunk_id);
}

// function core_routing_rename($oldname, $newname)
function core_routing_renamebyid($route_id, $new_name) {
	global $db;
	$route_id = q($db->escapeSimple($route_id));
	$new_name = $db->escapeSimple($new_name);
	sql("UPDATE `outbound_routes` SET `name = '$new_name'  WHERE `route_id` = $route_id");
}

// function core_routing_getroutepatterns($route)
function core_routing_getroutepatternsbyid($route_id) {
	global $db;
	$route_id = q($db->escapeSimple($route_id));
	$sql = "SELECT * FROM `outbound_route_patterns` WHERE `route_id` = $route_id ORDER BY `match_pattern_prefix`, `match_pattern_pass`";
	$patterns = sql($sql,"getAll",DB_FETCHMODE_ASSOC);
	return $patterns;
}

/* Utility function to determine required dialpattern and offsets for a specific dialpattern record.
* Used when generating the dialplan and can be used by other modules that may be splicing into the
* dialplan such as pinsets or others.
*/
function core_routing_formatpattern($pattern) {
	$exten = $pattern['match_pattern_prefix'].$pattern['match_pattern_pass'];
	$cid = $pattern['match_cid'];
	if (!preg_match("/^[0-9*+]+$/",$exten)) {
		// if # is detected above (as not in the list of acceptable patterns) then _ is appended due to Asterisk
		// particulars in dealing with #
		$exten = "_".$exten;
	}
	if ($cid != '' && !preg_match("/^[0-9*+]+$/",$cid)) {
		// same comment as above wrt to #
		$cid = "_".$cid;
	}
	$full_exten = $cid != '' ? $exten.'/'.$cid : $exten;
	// ticket #3998: the $pos is incorrect if a range is included such as 9[0-3]|NXX in the prefix.
	// in this example we end up with EXTEN:6 instead of the correct EXTEN:2
	//
	$pos = strlen(preg_replace('/(\[[^\]]*\])/','X',$pattern['match_pattern_prefix']));
	return array('prepend_digits' => $pattern['prepend_digits'], 'dial_pattern' => $full_exten, 'base_pattern' => $exten, 'offset' => $pos);
}

// function core_routing_getroutetrunks($route)
function core_routing_getroutetrunksbyid($route_id) {
	global $db;
	$route_id = q($db->escapeSimple($route_id));
	$sql = "SELECT `trunk_id` FROM `outbound_route_trunks` WHERE `route_id` = $route_id ORDER BY `seq`";
	$trunks = $db->getCol($sql);
	if(DB::IsError($trunks)) {
		die_freepbx($trunks->getDebugInfo());
	}
	return $trunks;
}

// function core_routing_edit($name,$patterns,$trunks,$pass,$emergency="",$intracompany="",$mohsilence="",$routecid="",$routecid_mode)
function core_routing_editbyid($route_id, $name, $outcid, $outcid_mode, $password, $emergency_route, $intracompany_route, $mohclass, $time_group_id, $patterns, $trunks, $seq = '', $dest = '') {
	global $db;

	$route_id = $db->escapeSimple($route_id);
	$name = $db->escapeSimple($name);
	$outcid = $db->escapeSimple($outcid);
	$outcid_mode = trim($outcid) == '' ? '' : $db->escapeSimple($outcid_mode);
	$password = $db->escapeSimple($password);
	$emergency_route = strtoupper($db->escapeSimple($emergency_route));
	$intracompany_route = strtoupper($db->escapeSimple($intracompany_route));
	$mohclass = $db->escapeSimple($mohclass);
	$seq = $db->escapeSimple($seq);
	$time_group_id = $time_group_id == ''? 'NULL':$db->escapeSimple($time_group_id);
	$dest = $db->escapeSimple($dest);
	$sql = "UPDATE `outbound_routes` SET
	`name`='$name', `outcid`='$outcid', `outcid_mode`='$outcid_mode', `password`='$password',
	`emergency_route`='$emergency_route', `intracompany_route`='$intracompany_route', `mohclass`='$mohclass',
	`time_group_id`=$time_group_id, `dest`='$dest' WHERE `route_id` = ".q($route_id);
	sql($sql);

	core_routing_updatepatterns($route_id, $patterns, true);
	core_routing_updatetrunks($route_id, $trunks, true);
	if ($seq != '') {
		core_routing_setrouteorder($route_id, $seq);
	}
}

// function core_routing_add($name,$patterns,$trunks,$method,$pass,$emergency="",$intracompany="",$mohsilence="",$routecid="",$routecid_mode="")
function core_routing_addbyid($name, $outcid, $outcid_mode, $password, $emergency_route, $intracompany_route, $mohclass, $time_group_id, $patterns, $trunks, $seq = 'new', $dest = '') {
	global $amp_conf;
	global $db;

	$name = $db->escapeSimple($name);
	$outcid = $db->escapeSimple($outcid);
	$outcid_mode = $db->escapeSimple($outcid_mode);
	$password = $db->escapeSimple($password);
	$emergency_route = strtoupper($db->escapeSimple($emergency_route));
	$intracompany_route = strtoupper($db->escapeSimple($intracompany_route));
	$mohclass = $db->escapeSimple($mohclass);
	$time_group_id = $time_group_id == ''? 'NULL':$db->escapeSimple($time_group_id);
	$dest = $db->escapeSimple($dest);
	$sql = "INSERT INTO `outbound_routes` (`name`, `outcid`, `outcid_mode`, `password`, `emergency_route`, `intracompany_route`, `mohclass`, `time_group_id`, `dest`)
	VALUES ('$name', '$outcid', '$outcid_mode', '$password', '$emergency_route', '$intracompany_route', '$mohclass', $time_group_id, '$dest')";
	sql($sql);

	// TODO: sqlite_last_insert_rowid() un-tested and php5 ???
	//
	if(method_exists($db,'insert_id')) {
		$route_id = $db->insert_id();
	} else {
		$route_id = $amp_conf["AMPDBENGINE"] == "sqlite3" ? sqlite_last_insert_rowid($db->connection) : mysql_insert_id($db->connection);
	}

	core_routing_updatepatterns($route_id, $patterns);
	core_routing_updatetrunks($route_id, $trunks);
	core_routing_setrouteorder($route_id, 'new');
	// this is lame, should change to do as a single call but for now this expects route_id to be in array for anything but new
	if ($seq != 'new') {
		core_routing_setrouteorder($route_id, $seq);
	}

	return ($route_id);
}

/* TODO: duplicate prepend_patterns is a problem as only one will win. We need to catch this and filter it out. We can silently trap it
by hashing without the prepend (since a blank prepend is similar to no prepend) at a minimum and decide if we want to catch
this and throw an error...
*/
function core_routing_updatepatterns($route_id, &$patterns, $delete = false) {
	global $db;

	$route_id =  $db->escapeSimple($route_id);
	$filter = '/[^0-9\*\#\+\-\.\[\]xXnNzZ]/';
	$insert_pattern = array();
	foreach ($patterns as $pattern) {
		$match_pattern_prefix = $db->escapeSimple(preg_replace($filter,'',strtoupper(trim($pattern['match_pattern_prefix']))));
		$match_pattern_pass = $db->escapeSimple(preg_replace($filter,'',strtoupper(trim($pattern['match_pattern_pass']))));
		$match_cid = $db->escapeSimple(preg_replace($filter,'',strtoupper(trim($pattern['match_cid']))));
		$prepend_digits = $db->escapeSimple(preg_replace($filter,'',strtoupper(trim($pattern['prepend_digits']))));

		if ($match_pattern_prefix.$match_pattern_pass.$match_cid == '') {
			continue;
		}

		$hash_index = md5($match_pattern_prefix.$match_pattern_pass.$match_cid);
		if (!isset($insert_pattern[$hash_index])) {
			$insert_pattern[$hash_index] = array($match_pattern_prefix, $match_pattern_pass, $match_cid, $prepend_digits);
		}
	}

	if ($delete) {
		sql('DELETE FROM `outbound_route_patterns` WHERE `route_id`='.q($route_id));
	}
	$compiled = $db->prepare('INSERT INTO `outbound_route_patterns` (`route_id`, `match_pattern_prefix`, `match_pattern_pass`, `match_cid`, `prepend_digits`) VALUES ('.$route_id.',?,?,?,?)');
	$result = $db->executeMultiple($compiled,$insert_pattern);
	if(DB::IsError($result)) {
		die_freepbx($result->getDebugInfo()."<br><br>".'error updating outbound_route_patterns');
	}
}

function core_routing_updatetrunks($route_id, &$trunks, $delete = false) {
	global $db;
	$dbh = \FreePBX::Database();
	$insert_trunk = array();
	$seq = 0;
	foreach ($trunks as $trunk) {
		$insert_trunk[] = array( $route_id,$trunk, $seq);
		$seq++;
	}
	if ($delete) {
		sql('DELETE FROM `outbound_route_trunks` WHERE `route_id`='.q($route_id));
	}
	$stmt = $dbh->prepare('INSERT INTO `outbound_route_trunks` (`route_id`, `trunk_id`, `seq`) VALUES (?,?,?)');
	$ret = array();
	foreach($insert_trunk as $t){
		$ret[] = $stmt->execute($t);
	}
	return $ret;
}

/* callback to Time Groups Module so it can display usage information
of specific groups
*/
function core_timegroups_usage($group_id) {

	$group_id = q($group_id);
	$results = sql("SELECT route_id, name FROM outbound_routes WHERE time_group_id = $group_id","getAll",DB_FETCHMODE_ASSOC);
	if (empty($results)) {
		return array();
	} else {
		foreach ($results as $result) {
			$usage_arr[] = array(
				"url_query" => "display=routing&extdisplay=".$result['route_id'],
				"description" => sprintf(_("Outbound Route: %s"),$result['name']),
			);
		}
		return $usage_arr;
	}
}

//-----------------------------------------------------------------------------------------------------------------------------------------
// The following APIs have all been removed and will result in crashes with traceback to obtain calling tree information

function core_routing_getroutenames() {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}
function core_routing_setroutepriority($routepriority, $reporoutedirection, $reporoutekey) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}
function core_routing_setroutepriorityvalue($key)
{
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}
function core_routing_add($name, $patterns, $trunks, $method, $pass, $emergency = "", $intracompany = "", $mohsilence = "", $routecid = "", $routecid_mode = "") {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}
function core_routing_edit($name, $patterns, $trunks, $pass, $emergency="", $intracompany = "", $mohsilence="", $routecid = "", $routecid_mode) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}
function core_routing_del($name) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}
function core_routing_trunk_del($trunknum) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}
function core_routing_rename($oldname, $newname) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}
function core_routing_getroutepatterns($route) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}
function core_routing_getroutetrunks($route) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}
function core_routing_getroutepassword($route) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}
function core_routing_getrouteemergency($route) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}
function core_routing_getrouteintracompany($route) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}
function core_routing_getroutemohsilence($route) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}
function core_routing_getroutecid($route) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}

/* end of outbound routes */

/**
 * Get Database AMP User, used in Administrators
 * @param string $username The username to get information about
 */
function core_getAmpUser($username) {
	global $db;

	$sql = "SELECT username, password_sha1, extension_low, extension_high, deptname, sections FROM ampusers WHERE username = '".$db->escapeSimple($username)."'";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
	   die_freepbx($sql."<br>\n".$results->getMessage());
	}

	if (count($results) > 0) {
		$user = array();
		$user["username"] = $results[0][0];
		$user["password_sha1"] = $results[0][1];
		$user["extension_low"] = $results[0][2];
		$user["extension_high"] = $results[0][3];
		$user["deptname"] = $results[0][4];
		$user["sections"] = explode(";",$results[0][5]);
		return $user;
	} else {

		return false;
	}
}

function core_indications_get($zone=false) {
	global $db;

	$sql = 'SELECT `name`, `iso`, `conf` FROM `indications_zonelist`';
	$orderby = ' ' . 'ORDER BY `name`';
	if ($zone === false) {
		return sql($sql.$orderby, 'getAll', DB_FETCHMODE_ASSOC);
	} else {
		$z = $db->escapeSimple($zone);
		$sql .= ' ' . "WHERE `iso` = '$z'";
		return sql($sql.$orderby, 'getRow', DB_FETCHMODE_ASSOC);
	}
}

function general_generate_indications() {
	global $db;
	global $amp_conf;

	$notify =& notifications::create($db);

	$country = $amp_conf['TONEZONE'];
	$filename = $amp_conf['ASTETCDIR'] . "/indications.conf";

	$zonelist = core_indications_get($country);
	$fd = fopen($filename, "w");

	if (empty($zonelist) || $fd === false) {
		$desc = sprintf(_("Failed to open %s for writing, aborting attempt to write the country indications. The file may be readonly or the permissions may be incorrect."), $filename);
		$notify->add_error('core','INDICATIONS',_("Failed to write indications.conf"), $desc);
		return;
	}
	$notify->delete('core', 'INDICATIONS');

	$indication_warning = ";--------------------------------------------------------------------------------;
; Do NOT edit this file as it is auto-generated by FreePBX. All modifications to ;
; this file must be done via the web gui.                                        ;
;--------------------------------------------------------------------------------;\n\n";
	fwrite($fd, $indication_warning);
	fwrite($fd, "[general]\ncountry=".$country."\n\n[".$country."]\n");
	fwrite($fd, "description = {$zonelist['name']}\n");
	fwrite($fd, "{$zonelist['conf']}\n\n");

	fclose($fd);
}
/* end page.routing.php functions */



// init registered 'your' config load and config process functions
function core_users_configpageinit($dispnum) {
	global $currentcomponent;
	global $amp_conf;

	if ( $dispnum == 'users' || $dispnum == 'extensions' ) {
		// Setup option list we need

		$currentcomponent->addoptlistitem('recording_options', 'force', _("Force"));
		$currentcomponent->addoptlistitem('recording_options', 'yes', _("Yes"));
		$currentcomponent->addoptlistitem('recording_options', 'dontcare', _("Don't Care"));
		$currentcomponent->addoptlistitem('recording_options', 'no', _("No"));
		$currentcomponent->addoptlistitem('recording_options', 'never', _("Never"));
		$currentcomponent->setoptlistopts('recording_options', 'sort', false);

		for ($i=0; $i <= 20; $i++) {
			$currentcomponent->addoptlistitem('recording_priority_options', "$i", "$i");
		}

		$currentcomponent->addoptlistitem('recording_ondemand_options', 'disabled', _("Disable"));
		$currentcomponent->addoptlistitem('recording_ondemand_options', 'enabled', _("Enable"));
		$currentcomponent->addoptlistitem('recording_ondemand_options', 'override', _("Override"));
		$currentcomponent->setoptlistopts('recording_ondemand_options', 'sort', false);

		$currentcomponent->addoptlistitem('callwaiting', 'enabled', _("Enable"));
		$currentcomponent->addoptlistitem('callwaiting', 'disabled', _("Disable"));
		$currentcomponent->setoptlistopts('callwaiting', 'sort', false);

		$currentcomponent->addoptlistitem('pinless', 'disabled', _("Disable"));
		$currentcomponent->addoptlistitem('pinless', 'enabled', _("Enable"));
		$currentcomponent->setoptlistopts('pinless', 'sort', false);

		$currentcomponent->addoptlistitem('call_screen', '0', _("Disable"));
		$currentcomponent->addoptlistitem('call_screen', 'nomemory', _("Screen Caller: No Memory"));
		$currentcomponent->addoptlistitem('call_screen', 'memory', _("Screen Caller: Memory"));
		$currentcomponent->setoptlistopts('call_screen', 'sort', false);

		$currentcomponent->addoptlistitem('ringtime', '0', _("Default"));
		$currentcomponent->addoptlistitem('cfringtime', '0', _("Default"));
		$currentcomponent->addoptlistitem('cfringtime', '-1', _("Always"));
		$currentcomponent->addoptlistitem('concurrency_limit', '0', _("No Limit"));
		for ($i=1; $i <= 120; $i++) {
			$currentcomponent->addoptlistitem('ringtime', "$i", "$i");
			$currentcomponent->addoptlistitem('cfringtime', "$i", "$i");
			$currentcomponent->addoptlistitem('concurrency_limit', "$i", "$i");
		}
		$currentcomponent->setoptlistopts('ringtime', 'sort', false);
		$currentcomponent->setoptlistopts('cfringtime', 'sort', false);
		$currentcomponent->setoptlistopts('concurrency_limit', 'sort', false);

		// Special CID handling to deal with Private, etc.
		//
		$js =
		'var mycid = thiscid.toLowerCase();
		if (isDialpattern(thiscid) || mycid.substring(0,4) == "priv" || mycid.substring(0,5) == "block" || mycid == "unknown" || mycid.substring(0,8) == "restrict" || mycid.substring(0,7) == "unavail" || mycid.substring(0,6) == "anonym" || mycid.substring(0,8) == "withheld") { return true } else { return false };
		';
		$currentcomponent->addjsfunc('isValidCID(thiscid)', $js);

		// Add the 'proces' functions
		$currentcomponent->addguifunc('core_users_configpageload');
		// Ensure users is called in middle order ($sortorder = 5), this is to allow
		// other modules to call stuff before / after the processing of users if needed
		// e.g. Voicemail module needs to create mailbox BEFORE the users as the mailbox
		// context is needed by the add users function
		$currentcomponent->addprocessfunc('core_users_configprocess', 5);
	}
}

// Used below in usort
function dev_grp($a, $b) {
	if ($a['devicetype'] == $b['devicetype']) {
		return ($a['id'] < $b['id']) ? -1 : 1;
	} else {
		return ($a['devicetype'] > $b['devicetype']) ? -1 : 1;
	}
}

function core_users_configpageload() {
	global $currentcomponent;
	global $amp_conf;

	// Ensure variables possibly extracted later exist
	$name = $outboundcid = $sipname = $cid_masquerade = $newdid_name = $newdid = $newdidcid = $call_screen = $pinless = null;

	// Init vars from $_REQUEST[]
	$display = isset($_REQUEST['display'])?$_REQUEST['display']:null;;
	$action = isset($_REQUEST['action'])?$_REQUEST['action']:null;
	$extdisplay = isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:null;
	$tech_hardware = isset($_REQUEST['tech_hardware'])?$_REQUEST['tech_hardware']:null;
	if(!checkRange($extdisplay)){
		return;
	}
	if ( $action == 'del' ) { // Deleted

		$currentcomponent->addguielem('_top', new gui_subheading('del', $extdisplay.' '._("deleted"), false));

	} elseif ( $display == 'extensions' && ($extdisplay == '' && $tech_hardware == '') ) { // Adding

		// do nothing as you want the Devices to handle this bit

	} else {

		$delURL = '?'.$_SERVER['QUERY_STRING'].'&action=del';

		if ( is_string($extdisplay) ) {

			if (!isset($GLOBALS['abort']) || $GLOBALS['abort'] !== true) {
				$extenInfo=\FreePBX::Core()->getUser($extdisplay);
				extract($extenInfo);
			}
			if (isset($deviceInfo) && is_array($deviceInfo)) {
				extract($deviceInfo);
			}

			if ( $display == 'extensions' ) {
				$currentcomponent->addguielem('_top', new gui_pageheading('title', _("Extension").": $extdisplay", false), 0);
				/*
				if (!isset($GLOBALS['abort']) || $GLOBALS['abort'] !== true) {
				$tlabel = sprintf(_("Delete Extension %s"),$extdisplay);
				$label = '<span><img width="16" height="16" border="0" title="'.$tlabel.'" alt="" src="images/user_delete.png"/>&nbsp;'.$tlabel.'</span>';
				$currentcomponent->addguielem('_top', new gui_link('del', $label, $delURL, true, false), 0);

				$usage_list = framework_display_destination_usage(core_getdest($extdisplay));
				if (!empty($usage_list)) {
				$currentcomponent->addguielem('_top', new gui_link_label('dests', $usage_list['text'], $usage_list['tooltip'], true), 0);
			}
		}
		*/
			} else {
				$currentcomponent->addguielem('_top', new gui_pageheading('title', _("User").": $extdisplay", false), 0);
				if (!isset($GLOBALS['abort']) || $GLOBALS['abort'] !== true) {
					/*
					$tlabel = sprintf(_("Delete User %s"),$extdisplay);
					$label = '<span><img width="16" height="16" border="0" title="'.$tlabel.'" alt="" src="images/user_delete.png"/>&nbsp;'.$tlabel.'</span>';
					$currentcomponent->addguielem('_top', new gui_link('del', $label, $delURL, true, false), 0);

					$usage_list = framework_display_destination_usage(core_getdest($extdisplay));
					if (!empty($usage_list)) {
						$currentcomponent->addguielem('_top', new gui_link_label('dests', $usage_list['text'], $usage_list['tooltip'], true), 0);
					}
					*/
				}
			}
		} elseif ( $display != 'extensions' ) {
			$currentcomponent->addguielem('_top', new gui_pageheading('title', _("Add User/Extension")), 0);
		}

		// Setup vars for use in the gui later on
		$fc_logon = featurecodes_getFeatureCode('core', 'userlogon');
		$fc_logoff = featurecodes_getFeatureCode('core', 'userlogoff');

		$msgInvalidExtNum = _("Please enter a valid extension number.");
		$msgInvalidCidNum = _("Please enter a valid CID Num Alias (must be a valid number).");
		$msgInvalidExtPwd = _("Please enter valid User Password using numbers only");
		$msgInvalidDispName = _("Please enter a valid Display Name");
		$msgInvalidOutboundCID = _("Please enter a valid Outbound CID");
		$msgInvalidPause = _("Please enter a valid pause time in seconds, using digits only");
		$msgInvalidDIDNum = _("You have entered a non-standard dialpattern for your DID. You can only enter standard dialpatterns. You must use the inbound routing form to enter non-standard patterns");
		$msgInvalidCIDNum = _("Please enter a valid CallerID Number or leave it blank for your Assigned DID/CID pair");

		// This is the actual gui stuff
		$currentcomponent->addguielem('_top', new gui_hidden('action', ((isset($extdisplay) && trim($extdisplay) !== '') ? 'edit' : 'add')));
		$currentcomponent->addguielem('_top', new gui_hidden('extdisplay', $extdisplay));

		if ( $display == 'extensions' ) {
			$section = ($extdisplay ? _("Edit Extension") : _("Add Extension"));
		} else {
			$section = ($extdisplay ? _("Edit User") : _("Add User"));
		}
		$category = "general";
		if ( trim($extdisplay) != '' ) {
			$currentcomponent->addguielem($section, new gui_hidden('extension', $extdisplay), 2, null, $category);
		} else {
			$currentcomponent->addguielem($section, new gui_textbox('extension', $extdisplay, _("User Extension"), _("The extension number to dial to reach this user."), '!isInteger()', $msgInvalidExtNum, false), 3, null, $category);
		}
		if ( $display != 'extensions' ) {
			$currentcomponent->addguielem($section, new gui_password('password', $password, _("User Password"), _("A user will enter this password when logging onto a device.").' '.$fc_logon.' '._("logs into a device.").' '.$fc_logoff.' '._("logs out of a device."), '!isInteger() && !isWhitespace()', $msgInvalidExtPwd, true), $category);
			// extra JS function check required for blank password warning -- call last in the onsubmit() function
			$currentcomponent->addjsfunc('onsubmit()', "\treturn checkBlankUserPwd();\n", 9);
		}
		if ($action == 'add') {
			$currentcomponent->addjsfunc('onsubmit()', "
			var exten = $('#extension').val();
			var ajax_result = true;
			$('#error').remove();
			$.ajax({
				type: 'POST',
				url: 'config.php',
				data: 'handler=api&function=framework_get_conflict_url_helper&args=' + exten,
				dataType: 'json',
				cache: false,
				async: false,
				success: function(data, textStatus, XMLHttpRequest) {
					if (data.length !== 0) {
						$('#title').after('<div id=\"error\"><h5>"._("Conflicting Extensions")."</h5>' + data + '</div>');
						ajax_result = false;
					}
				},
				error: function(data) {
					console.log('an error was recevied: ' + data);
					// TODO: Should we stop the submital and do something here?
				}
			});
			if (!ajax_result) {
				alert('". _("Extension number conflict, please choose another.") . "');
				$('#extension').focus();
				return false;
			}", 9);
		}
		$currentcomponent->addguielem($section, new gui_textbox('name', $name, _("Display Name"), _("The CallerID name for calls from this user will be set to this name. Only enter the name, NOT the number."),  '(typeof isCorrectLengthExtensions != "undefined") ? !isCorrectLengthExtensions() || !isUnicodeLetter || isWhitespace() : !isUnicodeLetter() || isWhitespace()', $msgInvalidDispName, false), 3, null, $category);
		$cid_masquerade = (trim($cid_masquerade) == $extdisplay)?"":$cid_masquerade;
		$currentcomponent->addguielem($section, new gui_textbox('cid_masquerade', $cid_masquerade, _("CID Num Alias"), _("The CID Number to use for internal calls, if different from the extension number. This is used to masquerade as a different user. A common example is a team of support people who would like their internal CallerID to display the general support number (a ringgroup or queue). There will be no effect on external calls."), '!isWhitespace() && !isInteger()', $msgInvalidCidNum, false), "advanced");
		$currentcomponent->addguielem($section, new gui_textbox('sipname', $sipname, _("SIP Alias"), _("If you want to support direct sip dialing of users internally or through anonymous sip calls, you can supply a friendly name that can be used in addition to the users extension to call them.")), "advanced");

		// If user mode, list devices associated with this user
		//
		if ($display == 'users' && trim($extdisplay != '')) {
			$section = _("User Devices");
			$device_list = core_devices_list('all','full');
			$device_list = is_array($device_list)?$device_list:array();
			usort($device_list,'dev_grp');

			$link_count = 0;
			foreach ($device_list as $device_item) {
				if ($device_item['user'] == $extdisplay) {
					$editURL = '?display=devices&skip=0&extdisplay='.$device_item['id'];
					$device_icon = ($device_item['devicetype'] == 'fixed') ? 'images/telephone_key.png' : 'images/telephone_edit.png';
					$device_label  = '&nbsp;';
					$device_label .=  _("Edit:");
					$device_label .= '&nbsp;'.$device_item['id'].'&nbsp;'.$device_item['description'];

					$device_label = '<span>
					<img width="16" height="16" border="0" title="Edit Device" alt="Edit Device" src="'.$device_icon.'"/>'.$device_label.
					'</span> ';

					$currentcomponent->addguielem($section, new gui_link('dev'.$link_count++, $device_label, $editURL, true, false), 2);
				}
			}
		}

		$currentcomponent->addguielem($section, new gui_textbox('outboundcid', $outboundcid, _("Outbound CID"), _("Overrides the CallerID when dialing out a trunk. Any setting here will override the common outbound CallerID set in the Trunks admin.<br><br>Format: <b>\"caller name\" &lt;#######&gt;</b><br><br>Leave this field blank to disable the outbound CallerID feature for this user."), '!isCallerID()', $msgInvalidOutboundCID, true),3, null, "general");

		$section = _("Extension Options");
		$category = "advanced";
		$ringtimer = (isset($ringtimer) ? $ringtimer : '0');

		$dialopts = isset($dialopts) ? $dialopts : false;
		$disable_dialopts = $dialopts === false;
		$currentcomponent->addguielem($section, new gui_textbox_check('dialopts', $dialopts, _("Asterisk Dial Options"), _("Cryptic Asterisk Dial Options, check to customize for this extension or un-check to use system defaults set in Advanced Options. These will not apply to trunk options which are configured with the trunk."), '', '', true, 0, $disable_dialopts, '<small>' . _("Override") . '</small>', $amp_conf['DIAL_OPTIONS'], true), $category);

		$currentcomponent->addguielem($section, new gui_selectbox('ringtimer', $currentcomponent->getoptlist('ringtime'), $ringtimer, _("Ring Time"), _("Number of seconds to ring prior to going to voicemail. Default will use the value set in Advanced Settings. If no voicemail is configured this will be ignored."), false), $category);

		if (!isset($cfringtimer)) {
			if ($amp_conf['CFRINGTIMERDEFAULT'] < 0 || ctype_digit($amp_conf['CFRINGTIMERDEFAULT'])) {
				$cfringtimer = $amp_conf['CFRINGTIMERDEFAULT'] < 0 ? -1 : ($amp_conf['CFRINGTIMERDEFAULT'] > 120 ? 120 : $amp_conf['CFRINGTIMERDEFAULT']);
			} else {
				$cfringtimer = 0;
			}
		}
		$currentcomponent->addguielem($section, new gui_selectbox('cfringtimer', $currentcomponent->getoptlist('cfringtime'), $cfringtimer, _("Call Forward Ring Time"), _("Number of seconds to ring during a Call Forward, Call Forward Busy or Call Forward Unavailable call prior to continuing to voicemail or specified destination. Setting to Always will not return, it will just continue to ring. Default will use the current Ring Time. If voicemail is disabled and their is not destination specified, it will be forced into Always mode"), false), $category);
		if (!isset($callwaiting)) {
			if ($amp_conf['ENABLECW']) {
				$callwaiting = 'enabled';
			} else {
				$callwaiting = 'disabled';
			}
		}

		$concurrency_limit = isset($concurrency_limit) ? $concurrency_limit : $amp_conf['CONCURRENCYLIMITDEFAULT'];
		$currentcomponent->addguielem($section, new gui_selectbox('concurrency_limit', $currentcomponent->getoptlist('concurrency_limit'), $concurrency_limit, _("Outbound Concurrency Limit"), _("Maximum number of outbound simultaneous calls that an extension can make. This is also very useful as a Security Protection against a system that has been compromised. It will limit the number of simultaneous calls that can be made on the compromised extension."), false), $category);

		$currentcomponent->addguielem($section, new gui_radio('callwaiting', $currentcomponent->getoptlist('callwaiting'), $callwaiting, _("Call Waiting"), _("Set the initial/current Call Waiting state for this user's extension"), false,'','',false),$category);
		$currentcomponent->addguielem($section, new gui_selectbox('call_screen', $currentcomponent->getoptlist('call_screen'), $call_screen, _("Call Screening"),_("Call Screening requires external callers to say their name, which will be played back to the user and allow the user to accept or reject the call.  Screening with memory only verifies a caller for their CallerID once. Screening without memory always requires a caller to say their name. Either mode will always announce the caller based on the last introduction saved with that CallerID. If any user on the system uses the memory option, when that user is called, the caller will be required to re-introduce themselves and all users on the system will have that new introduction associated with the caller's CallerID."), false), $category);
		$pinless = isset($pinless) ? $pinless : "disabled";
		$currentcomponent->addguielem($section, new gui_radio('pinless', $currentcomponent->getoptlist('pinless'), $pinless, _("Pinless Dialing"), _("Enabling Pinless Dialing will allow this extension to bypass any pin codes normally required on outbound calls"), false, '','',false), $category);

		$section = _("Assigned DID/CID");
		$category = "advanced";
		$currentcomponent->addguielem($section, new gui_textbox('newdid_name', $newdid_name, _("DID Description"), _("A description for this DID, such as \"Fax\"")), 4, null, $category);
		$currentcomponent->addguielem($section, new gui_textbox('newdid', $newdid, _("Add Inbound DID"), _("A direct DID that is associated with this extension. The DID should be in the same format as provided by the provider (e.g. full number, 4 digits for 10x4, etc).<br><br>Format should be: <b>XXXXXXXXXX</b><br><br>.An optional CID can also be associated with this DID by setting the next box"),'!isDialpattern()',$msgInvalidDIDNum,true), 4, null, $category);
		$currentcomponent->addguielem($section, new gui_textbox('newdidcid', $newdidcid, _("Add Inbound CID"), _("Add a CID for more specific DID + CID routing. A DID must be specified in the above Add DID box. In addition to standard dial sequences, you can also put Private, Blocked, Unknown, Restricted, Anonymous, Withheld and Unavailable in order to catch these special cases if the Telco transmits them."),"!frm_${display}_isValidCID()",$msgInvalidCIDNum,true), 4, null, $category);

		$dids = \FreePBX::Core()->getAllDIDs('extension');
		$did_count = 0;
		foreach ($dids as $did) {
			$did_dest = preg_split('/,/',$did['destination']);
			if (isset($did_dest[1]) && $did_dest[1] === $extdisplay) {

				$did_title = ($did['description'] != '') ? $did['description'] : _("DID / CID");

				$addURL = '?display=did&view=form&extdisplay='.urlencode($did['extension'].'/'.$did['cidnum']);
				$did_icon = 'images/email_edit.png';
				$did_label = trim($did['extension']) == '' ? ' '._("Any DID") : ' '.$did['extension'];
				if (trim($did['cidnum']) != '') {
					$did_label .= ' / '.$did['cidnum'];
				}
				if (trim($did['description']) != '') {
					$did_label .= ' ('.$did['description'].')';
				}

				$did_label = '&nbsp;<span>
				<img width="16" height="16" border="0" title="'.$did_title.'" alt="" src="'.$did_icon.'"/>'.$did_label.
				'</span> ';

				$currentcomponent->addguielem($section, new gui_link('did_'.$did_count++, $did_label, $addURL, true, false), 4, null, $category);
			}
		}

		$section = _("Recording Options");

		$recording_in_external = isset($recording_in_external) ? $recording_in_external : 'dontcare';
		$recording_out_external = isset($recording_out_external) ? $recording_out_external : 'dontcare';
		$recording_in_internal = isset($recording_in_internal) ? $recording_in_internal : 'dontcare';
		$recording_out_internal = isset($recording_out_internal) ? $recording_out_internal : 'dontcare';
		$recording_ondemand = isset($recording_ondemand) ? $recording_ondemand : 'disabled';
		$recording_priority = isset($recording_priority) ? $recording_priority : '10';
		$currentcomponent->addguielem($section, new gui_radio('recording_in_external', $currentcomponent->getoptlist('recording_options'), $recording_in_external, _('Inbound External Calls'), _("Recording of inbound calls from external sources.")),$category);
		$currentcomponent->addguielem($section, new gui_radio('recording_out_external', $currentcomponent->getoptlist('recording_options'), $recording_out_external, _('Outbound External Calls'), _("Recording of outbound calls to external sources.")),$category);
		$currentcomponent->addguielem($section, new gui_radio('recording_in_internal', $currentcomponent->getoptlist('recording_options'), $recording_in_internal, _('Inbound Internal Calls'), _("Recording of calls received from other extensions on the system.")),$category);
		$currentcomponent->addguielem($section, new gui_radio('recording_out_internal', $currentcomponent->getoptlist('recording_options'), $recording_out_internal, _('Outbound Internal Calls'), _("Recording of calls made to other extensions on the system.")),$category);
		$currentcomponent->addguielem($section, new gui_radio('recording_ondemand', $currentcomponent->getoptlist('recording_ondemand_options'), $recording_ondemand, _('On Demand Recording'), _("Enable or disable the ability to do on demand (one-touch) recording. The overall calling policy rules still apply and if calls are already being recorded by 'Force' or 'Never', the can not be paused unless 'Override' is selected..")),$category);
		$currentcomponent->addguielem($section, new gui_selectbox('recording_priority', $currentcomponent->getoptlist('recording_priority_options'), $recording_priority, _("Record Priority Policy"), _("Call recording policy priority relative to other extensions when there is a conflict between an extension wanting recording and the other not wanting it. The higher of the two determines the policy, on a tie the global policy (caller or callee) determines the policy."), false),$category);

		$section = _("Optional Destinations");
		$noanswer_dest = isset($noanswer_dest) ? $noanswer_dest : '';
		$busy_dest = isset($busy_dest) ? $busy_dest : '';
		$chanunavail_dest = isset($chanunavail_dest) ? $chanunavail_dest : '';

		$noanswer_cid = isset($noanswer_cid) ? $noanswer_cid : '';
		$busy_cid = isset($busy_cid) ? $busy_cid : '';
		$chanunavail_cid = isset($chanunavail_cid) ? $chanunavail_cid : '';

		if ($amp_conf['CWINUSEBUSY']) {
			$helptext = _('Optional destination call is routed to when the call is not answered on an otherwise idle phone. If the phone is in use and the call is simply ignored, then the busy destination will be used.');
		} else {
			$helptext = _('Optional destination call is routed to when the call is not answered.');
		}
		$nodest_msg = _('Unavail Voicemail if Enabled');
		$currentcomponent->addguielem($section, new gui_drawselects('noanswer_dest', '0', $noanswer_dest, _('No Answer'), $helptext, false, '', $nodest_msg),5,9,$category);
		$currentcomponent->addguielem($section, new gui_textbox('noanswer_cid', $noanswer_cid, '&nbsp;&nbsp;'._("CID Prefix"), _("Optional CID Prefix to add before sending to this no answer destination.")),5,9,$category);

		if ($amp_conf['CWINUSEBUSY']) {
			$helptext = _('Optional destination the call is routed to when the phone is busy or the call is rejected by the user. This destination is also used on an unanswered call if the phone is in use and the user chooses not to pickup the second call.');
		} else {
			$helptext = _('Optional destination the call is routed to when the phone is busy or the call is rejected by the user.');
		}
		$nodest_msg = _('Busy Voicemail if Enabled');
		$currentcomponent->addguielem($section, new gui_drawselects('busy_dest', '1', $busy_dest, _('Busy'), $helptext, false, '', $nodest_msg),5,9,$category);
		$currentcomponent->addguielem($section, new gui_textbox('busy_cid', $busy_cid, '&nbsp;&nbsp;'._("CID Prefix"), _("Optional CID Prefix to add before sending to this busy destination.")),5,9,$category);

		$helptext = _('Optional destination the call is routed to when the phone is offline, such as a softphone currently off or a phone unplugged.');
		$nodest_msg = _('Unavail Voicemail if Enabled');
		$currentcomponent->addguielem($section, new gui_drawselects('chanunavail_dest', '2', $chanunavail_dest, _('Not Reachable'), $helptext, false, '', $nodest_msg),5,9,$category);
		$currentcomponent->addguielem($section, new gui_textbox('chanunavail_cid', $chanunavail_cid, '&nbsp;&nbsp;'._("CID Prefix"), _("Optional CID Prefix to add before sending to this not reachable destination.")),5,9,$category);
	}
}

function core_users_configprocess() {

	//create vars from the request
	extract($_REQUEST);

	//make sure we can connect to Asterisk Manager
	if (!checkAstMan()) {
		return false;
	}

	//check if the extension is within range for this user
	if (isset($extension) && !checkRange($extension)){
		echo "<script>javascript:alert('". _("Warning! Extension")." ".$extension." "._("is not allowed for your account").".');</script>";
		$GLOBALS['abort'] = true;
	} else {
		//if submitting form, update database
		if (!isset($action)) $action = null;
		switch ($action) {
			case "add":
			if (core_users_add($_REQUEST)) {
				// TODO: Check this if it's the same in device and user mode, and in fact we can't support this in that
				//       mode at least without fixing the complexities of adding the devices which gets ugly!
				//
				$this_dest = core_getdest($_REQUEST['extension']);
				fwmsg::set_dest($this_dest[0]);
				needreload();
				//redirect_standard_continue();
			} else {
				// really bad hack - but if core_users_add fails, want to stop core_devices_add
				// Comment, this does not help everywhere. Other hooks functions can hook before
				// this like voicemail!
				//
				$GLOBALS['abort'] = true;
			}
			break;
			case "del":
			\FreePBX::Core()->delUser($extdisplay);
			core_users_cleanastdb($extdisplay);
			if (function_exists('findmefollow_del')) {
				findmefollow_del($extdisplay);
			}
			needreload();
			//redirect_standard_continue();
			break;
			case "edit":
			if (core_users_edit($extdisplay,$_REQUEST)) {
				needreload();
				//redirect_standard_continue('extdisplay');
			} else {
				// really bad hack - but if core_users_edit fails, want to stop core_devices_edit
				$GLOBALS['abort'] = true;
			}
			break;
		}
	}
	return true;
}


function core_devices_configpageinit($dispnum) {
	global $currentcomponent, $amp_conf;
	if ( $dispnum == 'devices' || $dispnum == 'extensions' ) {
		// Option lists used by the gui
		$currentcomponent->addoptlistitem('devicetypelist', 'fixed', _("Fixed"));
		$currentcomponent->addoptlistitem('devicetypelist', 'adhoc', _("Adhoc"));
		$currentcomponent->setoptlistopts('devicetypelist', 'sort', false);

		$currentcomponent->addoptlistitem('deviceuserlist', 'none', _("none"));
		$users = core_users_list();
		if (isset($users)) {
			foreach ($users as $auser) {
				$currentcomponent->addoptlistitem('deviceuserlist', $auser[0], $auser[0] . " (" . $auser[1] . ")");
			}
		}
		$currentcomponent->setoptlistopts('deviceuserlist', 'sort', false);

		// Add the 'process' functions
		$currentcomponent->addguifunc('core_devices_configpageload');
		$currentcomponent->addprocessfunc('core_devices_configprocess');
	}
}

function core_devices_configpageload() {
	global $currentcomponent;
	global $amp_conf;

	$tech_hardware = isset($_REQUEST['tech_hardware'])?$_REQUEST['tech_hardware']:null;
	if ($tech_hardware == 'virtual') {
		$currentcomponent->addguielem('_top', new gui_hidden('tech', "virtual"));
		$currentcomponent->addguielem('_top', new gui_pageheading('title', _("Add Virtual Extension")), 0);
		return true;
	}

	// Init vars from $_REQUEST[]
	$display = isset($_REQUEST['display'])?$_REQUEST['display']:null;;
	$action = isset($_REQUEST['action'])?$_REQUEST['action']:null;
	$extdisplay = isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:null;

	if ( $action == 'del' ) { // Deleted

		if ( $display != 'extensions' )
		$currentcomponent->addguielem('_top', new gui_subheading('del', $extdisplay.' '._("deleted"), false));

	} elseif ( $extdisplay == '' && $tech_hardware == '' ) { // Adding
	} else {

		$deviceInfo = array();
		if ( $extdisplay ) { // Editing

			$deviceInfo = core_devices_get($extdisplay);

			if ( $display != 'extensions' ) {
				$currentcomponent->addguielem('_top', new gui_pageheading('title', _("Device").": $extdisplay", false), 0);

				$delURL = '?'.$_SERVER['QUERY_STRING'].'&action=del';
				$tlabel = sprintf(_("Delete Device %s"),$extdisplay);
				$label = '<span><img width="16" height="16" border="0" title="'.$tlabel.'" alt="" src="images/telephone_delete.png"/>&nbsp;'.$tlabel.'</span>';
				$currentcomponent->addguielem('_top', new gui_link('del', $label, $delURL, true, false), 0);

				if ($deviceInfo['device_user'] != 'none') {
					$editURL = '?display=users&skip=0&extdisplay='.urlencode($deviceInfo['user']);
					$tlabel =  $deviceInfo['devicetype'] == 'adhoc' ? sprintf(_("Edit Default User: %s"),$deviceInfo['user']) : sprintf(_("Edit Fixed User: %s"),$deviceInfo['user']);
					$label = '<span><img width="16" height="16" border="0" title="'.$tlabel.'" alt="" src="images/user_edit.png"/>&nbsp;'.$tlabel.'</span>';
					$currentcomponent->addguielem('_top', new gui_link('edit_user', $label, $editURL, true, false), 0);
				}
			}
		} else {


			$tmparr = explode('_', $tech_hardware);
			$deviceInfo['tech'] = $tmparr[0];
			$deviceInfo['hardware'] = $tmparr[1];
			unset($tmparr);

			if ( $display != 'extensions' ) {
				$currentcomponent->addguielem('_top', new gui_pageheading('title', sprintf(_("Add %s Device"), strtoupper($deviceInfo['tech'])) ), 0);
			} else {
				$currentcomponent->addguielem('_top', new gui_pageheading('title', sprintf(_("Add %s Extension"), strtoupper($deviceInfo['tech'])) ), 0);
			}
		}

		// Ensure they exist before the extract
		$devinfo_description = $devinfo_emergency_cid = null;
		$devinfo_devicetype = $devinfo_user = $devinfo_hardware = null;
		$devinfo_tech = null;
		if ( is_array($deviceInfo) ) {
			extract($deviceInfo, EXTR_PREFIX_ALL, 'devinfo');
		}

		// Setup vars for use in the gui later on
		$fc_logon = featurecodes_getFeatureCode('core', 'userlogon');
		$fc_logoff = featurecodes_getFeatureCode('core', 'userlogoff');

		$msgInvalidDevID = _("Please enter a device id.");
		$msgInvalidDevDesc = _("Please enter a valid Description for this device");
		$msgInvalidEmergCID = _("Please enter a valid Emergency CID");
		$msgInvalidExtNum = _("Please enter a valid extension number.");

		// Actual gui
		$currentcomponent->addguielem('_top', new gui_hidden('action', ((isset($extdisplay) && trim($extdisplay) !== '') ? 'edit' : 'add')));
		$currentcomponent->addguielem('_top', new gui_hidden('extdisplay', $extdisplay));

		if ( $display != 'extensions' ) {
			$section = ($extdisplay ? _("Edit User") : _("Add User"));
			if ( $extdisplay ) { // Editing
				$currentcomponent->addguielem($section, new gui_hidden('deviceid', $extdisplay),"general");
			} else { // Adding
				$currentcomponent->addguielem($section, new gui_textbox('deviceid', $extdisplay, _("Device ID"), _("Give your device a unique integer ID.  The device will use this ID to authenticate to the system."), '!isInteger()', $msgInvalidDevID, false),"general");
			}
			$currentcomponent->addguielem($section, new gui_textbox('description', $devinfo_description, _("Description"), _("The CallerID name for this device will be set to this description until it is logged into."), '!isUnicodeLetter() || isWhitespace()', $msgInvalidDevDesc, false),"general");
			$currentcomponent->addguielem($section, new gui_selectbox('devicetype', $currentcomponent->getoptlist('devicetypelist'), $devinfo_devicetype, _("Device Type"), _("Devices can be fixed or adhoc. Fixed devices are always associated to the same extension/user. Adhoc devices can be logged into and logged out of by users.").' '.$fc_logon.' '._("logs into a device.").' '.$fc_logoff.' '._("logs out of a device."), false),"general");
			$currentcomponent->addguielem($section, new gui_selectbox('deviceuser', $currentcomponent->getoptlist('deviceuserlist'), $devinfo_user, _("Default User"), _("Fixed devices will always mapped to this user.  Adhoc devices will be mapped to this user by default.<br><br>If selecting 'New User', a new User Extension of the same Device ID will be set as the Default User."), false),"general");
			$currentcomponent->addguielem($section, new gui_textbox('emergency_cid', $devinfo_emergency_cid, _("Emergency CID"), _("This CallerID will always be set when dialing out an Outbound Route flagged as Emergency.  The Emergency CID overrides all other CallerID settings."), '!isCallerID()', $msgInvalidEmergCID),"advanced");
		} else {
			$section = _("Extension Options");
			$currentcomponent->addguielem($section, new gui_textbox('emergency_cid', $devinfo_emergency_cid, _("Emergency CID"), _("This CallerID will always be set when dialing out an Outbound Route flagged as Emergency.  The Emergency CID overrides all other CallerID settings."), '!isCallerID()', $msgInvalidEmergCID),"advanced");
		}
		$currentcomponent->addguielem('_top', new gui_hidden('tech', $devinfo_tech));
		$currentcomponent->addguielem('_top', new gui_hidden('hardware', $devinfo_hardware));

		if ($devinfo_tech && $devinfo_tech != "virtual") {
			$section = _("Device Options");

			$msgInvalidChannel = _("Please enter the channel for this device");
			$msgConfirmSecret = _("You have not entered a Secret for this device, although this is possible it is generally bad practice to not assign a Secret to a device. Are you sure you want to leave the Secret empty?");
			$msgInvalidSecret = _("Please enter a Secret for this device");

			$secret_validation = '(isEmpty() && !confirm("'.$msgConfirmSecret.'"))';
			if ($amp_conf['DEVICE_STRONG_SECRETS']) {
				$secret_validation .= ' || (!isEmpty() && weakSecret())';
			}

			if ( $display == 'extensions' ) {
				$section = ($extdisplay ? _("Edit Extension") : _("Add Extension"));
			} else {
				$section = ($extdisplay ? _("Edit User") : _("Add User"));
			}

			$drivers = FreePBX::Core()->getAllDrivers();
			if(isset($drivers[$devinfo_tech])) {
				$devopts = $drivers[$devinfo_tech]->getDeviceDisplay($display, $deviceInfo, $currentcomponent, $section);
			} else {
				$devopts = array();
			}

			if (is_array($devopts)) {
				foreach ($devopts as $devopt => $devoptarr) {
					$devopname = 'devinfo_'.$devopt;
					$devoptcurrent = isset($$devopname) ? $$devopname : $devoptarr['value'];
					$devoptjs = isset($devoptarr['jsvalidation']) ? $devoptarr['jsvalidation'] : '';
					$devoptfailmsg = isset($devoptarr['failvalidationmsg']) ? $devoptarr['failvalidationmsg'] : '';
					$devdisable = isset($devoptarr['disable']) ? $devoptarr['disable'] : false;
					$devonchange = isset($devoptarr['onchange']) ? $devoptarr['onchange'] : '';
					$prompttext = isset($devoptarr['prompttext']) ? $devoptarr['prompttext'] : $devopt;
					$hidden =  isset($devoptarr['hidden']) ? $devoptarr['hidden'] : false;
					$type = isset($devoptarr['type']) ? $devoptarr['type'] : (isset($devoptarr['select']) ? 'select' : 'text');
					$text = isset($devoptarr['text']) ? $devoptarr['text'] : '';
					$category = isset($devoptarr['category']) ? $devoptarr['category'] : 'advanced';
					$sec = isset($devoptarr['section']) ? $devoptarr['section'] : $section;
					$class = isset($devoptarr['class']) ? $devoptarr['class'] : '';
					$autocomplete = false;

					// We compare the existing secret against what might be in the put to detect changes when validating
					if ($devopt == "secret") {
						$currentcomponent->addguielem($sec, new gui_hidden($devopname . "_origional", $devoptcurrent), 4, null, $category);
						if ($devoptcurrent == '' && empty($extdisplay)) {
							$devoptcurrent = \FreePBX::Core()->generateSecret();
						}
					}

					if (!$hidden) { // editing to show advanced as well
						// Added optional selectbox to enable the unsupported misdn module
						$tooltip = isset($devoptarr['tt']) ? $devoptarr['tt'] : '';
						if ($type == 'select') {
							$currentcomponent->addguielem($sec, new gui_selectbox($devopname, $devoptarr['select'], $devoptcurrent, $prompttext, $tooltip, false, $devonchange, $devdisable, $class), 4, null, $category);
						} elseif($type == 'text') {
							$currentcomponent->addguielem($sec, new gui_textbox($devopname, $devoptcurrent, $prompttext, $tooltip, $devoptjs, $devoptfailmsg, true, 0, $devdisable, false, $class, $autocomplete), 4, null, $category);
						} elseif($type == 'button') {
							$currentcomponent->addguielem($sec, new gui_button($devopname, $devoptcurrent, $prompttext, $tooltip, $text, $devoptjs, $devdisable, $class), 4, null, $category);
						} elseif($type == 'radio') {
							$currentcomponent->addguielem($sec, new gui_radio($devopname, $devoptarr['select'], $devoptcurrent, $prompttext, $tooltip, false, $devonchange, $devdisable, $class, false), 4, null, $category);
						}
					} else { // add so only basic
						$currentcomponent->addguielem($sec, new gui_hidden($devopname, $devoptcurrent), 4, null, $category);
					}
				}
			}
		}
	}
}

function core_devices_configprocess() {
	if ( !class_exists('agi_asteriskmanager') )
	include 'common/php-asmanager.php';

	//make sure we can connect to Asterisk Manager
	if (!checkAstMan()) {
		return false;
	}

	//create vars from the request
	$tech = $action = null;
	extract($_REQUEST);

	if ($tech == "virtual" || $action == "edit" && $tech == '') {
		return true;
	}
	$extension = isset($extension)?$extension:null;
	$deviceid = isset($deviceid)?$deviceid:null;
	$name = isset($name)?$name:null;
	$action = isset($action)?$action:null;

	// fixed users only in extensions mode
	if ( $display == 'extensions' ) {
		$devicetype = 'fixed';
		$deviceid = $deviceuser = $extension;
		$description = $name;
	}

	//if submitting form, update database
	switch ($action) {
		case "add":
		// really bad hack - but if core_users_add fails, want to stop core_devices_add

		if (!isset($GLOBALS['abort']) || $GLOBALS['abort'] !== true || !$_SESSION["AMP_user"]->checkSection('999')) {
			if (core_devices_add($deviceid,$tech,$devinfo_dial,$devicetype,$deviceuser,$description,$emergency_cid)) {
				needreload();
				if ($deviceuser == 'new') {
					//redirect_standard_continue();
				}
			}
		} else {
			// This is a bit messy, because by this time, other modules may have added the device but this tries to block
			// the user who does not have add permission from adding a new extension.
			//
			$GLOBALS['abort'] = true;
		}
		break;
		case "del":
		core_devices_del($extdisplay);
		//$_REQUEST['exdisplay'] = isset($_REQUEST['exdisplay'])?NULL:'';
		//$_REQUEST['action'] = isset($_REQUEST['action'])?NULL:'';
		needreload();
	  redirect_standard_continue();
		break;
		case "edit":  //just delete and re-add
		// really bad hack - but if core_users_edit fails, want to stop core_devices_edit
		if (!isset($GLOBALS['abort']) || $GLOBALS['abort'] !== true) {
			//delete then re add, insanity.
			core_devices_del($extdisplay,true);
			//PJSIP <--> CHAN_SIP Switcher, not the best but better than it was before and lets us continue forward into PHP 5.5
			if(isset($_REQUEST['changesipdriver']) && !empty($_REQUEST['devinfo_sipdriver']) && ($tech == 'pjsip' || $tech == 'sip')) {
				$tech = ($_REQUEST['devinfo_sipdriver'] == 'chan_sip') ? 'sip' : 'pjsip';
				$rtech = ($_REQUEST['devinfo_sipdriver'] == 'chan_sip') ? 'pjsip' : 'sip';
				$devinfo_dial = preg_replace('/^'.$rtech.'\/'.$deviceid.'$/i',strtoupper($tech).'/'.$deviceid,$devinfo_dial);
				$flag = 2;
				$fields = FreePBX::Core()->convertRequest2Array($deviceid,$tech,$flag);
				$settings = array(
					"dial" => array("value" => $devinfo_dial, "flag" => isset($fields['dial']['flag']) ? $fields['dial']['flag'] : $flag++),
					"devicetype" => array("value" => $devicetype, "flag" => isset($fields['devicetype']['flag']) ? $fields['devicetype']['flag'] : $flag++),
					"user" => array("value" => $deviceuser, "flag" => isset($fields['deviceuser']['flag']) ? $fields['deviceuser']['flag'] : $flag++),
					"description" => array("value" => $description, "flag" => isset($fields['description']['flag']) ? $fields['description']['flag'] : $flag++),
					"emergency_cid" => array("value" => $emergency_cid, "flag" => isset($fields['emergency_cid']['flag']) ? $fields['emergency_cid']['flag'] : $flag++)
				);
				$settings = array_merge($fields,$settings);
				return FreePBX::Core()->addDevice($deviceid,$tech,$settings,true);
			} else {
				core_devices_add($deviceid,$tech,$devinfo_dial,$devicetype,$deviceuser,$description,$emergency_cid,true);
			}

			needreload();
			//redirect_standard_continue('extdisplay');
		}
		break;
		case "resetall":  //form a url with this option to nuke the AMPUSER & DEVICE trees and start over.
		core_users2astdb();
		core_devices2astdb();
		break;
	}
	return true;
}

function _core_backtrace() {
	$trace = debug_backtrace();
	$function = $trace[1]['function'];
	$line = $trace[1]['line'];
	$file = $trace[1]['file'];
	freepbx_log(FPBX_LOG_WARNING,'Depreciated Function '.$function.' detected in '.$file.' on line '.$line);
}

function core_module_repo_parameters_callback($opts) {
	$final = array();
	if(\FreePBX::Config()->get('BROWSER_STATS')) {
		$final['udmode'] = \FreePBX::Config()->get('AMPEXTENSIONS');
	}
	return $final;
}

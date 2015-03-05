<?php
// vim: set ai ts=4 sw=4 ft=php:
namespace FreePBX\modules;
class Core extends \FreePBX_Helpers implements \BMO  {

	private $drivers = array();

	public function __construct($freepbx = null) {
		parent::__construct($freepbx);
		//other options
		$this->database = $freepbx->Database;
		$this->config = $freepbx->Config;
		$this->freepbx = $freepbx;

		//load drivers
		$this->loadDrivers();
	}

	public function ajaxRequest($req, &$setting) {
		$setting['authenticate'] = false;
		$setting['allowremote'] = false;
		switch($req) {
			case "quickcreate":
				return true;
			break;
		}
		return false;
	}

	public function ajaxHandler() {
		switch($_REQUEST['command']) {
			case "quickcreate":
				$settings = $this->generateDefaultDeviceSettings($_POST['tech'],$_POST['extension'],$_POST['name']);
				$this->addDevice($_POST['extension'],$_POST['tech'],$settings);
				$settings = $this->generateDefaultUserSettings($_POST['extension'],$_POST['name']);
				$this->addUser($_POST['extension'], $settings);
				return array("status" => true);
			break;
		}
	}

	public function getAllDrivers() {
		return $this->drivers;
	}

	public function loadDrivers() {
		include(__DIR__."/functions.inc/Driver.class.php");
		$driverNamespace = "\\FreePBX\\Modules\\Core\\Drivers";
		foreach(glob(__DIR__."/functions.inc/drivers/*.class.php") as $driver) {
			if(preg_match("/\/([a-z1-9]*)\.class\.php$/i",$driver,$matches)) {
				$name = $matches[1];
				$class = $driverNamespace . "\\" . $name;
				if(!class_exists($class)) {
					include($driver);
				}
				if(class_exists($class)) {
					$this->drivers[strtolower($name)] = new $class($this->freepbx);
				} else {
					throw new \Exception("Invalid Class inside the drivers folder");
				}
			}
		}
	}

	public function getAllDriversInfo() {
		$final = array();

		foreach($this->drivers as $driver) {
			$info = $driver->getInfo();
			if($info === false) {
				continue;
			}
			$rn = $info['rawName'];
			$final[$rn] = $info;
		}
		return $final;
	}

	public function getQuickCreateDisplay() {
		$devs = $this->getAllUsersByDeviceType();
		$dev = end($devs);
		$startExt = $dev['extension'] + 1;

		$pages = array();
		$pages[0][] = array(
			'html' => load_view(__DIR__.'/views/quickCreate.php',array('startExt' => $startExt)),
			'validate' => 'if($("#extension").val().trim() == "") {alert("'._("Extension can not be blank!").'");jumpPage(1,$("#quickCreate"));return false}if(typeof extmap[$("#extension").val().trim()] !== "undefined") {alert("'._("Extension already in use!").'");jumpPage(1,$("#quickCreate"));return false}if($("#name").val().trim() == "") {alert("'._("Display Name can not be blank!").'");jumpPage(1,$("#quickCreate"));return false}if(!isEmail($("#email").val())) {alert("'._("Email must be valid!").'");jumpPage(1,$("#quickCreate"));return false}'
		);
		$modules = $this->freepbx->hooks->processHooks();
		foreach($modules as $module) {
			foreach($module as $page => $datas) {
				foreach($datas as $html) {
					$pages[$page][] = $html;
				}
			}
		}
		return $pages;
	}

	public function showQCDisplay() {

	}

	public function processQuickCreate() {

	}

	public function processQC() {

	}

	public function genConfig() {
		$conf = array();
		foreach($this->drivers as $driver) {
			$c = $driver->genConfig();
			if(!empty($c)) {
				$conf = array_merge($c, $conf);
			}
		}
		return $conf;
	}

	public function writeConfig($config) {
		foreach($this->drivers as $driver) {
			$config = $driver->writeConfig($config);
		}
		return $config;
	}

	public function getActionBar($request) {
		$buttons = array();
		switch($request['display']) {
			case 'ampusers':
				$buttons = array(
					'delete' => array(
						'name' => 'delete',
						'id' => 'delete',
						'value' => _('Delete')
					),
					'reset' => array(
						'name' => 'reset',
						'id' => 'reset',
						'value' => _('Reset')
					),
					'submit' => array(
						'name' => 'submit',
						'id' => 'submit',
						'value' => _('Submit')
					)
				);
				if (empty($request['userdisplay'])) {
					unset($buttons['delete']);
				}
			break;
			case 'advancedsettings':
				$buttons = array(
					'reset' => array(
						'name' => 'reset',
						'id' => 'reset',
						'value' => _('Reset')
					),
					'submit' => array(
						'name' => 'submit',
						'id' => 'submit',
						'value' => _('Submit')
					)
				);
			break;
			case 'dahdichandids':
				$buttons = array(
					'delete' => array(
						'name' => 'delete',
						'id' => 'delete',
						'value' => _('Delete')
					),
					'reset' => array(
						'name' => 'reset',
						'id' => 'reset',
						'value' => _('Reset')
					),
					'submit' => array(
						'name' => 'submit',
						'id' => 'submit',
						'value' => _('Submit')
					)
				);
				if (empty($request['extdisplay'])) {
					unset($buttons['delete']);
				}
			case 'did':
				$buttons = array(
					'delete' => array(
						'name' => 'delete',
						'id' => 'delete',
						'value' => _('Delete')
					),
					'reset' => array(
						'name' => 'reset',
						'id' => 'reset',
						'value' => _('Reset')
					),
					'submit' => array(
						'name' => 'submit',
						'id' => 'submit',
						'value' => _('Submit')
					)
				);
				if (empty($request['extdisplay'])) {
					unset($buttons['delete']);
				}
			break;
			case 'routing':
				$buttons = array(
					'delete' => array(
						'name' => 'delete',
						'id' => 'delete',
						'value' => _('Delete')
					),
					'duplicate' => array(
						'name' => 'duplicate',
						'id' => 'duplicate',
						'value' => _('Duplicate')
					),
					'reset' => array(
						'name' => 'reset',
						'id' => 'reset',
						'value' => _('Reset')
					),
					'submit' => array(
						'name' => 'submit',
						'id' => 'submit',
						'value' => _('Submit')
					)
				);
				if (empty($request['id'])) {
					unset($buttons['delete'], $buttons['duplicate']);
				}
				if (empty($request['view'])){
					unset($buttons);
				}
			break;
			case 'trunks':
				$tmpButtons = array(
					'delete' => array(
						'name' => 'delete',
						'id' => 'delete',
						'value' => _('Delete')
					),
					'duplicate' => array(
						'name' => 'duplicate',
						'id' => 'duplicate',
						'value' => _('Duplicate')
					),
					'reset' => array(
						'name' => 'reset',
						'id' => 'reset',
						'value' => _('Reset')
					),
					'submit' => array(
						'name' => 'submit',
						'id' => 'submit',
						'value' => _('Submit')
					)
				);
				if (!empty($request['extdisplay'])) {
					$buttons = $tmpButtons;
				} else if (!empty($request['tech'])) {
					unset($tmpButtons['delete'], $tmpButtons['duplicate']);
					$buttons = $tmpButtons;
				}
			break;
			case 'users':
			case 'devices':
			case 'extensions':
			case 'dahdichandids':
				$buttons = array(
					'delete' => array(
						'name' => 'delete',
						'id' => 'delete',
						'value' => _('Delete')
					),
					'reset' => array(
						'name' => 'reset',
						'id' => 'reset',
						'value' => _('Reset')
					),
					'submit' => array(
						'name' => 'submit',
						'id' => 'submit',
						'value' => _('Submit')
					)
				);
				if (empty($request['extdisplay'])) {
					unset($buttons['delete']);
				}
				if (($request['display'] == "users" && $request['view'] != 'add') && empty($request['tech_hardware']) && trim($request['extdisplay']) == "") {
					$buttons = array();
				}
			break;
		}
		return $buttons;
	}

	public function install() {
	}

	public function uninstall() {
	}

	public function backup() {
	}

	public function restore($backup) {
	}

	public function doTests($db) {
		return true;
	}

	public function doConfigPageInit($page) {
		//Reassign $_REQUEST as it will be immutable in the future.
		$request = $_REQUEST;
		global $amp_conf;
		if ($page == "advancedsettings"){
			$freepbx_conf = $this->config;
			$settings = $freepbx_conf->get_conf_settings();
			foreach($request as $key => $val){
				if (isset($settings[$key])) {
					if($key == 'CRONMAN_UPDATES_CHECK') {
						$cm = \cronmanager::create($db);
						if($val == 'true') {
							$cm->enable_updates();
						} else {
							$cm->disable_updates();
						}
					}
					switch($settings[$key]['type']) {
						case CONF_TYPE_BOOL:
							$val = ($val == 'true') ? 1 : 0;
						break;
						default:
							$val = trim($val);
						break;
					}
					$freepbx_conf->set_conf_values(array($key => $val),true,$amp_conf['AS_OVERRIDE_READONLY']);
					$status = $freepbx_conf->get_last_update_status();
					if ($status[$key]['saved']) {
						//debug(sprintf(_("Advanced Settings changed freepbx_conf setting: [$key] => [%s]"),$val));
						needreload();
					}
				}
			}

		}// $page == "advancedsettings"
		if ($page == "dahdichandids"){
			if(!isset($_REQUEST['action'])){
				return;
			}
			$type = isset($request['type']) ? $request['type'] :  'setup';
			$action = isset($request['action']) ? $request['action'] :  '';
			if (isset($request['delete'])) $action = 'delete';
			$extdisplay  = isset($request['extdisplay']) ? $request['extdisplay'] : '';
			$channel = isset($request['channel']) ? $request['channel'] :  false;
			$description = isset($request['description']) ? $request['description'] :  '';
			$did = isset($request['did']) ? $request['did'] :  '';
			switch ($action) {
				case 'add':
					if (core_dahdichandids_add($description, $channel, $did)) {
						needreload();
						$_REQUEST['extdisplay'] = $channel;
						$this->freepbx->View->redirect_standard('extdisplay');
					}
				break;
				case 'edit':
					if (core_dahdichandids_edit($description, $channel, $did)) {
						needreload();
					}
				break;
				case 'delete':
					core_dahdichandids_delete($channel);
					needreload();
				break;
			}
		}// $page == "dahdichandids"

		if ($page == "routing") {
			$display='routing';
			$extdisplay=isset($request['extdisplay'])?$request['extdisplay']:'';
			$action = isset($request['action'])?$request['action']:'';
			if (isset($request['copyroute'])) {
				$action = 'copyroute';
			}
			$repotrunkdirection = isset($request['repotrunkdirection'])?$request['repotrunkdirection']:'';
			//this was effectively the sequence, now it becomes the route_id and the value past will have to change
			$repotrunkkey = isset($request['repotrunkkey'])?$request['repotrunkkey']:'';
			// Check if they uploaded a CSV file for their route patterns
			//
			if (isset($_FILES['pattern_file']) && $_FILES['pattern_file']['tmp_name'] != '') {
				$fh = fopen($_FILES['pattern_file']['tmp_name'], 'r');
				if ($fh !== false) {
					$csv_file = array();
					$index = array();

					// Check first row, ingoring empty rows and get indices setup
					//
					while (($row = fgetcsv($fh, 5000, ",", "\"")) !== false) {
						if (count($row) == 1 && $row[0] == '') {
							continue;
						} else {
							$count = count($row) > 4 ? 4 : count($row);
							for ($i=0;$i<$count;$i++) {
								switch (strtolower($row[$i])) {
									case 'prepend':
									case 'prefix':
									case 'match pattern':
									case 'callerid':
										$index[strtolower($row[$i])] = $i;
									break;
									default:
									break;
								}
							}
							// If no headers then assume standard order
							if (count($index) == 0) {
								$index['prepend'] = 0;
								$index['prefix'] = 1;
								$index['match pattern'] = 2;
								$index['callerid'] = 3;
								if ($count == 4) {
									$csv_file[] = $row;
								}
							}
							break;
						}
					}
					$row_count = count($index);
					while (($row = fgetcsv($fh, 5000, ",", "\"")) !== false) {
						if (count($row) == $row_count) {
							$csv_file[] = $row;
						}
					}
				}
			}
			// If we have a CSV file it replaces any existing patterns
			//
			if (!empty($csv_file)) {
				foreach ($csv_file as $row) {
					$this_prepend = isset($index['prepend']) ? htmlspecialchars(trim($row[$index['prepend']])) : '';
					$this_prefix = isset($index['prefix']) ? htmlspecialchars(trim($row[$index['prefix']])) : '';
					$this_match_pattern = isset($index['match pattern']) ? htmlspecialchars(trim($row[$index['match pattern']])) : '';
					$this_callerid = isset($index['callerid']) ? htmlspecialchars(trim($row[$index['callerid']])) : '';

					if ($this_prepend != '' || $this_prefix  != '' || $this_match_pattern != '' || $this_callerid != '') {
						$dialpattern_insert[] = array(
							'prepend_digits' => $this_prepend,
							'match_pattern_prefix' => $this_prefix,
							'match_pattern_pass' => $this_match_pattern,
							'match_cid' => $this_callerid,
						);
					}
				}
			} else if (isset($request["prepend_digit"])) {
				$prepend_digit = $request["prepend_digit"];
				$pattern_prefix = $request["pattern_prefix"];
				$pattern_pass = $request["pattern_pass"];
				$match_cid = $request["match_cid"];

				foreach (array_keys($prepend_digit) as $key) {
					if ($prepend_digit[$key]!='' || $pattern_prefix[$key]!='' || $pattern_pass[$key]!='' || $match_cid[$key]!='') {

						$dialpattern_insert[] = array(
							'prepend_digits' => htmlspecialchars(trim($prepend_digit[$key])),
							'match_pattern_prefix' => htmlspecialchars(trim($pattern_prefix[$key])),
							'match_pattern_pass' => htmlspecialchars(trim($pattern_pass[$key])),
							'match_cid' => htmlspecialchars(trim($match_cid[$key])),
						);
					}
				}
			} else if (isset($request["bulk_patterns"])) {
				$prepend = '/^([^+]*)\+/';
				$prefix = '/^([^|]*)\|/';
				$match_pattern = '/([^/]*)/';
				$callerid = '/\/(.*)$/';

				$data = explode("\n",$request['bulk_patterns']);
				foreach($data as $list) {
					if (preg_match('/^\s*$/', $list)) {
						continue;
					}

					$this_prepend = $this_prefix = $this_callerid = '';

					if (preg_match($prepend, $list, $matches)) {
						$this_prepend = $matches[1];
						$list = preg_replace($prepend, '', $list);
					}

					if (preg_match($prefix, $list, $matches)) {
						$this_prefix = $matches[1];
						$list = preg_replace($prefix, '', $list);
					}

					if (preg_match($callerid, $list, $matches)) {
						$this_callerid = $matches[1];
						$list = preg_replace($callerid, '', $list);
					}

					$dialpattern_insert[] = array(
						'prepend_digits' => htmlspecialchars(trim($this_prepend)),
						'match_pattern_prefix' => htmlspecialchars(trim($this_prefix)),
						'match_pattern_pass' => htmlspecialchars(trim($list)),
						'match_cid' => htmlspecialchars(trim($this_callerid)),
					);

					$i++;
				}
			}

			if ( isset($request['reporoutedirection']) && $request['reporoutedirection'] != '' && isset($request['reporoutekey']) && $request['reporoutekey'] != '') {
			  $request['route_seq'] = core_routing_setrouteorder($request['reporoutekey'], $request['reporoutedirection']);
			}

			$trunkpriority = array();
			if (isset($request["trunkpriority"])) {
				$trunkpriority = $request["trunkpriority"];

				if (!$trunkpriority) {
					$trunkpriority = array();
				}

				// delete blank entries and reorder
				foreach (array_keys($trunkpriority) as $key) {
					if ($trunkpriority[$key] == '') {
						// delete this empty
						unset($trunkpriority[$key]);

					} else if (($key==($repotrunkkey-1)) && ($repotrunkdirection=="up")) {
						// swap this one with the one before (move up)
						$temptrunk = $trunkpriority[$key];
						$trunkpriority[ $key ] = $trunkpriority[ $key+1 ];
						$trunkpriority[ $key+1 ] = $temptrunk;

					} else if (($key==($repotrunkkey)) && ($repotrunkdirection=="down")) {
						// swap this one with the one after (move down)
						$temptrunk = $trunkpriority[ $key+1 ];
						$trunkpriority[ $key+1 ] = $trunkpriority[ $key ];
						$trunkpriority[ $key ] = $temptrunk;
					}
				}
				unset($temptrunk);
				$trunkpriority = array_unique(array_values($trunkpriority)); // resequence our numbers
			  if ($action == '') {
				$action = "updatetrunks";
			  }

			}
			$routename = isset($request['routename']) ? $request['routename'] : '';
			$routepass = isset($request['routepass']) ? $request['routepass'] : '';
			$emergency = isset($request['emergency']) ? $request['emergency'] : '';
			$intracompany = isset($request['intracompany']) ? $request['intracompany'] : '';
			$mohsilence = isset($request['mohsilence']) ? $request['mohsilence'] : '';
			$outcid = isset($request['outcid']) ? $request['outcid'] : '';
			$outcid_mode = isset($request['outcid_mode']) ? $request['outcid_mode'] : '';
			$time_group_id = isset($request['time_group_id']) ? $request['time_group_id'] : '';
			$route_seq = isset($request['route_seq']) ? $request['route_seq'] : '';

			$goto = isset($request['goto0'])?$request['goto0']:'';
			$dest = $goto ? $request[$goto . '0'] : '';
			//if submitting form, update database
			switch ($action) {
				case 'ajaxroutepos':
					$ret = core_routing_setrouteorder($repotrunkkey, $repotrunkdirection);
					needreload();

					header("Content-type: application/json");
					echo json_encode(array('position' => $ret));
					exit;
				break;
				case "copyroute":
					$routename .= "_copy_$extdisplay";
					$extdisplay='';
					$route_seq++;
					// Fallthrough to addtrunk now...
					//
				case "addroute":
					$extdisplay = core_routing_addbyid($routename, $outcid, $outcid_mode, $routepass, $emergency, $intracompany, $mohsilence, $time_group_id, $dialpattern_insert, $trunkpriority, $route_seq, $dest);
					$_REQUEST['id'] = $extdisplay;
					needreload();
					$this->freepbx->View->redirect_standard('id');
				break;
				case "editroute":
					core_routing_editbyid($extdisplay, $routename, $outcid, $outcid_mode, $routepass, $emergency, $intracompany, $mohsilence, $time_group_id, $dialpattern_insert, $trunkpriority, $route_seq, $dest);
					needreload();
				break;
				case "updatetrunks":
					$ret = core_routing_updatetrunks($extdisplay, $trunkpriority, true);
					header("Content-type: application/json");
					echo json_encode(array('result' => $ret));
					needreload();
					exit;
				break;
				case "delroute":
					$ret = core_routing_delbyid($request['id']);
					// re-order the routes to make sure that there are no skipped numbers.
					// example if we have 001-test1, 002-test2, and 003-test3 then delete 002-test2
					// we do not want to have our routes as 001-test1, 003-test3 we need to reorder them
					// so we are left with 001-test1, 002-test3
					needreload();
					if($request['json']){
						header("Content-type: application/json");
						echo json_encode($ret);
					}
					exit;
				break;
				case 'prioritizeroute':
					needreload();
				break;
				case 'getnpanxxjson':
					try {
						$npa = $request['npa'];
						$nxx = $request['nxx'];
						$url = 'http://www.localcallingguide.com/xmllocalprefix.php?npa=602&nxx=930';
						$request = new \Pest('http://www.localcallingguide.com/xmllocalprefix.php');
						$data = $request->get('?npa='.$npa.'&nxx='.$nxx);
						$xml = new \SimpleXMLElement($data);
						$pfdata = $xml->xpath('//lca-data/prefix');
						$retdata = array();
						foreach($pfdata as $item){
							$inpa = (string)$item->npa;
							$inxx = (string)$item->nxx;
							$retdata[$inpa.$inxx] = array('npa' => $inpa, 'nxx' => $inxx);
						}
						$ret = json_encode($retdata);
						header("Content-type: application/json");
						echo $ret;
						exit;
					}catch(Pest_NotFound $e){
						header("Content-type: application/json");
						echo json_encode(array('error' => $e));
						exit;
					}
				break;
				case 'populatenpanxx':
					$dialpattern_array = $dialpattern_insert;
					if (preg_match("/^([2-9]\d\d)-?([2-9]\d\d)$/", $_REQUEST["npanxx"], $matches)) {
						// first thing we do is grab the exch:
						$ch = curl_init();
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($ch, CURLOPT_URL, "http://www.localcallingguide.com/xmllocalprefix.php?npa=".$matches[1]."&nxx=".$matches[2]);
						curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Linux; FreePBX Local Trunks Configuration)");
						$str = curl_exec($ch);
						curl_close($ch);

						// quick 'n dirty - nabbed from PEAR
						global $amp_conf;
						require_once($amp_conf['AMPWEBROOT'] . '/admin/modules/core/XML_Parser.php');
						require_once($amp_conf['AMPWEBROOT'] . '/admin/modules/core/XML_Unserializer.php');

						$xml = new xml_unserializer;
						$xml->unserialize($str);
						$xmldata = $xml->getUnserializedData();

						$hash_filter = array(); //avoid duplicates
						if (isset($xmldata['lca-data']['prefix'])) {
							// we do the loops separately so patterns are grouped together

							// match 1+NPA+NXX (dropping 1)
							foreach ($xmldata['lca-data']['prefix'] as $prefix) {
								if (isset($hash_filter['1'.$prefix['npa'].$prefix['nxx']])) {
									continue;
								} else {
									$hash_filter['1'.$prefix['npa'].$prefix['nxx']] = true;
								}
								$dialpattern_array[] = array(
									'prepend_digits' => '',
									'match_pattern_prefix' => '1',
									'match_pattern_pass' => htmlspecialchars($prefix['npa'].$prefix['nxx']).'XXXX',
									'match_cid' => '',
									);
							}
							// match NPA+NXX
							foreach ($xmldata['lca-data']['prefix'] as $prefix) {
								if (isset($hash_filter[$prefix['npa'].$prefix['nxx']])) {
									continue;
								} else {
									$hash_filter[$prefix['npa'].$prefix['nxx']] = true;
								}
								$dialpattern_array[] = array(
									'prepend_digits' => '',
									'match_pattern_prefix' => '',
									'match_pattern_pass' => htmlspecialchars($prefix['npa'].$prefix['nxx']).'XXXX',
									'match_cid' => '',
									);
							}
							// match 7-digits
							foreach ($xmldata['lca-data']['prefix'] as $prefix) {
								if (isset($hash_filter[$prefix['nxx']])) {
									continue;
								} else {
									$hash_filter[$prefix['nxx']] = true;
								}
									$dialpattern_array[] = array(
										'prepend_digits' => '',
										'match_pattern_prefix' => '',
										'match_pattern_pass' => htmlspecialchars($prefix['nxx']).'XXXX',
										'match_cid' => '',
										);
							}
							unset($hash_filter);
						} else {
							$errormsg = _("Error fetching prefix list for: "). $request["npanxx"];
						}
					} else {
						// what a horrible error message... :p
						$errormsg = _("Invalid format for NPA-NXX code (must be format: NXXNXX)");
					}

					if (isset($errormsg)) {
						echo "<script language=\"javascript\">alert('".addslashes($errormsg)."');</script>";
						unset($errormsg);
					}
				break;
			}


		}// $page == "routing"

		if ($page == "did") {
			$extdisplay= htmlspecialchars(isset($request['extdisplay'])?$request['extdisplay']:'');
			$old_extdisplay = $extdisplay;
			$dispnum = 'did'; //used for switch on config.php
			$account = isset($request['account'])?$request['account']:'';
			$action = isset($request['action'])?$request['action']:'';
			$goto = isset($request['goto0'])?$request['goto0']:'';
			$ringing = isset($request['ringing'])?$request['ringing']:'';
			$reversal = isset($request['reversal'])?$request['reversal']:'';
			$description = htmlspecialchars(isset($request['description'])?$request['description']:'');
			$privacyman = isset($request['privacyman'])?$request['privacyman']:'0';
			$pmmaxretries = isset($request['pmmaxretries'])?$request['pmmaxretries']:'';
			$pmminlength = isset($request['pmminlength'])?$request['pmminlength']:'';
			$alertinfo = htmlspecialchars(isset($request['alertinfo'])?$request['alertinfo']:'');
			$mohclass = isset($request['mohclass'])?$request['mohclass']:'default';
			$grppre = isset($request['grppre'])?$request['grppre']:'';
			$delay_answer = isset($request['delay_answer'])&&$request['delay_answer']?$request['delay_answer']:'';
			$pricid = isset($request['pricid'])?$request['pricid']:'';
			$rnavsort = isset($request['rnavsort'])?$request['rnavsort']:'description';
			$didfilter = isset($request['didfilter'])?$request['didfilter']:'';
			if (isset($request['submitclear']) && isset($request['goto0'])) {
				$request[$request['goto0'].'0'] = '';
			}

			if (isset($request['extension']) && isset($request['cidnum'])) {
				$extdisplay = $request['extension']."/".$request['cidnum'];
			}
			if (isset($request['old_extension']) && isset($request['old_cidnum'])) {
				$old_extdisplay = $request['old_extension']."/".$request['old_cidnum'];
			}

			//update db if submiting form
			switch ($action) {
				case 'addIncoming':
					//create variables from request
					extract($request);
					//add details to the 'incoming' table
					if (core_did_add($request)) {
						needreload();
						$_REQUEST['extdisplay'] = $_REQUEST['extension']."/".$_REQUEST['cidnum'];
						$this->freepbx->View->redirect_standard('extdisplay', 'didfilter', 'rnavsort');
					}
				break;
				case 'delIncoming':
					$extarray=explode('/',$extdisplay,2);
					core_did_del($extarray[0],$extarray[1]);
					needreload();
				break;
				case 'edtIncoming':
					$extarray=explode('/',$old_extdisplay,2);
					if (core_did_edit($extarray[0],$extarray[1],$_REQUEST)) {
						needreload();
					}
				break;
			}

		}// $page == "did"

		if ($page == "astmodules") {
			$action = $request['action'];
			$section = $request['section'];
			$module = $request['module'];
			switch($action){
				case 'add':
					switch($section){
						case 'amodnoload':
							$this->ModulesConf->noload($module);
							return true;
						break;
						case 'amodpreload':
							$this->ModulesConf->preload($module);
							return true;
						break;
						default:
							return false;
						break;
					}
				break;
				case 'del':
					switch($section){
						case 'amodnoload':
							$this->ModulesConf->removenoload($module);
							return true;
						break;
						case 'amodpreload':
							$this->ModulesConf->removepreload($module);
							return true;
						break;
						default:
							return false;
						break;
					}
				break;
				default:
				return false;
				break;
			}
		} // $page == "astmodules"

	}

	/**
	 * Converts a request into an array that core wants.
	 * @param {int} $account The Account Number
	 * @param {string} The TECH type
	 * @param {int} &$flag   The Flag Number
	 */
	public function convertRequest2Array($account,$tech,&$flag = 2) {
		$flag = !empty($flag) ? $flag : 2;
		$fields = array();
		$tech = strtoupper($tech);
		foreach ($_REQUEST as $req=>$data) {
			if ( substr($req, 0, 8) == 'devinfo_' ) {
				$keyword = substr($req, 8);
				$data = trim($data);
				if ( $keyword == 'dial' && $data == '' ) {
					if($tech == 'ZAP' || $tech == 'DAHDI') {
						$chan = $_REQUEST['devinfo_channel'] != '' ? $_REQUEST['devinfo_channel'] : $_REQUEST['channel'];
						$fields[$keyword] = array("value" => $tech.'/'.$chan, "flag" => $flag++);
					} else {
						$fields[$keyword] = array("value" => $tech.'/'.$account, "flag" => $flag++);
					}
				} elseif ($keyword == 'mailbox' && $data == '') {
					$fields['mailbox'] = array("value" => $account.'@device', "flag" => $flag++);
				} elseif ($keyword == 'vmexten' && $data == '') {
					// don't add it
				} else {
					$fields[$keyword] = array("value" => $data, "flag" => $flag++);
				}
			}
		}
		if(empty($fields)) {
			die_freepbx('Fields are empty');
		}
		$fields['account'] = array("value" => $account, "flag" => $flag++);
		$fields['callerid'] = array("value" => (isset($_REQUEST['description']) && $_REQUEST['description']) ? $_REQUEST['description']." <".$account.'>' : 'device'." <".$account.'>', "flag" => $flag++);
		return $fields;
	}

	public function generateDefaultUserSettings($number,$displayname) {
		return array(
			"extension" => $number,
			"name" => $displayname,
			"outboundcid" => "",
			"password" => "",
			"sipname" => "",
			"ringtimer" => 0,
			"callwaiting" => "enabled",
		);
	}

	/**
	 * Generate the default settings when creating a device
	 * TODO: This is beta, will be cleaned up in 13
	 * @param {string} The TECH
	 * @param {int} The exten or device number
	 * @param {string} $displayname The displayname
	 */
	public function generateDefaultDeviceSettings($tech,$number,$displayname,&$flag = 2) {
		$flag = !empty($flag) ? $flag : 2;
		$dial = '';
		$settings = array();
		if(isset($this->drivers[$tech])) {
			$settings = $this->drivers[$tech]->getDefaultDeviceSettings($number, $displayname, $flag);
			if(empty($settings)) {
				return array();
			}
		}
		$gsettings  = array(
			"devicetype" => array(
				"value" => "fixed"
			),
			"user" => array(
				"value" => $number
			),
			"description" => array(
				"value" => $displayname
			),
			"emergency_cid" => array(
				"value" => $displayname,
			),
			"dial" => array(
				"value" => $settings['dial']."/".$number,
				"flag" => $flag++
			),
			"secret" => array(
				"value" => md5(uniqid()),
				"flag" => $flag++
			),
			"context" => array(
				"value" => "from-internal",
				"flag" => $flag++
			),
			"mailbox" => array(
				"value" => $number."@device",
				"flag" => $flag++
			),
			"account" => array(
				"value" => $number,
				"flag" => $flag++
			),
			"callerid" => array(
				"value" => "device <".$number.">",
				"flag" => $flag++
			)
		);

		return array_merge($settings['settings'],$gsettings);
	}

	/**
	 * Add Device
	 * @param {int} The Device Number
	 * @param {string} The TECH type
	 * @param {array} $settings=array() Array with all settings
	 * @param {bool} $editmode=false   If edited, (this is so it doesnt destroy the AsteriskDB)
	 */
	public function addDevice($id,$tech,$settings=array(),$editmode=false) {
		if ($tech == '' || trim($tech) == 'virtual') {
			return true;
		}

		if (trim($id) == '' || empty($settings)) {
			throw new \Exception(_("Device Extension was blank or there were no settings defined"));
			return false;
		}

		//ensure this id is not already in use
		$dev = $this->getDevice($id);
		if(!empty($dev)) {
			throw new \Exception(_("This device id is already in use"));
		}

		//unless defined, $dial is TECH/id
		if ($settings['dial']['value'] == '') {
			//zap, dahdi are exceptions
			if (strtolower($tech) == "zap" || strtolower($tech) == 'dahdi') {
				$thischan = $settings['devinfo_channel']['value'] != '' ? $settings['devinfo_channel']['value'] : $settings['channel']['value'];
				$settings['dial']['value'] = strtoupper($tech).'/'.$thischan;
				//-------------------------------------------------------------------------------------------------
				// Added to enable the unsupported misdn module
				//
			} else if (strtolower($tech) == "misdn") {
				$settings['dial']['value'] = $settings['devinfo_port']['value'].'/'.($settings['devinfo_msn']['value'] ? $settings['devinfo_msn']['value'] : $id);
				//-------------------------------------------------------------------------------------------------
			} else {
				$settings['dial']['value'] = strtoupper($tech)."/".$id;
			}
		}

		$settings['user']['value'] = ($settings['user']['value'] == 'new') ? $id : $settings['user']['value'];
		$settings['emergency_cid']['value'] = trim($settings['emergency_cid']['value']);
		$settings['description']['value'] = trim($settings['description']['value']);

		//insert into devices table
		$sql="INSERT INTO devices (id,tech,dial,devicetype,user,description,emergency_cid) values (?,?,?,?,?,?,?)";
		$sth = $this->database->prepare($sql);
		try {
			$sth->execute(array($id,$tech,$settings['dial']['value'],$settings['devicetype']['value'],$settings['user']['value'],$settings['description']['value'],$settings['emergency_cid']['value']));
		} catch(\Exception $e) {
			die_freepbx("Could Not Insert Device", $e->getMessage());
			return false;
		}

		$astman = $this->FreePBX->astman;
		//add details to astdb
		if ($astman->connected()) {
			// if adding or editting a fixed device, user property should always be set
			if ($settings['devicetype']['value'] == 'fixed' || !$editmode) {
				$astman->database_put("DEVICE",$id."/user",$settings['user']['value']);
			}
			// If changing from a fixed to an adhoc, the user property should be intialized
			// to the new default, not remain as the previous fixed user
			if ($editmode) {
				$previous_type = $astman->database_get("DEVICE",$id."/type");
				if ($previous_type == 'fixed' && $settings['devicetype']['value'] == 'adhoc') {
					$astman->database_put("DEVICE",$id."/user",$settings['user']['value']);
				}
			}
			$astman->database_put("DEVICE",$id."/dial",$settings['dial']['value']);
			$astman->database_put("DEVICE",$id."/type",$settings['devicetype']['value']);
			$astman->database_put("DEVICE",$id."/default_user",$settings['user']['value']);
			if($settings['emergency_cid']['value'] != '') {
				$astman->database_put("DEVICE",$id."/emergency_cid","\"".$settings['emergency_cid']['value']."\"");
			} else {
				$astman->database_del("DEVICE",$id."/emergency_cid");
			}

			$apparent_connecteduser = ($editmode && $settings['user']['value'] != "none") ? $astman->database_get("DEVICE",$id."/user") : $settings['user']['value'];
			if ($settings['user']['value'] != "none" && $apparent_connecteduser == $settings['user']['value'])  {
				$existingdevices = $astman->database_get("AMPUSER",$settings['user']['value']."/device");
				if (empty($existingdevices)) {
					$astman->database_put("AMPUSER",$settings['user']['value']."/device",$id);
				} else {
					$existingdevices_array = explode('&',$existingdevices);
					if (!in_array($id, $existingdevices_array)) {
						$existingdevices_array[]=$id;
						$existingdevices = implode('&',$existingdevices_array);
						$astman->database_put("AMPUSER",$settings['user']['value']."/device",$existingdevices);
					}
				}
			}

		} else {
			die_freepbx("Cannot connect to Asterisk Manager with ".$this->config->get('AMPMGRUSER')."/".$this->config->get('AMPMGRPASS'));
		}

		// create a voicemail symlink if needed
		$thisUser = $this->getUser($settings['user']['value']);
		dbug($thisUser);
		if(isset($thisUser['voicemail']) && ($thisUser['voicemail'] != "novm")) {
			if(empty($thisUser['voicemail'])) {
				$vmcontext = "default";
			} else {
				$vmcontext = $thisUser['voicemail'];
			}

			//voicemail symlink
			$spooldir = $this->config->get('ASTSPOOLDIR');
			exec("rm -f ".$spooldir."/voicemail/device/".$id);
			exec("/bin/ln -s ".$spooldir."/voicemail/".$vmcontext."/".$settings['user']['value']."/ ".$spooldir."/voicemail/device/".$id);
		}

		// before calling device specifc funcitions, get rid of any bogus fields in the array
		//
		if (isset($settings['devinfo_secret_origional'])) {
			unset($settings['devinfo_secret_origional']);
		}

		unset($settings['devicetype']);
		unset($settings['user']);
		unset($settings['description']);
		unset($settings['emergency_cid']);
		unset($settings['changecdriver']);

		//take care of sip/iax/zap config
		$tech = strtolower($tech);
		if(isset($this->drivers[$tech])) {
			return $this->drivers[$tech]->addDevice($id, $settings);
		}

		return true;
	}

	/**
	* Get all the users
	* @param {bool} $get_all=false Whether to get all of check in the range
	*/
	function listUsers($get_all=false) {
		$sql = 'SELECT extension,name,voicemail FROM users ORDER BY extension';
		$sth = $this->database->prepare($sql);
		$sth->execute();
		$results = $sth->fetchAll(\PDO::FETCH_BOTH);
		//only allow extensions that are within administrator's allowed range
		foreach($results as $result){
			if ($get_all || checkRange($result[0])){
				$extens[] = array($result[0],$result[1],$result[2]);
			}
		}

		if (isset($extens)) {
			sort($extens);
			return $extens;
		} else {
			return null;
		}
	}

	/**
	 * Delete a Device
	 * @param {int} The Device ID
	 * @param {bool} $editmode=false If in edit mode (this is so it doesnt destroy the AsteriskDB)
	 */
	public function delDevice($account,$editmode=false) {
		$astman = $this->FreePBX->astman;
		//get all info about device
		$devinfo = $this->getDevice($account);
		if (empty($devinfo)) {
			return true;
		}

		//delete details to astdb
		if ($astman->connected()) {
			// If a user was selected, remove this device from the user
			$deviceuser = $astman->database_get("DEVICE",$account."/user");
			if (isset($deviceuser) && $deviceuser != "none") {
				// Remove the device record from the user's device list
				$userdevices = $astman->database_get("AMPUSER",$deviceuser."/device");

				// We need to remove just this user and leave the rest alone
				$userdevicesarr = explode("&", $userdevices);
				$userdevicesarr_hash = array_flip($userdevicesarr);
				unset($userdevicesarr_hash[$account]);
				$userdevicesarr = array_flip($userdevicesarr_hash);
				$userdevices = implode("&", $userdevicesarr);

				if (empty($userdevices)) {
					$astman->database_del("AMPUSER",$deviceuser."/device");
				} else {
					$astman->database_put("AMPUSER",$deviceuser."/device",$userdevices);
				}
			}
			if (!$editmode) {
				$astman->database_del("DEVICE",$account."/dial");
				$astman->database_del("DEVICE",$account."/type");
				$astman->database_del("DEVICE",$account."/user");
				$astman->database_del("DEVICE",$account."/default_user");
				$astman->database_del("DEVICE",$account."/emergency_cid");
			}

			//delete from devices table
			$sql = "DELETE FROM devices WHERE id = ?";
			$sth = $this->database->prepare($sql);
			try {
				$sth->execute(array($account));
			} catch(\Exception $e) {
			}

			//voicemail symlink
			$spooldir = $this->config->get('ASTSPOOLDIR');
			if(file_exists($spooldir."/voicemail/device/".$account)) {
				exec("rm -f ".$spooldir."/voicemail/device/".$account);
			}
		} else {
			die_freepbx("Cannot connect to Asterisk Manager with ".$this->config->get("AMPMGRUSER")."/".$this->config->get("AMPMGRPASS"));
		}

		$tech = $devinfo['tech'];
		if(isset($this->drivers[$tech])) {
			return $this->drivers[$tech]->delDevice($account);
		}
		return true;
	}

	public function getAllDevicesByType($type="") {
		if(empty($type)) {
			$sql = "SELECT * FROM devices ORDER BY id";
			$sth = $this->database->prepare($sql);
			try {
				$sth->execute();
				$results = $sth->fetchAll(\PDO::FETCH_ASSOC);
			} catch(\Exception $e) {
				return array();
			}
		} else {
			$sql = "SELECT * FROM devices WHERE tech = ? ORDER BY id";
			$sth = $this->database->prepare($sql);
			try {
				$sth->execute(array($type));
				$results = $sth->fetchAll(\PDO::FETCH_ASSOC);
			} catch(\Exception $e) {
				return array();
			}
		}
		return $results;
	}

	public function getAllUsers() {
		$sql = 'SELECT extension,name,voicemail FROM users ORDER BY extension';
		$sth = $this->database->prepare($sql);
		try {
			$sth->execute();
			$results = $sth->fetchAll(\PDO::FETCH_ASSOC);
		} catch(\Exception $e) {
			return array();
		}
		return $results;
	}

	public function getAllUsersByDeviceType($type="") {
		if(empty($type)) {
			$sql = "SELECT * FROM users LEFT JOIN devices ON users.extension = devices.id ORDER BY users.extension";
			$sth = $this->database->prepare($sql);
			try {
				$sth->execute();
				$results = $sth->fetchAll(\PDO::FETCH_ASSOC);
			} catch(\Exception $e) {
				return array();
			}
		} else {
			$sql = "SELECT * FROM users LEFT JOIN devices ON users.extension = devices.id WHERE devices.tech = ? ORDER BY users.extension";
			$sth = $this->database->prepare($sql);
			try {
				$sth->execute(array($type));
				$results = $sth->fetchAll(\PDO::FETCH_ASSOC);
			} catch(\Exception $e) {
				return array();
			}
		}
		return $results;
	}

	public function addUser($extension, $settings, $editmode=false) {
		if (trim($extension) == '' ) {
			throw new \Exception(_("You must put in an extension (or user) number"));
		}

		//ensure this id is not already in use
		//TODO: fix
		$extens = $this->listUsers();
		if(is_array($extens)) {
			foreach($extens as $exten) {
				if ($exten[0]===$extension) {
					throw new \Exception(sprintf(_("This user/extension %s is already in use"),$extension));
				}
			}
		}

		$settings['newdid_name'] = isset($settings['newdid_name']) ? $settings['newdid_name'] : '';
		$settings['newdid'] = isset($settings['newdid']) ? preg_replace("/[^0-9._XxNnZz\[\]\-\+]/" ,"", trim($settings['newdid'])) : '';
		$settings['newdidcid'] = isset($settings['newdidcid']) ? trim($settings['newdidcid']) : '';

		if (!preg_match('/^priv|^block|^unknown|^restrict|^unavail|^anonym|^withheld/',strtolower($newdidcid))) {
			$newdidcid = preg_replace("/[^0-9._XxNnZz\[\]\-\+]/" ,"", $newdidcid);
		}

		if ($settings['newdid'] != '' || $settings['newdidcid'] != '') {
			//TODO: fix to use BMO
			$existing = core_did_get($settings['newdid'], $settings['newdidcid']);
			if (!empty($existing)) {
				throw new \Exception(sprintf(_("A route with this DID/CID: %s/%s already exists"),$existing['extension'],$existing['cidnum']));
			}
		}

		$settings['sipname'] = isset($settings['sipname']) ? preg_replace("/\s/" ,"", trim($settings['sipname'])) : '';
		//TODO: fix to use BMO
		if (! core_sipname_check($settings['sipname'], $extension)) {
			throw new \Exception(_("This sipname: {$sipname} is already in use"));
		}

		// strip the ugly return of the gui radio funciton which comes back as "recording_out_internal=always" for example
		// TODO this should be done with a hook
		if (isset($settings['recording_in_external'])) {
			$rec_tmp = explode('=',$settings['recording_in_external'],2);
			$settings['recording_in_external'] = count($rec_tmp) == 2 ? $rec_tmp[1] : 'dontcare';
		} else {
			$settings['recording_in_external'] = 'dontcare';
		}
		if (isset($settings['recording_out_external'])) {
			$rec_tmp = explode('=',$recording_out_external,2);
			$settings['recording_out_external'] = count($rec_tmp) == 2 ? $rec_tmp[1] : 'dontcare';
		} else {
			$settings['recording_out_external'] = 'dontcare';
		}
		if (isset($settings['recording_in_internal'])) {
			$rec_tmp = explode('=',$settings['recording_in_internal'],2);
			$settings['recording_in_internal'] = count($rec_tmp) == 2 ? $rec_tmp[1] : 'dontcare';
		} else {
			$settings['recording_in_internal'] = 'dontcare';
		}
		if (isset($settings['recording_out_internal'])) {
			$rec_tmp = explode('=',$settings['recording_out_internal'],2);
			$settings['recording_out_internal'] = count($rec_tmp) == 2 ? $rec_tmp[1] : 'dontcare';
		} else {
			$settings['recording_out_internal'] = 'dontcare';
		}
		if (isset($settings['recording_ondemand'])) {
			$rec_tmp = explode('=',$settings['recording_ondemand'],2);
			$settings['recording_ondemand'] = count($rec_tmp) == 2 ? $rec_tmp[1] : 'disabled';
		} else {
			$settings['recording_ondemand'] = 'disabled';
		}

		//if voicemail is enabled, set the box@context to use
		//havn't checked but why is voicemail needed on users anyway?  Doesn't exactly make it modular !
		//TODO use a hook here
		if ( function_exists('voicemail_mailbox_get') ) {
			$vmbox = voicemail_mailbox_get($extension);
			if ( $vmbox == null ) {
				$settings['voicemail'] = "novm";
			} else {
				$settings['voicemail'] = $vmbox['vmcontext'];
			}
		}

		$sql = "INSERT INTO users (extension,password,name,voicemail,ringtimer,noanswer,recording,outboundcid,sipname,noanswer_cid,busy_cid,chanunavail_cid,noanswer_dest,busy_dest,chanunavail_dest) " .
						"VALUES (:extension, :password, :name, :voicemail, :ringtimer, :noanswer, :recording, :outboundcid, :sipname, :noanswer_cid, :busy_cid, :chanunavail_cid, :noanswer_dest, :busy_dest, :chanunavail_dest)";
		$sth = $this->database->prepare($sql);
		try {
			$sth->execute(array(
				"extension" => $extension,
				"password" => isset($settings['password']) ? $settings['password'] : '',
				"name" => isset($settings['name']) ? preg_replace(array('/</','/>/'), array('(',')'), trim($settings['name'])) : '',
				"voicemail" => isset($settings['voicemail']) ? $settings['voicemail'] : 'default',
				"ringtimer" => isset($settings['ringtimer']) ? $settings['ringtimer'] : '',
				"noanswer" => isset($settings['noanswer']) ? $settings['noanswer'] : '',
				"recording" => isset($settings['recording']) ? $settings['recording'] : '',
				"outboundcid" => isset($settings['outboundcid']) ? $settings['outboundcid'] : '',
				"sipname" => isset($settings['sipname']) ? $settings['sipname'] : '',
				"noanswer_cid" => isset($settings['noanswer_cid']) ? $settings['noanswer_cid'] : "",
				"busy_cid" => isset($settings['busy_cid']) ? $settings['busy_cid'] : "",
				"chanunavail_cid" => isset($settings['chanunavail_cid']) ? $settings['chanunavail_cid'] : "",
				"noanswer_dest" => !empty($settings['noanswer_dest']) && $settings[$settings[$settings['noanswer_dest']].'0'] != '' ? $settings[$settings[$settings['noanswer_dest']].'0'] : "",
				"busy_dest" => !empty($settings['busy_dest']) && $settings[$settings[$settings['busy_dest']].'1'] != '' ? $settings[$settings[$settings['busy_dest']].'1'] : "",
				"chanunavail_dest" => !empty($settings['chanunavail_dest']) && $settings[$settings[$settings['chanunavail_dest']].'2'] != '' ? $settings[$settings[$settings['chanunavail_dest']].'2'] : ""
			));
		} catch(\Exception $e) {
			throw new \Exception("Unable to insert into users: ".addSlashes($e->getMessage()));
		}

		//write to astdb
		$astman = $this->FreePBX->astman;
		if ($astman) {
			$astman->database_put("AMPUSER",$extension."/password",isset($settings['password']) ? $settings['password'] : '');
			$astman->database_put("AMPUSER",$extension."/ringtimer",isset($settings['noanswer']) ? $settings['noanswer'] : '');
			$astman->database_put("AMPUSER",$extension."/cfringtimer",isset($settings['cfringtimer']) ? $settings['cfringtimer'] : 0);
			$astman->database_put("AMPUSER",$extension."/concurrency_limit",isset($settings['concurrency_limit']) ? $settings['concurrency_limit'] : 0);
			$astman->database_put("AMPUSER",$extension."/noanswer",isset($settings['noanswer']) ? $settings['noanswer'] : '');
			$astman->database_put("AMPUSER",$extension."/recording",isset($settings['recording']) ? $settings['recording'] : '');
			$astman->database_put("AMPUSER",$extension."/outboundcid",isset($settings['outboundcid']) ? '"'.$settings['outboundcid'].'"' : '');
			$astman->database_put("AMPUSER",$extension."/cidname",isset($settings['name']) ? '"'.$settings['name'].'"' : '');
			$astman->database_put("AMPUSER",$extension."/cidnum",(isset($settings['cid_masquerade']) && trim($settings['cid_masquerade']) != "") ? trim($settings['cid_masquerade']) : $extension);
			$astman->database_put("AMPUSER",$extension."/voicemail",'"'.(isset($settings['voicemail']) ? $settings['voicemail'] : '').'"');
			$astman->database_put("AMPUSER",$extension."/answermode",'"'.(isset($settings['answermode']) ? $settings['answermode']: 'disabled').'"');

			$astman->database_put("AMPUSER",$extension."/recording/in/external",'"'.$settings['recording_in_external'].'"');
			$astman->database_put("AMPUSER",$extension."/recording/out/external",'"'.$settings['recording_out_external'].'"');
			$astman->database_put("AMPUSER",$extension."/recording/in/internal",'"'.$settings['recording_in_internal'].'"');
			$astman->database_put("AMPUSER",$extension."/recording/out/internal",'"'.$settings['recording_out_internal'].'"');
			$astman->database_put("AMPUSER",$extension."/recording/ondemand",'"'.$settings['recording_ondemand'].'"');
			$astman->database_put("AMPUSER",$extension."/recording/priority",'"'.(isset($settings['recording_priority']) ? $settings['recording_priority'] : '10').'"');

			// If not set then we are using system default so delete the tree all-together
			//
			if (isset($settings['dialopts'])) {
				$astman->database_put("AMPUSER",$extension."/dialopts", $settings['dialopts']);
			} else {
				$astman->database_del("AMPUSER",$extension."/dialopts");
			}

			$call_screen = isset($settings['call_screen']) ? $settings['call_screen'] : '0';
			switch ($call_screen) {
				case '0':
					$astman->database_del("AMPUSER",$extension."/screen");
				break;
				case 'nomemory':
					$astman->database_put("AMPUSER",$extension."/screen",'"nomemory"');
				break;
				case 'memory':
					$astman->database_put("AMPUSER",$extension."/screen",'"memory"');
				break;
				default:
				break;
			}

			if (!$editmode) {
				$astman->database_put("AMPUSER",$extension."/device",'"'.((isset($settings['device'])) ? $settings['device'] : '').'"');
			}

			if (trim($settings['callwaiting']) == 'enabled') {
				$astman->database_put("CW",$extension,"\"ENABLED\"");
			} else if (trim($callwaiting) == 'disabled') {
				$astman->database_del("CW",$extension);
			}

			if (trim($settings['pinless']) == 'enabled') {
				$astman->database_put("AMPUSER",$extension."/pinless","\"NOPASSWD\"");
			} else if (trim($pinless) == 'disabled') {
				$astman->database_del("AMPUSER",$extension."/pinless");
			}
		} else {
			die_freepbx("Cannot connect to Asterisk Manager with ".$this->FreePBX->Config->get("AMPMGRUSER")."/".$this->FreePBX->Config->get("AMPMGRPASS"));
		}

		// OK - got this far, if they entered a new inbound DID/CID let's deal with it now
		// remember - in the nice and ugly world of this old code, $vars has been extracted
		// newdid and newdidcid

		// Now if $newdid is set we need to add the DID to the routes
		//
		if ($settings['newdid'] != '' || $settings['newdidcid'] != '') {
			$did_dest                = 'from-did-direct,'.$extension.',1';
			$did_vars = array();
			$did_vars['extension']   = $settings['newdid'];
			$did_vars['cidnum']      = $settings['newdidcid'];
			$did_vars['privacyman']  = '';
			$did_vars['alertinfo']   = '';
			$did_vars['ringing']     = '';
			$did_vars['reversal']     = '';
			$did_vars['mohclass']    = 'default';
			$did_vars['description'] = $settings['newdid_name'];
			$did_vars['grppre']      = '';
			$did_vars['delay_answer']= '0';
			$did_vars['pricid']= '';
			core_did_add($did_vars, $did_dest);
		}

		return true;
	}

	/**
	 * Delete a User
	 * @param int $extension The user extension
	 * @param {bool} $editmode=false If in edit mode (this is so it doesnt destroy the AsteriskDB)
	 */
	public function delUser($extension, $editmode=false) {
		global $db;
		global $amp_conf;
		global $astman;

		//delete from devices table
		$sql = "DELETE FROM users WHERE extension = ?";
		$sth = $this->database->prepare($sql);
		try {
			$sth->execute(array($extension));
		} catch(\Exception $e) {
			die_freepbx($e->getMessage().$sql);
		}

		//delete details to astdb
		$astman = $this->FreePBX->astman;
		if($astman)  {
			$astman->database_del("AMPUSER",$extension."/screen");
		}
		if ($astman && !$editmode) {
			// TODO just change this to delete everything
			$astman->database_deltree("AMPUSER/".$extension);
		}

		return true;
	}

	/**
	 * Get User Details
	 * @param int $extension The user number (extension)
	 */
	public function getUser($extension) {
		$sql = "SELECT * FROM users WHERE extension = ?";
		$sth = $this->database->prepare($sql);
		try {
			$sth->execute(array($extension));
			$results = $sth->fetch(\PDO::FETCH_ASSOC);
		} catch(\Exception $e) {
			return array();
		}

		$astman = $this->FreePBX->astman;
		if ($astman) {

			if (function_exists('paging_get_config')) {
				$answermode=$astman->database_get("AMPUSER",$extension."/answermode");
				$results['answermode'] = (trim($answermode) == '') ? 'disabled' : $answermode;
			}

			$cw = $astman->database_get("CW",$extension);
			$results['callwaiting'] = (trim($cw) == 'ENABLED') ? 'enabled' : 'disabled';
			$cid_masquerade=$astman->database_get("AMPUSER",$extension."/cidnum");
			$results['cid_masquerade'] = (trim($cid_masquerade) != "")?$cid_masquerade:$extension;

			$call_screen=$astman->database_get("AMPUSER",$extension."/screen");
			$results['call_screen'] = (trim($call_screen) != "")?$call_screen:'0';

			$pinless=$astman->database_get("AMPUSER",$extension."/pinless");
			$results['pinless'] = (trim($pinless) == 'NOPASSWD') ? 'enabled' : 'disabled';

			$results['ringtimer'] = (int) $astman->database_get("AMPUSER",$extension."/ringtimer");

			$results['cfringtimer'] = (int) $astman->database_get("AMPUSER",$extension."/cfringtimer");
			$results['concurrency_limit'] = (int) $astman->database_get("AMPUSER",$extension."/concurrency_limit");

			$results['dialopts'] = $astman->database_get("AMPUSER",$extension."/dialopts");

			$results['recording_in_external'] = strtolower($astman->database_get("AMPUSER",$extension."/recording/in/external"));
			$results['recording_out_external'] = strtolower($astman->database_get("AMPUSER",$extension."/recording/out/external"));
			$results['recording_in_internal'] = strtolower($astman->database_get("AMPUSER",$extension."/recording/in/internal"));
			$results['recording_out_internal'] = strtolower($astman->database_get("AMPUSER",$extension."/recording/out/internal"));
			$results['recording_ondemand'] = strtolower($astman->database_get("AMPUSER",$extension."/recording/ondemand"));
			$results['recording_priority'] = (int) $astman->database_get("AMPUSER",$extension."/recording/priority");

		} else {
			throw new Exception("Cannot connect to Asterisk Manager with ".$this->FreePBX->Config->get("AMPMGRUSER")."/".$this->FreePBX->Config->get("AMPMGRPASS"));
		}
		return $results;
	}

	/**
	 * Get Device Details
	 * @param {int} $account The Device ID
	 */
	public function getDevice($account) {
		$sql = "SELECT * FROM devices WHERE id = ?";
		$sth = $this->database->prepare($sql);
		try {
			$sth->execute(array($account));
			$device = $sth->fetch(\PDO::FETCH_ASSOC);
		} catch(\Exception $e) {
			return array();
		}

		if (empty($device)) {
			return array();
		}

		$t = $device['tech'];
		$tech = array();
		if(isset($this->drivers[$t])) {
			$tech = $this->drivers[$t]->getDevice($account);
		}

		$results = array_merge($device,$tech);

		return $results;
	}
}

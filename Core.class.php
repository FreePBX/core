<?php
// vim: set ai ts=4 sw=4 ft=php:

class Core extends FreePBX_Helpers implements BMO  {

	public function __construct($freepbx = null) {

		parent::__construct($freepbx);
		//Hackery-Jackery for Core only really
		if(!class_exists('PJSip') && file_exists(__DIR__.'/functions.inc/PJSip.class.php')) {
			include(__DIR__.'/functions.inc/PJSip.class.php');
			$this->FreePBX->PJSip = new PJSip($this->FreePBX);
		}
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
		if ($page == "astmodules") {
			foreach ($_REQUEST as $key => $var) {
				// Do they want to delete something?
				if (preg_match("/^delete-(.+)-(.+)$/", $key, $match)) {
					// Note - we base64 encode, to ensure -, _ and . don't get mangled by the browser.
					// print "You want to delete ".base64_decode($match[2])." from ".$match[1]."<br />\n";
					if ($match[1] == "noload") {
						$this->ModulesConf->removenoload(base64_decode($match[2]));
					} elseif ($match[1] == "preload") {
						$this->ModulesConf->removepreload(base64_decode($match[2]));
					} else {
						print "Unsupported section ".$match[1]."<br />\n";
					}
					// Or, they may want to add something..
				} elseif (preg_match("/^add-(.+)$/", $key, $match)) {
					$section = $match[1];
					if (!isset($_REQUEST["new-$section"])) {
						// Post was blank, or didn't exist at all.
						return;
					} else {
						$add = $_REQUEST["new-$section"];
					}

					// Now, actually add it.
					if ($section == "noload") {
						$this->ModulesConf->noload($add);
					} elseif ($section == "preload") {
						$this->ModulesConf->preload($add);
					} else {
						print "Unsupported section ".$section."<br />\n";
					}
				}
			} // foreach
		} // $page == "astmodules"
	}

	public function convertRequest2Array($account,$tech,&$flag) {
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

	public function addDevice($id,$tech,$settings=array(),$editmode=false) {
		global $amp_conf;
		global $astman;
		global $db;

		if ($tech == '' || trim($tech) == 'virtual') {
			return true;
		}

		if (trim($id) == '' || empty($settings)) {
			return false;
		}

		//ensure this id is not already in use
		$dev = $this->getDevice($id);
		if(!empty($dev)) {
			return false;
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
		$sth = FreePBX::Database()->prepare($sql);
		try {
			$sth->execute(array($id,$tech,$settings['dial']['value'],$settings['devicetype']['value'],$settings['user']['value'],$settings['description']['value'],$settings['emergency_cid']['value']));
		} catch(\Exception $e) {

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
			if($emergency_cid != '') {
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
			die_freepbx("Cannot connect to Asterisk Manager with ".FreePBX::Config()->get('AMPMGRUSER')."/".FreePBX::Config()->get('AMPMGRPASS'));
		}

		// create a voicemail symlink if needed
		$thisUser = core_users_get($settings['user']['value']);
		if(isset($thisUser['voicemail']) && ($thisUser['voicemail'] != "novm")) {
			if(empty($thisUser['voicemail'])) {
				$vmcontext = "default";
			} else {
				$vmcontext = $thisUser['voicemail'];
			}

			//voicemail symlink
			$spooldir = FreePBX::Config()->get('ASTSPOOLDIR');
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
		switch($tech) {
			case 'pjsip':
			case 'sip':
				$sql = 'INSERT INTO sip (id, keyword, data, flags) values (?,?,?,?)';
				$sth = $this->FreePBX->Database->prepare($sql);
				foreach($settings as $key => $setting) {
					try {
						$sth->execute(array($id,$key,$setting['value'],$setting['flag']));
					} catch(\Exception $e) {
						die_freepbx($e->getMessage()."<br><br>".'error adding to SIP table');
					}
				}
			break;
			case 'iax2':
				$sql = 'INSERT INTO iax (id, keyword, data, flags) values (?,?,?,?)';
				$sth = $this->FreePBX->Database->prepare($sql);
				foreach($settings as $key => $setting) {
					try {
						$sth->execute(array($id,$key,$setting['value'],$setting['flag']));
					} catch(\Exception $e) {
						die_freepbx($e->getMessage()."<br><br>".'error adding to IAX2 table');
					}
				}
			break;
			case 'zap':
				$sql = 'INSERT INTO zap (id, keyword, data) values (?,?,?)';
				$sth = $this->FreePBX->Database->prepare($sql);
				foreach($settings as $key => $setting) {
					try {
						$sth->execute(array($id,$key,$setting['value']));
					} catch(\Exception $e) {
						die_freepbx($e->getMessage()."<br><br>".'error adding to ZAP table');
					}
				}
			break;
			case 'dahdi':
				$sql = 'INSERT INTO dahdi (id, keyword, data) values (?,?,?)';
				$sth = $this->FreePBX->Database->prepare($sql);
				foreach($settings as $key => $setting) {
					try {
						$sth->execute(array($id,$key,$setting['value']));
					} catch(\Exception $e) {
						die_freepbx($e->getMessage()."<br><br>".'error adding to DAHDI table');
					}
				}
			break;
		}
		return true;
	}

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
			$sth = $this->FreePBX->Database->prepare($sql);
			try {
				$sth->execute(array($account));
			} catch(\Exception $e) {
			}

			//voicemail symlink
			$spooldir = FreePBX::Config()->get('ASTSPOOLDIR');
			if(file_exists($spooldir."/voicemail/device/".$account)) {
				exec("rm -f ".$spooldir."/voicemail/device/".$account);
			}
		} else {
			die_freepbx("Cannot connect to Asterisk Manager with ".FreePBX::Config()->get("AMPMGRUSER")."/".FreePBX::Config()->get("AMPMGRPASS"));
		}

		switch($devinfo['tech']) {
			case 'pjsip':
			case 'sip':
				$type = 'sip';
			break;
			case 'iax2':
				$type = 'iax';
			break;
			case 'zap':
				$type = 'zap';
			break;
			case 'dahdi':
				$type = 'dahdi';
			break;
			default:
				return false;
			break;
		}
		$sql = "DELETE FROM ".$type." WHERE id = ?";
		$sth = $this->FreePBX->Database->prepare($sql);
		try {
			$sth->execute(array($account));
		} catch(\Exception $e) {
			die_freepbx($e->getMessage().$sql);
		}
		return true;
	}

	public function getDevice($account) {
		$sql = "SELECT * FROM devices WHERE id = ?";
		$sth = $this->FreePBX->Database->prepare($sql);
		try {
			$sth->execute(array($account));
			$device = $sth->fetch(PDO::FETCH_ASSOC);
		} catch(\Exception $e) {
			return array();
		}

		if (empty($device)) {
			return array();
		}

		switch($device['tech']) {
			case 'pjsip':
			case 'sip':
				$type = 'sip';
			break;
			case 'iax2':
				$type = 'iax';
			break;
			case 'zap':
				$type = 'zap';
			break;
			case 'dahdi':
				$type = 'dahdi';
			break;
			default:
				return array();
			break;
		}
		$sql = "SELECT keyword,data FROM ".$type." WHERE id = ?";
		$sth = $this->FreePBX->Database->prepare($sql);
		try {
			$sth->execute(array($account));
			$tech = $sth->fetchAll(PDO::FETCH_COLUMN|PDO::FETCH_GROUP);
			//reformulate into what is expected
			//This is in the try catch just for organization
			foreach($tech as &$value) {
				$value = $value[0];
			}
		} catch(\Exception $e) {
			return array();
		}

		$results = array_merge($device,$tech);

		return $results;
	}
}

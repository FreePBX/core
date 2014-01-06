<?php
// vim: set ai ts=4 sw=4 ft=php:

class PJSip implements BMO {

	private $codecs = array(
		"g722" => false,
		"ulaw" => true,
		"alaw" => true,
		"gsm" => false,
	);
	private $PJSipModules = array("chan_pjsip.so", "res_pjsip_endpoint_identifier_anonymous.so", "res_pjsip_messaging.so",
		"res_pjsip_pidf.so", "res_pjsip_session.so", "func_pjsip_endpoint.so", "res_pjsip_endpoint_identifier_ip.so", "res_pjsip_mwi.so",
		"res_pjsip_pubsub.so", "res_pjsip.so", "res_pjsip_acl.so", "res_pjsip_endpoint_identifier_user.so", "res_pjsip_nat.so",
		"res_pjsip_refer.so", "res_pjsip_t38.so", "res_pjsip_authenticator_digest.so", "res_pjsip_exten_state.so", "res_pjsip_notify.so",
		"res_pjsip_registrar_expire.so", "res_pjsip_transport_websocket.so", "res_pjsip_caller_id.so", "res_pjsip_header_funcs.so",
		"res_pjsip_one_touch_record_info.so", "res_pjsip_registrar.so", "res_pjsip_diversion.so", "res_pjsip_log_forwarder.so", 
		"res_pjsip_outbound_authenticator_digest.so", "res_pjsip_rfc3326.so", "res_pjsip_dtmf_info.so", "res_pjsip_logger.so",
		"res_pjsip_outbound_registration.so", "res_pjsip_sdp_rtp.so");

	public function __construct($freepbx = null) {
		if ($freepbx == null)
			throw new Exception("Not given a FreePBX Object");

		$this->FreePBX = $freepbx;
		$this->db = $freepbx->Database;

		// Add Core_SipSettings class
		if (!class_exists("Core_SipSettings"))
			include "Core_SipSettings.class.php";

		$this->Core_SipSettings = new Core_SipSettings($this->FreePBX);

	}

	private function getAllDevs() {
		$alldevices = $this->db->query("SELECT * FROM devices WHERE tech = 'pjsip'",PDO::FETCH_ASSOC);
		$devlist = array();
		foreach($alldevices as $device) {
			$id = $device['id'];
			// Have we already prepared our query?
			$q = $this->db->prepare("SELECT * FROM `sip` WHERE `id` = :id");

			$q->execute(array(":id" => $id));
			$data = $q->fetchAll(PDO::FETCH_ASSOC);

			$devlist[$id] = $device;

			foreach($data as $setting) {
				$devlist[$id][$setting['keyword']] = $setting['data'];
			}
		}
		return $devlist;
	}

	// Return an array consisting of all SIP devices, Trunks, or both.
	private function getAllOld($type = null) {
		$allkeys = $this->db->query("SELECT DISTINCT(`id`) FROM `sip`");
		$out = $allkeys->fetchAll(PDO::FETCH_ASSOC);
		foreach ($out as $res) {
			if (strpos($res['id'], "tr-") === false) {
				// This isn't a trunk. 
				// Do we want stuff that's not a trunk?
				if (!$type || $type == "devices") {
					$retarr['device'][] = $res['id'];
				}
			} else {
				// This IS a trunk
				if (preg_match("/^tr-.+-(\d+)/", $res['id'], $output)) {
					if (!$type || $type == "trunks") {
						$retarr['trunk'][] = $output[1];
					}
				} else {
					throw new Exception("I have no idea what ".$res['id']." is.");
				}
			}
		}
		if (isset($retarr)) {
			return $retarr;
		} else {
			return array();
		}
	}

	// Grab an Old Extension from the existing database
	private function getExtOld($ext = null) {

		// Careful - 0 is, sorta kinda, a valid device.
		if ($ext === null)
			throw new Exception("No Device given to getExtOld");

		// Have we already prepared our query?
		if (!isset($this->getExtOldQuery)) {
			$this->getExtOldQuery = $this->db->prepare("SELECT * FROM `sip` WHERE `id` = :id");
		}

		$this->getExtOldQuery->execute(array(":id" => $ext));
		$output = $this->getExtOldQuery->fetchAll(PDO::FETCH_ASSOC);

		// Tidy up the return
		foreach ($output as $entry) {
			$retarr[$entry['keyword']] = $entry['data'];
		}

		if (isset($retarr)) {
			return $retarr;
		} else {
			// return array();
			throw new Exception("Old SIP Device $ext not found");
		}
	}

	public function getTransportConfigs() {
		//
		// Grab settings from sipsettings module.
		//

		// Cache
		if (isset($this->TransportConfigCache))
			return $this->TransportConfigCache;

		$binds = $this->FreePBX->Sipsettings->getConfig("binds");

		foreach ($binds as $protocol => $arr) {
			foreach ($arr as $ip => $on) {
				$t = "$ip-$protocol";
				$transport[$t]['type'] = "transport";
				$transport[$t]['protocol'] = $protocol;
				$port = $this->FreePBX->Sipsettings->getConfig($protocol."port-$ip");
				if (!$port) {
					$transport[$t]['bind'] = "$ip";
				} else {
					$transport[$t]['bind'] = "$ip:$port";
				}
				$extip = $this->FreePBX->Sipsettings->getConfig($protocol."extip-$ip");
				if ($extip) {
					$transport[$t]['external_media_address'] = $extip;
					$transport[$t]['external_signaling_address'] = $extip;
				}
				$localnet = $this->FreePBX->Sipsettings->getConfig($protocol."localnet-$ip");
				if ($localnet) {
					$transport[$t]['local_net'] = $localnet;
				}
			}
		}

		$this->TransportConfigCache = $transport;
		return $transport;
	}

	private function generateEndpoints() {
		// More Efficent Function here.
		foreach ($this->getAllDevs() as $dev) {
			$this->generateEndpoint($dev, $retarr);
		}

		// Check to see if 'Allow Guest' is enabled in SIP Settings. If it is,
		// we need to create the magic 'anonymous' endpoint.
		$allowguest = $this->db->getOne('SELECT `data` FROM `sipsettings` WHERE `keyword`="allowguest"');
		if ($allowguest == 'yes') {
			$endpoint[] = "type=endpoint";
			// Do we have a custom contet for anon calls to go to?
			$context = $this->db->getOne('SELECT `data` FROM `sipsettings` WHERE `keyword`="context"');
			if (empty($context))
				$context = "from-sip-external";
			$endpoint[] = "context=$context";
			$endpoint[] = "allow=all";
			$endpoint[] = "transport=udp,tcp,ws,wss";
			$retarr["pjsip.endpoint.conf"]["anonymous"] = $endpoint;
		}

		return $retarr;
	}

	private function generateEndpoint($config, &$retarr) {
		// Validate $config array
		$this->validateEndpoint($config);

		if($config['sipdriver'] != 'chan_pjsip') {
			return false;
		}

		// With pjsip, we need three sections. 
		$endpointname = $config['account'];
		$endpoint[] = "type=endpoint";
		$authname = "$endpointname-auth";
		$auth[] = "type=auth";
		$aorname = "$endpointname";
		$aor[] = "type=aor";

		// Endpoint
		$endpoint[] = "aors=$aorname";
		$endpoint[] = "auth=$authname";

		$endpoint[] = "allow=".$config['allow'];

		if (!empty($config['disallow']))
			$endpoint[] = "disallow=".$config['disallow'];

		$endpoint[] = "context=".$config['context'];
		$endpoint[] = "callerid=".$config['callerid'];
		$endpoint[] = "dtmf_mode=".$config['dtmfmode'];
		$endpoint[] = "mailboxes=".$config['mailbox'];
		//check transport to make sure it's valid
		$trans = array_keys($this->getTransportConfigs());
		if(!in_array($config['transport'],$trans)) {
			throw new Exception('Invalid Transport Defined on device '.$endpointname);
		}
		$endpoint[] = "transport=".$config['transport'];
		if (!empty($config['call_group']))
			$endpoint[] = "call_group=".$config['callgroup'];

		if (!empty($config['pickup_group']))
			$endpoint[] = "pickup_group=".$config['pickupgroup'];

		if (!empty($config['avpf']))
			$endpoint[] = "use_avpf=".$config['avpf'];

		if (!empty($config['icesupport']))
			$endpoint[] = "ice_support=".$config['icesupport'];

		if (!empty($config['trustrpid']))
			$endpoint[] = "trust_id_inbound=".$config['trustrpid'];

		if (isset($config['sendrpid'])) {
			if ($config['sendrpid'] == "yes" || $config['sendrpid'] == "both") {
				$endpoint[] = "send_rpid=yes";
			}
			if ($config['sendrpid'] == "pai" || $config['sendrpid'] == "both") {
				$endpoint[] = "send_pai=yes";
			}
		}
		
		//rtp_symmetric needs to be yes for NAT --mjordan, Digium
		$endpoint[] = !empty($config['rtp_symmetric']) ? "rtp_symmetric=".$config['rtp_symmetric'] : "rtp_symmetric=yes";
		//rewrite_contact needs to be yes for NAT --mjordan, Digium
		$endpoint[] = !empty($config['rewrite_contact']) ? "rewrite_contact=".$config['max_contacts'] : "rewrite_contact=yes";

		// Auth
		$auth[] = "auth_type=userpass";
		$auth[] = "password=".$config['secret'];
		$auth[] = "username=".$config['username'];

		// AOR
		// check for 0? allow 0? why?
		$aor[] = !empty($config['max_contacts']) ? "max_contacts=".$config['max_contacts'] : "max_contacts=1";

		//If remove existing hasn't been defined then set to yes, which is not the default but makes pjsip act like chan_sip
		$aor[] = !empty($config['remove_existing']) ? "remove_existing=".$config['remove_existing'] : "remove_existing=yes";

		if (!empty($config['qualifyfreq']))
			$aor[] = "qualify_frequency=".$config['qualifyfreq'];

		if (isset($retarr["pjsip.endpoint.conf"][$endpointname]))
			throw new Exception("Endpoint $endpointname already exists.");
		$retarr["pjsip.endpoint.conf"][$endpointname] = $endpoint;

		if (isset($retarr["pjsip.auth.conf"][$authname]))
			throw new Exception("Auth $authname already exists.");
		$retarr["pjsip.auth.conf"][$authname] = $auth;

		if (isset($retarr["pjsip.aor.conf"][$aorname]))
			throw new Exception("AOR $aorname already exists.");
		$retarr["pjsip.aor.conf"][$aorname] = $aor; 
	}

	private function validateEndpoint(&$config) {

		// Currently unported: 
		//   accountcode, callgroup, 

		// DTMF Mode has changed.
		if ($config['dtmfmode'] == "rfc2833")
			$config['dtmfmode'] = "rfc4733";

		// 'username' is for when username != exten.
		if (!isset($config['username']))
			$config['username'] = $config['account'];

		// Codec allow is now mandatory
		if (empty($config['allow'])) {
			$config['allow'] = $this->getDefaultSIPCodecs();
		}

	}

	public function getDefaultSIPCodecs() {
		// Grab the default Codecs from the sipsettings module. 
		if (isset($this->DefaultSipCodecs))
			return $this->DefaultSipCodecs;

		// If module_exists('sipsettings') ..
		$codecsquery = $this->db->query('SELECT `keyword` from `sipsettings` WHERE `type`=1 AND `data` <> ""  ORDER BY `data`');
		$codecs = $codecsquery->fetchAll(PDO::FETCH_NUM);
		foreach ($codecs as $res) {
			$codecarr[] = $res[0];
		}
		if (empty($codecarr))
			throw new Exception("No SIP Codecs defined. This will never work.");

		$this->DefaultSipCodecs = join(",", $codecarr);
		return $this->DefaultSipCodecs;
	}

	/* Assorted stubs to validate the BMO Interface */
	public function install() {}
		public function uninstall() {}
		public function backup() {}
		public function restore($config) {}
		public function showPage($request) { return false; }

		/* Hook definitions */
		public static function xmyGuiHooks() { return array("core", "INTERCEPT" => "modules/sipsettings/page.sipsettings.php"); }
		public static function myConfigPageInits() { return array("trunks"); }

		/* Hook Callbacks */
		public function doGuiIntercept($filename, &$text) {
			if ($filename == "modules/sipsettings/page.sipsettings.php") {
				$this->Core_SipSettings->doPage("page.sipsettings.php", $text);
			} else {
				throw new Exception("doGuiIntercept was called with $filename. This shouldn't ever happen");
			}
		}

	public function doGuiHook(&$currentconfig) {
		return true;
	}

	public function doConfigPageInit($page) {
		if (isset($_REQUEST['tech']) && strtoupper($_REQUEST['tech']) == 'PJSIP') {
			//print "PJSip was called with $page<br />";
			//print_r($_REQUEST);
		}
	}

	public function getConfig() {

		$conf = $this->generateEndpoints();

		// Generate includes
		$pjsip = "#include pjsip.custom.conf\n#include pjsip.transports.conf\n#include pjsip.endpoint.conf\n#include pjsip.aor.conf\n";
		$pjsip .= "#include pjsip.auth.conf\n#include pjsip.manualtrunks.conf\n#include pjsip.registration.conf\n#include pjsip.identify.conf\n";
		$conf['pjsip.conf'][] = $pjsip;

		// Transports are a multi-dimensional array, because
		// we use it earlier to match extens with transports
		// So we need to flatten it to something that can be
		// written to a file.
		$transports = $this->getTransportConfigs();
		foreach ($transports as $transport => $entries) {
			$tmparr = array();
			foreach ($entries as $key => $val) {
				$tmparr[] = "$key=$val";
			}
			$conf['pjsip.transports.conf'][$transport] = $tmparr;
		}

		//TODO: Rob can we fix this please?
		global $version;
		$conf['pjsip.conf']['global'] = array(
			'type=global',
			'user_agent='.$this->FreePBX->Config->get_conf_setting('SIPUSERAGENT') . '-' . getversion() . "($version)"
		);

		$trunks = $this->getAllTrunks();

		foreach($trunks as $trunk) {
			$tn = $trunk['trunk_name'];
			//prevent....special people
			$trunk['sip_server_port'] = !empty($trunk['sip_server_port']) ? $trunk['sip_server_port'] : '5060';
			$conf['pjsip.registration.conf'][$tn] = array(
				'type' => 'registration',
				'transport' => $trunk['transport'],
				'outbound_auth' => $tn,
				'retry_interval' => $trunk['retry_interval'],
				'expiration' => $trunk['expiration'],
				'auth_rejection_permanent' => ($trunk['auth_rejection_permanent'] == 'on') ? 'yes' : 'no'
			);
			if(!empty($trunk['contact_user']))
				$conf['pjsip.registration.conf'][$tn]['contact_user'] = $trunk['contact_user'];

			if(empty($trunk['configmode']) || $trunk['configmode'] == 'simple') {
				if(empty($trunk['sip_server'])) {
					throw new Exception('Asterisk will crash if sip_server is blank!');
				}
				$conf['pjsip.registration.conf'][$tn]['server_uri'] = 'sip:'.$trunk['sip_server'].':'.$trunk['sip_server_port'];
				$conf['pjsip.registration.conf'][$tn]['client_uri'] = 'sip:'.$trunk['username'].'@'.$trunk['sip_server'].':'.$trunk['sip_server_port'];
			} else {
				if(empty($trunk['server_uri']) || $trunk['client_uri']) {
					throw new Exception('Asterisk will crash if server_uri or client_uri is blank!');
				}
				$conf['pjsip.registration.conf'][$tn]['server_uri'] = $trunk['server_uri'];
				$conf['pjsip.registration.conf'][$tn]['client_uri'] = $trunk['client_uri'];
			}

			$conf['pjsip.auth.conf'][$tn] = array(
				'type' => 'auth',
				'auth_type' => 'userpass',
				'password' => $trunk['secret'],
				'username' => $trunk['username']
			);

			$conf['pjsip.aor.conf'][$tn] = array(
				'type' => 'aor',
				'qualify_frequency' => !empty($trunk['qualify_frequency']) ? $trunk['qualify_frequency'] : 60
			);
			if(empty($trunk['configmode']) || $trunk['configmode'] == 'simple') {
				$conf['pjsip.aor.conf'][$tn]['contact'] = 'sip:'.$trunk['sip_server'].':'.$trunk['sip_server_port'];
			} else {
				$conf['pjsip.aor.conf'][$tn]['contact'] = $trunk['aor_contact'];
			}

			$conf['pjsip.endpoint.conf'][$tn] = array(
				'type' => 'endpoint',
				'transport' => !empty($trunk['transport']) ? $trunk['transport'] : 'udp',
				'context' => !empty($trunk['context']) ? $trunk['context'] : 'from-pstn',
				'disallow' => 'all',
				'allow' => !empty($trunk['codecs']) ? $trunk['codecs'] : 'ulaw',
				'outbound_auth' => $tn,
				'aors' => $tn
			);

			$conf['pjsip.identify.conf'][$tn] = array(
				'type' => 'identify',
				'endpoint' => $tn,
				'match' => $trunk['sip_server']
			);
		}
		return $conf;
	}

	public function writeConfig($conf) {
		//TODO: Rob please remove this global
		//we also need to do port checking and if in chan sip mode port on 5060, if in both mode then put if on 5061
		global $astman,$version;
		$nt = notifications::create($db);

		if(version_compare($version, '12', 'ge')) {
			if($this->FreePBX->Config->get_conf_setting('ASTSIPDRIVER') == 'both') {
				$this->FreePBX->ModulesConf->removenoload("chan_sip.so");
				foreach ($this->PJSipModules as $mod)
					$this->FreePBX->ModulesConf->removenoload($mod);
			} elseif($this->FreePBX->Config->get_conf_setting('ASTSIPDRIVER') == 'chan_pjsip') {
				$this->enablePJSipModules();
			} elseif($this->FreePBX->Config->get_conf_setting('ASTSIPDRIVER') == 'chan_sip') {
				$this->disablePJSipModules();
			}
		} else {
			$sip_missing = _("PJSIP Not Supported");
			$sip_missing_desc = _("Your SIP Channel Driver (ASTSIPDRIVER) was automatically changed from %s to chan_sip because chan_pjsip is not supported on your Asterisk installation");
			$nt->add_notice('framework', 'ASTSIPDRIVERCHG', $sip_missing, sprintf($sip_missing_desc,$this->FreePBX->Config->get_conf_setting('ASTSIPDRIVER')));
			$this->FreePBX->Config->set_conf_values(array('ASTSIPDRIVER' => 'chan_sip'), true, true);
			$nt->delete('framework', 'ASTSIPDRIVERMISSING');
		}

		$this->FreePBX->WriteConfig($conf);
	}

	private function enablePJSipModules() {
		// We need to DISABLE chan_sip.so, and remove any noload lines for the pjsip stuff.
		//
		// This is just to save typing. I'm lazy. 
		$m = $this->FreePBX->ModulesConf;

		$m->noload("chan_sip.so");
		foreach ($this->PJSipModules as $mod)
			$m->removenoload($mod);
	}

	private function disablePJSipModules() {
		// We need to ENABLE chan_sip.so, and add all the noload lines for the pjsip stuff.
		//
		// This is just to save typing. I'm lazy. 
		$m = $this->FreePBX->ModulesConf;

		$m->removenoload("chan_sip.so");
		foreach ($this->PJSipModules as $mod)
			$m->noload($mod);
	}

	public function addTrunk($trunknum) {
		// These are the vars we DON'T care about that are being submitted from the PJSip page
		$ignore = array('display', 'action', 'Submit', 'prepend_digit', 'pattern_prefix', 'pattern_pass');
		// We care about the arrays later

		$_REQUEST['codecs'] = implode(",",array_keys($_REQUEST['codec']));
		$ins = $this->db->prepare("INSERT INTO `pjsip` (`id`, `keyword`, `data`, `flags`) VALUES ( $trunknum, :keyword, :data, 0 )");
		foreach ($_REQUEST as $k => $v) {
			// Skip this value if we don't care about it.
			if (in_array($k, $ignore))
				continue;

			// Otherwise, we can insert it.
			$ins->bindParam(':keyword', $k);
			$ins->bindParam(':data', $v);
			$ins->execute();
		}

		// TODO: prepend, pattern_prefix and pattern_pass
	}

	public function getAllTrunks() {
		$get = $this->db->prepare("SELECT id, keyword, data FROM pjsip");
		$get->execute();
		$result = $get->fetchAll(PDO::FETCH_ASSOC);
		$final = array();
		foreach($result as $values) {
			$final[$values['id']][$values['keyword']] = $values['data'];
		}
		return $final;
	}

	public function getActiveTransports() {
		$tports = array();
		foreach(array_keys($this->getTransportConfigs()) as $tran) {
			$tports[] = array(
				'value' => $tran,
				'text' => $tran
			);
		}
		return $tports;
	}

	public function getDisplayVars($trunkid, &$dispvars) {
		if(!empty($trunkid)) {
			$get = $this->db->prepare("SELECT keyword, data FROM pjsip WHERE id = :id");
			$get->bindParam(':id', str_replace('OUT_','',$trunkid));
			$get->execute();
			$result = $get->fetchAll(PDO::FETCH_COLUMN|PDO::FETCH_GROUP);
			foreach($result as $key => $val) {
				$dispvars[$key] = $val[0];
			}

			$codecs = explode(",",$dispvars['codecs']);
			$dispvars['codecs'] = array();
			foreach($codecs as $codec) {
				$dispvars['codecs'][$codec] = true;
			}

			foreach($this->codecs as $codec => $state) {
				if(!isset($dispvars['codecs'][$codec])) {
					$dispvars['codecs'][$codec] = false;
				}
			}
			
			$dispvars['qualify_frequency'] = !empty($dispvars['qualify_frequency']) ? $dispvars['qualify_frequency'] : 60;
		} else {
			$dispvars = array(
				"auth_rejection_permanent" => "on",
				"expiration" => 3600,
				"retry_interval" => 60,
				"forbidden_retry_interval" => 10,
				"max_retries" => 10,
				"context" => "from-pstn",
				"transport" => null,
				"codecs" => $this->codecs,
				"qualify_frequency" => 60
			);
		}
		$dispvars['transports'] = array_keys($this->getTransportConfigs());
	}
}

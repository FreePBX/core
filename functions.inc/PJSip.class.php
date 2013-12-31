<?php
// vim: set ai ts=4 sw=4 ft=php:

class PJSip implements BMO {

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
		$sipsettings = $this->db->query('SELECT `keyword`, `data` FROM `sipsettings` WHERE `type`=0');
		$settings = $sipsettings->fetchAll(PDO::FETCH_ASSOC);

		foreach($settings as $row) {
			$sip[$row['keyword']] = $row['data'];
		}

		if (empty($sip['bindaddr'])) {
			$bind = "0.0.0.0";
		} else {
			$bind = $sip['bindaddr'];
		}

		if (empty($sip['bindport'])) {
			$port = "5060";
		} else {
			$port = $sip['bindport'];
		}

		$transport['udp'] = array( "protocol" => "udp", "bind" => "$bind:$port", "type" => "transport");

		// Do we know about NAT?
		if (isset($sip['nat']) && $sip['nat'] != 'never') {
			// At Asterisk 12-b1, only one local_net works.
			if (isset($sip['localnet_1']))
				throw new Exception('Only one local net supported with PJSip');

			$transport['udp'] =
				// FIXME - localnet needs to have its subnet calculated
				array( 
					"type" => "transport", "protocol" => "udp", "bind" => "$bind:$port", 
					"local_net" => $sip['localnet_0']."/24", "external_media_address" => $sip['externip_val'],
					"external_signaling_address" => $sip['externip_val']
			);
		}

		$transport['tcp'] = array( "protocol" => "tcp", "bind" => "$bind:$port", "type" => "transport");
		$transport['ws'] = array( "protocol" => "ws", "bind" => $bind, "type" => "transport");
		$transport['wss'] = array( "protocol" => "wss", "bind" => $bind, "type" => "transport");

		// Add TLS Configuration here.
		// $transport['tls'] = array( "protocol" => "tls", "bind" => "$bind:", "type" => "transport");
		// $transport['tls] = $this->getTLSConfig();
		return $transport;
	}

	private function generateEndpoints() {
		// Only old stuff for the moment.
		$allEndpoints = $this->getAllOld("devices");

		foreach ($allEndpoints['device'] as $dev)
			$this->generateEndpoint($this->getExtOld($dev), $retarr);

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

		// Auth
		$auth[] = "auth_type=userpass";
		$auth[] = "password=".$config['secret'];
		$auth[] = "username=".$config['username'];

		// AOR
		$aor[] = "max_contacts=1";

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
	public static function myGuiHooks() { return array("core", "INTERCEPT" => "modules/sipsettings/page.sipsettings.php"); }
	public static function myConfigPageInits() { return array("trunks"); }

	/* Hook Callbacks */
	public function doGuiIntercept($filename, &$text) {
		if ($filename == "modules/sipsettings/page.sipsettings.php") {
			$foo = split("\n", $text);
			$header = array_shift($foo);
			$str = "Asterisk is currently using <strong>".$this->FreePBX->Config->get_conf_setting('ASTSIPDRIVER')."</strong> for SIP Traffic.<br />You can change this on the Advanced Settings Page<br />\n";
			array_unshift($foo, $header, $str);
			$text = implode("\n", $foo);
		} else {
			throw new Exception("doGuiIntercept was called with $filename. This shouldn't ever happen");
		}
	}

	public function doGuiHook(&$currentconfig) {
		return true;
	}

	public function doConfigPageInit($page) {
		if (isset($_REQUEST['tech']) && strtoupper($_REQUEST['tech']) == 'PJSIP') {
			print "PJSip was called with $page<br />";
			print_r($_REQUEST);
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

		$conf['pjsip.registration.conf']['mytrunk1'] = array(
			'type=registration',
			'transport=udp',
			'outbound_auth=mytrunk1',
			'server_uri=sip:<user>@trunk1.freepbx.com:5060',
			'client_uri=sip:<user>@<localip>:5060',
			'retry_interval=60',
			'expiration=60'
		);
		$conf['pjsip.auth.conf']['mytrunk1'] = array(
			'type=auth',
			'auth_type=userpass',
			'password=<pass>',
			'username=<secret>'
		);
		$conf['pjsip.aor.conf']['mytrunk1'] = array(
			'type=aor',
			'contact=trunk1.freepbx.com',
			
		);
		$conf['pjsip.endpoint.conf']['mytrunk1'] = array(
			'type=endpoint',
			'transport=udp',
			'context=from-pstn',
			'disallow=all',
			'allow=ulaw',
			'outbound_auth=mytrunk1',
			'aors=mytrunk1'
		);
		$conf['pjsip.identify.conf']['mytrunk1'] = array(
			'type=identify',
			'endpoint=mytrunk1',
			'match=trunk1.freepbx.com'
			
		);
		
		$conf['pjsip.registration.conf']['mytrunk2'] = array(
			'type=registration',
			'transport=udp',
			'outbound_auth=mytrunk2',
			'server_uri=sip:<user>@trunk2.freepbx.com:5060',
			'client_uri=sip:<user>@<localip>:5060',
			'retry_interval=60',
			'expiration=60'
		);
		$conf['pjsip.auth.conf']['mytrunk2'] = array(
			'type=auth',
			'auth_type=userpass',
			'password=<pass>',
			'username=<user>'
		);
		$conf['pjsip.aor.conf']['mytrunk2'] = array(
			'type=aor',
			'contact=trunk2.freepbx.com',
			
		);
		$conf['pjsip.endpoint.conf']['mytrunk2'] = array(
			'type=endpoint',
			'transport=udp',
			'context=from-pstn',
			'disallow=all',
			'allow=ulaw',
			'outbound_auth=mytrunk2',
			'aors=mytrunk2'
		);
		$conf['pjsip.identify.conf']['mytrunk2'] = array(
			'type=identify',
			'endpoint=mytrunk2',
			'match=trunk2.freepbx.com'
			
		);
		return $conf;
	}

	public function writeConfig($conf) {
		// Check to see if we're enabled
		if ($this->FreePBX->Config->get_conf_setting('ASTSIPDRIVER') == "chan_pjsip") {
			// We're enabled. Let's make sure that chan_sip is disabled.
			$this->enablePJSipModules();
		} else {
			$this->disablePJSipModules();
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

	public function getDisplayVars($trunkid, &$dispvars) {
		$dispvars['client_uri'] = "this should work";
	}

}

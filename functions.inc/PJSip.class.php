<?php
// vim: set ai ts=4 sw=4 ft=php:
namespace FreePBX\modules\Core;
class PJSip extends \FreePBX_Helpers implements \BMO {

	private $PJSipModules = array("chan_pjsip.so", "res_pjsip_endpoint_identifier_anonymous.so", "res_pjsip_messaging.so",
		"res_pjsip_pidf.so", "res_pjsip_session.so", "func_pjsip_endpoint.so", "res_pjsip_endpoint_identifier_ip.so", "res_pjsip_mwi.so",
		"res_pjsip_pubsub.so", "res_pjsip.so", "res_pjsip_acl.so", "res_pjsip_endpoint_identifier_user.so", "res_pjsip_nat.so",
		"res_pjsip_refer.so", "res_pjsip_t38.so", "res_pjsip_authenticator_digest.so", "res_pjsip_exten_state.so", "res_pjsip_notify.so",
		"res_pjsip_registrar_expire.so", "res_pjsip_transport_websocket.so", "res_pjsip_caller_id.so", "res_pjsip_header_funcs.so",
		"res_pjsip_one_touch_record_info.so", "res_pjsip_registrar.so", "res_pjsip_diversion.so", "res_pjsip_log_forwarder.so",
		"res_pjsip_outbound_authenticator_digest.so", "res_pjsip_rfc3326.so", "res_pjsip_dtmf_info.so", "res_pjsip_logger.so",
		"res_pjsip_outbound_registration.so", "res_pjsip_sdp_rtp.so", "res_pjsip_outbound_publish.so");

	private $_endpoint = array();
	private $_auth = array();
	private $_aor = array();
	private $_global = array();
	private $_registration = array();
	private $_identify = array();
	private $version = null;

	public function __construct($freepbx) {
		parent::__construct($freepbx);
		$this->db = $freepbx->Database;
		$this->version = $freepbx->Config->get('ASTVERSION');
	}

	/* Assorted stubs to validate the BMO Interface */
	public function install() {
	}
	public function uninstall() {
	}
	public function backup() {
	}
	public function restore($config) {
	}

	/**
	* Hook definitions
	* @param {string} $filename
	* @param {string} &$text
	*/
	public function doGuiIntercept($filename, &$text) {
		if ($filename == "modules/sipsettings/page.sipsettings.php") {
			// $this->doPage("page.sipsettings.php", $text);
		}
	}

	/**
	* Hook Definitions
	*/
	public function doGuiHook(&$currentconfig) {
		return true;
	}

	/**
	* Do Config Page Init
	* @param {[type]} $page [description]
	*/
	public function doConfigPageInit($page) {
		if (isset($_REQUEST['tech']) && strtoupper($_REQUEST['tech']) == 'PJSIP') {
			//print "PJSip was called with $page<br />";
			//print_r($_REQUEST);
		}
	}

	/**
	* Hook Definitions
	*/
	public function genConfig() {

		//clear before write.
		$conf['pjsip.registration.conf'][] = '#include pjsip.registration_custom.conf';
		$conf['pjsip.auth.conf'][] = '#include pjsip.auth_custom.conf';
		$conf['pjsip.aor.conf'][] = '#include pjsip.aor_custom.conf';
		$conf['pjsip.endpoint.conf'][] = '#include pjsip.endpoint_custom.conf';
		$conf['pjsip.identify.conf'][] = '#include pjsip.identify_custom.conf';
		$conf['pjsip.transports.conf'][] = '#include pjsip.transports_custom.conf';
		//$conf['pjsip.notify.conf'][] = '#include pjsip.notify_custom.conf';
		// Generate includes
		$pjsip = "#include pjsip_custom.conf\n#include pjsip.transports.conf\n#include pjsip.endpoint.conf\n#include pjsip.aor.conf\n";
		$pjsip .= "#include pjsip.auth.conf\n#include pjsip.registration.conf\n#include pjsip.identify.conf\n";
		$conf['pjsip.conf'][] = $pjsip;

		$conf = $this->generateEndpoints($conf);

		// Transports are a multi-dimensional array, because
		// we use it earlier to match extens with transports
		// So we need to flatten it to something that can be
		// written to a file.
		$transports = $this->getTransportConfigs();
		foreach ($transports as $transport => $entries) {
			$tmparr = array();
			foreach ($entries as $key => $val) {
				// Check for multiple defintions of the same var (eg, local_net)
				if (is_array($val)) {
					foreach ($val as $line) {
						$tmparr[] = "$key=$line";
					}
				} else {
					$tmparr[] = "$key=$val";
				}
			}
			$conf['pjsip.transports.conf'][$transport] = $tmparr;
		}

		$conf['pjsip.conf']['global'] = array(
			'type=global',
			'user_agent='.$this->FreePBX->Config->get('SIPUSERAGENT') . '-' . getversion() . "(" . $this->version . ")"
		);
		if(!empty($this->_global)) {
			foreach($this->_global as $el) {
				$conf['pjsip.conf']['global'][] = "{$el['key']}={$el['value']}";
			}
		}

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
			if(!empty($trunk['contact_user'])) {
				$conf['pjsip.registration.conf'][$tn]['contact_user'] = $trunk['contact_user'];
			}

			if(empty($trunk['configmode']) || $trunk['configmode'] == 'simple') {
				if(empty($trunk['sip_server'])) {
					throw new \Exception('Asterisk will crash if sip_server is blank!');
				}
				$conf['pjsip.registration.conf'][$tn]['server_uri'] = 'sip:'.$trunk['sip_server'].':'.$trunk['sip_server_port'];
				$conf['pjsip.registration.conf'][$tn]['client_uri'] = 'sip:'.$trunk['username'].'@'.$trunk['sip_server'].':'.$trunk['sip_server_port'];
			} else {
				if(empty($trunk['server_uri']) || empty($trunk['client_uri'])) {
					throw new \Exception('Asterisk will crash if server_uri or client_uri is blank!');
				}
				$conf['pjsip.registration.conf'][$tn]['server_uri'] = $trunk['server_uri'];
				$conf['pjsip.registration.conf'][$tn]['client_uri'] = $trunk['client_uri'];
			}

			if(!empty($this->_registration[$tn])) {
				foreach($this->_registration[$tn] as $el) {
					$conf["pjsip.registration.conf"][$tn][] = "{$el['key']}={$el['value']}";
				}
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
				$conf['pjsip.aor.conf'][$tn]['contact'] = 'sip:'.$trunk['username'].'@'.$trunk['sip_server'].':'.$trunk['sip_server_port'];
			} else {
				$conf['pjsip.aor.conf'][$tn]['contact'] = $trunk['aor_contact'];
			}
			if(!empty($this->_aor[$tn])) {
				foreach($this->_aor[$tn] as $el) {
					$conf["pjsip.aor.conf"][$tn][] = "{$el['key']}={$el['value']}";
				}
			}

			$conf['pjsip.endpoint.conf'][$tn] = array(
				'type' => 'endpoint',
				'transport' => !empty($trunk['transport']) ? $trunk['transport'] : 'udp',
				'context' => !empty($trunk['context']) ? $trunk['context'] : 'from-pstn',
				'disallow' => 'all',
				'allow' => str_replace('&', ',', !empty($trunk['codecs']) ? $trunk['codecs'] : 'ulaw'), // '&' is invalid in pjsip, valid in chan_sip
				'outbound_auth' => $tn,
				'aors' => $tn
			);
			if(!empty($this->_endpoint[$tn])) {
				foreach($this->_endpoint[$tn] as $el) {
					$conf["pjsip.endpoint.conf"][$tn][] = "{$el['key']}={$el['value']}";
				}
			}

			$conf['pjsip.identify.conf'][$tn] = array(
				'type' => 'identify',
				'endpoint' => $tn,
				'match' => $trunk['sip_server']
			);

			if(!empty($this->_identify[$tn])) {
				foreach($this->_identify[$tn] as $el) {
					$conf["pjsip.identify.conf"][$tn][] = "{$el['key']}={$el['value']}";
				}
			}
		}

		//if we have an additional and custom file for sip_notify, write a pjsip_notify.conf
		$ast_etc_dir = $this->FreePBX->Config->get_conf_setting('ASTETCDIR');
		$ast_sip_notify_additional_conf = $ast_etc_dir . "/sip_notify_additional.conf";
		$ast_sip_notify_custom_conf = $ast_etc_dir . "/sip_notify_custom.conf";
		if (file_exists($ast_sip_notify_additional_conf) && file_exists($ast_sip_notify_custom_conf)) {
			$conf['pjsip_notify.conf'] = "\n#include sip_notify_custom.conf\n#include sip_notify_additional.conf\n";
		}

		return $conf;
	}

	/**
	* Hook Definitions
	* @param {string} $conf The Configuration being passed through
	*/
	public function writeConfig($conf) {
		//we also need to do port checking and if in chan sip mode port on 5060, if in both mode then put if on 5061
		$nt = \notifications::create();

		$ast_sip_driver = $this->FreePBX->Config->get_conf_setting('ASTSIPDRIVER');
		if(version_compare($this->version, '12', 'ge')) {
			if($ast_sip_driver == 'both') {
				$this->FreePBX->ModulesConf->removenoload("chan_sip.so");
				foreach ($this->PJSipModules as $mod) {
					$this->FreePBX->ModulesConf->removenoload($mod);
				}
			} elseif($ast_sip_driver == 'chan_pjsip') {
				$this->enablePJSipModules();
			} elseif($ast_sip_driver == 'chan_sip') {
				$this->disablePJSipModules();
			}
		} else {
			// We don't support pjsip. If we're trying to use it, don't. Note
			// that if there are devices or trunks trying to use chan_pjsip, we
			// complain loudly about it core_devices_configpageload
			if($ast_sip_driver == 'chan_pjsip' || $ast_sip_driver == 'both') {
				$this->FreePBX->Config->set_conf_values(array('ASTSIPDRIVER' => 'chan_sip'), true, true);
			}
		}

		$this->FreePBX->WriteConfig($conf);
	}

	/**
	 * External Hook to Add settings to the Endpoint Section
	 * Works like Core Conf
	 * @param {string} $section The section to be adding information to
	 * @param {string} $key     The Key
	 * @param {string} $value   The Value
	 */
	public function addEndpoint($section, $key, $value) {
		$this->_endpoint[$section][] = array('key' => $key, 'value' => $value);
	}

	/**
	* External Hook to Add settings to the AOR Section
	* Works like Core Conf
	* @param {string} $section The section to be adding information to
	* @param {string} $key     The Key
	* @param {string} $value   The Value
	*/
	public function addAor($section, $key, $value) {
		$this->_aor[$section][] = array('key' => $key, 'value' => $value);
	}

	/**
	* External Hook to Add settings to the Auth Section
	* Works like Core Conf
	* @param {string} $section The section to be adding information to
	* @param {string} $key     The Key
	* @param {string} $value   The Value
	*/
	public function addAuth($section, $key, $value) {
		$this->_auth[$section][] = array('key' => $key, 'value' => $value);
	}

	/**
	* External Hook to Add settings to the Global Section
	* Works like Core Conf
	* @param {string} $section The section to be adding information to
	* @param {string} $key     The Key
	* @param {string} $value   The Value
	*/
	public function addGlobal($key, $value) {
		$this->_global[] = array('key' => $key, 'value' => $value);
	}

	/**
	* External Hook to Add settings to the Registration Section
	* Works like Core Conf
	* @param {string} $section The section to be adding information to
	* @param {string} $key     The Key
	* @param {string} $value   The Value
	*/
	public function addRegistration($section, $key, $value) {
		$this->_registration[$section][] = array('key' => $key, 'value' => $value);
	}

	/**
	* External Hook to Add settings to the Identify Section
	* Works like Core Conf
	* @param {string} $section The section to be adding information to
	* @param {string} $key     The Key
	* @param {string} $value   The Value
	*/
	public function addIdentify($section, $key, $value) {
		$this->_identify[$section][] = array('key' => $key, 'value' => $value);
	}

	/**
	 * Get Transport Configs from SIPSettings module
	 */
	public function getTransportConfigs() {
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

				if (!$extip) {
					// Is there a global extern setting?
					$extip = $this->FreePBX->Sipsettings->getConfig("externip");
				}

				if ($extip) {
					$transport[$t]['external_media_address'] = $extip;
					$transport[$t]['external_signaling_address'] = $extip;
				}

				// Add the Generic localnet settings.
				$localnets = $this->FreePBX->Sipsettings->getConfig('localnets');
				foreach($localnets as $arr) {
					$transport[$t]['local_net'][] = $arr['net']."/".$arr['mask'];
				}

				// If there's a specific local net for this interface, add it too.
				$localnet = $this->FreePBX->Sipsettings->getConfig($protocol."localnet-$ip");
				if ($localnet) {
					$transport[$t]['local_net'][] =  $localnet;
				}
			}
		}

		$this->TransportConfigCache = $transport;
		return $transport;
	}

	/**
	 * Get Default SIP Codecs
	 */
	public function getDefaultSIPCodecs() {
		// Grab the default Codecs from the sipsettings module.
		$codecs = $this->FreePBX->Sipsettings->getConfig('voicecodecs');

		if (!$codecs) {
			// Sipsettings doesn't have any codecs yet.
			// Grab the default codecs from BMO
			foreach ($this->FreePBX->Codecs->getAudio(true) as $c => $en) {
				if ($en) {
					$codecs[$c] = $en;
				}
			}
		}

		$this->DefaultSipCodecs = join(",", array_keys($codecs));
		return $this->DefaultSipCodecs;
	}

	/**
	 * Generate Endpoints
	 */
	private function generateEndpoints($retarr) {
		// More Efficent Function here.
		foreach ($this->getAllDevs() as $dev) {
			$this->generateEndpoint($dev, $retarr);
		}

		// Check to see if 'Allow Guest' is enabled in SIP Settings. If it is,
		// we need to create the magic 'anonymous' endpoint.
		$allowguest = $this->Sipsettings->getConfig('allowguest');
		if ($allowguest == 'yes') {
			$endpoint[] = "type=endpoint";
			// Do we have a custom contet for anon calls to go to?
			$context = $this->db->getOne('SELECT `data` FROM `sipsettings` WHERE `keyword`="context"');
			if (empty($context)) {
				$context = "from-sip-external";
			}
			$endpoint[] = "context=$context";
			$endpoint[] = "allow=all";
			$endpoint[] = "transport=udp,tcp,ws,wss";
			$retarr["pjsip.endpoint.conf"]["anonymous"] = $endpoint;
		}

		return $retarr;
	}

	/**
	 * Generate Individual Endpoint
	 * @param {string} $config  configuration
	 * @param {array} &$retarr Returned Array
	 */
	private function generateEndpoint($config, &$retarr) {
		// Validate $config array
		$this->validateEndpoint($config);

		if($config['sipdriver'] != 'chan_pjsip') {
			return false;
		}

		$endpoint = $auth = $aor = array();

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

		$endpoint[] = "allow=".str_replace('&', ',', $config['allow']); // & is invalid in pjsip, but valid in chan_sip

		if (!empty($config['disallow'])) {
			$endpoint[] = "disallow=".str_replace('&', ',', $config['disallow']); // As above.
		}

		$endpoint[] = "context=".$config['context'];
		$endpoint[] = "callerid=".$config['callerid'];
		// PJSIP Has a limited number of dtmf settings. If we don't know what it is, set it to RFC.
		if ($config['dtmfmode'] != "rfc4733" && $config['dtmfmode'] != 'inband' && $config['dtmfmode'] != 'info'
			&& $config['dtmfmode'] != 'none' ) {
			$config['dtmfmode'] = "rtc4733";
		}
		$endpoint[] = "dtmf_mode=".$config['dtmfmode'];
		//unsolicited mwi
		$endpoint[] = "mailboxes=".$config['mailbox'];
		//solicited mwi
		$aor[] = "mailboxes=".$config['mailbox'];
		//check transport to make sure it's valid
		$trans = array_keys($this->getTransportConfigs());
		if(!empty($config['transport'])) {
			if (!in_array($config['transport'],$trans)) {
				// throw new Exception('Invalid Transport Defined on device '.$endpointname);
				// Remove it, it's now autodetecting.
				unset($config['transport']);
			} else {
				$endpoint[] = "transport=".$config['transport'];
			}
		}

		if (!empty($config['callgroup'])) {
			$endpoint[] = "call_group=".$config['callgroup'];
		}

		if (!empty($config['pickupgroup'])) {
			$endpoint[] = "pickup_group=".$config['pickupgroup'];
		}

		if (!empty($config['avpf'])) {
			$endpoint[] = "use_avpf=".$config['avpf'];
		}

		if (!empty($config['icesupport'])) {
			$endpoint[] = "ice_support=".$config['icesupport'];
		}

		if (!empty($config['media_use_received_transport']) && version_compare($this->version, "12.4.0", "ge")) {
			$endpoint[] = "media_use_received_transport=".$config['media_use_received_transport'];
		}

		if (!empty($config['trustrpid'])) {
			$endpoint[] = "trust_id_inbound=".$config['trustrpid'];
		}

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
		$endpoint[] = !empty($config['rewrite_contact']) ? "rewrite_contact=".$config['rewrite_contact'] : "rewrite_contact=yes";

		// Auth
		$auth[] = "auth_type=userpass";
		$auth[] = "password=".$config['secret'];
		$auth[] = "username=".$config['username'];

		// AOR
		// Never allow zero. Zero is not what you want.
		if (!isset($config['max_contacts']) || $config['max_contacts'] < 2) {
			$aor[]="max_contacts=1";
			$aor[]="remove_existing=yes";
		} else {
			$aor[]="max_contacts=".$config['max_contacts'];
			$aor[]="remove_existing=no";
		}

		if (!empty($config['qualifyfreq']))
			$aor[] = "qualify_frequency=".$config['qualifyfreq'];

		if (isset($retarr["pjsip.endpoint.conf"][$endpointname])) {
			throw new \Exception("Endpoint $endpointname already exists.");
		}
		$retarr["pjsip.endpoint.conf"][$endpointname] = $endpoint;
		if(!empty($this->_endpoint[$endpointname])) {
			foreach($this->_endpoint[$endpointname] as $el) {
				$retarr["pjsip.endpoint.conf"][$endpointname][] = "{$el['key']}={$el['value']}";
			}
		}

		if (isset($retarr["pjsip.auth.conf"][$authname])) {
			throw new \Exception("Auth $authname already exists.");
		}
		$retarr["pjsip.auth.conf"][$authname] = $auth;
		if(!empty($this->_auth[$authname])) {
			foreach($this->_auth[$authname] as $el) {
				$retarr["pjsip.auth.conf"][$authname][] = "{$el['key']}={$el['value']}";
			}
		}

		if (isset($retarr["pjsip.aor.conf"][$aorname])) {
			throw new \Exception("AOR $aorname already exists.");
		}
		$retarr["pjsip.aor.conf"][$aorname] = $aor;
		if(!empty($this->_aor[$aorname])) {
			foreach($this->_aor[$aorname] as $el) {
				$retarr["pjsip.aor.conf"][$aorname][] = "{$el['key']}={$el['value']}";
			}
		}
	}

	/**
	 * Validate Endpoint
	 * @param {string} &$config Configuration to be passed back
	 */
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

	/**
	 * Enable PJSip Modules through module.conf control
	 */
	private function enablePJSipModules() {
		// We need to DISABLE chan_sip.so, and remove any noload lines for the pjsip stuff.
		//
		// This is just to save typing. I'm lazy.
		$m = $this->FreePBX->ModulesConf;

		$m->noload("chan_sip.so");
		foreach ($this->PJSipModules as $mod)
			$m->removenoload($mod);
	}

	/**
	 * Disable PJSip Modules through module.conf control
	 */
	private function disablePJSipModules() {
		// We need to ENABLE chan_sip.so, and add all the noload lines for the pjsip stuff.
		//
		// This is just to save typing. I'm lazy.
		$m = $this->FreePBX->ModulesConf;

		$m->removenoload("chan_sip.so");
		foreach ($this->PJSipModules as $mod)
			$m->noload($mod);
	}

	/**
	 * Add PJSip Trunk
	 * @param {int} $trunknum The Trunk Number
	 */
	public function addTrunk($trunknum) {
		// These are the vars we DON'T care about that are being submitted from the PJSip page
		$ignore = array('display', 'action', 'Submit', 'prepend_digit', 'pattern_prefix', 'pattern_pass');
		// We care about the arrays later

		//this is really BAD, why do we always have to set to the dang _REQUESTER argh!
		if(empty($_REQUEST['codec'])) {
			$defaultCodecs = $this->FreePBX->Sipsettings->getCodecs('audio');
			$_REQUEST['codecs'] = implode(",",array_keys($defaultCodecs));
		} else {
			$_REQUEST['codecs'] = implode(",",array_keys($_REQUEST['codec']));
		}
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

	/**
	 * Get All Trunks
	 */
	public function getAllTrunks() {
		$get = $this->db->prepare("SELECT id, keyword, data FROM pjsip");
		$get->execute();
		$result = $get->fetchAll(\PDO::FETCH_ASSOC);
		$final = array();
		foreach($result as $values) {
			$final[$values['id']][$values['keyword']] = $values['data'];
		}
		return $final;
	}

	/**
	 * Get All Active Transports
	 */
	public function getActiveTransports() {
		$tports = array(array("value" => "", "text" => "Auto"));

		foreach(array_keys($this->getTransportConfigs()) as $tran) {
			$tports[] = array(
				'value' => $tran,
				'text' => $tran
			);
		}
		return $tports;
	}

	/**
	 * Get Display Variables
	 * @param {int} $trunkid   Trunk ID
	 * @param {array} &$dispvars Display Variables
	 */
	public function getDisplayVars($trunkid, &$dispvars) {
		$sipSettingsCodecs = $this->FreePBX->Sipsettings->getCodecs('audio',true);
		if(!empty($trunkid)) {
			$get = $this->db->prepare("SELECT keyword, data FROM pjsip WHERE id = :id");
			$get->bindParam(':id', str_replace('OUT_','',$trunkid));
			$get->execute();
			$result = $get->fetchAll(\PDO::FETCH_COLUMN|\PDO::FETCH_GROUP);
			foreach($result as $key => $val) {
				$dispvars[$key] = $val[0];
			}

			$codecs = explode(",",$dispvars['codecs']);
			$dispvars['codecs'] = array();
			foreach($codecs as $codec) {
				$dispvars['codecs'][$codec] = true;
			}



			foreach($sipSettingsCodecs as $codec => $state) {
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
				"codecs" => $sipSettingsCodecs,
				"qualify_frequency" => 60
			);
		}
		$dispvars['transports'] = array_keys($this->getTransportConfigs());
	}

	/**
	* Get All Devices
	*/
	private function getAllDevs() {
		$alldevices = $this->db->query("SELECT * FROM devices WHERE tech = 'pjsip'",\PDO::FETCH_ASSOC);
		$devlist = array();
		foreach($alldevices as $device) {
			$id = $device['id'];
			// Have we already prepared our query?
			$q = $this->db->prepare("SELECT * FROM `sip` WHERE `id` = :id");

			$q->execute(array(":id" => $id));
			$data = $q->fetchAll(\PDO::FETCH_ASSOC);

			$devlist[$id] = $device;

			foreach($data as $setting) {
				$devlist[$id][$setting['keyword']] = $setting['data'];
			}
		}
		return $devlist;
	}

	/**
	* Return an array consisting of all SIP devices, Trunks, or both.
	* @param {[type]} $type = null Devices or Trunks
	*/
	private function getAllOld($type = null) {
		$allkeys = $this->db->query("SELECT DISTINCT(`id`) FROM `sip`");
		$out = $allkeys->fetchAll(\PDO::FETCH_ASSOC);
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
					throw new \Exception("I have no idea what ".$res['id']." is.");
				}
			}
		}
		if (isset($retarr)) {
			return $retarr;
		} else {
			return array();
		}
	}

	/**
	* Grab an Old Extension from the existing database
	* @param {string} $ext = null The extension/device id
	*/
	private function getExtOld($ext = null) {

		// Careful - 0 is, sorta kinda, a valid device.
		if ($ext === null)
			throw new \Exception("No Device given to getExtOld");

		// Have we already prepared our query?
		if (!isset($this->getExtOldQuery)) {
			$this->getExtOldQuery = $this->db->prepare("SELECT * FROM `sip` WHERE `id` = :id");
		}

		$this->getExtOldQuery->execute(array(":id" => $ext));
		$output = $this->getExtOldQuery->fetchAll(\PDO::FETCH_ASSOC);

		// Tidy up the return
		foreach ($output as $entry) {
			$retarr[$entry['keyword']] = $entry['data'];
		}

		if (isset($retarr)) {
			return $retarr;
		} else {
			// return array();
			throw new \Exception("Old SIP Device $ext not found");
		}
	}
}

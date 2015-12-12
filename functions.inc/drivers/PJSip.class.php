<?php
// vim: set ai ts=4 sw=4 ft=php:
namespace FreePBX\modules\Core\Drivers;
if(!class_exists("\\FreePBX\\Modules\\Core\\Drivers\\Sip")) {
	include(__DIR__."/Sip.class.php");
}
class PJSip extends \FreePBX\modules\Core\Drivers\Sip {

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

	public function __construct($freepbx) {
		parent::__construct($freepbx);
		$this->db = $this->database;
	}

	public function getInfo() {
		$sipdriver = $this->freepbx->Config->get_conf_setting('ASTSIPDRIVER');
		if(($sipdriver != "chan_pjsip" && $sipdriver != "both") || version_compare("12.0",$this->version,">")) {
			return false;
		}
		return array(
			"rawName" => "pjsip",
			"hardware" => "pjsip_generic",
			"prettyName" => _("Generic PJSIP Device"),
			"shortName" => "PJSIP",
			"description" => _("A new SIP channel driver for Asterisk, chan_pjsip is built on the PJSIP SIP stack. A collection of resource modules provides the bulk of the SIP functionality")
		);
	}

	public function addDevice($id, $settings) {
		$sql = 'INSERT INTO sip (id, keyword, data, flags) values (?,?,?,?)';
		$sth = $this->database->prepare($sql);
		$settings = is_array($settings)?$settings:array();
		foreach($settings as $key => $setting) {
			$sth->execute(array($id,$key,$setting['value'],$setting['flag']));
		}
		return true;
	}

	public function delDevice($id) {
		$sql = "DELETE FROM sip WHERE id = ?";
		$sth = $this->database->prepare($sql);
		$sth->execute(array($id));
		return true;
	}

	public function getDevice($id) {
		$sql = "SELECT keyword,data FROM sip WHERE id = ?";
		$sth = $this->database->prepare($sql);
		$tech = array();
		try {
			$sth->execute(array($id));
			$tech = $sth->fetchAll(\PDO::FETCH_COLUMN|\PDO::FETCH_GROUP);
			//reformulate into what is expected
			//This is in the try catch just for organization
			foreach($tech as &$value) {
				$value = $value[0];
			}
		} catch(\Exception $e) {}

		return $tech;
	}

	public function getDefaultDeviceSettings($id, $displayname, &$flag) {
		$dtmf = $this->freepbx->Config->get('DEVICE_SIP_DTMF');
		if($dtmf == "rfc2833") {
			$dtmf = "rfc4733";
		}
		$dial = 'PJSIP';
		$settings  = array(
			"sipdriver" => array(
				"value" => "chan_pjsip",
				"flag" => $flag++
			),
			"secret" => array(
				"value" => $this->freepbx->Core()->generateSecret(),
				"flag" => $flag++
			),
			"dtmfmode" => array(
				"value" => $dtmf,
				"flag" => $flag++
			),
			"trustrpid" => array(
				"value" => $this->freepbx->Config->get('DEVICE_SIP_TRUSTRPID'),
				"flag" => $flag++
			),
			"sendpid" => array(
				"value" => $this->freepbx->Config->get('DEVICE_SIP_SENDRPID'),
				"flag" => $flag++
			),
			"qualifyfreq" => array(
				"value" => $this->freepbx->Config->get('DEVICE_SIP_QUALIFYFREQ'),
				"flag" => $flag++
			),
			"transport" => array(
				"value" => "",
				"flag" => $flag++
			),
			"avpf" => array(
				"value" => "no",
				"flag" => $flag++
			),
			"icesupport" => array(
				"value" => "no",
				"flag" => $flag++
			),
			"callgroup" => array(
				"value" => $this->freepbx->Config->get('DEVICE_CALLGROUP'),
				"flag" => $flag++
			),
			"pickupgroup" => array(
				"value" => $this->freepbx->Config->get('DEVICE_PICKUPGROUP'),
				"flag" => $flag++
			),
			"disallow" => array(
				"value" => $this->freepbx->Config->get('DEVICE_DISALLOW'),
				"flag" => $flag++
			),
			"allow" => array(
				"value" => $this->freepbx->Config->get('DEVICE_ALLOW'),
				"flag" => $flag++
			),
			"accountcode" => array(
				"value" => "",
				"flag" => $flag++
			),
			"mailbox" => array(
				"value" => $id."@device",
				"flag" => $flag++
			),
			"max_contact" => array(
				"value" => "1",
				"flag" => $flag++
			),
			"max_contact" => array(
				"value" => "1",
				"flag" => $flag++
			),
			"media_use_received_transport" => array(
				"value" => "no",
				"flag" => $flag++
			),
			"rtp_symmetric" => array(
				"value" => "yes",
				"flag" => $flag++
			),
			"force_rport" => array(
				"value" => "no",
				"flag" => $flag++
			),
			"rewrite_contact" => array(
				"value" => "yes",
				"flag" => $flag++
			),
			"mwi_subscription" => array(
				"value" => "solicited",
				"flag" => $flag++
			),
		);
		return array(
			"dial" => $dial,
			"settings" => $settings
		);
	}

	public function getDeviceDisplay($display, $deviceInfo, $currentcomponent, $primarySection) {
		$tmparr = parent::getDeviceDisplay($display, $deviceInfo, $currentcomponent, $primarySection);
		unset($tmparr['force_avp'],$tmparr['permit'],$tmparr['deny'], $tmparr['accountcode'], $tmparr['encryption'], $tmparr['type'], $tmparr['qualify'],$tmparr['port'],$tmparr['canreinvite'],$tmparr['host'],$tmparr['nat']);
		if (version_compare($this->version,'12.5.0','ge')) {
			$tt = _("Account Code for this extension");
			$tmparr['accountcode'] = array('prompttext' => _("Account Code"), 'value' => '', 'tt' => $tt, 'level' => 1);
		}
		$tt = _("Maximum number of Endpoints that can associate with this Device");
		$tmparr['max_contacts'] = array('prompttext' => _('Max Contacts'), 'value' => '1', 'tt' => $tt, 'level' => 1);
		unset($select);

		$select[] = array('value' => 'yes', 'text' => 'Yes');
		$select[] = array('value' => 'no', 'text' => 'No');

		if (version_compare($this->version,'12.4.0','ge')) {
			//media_use_received_transport
			$tt = _("Determines whether res_pjsip will use the media transport received in the offer SDP in the corresponding answer SDP.");
			$tmparr['media_use_received_transport'] = array('prompttext' => _('Media Use Received Transport'), 'value' => 'no', 'tt' => $tt, 'select' => $select, 'level' => 1, 'type' => 'radio');
		}
		$tt = _("Enforce that RTP must be symmetric. If this device is natting in it is usually a good idea to enable this. Disable only if you are having issues.");
		$tmparr['rtp_symmetric'] = array('prompttext' => _('RTP Symmetric'), 'value' => 'yes', 'tt' => $tt, 'select' => $select, 'level' => 1, 'type' => 'radio');
		$tt = _("Allow Contact header to be rewritten with the source IP address-port");
		$tmparr['rewrite_contact'] = array('prompttext' => _('Rewrite Contact'), 'value' => 'yes', 'tt' => $tt, 'select' => $select, 'level' => 1, 'type' => 'radio');
		$tt = _("Force use of return port.");
		$tmparr['force_rport'] = array('prompttext' => _('Force rport'), 'value' => 'no', 'tt' => $tt, 'select' => $select, 'level' => 1, 'type' => 'radio');

		unset($select);

		$select[] = array('value' => 'solicited', 'text' => _('Solicited'));
		$select[] = array('value' => 'unsolicited', 'text' => _('Unsolicited'));
		$tt = _("For Message Waiting indicators there are two types: Solicited and Unsolicited. Solicited means Subscribe 200 then Notify 200. Unsolicited means only Notify 200. No need to Subscribe. Solicited is the default and should only be changed if you see errors in the Asterisk logs");
		$tmparr['mwi_subscription'] = array('prompttext' => _('MWI Subscription Type'), 'value' => 'solicited', 'tt' => $tt, 'select' => $select, 'level' => 1, 'type' => 'radio');
		unset($select);

		$select[] = array('value' => 'no', 'text' => _('No'));
		$select[] = array('value' => 'yes', 'text' => _('Yes'));
		$tt = _("When enabled, Asterisk condenses message waiting notifications from multiple mailboxes into a single NOTIFY. If it is disabled, individual NOTIFYs are sent for each mailbox.");
		$tmparr['aggregate_mwi'] = array('prompttext' => _('Aggregate MWI'), 'value' => 'yes', 'tt' => $tt, 'select' => $select, 'level' => 1, 'type' => 'radio');
		unset($select);

		$select[] = array('value' => 'no', 'text' => _('None'));
		$select[] = array('value' => 'sdes', 'text' => _('SRTP via in-SDP'));
		$select[] = array('value' => 'dtls', 'text' => _('DTLS-SRTP'));
		$tt = _("Media (RTP) Encryption. Normally you would use None, unless you have explicitly set up SDP or DTLS.").' [media_encryption]';
		$tmparr['mediaencryption'] = array('prompttext' => _('Media Encryption'), 'value' => 'no', 'tt' => $tt, 'select' => $select, 'level' => 1);
		unset($select);

		$select[] = array('value' => 'no', 'text' => _('No'));
		$select[] = array('value' => 'yes', 'text' => _('Yes'));
		$tt = _("Determines whether encryption should be used if possible but does not terminate the session if not achieved. This option only applies if Media Encryption is set to SRTP via in-SDP or DTLS-SRTP.").' [media-encryption_optimistic]';
		$tmparr['mediaencryptionoptimistic'] = array('prompttext' => _('Media Encryption Optimistic'), 'value' => 'no', 'tt' => $tt, 'select' => $select, 'level' => 1, 'type' => 'radio');

		//https://wiki.asterisk.org/wiki/display/AST/Asterisk+13+Configuration_res_pjsip_endpoint_identifier_ip
		$tt = _("The value is a comma-delimited list of IP addresses. IP addresses may have a subnet mask appended. The subnet mask may be written in either CIDR or dot-decimal notation. Separate the IP address and subnet mask with a slash ('/')");
		$tmparr['match'] = array('prompttext' => _('Match (Permit)'), 'value' => '', 'tt' => $tt, 'level' => 1);
		unset($select);

		$tt = _("Maximum time to keep an AoR");
		$tmparr['maximum_expiration'] = array('prompttext' => _('Maximum Expiration'), 'value' => '7200', 'tt' => $tt, 'level' => 1);
		unset($select);

		$tt = _("Minimum time to keep an AoR");
		$tmparr['minimum_expiration'] = array('prompttext' => _('Minimum Expiration'), 'value' => '60', 'tt' => $tt, 'level' => 1);
		unset($select);

		//Use the transport engine, don't cross migrate anymore, it just doesn't work
		$transports = $this->getActiveTransports();
		$transports = is_array($transports)?$transports:array();
		foreach($transports as $transport) {
			$select[] = array('value' => $transport['value'], 'text' => $transport['text']);
		}
		$tmparr['transport']['select'] = $select;
		$tmparr['transport']['level'] = 0;
		unset($select);
		return $tmparr;
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
		$pjsip = "#include pjsip_custom.conf\n#include pjsip.transports.conf\n#include pjsip.transports_custom_post.conf\n#include pjsip.endpoint.conf\n#include pjsip.endpoint_custom_post.conf\n#include pjsip.aor.conf\n#include pjsip.aor_custom_post.conf\n";
		$pjsip .= "#include pjsip.auth.conf\n#include pjsip.auth_custom_post.conf\n#include pjsip.registration.conf\n#include pjsip.registration_custom_post.conf\n#include pjsip.identify.conf\n#include pjsip.identify_custom_post.conf\n";
		$conf['pjsip.conf'][] = $pjsip;

		$conf = $this->generateEndpoints($conf);

		// Transports are a multi-dimensional array, because
		// we use it earlier to match extens with transports
		// So we need to flatten it to something that can be
		// written to a file.
		$transports = $this->getTransportConfigs();
		$transports = is_array($transports)?$transports:array();
		foreach ($transports as $transport => $entries) {
			$tmparr = array();
			$entries = is_array($entries)?$entries:array();
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
			'user_agent='.$this->freepbx->Config->get('SIPUSERAGENT') . '-' . getversion() . "(" . $this->version . ")"
		);
		if(!empty($this->_global) && is_array($this->_global)) {
			foreach($this->_global as $el) {
				$conf['pjsip.conf']['global'][] = "{$el['key']}={$el['value']}";
			}
		}

		$trunks = $this->getAllTrunks();
		foreach($trunks as $trunk) {
			$tn = $trunk['trunk_name'];
			//prevent....special people
			$trunk['sip_server_port'] = !empty($trunk['sip_server_port']) ? $trunk['sip_server_port'] : '5060';

			// Checkboxes aren't saved if they're unchecked.
			if (!isset($trunk['auth_rejection_permanent'])) {
				$trunk['auth_rejection_permanent'] = 'off';
			}
			// Ensure our registration and auth values are set sanely
			if (!isset($trunk['registration'])) {
				$trunk['registration'] = "send";
			}
			if (empty($trunk['authentication'])) {
				$trunk['authentication'] = "outbound";
			}

			// Have we been asked to send registrations?
			if ($trunk['registration'] === "send") {
				$conf['pjsip.registration.conf'][$tn] = array(
					'type' => 'registration',
					'transport' => $trunk['transport'],
					'outbound_auth' => $tn,
					'retry_interval' => $trunk['retry_interval'],
					'max_retries' => $trunk['max_retries'],
					'expiration' => $trunk['expiration'],
					'auth_rejection_permanent' => ($trunk['auth_rejection_permanent'] == 'on') ? 'yes' : 'no'
				);
				if(!empty($trunk['contact_user'])) {
					$conf['pjsip.registration.conf'][$tn]['contact_user'] = $trunk['contact_user'];
				}
				if(empty($trunk['server_uri']) && empty($trunk['sip_server'])) {
					throw new \Exception('Asterisk will crash if sip_server is blank!');
				} else if(!empty($trunk['server_uri'])) {
					$conf['pjsip.registration.conf'][$tn]['server_uri'] = $trunk['server_uri'];
				} else {
					$conf['pjsip.registration.conf'][$tn]['server_uri'] = 'sip:'.$trunk['sip_server'].':'.$trunk['sip_server_port'];
				}

				if(empty($trunk['client_uri']) && empty($trunk['sip_server'])) {
					throw new \Exception('Asterisk will crash if sip_server is blank!');
				} else if(!empty($trunk['client_uri'])) {
					$conf['pjsip.registration.conf'][$tn]['client_uri'] = $trunk['client_uri'];
				} else {
					$conf['pjsip.registration.conf'][$tn]['client_uri'] = 'sip:'.$trunk['username'].'@'.$trunk['sip_server'].':'.$trunk['sip_server_port'];
				}
				if(!empty($this->_registration[$tn]) && is_array($this->_registration[$tn])) {
					foreach($this->_registration[$tn] as $el) {
						$conf["pjsip.registration.conf"][$tn][] = "{$el['key']}={$el['value']}";
					}
				}
			}

			// Are we doing authentication?
			if ($trunk['authentication'] !== "none") {
				$conf['pjsip.auth.conf'][$tn] = array(
					'type' => 'auth',
					'auth_type' => 'userpass',
					'password' => $trunk['secret'],
				);
				// If this is inbound or both auth, it's the trunk name
				if ($trunk['authentication'] == "inbound" || $trunk['authentication'] == "both" || empty($trunk['username'])) {
					$conf['pjsip.auth.conf'][$tn]['username'] = $tn;
				} else {
					$conf['pjsip.auth.conf'][$tn]['username'] = $trunk['username'];
				}
			}

			$conf['pjsip.aor.conf'][$tn] = array(
				'type' => 'aor',
				'qualify_frequency' => !empty($trunk['qualify_frequency']) ? $trunk['qualify_frequency'] : 60
			);

			// We only have a contact if we're sending, or not using registrations
			if ($trunk['registration'] == "send" || $trunk['registration'] == "none") {
				if(!empty($trunk['aor_contact'])) {
					$conf['pjsip.aor.conf'][$tn]['contact'] = $trunk['aor_contact'];
				} else {
					$conf['pjsip.aor.conf'][$tn]['contact'] = 'sip:'.$trunk['username'].'@'.$trunk['sip_server'].':'.$trunk['sip_server_port'];
				}
			} elseif ($trunk['registration'] == "receive") {
				$conf['pjsip.aor.conf'][$tn]['max_contacts'] = 1;
			}

			if(!empty($this->_aor[$tn]) && is_array($this->_aor[$tn])) {
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
				'aors' => $tn
			);

			if ($trunk['authentication'] == "outbound" || $trunk['authentication'] == "both") {
				$conf['pjsip.endpoint.conf'][$tn]['outbound_auth'] = $tn;
			}
			if ($trunk['authentication'] == "inbound" || $trunk['authentication'] == "both") {
				$conf['pjsip.endpoint.conf'][$tn]['auth'] = $tn;
			}
			if(!empty($trunk['from_domain'])) {
				$conf['pjsip.endpoint.conf'][$tn]['from_domain'] = $trunk['from_domain'];
			}
			if(!empty($trunk['from_user'])) {
				$conf['pjsip.endpoint.conf'][$tn]['from_user'] = $trunk['from_user'];
			}

			if(!empty($trunk['dtmfmode'])) {
				// PJSIP Has a limited number of dtmf settings. If we don't know what it is, set it to RFC.
				$validdtmf = array("rfc4733","inband","info");
				if(version_compare($this->version,'13','ge')) {
					$validdtmf[] = "auto";
				}
				if (!in_array($trunk['dtmfmode'],$validdtmf)) {
					$trunk['dtmfmode'] = "rfc4733";
				}
				//FREEPBX-10666
				//yes,no
				if(!empty($trunk['t38_udptl'])){
					$conf['pjsip.endpoint.conf'][$tn]['t38_udptl'] = $trunk['t38_udptl'];
				}
				//none, fec, redundancy
				if(!empty($trunk['t38_udptl_ec'])){
					$conf['pjsip.endpoint.conf'][$tn]['t38_udptl_ec'] = $trunk['t38_udptl_ec'];
				}
				//yes,no
				if(!empty($trunk['fax_detect'])){
					$conf['pjsip.endpoint.conf'][$tn]['fax_detect'] = $trunk['fax_detect'];
				}
				//yes,no
				if(!empty($trunk['t38_udptl_nat'])){
					$conf['pjsip.endpoint.conf'][$tn]['t38_udptl_nat'] = $trunk['t38_udptl_nat'];
				}

				$conf['pjsip.endpoint.conf'][$tn]['dtmf_mode'] = $trunk['dtmfmode'];
			}

			if(!empty($this->_endpoint[$tn]) && is_array($this->_endpoint[$tn])) {
				foreach($this->_endpoint[$tn] as $el) {
					$conf["pjsip.endpoint.conf"][$tn][] = "{$el['key']}={$el['value']}";
				}
			}

			// Identify types aren't used when we're receiving registrations
			if ($trunk['registration'] != "receive") {
				$conf['pjsip.identify.conf'][$tn] = array(
					'type' => 'identify',
					'endpoint' => $tn,
					'match' => !empty($trunk['match']) ? $trunk['match'] : $trunk['sip_server']
				);
			}

			if(!empty($this->_identify[$tn]) && is_array($this->_identify[$tn])) {
				foreach($this->_identify[$tn] as $el) {
					$conf["pjsip.identify.conf"][$tn][] = "{$el['key']}={$el['value']}";
				}
			}
		}

		//if we have an additional and custom file for sip_notify, write a pjsip_notify.conf
		$ast_etc_dir = $this->freepbx->Config->get_conf_setting('ASTETCDIR');
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

		$ast_sip_driver = $this->freepbx->Config->get_conf_setting('ASTSIPDRIVER');
		if(version_compare($this->version, '12', 'ge')) {
			if($ast_sip_driver == 'both') {
				$this->freepbx->ModulesConf->removenoload("chan_sip.so");
				foreach ($this->PJSipModules as $mod) {
					$this->freepbx->ModulesConf->removenoload($mod);
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
				$this->freepbx->Config->set_conf_values(array('ASTSIPDRIVER' => 'chan_sip'), true, true);
			}
		}

		$this->freepbx->WriteConfig($conf);
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
		if (isset($this->TransportConfigCache)) {
			return $this->TransportConfigCache;
		}
		$transport = array();
		//TODO: move this to \FreePBX::Sipsettings()->getBinds();
		//Calling the config directly will return an array or false.
		$binds = $this->freepbx->Sipsettings->getConfig("binds");
		//false breaks the foreach loop FREEPBX-9419
		$binds = is_array($binds)?$binds:array();

		foreach ($binds as $protocol => $arr) {
			foreach ($arr as $ip => $on) {
				if($on != "on") {
					continue;
				}
				$t = "$ip-$protocol";
				$transport[$t]['type'] = "transport";
				$transport[$t]['protocol'] = $protocol;
				$port = $this->freepbx->Sipsettings->getConfig($protocol."port-$ip");
				if (!$port) {
					$transport[$t]['bind'] = "$ip";
				} else {
					$transport[$t]['bind'] = "$ip:$port";
				}
				$extip = $this->freepbx->Sipsettings->getConfig($protocol."extip-$ip");

				if (!$extip) {
					// Is there a global extern setting?
					$extip = $this->freepbx->Sipsettings->getConfig("externip");
				}

				if ($extip) {
					$transport[$t]['external_media_address'] = $extip;
					$transport[$t]['external_signaling_address'] = $extip;
				}

				// Add the Generic localnet settings.
				//TODO: This should call a method and not the config direct.
				$localnets = $this->freepbx->Sipsettings->getConfig('localnets');
				$localnets = is_array($localnets)?$localnets:array();
				if ($localnets) {
					foreach($localnets as $arr) {
						$transport[$t]['local_net'][] = $arr['net']."/".$arr['mask'];
					}
				}

				// If there's a specific local net for this interface, add it too.
				$localnet = $this->freepbx->Sipsettings->getConfig($protocol."localnet-$ip");
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
		$codecs = $this->freepbx->Sipsettings->getConfig('voicecodecs');

		if (!$codecs) {
			// Sipsettings doesn't have any codecs yet.
			// Grab the default codecs from BMO
			foreach ($this->freepbx->Codecs->getAudio(true) as $c => $en) {
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
		$allowguest = $this->freepbx->Sipsettings->getConfig('allowguest');
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

		$endpoint = $auth = $aor = $identify = array();

		// With pjsip, we need three sections.
		$endpointname = $config['account'];
		$endpoint[] = "type=endpoint";
		$authname = "$endpointname-auth";
		$identifyname = "$endpointname-identify";
		$identify[] = "type=identify";
		$auth[] = "type=auth";
		$aorname = "$endpointname";
		$aor[] = "type=aor";

		// Endpoint
		$endpoint[] = "aors=$aorname";
		$endpoint[] = "auth=$authname";

		if (!empty($config['disallow'])) {
			$endpoint[] = "disallow=".str_replace('&', ',', $config['disallow']); // As above.
		}
		$endpoint[] = "allow=".str_replace('&', ',', $config['allow']); // & is invalid in pjsip, but valid in chan_sip

		$endpoint[] = "context=".$config['context'];
		$endpoint[] = "callerid=".$config['callerid'];
		// PJSIP Has a limited number of dtmf settings. If we don't know what it is, set it to RFC.
		$validdtmf = array("rfc4733","inband","info");
		if(version_compare($this->version,'13','ge')) {
			$validdtmf[] = "auto";
		}
		if (!in_array($trunk['dtmfmode'],$validdtmf)) {
			$config['dtmfmode'] = "rfc4733";
		}
		$endpoint[] = "dtmf_mode=".$config['dtmfmode'];

		//http://issues.freepbx.org/browse/FREEPBX-8643
		if(isset($config['mailbox'])) {
			if(empty($config['mwi_subscription']) || $config['mwi_subscription'] == 'solicited') {
				//solicited mwi
				$aor[] = "mailboxes=".$config['mailbox'];
			} else {
				//unsolicited mwi
				$endpoint[] = "mailboxes=".$config['mailbox'];
			}
		}
		if (version_compare($this->version,'12.5.0','ge') && isset($config['accountcode']) && trim($config['accountcode']) != "") {
			$endpoint[] = "accountcode=".$config['accountcode'];
		}
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

		$endpoint[] = "aggregate_mwi=".(isset($config['aggregate_mwi']) ? $config['aggregate_mwi'] : "yes");

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

		if (!empty($config['match'])) {
			$identify[] = "match=".$config['match'];
		}

		if (!empty($config['mediaencryption'])) {
			$endpoint[] = "media_encryption=".$config['mediaencryption'];
		}

		if (!empty($config['mediaencryptionoptimistic'])) {
			$endpoint[] = "media_encryption_optimistic=".$config['mediaencryptionoptimistic'];
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

		if ($this->freepbx->Modules->moduleHasMethod('Soundlang', 'getLanguage')) {
			$language = $this->freepbx->Soundlang->getLanguage();
			if ($language != "") {
				$endpoint[] = "language=" . $language;
			}
		}

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

		if(isset($config['maximum_expiration'])) {
			$aor[] = "maximum_expiration=".$config['maximum_expiration'];
		}

		if(isset($config['minimum_expiration'])) {
			$aor[] = "minimum_expiration=".$config['minimum_expiration'];
		}

		if (!empty($config['qualifyfreq']))
			$aor[] = "qualify_frequency=".$config['qualifyfreq'];

		if (isset($retarr["pjsip.endpoint.conf"][$endpointname])) {
			throw new \Exception("Endpoint $endpointname already exists.");
		}
		$retarr["pjsip.endpoint.conf"][$endpointname] = $endpoint;
		if(!empty($this->_endpoint[$endpointname]) && is_array($this->_endpoint[$endpointname]) ) {
			foreach($this->_endpoint[$endpointname] as $el) {
				$retarr["pjsip.endpoint.conf"][$endpointname][] = "{$el['key']}={$el['value']}";
			}
		}

		if (isset($retarr["pjsip.auth.conf"][$authname])) {
			throw new \Exception("Auth $authname already exists.");
		}
		$retarr["pjsip.auth.conf"][$authname] = $auth;
		if(!empty($this->_auth[$authname]) && is_array($this->_auth[$authname])) {
			foreach($this->_auth[$authname] as $el) {
				$retarr["pjsip.auth.conf"][$authname][] = "{$el['key']}={$el['value']}";
			}
		}

		if (isset($retarr["pjsip.aor.conf"][$aorname])) {
			throw new \Exception("AOR $aorname already exists.");
		}
		$retarr["pjsip.aor.conf"][$aorname] = $aor;
		if(!empty($this->_aor[$aorname]) && is_array($this->_aor[$aorname])) {
			foreach($this->_aor[$aorname] as $el) {
				$retarr["pjsip.aor.conf"][$aorname][] = "{$el['key']}={$el['value']}";
			}
		}


		if (isset($retarr["pjsip.identify.conf"][$identifyname])) {
			throw new \Exception("Identify $aorname already exists.");
		}
		$retarr["pjsip.identify.conf"][$identifyname] = $aor;
		if(!empty($this->_identify[$identifyname]) && is_array($this->_identify[$identifyname])) {
			foreach($this->_identify[$identifyname] as $el) {
				$retarr["pjsip.identify.conf"][$identifyname][] = "{$el['key']}={$el['value']}";
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
		if ($config['dtmfmode'] == "rfc2833") {
			$config['dtmfmode'] = "rfc4733";
		}

		if(version_compare($this->version,'13','lt')) {
			if ($config['dtmfmode'] == "auto") {
				$config['dtmfmode'] = "rfc4733";
			}
		}

		// 'username' is for when username != exten.
		if (!isset($config['username'])) {
			$config['username'] = $config['account'];
		}

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
		$m = $this->freepbx->ModulesConf;

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
		$m = $this->freepbx->ModulesConf;

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
			$defaultCodecs = $this->freepbx->Sipsettings->getCodecs('audio');
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
	public function getDisplayVars($trunkid, $dispvars) {
		$sipSettingsCodecs = $this->freepbx->Sipsettings->getCodecs('audio',true);
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
				"qualify_frequency" => 60,
				"dtmfmode" => "rfc4733"
			);
		}
		$dispvars['transports'] = array_keys($this->getTransportConfigs());

		// Ensure we have a sane registration configuration on all trunks, new and old.
		if (empty($dispvars['authentication'])) {
			$dispvars['authentication'] = "outbound";
		}
		if (empty($dispvars['registration'])) {
			$dispvars['registration'] = "send";
		}

		return $dispvars;
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

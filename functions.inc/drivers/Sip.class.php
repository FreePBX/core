<?php
// vim: set ai ts=4 sw=4 ft=php:
namespace FreePBX\modules\Core\Drivers;
class Sip extends \FreePBX\modules\Core\Driver {
	public $version;

	public function __construct($freepbx) {
		parent::__construct($freepbx);
		$this->version = $freepbx->Config->get('ASTVERSION');
	}

	public function getInfo() {
		$sipdriver = $this->freepbx->Config->get_conf_setting('ASTSIPDRIVER');
		if(($sipdriver != "chan_sip" && $sipdriver != "both")) {
			return false;
		}
		return array(
			"rawName" => "sip",
			"hardware" => "sip_generic",
			"prettyName" => _("Generic CHAN SIP Driver"),
			"shortName" => "Chan_SIP",
			"description" => _("The legacy SIP channel driver in Asterisk")
		);
	}

	public function getDefaultDeviceSettings($id, $displayname, &$flag) {
		$dial = 'SIP';
		$settings  = array(
			"sipdriver" => array(
				"value" => "chan_sip",
				"flag" => $flag++
			),
			"dtmfmode" => array(
				"value" => $this->freepbx->Config->get('DEVICE_SIP_DTMF'),
				"flag" => $flag++
			),
			"canreinvite" => array(
				"value" => $this->freepbx->Config->get('DEVICE_SIP_CANREINVITE'),
				"flag" => $flag++
			),
			"host" => array(
				"value" => "dynamic",
				"flag" => $flag++
			),
			"trustpid" => array(
				"value" => $this->freepbx->Config->get('DEVICE_SIP_TRUSTRPID'),
				"flag" => $flag++
			),
			"sendpid" => array(
				"value" => $this->freepbx->Config->get('DEVICE_SIP_SENDRPID'),
				"flag" => $flag++
			),
			"type" => array(
				"value" => "friend",
				"flag" => $flag++
			),
			"nat" => array(
				"value" => $this->freepbx->Config->get('DEVICE_SIP_NAT'),
				"flag" => $flag++
			),
			"port" => array(
				"value" => "5060",
				"flag" => $flag++
			),
			"qualify" => array(
				"value" => $this->freepbx->Config->get('DEVICE_QUALIFY'),
				"flag" => $flag++
			),
			"qualifyfreq" => array(
				"value" => $this->freepbx->Config->get('DEVICE_SIP_QUALIFYFREQ'),
				"flag" => $flag++
			),
			"transport" => array(
				"value" => "udp,tcp,tls",
				"flag" => $flag++
			),
			"avpf" => array(
				"value" => "no",
				"flag" => $flag++
			),
			"force_avp" => array(
				"value" => "no",
				"flag" => $flag++
			),
			"sessiontimers" => array(
				"value" => "accept",
				"flag" => $flag++
			),
			"videosupport" => array(
				"value" => "inherit",
				"flag" => $flag++
			),
			"icesupport" => array(
				"value" => "no",
				"flag" => $flag++
			),
			"encryption" => array(
				"value" => $this->freepbx->Config->get('DEVICE_SIP_ENCRYPTION'),
				"flag" => $flag++
			),
			"namedcallgroup" => array(
				"value" => $this->freepbx->Config->get('DEVICE_CALLGROUP'),
				"flag" => $flag++
			),
			"namedpickupgroup" => array(
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
			"deny" => array(
				"value" => "0.0.0.0/0.0.0.0",
				"flag" => $flag++
			),
			"permit" => array(
				"value" => "0.0.0.0/0.0.0.0",
				"flag" => $flag++
			),
		);
		return array(
			"dial" => $dial,
			"settings" => $settings
		);
	}

	public function addDevice($id, $settings) {
		$sql = 'INSERT INTO sip (id, keyword, data, flags) values (?,?,?,?)';
		$sth = $this->database->prepare($sql);
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

	public function getDeviceDisplay($display, $deviceInfo, $currentcomponent, $primarySection) {
		$section = _("Settings");
		$category = "general";
		$pports = array();
		$techd = ($deviceInfo['tech'] == 'sip') ? 'CHAN_SIP' : strtoupper($deviceInfo['tech']);
		$devinfo_tech = $deviceInfo['tech'];
		if($this->freepbx->Modules->moduleHasMethod("sipsettings","getBinds")) {
			$out = $this->freepbx->Sipsettings->getBinds();
			if (isset($out[$devinfo_tech])) {
				foreach($out[$devinfo_tech] as $ip => $data1) {
					foreach($data1 as $protocol => $port) {
						if ($protocol == "ws" || $protocol == "wss") {
							continue;
						}
						// Is this the default port for this protocol?
						$defaultport = false;
						if ($protocol == "udp" && $port == 5060) {
							$defaultport = true;
						} elseif ($protocol == "tcp" && $port == 5060) {
							$defaultport = true;
						} elseif ($protocol == "tls" && $port == 5061) {
							$defaultport = true;
						}

						// If the bind address is 0.0.0.0 (or :: or [::]), we don't need to say
						// that it's listening on a specific address.
						if ($ip == "0.0.0.0" || $ip == "::" || $ip = "[::]") {
							if ($defaultport) {
								$pports[] = sprintf(_("Port %s (%s)"), $port, strtoupper($protocol));
							} else {
								$pports[] = sprintf(_("Port %s (%s - this is a <strong>NON STANDARD</strong> port)"), $port, strtoupper($protocol));
							}
						} else {
							if ($defaultport) {
								$pports[] = sprintf(_("Interface %s, Port %s (%s)"), $ip, $port, strtoupper($protocol));
							} else {
								$pports[] = sprintf(_("Interface %s, Port %s (%s - this is a <strong>NON STANDARD</strong> port)"), $ip, $port, strtoupper($protocol));
							}
						}
					}
				}
			}
			if (!$pports) {
				$pport = "(SipSettings Error)";
			} else {
				$pport = join(", ", $pports);
			}

			$display_mode = "advanced";
			$mode = \FreePBX::Config()->get("FPBXOPMODE");
			if(!empty($mode)) {
				$display_mode = $mode;
			}
			if ($display_mode != 'basic') {
				$extrac = !empty($pport) ? sprintf(_('listening on %s'),$pport) : '';
				$device_uses = sprintf(_("This device uses %s technology %s"),"<strong>".$techd."</strong>",$extrac);
				$currentcomponent->addguielem($primarySection, new \gui_label('techlabel', '<div class="alert alert-info" role="alert" style="width:100%">'.$device_uses.'</div>'),1, null, $category);
			}
		} else {
			$currentcomponent->addguielem($primarySection, new \gui_label('techlabel', '<div class="alert alert-danger" role="alert" style="width:100%">'._("The Asterisk SIP Setting Module is not installed or is disabled. Please install it.").'</div>'),1, null, $category);
		}


		// We need to scream loudly if this device is using a channel driver that's disabled.
		if ($devinfo_tech == "pjsip" || $devinfo_tech == "sip") {
			$sipdriver = $this->freepbx->Config->get_conf_setting('ASTSIPDRIVER');
			if ($sipdriver != "both") {
				// OK, one is disabled.
				if ($devinfo_tech == "sip") {
					$iwant = "chan_sip";
				} else {
					$iwant = "chan_pjsip";
				}

				if ($iwant != $sipdriver) {
					// Poot.
					$err = sprintf(_("<strong>CRITICAL ERROR!</strong> Required Service %s is disabled! This device is unusable!"), strtoupper($iwant));
					$currentcomponent->addguielem($primarySection, new \gui_label('techerrlabel', $err),3, null, $category);
				}
			}
		}

		if(!empty($_REQUEST['tech_hardware'])) {
			$tmparr = explode('_', $_REQUEST['tech_hardware']);
			$deviceInfo['tech'] = $tmparr[0];
		} else {
			$tmparr = core_devices_get($_REQUEST['extdisplay']);
			$deviceInfo['tech'] = $tmparr['tech'];
		}
		unset($tmparr);
		$tmparr = array();
		$msgConfirmSecret = _("You have not entered a Secret for this device, although this is possible it is generally bad practice to not assign a Secret to a device. Are you sure you want to leave the Secret empty?");
		$msgInvalidSecret = _("Please enter a Secret for this device");
		$secret_validation = '(isEmpty() && !confirm("'.$msgConfirmSecret.'"))';
		$tt = _("Password (secret) configured for the device. Should be alphanumeric with at least 2 letters and numbers to keep secure.").' [secret]';
		$tmparr['secret'] = array('prompttext' => _('Secret'), 'class' => 'password-meter', 'value' => '', 'tt' => $tt, 'level' => 0, 'jsvalidation' => $secret_validation, 'failvalidationmsg' => $msgInvalidSecret, 'category' => $category, 'section' => $primarySection);

		$section = _("Device Options");
		$category = "advanced";

		$sipdriver = $this->freepbx->Config->get_conf_setting('ASTSIPDRIVER');
		$tmparr['sipdriver'] = array('hidden' => true, 'value' => 'chan_'.strtolower($deviceInfo['tech']), 'level' => 0);

		if ($deviceInfo['tech'] == "sip") {
			$mydriver = "CHAN_SIP";
			$otherdriver = "CHAN_PJSIP";
		} else {
			$mydriver = "CHAN_PJSIP";
			$otherdriver = "CHAN_SIP";
		}

		//Inverted Driver, only allow the change if in certain modes
		if(isset($deviceInfo['id'])) {
			$ttt = sprintf(_("Change To %s Driver"),$otherdriver);
			if($sipdriver == 'both' || ($sipdriver == 'chan_sip' && $deviceInfo['tech'] == 'pjsip') || ($sipdriver == 'chan_pjsip' && $deviceInfo['tech'] == 'sip')) {
				$tt = _("Change the SIP Channel Driver to use $otherdriver.");
				$tmparr['changecdriver'] = array('text' => $ttt, 'prompttext' => _('Change SIP Driver'), 'type' => 'button', 'value' => 'button', 'tt' => $tt, 'level' => 1, 'jsvalidation' => "frm_".$display."_changeDriver();return false;");
			} else {
				$tt = _("You cannot change to $otherdriver as it is not enabled. Please enable $otherdriver in Advanced Settings");
				$tmparr['changecdriver'] = array('text' => _("Changing SIP Driver unavailable"), 'prompttext' => $ttt, 'type' => 'button', 'value' => 'button', 'tt' => $tt, 'level' => 1, 'disable' => true);
			}
		}

		if ($mydriver == "CHAN_PJSIP") {
			$select[] = array('value' => 'rfc4733', 'text' => _('RFC 4733'));
			if(version_compare($this->version,'13','ge')) {
				$select[] = array('value' => 'auto', 'text' => _('Auto'));
			}
		} else {
			$select[] = array('value' => 'rfc2833', 'text' => _('RFC 2833'));
			$select[] = array('value' => 'auto', 'text' => _('Auto'));
			$select[] = array('value' => 'shortinfo', 'text' => _('SIP INFO (application/dtmf)'));
		}
		unset($tt, $ttt, $mydriver, $otherdriver);
		$select[] = array('value' => 'info', 'text' => _('SIP INFO (application/dtmf-relay)'));
		$select[] = array('value' => 'inband', 'text' => _('In band audio (Not recommended)'));
		$tt = _("The DTMF signaling mode used by this device, usually RFC for most phones.").' [dtmfmode]';
		$tmparr['dtmfmode'] = array('prompttext' => _('DTMF Signaling'), 'value' => 'rfc2833', 'tt' => $tt, 'select' => $select, 'level' => 0);

		unset($select);
		$tt = _("Re-Invite policy for this device, see Asterisk documentation for details.").' [canreinvite]';
		$select[] = array('value' => 'no', 'text' => _('No'));
		$select[] = array('value' => 'yes', 'text' => _('Yes'));
		$select[] = array('value' => 'nonat', 'text' => 'nonat');
		$select[] = array('value' => 'update', 'text' => 'update');
		$tmparr['canreinvite'] = array('prompttext' => _('Can Reinvite'), 'value' => $this->freepbx->Config->get_conf_setting('DEVICE_SIP_CANREINVITE'), 'tt' => $tt, 'select' => $select, 'level' => 1, 'type' => 'radio');

		$tt = _("Asterisk context this device will send calls to. Only change this is you know what you are doing.").' [context]';
		$tmparr['context'] = array('prompttext' => _('Context'), 'value' => 'from-internal', 'tt' => $tt, 'level' => 1);

		$tt = _("Host settings for this device, almost always dynamic for endpoints.").' [host]';
		$tmparr['host'] = array('prompttext' => _('Host'), 'value' => 'dynamic', 'tt' => $tt, 'level' => 1);

		unset($select);
		$select[] = array('value' => 'no', 'text' => _('No'));
		$select[] = array('value' => 'yes', 'text' => _('Yes'));
		$tt = _("Whether Asterisk should trust the RPID settings from this device. Usually should be yes for CONNECTEDLINE() functionality to work if supported by the endpoint.").'[trustrpid]';
		$tmparr['trustrpid'] = array('prompttext' => _('Trust RPID'), 'value' => $this->freepbx->Config->get_conf_setting('DEVICE_SIP_TRUSTRPID'), 'tt' => $tt, 'select' => $select, 'level' => 1, 'type' => 'radio');

		unset($select);
		$select[] = array('value' => 'no', 'text' => _('No'));
		$select[] = array('value' => 'yes', 'text' => _('Send Remote-Party-ID header'));

		if (version_compare($this->version,'1.8','ge')) {
			$select[] = array('value' => 'pai', 'text' => _('Send P-Asserted-Identity header'));
		}
		$tt = _("Whether Asterisk should send RPID (or PAI) info to the device. Usually should be enabled to the settings used by your device for CONNECTEDLINE() functionality to work if supported by the endpoint.").'[sendrpid]';
		$tmparr['sendrpid'] = array('prompttext' => _('Send RPID'),'value' => $this->freepbx->Config->get_conf_setting('DEVICE_SIP_SENDRPID'), 'tt' => $tt, 'select' => $select, 'level' => 1);

		unset($select);
		$select[] = array('value' => 'friend', 'text' => 'friend');
		$select[] = array('value' => 'peer', 'text' => 'peer');
		$select[] = array('value' => 'user', 'text' => 'user');
		$tt = _("Asterisk connection type, usually friend for endpoints.").'[type]';
		$tmparr['type'] = array('prompttext' => _('Connection Type'),'value' => 'friend', 'tt' => $tt, 'select' => $select, 'level' => 1);

		unset($select);
		$select[] = array('value' => 'accept', 'text' => _('Accept'));
		$select[] = array('value' => 'originate', 'text' => _('originate'));
		$select[] = array('value' => 'refuse', 'text' => _('Refuse'));
		$tt = _("The sessions are kept alive by sending a RE-INVITE or UPDATE request at a negotiated interval. If a session refresh fails then all the entities that support Session-Timers clear their internal session state. Default is Accept.").'[session-timers]';
		$tmparr['sessiontimers'] = array('prompttext' => _('Session Timers'),'value' => 'accept', 'tt' => $tt, 'select' => $select, 'level' => 1);

		unset($select);
		$select[] = array('value' => 'yes', 'text' => sprintf(_('Yes - (%s)'),'force_rport,comedia'));
		$select[] = array('value' => 'no', 'text' => sprintf(_('No - (%s)'),'no'));

		if (version_compare($this->version,'1.8','ge')) {
			$select[] = array('value' => 'force_rport', 'text' => sprintf(_('Force rport - (%s)'),'force_rport'));
			$select[] = array('value' => 'comedia', 'text' => sprintf(_('comedia - (%s)'),'comedia'));
		}

		if (version_compare($this->version,'11.5','ge')) {
			$select[] = array('value' => 'auto_force_rport,auto_comedia', 'text' => sprintf(_('Automatic Force Both - (%s)'),"auto_force_rport,auto_comedia"));
			$select[] = array('value' => 'auto_force_rport', 'text' => sprintf(_('Automatic Force rport - (%s)'),'auto_force_rport'));
			$select[] = array('value' => 'auto_comedia', 'text' => sprintf(_('Automatic comedia - (%s)'),'auto_comedia'));
		}

		$select[] = array('value' => 'never', 'text' => sprintf(_('Never - (%s)'),'no'));
		$select[] = array('value' => 'route', 'text' => sprintf(_('Route - (%s)'),'force_rport'));

		$tt = _("NAT setting, see Asterisk documentation for details. Yes usually works for both internal and external devices. Set to No if the device will always be internal.").'[nat]';
		$tmparr['nat'] = array('prompttext' => _('NAT Mode'), 'value' => $this->freepbx->Config->get_conf_setting('DEVICE_SIP_NAT'), 'tt' => $tt, 'select' => $select, 'level' => 0);

		$tt = _("Endpoint port number to use, usually 5060. Some 2 ports devices such as ATA may used 5061 for the second port.");
		$tmparr['port'] = array('prompttext' => _('Port'),'value' => '5060', 'tt' => $tt, 'level' => 1);
		$tt = _("Setting to yes (equivalent to 2000 msec) will send an OPTIONS packet to the endpoint periodically (default every minute). Used to monitor the health of the endpoint. If delays are longer then the qualify time, the endpoint will be taken offline and considered unreachable. Can be set to a value which is the msec threshhold. Setting to no will turn this off. Can also be helpful to keep NAT pinholes open.");
		$tmparr['qualify'] = array('prompttext' => _('Qualify'), 'value' => $this->freepbx->Config->get_conf_setting('DEVICE_QUALIFY'), 'tt' => $tt, 'level' => 1);
		if (version_compare($this->version,'1.6','ge')) {
			$tt = _("Frequency in seconds to send qualify messages to the endpoint.");
			$tmparr['qualifyfreq'] = array('prompttext' => _('Qualify Frequency'), 'value' => $this->freepbx->Config->get_conf_setting('DEVICE_SIP_QUALIFYFREQ'), 'tt' => $tt, 'level' => 1);
		}
		if (version_compare($this->version,'1.8','ge')) {
			unset($select);
			$select[] = array('value' => 'udp,tcp,tls', 'text' => _('All - UDP Primary'));
			$select[] = array('value' => 'tcp,udp,tls', 'text' => _('All - TCP Primary'));
			$select[] = array('value' => 'tls,udp,tcp', 'text' => _('All - TLS Primary'));
			if (version_compare($this->version,'11','ge')) {
				$select[] = array('value' => 'ws,udp,tcp,tls', 'text' => _('All - WS Primary'));
				$select[] = array('value' => 'wss,udp,tcp,tls', 'text' => _('All - WSS Primary'));
			}
			$select[] = array('value' => 'udp', 'text' => _('UDP Only'));
			$select[] = array('value' => 'tcp', 'text' => _('TCP Only'));
			$select[] = array('value' => 'tls', 'text' => _('TLS Only'));
			if (version_compare($this->version,'11','ge')) {
				$select[] = array('value' => 'ws', 'text' => _('WS Only'));
				$select[] = array('value' => 'wss', 'text' => _('WSS Only'));
				$select[] = array('value' => 'wss,ws', 'text' => _('WS, WSS Only - WSS Primary'));
			}
			$tt = _("This sets the allowed transport settings for this device and the default (Primary) transport for outgoing. The default transport is only used for outbound messages until a registration takes place.  During the peer registration the transport type may change to another supported type if the peer requests so. In most common cases, this does not have to be changed as most devices register in conjunction with the host=dynamic setting. If you are using TCP and/or TLS you need to make sure the general SIP Settings are configured for the system to operate in those modes and for TLS, proper certificates have been generated and configured. If you are using websockets (such as WebRTC) then you must select an option that includes WS");
			$tmparr['transport'] = array('prompttext' => _('Transport'), 'value' => 'Auto', 'tt' => $tt, 'select' => $select, 'level' => 1);

			if (version_compare($this->version,'11','ge')) {
				unset($select);
				$select[] = array('value' => 'no', 'text' => _('No'));
				$select[] = array('value' => 'yes', 'text' => _('Yes'));
				$tt = _("Whether to Enable AVPF. Defaults to no. The WebRTC standard has selected AVPF as the audio video profile to use for media streams. This is not the default profile in use by Asterisk. As a result the following must be enabled to use WebRTC");
				$tmparr['avpf'] = array('prompttext' => _('Enable AVPF'), 'value' => 'no', 'tt' => $tt, 'select' => $select, 'level' => 1, 'type' => 'radio');
			}

			if (version_compare($this->version,'11','ge')) {
				unset($select);
				$select[] = array('value' => 'no', 'text' => _('No'));
				$select[] = array('value' => 'yes', 'text' => _('Yes'));
				$tt = _("Force 'RTP/AVP', 'RTP/AVPF', 'RTP/SAVP', and 'RTP/SAVPF' to be used for media streams when appropriate, even if a DTLS stream is present.");
				$tmparr['force_avp'] = array('prompttext' => _('Force AVP'), 'value' => 'no', 'tt' => $tt, 'select' => $select, 'level' => 1, 'type' => 'radio');
			}

			if (version_compare($this->version,'11','ge')) {
				unset($select);
				$select[] = array('value' => 'no', 'text' => _('No'));
				$select[] = array('value' => 'yes', 'text' => _('Yes'));
				$tt = _("Whether to Enable ICE Support. Defaults to no. ICE (Interactive Connectivity Establishment) is a protocol for Network Address Translator(NAT) traversal for UDP-based multimedia sessions established with the offer/answer model. This option is commonly enabled in WebRTC setups");
				$tmparr['icesupport'] = array('prompttext' => _('Enable ICE Support'),'value' => 'no', 'tt' => $tt, 'select' => $select, 'level' => 1, 'type' => 'radio');
			}

			unset($select);
			$select[] = array('value' => 'no', 'text' => _('No'));
			$select[] = array('value' => 'yes', 'text' => _('Yes (SRTP only)'));
			$tt = _("Whether to offer SRTP encrypted media (and only SRTP encrypted media) on outgoing calls to a peer. Calls will fail with HANGUPCAUSE=58 if the peer does not support SRTP. Defaults to no.");
			$tmparr['encryption'] = array('prompttext' => _('Enable Encryption'), 'value' => $this->freepbx->Config->get_conf_setting('DEVICE_SIP_ENCRYPTION'), 'tt' => $tt, 'select' => $select, 'level' => 1, 'type' => 'radio');
		}

		unset($select);
		$select[] = array('value' => 'no', 'text' => _('No'));
		$select[] = array('value' => 'yes', 'text' => _('Yes'));
		$select[] = array('value' => 'inherit', 'text' => _('Inherit'));
		$tt = _("Enable or disable video support for this extension. If set to inherit it will use the global value from SIP Settings. Default is inherit");
		$tmparr['videosupport'] = array('prompttext' => _('Video Support'),'value' => 'inherit', 'tt' => $tt, 'select' => $select, 'level' => 1, 'type' => 'radio');
		//videosupport

		$tt = _("Callgroup(s) that this device is part of, can be one or more alpha/numeric callgroups, e.g. '1,3000-3005,sales,sales2'.");
		$tmparr['namedcallgroup'] = array('prompttext' => _('Call Groups'),'value' => $this->freepbx->Config->get_conf_setting('DEVICE_CALLGROUP'), 'tt' => $tt, 'level' => 1, 'jsvalidation' => "frm_".$display."_pickupGroup()");
		$tt = _("Pickupgroups(s) that this device can pickup calls from, can be one or more alpha/numeric callgroups, e.g. '1,3000-3005,sales,sales2'. Device does not have to be in a group to be able to pickup calls from that group.");
		$tmparr['namedpickupgroup'] = array('prompttext' => _('Pickup Groups'),'value' => $this->freepbx->Config->get_conf_setting('DEVICE_PICKUPGROUP'), 'tt' => $tt, 'level' => 1, 'jsvalidation' => "frm_".$display."_pickupGroup()");
		$tt = _("Disallowed codecs. Set this to all to remove all codecs defined in the general settings and then specify specific codecs separated by '&' on the 'allow' setting, or just disallow specific codecs separated by '&'.");
		$tmparr['disallow'] = array('prompttext' => _('Disallowed Codecs'), 'value' => $this->freepbx->Config->get_conf_setting('DEVICE_DISALLOW'), 'tt' => $tt, 'level' => 1);
		$tt = _("Allow specific codecs, separated by the '&' sign and in priority order. E.g. 'ulaw&g729'. Codecs allowed in the general settings will also be allowed unless removed with the 'disallow' directive.");
		$tmparr['allow'] = array('prompttext' => _('Allowed Codecs'), 'value' => $this->freepbx->Config->get_conf_setting('DEVICE_ALLOW'), 'tt' => $tt, 'level' => 1);
		$tt = _("How to dial this device, this should not be changed unless you know what you are doing.");
		$tmparr['dial'] = array('prompttext' => _('Dial'), 'value' => '', 'tt' => $tt, 'level' => 2);
		$tt = _("Accountcode for this device.");
		$tmparr['accountcode'] = array('prompttext' => _('Account Code'), 'value' => '', 'tt' => $tt, 'level' => 1);
		$tt = _("Mailbox for this device. This should not be changed unless you know what you are doing.");
		$tmparr['mailbox'] = array('prompttext' => _('Mailbox'), 'value' => '', 'tt' => $tt, 'level' => 2);
		$tt = _("Asterisk dialplan extension to reach voicemail for this device. Some devices use this to auto-program the voicemail button on the endpoint. If left blank, the default vmexten setting is automatically configured by the voicemail module. Only change this on devices that may have special needs.");
		$tmparr['vmexten'] = array('prompttext' => _('Voicemail Extension'), 'value' => '', 'tt' => $tt, 'level' => 1);
		$tt = _("IP Address range to deny access to, in the form of network/netmask.")." "._("You may add multiple subnets, separate them with an &amp;.");
		$tmparr['deny'] = array('prompttext' => _('Deny'), 'value' => '0.0.0.0/0.0.0.0', 'tt' => $tt, 'level' => 1);
		$tt = _("IP Address range to allow access to, in the form of network/netmask. This can be a very useful security option when dealing with remote extensions that are at a known location (such as a branch office) or within a known ISP range for some home office situations.")." "._("You may add multiple subnets, separate them with an &amp;.");
		$tmparr['permit'] = array('prompttext' => _('Permit'), 'value' => '0.0.0.0/0.0.0.0', 'tt' => $tt, 'level' => 1);
		$currentcomponent->addjsfunc('changeDriver()',"
		if(confirm('"._('Are you Sure you want to Change the SIP Channel Driver? (The Page will Refresh, then you MUST hit submit to resave the device when you are done to propagate the new settings)')."')) {
			if($('#devinfo_sipdriver').val() == 'chan_sip') {
				$('#devinfo_sipdriver').val('chan_pjsip');
			} else {
				$('#devinfo_sipdriver').val('chan_sip');
			}
			$('form[name=frm_".$display."]').append('<input type=\"hidden\" name=\"changesipdriver\" value=\"yes\">');
			$('form[name=frm_".$display."]').submit();
		}
		",0);
		$currentcomponent->addjsfunc('pickupGroup()',"
		var val = $('#devinfo_pickupgroup').val();
		if(isEmpty(val) && val != '0') {
			return false;
		}
		var commas = $('#devinfo_pickupgroup').val().split(','), stop = false;
		$.each(commas, function(i,v) {
			var s = v.split('-');
			$.each(s, function(i,v) {
				if(parseInt(v) < 1 || parseInt(v) > 63) {
					alert('"._("Value must be between 1 and 63")."')
					stop = true;
				}
			});
		});
		return stop;
		",0);
		$devopts = $tmparr;

		return $devopts;
	}

	public function getDeviceHeaders() {
		return array(
			'secret' => array('identifier' => _('Secret'), 'description' => sprintf(_('Secret [Enter "%s" to regenerate]'),"REGEN")),
		);
	}
}

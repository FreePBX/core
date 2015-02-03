<?php
// vim: set ai ts=4 sw=4 ft=php:
namespace FreePBX\modules\Core\Drivers;
class Sip extends \FreePBX\modules\Core\Driver {
	public function getInfo() {
		return array(
			"rawName" => "sip",
			"hardware" => "sip_generic",
			"prettyName" => _("Generic CHAN SIP Driver"),
			"description" => _("The legacy SIP channel driver in Asterisk"),
			"asteriskSupport" => ">=1.0"
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
				"value" => "rfc2833",
				"flag" => $flag++
			),
			"canreinvite" => array(
				"value" => "no",
				"flag" => $flag++
			),
			"host" => array(
				"value" => "dynamic",
				"flag" => $flag++
			),
			"trustpid" => array(
				"value" => "yes",
				"flag" => $flag++
			),
			"sendpid" => array(
				"value" => "no",
				"flag" => $flag++
			),
			"type" => array(
				"value" => "friend",
				"flag" => $flag++
			),
			"nat" => array(
				"value" => "no",
				"flag" => $flag++
			),
			"port" => array(
				"value" => "5060",
				"flag" => $flag++
			),
			"qualify" => array(
				"value" => "yes",
				"flag" => $flag++
			),
			"qualifyfreq" => array(
				"value" => "60",
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
			"icesupport" => array(
				"value" => "no",
				"flag" => $flag++
			),
			"encryption" => array(
				"value" => "no",
				"flag" => $flag++
			),
			"callgroup" => array(
				"value" => "",
				"flag" => $flag++
			),
			"pickupgroup" => array(
				"value" => "",
				"flag" => $flag++
			),
			"disallow" => array(
				"value" => "",
				"flag" => $flag++
			),
			"allow" => array(
				"value" => "",
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
			try {
				$sth->execute(array($id,$key,$setting['value'],$setting['flag']));
			} catch(\Exception $e) {
				die_freepbx($e->getMessage()."<br><br>".'error adding to SIP table');
			}
		}
		return true;
	}

	public function delDevice($id) {
		$sql = "DELETE FROM sip WHERE id = ?";
		$sth = $this->database->prepare($sql);
		try {
			$sth->execute(array($id));
		} catch(\Exception $e) {
			die_freepbx($e->getMessage().$sql);
		}
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
		$techd = ($deviceInfo['tech'] == 'sip') ? 'CHAN_SIP' : strtoupper($deviceInfo['tech']);
		if(function_exists('sipsettings_get') && $techd == 'CHAN_SIP') {
			$out = sipsettings_get();
			$pport = $out['bindaddr'].':'.$out['bindport'];
		} elseif(method_exists($this->freepbx->Sipsettings,'getBinds') && $techd == 'PJSIP') {
			$out = $this->freepbx->Sipsettings->getBinds();
			foreach($out as $o) {
				$pport .= $o.', ';
			}
			$pport = rtrim($pport,", ");
		} else {
			$pport = '';
		}

		$extrac = !empty($pport) ? sprintf(_('listening on <strong>%s</strong>'),$pport) : '';
		$device_uses = sprintf(_("This device uses %s technology %s"),"<strong>".$techd."</strong>",$extrac).(strtoupper($devinfo['tech']) == 'ZAP' && ast_with_dahdi()?" ("._("Via DAHDi compatibility mode").")":"");
		$currentcomponent->addguielem($primarySection, new \gui_label('techlabel', '<div class="alert alert-info" role="alert" style="width:100%">'.$device_uses.'</div>'),1, null, $category);
		// We need to scream loudly if this device is using a channel driver that's disabled.
		if ($devinfo_tech == "pjsip" || $devinfo['tech'] == "sip") {
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
		$tt = _("Password (secret) configured for the device. Should be alphanumeric with at least 2 letters and numbers to keep secure.").' [secret]';
		$tmparr['secret'] = array('prompttext' => 'Secret', 'class' => 'password-meter', 'value' => '', 'tt' => $tt, 'level' => 0, 'jsvalidation' => $secret_validation, 'failvalidationmsg' => $msgInvalidSecret, 'category' => $category, 'section' => $primarySection);

		$section = _("Device Options");
		$category = "advanced";

		$sipdriver = $this->freepbx->Config->get_conf_setting('ASTSIPDRIVER');
		$tmparr['sipdriver'] = array('hidden' => true, 'value' => 'chan_'.strtolower($deviceInfo['tech']), 'level' => 0);

		//Inverted Driver, only allow the change if in certain modes
		if ($deviceInfo['tech'] == "sip") {
			$mydriver = "CHAN_SIP";
			$otherdriver = "CHAN_PJSIP";
		} else {
			$mydriver = "CHAN_PJSIP";
			$otherdriver = "CHAN_SIP";
		}
		$ttt = sprintf(_("Change To %s Driver"),$otherdriver);
		if($sipdriver == 'both' || ($sipdriver == 'chan_sip' && $deviceInfo['tech'] == 'pjsip') || ($sipdriver == 'chan_pjsip' && $deviceInfo['tech'] == 'sip')) {
			$tt = _("Change the SIP Channel Driver to use $otherdriver.");
			$tmparr['changecdriver'] = array('text' => $ttt, 'prompttext' => 'Change SIP Driver', 'type' => 'button', 'value' => 'button', 'tt' => $tt, 'level' => 1, 'jsvalidation' => "frm_".$dispnum."_changeDriver();return false;");
		} else {
			$tt = _("You cannot change to $otherdriver as it is not enabled. Please enable $otherdriver in Advanced Settings");
			$tmparr['changecdriver'] = array('text' => _("Changing SIP Driver unavailable"), 'prompttext' => $ttt, 'type' => 'button', 'value' => 'button', 'tt' => $tt, 'level' => 1, 'disable' => true);
		}

		if ($mydriver == "CHAN_PJSIP") {
			$select[] = array('value' => 'rfc4733', 'text' => _('RFC 4733'));
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
		// $amp_conf['DEVICE_SIP_CANREINVITE']
		// $amp_conf['DEVICE_SIP_TRUSTRPID']
		// $amp_conf['DEVICE_SIP_SENDRPID']
		// $amp_conf['DEVICE_SIP_NAT']
		// $amp_conf['DEVICE_SIP_ENCRYPTION']
		// $amp_conf['DEVICE_SIP_QUALIFYFREQ']
		// $amp_conf['DEVICE_QUALIFY']
		// $amp_conf['DEVICE_DISALLOW']
		// $amp_conf['DEVICE_ALLOW']
		// $amp_conf['DEVICE_CALLGROUP']
		// $amp_conf['DEVICE_PICKUPGROUP']

		unset($select);
		$tt = _("Re-Invite policy for this device, see Asterisk documentation for details.").' [canreinvite]';
		$select[] = array('value' => 'no', 'text' => _('No'));
		$select[] = array('value' => 'yes', 'text' => _('Yes'));
		$select[] = array('value' => 'nonat', 'text' => 'nonat');
		$select[] = array('value' => 'update', 'text' => 'update');
		$tmparr['canreinvite'] = array('prompttext' => _('Can Reinvite'), 'value' => $amp_conf['DEVICE_SIP_CANREINVITE'], 'tt' => $tt, 'select' => $select, 'level' => 1);

		$tt = _("Asterisk context this device will send calls to. Only change this is you know what you are doing.").' [context]';
		$tmparr['context'] = array('prompttext' => _('Context'), 'value' => 'from-internal', 'tt' => $tt, 'level' => 1);

		$tt = _("Host settings for this device, almost always dynamic for endpoints.").' [host]';
		$tmparr['host'] = array('prompttext' => _('Host'), 'value' => 'dynamic', 'tt' => $tt, 'level' => 1);

		unset($select);
		$select[] = array('value' => 'no', 'text' => _('No'));
		$select[] = array('value' => 'yes', 'text' => _('Yes'));
		$tt = _("Whether Asterisk should trust the RPID settings from this device. Usually should be yes for CONNECTEDLINE() functionality to work if supported by the endpoint.").'[trustrpid]';
		$tmparr['trustrpid'] = array('prompttext' => _('Trust RPID'), 'value' => $amp_conf['DEVICE_SIP_TRUSTRPID'], 'tt' => $tt, 'select' => $select, 'level' => 1);

		unset($select);
		$select[] = array('value' => 'no', 'text' => _('No'));
		$select[] = array('value' => 'yes', 'text' => _('Send Remote-Party-ID header'));

		if (version_compare($amp_conf['ASTVERSION'],'1.8','ge')) {
			$select[] = array('value' => 'pai', 'text' => _('Send P-Asserted-Identity header'));
		}
		$tt = _("Whether Asterisk should send RPID (or PAI) info to the device. Usually should be enabled to the settings used by your device for CONNECTEDLINE() functionality to work if supported by the endpoint.").'[sendrpid]';
		$tmparr['sendrpid'] = array('prompttext' => _('Send RPID'),'value' => $amp_conf['DEVICE_SIP_SENDRPID'], 'tt' => $tt, 'select' => $select, 'level' => 1);

		unset($select);
		$select[] = array('value' => 'friend', 'text' => 'friend');
		$select[] = array('value' => 'peer', 'text' => 'peer');
		$select[] = array('value' => 'user', 'text' => 'user');
		$tt = _("Asterisk connection type, usually friend for endpoints.").'[type]';
		$tmparr['type'] = array('prompttext' => _('Connection Type'),'value' => 'friend', 'tt' => $tt, 'select' => $select, 'level' => 1);

		unset($select);
		$select[] = array('value' => 'yes', 'text' => _('Yes - (force_rport,comedia)'));
		$select[] = array('value' => 'no', 'text' => _('No - (no)'));

		if (version_compare($amp_conf['ASTVERSION'],'1.8','ge')) {
			$select[] = array('value' => 'force_rport', 'text' => _('Force rport - (force_rport)'));
			$select[] = array('value' => 'comedia', 'text' => _('comedia - (comedia)'));
		}

		if (version_compare($amp_conf['ASTVERSION'],'11.5','ge')) {
			$select[] = array('value' => 'auto_force_rport,auto_comedia', 'text' => _('Automatic Force Both - (auto_force_rport,auto_comedia)'));
			$select[] = array('value' => 'auto_force_rport', 'text' => _('Automatic Force rport - (auto_force_rport)'));
			$select[] = array('value' => 'auto_comedia', 'text' => _('Automatic comedia - (auto_comedia)'));
		}

		$select[] = array('value' => 'never', 'text' => _('never - (no)'));
		$select[] = array('value' => 'route', 'text' => _('route - (force_rport)'));

		$tt = _("NAT setting, see Asterisk documentation for details. Yes usually works for both internal and external devices. Set to No if the device will always be internal.").'[nat]';
		$tmparr['nat'] = array('prompttext' => _('NAT Mode'), 'value' => $amp_conf['DEVICE_SIP_NAT'], 'tt' => $tt, 'select' => $select, 'level' => 0);

		$tt = _("Endpoint port number to use, usually 5060. Some 2 ports devices such as ATA may used 5061 for the second port.");
		$tmparr['port'] = array('prompttext' => _('Port'),'value' => '5060', 'tt' => $tt, 'level' => 1);
		$tt = _("Setting to yes (equivalent to 2000 msec) will send an OPTIONS packet to the endpoint periodically (default every minute). Used to monitor the health of the endpoint. If delays are longer then the qualify time, the endpoint will be taken offline and considered unreachable. Can be set to a value which is the msec threshhold. Setting to no will turn this off. Can also be helpful to keep NAT pinholes open.");
		$tmparr['qualify'] = array('prompttext' => _('Qualify'), 'value' => $amp_conf['DEVICE_QUALIFY'], 'tt' => $tt, 'level' => 1);
		if (version_compare($amp_conf['ASTVERSION'],'1.6','ge')) {
			$tt = _("Frequency in seconds to send qualify messages to the endpoint.");
			$tmparr['qualifyfreq'] = array('prompttext' => _('Qualify Frequency'), 'value' => $amp_conf['DEVICE_SIP_QUALIFYFREQ'], 'tt' => $tt, 'level' => 1);
		}
		if (version_compare($amp_conf['ASTVERSION'],'1.8','ge')) {
			unset($select);
			$select[] = array('value' => 'udp,tcp,tls', 'text' => _('All - UDP Primary'));
			$select[] = array('value' => 'tcp,udp,tls', 'text' => _('All - TCP Primary'));
			$select[] = array('value' => 'tls,udp,tcp', 'text' => _('All - TLS Primary'));
			if (version_compare($amp_conf['ASTVERSION'],'11','ge')) {
				$select[] = array('value' => 'ws,udp,tcp,tls', 'text' => _('All - WS Primary'));
			}
			$select[] = array('value' => 'udp', 'text' => _('UDP Only'));
			$select[] = array('value' => 'tcp', 'text' => _('TCP Only'));
			$select[] = array('value' => 'tls', 'text' => _('TLS Only'));
			if (version_compare($amp_conf['ASTVERSION'],'11','ge')) {
				$select[] = array('value' => 'ws', 'text' => _('WS Only'));
			}
			$tt = _("This sets the allowed transport settings for this device and the default (Primary) transport for outgoing. The default transport is only used for outbound messages until a registration takes place.  During the peer registration the transport type may change to another supported type if the peer requests so. In most common cases, this does not have to be changed as most devices register in conjunction with the host=dynamic setting. If you are using TCP and/or TLS you need to make sure the general SIP Settings are configured for the system to operate in those modes and for TLS, proper certificates have been generated and configured. If you are using websockets (such as WebRTC) then you must select an option that includes WS");
			$tmparr['transport'] = array('prompttext' => _('Transport'), 'value' => 'Auto', 'tt' => $tt, 'select' => $select, 'level' => 1);

			if (version_compare($amp_conf['ASTVERSION'],'11','ge')) {
				unset($select);
				$select[] = array('value' => 'no', 'text' => _('No'));
				$select[] = array('value' => 'yes', 'text' => _('Yes'));
				$tt = _("Whether to Enable AVPF. Defaults to no. The WebRTC standard has selected AVPF as the audio video profile to use for media streams. This is not the default profile in use by Asterisk. As a result the following must be enabled to use WebRTC");
				$tmparr['avpf'] = array('prompttext' => _('Enable AVPF'), 'value' => 'no', 'tt' => $tt, 'select' => $select, 'level' => 1);
			}

			if (version_compare($amp_conf['ASTVERSION'],'11','ge')) {
				unset($select);
				$select[] = array('value' => 'no', 'text' => _('No'));
				$select[] = array('value' => 'yes', 'text' => _('Yes'));
				$tt = _("Force 'RTP/AVP', 'RTP/AVPF', 'RTP/SAVP', and 'RTP/SAVPF' to be used for media streams when appropriate, even if a DTLS stream is present.");
				$tmparr['force_avp'] = array('prompttext' => _('Force AVP'), 'value' => 'no', 'tt' => $tt, 'select' => $select, 'level' => 1);
			}

			if (version_compare($amp_conf['ASTVERSION'],'11','ge')) {
				unset($select);
				$select[] = array('value' => 'no', 'text' => _('No'));
				$select[] = array('value' => 'yes', 'text' => _('Yes'));
				$tt = _("Whether to Enable ICE Support. Defaults to no. ICE (Interactive Connectivity Establishment) is a protocol for Network Address Translator(NAT) traversal for UDP-based multimedia sessions established with the offer/answer model. This option is commonly enabled in WebRTC setups");
				$tmparr['icesupport'] = array('prompttext' => _('Enable ICE Support'),'value' => 'no', 'tt' => $tt, 'select' => $select, 'level' => 1);
			}

			unset($select);
			$select[] = array('value' => 'no', 'text' => _('No'));
			$select[] = array('value' => 'yes', 'text' => _('Yes (SRTP only)'));
			$tt = _("Whether to offer SRTP encrypted media (and only SRTP encrypted media) on outgoing calls to a peer. Calls will fail with HANGUPCAUSE=58 if the peer does not support SRTP. Defaults to no.");
			$tmparr['encryption'] = array('prompttext' => _('Enable Encryption'), 'value' => $amp_conf['DEVICE_SIP_ENCRYPTION'], 'tt' => $tt, 'select' => $select, 'level' => 1);
		}

		$tt = _("Callgroup(s) that this device is part of, can be one or more callgroups, e.g. '1,3-5' would be in groups 1,3,4,5.");
		$tmparr['callgroup'] = array('prompttext' => _('Call Groups'),'value' => $amp_conf['DEVICE_CALLGROUP'], 'tt' => $tt, 'level' => 1);
		$tt = _("Pickupgroups(s) that this device can pickup calls from, can be one or more groups, e.g. '1,3-5' would be in groups 1,3,4,5. Device does not have to be in a group to be able to pickup calls from that group.");
		$tmparr['pickupgroup'] = array('prompttext' => _('Pickup Groups'),'value' => $amp_conf['DEVICE_PICKUPGROUP'], 'tt' => $tt, 'level' => 1);
		$tt = _("Disallowed codecs. Set this to all to remove all codecs defined in the general settings and then specify specific codecs separated by '&' on the 'allow' setting, or just disallow specific codecs separated by '&'.");
		$tmparr['disallow'] = array('prompttext' => _('Disallowed Codecs'), 'value' => $amp_conf['DEVICE_DISALLOW'], 'tt' => $tt, 'level' => 1);
		$tt = _("Allow specific codecs, separated by the '&' sign and in priority order. E.g. 'ulaw&g729'. Codecs allowed in the general settings will also be allowed unless removed with the 'disallow' directive.");
		$tmparr['allow'] = array('prompttext' => _('Allowed Codecs'), 'value' => $amp_conf['DEVICE_ALLOW'], 'tt' => $tt, 'level' => 1);
		$tt = _("How to dial this device, this should not be changed unless you know what you are doing.");
		$tmparr['dial'] = array('prompttext' => _('Dial'), 'value' => '', 'tt' => $tt, 'level' => 2);
		$tt = _("Accountcode for this device.");
		$tmparr['accountcode'] = array('prompttext' => _('Account Code'), 'value' => '', 'tt' => $tt, 'level' => 1);
		$tt = _("Mailbox for this device. This should not be changed unless you know what you are doing.");
		$tmparr['mailbox'] = array('prompttext' => _('Mailbox'), 'value' => '', 'tt' => $tt, 'level' => 2);
		$tt = _("Asterisk dialplan extension to reach voicemail for this device. Some devices use this to auto-program the voicemail button on the endpoint. If left blank, the default vmexten setting is automatically configured by the voicemail module. Only change this on devices that may have special needs.");
		$tmparr['vmexten'] = array('prompttext' => _('Voicemail Extension'), 'value' => '', 'tt' => $tt, 'level' => 1);
		$tt = _("IP Address range to deny access to, in the form of network/netmask.");
		$tmparr['deny'] = array('prompttext' => _('Deny'), 'value' => '0.0.0.0/0.0.0.0', 'tt' => $tt, 'level' => 1);
		$tt = _("IP Address range to allow access to, in the form of network/netmask. This can be a very useful security option when dealing with remote extensions that are at a known location (such as a branch office) or within a known ISP range for some home office situations.");
		$tmparr['permit'] = array('prompttext' => _('Permit'), 'value' => '0.0.0.0/0.0.0.0', 'tt' => $tt, 'level' => 1);
		$currentcomponent->addjsfunc('changeDriver()',"
		if(confirm('"._('Are you Sure you want to Change the SIP Channel Driver? (The Page will Refresh, then you MUST hit save when you are done to propagate the new settings)')."')) {
			if($('#devinfo_sipdriver').val() == 'chan_sip') {
				$('#devinfo_sipdriver').val('chan_pjsip');
			} else {
				$('#devinfo_sipdriver').val('chan_sip');
			}
			$('form[name=frm_".$display."]').append('<input type=\"hidden\" name=\"changesipdriver\" value=\"yes\">');
			$('form[name=frm_".$display."]').submit();
		}
		",0);
		$devopts = $tmparr;

		return $devopts;
	}
}

<?php
// vim: set ai ts=4 sw=4 ft=php:
namespace FreePBX\modules\Core\Drivers;
class Iax2 extends \FreePBX\modules\Core\Driver {
	public function getInfo() {
		return array(
			"rawName" => "iax2",
			"prettyName" => _("Generic IAX2 Driver"),
			"description" => _("Inter-Asterisk eXchange (IAX) is a communications protocol native to the Asterisk private branch exchange (PBX) software, and is supported by a few other softswitches, PBX systems, and softphones. It is used for transporting VoIP telephony sessions between servers and to terminal devices"),
			"asteriskSupport" => ">=1.0"
		);
	}
	public function getDisplay($display, $deviceInfo, $currentcomponent) {
		$tmparr = array();
		$tt = _("Password (secret) configured for the device. Should be alphanumeric with at least 2 letters and numbers to keep secure.");
		$tmparr['secret'] = array('value' => '', 'tt' => $tt, 'level' => 0, 'jsvalidation' => $secret_validation, 'failvalidationmsg' => $msgInvalidSecret);

		unset($select);
		$select[] = array('value' => 'yes', 'text' => _('Yes'));
		$select[] = array('value' => 'no', 'text' => _('No'));
		$select[] = array('value' => 'mediaonly', 'text' => _('Media Only'));
		$tt = _("IAX transfer capabilities, see the Asterisk documentation for details.");
		$tmparr['transfer'] = array('value' => 'yes', 'tt' => $tt, 'select' => $select, 'level' => 1);

		$tt = _("Asterisk context this device will send calls to. Only change this is you know what you are doing.");
		$tmparr['context'] = array('value' => 'from-internal', 'tt' => $tt, 'level' => 1);
		$tt = _("Host settings for this device, almost always dynamic for endpoints.");
		$tmparr['host'] = array('value' => 'dynamic', 'tt' => $tt, 'level' => 1);

		unset($select);
		$select[] = array('value' => 'friend', 'text' => 'friend');
		$select[] = array('value' => 'peer', 'text' => 'peer');
		$select[] = array('value' => 'user', 'text' => 'user');
		$tt = _("Asterisk connection type, usually friend for endpoints.");
		$tmparr['type'] = array('value' => 'friend', 'tt' => $tt, 'select' => $select, 'level' => 1);

		$tt = _("Endpoint port number to use, usually 4569.");
		$tmparr['port'] = array('value' => '4569', 'tt' => $tt, 'level' => 1);
		$tt = _("Setting to yes (equivalent to 2000 msec) will send an OPTIONS packet to the endpoint periodically (default every minute). Used to monitor the health of the endpoint. If delays are longer then the qualify time, the endpoint will be taken offline and considered unreachable. Can be set to a value which is the msec threshold. Setting to no will turn this off. Can also be helpful to keep NAT pinholes open.");
		$tmparr['qualify'] = array('value' => $amp_conf['DEVICE_QUALIFY'], 'tt' => $tt, 'level' => 1);
		$tt = _("Disallowed codecs. Set this to all to remove all codecs defined in the general settings and then specify specific codecs separated by '&' on the 'allow' setting, or just disallow specific codecs separated by '&'.");
		$tmparr['disallow'] = array('value' => $amp_conf['DEVICE_DISALLOW'], 'tt' => $tt, 'level' => 1);
		$tt = _("Allow specific codecs, separated by the '&' sign and in priority order. E.g. 'ulaw&g729'. Codecs allowed in the general settings will also be allowed unless removed with the 'disallow' directive.");
		$tmparr['allow'] = array('value' => $amp_conf['DEVICE_ALLOW'], 'tt' => $tt, 'level' => 1);
		$tt = _("How to dial this device, this should not be changed unless you know what you are doing.");
		$tmparr['dial'] = array('value' => '', 'tt' => $tt, 'level' => 2);
		$tt = _("Accountcode for this device.");
		$tmparr['accountcode'] = array('value' => '', 'tt' => $tt, 'level' => 1);
		$tt = _("Mailbox for this device. This should not be changed unless you know what you are doing.");
		$tmparr['mailbox'] = array('value' => '', 'tt' => $tt, 'level' => 2);
		$tt = _("IP Address range to deny access to, in the form of network/netmask.");
		$tmparr['deny'] = array('value' => '0.0.0.0/0.0.0.0', 'tt' => $tt, 'level' => 1);
		$tt = _("IP Address range to allow access to, in the form of network/netmask. This can be a very useful security option when dealing with remote extensions that are at a known location (such as a branch office) or within a known ISP range for some home office situations.");
		$tmparr['permit'] = array('value' => '0.0.0.0/0.0.0.0', 'tt' => $tt, 'level' => 1);

		unset($select);
		$select[] = array('value' => 'yes', 'text' => _('Yes'));
		$select[] = array('value' => 'no', 'text' => _('No'));
		$select[] = array('value' => 'auto', 'text' => _('Auto'));
		$tt = _("IAX security setting. See IAX documentation and device compatibility for details.");
		$tmparr['requirecalltoken'] = array('value' => 'yes', 'tt' => $tt, 'select' => $select, 'level' => 1);
		$devopts = $tmparr;
		return $devopts;
	}
}

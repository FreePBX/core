<?php
// vim: set ai ts=4 sw=4 ft=php:
namespace FreePBX\modules\Core\Drivers;
class Zap extends \FreePBX\modules\Core\Driver {
	public function getInfo() {
		return array(
			"rawName" => "zap",
			"prettyName" => _("Generic ZAPTEL Driver"),
			"asteriskSupport" => ">=1.0"
		);
	}
	public function getDisplay($display, $deviceInfo, $currentcomponent) {
		$tmparr = array();
		$tt = _("The Zap channel number for this port.");
		$tmparr['channel'] = array('value' => '', 'tt' => $tt, 'level' => 0, 'jsvalidation' => 'isEmpty()', 'failvalidationmsg' => $msgInvalidChannel);
		$tt = _("Asterisk context this device will send calls to. Only change this is you know what you are doing.");
		$tmparr['context'] = array('value' => 'from-internal', 'tt' => $tt, 'level' => 1);
		unset($select);
		$select[] = array('value' => 'yes', 'text' => _('Yes'));
		$select[] = array('value' => 'no', 'text' => _('No'));
		$tt = _("Zap immediate mode setting, see Zap documentation for details.");
		$tmparr['immediate'] = array('value' => 'no', 'tt' => $tt, 'select' => $select, 'level' => 1);
		$tt = _("Zap signaling, usually fxo_ks when connected to an analog phone. Some special applications or channel bank connections may require fxs_ks or other valid settings. See Zap and card documentation for details.");
		$tmparr['signalling'] = array('value' => 'fxo_ks', 'tt' => $tt, 'level' => 1);
		$tt = _("Zap echocancel setting, see Zap documentation for details.");
		$tmparr['echocancel'] = array('value' => 'yes', 'tt' => $tt, 'level' => 1);
		unset($select);
		$select[] = array('value' => 'yes', 'text' => _('Yes'));
		$select[] = array('value' => 'no', 'text' => _('No'));
		$tt = _("Whether to turn on echo cancellation when bridging between Zap channels. See Zap documentation for details.");
		$tmparr['echocancelwhenbridged'] = array('value' => 'no', 'tt' => $tt, 'select' => $select, 'level' => 1);
		$tt = _("Echo training requirements of this card. See Zap documentation for details.");
		$tmparr['echotraining'] = array('value' => '800', 'tt' => $tt, 'level' => 1);
		unset($select);
		$select[] = array('value' => 'yes', 'text' => _('Yes'));
		$select[] = array('value' => 'no', 'text' => _('No'));
		$tt = _("Experimental and un-reliable setting to try and detect a busy signal. See Zap documentation for details.");
		$tmparr['busydetect'] = array('value' => 'no', 'tt' => $tt, 'select' => $select, 'level' => 1);
		$tt = _("Experimental and un-reliable setting to try and detect a busy signal, number of iterations to conclude busy. See Zap documentation for details.");
		$tmparr['busycount'] = array('value' => '7', 'tt' => $tt, 'level' => 1);
		unset($select);
		$select[] = array('value' => 'yes', 'text' => _('Yes'));
		$select[] = array('value' => 'no', 'text' => _('No'));
		$tt = _("Experimental and un-reliable setting to try and detect call progress tones. See Zap documentation for details.");
		$tmparr['callprogress'] = array('value' => 'no', 'tt' => $tt, 'select' => $select, 'level' => 1);
		$tt = _("How to dial this device, this should not be changed unless you know what you are doing.");
		$tmparr['dial'] = array('value' => '', 'tt' => $tt, 'level' => 2);
		$tt = _("Accountcode for this device.");
		$tmparr['accountcode'] = array('value' => '', 'tt' => $tt, 'level' => 1);
		$tt = _("Callgroup(s) that this device is part of, can be one or more callgroups, e.g. '1,3-5' would be in groups 1,3,4,5.");
		$tmparr['callgroup'] = array('value' => $amp_conf['DEVICE_CALLGROUP'], 'tt' => $tt, 'level' => 1);
		$tt = _("Pickupgroups(s) that this device can pickup calls from, can be one or more groups, e.g. '1,3-5' would be in groups 1,3,4,5. Device does not have to be in a group to be able to pickup calls from that group.");
		$tmparr['pickupgroup'] = array('value' => $amp_conf['DEVICE_PICKUPGROUP'], 'tt' => $tt, 'level' => 1);
		$tt = _("Mailbox for this device. This should not be changed unless you know what you are doing.");
		$tmparr['mailbox'] = array('value' => '', 'tt' => $tt, 'level' => 2);
		return $tmparr;
	}
}

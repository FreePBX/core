<?php
// vim: set ai ts=4 sw=4 ft=php:
namespace FreePBX\modules\Core\Drivers;
class Dahdi extends \FreePBX\modules\Core\Driver {
	public function getInfo() {
		return array(
			"rawName" => "dahdi",
			"hardware" => "dahdi_generic",
			"prettyName" => _("Generic DAHDi Driver"),
			"shortName" => "DAHDi",
			"description" => _("Short for 'Digium Asterisk Hardware Device Interface'"),
			"asteriskSupport" => ">=1.0"
		);
	}

	public function addDevice($id, $settings) {
		$sql = 'INSERT INTO dahdi (id, keyword, data) values (?,?,?)';
		$sth = $this->database->prepare($sql);
		foreach($settings as $key => $setting) {
			try {
				$sth->execute(array($id,$key,$setting['value']));
			} catch(\Exception $e) {
				die_freepbx($e->getMessage()."<br><br>".'error adding to DAHDI table');
			}
		}
		return true;
	}

	public function delDevice($id) {
		$sql = "DELETE FROM dahdi WHERE id = ?";
		$sth = $this->database->prepare($sql);
		try {
			$sth->execute(array($id));
		} catch(\Exception $e) {
			die_freepbx($e->getMessage().$sql);
		}
		return true;
	}

	public function getDevice($id) {
		$sql = "SELECT keyword,data FROM dahdi WHERE id = ?";
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

	public function getDeviceDisplay($display, $deviceInfo, $currentcomponent) {
		$tmparr = array();
		$tt = _("The DAHDi channel number for this port.");
		$tmparr['channel'] = array('value' => '', 'tt' => $tt, 'level' => 0, 'jsvalidation' => 'isEmpty()', 'failvalidationmsg' => $msgInvalidChannel);
		$tt = _("Asterisk context this device will send calls to. Only change this is you know what you are doing.");
		$tmparr['context'] = array('value' => 'from-internal', 'tt' => $tt, 'level' => 1);

		unset($select);
		$select[] = array('value' => 'yes', 'text' => _('Yes'));
		$select[] = array('value' => 'no', 'text' => _('No'));
		$tt = _("DAHDi immediate mode setting, see DAHDi documentation for details.");
		$tmparr['immediate'] = array('value' => 'no', 'tt' => $tt, 'select' => $select, 'level' => 1);

		$tt = _("DAHDi signalling, usually fxo_ks when connected to an analog phone. Some special applications or channel bank connections may require fxs_ks or other valid settings. See DAHDi and card documentation for details.");
		$tmparr['signalling'] = array('value' => 'fxo_ks', 'tt' => $tt, 'level' => 1);
		$tt = _("DAHDi echocancel setting, see DAHDi documentation for details.");
		$tmparr['echocancel'] = array('value' => 'yes', 'tt' => $tt, 'level' => 1);

		unset($select);
		$select[] = array('value' => 'yes', 'text' => _('Yes'));
		$select[] = array('value' => 'no', 'text' => _('No'));
		$tt = _("Whether to turn on echo cancellation when bridging between DAHDi channels. See DAHDi documentation for details.");
		$tmparr['echocancelwhenbridged'] = array('value' => 'no', 'tt' => $tt, 'select' => $select, 'level' => 1);

		$tt = _("Echo training requirements of this card. See DAHDi documentation for details.");
		$tmparr['echotraining'] = array('value' => '800', 'tt' => $tt, 'level' => 1);

		unset($select);
		$select[] = array('value' => 'yes', 'text' => _('Yes'));
		$select[] = array('value' => 'no', 'text' => _('No'));
		$tt = _("Experimental and un-reliable setting to try and detect a busy signal. See DAHDi documentation for details.");
		$tmparr['busydetect'] = array('value' => 'no', 'tt' => $tt, 'select' => $select, 'level' => 1);

		$tt = _("Experimental and un-reliable setting to try and detect a busy signal, number of iterations to conclude busy. See DAHDi documentation for details.");
		$tmparr['busycount'] = array('value' => '7', 'tt' => $tt, 'level' => 1);

		unset($select);
		$select[] = array('value' => 'yes', 'text' => _('Yes'));
		$select[] = array('value' => 'no', 'text' => _('No'));
		$tt = _("Experimental and un-reliable setting to try and detect call progress tones. See DAHDi documentation for details.");
		$tmparr['callprogress'] = array('value' => 'no', 'tt' => $tt, 'select' => $select, 'level' => 1);

		$tt = _("How to dial this device, this should not be changed unless you know what you are doing.");
		$tmparr['dial'] = array('value' => '', 'level' => 2);
		$tt = _("Accountcode for this device.");
		$tmparr['accountcode'] = array('value' => '', 'tt' => $tt, 'level' => 1);
		$tt = _("Callgroup(s) that this device is part of, can be one or more callgroups, e.g. '1,3-5' would be in groups 1,3,4,5.");
		$tmparr['callgroup'] = array('value' => $amp_conf['DEVICE_CALLGROUP'], 'tt' => $tt, 'level' => 1);
		$tt = _("Pickupgroups(s) that this device can pickup calls from, can be one or more groups, e.g. '1,3-5' would be in groups 1,3,4,5. Device does not have to be in a group to be able to pickup calls from that group.");
		$tmparr['pickupgroup'] = array('value' => $amp_conf['DEVICE_PICKUPGROUP'], 'tt' => $tt, 'level' => 1);
		$tt = _("Mailbox for this device. This should not be changed unless you know what you are doing.");
		$tmparr['mailbox'] = array('value' => '', 'tt' => $tt, 'level' => 2);
		$devopts = $tmparr;
		return $devopts;
	}
}

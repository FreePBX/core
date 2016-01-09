<?php
// vim: set ai ts=4 sw=4 ft=php:
namespace FreePBX\modules\Core\Drivers;
class Custom extends \FreePBX\modules\Core\Driver {
	public function getInfo() {
		return array(
			"rawName" => "custom",
			"hardware" => "custom_custom",
			"prettyName" => _("Generic Custom Driver"),
			"shortName" => _("Custom"),
			"description" => _("Custom Device")
		);
	}

	public function addDevice($id, $settings) {
		return true;
	}

	public function delDevice($id) {
		return true;
	}

	public function getDevice($id) {
		return array();
	}

	public function getDefaultDeviceSettings($id, $displayname, &$flag) {
		return array(
			"dial" => "",
			"settings" => array()
		);
	}

	public function getDeviceDisplay($display, $deviceInfo, $currentcomponent, $primarySection) {
		$tmparr = array();
		$tt = _("How to dial this device. This will be device specific. For example, a custom device which is really a remote SIP URI might be configured such as SIP/joe@somedomain.com");
		$tmparr['dial'] = array('prompttext' => _("Dial"), 'value' => '', 'tt' => $tt, 'level' => 0);
		$devopts = $tmparr;
		return $devopts;
	}
}

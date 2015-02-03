<?php
// vim: set ai ts=4 sw=4 ft=php:
namespace FreePBX\modules\Core\Drivers;
class Custom extends \FreePBX\modules\Core\Driver {
	public function getInfo() {
		return array(
			"rawName" => "custom",
			"prettyName" => _("Generic Custom Driver"),
			"description" => _("Other (Custom) Device"),
			"asteriskSupport" => ">=1.0"
		);
	}
	public function getDisplay($display, $deviceInfo, $currentcomponent) {
		$tmparr = array();
		$tt = _("How to dial this device. This will be device specific. For example, a custom device which is really a remote SIP URI might be configured such as SIP/joe@somedomain.com");
		$tmparr['dial'] = array('value' => '', 'tt' => $tt, 'level' => 0);
		unset($tmparr);
		$devopts = $tmparr;
		return $devopts;
	}
}

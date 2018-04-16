<?php
// vim: set ai ts=4 sw=4 ft=php:
namespace FreePBX\modules\Core\Drivers;
class Virtual extends \FreePBX\modules\Core\Driver {
	public function getInfo() {
		return array(
			"rawName" => "virtual",
			"hardware" => "virtual",
			"prettyName" => _("None (virtual exten)"),
			"shortName" => _("Virtual")
		);
	}
	public function getDefaultDeviceSettings($id, $displayname, &$flag) {
                return array(
                        "dial" => "",
                        "settings" => array()
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

}

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
}

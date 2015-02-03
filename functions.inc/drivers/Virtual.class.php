<?php
// vim: set ai ts=4 sw=4 ft=php:
namespace FreePBX\modules\Core\Drivers;
class Virtual extends \FreePBX\modules\Core\Driver {
	public function getInfo() {
		return array(
			"rawName" => "virtual",
			"prettyName" => _("Generic Custom Driver"),
			"asteriskSupport" => ">=1.0"
		);
	}
}

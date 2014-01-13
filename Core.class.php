<?php
// vim: set ai ts=4 sw=4 ft=php:

class Core extends FreePBX_Helpers implements BMO  {

	public function __construct($freepbx = null) {

		parent::__construct($freepbx);
		//Hackery-Jackery for Core only really
		if(!class_exists('PJSip') && file_exists(__DIR__.'/functions.inc/PJSip.class.php')) {
			include(__DIR__.'/functions.inc/PJSip.class.php');
			$this->FreePBX->PJSip = new PJSip($this->FreePBX);
		}
	}

	public function install() {
	}

	public function uninstall() {
	}

	public function backup() {
	}

	public function restore($backup) {
	}

	public function doTests($db) {
		return true;
	}

	public function doConfigPageInit($page) {
		return true;
	}

}

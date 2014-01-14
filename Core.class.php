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
		if ($page == "astmodules") {
			foreach ($_REQUEST as $key => $var) {
				// Do they want to delete something?
				if (preg_match("/^delete-(.+)-(.+)$/", $key, $match)) {
					// Note - we base64 encode, to ensure -, _ and . don't get mangled by the browser.
					// print "You want to delete ".base64_decode($match[2])." from ".$match[1]."<br />\n";
					if ($match[1] == "noload") {
						$this->ModulesConf->removenoload(base64_decode($match[2]));
					} elseif ($match[1] == "preload") {
						$this->ModulesConf->removepreload(base64_decode($match[2]));
					} else {
						print "Unsupported section ".$match[1]."<br />\n";
					}
					// Or, they may want to add something..
				} elseif (preg_match("/^add-(.+)$/", $key, $match)) {
					$section = $match[1];
					if (!isset($_REQUEST["new-$section"])) {
						// Post was blank, or didn't exist at all.
						return;
					} else {
						$add = $_REQUEST["new-$section"];
					}

					// Now, actually add it.
					if ($section == "noload") {
						$this->ModulesConf->noload($add);
					} elseif ($section == "preload") {
						$this->ModulesConf->preload($add);
					} else {
						print "Unsupported section ".$section."<br />\n";
					}
				}
			} // foreach
		} // $page == "astmodules"
	}
}

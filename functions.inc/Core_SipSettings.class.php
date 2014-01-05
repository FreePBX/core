<?php

class Core_SipSettings {

	public function __construct($freepbx) {
		$this->FreePBX = $freepbx;

	}

	public function doPage($pagename, &$text) {
		if ($pagename == "page.sipsettings.php") {
			$this->doSipsettingsPage($text);
		} else {
			throw new Exception("I don't know what to do with $pagename");
		}
	}

	private function doSipsettingsPage(&$text) {
		// Split the entire page at EOL
		$foo = split("\n", $text);

		// Grab the first line, we'll need this later.
		$header = array_shift($foo);

		// Tell people what SIP Driver(s) they're using.
		$driver = $this->FreePBX->Config->get_conf_setting('ASTSIPDRIVER');
		if ($driver == "both") {
			$str = "Asterisk is currently using <strong>chan_sip AND chan_pjsip</strong> for SIP Traffic.<br />You can change this on the Advanced Settings Page<br />\n";
		} else {
			$str = "Asterisk is currently using <strong>$driver</strong> for SIP Traffic.<br />You can change this on the Advanced Settings Page<br />\n";
		}

		// Add it onto the front of the array
		array_unshift($foo, $header, $str);

		// and put it back together. Note, $text is a reference. Don't need to return it.
		$text = implode("\n", $foo);
	}
}



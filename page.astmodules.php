<?php
$mods = FreePBX::create()->ModulesConf();

print "<h2>"._("Asterisk Modules")."<hr></h2>\n";
print "<div>"._("Note that this is for ASTERISK modules, not FreePBX Modules.")."</div>\n";
print "<div>"._("It is unlikely you'll need to change anything here.")."<br />\n";
print _("Please be careful when adding or removing modules, as it is possible to stop Asterisk from starting with an incorrect configuration.")."<br />\n";
print _("Deleting the modules.conf file will reset this to defaults.")."<br />\n</div>";
print "<form method='post'>\n";
print "<table>\n";
$pc = $mods->ProcessedConfig['modules'];

// Autoload is always on, for the moment.
unset($pc['autoload']);

foreach ($pc as $type => $filename) {
	if ($type == "preload") {
		$title = _("Preloaded Modules");
	} elseif ($type == "noload") {
		$title = _("Excluded Modules");
	} elseif ($type == "load") {
		$title= _("Manually Loaded Modules");
	} else {
		$title = _("Unknown Entry")." '$type'";
	}

	print "<th colspan=2>$title</th>\n";

	if (is_array($filename)) {
		foreach($filename as $file) {
			showEntry($file);
		}
	} else {
		showEntry($filename);
	}
	print "<tr><td><input name='new-$type' type='text'></td><td>Add</td></tr>\n";
}

print "</table>\n";
print "</form>\n";

function showEntry($file) {
	print "<tr><td><span style='font-family: monospace'>$file</span></td><td>Delete</td></tr>\n";
}

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

$sections = array ("noload", "preload", "load");

foreach ($sections as $type) {
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
	print "<input type='hidden' name='area' value='$type'>\n";

	if (isset($pc[$type])) {
		$files = $pc[$type];
		if (is_array($files)) {
			foreach($files as $file) {
				showEntry($type, $file);
			}
		} else {
			showEntry($type, $files);
		}
	}
	print "<tr><td><input class='txt' name='new-$type' data-submit='add-$type' type='text' size=35 style='font-family: monospace'></td>\n";
	print "<td><input type='submit' id='add-$type' name='add-$type' value='"._("Add")."' /></td></tr>\n";
}

print "</table>\n";
print "</form>\n";

function showEntry($type, $file) {
	print "<tr><td><span style='font-family: monospace'>$file</span></td>\n";
	print "<td><input type='submit' name='delete-$type-".base64_encode($file)."' value='"._("Delete")."' /></td></tr>\n";
}
?>

<script type="text/javascript">
$(document).ready(function() {
	$(".txt").keyup(function(e) {
		console.log(e);
		if (e.which == 13) {
			var target = $(e.currentTarget).data('submit');
			$("#"+target).click();
		}
	}).keydown(function(e) {
		if (e.which == 13) {
			e.preventDefault();
		}
	});
});
</script>


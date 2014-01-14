<?php

global $currentcomponent;

$cc =& $currentcomponent;

$mods = FreePBX::create()->ModulesConf();

$cc->addguielem("_top", new gui_pageheading(null, _("Asterisk Modules")));
$cc->addguielem("_top", new guitext(null, _("Note that this is for ASTERISK modules, not FreePBX Modules.")));

$pc = $mods->ProcessedConfig['modules'];

unset($pc['autoload']);

foreach ($pc as $type => $filename) {
	if (is_array($filename)) {
		foreach($filename as $file) {
			$cc->addguielem($type, new guitext(null, $file));
		}
	} else {
		$cc->addguielem($type, new guitext(null, $filename));
	}

}


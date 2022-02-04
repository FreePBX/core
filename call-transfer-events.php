#!/usr/bin/php -q
<?php

// bootstrap freepbx
$bootstrap_settings['freepbx_auth'] = false;
$restrict_mods = array(
	'core' => true,
);
$bootstrap_settings['astman_options']['cachemode'] = false;
include '/etc/freepbx.conf';
include __DIR__.'/functions.inc/calltrasnfer-eventlistener.php';

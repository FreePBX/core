#!/usr/bin/php -q
<?php

// bootstrap freepbx
$bootstrap_settings['freepbx_auth'] = false;
$restrict_mods = array(
	'core' => true,
);
$bootstrap_settings['astman_options']['cachemode'] = false;
include '/etc/freepbx.conf';
$freepbx = \FreePBX::Create();
$config = $freepbx->Config();
$monitordir  = $config->get('ASTSPOOLDIR').'/moinitor';
include __DIR__.'/functions.inc/calltrasnfer-eventlistener.php';

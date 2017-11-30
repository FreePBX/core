#!/usr/bin/env php
<?php
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2017 Sangoma Technologies Inc

$channel = $argv[1];
$filename = $argv[2];

// NO channel !! exit !!
if($channel == NULL){
	exit;
}

// NO filename !!! exit !!
if($filename == NULL){
	exit;
}

//Bootstrap FreePBX
$bootstrap_settings['freepbx_auth'] = false;
$restrict_mods = true;
include '/etc/freepbx.conf';

//jic these got overwritten inside of freepbx
$channel = $argv[1];
$filename = $argv[2];

// start recording now
$astman->mixmonitor($channel, "$filename", "ai(LOCAL_MIXMON_ID)");

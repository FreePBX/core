#!/usr/bin/env php
<?php
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright (C) 2012 HEHE Enterprises, LLC d.b.a. i9 Technologies
//	Copyright 2013,2014 Schmooze Com Inc.

//Bootstrap FreePBX
$bootstrap_settings['freepbx_auth'] = false;
include '/etc/freepbx.conf';

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

// start recording now
$astman->mixmonitor($channel, "$filename", "ai(LOCAL_MIXMON_ID)");


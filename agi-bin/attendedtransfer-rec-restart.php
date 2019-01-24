#!/usr/bin/env php
<?php
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2017 Sangoma Technologies Inc

//Bootstrap FreePBX
$bootstrap_settings['freepbx_auth'] = false;
$restrict_mods = true;
include '/etc/freepbx.conf';
require_once "phpagi.php";
$AGI = new AGI();

$channel = $argv[1];
$filename = $argv[2];

// start recording now
$astman->mixmonitor($channel, "$filename", "ai(LOCAL_MIXMON_ID)");

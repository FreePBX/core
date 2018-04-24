<?php
/** Deprecated functions that will go away */
//I dont wanna talk about it.
function core_devices_addpjsip($account) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}

//add to sip table
function core_devices_addsip($account,$tech='SIP') {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}

//add to iax table
function core_devices_addiax2($account) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}

function core_devices_addzap($account) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}

function core_devices_adddahdi($account) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}

function core_devices_deliax2($account) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}

function core_devices_delzap($account) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}

function core_devices_deldahdi($account) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}

function core_devices_delpjsip($account) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}

function core_devices_delsip($account) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}

function core_devices_getiax2($account) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}

function core_devices_getzap($account) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}

function core_devices_getdahdi($account) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}

function core_devices_getpjsip($account) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}

function core_devices_getsip($account) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}
function core_trunks_addDialRules($trunknum, $dialrules) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}

function core_trunk_has_registrations($type = ''){
	return \FreePBX::Core()->trunkHasRegistrations($type);
}

function core_trunks_deleteDialRules($trunknum) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}

function core_trunks_getDialRules($trunknum) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}

function core_trunks_readDialRulesFile() {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}



function core_routing_getroutenames() {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}
function core_routing_setroutepriority($routepriority, $reporoutedirection, $reporoutekey) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}
function core_routing_setroutepriorityvalue($key)
{
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}
function core_routing_add($name, $patterns, $trunks, $method, $pass, $emergency = "", $intracompany = "", $mohsilence = "", $routecid = "", $routecid_mode = "") {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}
function core_routing_edit($name, $patterns, $trunks, $pass, $emergency="", $intracompany = "", $mohsilence="", $routecid = "", $routecid_mode) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}
function core_routing_del($name) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}
function core_routing_trunk_del($trunknum) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}
function core_routing_rename($oldname, $newname) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}
function core_routing_getroutepatterns($route) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}
function core_routing_getroutetrunks($route) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}
function core_routing_getroutepassword($route) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}
function core_routing_getrouteemergency($route) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}
function core_routing_getrouteintracompany($route) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}
function core_routing_getroutemohsilence($route) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}
function core_routing_getroutecid($route) {
	$trace = debug_backtrace();
	$function = $trace[0]['function'];
	die_freepbx("function: $function has been deprecated and removed");
}

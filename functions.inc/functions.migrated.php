<?php
/** Functions maintained for backwards compat but moved to BMO */
function core_devices_del($account,$editmode=false){
	_core_backtrace();
	return FreePBX::Core()->delDevice($account,$editmode);
}

function core_devices_get($account){
	_core_backtrace();
	return FreePBX::Core()->getDevice($account);
}

function core_trunks_getTrunkTrunkName($trunknum) {
	return \FreePBX::Core()->getTrunkTrunkNameByID($trunknum);
}

function core_trunks_getTrunkPeerDetails($trunknum) {
	return \FreePBX::Core()->getTrunkPeerDetailsByID($trunknum);
}

function core_trunks_getTrunkUserContext($trunknum) {
	return \FreePBX::Core()->getTrunkUserContext($trunknum);
}

function core_trunks_getTrunkUserConfig($trunknum) {
	return \FreePBX::Core()->getTrunkUserConfigByID($trunknum);
}

//get trunk account register string
function core_trunks_getTrunkRegister($trunknum) {
	return \FreePBX::Core()->getTrunkRegisterStringByID($trunknum);
}

function core_trunks_get_dialrules($trunknum = false) {
	if ($trunknum === false) {
		return \FreePBX::Core()->getAllTrunkDialRules();
	} else {
		return \FreePBX::Core()->getTrunkDialRulesByID($trunknum);
	}
}

//get outbound routes for a given trunk
function core_trunks_gettrunkroutes($trunknum) {
	return \FreePBX::Core()->getTrunkRoutesByID($trunknum);
}

function core_trunks_delete_dialrules($trunknum) {
	return \FreePBX::Core()->deleteTrunkDialRulesByID($trunknum);
}
function core_routing_get($route_id) {
	return \FreePBX::Core()->getRouteByID($route_id);
}

// function core_routing_getroutenames()
function core_routing_list() {
	_core_backtrace();
	return \FreePBX::Core()->getAllRoutes();
}

function core_ampusers_add($username, $password, $extension_low, $extension_high, $deptname, $sections) {
	_core_backtrace();
	return \FreePBX::Core()->addAMPUser($username, $password, $extension_low, $extension_high, $deptname, $sections);
}


function core_did_list($order='extension'){
	_core_backtrace();
	return FreePBX::Core()->getAllDIDs($order);
}

function core_did_get($extension="",$cidnum=""){
	_core_backtrace();
	return FreePBX::Core()->getDID($extension,$cidnum);
}

function core_did_del($extension,$cidnum){
	_core_backtrace();
	return FreePBX::Core()->delDID($extension,$cidnum);
}

/* Create a new did with values passed into $did_vars and defaults used otherwise
*/
function core_did_create_update($did_vars) {
	return FreePBX::Core()->createUpdateDID($did_vars);
}


/* Edits the poperties of a did, but not the did or cid nums since those could of course be in conflict
*/
function core_did_edit_properties($did_vars) {
	return FreePBX::Core()->editDIDProperties($did_vars);
}

// get the existing extensions
// the returned arrays contain [0]:extension [1]:name
function core_users_list($get_all=false){
	_core_backtrace();
	return FreePBX::Core()->listUsers($get_all);
}

function core_users_get($extension){
	_core_backtrace();
	return FreePBX::Core()->getUser($extension);
}

function core_users_del($extension, $editmode=false){
	_core_backtrace();
	return FreePBX::Core()->delUser($extension,$editmode);
}

function core_users_directdid_get($directdid=""){
	return array();
}

function core_trunks_del($trunknum, $tech = null , $edit = false) {
	_core_backtrace();
	return \FreePBX::Core()->deleteTrunk($trunknum, $tech, $edit);
}


// function core_routing_trunk_del($trunknum)
function core_routing_trunk_delbyid($trunk_id) {
	_core_backtrace();
	return \FreePBX::Core()->delRouteTrunkByID($trunk_id);
}

// function core_routing_getroutepatterns($route)
function core_routing_getroutepatternsbyid($route_id) {
	return \FreePBX::Core()->getRoutePatternsByID($route_id);
}

// function core_routing_getroutetrunks($route)
function core_routing_getroutetrunksbyid($route_id) {
	return FreePBX::Core()->getRouteTrunksByID($route_id);
}
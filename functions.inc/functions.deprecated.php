<?php
/** Deprecated functions that will go away */
//I dont wanna talk about it.
function core_devices_addpjsip() {
	FreePBX::Modules()->deprecatedFunction();
}

//add to sip table
function core_devices_addsip() {
	FreePBX::Modules()->deprecatedFunction();
}

//add to iax table
function core_devices_addiax2() {
	FreePBX::Modules()->deprecatedFunction();
}

function core_devices_addzap() {
	FreePBX::Modules()->deprecatedFunction();
}

function core_devices_adddahdi() {
	FreePBX::Modules()->deprecatedFunction();
}

function core_devices_deliax2() {
	FreePBX::Modules()->deprecatedFunction();
}

function core_devices_delzap() {
	FreePBX::Modules()->deprecatedFunction();
}

function core_devices_deldahdi() {
	FreePBX::Modules()->deprecatedFunction();
}

function core_devices_delpjsip() {
	FreePBX::Modules()->deprecatedFunction();
}

function core_devices_delsip() {
	FreePBX::Modules()->deprecatedFunction();
}

function core_devices_getiax2() {
	FreePBX::Modules()->deprecatedFunction();
}

function core_devices_getzap() {
	FreePBX::Modules()->deprecatedFunction();
}

function core_devices_getdahdi() {
	FreePBX::Modules()->deprecatedFunction();
}

function core_devices_getpjsip() {
	FreePBX::Modules()->deprecatedFunction();
}

function core_devices_getsip() {
	FreePBX::Modules()->deprecatedFunction();
}
function core_trunks_addDialRules() {
	FreePBX::Modules()->deprecatedFunction();
}

function core_trunk_has_registrations($type = ''){
	FreePBX::Modules()->deprecatedFunction();
	return \FreePBX::Core()->trunkHasRegistrations($type);
}

function core_trunks_deleteDialRules() {
	FreePBX::Modules()->deprecatedFunction();
}

function core_trunks_getDialRules() {
	FreePBX::Modules()->deprecatedFunction();
}

function core_trunks_readDialRulesFile() {
	FreePBX::Modules()->deprecatedFunction();
}

function core_dahdichandids_add($description, $channel, $did){
	FreePBX::Modules()->deprecatedFunction();
	$dahdichannels = new FreePBX\modules\Core\Components\Dahdichannels();
	return $dahdichannels->add($description, $channel, $did);
}

function core_dahdichandids_edit($description, $channel, $did){
	FreePBX::Modules()->deprecatedFunction();
	$dahdichannels = new FreePBX\modules\Core\Components\Dahdichannels();
	return $dahdichannels->add($description, $channel, $did);
}

function core_dahdichandids_delete($channel){
	FreePBX::Modules()->deprecatedFunction();
	$dahdichannels = new FreePBX\modules\Core\Components\Dahdichannels();
	return $dahdichannels->delete($channel);
}

function core_dahdichandids_list(){
	FreePBX::Modules()->deprecatedFunction();
	$dahdichannels = new FreePBX\modules\Core\Components\Dahdichannels();
	return $dahdichannels->listChannels();
}

function core_dahdichandids_get($channel){
	FreePBX::Modules()->deprecatedFunction();
	$dahdichannels = new FreePBX\modules\Core\Components\Dahdichannels();
	return $dahdichannels->get($channel);
}

function core_devices_del($account, $editmode = false){
	FreePBX::Modules()->deprecatedFunction();
	return FreePBX::Core()->delDevice($account, $editmode);
}

function core_devices_get($account){
	FreePBX::Modules()->deprecatedFunction();
	return FreePBX::Core()->getDevice($account);
}

function core_trunks_getTrunkTrunkName($trunknum){
	FreePBX::Modules()->deprecatedFunction();
	return \FreePBX::Core()->getTrunkTrunkNameByID($trunknum);
}

function core_trunks_getTrunkPeerDetails($trunknum){
	FreePBX::Modules()->deprecatedFunction();
	return \FreePBX::Core()->getTrunkPeerDetailsByID($trunknum);
}

function core_trunks_getTrunkUserContext($trunknum){
	FreePBX::Modules()->deprecatedFunction();
	return \FreePBX::Core()->getTrunkUserContext($trunknum);
}

function core_trunks_getTrunkUserConfig($trunknum){
	FreePBX::Modules()->deprecatedFunction();
	return \FreePBX::Core()->getTrunkUserConfigByID($trunknum);
}

//get trunk account register string
function core_trunks_getTrunkRegister($trunknum){
	FreePBX::Modules()->deprecatedFunction();
	return \FreePBX::Core()->getTrunkRegisterStringByID($trunknum);
}

function core_trunks_get_dialrules($trunknum = false){
	FreePBX::Modules()->deprecatedFunction();

	if (false === $trunknum) {
		return \FreePBX::Core()->getAllTrunkDialRules();
	}
	return \FreePBX::Core()->getTrunkDialRulesByID($trunknum);
}

//get outbound routes for a given trunk
function core_trunks_gettrunkroutes($trunknum){
	FreePBX::Modules()->deprecatedFunction();
	return \FreePBX::Core()->getTrunkRoutesByID($trunknum);
}

function core_trunks_delete_dialrules($trunknum){
	FreePBX::Modules()->deprecatedFunction();
	return \FreePBX::Core()->deleteTrunkDialRulesByID($trunknum);
}

function core_ampusers_add($username, $password, $extension_low, $extension_high, $deptname, $sections, $userExtension, $userEmail){
	FreePBX::Modules()->deprecatedFunction();
	return \FreePBX::Core()->addAMPUser($username, $password, $extension_low, $extension_high, $deptname, $sections, false,$userExtension, $userEmail);
}


function core_did_list($order = 'extension'){
	FreePBX::Modules()->deprecatedFunction();
	return FreePBX::Core()->getAllDIDs($order);
}

function core_did_get($extension = "", $cidnum = ""){
	FreePBX::Modules()->deprecatedFunction();
	return FreePBX::Core()->getDID($extension, $cidnum);
}

function core_did_del($extension, $cidnum){
	FreePBX::Modules()->deprecatedFunction();
	return FreePBX::Core()->delDID($extension, $cidnum);
}

function core_routing_setrouteorder($route_id, $seq){
	FreePBX::Modules()->deprecatedFunction();
	$routing = new FreePBX\modules\Core\Components\Outboundrouting();
	return $routing->setOrder($route_id, $seq);
}


/* Create a new did with values passed into $did_vars and defaults used otherwise
 */
function core_did_create_update($did_vars){
	FreePBX::Modules()->deprecatedFunction();
	return FreePBX::Core()->createUpdateDID($did_vars);
}


/* Edits the poperties of a did, but not the did or cid nums since those could of course be in conflict
 */
function core_did_edit_properties($did_vars){
	FreePBX::Modules()->deprecatedFunction();
	return FreePBX::Core()->editDIDProperties($did_vars);
}

// get the existing extensions
// the returned arrays contain [0]:extension [1]:name
function core_users_list($get_all = false){
	FreePBX::Modules()->deprecatedFunction();
	return FreePBX::Core()->listUsers($get_all);
}

function core_users_get($extension){
	FreePBX::Modules()->deprecatedFunction();
	return FreePBX::Core()->getUser($extension);
}

function core_users_del($extension, $editmode = false){
	FreePBX::Modules()->deprecatedFunction();
	return FreePBX::Core()->delUser($extension, $editmode);
}

function core_trunks_del($trunknum, $tech = null, $edit = false){
	FreePBX::Modules()->deprecatedFunction();
	return \FreePBX::Core()->deleteTrunk($trunknum, $tech, $edit);
}

function core_routing_trunk_delbyid($trunk_id){
	FreePBX::Modules()->deprecatedFunction();

	$routing = new FreePBX\modules\Core\Components\Outboundrouting();
	return $routing->deleteOutboundRouteTrunksByTrunkId($trunk_id);
}

function core_routing_getroutepatternsbyid($route_id){
	FreePBX::Modules()->deprecatedFunction();
	$routing = new FreePBX\modules\Core\Components\Outboundrouting();
	return $routing->getRoutePatternsById($route_id);
}

function core_routing_getroutetrunksbyid($route_id){
	FreePBX::Modules()->deprecatedFunction();
	$routing = new FreePBX\modules\Core\Components\Outboundrouting();
	return $routing->getRouteTrunksByID($route_id);
}

function core_routing_getrouteemailbyid($route_id){
	FreePBX::Modules()->deprecatedFunction();
	$routing = new FreePBX\modules\Core\Components\Outboundrouting();
	return $routing->getRouteEmailByID($route_id);
}

function core_routing_updatepatterns($route_id, &$patterns, $delete = false){
	FreePBX::Modules()->deprecatedFunction();
	$routing = new FreePBX\modules\Core\Components\Outboundrouting();
	return $routing->updatePatterns($route_id, $patterns, $delete);
}

function core_routing_updatetrunks($route_id, &$trunks, $delete = false){
	FreePBX::Modules()->deprecatedFunction();
	$routing = new FreePBX\modules\Core\Components\Outboundrouting();
	return $routing->updateTrunks($route_id, $trunks, $delete);
}

function core_routing_get($route_id){
	FreePBX::Modules()->deprecatedFunction();
	return FreePBX::Core()->getRouteByID($route_id);
}

// function core_routing_getroutenames()
function core_routing_list(){
	FreePBX::Modules()->deprecatedFunction();
	return FreePBX::Core()->getAllRoutes();
}

function core_routing_editbyid($route_id, $name, $outcid, $outcid_mode, $password, $emergency_route, $intracompany_route, $mohclass, $time_group_id, $patterns, $trunks, $seq = '', $dest = '', $time_mode = '', $timezone = '', $calendar_id = '', $calendar_group_id = '', $notification_on = '', $emailfrom = '', $emailto = '', $emailsubject = '', $emailbody = '') {
	FreePBX::Modules()->deprecatedFunction();
	$routing = new FreePBX\modules\Core\Components\Outboundrouting();
	return $routing->editById($route_id, $name, $outcid, $outcid_mode, $password, $emergency_route, $intracompany_route, $mohclass, $time_group_id, $patterns, $trunks, $seq, $dest, $time_mode, $timezone, $calendar_id, $calendar_group_id, $notification_on, $emailfrom, $emailto, $emailsubject, $emailbody);
}

function core_routing_addbyid($name, $outcid, $outcid_mode, $password, $emergency_route, $intracompany_route, $mohclass, $time_group_id, $patterns, $trunks, $seq = 'new', $dest = '', $time_mode = '', $timezone = '', $calendar_id = '', $calendar_group_id = '', $notification_on = '', $emailfrom = '', $emailto = '', $emailsubject = '', $emailbody = '') {
	FreePBX::Modules()->deprecatedFunction();
	$routing = new FreePBX\modules\Core\Components\Outboundrouting();
	return $routing->add($name, $outcid, $outcid_mode, $password, $emergency_route, $intracompany_route, $mohclass, $time_group_id, $patterns, $trunks, $seq, $dest, $time_mode, $timezone, $calendar_id, $calendar_group_id, $notification_on, $emailfrom, $emailto, $emailsubject, $emailbody);
}

function core_routing_delbyid($route_id) {
	FreePBX::Modules()->deprecatedFunction();
	$routing = new FreePBX\modules\Core\Components\Outboundrouting();
	return $routing->deleteById($route_id);
}

function core_routing_getroutenames() {
	FreePBX::Modules()->deprecatedFunction();
}
function core_routing_setroutepriority() {
	FreePBX::Modules()->deprecatedFunction();
}
function core_routing_setroutepriorityvalue(){
	FreePBX::Modules()->deprecatedFunction();
}
function core_routing_add() {
	FreePBX::Modules()->deprecatedFunction();
}
function core_routing_edit() {
	FreePBX::Modules()->deprecatedFunction();
}
function core_routing_del() {
	FreePBX::Modules()->deprecatedFunction();
}
function core_routing_trunk_del() {
	FreePBX::Modules()->deprecatedFunction();
}
function core_routing_rename() {
	FreePBX::Modules()->deprecatedFunction();
}
function core_routing_getroutepatterns() {
	FreePBX::Modules()->deprecatedFunction();
}
function core_routing_getroutetrunks() {
	FreePBX::Modules()->deprecatedFunction();
}
function core_routing_getroutepassword() {
	FreePBX::Modules()->deprecatedFunction();
}
function core_routing_getrouteemergency() {
	FreePBX::Modules()->deprecatedFunction();
}
function core_routing_getrouteintracompany() {
	FreePBX::Modules()->deprecatedFunction();
}
function core_routing_getroutemohsilence() {
	FreePBX::Modules()->deprecatedFunction();
}
function core_routing_getroutecid() {
	FreePBX::Modules()->deprecatedFunction();
}
function core_users_directdid_get(){
	FreePBX::Modules()->deprecatedFunction();
}

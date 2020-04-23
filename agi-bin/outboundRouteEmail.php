#!/usr/bin/php -q
<?php
/*
;------------------------------------------------------------------------
; called by [macro-send-obroute-email]
;------------------------------------------------------------------------
; This script recieves some call variables from the dialplan, and
; sends and email based on the route's db settings, if a valid 'Email To' is set
; on the Outbound Route.
;
; ${ARG1} - the number sent to the trunk, after prepend/stripping
; ${ARG2} - the raw number dialed, before any prepend/stripping
; ${ARG3} - the trunk id number
; ${ARG4} - the epoch time of the call
; ${ARG5} - the outgoing callerId name
; ${ARG6} - the outgoing callerId number
; ${ARG7} - the Outbound Route ID
; ${ARG8} - the Outbound Route Name
; ${ARG9} - the calling party's Name
; ${ARG10}- the calling party's Number
; ${ARG11}- the call's LINKEDID
;------------------------------------------------------------------------
*/
if (!@include_once(getenv('FREEPBX_CONF') ? getenv('FREEPBX_CONF') : '/etc/freepbx.conf')) {
	include_once('/etc/asterisk/freepbx.conf');
}

global $db;

$dialedNumber = !empty($argv[1]) ? $argv[1]:'';
$dialedNumberRaw = !empty($argv[2]) ? $argv[2]:'';
$trunkId = !empty($argv[3]) ? $argv[3]:'';
$nowEpoch = !empty($argv[4]) ? $argv[4]:'';
$outgoingCallerIdName = !empty($argv[5]) ? $argv[5]:'';
$outgoingCallerIdNumber = !empty($argv[6]) ? $argv[6]:'';
$routeId = !empty($argv[7]) ? $argv[7]:'';
$routeName = !empty($argv[8]) ? $argv[8]:'';
$callerName = !empty($argv[9]) ? $argv[9]:'';
$callerNumber = !empty($argv[10]) ? $argv[10]:'';
$cuid = !empty($argv[11]) ? $argv[11]:'';

//If we don't get a routeId, something's wrong.
if (empty($routeId)) {
	exit();
}

//Get the email values from the outbound route
$sql = "SELECT * FROM outbound_route_email WHERE route_id = $routeId";
$results = $db->getAll($sql, DB_FETCHMODE_ASSOC);
if (DB::IsError($results) || !is_array($results)) {
	$results = array();
}
$emailFrom = $results[0]['emailfrom'];
$emailTo = $results[0]['emailto'];
$emailSubject = $results[0]['emailsubject'];
$emailBody = $results[0]['emailbody'];

//Exit if outbound route doesn't have an emailto set
//we shouldn't end up here but just in case
if (empty($emailTo)) {
	exit();
}

//Get the trunk name
$sql = "SELECT name FROM trunks WHERE trunkid = $trunkId";
$results = $db->getAll($sql, DB_FETCHMODE_ASSOC);
if (DB::IsError($results) || !is_array($results)) {
	$results = array();
}
$trunkName = !empty($results[0]['name']) ? $results[0]['name']:'';

//Fill in the email vars
$emailSubject = parse_email_vars($emailSubject);
$emailBody = parse_email_vars($emailBody);

//Send it
$email = new \CI_Email();
$email->from($emailFrom);
$email->to($emailTo);
$email->subject($emailSubject);
$email->message($emailBody);
$email->send();
dbug("Outbound Route email notification has been sent to: $emailTo");

//Job's Done
exit();

//Replace the {{VAR}}'s in the email subject and body templates with current values
function parse_email_vars($emailText) {
	global $cuid, $dialedNumber, $dialedNumberRaw, $routeName, $callerName, $callerNumber,
		$trunkName, $outgoingCallerIdNumber, $outgoingCallerIdName, $nowEpoch;

	$callerAll = '"' . $callerName . '"' . ' <' . $callerNumber . '>';
	$outgoingCallerIdAll = '"' . $outgoingCallerIdName . '"' . ' <' . $outgoingCallerIdNumber . '>';

	//set the dateTime vars
	$iniTz = ini_get('date.timezone');
	if (empty($iniTz)) {
		//the tz set in php.ini comes from sysadmin. if they never set that, use UTC for these emails
		date_default_timezone_set('UTC');
	}
	$dt = new DateTime("@$nowEpoch");
	$dt->setTimezone(new DateTimeZone(date_default_timezone_get()));
	$month = $dt->format('m');
	$day = $dt->format('d');
	$year = $dt->format('Y');
	$timeampm = $dt->format('g:i:s A');
	$time = $dt->format('G:i:s');
	$timezoneFull = $dt->format('e'); // America/New_York
	$timezoneShort = $dt->format('T'); // UTC, PST, +12

	//Replace the vars in email subject and body
	$emailText = str_replace('{{CALLUID}}', $cuid, $emailText);
	$emailText = str_replace('{{ROUTENAME}}', $routeName, $emailText);
	$emailText = str_replace('{{DIALEDNUMBER}}', $dialedNumber, $emailText);
	$emailText = str_replace('{{DIALEDNUMBERRAW}}', $dialedNumberRaw, $emailText);
	$emailText = str_replace('{{CALLERNAME}}', $callerName, $emailText);
	$emailText = str_replace('{{CALLERNUMBER}}', $callerNumber, $emailText);
	$emailText = str_replace('{{CALLERALL}}', $callerAll, $emailText);
	$emailText = str_replace('{{OUTGOINGCALLERIDNAME}}', $outgoingCallerIdName, $emailText);
	$emailText = str_replace('{{OUTGOINGCALLERIDNUMBER}}', $outgoingCallerIdNumber, $emailText);
	$emailText = str_replace('{{OUTGOINGCALLERIDALL}}', $outgoingCallerIdAll, $emailText);
	$emailText = str_replace('{{TRUNKNAME}}', $trunkName, $emailText);
	$emailText = str_replace('{{MONTH}}', $month, $emailText);
	$emailText = str_replace('{{DAY}}', $day, $emailText);
	$emailText = str_replace('{{YEAR}}', $year, $emailText);
	$emailText = str_replace('{{TIME}}', $time, $emailText);
	$emailText = str_replace('{{TIMEAMPM}}', $timeampm, $emailText);
	$emailText = str_replace('{{TZFULL}}', $timezoneFull, $emailText);
	$emailText = str_replace('{{TZSHORT}}', $timezoneShort, $emailText);

	return $emailText;
}
?>

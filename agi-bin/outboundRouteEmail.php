#!/usr/bin/php -q
<?php 
/*
;------------------------------------------------------------------------
; [macro-send-obroute-email]
;------------------------------------------------------------------------
; Send the info to a script that sends an email with the
; call info, if the route has this feature enabled
;
; ${ARG1} - the number sent to the trunk, after prepend/stripping
; ${ARG2} - the raw number dialed, before any prepend/stripping
; ${ARG3} - the Outbound Route ID
; ${ARG4} - the Outbound Route Name
; ${ARG5} - the calling party's Name
; ${ARG6} - the calling party's Number
; ${ARG7} - the trunk id number
; ${ARG8} - the epoch time of the call
; ${ARG9} - the outgoing callerId name
; ${ARG10}- the outgoing callerId number
; ${ARG11}- the call's LINKEDID 
;------------------------------------------------------------------------
*/
if (!@include_once(getenv('FREEPBX_CONF') ? getenv('FREEPBX_CONF') : '/etc/freepbx.conf')) {
    include_once('/etc/asterisk/freepbx.conf');
}

global $db;
$freepbx = \FreePBX::Create();
$agidir = $freepbx->Config->get('ASTAGIDIR');
require_once $agidir."/phpagi.php";

$agi = new AGI();

$dialedNumber = !empty($argv[1]) ? $argv[1]:'';
$dialedNumberRaw = !empty($argv[2]) ? $argv[2]:'';
$routeId = !empty($argv[3]) ? $argv[3]:'';
$routeName = !empty($argv[4]) ? $argv[4]:'';
$callerName = !empty($argv[5]) ? $argv[5]:'';
$callerNumber = !empty($argv[6]) ? $argv[6]:'';
$trunkId = !empty($argv[7]) ? $argv[7]:'';
$nowEpoch = !empty($argv[8]) ? $argv[8]:'';
$outgoingCallerIdName = !empty($argv[9]) ? $argv[9]:'';
$outgoingCallerIdNumber = !empty($argv[10]) ? $argv[10]:'';
$cuid = !empty($argv[11]) ? $argv[11]:'';

//If we don't get a routeId, something's wrong.
if (empty($routeId)) { exit(); }

//Get the email values from the outbound route
$sql = "SELECT * FROM outbound_route_email WHERE route_id = $routeId";
$results = $db->getAll($sql, DB_FETCHMODE_ASSOC);
if(DB::IsError($results) || !is_array($results)) {
	$results = array();
}
$emailFrom = $results[0]['emailfrom'];
$emailTo = $results[0]['emailto'];
$emailSubject = $results[0]['emailsubject'];
$emailBody = $results[0]['emailbody'];

//Exit if outbound route doesn't have an emailto set
if (empty($emailTo)) { exit(); }

//Get the trunk name
$sql = "SELECT name FROM trunks WHERE trunkid = $trunkId";
$results = $db->getAll($sql, DB_FETCHMODE_ASSOC);
if(DB::IsError($results) || !is_array($results)) {
	$results = array();
}
$trunkName = !empty($results[0]['name']) ? $results[0]['name']:'';

//Fill in the email vars
$emailSubject = parse_email_vars($emailSubject);
$emailBody = parse_email_vars($emailBody);
dbug($emailSubject);
dbug($emailBody);
/*****
$agi->verbose("ARG1: $argv[1]", 0);
$agi->verbose("CUID: $cuid", 0);
$agi->verbose("ROUTENAME: $routeName", 0);
$agi->verbose("DIALEDNUMBER: $dialedNumber", 0);
$agi->verbose("CALLERNAME: $callerName", 0);
$agi->verbose("CALLERNUMBER: $callerNumber", 0);
$agi->verbose("OUTGOINGCALLERIDNAME: $outgoingCallerIdName", 0);
$agi->verbose("OUTGOINGCALLERIDNUMBER: $outgoingCallerIdNumber", 0);
$agi->verbose("CALLERID(all): $outgoingCallerIdAll", 0);
$agi->verbose("NowEpoch: $nowEpoch", 0);
****/
exit();

dbug($emailTo . '    ' . $emailFrom);
	$email = new \CI_Email();
	$email->from($emailFrom);
	$email->to($emailTo);
	$email->subject($emailSubject);
	$email->message($emailBody);
	$email->send();
dbug('sending email........');

exit();

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
	$timeampm = $dt->format('g:i:sA');
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

function agi_get_var($value) {
    global $agi;
    $r = $agi->get_variable($value);

    if ($r['result'] == 1) {
        $result = $r['data'];
        return $result;
    }
    return '';
}


?>

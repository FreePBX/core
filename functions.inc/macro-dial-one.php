<?php

/*
 * macro-dial-one
 */

$mcontext = 'macro-dial-one';
$exten = 's';

$ext->add($mcontext,$exten,'', new ext_set('DEXTEN', '${ARG3}'));
$ext->add($mcontext,$exten,'', new ext_set('DIALSTATUS_CW', ''));
$ext->add($mcontext,$exten,'', new ext_gosubif('$["${FROM_DID}"!="" & "${SCREEN}"="" & "${DB(AMPUSER/${DEXTEN}/screen)}"!=""]','screen,1'));
$ext->add($mcontext,$exten,'', new ext_gosubif('$["${DB(CF/${DEXTEN})}"!=""]','cf,1'));
$ext->add($mcontext,$exten,'', new ext_gotoif('$["${DEXTEN:-1}"="#" | "${DB(DND/${DEXTEN})}"=""]','skip1'));
$ext->add($mcontext,$exten,'', new ext_set('DEXTEN', ''));
$ext->add($mcontext,$exten,'', new ext_set('DIALSTATUS', 'BUSY'));
$ext->add($mcontext,$exten,'skip1', new ext_gotoif('$["${DEXTEN}"=""]','nodial'));
$ext->add($mcontext,$exten,'', new ext_gotoif('$["${DEXTEN:-1}"="#"]','continue'));
$ext->add($mcontext,$exten,'', new ext_set('EXTHASCW', '${IF($["${CWIGNORE}"!=""]? :${DB(CW/${DEXTEN})})}'));
$ext->add($mcontext,$exten,'', new ext_gotoif('$["${EXTHASCW}"="" | "${DB(CFB/${DEXTEN})}"!="" | "${DB(CFU/${DEXTEN})}"!=""]','next1','cwinusebusy'));

$ext->add($mcontext,$exten,'next1', new ext_gotoif('$["${DB(CFU/${DEXTEN})}"!="" & ("${EXTENSION_STATE(${DEXTEN})}"="UNAVAILABLE" | "${EXTENSION_STATE(${DEXTEN})}"="UNKNOWN")]','docfu','skip3'));
$ext->add($mcontext,$exten,'docfu', new ext_set('DEXTEN', ''));
$ext->add($mcontext,$exten,'', new ext_set('DIALSTATUS', 'NOANSWER'));
$ext->add($mcontext,$exten,'',new ext_goto('nodial'));
$ext->add($mcontext,$exten,'skip3', new ext_gotoif('$["${EXTHASCW}"="" | "${DB(CFB/${DEXTEN})}"!=""]','next2','continue'));
$ext->add($mcontext,$exten,'next2', new ext_gotoif('$["${EXTENSION_STATE(${DEXTEN})}"="NOT_INUSE" | "${EXTENSION_STATE(${DEXTEN})}"="UNAVAILABLE" | "${EXTENSION_STATE(${DEXTEN})}"="UNKNOWN"]','continue'));
$ext->add($mcontext,$exten,'', new ext_execif('$["${DB(CFB/${DEXTEN})}"!="" & "${CFIGNORE}"=""]', 'Set', 'DIALSTATUS=BUSY'));
$ext->add($mcontext,$exten,'', new ext_gotoif('$["${EXTHASCW}"!="" | "${DEXTEN:-1}"="#"]','cwinusebusy'));
$ext->add($mcontext,$exten,'', new ext_set('DEXTEN', ''));
$ext->add($mcontext,$exten,'', new ext_set('DIALSTATUS', 'BUSY'));
$ext->add($mcontext,$exten,'', new ext_goto('nodial'));
//TODO: we know about CWINUSEBUSY at generation time, so change this and above corresponding targets to streamline at generation
$ext->add($mcontext,$exten,'cwinusebusy', new ext_gotoif('$["${EXTHASCW}"!="" & "${CWINUSEBUSY}"="true"]','next3','continue'));
$ext->add($mcontext,$exten,'next3', new ext_execif('$["${EXTENSION_STATE(${DEXTEN})}"!="UNAVAILABLE" & "${EXTENSION_STATE(${DEXTEN})}"!="NOT_INUSE" & "${EXTENSION_STATE(${DEXTEN})}"!="UNKNOWN"]', 'Set', 'DIALSTATUS_CW=BUSY'));
$ext->add($mcontext,$exten,'continue', new ext_gotoif('$["${DEXTEN}"=""]','nodial'));
$ext->add($mcontext,$exten,'', new ext_gosubif('$["${DEXTEN:-1}"!="#"]','dstring,1','dlocal,1'));
$ext->add($mcontext,$exten,'', new ext_gotoif('$[${LEN(${DSTRING})}=0]','nodial'));
$ext->add($mcontext,$exten,'', new ext_gotoif('$["${DEXTEN:-1}"="#"]','skiptrace'));
$ext->add($mcontext,$exten,'', new ext_gosubif('$[${REGEX("^[\+]?[0-9]+$" ${CALLERID(number)})} = 1]','ctset,1','ctclear,1'));
//TODO: do we need to check for anything beyond auto-blkvm in this call path?
$ext->add($mcontext,$exten,'skiptrace', new ext_set('D_OPTIONS', '${IF($["${NODEST}"!="" & ${REGEX("(M[(]auto-blkvm[)])" ${ARG2})} != 1]?${ARG2}M(auto-blkvm):${ARG2})}'));

//Advanced settings alert info internal calls
$ext->add($mcontext,$exten,'', new ext_noop('Blind Transfer: ${BLINDTRANSFER}, Attended Transfer: ${ATTENDEDTRANSFER}, User: ${AMPUSER}, Alert Info: ${ALERT_INFO}'));
$ai = FreePBX::Config()->get('INTERNALALERTINFO');
$ai = trim($ai);
$ai = ($ai != "none" && $ai != "inherit") ? $ai : '';
$ext->add($mcontext,$exten,'', new ext_execif('$["${ALERT_INFO}"="" & ${LEN(${AMPUSER})}!=0 & ${LEN(${BLINDTRANSFER})}=0 & ${LEN(${ATTENDEDTRANSFER})}=0]', 'Set', 'ALERT_INFO='.$ai));

//Advanced settings alert info Blind Transfer
$bt = FreePBX::Config()->get('BLINDTRANSALERTINFO');
$bt = trim($bt);
$bt = ($bt != "none" && $bt != "inherit") ? $bt : '';
$ext->add($mcontext,$exten,'', new ext_execif('$[${LEN(${BLINDTRANSFER})}!=0]', 'Set', 'ALERT_INFO='.$bt));

//Advanced settings alert info Attended Transfer
$at = FreePBX::Config()->get('ATTTRANSALERTINFO');
$at = trim($at);
$at = ($at != "none" && $at != "inherit") ? $at : '';
$ext->add($mcontext,$exten,'', new ext_execif('$[${LEN(${ATTENDEDTRANSFER})}!=0]', 'Set', 'ALERT_INFO='.$at));

//Now set Alert Info
$ext->add($mcontext,$exten,'', new ext_gosubif('$["${ALERT_INFO}"!="" & "${ALERT_INFO}"!=" "]', 'func-set-sipheader,s,1', false, 'Alert-Info,${ALERT_INFO}'));
// This is now broken. SIPADDHEADER needs to be a hash. TODO figure out how to fix this
// $ext->add($mcontext,$exten,'', new ext_execif('$["${SIPADDHEADER}"!=""]', 'SIPAddHeader', '${SIPADDHEADER}'));

$ext->add($mcontext,$exten,'', new ext_execif('$[("${MOHCLASS}"!="default") & ("${MOHCLASS}"!="")]', 'Set', 'CHANNEL(musicclass)=${MOHCLASS}'));
$ext->add($mcontext,$exten,'', new ext_gosubif('$["${QUEUEWAIT}"!=""]','qwait,1'));
$ext->add($mcontext,$exten,'', new ext_set('__CWIGNORE', '${CWIGNORE}'));
$ext->add($mcontext,$exten,'', new ext_set('__KEEPCID', 'TRUE'));

// Use goto if no timelimit set from CF
$ext->add($mcontext,$exten,'', new ext_gotoif('$["${USEGOTO}"="1"]','usegoto,1'));

// Once setting CONNECTEDLINE(), add the I option to Dial() so the device doesn't further update the value with the
// "device" <devicenum> data from device CID information, don't send an update if the calling party is not an extension it breaks some providers
//
if ($amp_conf['AST_FUNC_CONNECTEDLINE']) {
	$ext->add($mcontext,$exten,'', new ext_gotoif('$["${DB(AMPUSER/${EXTTOCALL}/cidname)}" = "" || "${DB(AMPUSER/${AMPUSER}/cidname)}" = ""]','godial'));
	$cidnameval = '${DB(AMPUSER/${EXTTOCALL}/cidname)}';
	if ($amp_conf['AST_FUNC_PRESENCE_STATE'] && $amp_conf['CONNECTEDLINE_PRESENCESTATE']) {
		$ext->add($mcontext,$exten,'', new ext_gosub('1','s','sub-presencestate-display','${EXTTOCALL}'));
		$cidnameval.= '${PRESENCESTATE_DISPLAY}';
	}
	$ext->add($mcontext,$exten,'', new ext_set('CONNECTEDLINE(name,i)', $cidnameval));
	$ext->add($mcontext,$exten,'', new ext_set('CONNECTEDLINE(num)', '${EXTTOCALL}'));
	$ext->add($mcontext,$exten,'', new ext_set('D_OPTIONS', '${D_OPTIONS}I'));
}
//Purpose is to have the option to add sip-headers as with the trunk pre dial out hook.
//We need to have this as we have mobile extensions connected directly to the pbx as sip extensions.
$ext->add($mcontext,$exten,'godial', new ext_macro('dialout-one-predial-hook'));

//dont allow inbound callers to transfer around inside the system
$ext->add($mcontext,$exten,'', new ext_execif('$["${DIRECTION}" = "INBOUND"]', 'Set', 'D_OPTIONS=${STRREPLACE(D_OPTIONS,T)}I'));
$ext->add($mcontext,$exten,'', new ext_dial('${DSTRING}', '${ARG1},${D_OPTIONS}b(func-apply-sipheaders^s^1)'));
$ext->add($mcontext,$exten,'', new ext_execif('$["${DIALSTATUS}"="ANSWER" & "${CALLER_DEST}"!=""]', 'MacroExit'));

$ext->add($mcontext,$exten,'', new ext_execif('$["${DIALSTATUS_CW}"!=""]', 'Set', 'DIALSTATUS=${DIALSTATUS_CW}'));
$ext->add($mcontext,$exten,'', new ext_gosubif('$[("${SCREEN}"!=""&("${DIALSTATUS}"="TORTURE"|"${DIALSTATUS}"="DONTCALL"))|"${DIALSTATUS}"="ANSWER"]','s-${DIALSTATUS},1'));

$ext->add($mcontext,$exten,'', new ext_macroexit());
$ext->add($mcontext,$exten,'nodial', new ext_execif('$["${DIALSTATUS}" = ""]', 'Set', 'DIALSTATUS=NOANSWER'));
$ext->add($mcontext,$exten,'', new ext_noop('Returned from dial-one with nothing to call and DIALSTATUS: ${DIALSTATUS}'));
$ext->add($mcontext,$exten,'', new ext_macroexit());

$exten = 'h';
$ext->add($mcontext, $exten, '', new ext_macro('hangupcall'));

$exten = 'usegoto';
$ext->add($mcontext,$exten,'', new ext_set('USEGOTO', ''));
$ext->add($mcontext,$exten,'', new ext_goto('1','${DSTRING}','from-internal'));

$exten = 'screen';
$ext->add($mcontext,$exten,'', new ext_gotoif('$["${DB(AMPUSER/${DEXTEN}/screen)}"!="nomemory" | "${CALLERID(number)}"=""]','memory'));
$ext->add($mcontext,$exten,'', new ext_execif('$[${REGEX("^[0-9a-zA-Z ]+$" ${CALLERID(number)})} = 1]', 'System', 'rm -f ${ASTVARLIBDIR}/sounds/priv-callerintros/${CALLERID(number)}.*'));
$ext->add($mcontext,$exten,'memory', new ext_set('__SCREEN', '${DB(AMPUSER/${DEXTEN}/screen)}'));
$ext->add($mcontext,$exten,'', new ext_set('__SCREEN_EXTEN', '${DEXTEN}'));
$ext->add($mcontext,$exten,'', new ext_set('ARG2', '${ARG2}p'));
$ext->add($mcontext,$exten,'', new ext_return(''));

$exten = 'cf';
$ext->add($mcontext,$exten,'', new ext_set('CFAMPUSER', '${IF($["${AMPUSER}"=""]?${CALLERID(number)}:${AMPUSER})}'));
$ext->add($mcontext,$exten,'', new ext_execif('$["${DB(CF/${DEXTEN})}"="${CFAMPUSER}" | "${DB(CF/${DEXTEN})}"="${REALCALLERIDNUM}" | "${CUT(CUT(BLINDTRANSFER,-,1),/,1)}" = "${DB(CF/${DEXTEN})}" | "${DEXTEN}"="${DB(CF/${DEXTEN})}"]', 'Return'));
$ext->add($mcontext,$exten,'', new ext_execif('$["${DB(AMPUSER/${DEXTEN}/cfringtimer)}" != "0" & "${DB(AMPUSER/${DEXTEN}/cfringtimer)}" != ""]', 'Set', 'ARG1=${IF($["${DB(AMPUSER/${DEXTEN}/cfringtimer)}"="-1"]? : ${DB(AMPUSER/${DEXTEN}/cfringtimer)})}'));
$ext->add($mcontext,$exten,'', new ext_set('DEXTEN', '${IF($["${CFIGNORE}"=""]?"${DB(CF/${DEXTEN})}#": )}'));
if ($amp_conf['DIVERSIONHEADER']) $ext->add($mcontext,$exten,'', new ext_set('__DIVERSION_REASON', '${IF($["${DEXTEN}"!=""]?"unconditional": )}'));
$ext->add($mcontext,$exten,'', new ext_execif('$["${DEXTEN}"!=""]', 'Return'));
$ext->add($mcontext,$exten,'', new ext_set('DIALSTATUS', 'NOANSWER'));
$ext->add($mcontext,$exten,'', new ext_return(''));

$exten = 'qwait';
$ext->add($mcontext,$exten,'', new ext_execif('$["${SAVEDCIDNAME}" = ""]', 'Set', '__SAVEDCIDNAME=${CALLERID(name)}'));
$ext->add($mcontext,$exten,'', new ext_set('ELAPSED', '${MATH($[${EPOCH}+30-${QUEUEWAIT}]/60,int)}'));
$ext->add($mcontext,$exten,'', new ext_set('CALLERID(name)', 'M${ELAPSED}:${SAVEDCIDNAME}'));
$ext->add($mcontext,$exten,'', new ext_return(''));

$exten = 'ctset';
$ext->add($mcontext,$exten,'', new ext_set('DB(CALLTRACE/${DEXTEN})', '${CALLERID(number)}'));
$ext->add($mcontext,$exten,'', new ext_return(''));

$exten = 'ctclear';
$ext->add($mcontext,$exten,'', new ext_dbdel('CALLTRACE/${DEXTEN}'));
$ext->add($mcontext,$exten,'', new ext_return(''));

$exten = 'dstring';
$ext->add($mcontext,$exten,'', new ext_set('DSTRING', ''));
$ext->add($mcontext,$exten,'', new ext_set('DEVICES', '${DB(AMPUSER/${DEXTEN}/device)}'));
$ext->add($mcontext,$exten,'', new ext_execif('$["${DEVICES}"=""]', 'Return'));
$ext->add($mcontext,$exten,'', new ext_execif('$["${DEVICES:0:1}"="&"]', 'Set', 'DEVICES=${DEVICES:1}'));
$ext->add($mcontext,$exten,'', new ext_set('LOOPCNT', '${FIELDQTY(DEVICES,&)}'));
$ext->add($mcontext,$exten,'', new ext_set('ITER', '1'));
$ext->add($mcontext,$exten,'begin', new ext_set('THISDIAL', '${DB(DEVICE/${CUT(DEVICES,&,${ITER})}/dial)}'));
if ($chan_dahdi) {
	$ext->add($mcontext,$exten,'', new ext_gosubif('$["${ASTCHANDAHDI}" = "1"]','zap2dahdi,1'));
}
// PJSip checks. Instead of just dialling PJSIP/xxx, always reference the function
// PJSIP_DIAL_CONTACTS with the endpoint id. This may return 'PJSIP/xxx', or it may
// return any number of strings that will be valid to pass to Dial().
$ext->add($mcontext,$exten,'', new ext_gotoif('$["${THISDIAL:0:5}"!="PJSIP"]', 'docheck'));
$ext->add($mcontext,$exten,'', new ext_noop('Debug: Found PJSIP Destination ${THISDIAL}, updating with PJSIP_DIAL_CONTACTS'));
$ext->add($mcontext,$exten,'', new ext_set('THISDIAL', '${PJSIP_DIAL_CONTACTS(${THISDIAL:6})}'));

// If PJSIP_DIAL_CONTACTS returns nothing, then don't try to add it to the dial string.
$ext->add($mcontext,$exten,'docheck', new ext_gotoif('$["${THISDIAL}"=""]','skipset'));

$ext->add($mcontext,$exten,'doset', new ext_set('DSTRING', '${DSTRING}${THISDIAL}&'));
$ext->add($mcontext,$exten,'skipset', new ext_set('ITER', '$[${ITER}+1]'));
$ext->add($mcontext,$exten,'', new ext_gotoif('$[${ITER}<=${LOOPCNT}]','begin'));

// Does it NOT end with an ampersand? We can just return.
$ext->add($mcontext,$exten,'', new ext_execif('$["${DSTRING:-1}"!="&"]', 'Return'));

// It does, remove it, and hand it back.
$ext->add($mcontext,$exten,'', new ext_set('DSTRING', '${DSTRING:0:$[${LEN(${DSTRING})}-1]}'));
$ext->add($mcontext,$exten,'', new ext_return(''));

$exten = 'dlocal';
//$ext->add($mcontext,$exten,'', new ext_set('DSTRING', 'Local/${DEXTEN:0:${MATH(${LEN(${DEXTEN})}-1,int)}}@from-internal/n'));
$ext->add($mcontext,$exten,'', new ext_set('DSTRING', '${IF($["${ARG1}"=""]?${DEXTEN:0:${MATH(${LEN(${DEXTEN})}-1,int)}}:Local/${DEXTEN:0:${MATH(${LEN(${DEXTEN})}-1,int)}}@from-internal/n)}'));
$ext->add($mcontext,$exten,'', new ext_set('USEGOTO', '${IF($["${ARG1}"=""]?1:0)}'));
$ext->add($mcontext,$exten,'', new ext_return(''));

if ($chan_dahdi) {
	$exten = 'zap2dahdi';
	$ext->add($mcontext,$exten,'', new ext_execif('$["${THISDIAL}" = ""]', 'Return'));
	$ext->add($mcontext,$exten,'', new ext_set('NEWDIAL', ''));
	$ext->add($mcontext,$exten,'', new ext_set('LOOPCNT2', '${FIELDQTY(THISDIAL,&)}'));
	$ext->add($mcontext,$exten,'', new ext_set('ITER2', '1'));
	$ext->add($mcontext,$exten,'begin2', new ext_set('THISPART2', '${CUT(THISDIAL,&,${ITER2})}'));
	$ext->add($mcontext,$exten,'', new ext_execif('$["${THISPART2:0:3}" = "ZAP"]', 'Set','THISPART2=DAHDI${THISPART2:3}'));
	$ext->add($mcontext,$exten,'', new ext_set('NEWDIAL', '${NEWDIAL}${THISPART2}&'));
	$ext->add($mcontext,$exten,'', new ext_set('ITER2', '$[${ITER2} + 1]'));
	$ext->add($mcontext,$exten,'', new ext_gotoif('$[${ITER2} <= ${LOOPCNT2}]','begin2'));
	$ext->add($mcontext,$exten,'', new ext_set('THISDIAL', '${NEWDIAL:0:$[${LEN(${NEWDIAL})}-1]}'));
	$ext->add($mcontext,$exten,'', new ext_return(''));
}

/*
 * There are reported bugs in Asterisk Blind Trasfers that result in Dial() returning and continuing
 * execution with a status of ANSWER. So we hangup at this point
 */
$exten = 's-ANSWER';
$ext->add($context, $exten, '', new ext_noop('Call successfully answered - Hanging up now'));
$ext->add($context, $exten, '', new ext_macro('hangupcall'));

$exten = 's-TORTURE';
$ext->add($mcontext,$exten,'', new ext_goto('1','musiconhold','app-blackhole'));
$ext->add($mcontext,$exten,'', new ext_macro('hangupcall'));

$exten = 's-DONTCALL';
$ext->add($mcontext,$exten,'', new ext_answer(''));
$ext->add($mcontext,$exten,'', new ext_wait('1'));
$ext->add($mcontext,$exten,'', new ext_zapateller(''));
$ext->add($mcontext,$exten,'', new ext_playback('ss-noservice'));
$ext->add($mcontext,$exten,'', new ext_macro('hangupcall'));

/*
 * If an endpoint is offline, app_dial returns with CHANUNAVAIL, we deal with this the same way
 * as we do with NOANSWER
 */

foreach (array('s-CHANUNAVAIL', 's-NOANSWER', 's-BUSY') as $exten) {
	$ext->add($mcontext,$exten,'', new ext_macro('vm','${SCREEN_EXTEN},BUSY,${IVR_RETVM}'));
	$ext->add($mcontext,$exten,'', new ext_execif('$["${IVR_RETVM}"!="RETURN" | "${IVR_CONTEXT}"=""]','Hangup'));
	$ext->add($mcontext,$exten,'', new ext_return(''));
}

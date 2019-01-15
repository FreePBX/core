<?php

/*
 * Set a SIP Header to be used in the next call.
 */

$c = 'func-set-sipheader'; // Context
$e = 's'; // Exten

$ext->add($c,$e,'', new ext_noop('Sip Add Header function called. Adding ${ARG1} = ${ARG2}'));
$ext->add($c,$e,'', new ext_set('HASH(_SIPHEADERS,${ARG1})', '${ARG2}'));
$ext->add($c,$e,'', new ext_return());

/*
 * Apply a SIP Header to the call that's about to be made
 */

$c = 'func-apply-sipheaders';

$ext->add($c,$e,'', new ext_noop('Applying SIP Headers to channel ${CHANNEL}'));
$ext->add($c,$e,'', new ext_set('TECH', '${CUT(CHANNEL,/,1)}'));
$ext->add($c,$e,'', new ext_set('SIPHEADERKEYS', '${HASHKEYS(SIPHEADERS)}'));
$ext->add($c,$e,'', new ext_execif('$["${HASH(SIPHEADERS,Alert-Info)}" = "unset"]', 'Set', 'Rheader=1')); //remove header
$ext->add($c,$e,'', new ext_while('$["${SET(sipkey=${SHIFT(SIPHEADERKEYS)})}" != ""]'));
$ext->add($c,$e,'', new ext_set('sipheader', '${HASH(SIPHEADERS,${sipkey})}'));
$ext->add($c,$e,'', new ext_execif('$["${sipkey}" = "Alert-Info" & ${REGEX("^<[^>]*>" ${sipheader})} != 1]', 'Set', 'sipheader=<uri>\;info=${sipheader}'));
$driver = \FreePBX::Config()->get("ASTSIPDRIVER");
if(in_array($driver,array("both","chan_sip"))) {
	$ext->add($c,$e,'', new ext_execif('$["${TECH}" = "SIP"]','SIPAddHeader','${sipkey},${sipheader}'));
}
if(in_array($driver,array("both","chan_pjsip"))) {
	$ext->add($c,$e,'', new ext_execif('$["${TECH}" = "PJSIP"]','Set','PJSIP_HEADER(add,${sipkey})=${sipheader}'));
}
$ext->add($c,$e,'', new ext_endwhile(''));
if (in_array($driver,array("both","chan_sip"))) {
	$ext->add($c,$e,'', new ext_execif('$["${Rheader}" = "1" & "${TECH}" = "SIP"]','SIPRemoveHeader','Alert-Info:'));
}
if (in_array($driver,array("both","chan_pjsip"))) {
	$ext->add($c,$e,'', new ext_execif('$["${Rheader}" = "1" & "${TECH}" = "PJSIP"]','Set','PJSIP_HEADER(remove,Alert-Info)='));
}
$ext->add($c,$e,'', new ext_return());

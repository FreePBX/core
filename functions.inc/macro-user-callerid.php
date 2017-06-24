<?php

/*
* sets the CallerID of the device to that of the logged in user
*
* ${AMPUSER} is set upon return to the real user despite any aliasing that may
* have been set as a result of the AMPUSER/<nnn>/cidnum field. This is used by
* features like DND, CF, etc. to set the proper structure on aliased instructions
*/
$context = 'macro-user-callerid';
$exten = 's';

//$ext->add($context, $exten, '', new ext_noop('user-callerid: ${CALLERID(name)} ${CALLERID(number)}'));

// for i18n playback in multiple languages
$ext->add($context, 'lang-playback', '', new ext_gosubif('$[${DIALPLAN_EXISTS('.$context.',${CHANNEL(language)})}]', $context.',${CHANNEL(language)},${ARG1}', $context.',en,${ARG1}'));
$ext->add($context, 'lang-playback', '', new ext_return());

$ext->add($context, $exten, '', new ext_set('TOUCH_MONITOR','${UNIQUEID}'));
// make sure AMPUSER is set if it doesn't get set below
$ext->add($context, $exten, '', new ext_set('AMPUSER', '${IF($["${AMPUSER}" = ""]?${CALLERID(number)}:${AMPUSER})}'));
$ext->add($context, $exten, '', new ext_gotoif('$["${CUT(CHANNEL,@,2):5:5}"="queue" | ${LEN(${AMPUSERCIDNAME})}]', 'report'));
//REALCALLERIDNUM Inheriting because of: http://issues.freepbx.org/browse/FREEPBX-13173
$ext->add($context, $exten, '', new ext_execif('$["${REALCALLERIDNUM:1:2}" = ""]', 'Set', '__REALCALLERIDNUM=${CALLERID(number)}'));
$ext->add($context, $exten, '', new ext_set('AMPUSER', '${DB(DEVICE/${REALCALLERIDNUM}/user)}'));

// Device & User: If they're not signed in, then they can't do anything.
$ext->add($context, $exten, '', new ext_gotoif('$["${AMPUSER}" = "none"]', 'limit'));

$ext->add($context, $exten, '', new ext_set('AMPUSERCIDNAME', '${DB(AMPUSER/${AMPUSER}/cidname)}'));
$ext->add($context, $exten, '', new ext_gotoif('$["${AMPUSERCIDNAME:1:2}" = ""]', 'report'));

// user may masquerade as a different user internally, so set the internal cid as indicated
// but keep the REALCALLERID which is used to determine their true identify and lookup info
// during outbound calls.
$ext->add($context, $exten, '', new ext_set('AMPUSERCID', '${IF($["${ARG2}" != "EXTERNAL" & "${DB_EXISTS(AMPUSER/${AMPUSER}/cidnum)}" = "1"]?${DB_RESULT}:${AMPUSER})}'));

// If there is a defined dialopts then use it, otherwise use the global default
//
$ext->add($context, $exten, '', new ext_set('__DIAL_OPTIONS', '${IF($["${DB_EXISTS(AMPUSER/${AMPUSER}/dialopts)}" = "1"]?${DB_RESULT}:${DIAL_OPTIONS})}'));

$ext->add($context, $exten, '', new ext_set('CALLERID(all)', '"${AMPUSERCIDNAME}" <${AMPUSERCID}>'));

$ext->add($context, $exten, '', new ext_noop_trace('Current Concurrency Count for ${AMPUSER}: ${GROUP_COUNT(${AMPUSER}@concurrency_limit)}, User Limit: ${DB(AMPUSER/${AMPUSER}/concurrency_limit)}'));
$ext->add($context, $exten, '', new ext_gotoif('$["${ARG1}"="LIMIT" & ${LEN(${AMPUSER})} & ${DB_EXISTS(AMPUSER/${AMPUSER}/concurrency_limit)} & ${DB(AMPUSER/${AMPUSER}/concurrency_limit)}>0 & ${GROUP_COUNT(${AMPUSER}@concurrency_limit)}>=${DB(AMPUSER/${AMPUSER}/concurrency_limit)}]', 'limit'));
$ext->add($context, $exten, '', new ext_execif('$["${ARG1}"="LIMIT" & ${LEN(${AMPUSER})}]', 'Set', 'GROUP(concurrency_limit)=${AMPUSER}'));
/*
* This is where to splice in things like setting the language based on a user's astdb setting,
* or where you might set the CID account code based on a user instead of the device settings.
*/

$ext->add($context, $exten, 'report', new ext_gotoif('$[ "${ARG1}" = "SKIPTTL" | "${ARG1}" = "LIMIT" ]', 'continue'));
$ext->add($context, $exten, 'report2', new ext_set('__TTL', '${IF($["foo${TTL}" = "foo"]?6:$[ ${TTL} - 1 ])}'));
$ext->add($context, $exten, '', new ext_gotoif('$[ ${TTL} > 0 ]', 'continue'));
$ext->add($context, $exten, '', new ext_wait('${RINGTIMER}'));  // wait for a while, to give it a chance to be picked up by voicemail
$ext->add($context, $exten, '', new ext_answer());
$ext->add($context, $exten, '', new ext_wait('1'));
$ext->add($context, $exten, '', new ext_gosub('1', 'lang-playback', $context, 'hook_0'));
$ext->add($context, $exten, '', new ext_macro('hangupcall'));
$ext->add($context, $exten, 'limit', new ext_answer());
$ext->add($context, $exten, '', new ext_wait('1'));
$ext->add($context, $exten, '', new ext_gosub('1', 'lang-playback', $context, 'hook_1'));
$ext->add($context, $exten, '', new ext_macro('hangupcall'));
$ext->add($context, $exten, '', new ext_congestion(20));

// Address Security Vulnerability in many earlier versions of Asterisk from an external source tranmitting a
// malicious CID that can cause overflows in the Asterisk code.
//
$ext->add($context, $exten, 'continue', new ext_set('CALLERID(number)','${CALLERID(number):0:40}'));
$ext->add($context, $exten, '', new ext_set('CALLERID(name)','${CALLERID(name):0:40}'));
//FREEPBX-12752 if CNAM is empty skip setting it...
$ext->add($context, $exten, '', new ext_gotoif('$["${CALLERID(name)}" = ""]', 'cnum'));
$ext->add($context, $exten, '', new ext_set('CDR(cnam)','${CALLERID(name)}'));
$ext->add($context, $exten, 'cnum', new ext_set('CDR(cnum)','${CALLERID(num)}'));

// CHANNEL(language) does not get inherited (which seems like an Asterisk bug as musicclass does)
// so if whe have MASTER_CHANNEL() available to us let's rectify that
//
if ($amp_conf['AST_FUNC_MASTER_CHANNEL']) {
  $ext->add($context, $exten, '', new ext_set('CHANNEL(language)', '${MASTER_CHANNEL(CHANNEL(language))}'));
}
$ext->add($context, $exten, '', new ext_noop_trace('Using CallerID ${CALLERID(all)}'));
$ext->add($context, 'h', '', new ext_macro('hangupcall'));

$lang = 'en'; //English
$ext->add($context, $lang, 'hook_0', new ext_playback('im-sorry&an-error-has-occurred&with&call-forwarding'));
$ext->add($context, $lang, '', new ext_return());
$ext->add($context, $lang, 'hook_1', new ext_playback('beep&im-sorry&your&simul-call-limit-reached&goodbye'));
$ext->add($context, $lang, '', new ext_return());
$lang = 'ja'; //Japanese
$ext->add($context, $lang, 'hook_0', new ext_playback('im-sorry&call-forwarding&jp-no&an-error-has-occured'));
$ext->add($context, $lang, '', new ext_return());
$ext->add($context, $lang, 'hook_1', new ext_playback('beep&im-sorry&simul-call-limit-reached'));
$ext->add($context, $lang, '', new ext_return());

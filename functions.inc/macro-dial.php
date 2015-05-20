<?php

/**
 * macro-dial
 *
 * Originally a hand-written macro in extensions.conf, it has now been ported
 * to generated dialplan, so it can be altered programatically.
 *
 * Note: This is EXACTLY THE SAME as the hand written code. If there is anything
 * here that doesn't seem correct, that's the way it's always been, so look harder
 * before you change anything.
 */

$c = "macro-dial";
$s = "s";

$ext->add($c,$s,'', new ext_gotoif('$["${MOHCLASS}" = ""]', 'dial'));
$ext->add($c,$s,'', new ext_set('CHANNEL(musicclass)', '${MOHCLASS}'));
$ext->add($c,$s,'dial', new ext_agi('dialparties.agi'));
$ext->add($c,$s,'', new ext_noop('Returned from dialparties with no extensions to call and DIALSTATUS: ${DIALSTATUS}'));
$ext->add($c,$s,'normdial', new ext_dial('${ds}b(func-apply-sipheaders^s^1)', ''), 'n', 2); // dialparties will set the priority to 10 if $ds is not null
$ext->add($c,$s,'', new ext_set('DIALSTATUS', '${IF($["${DIALSTATUS_CW}"!="" ]?${DIALSTATUS_CW}:${DIALSTATUS})}'));
$ext->add($c,$s,'', new ext_gosubif('$[("${SCREEN}" != "" & ("${DIALSTATUS}" = "TORTURE" | "${DIALSTATUS}" = "DONTCALL"))  | "${DIALSTATUS}" = "ANSWER"]', '${DIALSTATUS},1'));

// This is bad and terrible, and don't do this. But we can't, currently,
// explicitly set a priority.
$ext->_exts[$c][" s "][] = array('basetag' => 20, 'tag' => 'huntdial', 'addpri' => false, 'cmd' => new ext_noop('Returned from dialparties with hunt groups to dial '));

$ext->add($c,$s,'', new ext_set('HuntLoop', (string) '0')); // String zeros, to avoid php getting confused.
$ext->add($c,$s,'a22', new ext_gotoif('$[${HuntMembers} >= 1]', 'a30')); // if this is from rg-group, don't strip prefix
$ext->add($c,$s,'', new ext_noop('Returning there are no members left in the hunt group to ring'));
$ext->add($c,$s,'a30', new ext_set('HuntMember', 'HuntMember${HuntLoop}'), 'n', 2);
$ext->add($c,$s,'', new ext_gotoif('$[$["${CALLTRACE_HUNT}" != "" ] & $[$["${RingGroupMethod}" = "hunt" ] | $["${RingGroupMethod}" = "firstavailable"] | $["${RingGroupMethod}" = "firstnotonphone"]]]', 'a32', 'a35'));
$ext->add($c,$s,'a32', new ext_set('CT_EXTEN', '${CUT(FILTERED_DIAL,,$[${HuntLoop} + 1])}'));
$ext->add($c,$s,'', new ext_set('DB(CALLTRACE/${CT_EXTEN})', '${CALLTRACE_HUNT}'));
$ext->add($c,$s,'', new ext_goto('a42', 's'));
$ext->add($c,$s,'a35', new ext_gotoif('$[$["${CALLTRACE_HUNT}" != "" ] & $["${RingGroupMethod}" = "memoryhunt" ]]', 'a36', 'a50'));
$ext->add($c,$s,'a36', new ext_set('CTLoop', (string) '0')); // String zeros.
$ext->add($c,$s,'a37', new ext_gotoif('$[${CTLoop} > ${HuntLoop}]', 'a42')); // if this is from rg-group, don't strip prefix
$ext->add($c,$s,'', new ext_set('CT_EXTEN', '${CUT(FILTERED_DIAL,,$[${CTLoop} + 1])}'));
$ext->add($c,$s,'', new ext_set('DB(CALLTRACE/${CT_EXTEN})', '${CALLTRACE_HUNT}'));
$ext->add($c,$s,'', new ext_set('CTLoop', '$[1 + ${CTLoop}]'));
$ext->add($c,$s,'', new ext_goto('a37', 's'));
$ext->add($c,$s,'a42', new ext_dial('${${HuntMember}}${ds}', ''));
$ext->add($c,$s,'', new ext_gotoif('$["${DIALSTATUS}" = "ANSWER"]', 'ANSWER,1'));
$ext->add($c,$s,'', new ext_set('HuntLoop', '$[1 + ${HuntLoop}]'));
$ext->add($c,$s,'', new ext_gotoif('$[$[$["foo${RingGroupMethod}" != "foofirstavailable"] & $["foo${RingGroupMethod}" != "foofirstnotonphone"]] | $["foo${DialStatus}" = "fooBUSY"]]', 'a46'));
$ext->add($c,$s,'', new ext_set('HuntMembers', (string) '0')); // String zeros.
$ext->add($c,$s,'a46', new ext_set('HuntMembers', '$[${HuntMembers} - 1]'));
$ext->add($c,$s,'', new ext_goto('a22', 's'));
$ext->add($c,$s,'a50', new ext_noop('Deleting: CALLTRACE/${CT_EXTEN} ${DB_DELETE(CALLTRACE/${CT_EXTEN})}'));
$ext->add($c,$s,'', new ext_goto('a42', 's'));

$s = 'NOANSWER';
$ext->add($c,$s,'', new ext_macro('vm', '${SCREEN_EXTEN},BUSY,${IVR_RETVM}'));
$ext->add($c,$s,'', new ext_gotoif('$["${IVR_RETVM}" != "RETURN" | "${IVR_CONTEXT}" = ""]', 'bye'));
$ext->add($c,$s,'', new ext_return());
$ext->add($c,$s,'bye', new ext_macro('hangupcall'));

$s = 'TORTURE';
$ext->add($c,$s,'', new ext_goto('app-blackhole,musiconhold,1'));
$ext->add($c,$s,'', new ext_macro('hangupcall'));

$s = 'DONTCALL';
$ext->add($c,$s,'', new ext_answer());
$ext->add($c,$s,'', new ext_wait(1));
$ext->add($c,$s,'', new ext_zapateller());
$ext->add($c,$s,'', new ext_playback('ss-noservice'));
$ext->add($c,$s,'', new ext_macro('hangupcall'));

$s = 'ANSWER';
$ext->add($c,$s,'', new ext_noop('Call successfully answered - Hanging up now'));
$ext->add($c,$s,'', new ext_macro('hangupcall'));

$ext->add($c,'h','', new ext_macro('hangupcall'));

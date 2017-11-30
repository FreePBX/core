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

//Advanced settings alert info internal calls
$ext->add($c,$s,'', new ext_noop('Blind Transfer: ${BLINDTRANSFER}, Attended Transfer: ${ATTENDEDTRANSFER}, User: ${AMPUSER}, Alert Info: ${ALERT_INFO}'));
$ai = FreePBX::Config()->get('INTERNALALERTINFO');
$ai = trim($ai);
$ai = ($ai != "none" && $ai != "inherit") ? $ai : '';
$ext->add($c,$s,'', new ext_execif('$["${ALERT_INFO}"="" & ${LEN(${AMPUSER})}!=0 & ${LEN(${BLINDTRANSFER})}=0 & ${LEN(${ATTENDEDTRANSFER})}=0]', 'Set', 'ALERT_INFO='.$ai));

//Advanced settings alert info Blind Transfer
$bt = FreePBX::Config()->get('BLINDTRANSALERTINFO');
$bt = trim($bt);
$bt = ($bt != "none" && $bt != "inherit") ? $bt : '';
$ext->add($c,$s,'', new ext_execif('$[${LEN(${BLINDTRANSFER})}!=0]', 'Set', 'ALERT_INFO='.$bt));

//Advanced settings alert info Attended Transfer
$at = FreePBX::Config()->get('ATTTRANSALERTINFO');
$at = trim($at);
$at = ($at != "none" && $at != "inherit") ? $at : '';
$ext->add($c,$s,'', new ext_execif('$[${LEN(${ATTENDEDTRANSFER})}!=0]', 'Set', 'ALERT_INFO='.$at));

$ext->add($c,$s,'', new ext_execif('$[("${MOHCLASS}"!="default") & ("${MOHCLASS}"!="")]', 'Set', 'CHANNEL(musicclass)=${MOHCLASS}'));

$ext->add($c,$s,'dial', new ext_agi('dialparties.agi'));
$ext->add($c,$s,'', new ext_noop('Returned from dialparties with no extensions to call and DIALSTATUS: ${DIALSTATUS}'));
$ext->add($c,$s,'', new ext_macroexit());

//Ringall
$ext->add($c,$s,'normdial', new ext_noop('Returned from dialparties with groups to dial')); // dialparties will set the priority to 10 if $ds is not null
$ext->add($c,$s,'', new ext_noop('ringall array ${FMGL_DIAL} '));
$ext->add($c,$s,'', new ext_set('__FMGL_DIAL','${FMGL_DIAL}'));
$ext->add($c,$s,'', new ext_set('LOOPCNT','${FIELDQTY(FILTERED_DIAL,-)}'));
$ext->add($c,$s,'', new ext_set('ITER','1'));
$ext->add($c,$s,'ndloopbegin', new ext_set('EXTTOCALL','${CUT(FILTERED_DIAL,-,${ITER})}'));
$ext->add($c,$s,'', new ext_noop('Working with ${EXTTOCALL}'));
$ext->add($c,$s,'', new ext_execif('$["${RVOL}"!=""]', 'Set', 'HASH(__SIPHEADERS,Alert-Info)=${IF($["${ALERT_INFO}"!=""]?${ALERT_INFO}:Normal)}\;volume=${RVOL}'));
$ext->add($c,$s,'', new ext_execif('$["${RVOL}"="" & "${DB(AMPUSER/${EXTTOCALL}/rvolume)}" != ""]', 'Set', 'HASH(__SIPHEADERS,Alert-Info)=${IF($["${ALERT_INFO}"!=""]?${ALERT_INFO}:Normal)}\;volume=${DB(AMPUSER/${EXTTOCALL}/rvolume)}'));
$ext->add($c,$s,'', new ext_set('ITER','$[${ITER}+1]'));
$ext->add($c,$s,'', new ext_gotoif('$[${ITER}<=${LOOPCNT}]', 'ndloopbegin')); // if this is from rg-group, don't strip prefix
$ext->add($c,$s,'', new ext_macro('dial-ringall-predial-hook'));
$ext->add($c,$s,'nddialapp', new ext_dial('${ds}b(func-apply-sipheaders^s^1)', '')); // dialparties will set the priority to 10 if $ds is not null
$ext->add($c,$s,'', new ext_set('DIALSTATUS', '${IF($["${DIALSTATUS_CW}"!="" ]?${DIALSTATUS_CW}:${DIALSTATUS})}'));
$ext->add($c,$s,'', new ext_gosubif('$[("${SCREEN}" != "" & ("${DIALSTATUS}" = "TORTURE" | "${DIALSTATUS}" = "DONTCALL"))  | "${DIALSTATUS}" = "ANSWER"]', '${DIALSTATUS},1'));
$ext->add($c,$s,'groupnoanswer', new ext_noop('Returning since nobody answered'));
$ext->add($c,$s,'', new ext_macroexit());

//Hunt Groups
$ext->add($c,$s,'huntdial', new ext_noop('Returned from dialparties with ${HuntMembers} hunt members to dial'));
$ext->add($c,$s,'', new ext_set('HuntLoop', (string) '0')); // String zeros, to avoid php getting confused.
$ext->add($c,$s,'', new ext_execif('$[${LEN(${HuntMembers})}=0]', 'Set', 'HuntMembers=0')); //make sure HuntMembers isnt set to null
$ext->add($c,$s,'a22', new ext_gotoif('$[${HuntMembers} >= 1]', 'a30')); // if this is from rg-group, don't strip prefix
$ext->add($c,$s,'huntnoanswer', new ext_noop('Returning as there are no members left in the hunt group to ring'));
$ext->add($c,$s,'', new ext_macroexit());
$ext->add($c,$s,'a30', new ext_set('HuntMember', 'HuntMember${HuntLoop}'));
$ext->add($c,$s,'', new ext_gotoif('$[$["${CALLTRACE_HUNT}" != "" ] & $[$["${RingGroupMethod}" = "hunt" ] | $["${RingGroupMethod}" = "firstavailable"] | $["${RingGroupMethod}" = "firstnotonphone"]]]', 'a32', 'a35'));
$ext->add($c,$s,'a32', new ext_set('CT_EXTEN', '${CUT(FILTERED_DIAL,,$[${HuntLoop} + 1])}'));
$ext->add($c,$s,'', new ext_set('EXTTOCALL','${CT_EXTEN}')); //keep all variables consistent
$ext->add($c,$s,'', new ext_set('__PICKUPMARK','${CT_EXTEN}')); //FREEPBX-10139 directed pickup and followme issue
$ext->add($c,$s,'', new ext_set('DB(CALLTRACE/${CT_EXTEN})', '${CALLTRACE_HUNT}'));
$ext->add($c,$s,'', new ext_goto('huntstart', 's'));
$ext->add($c,$s,'a35', new ext_gotoif('$[$["${CALLTRACE_HUNT}" != "" ] & $["${RingGroupMethod}" = "memoryhunt" ]]', 'a36', 'a50'));
$ext->add($c,$s,'a36', new ext_set('CTLoop', (string) '0')); // String zeros.
$ext->add($c,$s,'a37', new ext_gotoif('$[${CTLoop} > ${HuntLoop}]', 'huntstart')); // if this is from rg-group, don't strip prefix
$ext->add($c,$s,'', new ext_set('CT_EXTEN', '${CUT(FILTERED_DIAL,,$[${CTLoop} + 1])}'));
$ext->add($c,$s,'', new ext_set('EXTTOCALL','${CT_EXTEN}')); //keep all variables consistent
$ext->add($c,$s,'', new ext_set('DB(CALLTRACE/${CT_EXTEN})', '${CALLTRACE_HUNT}'));
$ext->add($c,$s,'', new ext_set('CTLoop', '$[1 + ${CTLoop}]'));
$ext->add($c,$s,'', new ext_goto('a37', 's'));
$ext->add($c,$s,'huntstart', new ext_noop("Hunt Dial Start"));
$ext->add($c,$s,'', new ext_execif('$["${RVOL}"!=""]', 'Set', 'HASH(__SIPHEADERS,Alert-Info)=${IF($["${ALERT_INFO}"!=""]?${ALERT_INFO}:Normal)}\;volume=${RVOL}'));
$ext->add($c,$s,'', new ext_execif('$["${RVOL}"="" & "${DB(AMPUSER/${EXTTOCALL}/rvolume)}" != ""]', 'Set', 'HASH(__SIPHEADERS,Alert-Info)=${IF($["${ALERT_INFO}"!=""]?${ALERT_INFO}:Normal)}\;volume=${DB(AMPUSER/${EXTTOCALL}/rvolume)}'));
$ext->add($c,$s,'', new ext_macro('dial-hunt-predial-hook'));
$ext->add($c,$s,'hsdialapp', new ext_dial('${${HuntMember}}${ds}b(func-apply-sipheaders^s^1)', ''));
$ext->add($c,$s,'', new ext_gotoif('$["${DIALSTATUS}" = "ANSWER"]', 'ANSWER,1'));
$ext->add($c,$s,'', new ext_set('HuntLoop', '$[1 + ${HuntLoop}]'));
$ext->add($c,$s,'', new ext_gotoif('$[$["${RingGroupMethod}" = "firstavailable"] | $["${RingGroupMethod}" = "firstnotonphone"]] & $[$["${DIALSTATUS}" != "CHANUNAVAIL"] & $["${DIALSTATUS}" != "CONGESTION"]]', 'huntreset', 'a46'));
$ext->add($c,$s,'huntreset', new ext_set('HuntMembers', (string) '1')); // String zeros.
$ext->add($c,$s,'a46', new ext_set('HuntMembers', '$[${HuntMembers} - 1]'));
$ext->add($c,$s,'', new ext_goto('a22', 's'));
$ext->add($c,$s,'a50', new ext_noop('Deleting: CALLTRACE/${CT_EXTEN} ${DB_DELETE(CALLTRACE/${CT_EXTEN})}'));
$ext->add($c,$s,'', new ext_goto('huntstart', 's'));

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
$ext->add($c,$s,'answered', new ext_noop('Call successfully answered - Hanging up now'));
//FREEPBX-14952 Caller Post Hangup Destination option under Virtual Queue is broken.
$ext->add($c,$s,'', new ext_gotoif('$["${CALLER_DEST}"!=""&&"${DIALSTATUS}"="ANSWER"]','${CUT(CALLER_DEST,^,1)},${CUT(CALLER_DEST,^,2)},${CUT(CALLER_DEST,^,3)}'));
$ext->add($c,$s,'', new ext_macro('hangupcall'));

$ext->add($c,'h','', new ext_macro('hangupcall'));

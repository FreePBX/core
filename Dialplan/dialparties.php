<?php
namespace FreePBX\modules\Core\Dialplan;

class dialparties{
	static function add($ext){
		$c = 'dialparties'; // Context

		$ext->add($c, 's', '', new \ext_noop('Starting New Dialparties'));
		$ext->add($c, 's', '', new \ext_setvar('ARG_CNT','1'));
		$ext->add($c, 's', '', new \ext_while('$["${ARG${ARG_CNT}}" != ""]'));
		$ext->add($c, 's', '', new \ext_noop('ARG${ARG_CNT}: ${ARG${ARG_CNT}}'));
		$ext->add($c, 's', '', new \ext_setvar('ARG_CNT','${MATH(${ARG_CNT}+1,int)}'));
		$ext->add($c, 's', '', new \ext_endwhile(''));

		// Get required channels variables that used to come from amportal.conf
		$ext->add($c, 's', '', new \ext_setvar('CWINUSEBUSY', '${CWINUSEBUSY}'));
		$ext->add($c, 's', '', new \ext_setvar('CWIGNORE', '${TOUPPER(${FILTER(A-Za-z0-9,${CWIGNORE})})}'));
		$ext->add($c, 's', '', new \ext_setvar('CFIGNORE', '${TOUPPER(${FILTER(A-Za-z0-9,${CFIGNORE})})}'));
		$ext->add($c, 's', '', new \ext_setvar('SIGNORE', '${TOUPPER(${FILTER(A-Za-z0-9,${SIGNORE})})}'));
		$ext->add($c, 's', '', new \ext_setvar('AMPUSER', '${FILTER(A-Za-z0-9,${AMPUSER})}'));

		$ext->add($c, 's', '', new \ext_setvar('DSARRAY',''));
		$ext->add($c, 's', '', new \ext_setvar('DSORDEREDARRAY',''));
		$ext->add($c, 's', '', new \ext_setvar('workingext',''));
		$ext->add($c, 's', '', new \ext_setvar('REALEXT',''));
		$ext->add($c, 's', '', new \ext_setvar('FINALDS',''));

		// Caller ID info is stored in $request in AGI class, passed from Asterisk
		$ext->add($c, 's', '', new \ext_setvar('CIDNUM','${CALLERID(num)}'));
		$ext->add($c, 's', '', new \ext_setvar('CIDNAME','${CALLERID(name)}'));
		$ext->add($c, 's', '', new \ext_noop('Caller ID name is ${CIDNAME} number is ${CIDNUM}'));
		$ext->add($c, 's', '', new \ext_noop('CW Ignore is: ${CWIGNORE}'));
		$ext->add($c, 's', '', new \ext_noop('CF Ignore is: ${CFIGNORE}'));
		$ext->add($c, 's', '', new \ext_noop('CW IN_USE/BUSY is: ${CWINUSEBUSY}'));

		//Check and add queuewait
		$ext->add($c, 's', '', new \ext_setvar('QUEUEWAIT', '${FILTER(A-Za-z0-9,${QUEUEWAIT})}'));
		$ext->add($c, 's', '', new \ext_gosubif('$["${QUEUEWAIT}"!=""]','dialparties-queuewait,s,1'));
		self::queueWait($ext);

		// From this point forward, Set KEEPCID in the channel so subsequent calls, CF, etc. retain the incoming
		// CID that get sent down channel local.
		$ext->add($c, 's', '', new \ext_setvar('__KEEPCID','TRUE'));

		// Set to '' in case it was previously set
		//
		$ext->add($c, 's', '', new \ext_setvar('DIALSTATUS_CW',''));

		$ext->add($c, 's', '', new \ext_setvar('TIMER','${ARG1}'));
		$ext->add($c, 's', '', new \ext_setvar('DIALOPTS','${ARG2}'));
		$ext->add($c, 's', '', new \ext_setvar('RGMETHOD','${FILTER(A-Za-z0-9\-,${RingGroupMethod})}'));

		// Get all headers
		$ext->add($c, 's', '', new \ext_setvar('ALERTINFO','${ALERT_INFO}'));
		$ext->add($c, 's', '', new \ext_gosubif('$["${ALERTINFO}"!=""]','dialparties-alertinfo,s,1'));
		self::alertInfo($ext);
		$ext->add($c, 's', '', new \ext_gosubif('$["${SIPADDHEADER}"!=""]','dialparties-setheader,s,1'));
		self::setHeader($ext);

		// Follow-Me is only one setting PR_DIALSTATUS so don't bother fetching if not a followme
		$ext->add($c, 's', '', new \ext_execif('$["${FMGRP}"=""]','Set','PR_DIALSTATUS='));

		$ext->add($c, 's', '', new \ext_execif('$["${USE_CONFIRMATION}"=""]','Set','USE_CONFIRMATION=FALSE'));

		$ext->add($c, 's', '', new \ext_noop('USE_CONFIRMATION: ${USE_CONFIRMATION}'));
		$ext->add($c, 's', '', new \ext_noop('RINGGROUP_INDEX: ${RINGGROUP_INDEX}'));

		$ext->add($c, 's', '', new \ext_execif('$["${TIMER}"=""]','Set','TIMER=0'));
		$ext->add($c, 's', '', new \ext_execif('$["${DIALOPTS}"=""]','Set','DIALOPTS='));
		$ext->add($c, 's', '', new \ext_execif('$["${RGMETHOD}"=""]','Set','RGMETHOD=none'));

		$ext->add($c, 's', '', new \ext_noop('Methodology of ring is ${RGMETHOD}'));


		// reset the ringgroup method to its fundamental algorithm and pull out if
		// master mode.
		$ext->add($c, 's', '', new \ext_setvar('RECALL_MASTERMODE','${RGMETHOD}'));
		$ext->add($c, 's', '', new \ext_gosubif('$["${DIALPLAN_EXISTS(dialparties-setmodes,${RGMETHOD},1)}"="1"]','dialparties-setmodes,${RGMETHOD},1','dialparties-setmodes,default,1'));
		self::setModes($ext);

		// Clear it now so subsequent transfers don't honor it any longer
		// unless it's a ringallv2 in which case it is going to get through
		// another level of local channels
		//
		$ext->add($c, 's', '', new \ext_execif('$["${CWIGNORE}"!="" & "${RGMETHOD}"="ringallv2"]','Set','__CWIGNORE='));

		// call confirmation only works with ringall and ringall-prim. The javascripts in ringgroups
		// and follow-me should enforce this. If that has been overridden then force ringall.
		// Keep this code after the matermode check above, since they will at least get mastermode
		// if they set a -prim mode in one of the others
		//
		$ext->add($c, 's', '', new \ext_gosubif('$["${USE_CONFIRMATION}"!="FALSE" & "${RGMETHOD}"!="ringall" & "${RGMETHOD}"!="ringallv2" & "${RGMETHOD}"!="hunt" & "${RGMETHOD}"!="random"]','dialparties-invalidringmethod,s,1'));

		$ext->add($c, 's', '', new \ext_setvar('I','0'));
		// Start with Arg Count set to 3 as two args are used
		$ext->add($c, 's', '', new \ext_setvar('ARG_CNT','3'));
		$ext->add($c, 's', '', new \ext_while('$["${ARG${ARG_CNT}}" != ""]'));
		$ext->add($c, 's', '', new \ext_setvar('TMPARG','${ARG${ARG_CNT}}'));
		$ext->add($c, 's', '', new \ext_while('$["${SET(ext=${SHIFT(TMPARG,-)})}" != ""]'));
		$ext->add($c, 's', '', new \ext_setvar('HASH(EXTLIST,${I})','${ext}'));
		$ext->add($c, 's', '', new \ext_noop('Added extension ${ext} to extension map'));
		$ext->add($c, 's', '', new \ext_setvar('I','${MATH(${I}+1,int)}'));
		$ext->add($c, 's', '', new \ext_endwhile(''));
		$ext->add($c, 's', '', new \ext_setvar('ARG_CNT','${MATH(${ARG_CNT}+1,int)}'));
		$ext->add($c, 's', '', new \ext_endwhile(''));

		// FollowMe Preparation for Pre-Ring:
		//
		// If the primary extension is in the ringgroup list, then it should be rung
		// during both the pre-ring time and the list time, so it's real prering time
		// is the entire time. If it is not in the list, then it should only ring
		// for the pre-ring time. This section determines the times and then adds it
		// to the list if not already there, so that the dialstring is computed
		// appropriately. This section also makes sure that the primary extension
		// is at the top of the list.
		//
		// Notes before I forget. The primary may have been in the list and screwed
		// above. So ... do I need to move this up, probably.
		//
		$ext->add($c, 's', '', new \ext_gosubif('$["${RGMETHOD}"="ringallv2" & "${FMGRP}"!=""]','dialparties-preringprep,s,1'));
		self::preRingPrep($ext);

		$ext->add($c, 's', '', new \ext_setvar('ALREADY_SCREENED','${SCREEN}')); // If this is the second pass through dialparties.agi, we don't want to double-screen the caller
		$ext->add($c, 's', '', new \ext_setvar('FROM_OUTSIDE','${FROM_DID}'));

		$ext->add($c, 's', '', new \ext_setvar('PRIMARY_EXT','${HASH(EXTLIST,0)}'));

		// If this isn't a ring group, check to see if the user has call screening on
		// Only screen calls if the primary extension is called, or it's follow-me is called, not ring groups
		$ext->add($c, 's', '', new \ext_gosubif('$["${FROM_OUTSIDE}"!="" & $["${RGMETHOD}"="none" | "${FMGRP}"!="${PRIMARY_EXT}"] & "${SIGNORE}"="FALSE" & "${ALREADY_SCREENED}"="" & $["${NODEST}"="" | "${FMGRP}"="NODEST"]]','dialparties-callscreencheck,s,1'));
		self::callScreenCheck($ext);

		// IF THE FIRST EXTENSION HAVE CALL FORWARD ENABLED (put in logic) then we don't do master mode
		// which means we reset the flag here after detecting that and just say we are not in master
		// mode and all is well. That means the loop below needs to be modified to detect the first
		// extension and do this if the case.
		$ext->add($c, 's', '', new \ext_gosub('1','s','dialparties-checkcfextensions'));
		self::checkCFExtensions($ext);

		// IF DND AND we process it as a DND (no CF enabled) then we need to some how flag that ALL THE REST
		// should now be ignored and not processed if in master mode (and this primary). Do this by setting some
		// sort of flag that says master mode DND so skip everything else below (set them all to "").
		//
		$ext->add($c, 's', '', new \ext_gosub('1','s','dialparties-checkdndextensions'));
		self::checkDNDExtensions($ext);

		// Main calling loop
		//
		$ext->add($c, 's', '', new \ext_gosub('1','s','dialparties-mainloop'));
		self::isExtAvail($ext);
		self::mainLoop($ext);

		$ext->add($c, 's', '', new \ext_execif('$["${FINALDS}" != "" & ${REGEX("&$" ${FINALDS})}]','Set','FINALDS=${FINALDS:0:-1}')); //remove trailing &
		$ext->add($c, 's', '', new \ext_noop('Final DS so far is ${FINALDS}'));

		//FREEPBX-13726 Follow-me firstnotonphone strategy no longer works as expected
		//if $rgmethod= firstnotonphone then rearrage the number according to their status
		$ext->add($c, 's', '', new \ext_setvar('EXTAVAIL',''));
		$ext->add($c, 's', '', new \ext_setvar('DSARRAYNEW',''));
		$ext->add($c, 's', '', new \ext_setvar('EXTARRAYNEW',''));
		$ext->add($c, 's', '', new \ext_gosubif('$["${RGMETHOD}"="firstnotonphone"]','dialparties-firstnotonphone,s,1'));
		self::firstNotOnPhone($ext);

		//loop over DSORDEREDARRAY
		$ext->add($c, 's', '', new \ext_setvar('Z', '0'));
		$ext->add($c, 's', '', new \ext_while('$["${SET(dsvalue=${HASH(DSORDEREDARRAY,${Z})})}" != ""]'));
		$ext->add($c, 's', '', new \ext_setvar('FILTERED_DIAL','${FILTERED_DIAL}${dsvalue}-'));
		$ext->add($c, 's', '', new \ext_setvar('Z','${MATH(${Z}+1,int)}'));
		$ext->add($c, 's', '', new \ext_endwhile(''));
		$ext->add($c, 's', '', new \ext_execif('$["${FILTERED_DIAL}" != "" & ${REGEX("-$" ${FILTERED_DIAL})}]','Set','FILTERED_DIAL=${FILTERED_DIAL:0:-1}')); //remove trailing -

		$ext->add($c, 's', '', new \ext_noop('Filtered ARG3: ${FILTERED_DIAL}'));
		$ext->add($c, 's', '', new \ext_setvar('FMGL_DIAL','${FILTERED_DIAL}'));

		$ext->add($c, 's', '', new \ext_setvar('DSHUNT',''));
		$ext->add($c, 's', '', new \ext_setvar('HUNT_LOOPS','0'));
		$ext->add($c, 's', '', new \ext_setvar('MYHUNTMEMBER',''));

		/** Here we setup the Channel Variables that are used to do the dialing, in all cases you will have:
		 *  ${HuntMembers} set to the number of phones to ring
		 *  ${HuntMemberN} set to the dial pattern that should be dialed. (N is 0, 1, 2 etc.)
		 */
		$ext->add($c, 's', '', new \ext_gosubif('$["${RGMETHOD}"="hunt" | "${RGMETHOD}"="random" | "${RGMETHOD}"="memoryhunt" | "${RGMETHOD}"="firstavailable" | "${RGMETHOD}"="firstnotonphone"]','dialparties-builddshunt,s,1'));
		self::buildDSHunt($ext);

		$ext->add($c, 's', '', new \ext_execif('$["${FINALDS}" != "" & ${REGEX("&$" ${FINALDS})}]','Set','FINALDS=${FINALDS:0:-1}')); //remove trailing &

		$ext->add($c, 's', '', new \ext_gosub('1','s','dialparties-checkblkvm'));
		self::checkBlkvm($ext);

		// FollowMe Changes:
		//
		// We need to determine if the generated dialstring can be dialed as is. This will be the case if there are no
		// or is only a single extension to dial.
		//
		// First, unset any blank fields so we know how many extensions there are to call.
		//
		// If mastermode (skipremaining == 1) was triggered then we just set the ringtime to what the primary extension
		// should ring for and let this dialstring go.
		//
		// If there is only one extension in the list, then we need to determine how long to ring it (depending on if it
		// was the primary or another extension, then let the generated dialstring ring it.
		//
		// Otherwise, we need to re-create the dialstring to be processed by our special dialplan that will ring the
		// primary extension and hold the group list for the required delay. Also - if we are in a call confirmation mode
		// then we need to reset the call confirm variables with one level of inheritance so that they remain in the new
		// channels but don't get further propogated after that. We also clear it for the remainder of this instance since
		// we are not yet triggering further actions until the next call.
		//
		// Notes: $fmgrp_primaryremoved is set to 1 if the primary has been removed from the list so we know that it was dnd-ed.
		//        this only matters in non-prim mode, where we need to know if the remaining list contains the primary extension
		//        or not.
		//
		$ext->add($c, 's', '', new \ext_gosubif('$["${RGMETHOD}"="ringallv2"]','dialparties-ringallv2,s,1'));
		self::ringAllV2($ext);

		$ext->add($c, 's', '', new \ext_gosub('1','s','dialparties-checkblkvm'));

		//dont allow inbound callers to transfer around inside the system
		$ext->add($c, 's', '', new \ext_execif('$["${DIRECTION}"="INBOUND"]','Set','DIALOPTS=${REPLACE(DIALOPTS,T,)}'));

		$ext->add($c, 's', '', new \ext_gosubif('$["${FINALDS}"!=""]','dialparties-finalbuild,s,1'));
		self::finalBuild($ext);

		// sanity check make sure dialstatus is set to something
		//
		$ext->add($c, 's', '', new \ext_execif('$["${FINALDS}"="" & "${DIALSTATUS}"!=""]','Set','DIALSTATUS=NOANSWER'));

		// Added for RVOL value set based on Rvol MODE{yes,no,never,force,dontcare}
		//we just need to set the RVOl=RVOLPARENT base on RVOL_MODE
		// get all varible needed.
		$ext->add($c, 's', '', new \ext_gosub('1','s','dialparties-setrvol'));
		self::setRvol($ext);

		$ext->add($c, 's', '', new \ext_setvar('ds','${FINALDS}'));
		$ext->add($c, 's', '', new \ext_setvar('TIMEOUT','${TIMER}'));
		$ext->add($c, 's', '', new \ext_setvar('DIALOPTS','${DIALOPTS}'));

		// EOF dialparties.agi
		$ext->add($c, 's', '', new \ext_return());
	}

	static function setRvol($ext) {
		$c = 'dialparties-setrvol';
		$ext->add($c, 's', '', new \ext_noop('RVOL_MODE is: ${RVOL_MODE} '));
		$ext->add($c, 's', '', new \ext_noop('RVOL is: ${RVOL}'));
		$ext->add($c, 's', '', new \ext_noop('RVOLPARENT is: ${RVOL_PARENT}'));
		$ext->add($c, 's', '', new \ext_gotoif('$["${DIALPLAN_EXISTS(dialparties-setrvol,${RVOL_MODE},1)}"="1"]','${RVOL_MODE},1'));
		$ext->add($c, 's', '', new \ext_return(''));

		//set RVOL=RVOLPARENT
		$ext->add($c, 'force', '', new \ext_setvar('__RVOL','${RVOL_PARENT}'));
		$ext->add($c, 'no', '', new \ext_return());

		//set RVOL=RVOLPARENT
		$ext->add($c, 'yes', '', new \ext_setvar('__RVOL','${RVOL_PARENT}'));
		$ext->add($c, 'no', '', new \ext_return());

		//should not override set again same value
		$ext->add($c, 'no', '', new \ext_setvar('__RVOL','${RVOL}'));
		$ext->add($c, 'no', '', new \ext_return());

		//should not override set again same value
		$ext->add($c, 'never', '', new \ext_setvar('__RVOL','${RVOL}'));
		$ext->add($c, 'no', '', new \ext_return());
	}

	static function finalBuild($ext) {
		$c = 'dialparties-finalbuild';
		$ext->add($c, 's', '', new \ext_gotoif('$["${RGMETHOD}"="hunt" | "${RGMETHOD}"="random" | "${RGMETHOD}"="memoryhunt" | "${RGMETHOD}"="firstavailable" | "${RGMETHOD}"="firstnotonphone"]','hunt,1','nohunt,1'));

		$ext->add($c, 'hunt', '', new \ext_setvar('FINALDS',','));
		$ext->add($c, 'hunt', '', new \ext_execif('$["${TIMER}"!=""]','Set','FINALDS=${FINALDS}${TIMER}'));
		$ext->add($c, 'hunt', '', new \ext_setvar('FINALDS','${FINALDS},${DIALOPTS}'));
		$ext->add($c, 'hunt', '', new \ext_setvar("HuntMembers",'${LOOPS}'));
		$ext->add($c, 'hunt', '', new \ext_setvar("MACRODIALGOTO_PRI","huntdial")); // dial command was at priority 20 where dialplan handles calling a ringgroup with strategy of "hunt" or "MemoryHunt"
		$ext->add($c, 'hunt', '', new \ext_return());

		$ext->add($c, 'nohunt', '', new \ext_setvar('FINALDS','${FINALDS},'));
		$ext->add($c, 'nohunt', '', new \ext_execif('$["${TIMER}"!=""]','Set','FINALDS=${FINALDS}${TIMER}'));
		$ext->add($c, 'nohunt', '', new \ext_execif('$["${TIMER}"!="" & "${USE_CONFIRMATION}"!="FALSE"]','Set','__RT=${TIMER}'));
		$ext->add($c, 'nohunt', '', new \ext_setvar('FINALDS','${FINALDS},${DIALOPTS}')); // pound to transfer, provide ringing
		$ext->add($c, 'nohunt', '', new \ext_execif('$["${SCREEN}"!=""]','Set','FINALDS=${FINALDS}p'));
		$ext->add($c, 'nohunt', '', new \ext_gotoif('$["${USE_CONFIRMATION}"!="FALSE"]','useconfirmation,1'));
		$ext->add($c, 'nohunt', 'nohuntend', new \ext_setvar("MACRODIALGOTO_PRI","normdial")); // dial command was at priority 10
		$ext->add($c, 'nohunt', '', new \ext_return());

		$ext->add($c, 'useconfirmation', '', new \ext_setvar('__RG_IDX','${RINGGROUP_INDEX}'));
		$ext->add($c, 'useconfirmation', '', new \ext_execif('$["${CIDNUM}"!=""]','Set','__CALLCONFIRMCID=${CIDNUM}','Set','__CALLCONFIRMCID=999'));
		$ext->add($c, 'useconfirmation', '', new \ext_goto('nohunt,nohuntend'));
	}

	static function ringAllV2($ext) {
		$c = 'dialparties-ringallv2'; // Context

		$ext->add($c, 's', '', new \ext_gotoif('$["${SKIPREMAINING}" = "1" | $["${EXTLISTCOUNT}"="1" & "${FMGRP_PRIMARYREMOVED}"="0"]]','timerrealprering,1'));
		$ext->add($c, 's', '', new \ext_gotoif('$["${EXTLISTCOUNT}"="1" & "${FMGRP_PRIMARYREMOVED}"="1"]','timergrptime,1'));
		$ext->add($c, 's', '', new \ext_gotoif('$["${EXTLISTCOUNT}"="1"]','timertotalprering,1')); // not sure what would trigger this ?
		$ext->add($c, 's', '', new \ext_setvar('TIMER','${FMGRP_TOTALPRERING}'));

		$ext->add($c, 's', '', new \ext_setvar('FINALDS','Local/FMPR-${HASH(EXTLIST,0)}@from-internal&Local/FMGL-'));

		$ext->add($c, 's', '', new \ext_setvar('I', '1'));
		$ext->add($c, 's', '', new \ext_while('$["${SET(workingext=${HASH(EXTLIST,${I})})}" != ""]'));
		$ext->add($c, 's', '', new \ext_gotoif('$["${workingext}"=""]','next'));
		$ext->add($c, 's', '', new \ext_setvar('FINALDS','${FINALDS}${workingext}-'));
		$ext->add($c, 's', '', new \ext_setvar('I','${MATH(${I}+1,int)}'));
		$ext->add($c, 's', '', new \ext_endwhile());

		$ext->add($c, 's', '', new \ext_execif('$["${FINALDS}" != "" & ${REGEX("-$" ${FINALDS})}]','Set','FINALDS=${FINALDS:0:-1}')); //remove trailing -
		$ext->add($c, 's', '', new \ext_setvar('FINALDS','${FINALDS}@from-internal'));
		$ext->add($c, 's', '', new \ext_setvar('_FMUNIQUE','${UNIQUEID}'));
		$ext->add($c, 's', '', new \ext_setvar('_RingGroupMethod',"ringall"));
		$ext->add($c, 's', '', new \ext_setvar('_FMPRERING','${MATH(${FMGRP_PRERING}-2,int)}'));
		$ext->add($c, 's', '', new \ext_setvar('_FMREALPRERING','${FMGRP_REALPRERING}'));
		$ext->add($c, 's', '', new \ext_setvar('_FMGRPTIME','${FMGRP_GRPTIME}'));
		$ext->add($c, 's', '', new \ext_execif('$["${RECALL_MASTERMODE}" = "ringallv2"]','Set','_FMPRIME=FALSE','Set','_FMPRIME=TRUE'));

		$ext->add($c, 's', '', new \ext_noop('FMUNIQUE: ${FMUNIQUE}, FMRERING: ${FMPRERING}, FMREALPRERING: ${FMREALPRERING}, FMGRPTIME: ${FMGRPTIME}'));

		$ext->add($c, 's', '', new \ext_gotoif('$["${USE_CONFIRMATION}"!="FALSE"]','useconfirmation,1'));
		$ext->add($c, 's', '', new \ext_return());

		$ext->add($c, 'timerrealprering', '', new \ext_setvar('TIMER','${FMGRP_REALPRERING}'));
		$ext->add($c, 'timerrealprering', '', new \ext_return());

		$ext->add($c, 'timergrptime', '', new \ext_setvar('TIMER','${FMGRP_GRPTIME}'));
		$ext->add($c, 'timergrptime', '', new \ext_return());

		$ext->add($c, 'timertotalprering', '', new \ext_setvar('TIMER','${FMGRP_TOTALPRERING}'));
		$ext->add($c, 'timertotalprering', '', new \ext_return());

		$ext->add($c, 'useconfirmation', '', new \ext_setvar('_USE_CONFIRMATION','${USE_CONFIRMATION}'));
		$ext->add($c, 'useconfirmation', '', new \ext_setvar('_RINGGROUP_INDEX','${RINGGROUP_INDEX}'));
		$ext->add($c, 'useconfirmation', '', new \ext_setvar('USE_CONFIRMATION','FALSE'));
		$ext->add($c, 'useconfirmation', '', new \ext_return());
	}

	static function checkBlkvm($ext) {
		$c = 'dialparties-checkblkvm'; // Context
		$ext->add($c, 's', '', new \ext_gotoif('$[$["${NODEST}"!="" & "${USE_CONFIRMATION}"="FALSE"] = 0]','endblkvm'));
		$ext->add($c, 's', '', new \ext_gotoif('$[$[${REGEX("auto-blkvm" ${DIALOPTS})} | ${REGEX("auto-confirm" ${DIALOPTS})} | ${REGEX("confirm" ${DIALOPTS})}] = 0]','addblkvm'));
		$ext->add($c, 's', '', new \ext_noop('NODEST: ${NODEST} blkvm enabled macro already in dialopts: ${DIALOPTS}'));
		$ext->add($c, 's', '', new \ext_goto('endblkvm'));
		$ext->add($c, 's', 'addblkvm', new \ext_setvar('DIALOPTS','${DIALOPTS}M(auto-blkvm)'));
		$ext->add($c, 's', '', new \ext_noop('NODEST: ${NODEST} adding M(auto-blkvm) to dialopts: ${DIALOPTS}'));
		$ext->add($c, 's', 'endblkvm', new \ext_return());
	}

	static function buildDSHunt($ext) {
		$c = 'dialparties-builddshunt'; // Context
		$ext->add($c, 's', '', new \ext_return());
		/*
		if ($cidnum) {
				$AGI->set_variable('CALLTRACE_HUNT',$cidnum);
			}
			// Treatment of random strategy
			if ($rgmethod == "random") {
				shuffle($extarray);	   // shuffle the array
			}
			foreach ($extarray as $k ) {
				// we loop through the original array to get the extensions in order of importance
				if (isset($ext_hunt[$k]) && $ext_hunt[$k]) {
					//If the original array is included in the extension hash then set variables
					$myhuntmember="HuntMember"."$loops";
					if (($rgmethod == "hunt") || ($rgmethod == "random") || ($rgmethod == "firstavailable") || ($rgmethod == "firstnotonphone")) { //added condition for random strategy
						$AGI->set_variable($myhuntmember,$ext_hunt[$k]);
					} elseif ($rgmethod == "memoryhunt") {
						if ($loops==0) {
							$dshunt =$ext_hunt[$k];
						} else {
							$dshunt .='&'.$ext_hunt[$k];
						}
						$AGI->set_variable($myhuntmember,$dshunt);
					}
					$loops += 1;
				}
			}
			*/
	}

	static function firstNotOnPhone($ext) {
		$c = 'dialparties-firstnotonphone'; // Context
		$ext->add($c, 's', '', new \ext_return());
		/*
				foreach($ext as $k){
			$status = is_ext_avail($k);
			if($status == 0){ // taking only the available extensions first
				$extenavail[]= $k;
				$dsarraynew[$k] = 1;
			}
		}
		debug("ONLY available extensions ".print_r($extenavail,true), 3);
	//now we should append other numbers to this array. incase noting is available !!!
		foreach($dsarray  as $key => $k){ // use dsarray to eliminate the extensions being filtered earlier
			if(!in_array($key,$extenavail)){
				$extenavail[] = $key;
				$dsarraynew[$key] = $k;
			}
		}
		unset($ext);
		unset($dsarray);
		$dsarray = $dsarraynew; // reassigning the filtered array
		$ext =  $extenavil;
		foreach($extenavail as $filtred){
			if(in_array($filtred.'#',$extarray)){
				$extarraynew[] = $filtred.'#';
			}else{
				$extarraynew[] = $filtred;
			}
		}
		$extarray = $extarraynew;
		*/
	}

	static function isExtAvail($ext) {
		$c = 'dialparties-isextavail'; // Context

		$ext->add($c, 's', '', new \ext_setvar('EXTSTATESTATUS', ''));
		$ext->add($c, 's', '', new \ext_execif('$["${AMPUSER}" = "${ARG1}"]','Set','EXTSTATE_RESULT=INUSE_ORIGINATOR','Set','EXTSTATE_RESULT=${EXTENSION_STATE(${ARG1}@ext-local)}'));
		$ext->add($c, 's', '', new \ext_gotoif('$["${DIALPLAN_EXISTS(dialparties-isextavail,${EXTSTATE_RESULT},1)}"="1"]','${EXTSTATE_RESULT},1','default,1'));

		$ext->add($c, 'NOT_INUSE', '', new \ext_setvar('EXTSTATESTATUS', '0'));
		$ext->add($c, 'NOT_INUSE', '', new \ext_goto('finish,1'));

		$ext->add($c, 'INUSE_ORIGINATOR', '', new \ext_setvar('EXTSTATESTATUS', '1'));
		$ext->add($c, 'INUSE_ORIGINATOR', '', new \ext_goto('finish,1'));

		$ext->add($c, 'INUSE', '', new \ext_setvar('EXTSTATESTATUS', '1'));
		$ext->add($c, 'INUSE', '', new \ext_goto('finish,1'));

		$ext->add($c, 'BUSY', '', new \ext_setvar('EXTSTATESTATUS', '2'));
		$ext->add($c, 'BUSY', '', new \ext_goto('finish,1'));

		$ext->add($c, 'RINGING', '', new \ext_setvar('EXTSTATESTATUS', '8'));
		$ext->add($c, 'RINGING', '', new \ext_goto('finish,1'));

		$ext->add($c, 'RINGINUSE', '', new \ext_setvar('EXTSTATESTATUS', '9'));
		$ext->add($c, 'RINGINUSE', '', new \ext_goto('finish,1'));

		$ext->add($c, 'HOLDINUSE', '', new \ext_setvar('EXTSTATESTATUS', '10'));
		$ext->add($c, 'HOLDINUSE', '', new \ext_goto('finish,1'));

		$ext->add($c, 'ONHOLD', '', new \ext_setvar('EXTSTATESTATUS', '16'));
		$ext->add($c, 'ONHOLD', '', new \ext_goto('finish,1'));

		$ext->add($c, 'UNAVAILABLE', '', new \ext_setvar('EXTSTATESTATUS', '4'));
		$ext->add($c, 'UNAVAILABLE', '', new \ext_goto('finish,1'));

		$ext->add($c, 'UNKNOWN', '', new \ext_setvar('EXTSTATESTATUS', '4'));
		$ext->add($c, 'UNKNOWN', '', new \ext_goto('finish,1'));

		$ext->add($c, 'default', '', new \ext_setvar('EXTSTATESTATUS', '4'));
		$ext->add($c, 'default', '', new \ext_goto('finish,1'));

		$ext->add($c, 'finish', '', new \ext_noop('EXTENSION_STATE: ${EXTSTATESTATUS} (${EXTSTATE_RESULT})'));
		$ext->add($c, 'finish', '', new \ext_return('${EXTSTATESTATUS}'));
	}

	static function mainLoop($ext) {
		// mastermode description:
		//
		// if mastermode is set then the first extension will be examined and mastermode will be reset so that the others
		// are left alone. If the remaining extensions are not to be tried, skipremaining will be set to 1 thus skipping them
		//
		// if cf unconditional was already detected on the primary, then mastermode will have been reset at this point
		// since that will negate the mastermode concpet.
		//
		// if dnd was set on the primary then skipremaining will already be set resulting in a completly blanked out list
		// since dnd on the primary means don't bother me on any. It will only have been set if in mastermode


		$c = 'dialparties-mainloop'; // Context
		$ext->add($c, 's', '', new \ext_setvar('I', '0'));
		$ext->add($c, 's', '', new \ext_setvar('EXTLISTCOUNT', '0'));
		$ext->add($c, 's', '', new \ext_while('$["${SET(workingext=${HASH(EXTLIST,${I})})}" != ""]'));
		$ext->add($c, 's', '', new \ext_gotoif('$["${workingext}"=""]','next'));
		$ext->add($c, 's', '', new \ext_setvar('EXTLISTCOUNT','${MATH(${EXTLISTCOUNT} + 1,int)}'));
		$ext->add($c, 's', '', new \ext_noop('Working with extension ${workingext}'));
		$ext->add($c, 's', '', new \ext_gosub('1','s','dialparties-isextavail','${workingext}'));

		// Don't bother checking these if we will be blanking the extnum anyhow
		// skip this loop since $skipremaining = 1 which is only the case in mastermode meaning the remaining code below will skip
		// this and remaining extensions
		$ext->add($c, 's', '', new \ext_gotoif('$["${SKIPREMAINING}" = "1"]','next'));
		// CWIGNORE is sent down the channel when all extensions should be treated as if they do not have
		// call waiting enabled. This is used primarily by Queue type setups (sometimes Ring Groups) when
		// you want to assure that calls go on to the next agent if the current one is on the phone instead
		// of ringing their line constantly.
		//
		$ext->add($c, 's', '', new \ext_execif('$["${CWIGNORE}" != ""]','Set','EXTHASCW=0','Set','EXTHASCW=${DB(CW/${workingext})}'));
		$ext->add($c, 's', '', new \ext_execif('$["${EXTHASCW}" = ""]','Set','EXTHASCW=0'));

		$ext->add($c, 's', '', new \ext_setvar('EXTCFB','${DB(CFB/${workingext})}'));
		$ext->add($c, 's', '', new \ext_execif('$["${EXTCFB}" != ""]','Set','EXTHASCFB=1','Set','EXTHASCFB=0'));

		$ext->add($c, 's', '', new \ext_setvar('EXTCFU','${DB(CFU/${workingext})}'));
		$ext->add($c, 's', '', new \ext_execif('$["${EXTCFU}" != ""]','Set','EXTHASCFU=1','Set','EXTHASCFU=0'));

		$ext->add($c, 's', '', new \ext_noop('extnum ${workingext} has:: cw: ${EXTHASCW}, hascfb: ${EXTHASCFB} [${EXTCFB}], hascfu: ${EXTHASCFU} [${EXTCFU}]'));

		// check if mastermode and then reset here. If mastermode, this will be the first extension so
		// the state is checked and a decision is made as to what to do. We have gotten all the cf variables
		// above. If CF unconditional was set, we never get here because we alread reset mastermode. If DND
		// were set then we never get here becasue didprimary was set
		$ext->add($c, 's', '', new \ext_gotoif('$["${MASTERMODE}"!="1"]','finishmastermodecwchecks'));
		$ext->add($c, 's', '', new \ext_setvar('MASTERMODE','0'));
		$ext->add($c, 's', '', new \ext_setvar('EXTSTATE',''));
		// process this one extension but the remaining should be skipped since there is cw and
		// the extension is occupied. This will try this extension but not the others.
		$ext->add($c, 's', '', new \ext_gotoif('$[$["${EXTHASCW}" = "1" & "${EXTSTATESTATUS}" != "0" & "${EXTSTATESTATUS}" != "4"] = 0]','checknocw'));
		$ext->add($c, 's', '', new \ext_setvar('SKIPREMAINING','1'));
		$ext->add($c, 's', '', new \ext_noop('In mastermode with cw enabled so ${workingext} will be tried and others skipped'));
		$ext->add($c, 's', '', new \ext_goto('finishmastermodecwchecks'));

		// no cw, ext is busy. So if cfb is set, it will forward there and if not, it will be
		// ignored as normal behavior. In either case, we skip the remaining numbers.
		$ext->add($c, 's', 'checknocw', new \ext_gotoif('$[$["${EXTHASCW}" = "0" & "${EXTSTATESTATUS}" != "0" & "${EXTSTATESTATUS}" != "4"] = 0]','finishmastermodecwchecks'));
		$ext->add($c, 's', '', new \ext_setvar('SKIPREMAINING','1'));
		$ext->add($c, 's', '', new \ext_noop('In mastermode with cw enabled so ${workingext}will be processed in case cfb set'));
		$ext->add($c, 's', '', new \ext_goto('finishmastermodecwchecks'));

		// All other cases should act like normal. Unavailable, not busy, ringing, etc.
		// should not be effected
		$ext->add($c, 's', 'finishmastermodecwchecks', new \ext_noop('Extension ${workingext} has ExtensionState: ${EXTSTATESTATUS}'));

		// if CF is not in use and $skipremaining is not set otherwise $extnum has been cleared and nothing to do
		//
		$ext->add($c, 's', '', new \ext_gosub('1','s','dialparties-checkcfextension'));
		self::checkCFExtension($ext);

		$ext->add($c, 's', '', new \ext_noop('Now working with: ${workingext}'));

		$ext->add($c, 's', '', new \ext_gotoif('$["${workingext}" = ""]','next'));
		// Still got an extension to be called?
		// check if we already have a dial string for this extension
		// if so, ignore it as it's pointless ringing it twice !
		$ext->add($c, 's', '', new \ext_setvar('REALEXT', '${REPLACE(workingext,#,)}'));
		$ext->add($c, 's', '', new \ext_gotoif('$["${HASH(DSARRAY,${REALEXT})}" != ""]','inhash'));
		$ext->add($c, 's', '', new \ext_setvar('HASH(DSARRAY,${REALEXT})', '1')); // could be dial string i suppose but currently only using for duplicate check
		$ext->add($c, 's', '', new \ext_setvar('HASH(DSORDEREDARRAY,${I})', '${REALEXT}'));
		$ext->add($c, 's', '', new \ext_gosub('1','s','dialparties-getdialstring'));
		self::getDialString($ext);

		$ext->add($c, 's', '', new \ext_gotoif('$["${DIALSTRING}" = ""]','updatecallerid'));
		$ext->add($c, 's', '', new \ext_setvar('FINALDS','${FINALDS}${DIALSTRING}'));
		$ext->add($c, 's', 'updatecallerid', new \ext_noop('TODO: CALL TRACE'));
		/* TODO: Add Caller ID Call Trace Application
		// Update Caller ID for calltrace application
			if ((substr($k,-1)!='#') && (($rgmethod != "hunt") && ($rgmethod != "random") && ($rgmethod != "memoryhunt") && ($rgmethod != "firstavailable") && ($rgmethod != "firstnotonphone")) ) { //added condition for random strategy
				if ( isset($cidnum) && is_numeric($cidnum) ) {
					$rc = $AGI->database_put('CALLTRACE', $k, $cidnum);
					if ($rc['result'] == 1) {
						debug("dbset CALLTRACE/$k to $cidnum", 3);
					} else {
						debug("Failed to DbSet CALLTRACE/$k to $cidnum ({$rc['result']})", 1);
					}
				} else {
					// We don't care about retval, this key may not exist
					$AGI->database_del('CALLTRACE', $k);
					debug("DbDel CALLTRACE/$k - Caller ID is not defined", 3);
				}
			} else {
				$ext_hunt[$k]=$extds; // Need to have the extension HASH set with technology for hunt group ring
			}
		*/
		$ext->add($c, 's', '', new \ext_goto('next'));

		$ext->add($c, 's', 'inhash', new \ext_noop('${REALEXT} already in the dialstring, ignoring duplicate'));
		$ext->add($c, 's', 'next', new \ext_setvar('I','${MATH(${I}+1,int)}'));
		$ext->add($c, 's', '', new \ext_endwhile(''));
		$ext->add($c, 's', 'exit', new \ext_return());
	}

	static function getDialString($ext) {
		$c = 'dialparties-getdialstring'; // Context
		$ext->add($c, 's', '', new \ext_setvar('DIALSTRING',''));
		$ext->add($c, 's', '', new \ext_gotoif('$[$[${REGEX("#" ${workingext})}] = 0]','internal,1'));
		// "#" used to identify external numbers in forwards and callgroups
		// If using call confirmation, need to put the # back into the new dialstring
		// we then place all external calls (denoted with a # at the end) through
		// the [grps] extension for the RINGGROUP_INDEX that was called. This
		// triggers the call confirmation macro along with the required messages
		// that were set.
		//
		$ext->add($c, 's', '', new \ext_setvar('workingext', '${REPLACE(workingext,#,)}'));
		$ext->add($c, 's', '', new \ext_execif('$["${USE_CONFIRMATION}"="FALSE"]','Set','DIALSTRING=Local/${workingext}@from-internal/n','Set','DIALSTRING=Local/RG-${RINGGROUP_INDEX}*-${workingext}#@from-internal'));
		$ext->add($c, 's', '', new \ext_noop('Built External dialstring component for ${workingext}: ${DIALSTRING}'));
		$ext->add($c, 's', '', new \ext_return());

		$ext->add($c, 'internal', '', new \ext_setvar('DEVICES','${DB(AMPUSER/${workingext}/device)}'));

		$ext->add($c, 'internal','', new \ext_while('$["${SET(DEVICE=${SHIFT(DEVICES,&)})}" != ""]'));
		$ext->add($c, 'internal', '', new \ext_noop('Working with device ${DEVICE}'));

		// Check to see if this is a PJSIP device. If so, grab
		// the correct dial string from PJSIP_DIAL_CONTACTS.

		$ext->add($c, 'internal', '', new \ext_gotoif('$["${USE_CONFIRMATION}"!="FALSE"]','useconfirmation'));
		$ext->add($c, 'internal', '', new \ext_setvar('DEVICE_DS','${DB(DEVICE/${DEVICE}/dial)}'));

		$ext->add($c, 'internal', '', new \ext_gotoif('$[${REGEX("^PJSIP" ${DEVICE_DS})}]','pjsip','setds'));

		$ext->add($c, 'internal', 'pjsip', new \ext_noop('Discovered PJSIP Endpoint ${DEVICE_DS}'));
		$ext->add($c, 'internal', '', new \ext_setvar('DEVICE_DS','${PJSIP_DIAL_CONTACTS(${DEVICE_DS:6})}'));
		$ext->add($c, 'internal', '', new \ext_gotoif('$["${DEVICE_DS}"!=""]','foundcontacts','nocontacts'));
		$ext->add($c, 'internal', 'foundcontacts', new \ext_noop('Discovered PJSIP Endpoint ${DEVICE_DS}'));
		$ext->add($c, 'internal', '', new \ext_goto('setds'));
		$ext->add($c, 'internal', 'nocontacts', new \ext_noop('Discovered PJSIP Endpoint ${DEVICE_DS}'));
		$ext->add($c, 'internal', '', new \ext_goto('next'));

		$ext->add($c, 'internal', 'setds', new \ext_setvar('DIALSTRING','${DIALSTRING}${DEVICE_DS}&'));
		$ext->add($c, 'internal', '', new \ext_goto('next'));

		$ext->add($c, 'internal', 'useconfirmation', new \ext_setvar('DIALSTRING','Local/LC-${DEVICE}@from-internal&'));
		$ext->add($c, 'internal', '', new \ext_goto('next'));

		$ext->add($c, 'internal','next', new \ext_endwhile(''));
		$ext->add($c, 'internal', '', new \ext_return());
	}

	static function checkCFExtension($ext) {
		$c = 'dialparties-checkcfextension'; // Context
		$ext->add($c, 's', '', new \ext_gotoif('$[${REGEX("#$" ${workingext})}]','return'));
		// CW is not in use or CFB is in use on this extension, then we need to check!
		$ext->add($c, 's', '', new \ext_gotoif('$["${EXTHASCW}"="0" | "${EXTHASCFB}"="1" | "${EXTHASCFU}"="1"]','check1,1'));
		$ext->add($c, 's', '', new \ext_gotoif('$["${RGMETHOD}"="none" | "${EXTHASCW}"="1" | "${cwinusebusy}"!=""]','check2,1'));
		// get ExtensionState: 0-idle; 1-busy; 4-unavail; 8-ringing <--- these are unconfirmed
		$ext->add($c, 's', '', new \ext_gotoif('$["${EXTHASCW}"="1" | "${RGMETHOD}"="firstnotonphone"]','check3,1'));
		$ext->add($c, 's', '', new \ext_gotoif('$["${RGMETHOD}"="firstavailable"]','check4,1'));
		$ext->add($c, 's', 'return', new \ext_return());

		// Ext has CFU and is Unavailable
		$ext->add($c, 'check1', '', new \ext_gotoif('$["${EXTHASCFU}"="1" & "${EXTSTATESTATUS}" != "4"]','check1-1,1'));
		$ext->add($c, 'check1', '', new \ext_gotoif('$["${EXTHASCW}"="0" & "${EXTHASCFB}"="1"]','check1-2,1'));
		// -1 means couldn't read status usually due to missing HINT
		$ext->add($c, 'check1', '', new \ext_gotoif('$["${EXTSTATESTATUS}" < "0"]','check1-3,1'));
		$ext->add($c, 'check1', '', new \ext_goto('s,return'));

		// If part of a ring group, then just do what CF does, otherwise needs to
		// drop back to dialplan with NOANSWER

		//
		// If cfignore is set, then we don't honor any CF settings
		//
		$ext->add($c, 'check1-1', '', new \ext_gotoif('$["${RGMETHOD}"!="" & "${RGMETHOD}" != "none" & "${CFIGNORE}"=""]','check1-1-1,1'));
		$ext->add($c, 'check1-1', '', new \ext_noop('Extension $extnum has call forward on no answer set and is unavailable'));
		$ext->add($c, 'check1-1', '', new \ext_setvar('DIALSTATUS','NOANSWER'));
		$ext->add($c, 'check1-1', '', new \ext_setvar('workingext',''));
		$ext->add($c, 'check1-1', '', new \ext_goto('s,return'));


		$ext->add($c, 'check1-1-1', '', new \ext_noop('Extension $extnum has call forward on no answer set and is unavailable and is part of a Ring Group forwarding to $extcfu'));
		$ext->add($c, 'check1-1-1', '', new \ext_setvar('workingext','${workingext}#'));# same method as the normal cf, i.e. send to Local
		$ext->add($c, 'check1-1-1', '', new \ext_goto('s,return'));


		$ext->add($c, 'check1-2', '', new \ext_noop('Checking CW and CFB status for extension ${workingext}'));

		$ext->add($c, 'check1-2', '', new \ext_gotoif('$["${EXTSTATESTATUS}">"0" & "${EXTSTATESTATUS}" != "4"]','check1-2-1,1'));
		$ext->add($c, 'check1-2', '', new \ext_gotoif('$["${EXTSTATESTATUS}"="4"]','check1-2-2,1'));
		$ext->add($c, 'check1-2', '', new \ext_goto('s,return'));

		$ext->add($c, 'check1-2-1','', new \ext_noop('Extension ${workingext} is not available to be called'));
		// extension in use
		// don't honor any CF settings when $cfignore is set
		//
		$ext->add($c, 'check1-2-1', '', new \ext_gotoif('$["${EXTHASCFB}"="1" & "${CFIGNORE}"="" & "${EXTHASCW}"="0"]','check1-2-1-1,1'));
		// CW not in use
		$ext->add($c, 'check1-2-1', '', new \ext_gotoif('$["${EXTHASCW}"="0"]','check1-2-1-2,1'));
		$ext->add($c, 'check1-2-1','', new \ext_noop('Extension ${workingext} has call waiting enabled'));
		$ext->add($c, 'check1-2-1', '', new \ext_goto('s,return'));

		$ext->add($c, 'check1-2-1-1', '', new \ext_gotoif('$["${RGMETHOD}"="0"]','check1-2-1-1-1,1'));
		$ext->add($c, 'check1-2-1-1','', new \ext_noop('Extension ${workingext} has call forward on busy set to ${EXTCFB}'));
		$ext->add($c, 'check1-2-1-1', '', new \ext_setvar('workingext','${EXTCFB}#')); //# same method as the normal cf, i.e. send to Local
		$ext->add($c, 'check1-2-1-1', '', new \ext_goto('s,return'));

		$ext->add($c, 'check1-2-1-1-1','', new \ext_noop('Extension ${workingext} has call forward on busy set to ${EXTCFB}, dropping to macro-dial'));
		$ext->add($c, 'check1-2-1-1-1', '', new \ext_setvar('workingext',''));
		$ext->add($c, 'check1-2-1-1-1', '', new \ext_setvar('DIALSTATUS','BUSY'));
		$ext->add($c, 'check1-2-1-1-1', '', new \ext_setvar('__DIVERSION_REASON','user-busy'));
		$ext->add($c, 'check1-2-1-1-1', '', new \ext_goto('s,return'));

		$ext->add($c, 'check1-2-1-2','', new \ext_noop('Extension ${workingext} has call waiting disabled'));
		$ext->add($c, 'check1-2-1-2', '', new \ext_setvar('workingext',''));
		$ext->add($c, 'check1-2-1-2', '', new \ext_setvar('DIALSTATUS','BUSY'));
		$ext->add($c, 'check1-2-1-2', '', new \ext_setvar('__DIVERSION_REASON','user-busy'));
		$ext->add($c, 'check1-2-1-2', '', new \ext_goto('s,return'));

		$ext->add($c, 'check1-2-2', '', new \ext_noop('Extension ${workingext} is not available to be called'));
		$ext->add($c, 'check1-2-2', '', new \ext_setvar('workingext',''));
		$ext->add($c, 'check1-2-2', '', new \ext_goto('s,return'));

		$ext->add($c, 'check1-3', '', new \ext_noop('ExtensionState for ${workingext} could not be read...assuming ok'));
		$ext->add($c, 'check1-3', '', new \ext_goto('s,return'));

		$ext->add($c, 'check2', '', new \ext_gotoif('$["${EXTSTATESTATUS}">"0" & "${EXTSTATESTATUS}" != "4"]','check2-1,1'));
		$ext->add($c, 'check2', '', new \ext_goto('s,return'));

		$ext->add($c, 'check2-1', '', new \ext_setvar('DIALSTATUS_CW','BUSY'));
		$ext->add($c, 'check2-1', '', new \ext_noop('Extension ${workingext} has call waiting enabled with state: ${EXTSTATESTATUS}'));
		$ext->add($c, 'check2-1', '', new \ext_goto('s,return'));

		$ext->add($c, 'check3', '', new \ext_noop('Extension ${workingext} has ExtensionState: ${EXTSTATESTATUS}'));
		// CW in use - but blocked for hunt
		// treat as on phone if already ringing, on hold, etc.
		$ext->add($c, 'check3', '', new \ext_gotoif('$["${EXTSTATESTATUS}" != "0"]','check3-1,1'));
		$ext->add($c, 'check3', '', new \ext_goto('s,return'));

		$ext->add($c, 'check3-1', '', new \ext_noop('Extension ${workingext} has call waiting enabled but blocked for hunt'));
		$ext->add($c, 'check3-1', '', new \ext_setvar('workingext',''));
		$ext->add($c, 'check3-1', '', new \ext_setvar('DIALSTATUS','BUSY'));
		$ext->add($c, 'check3-1', '', new \ext_goto('s,return'));

		$ext->add($c, 'check4', '', new \ext_noop('Extension ${workingext} has ExtensionState: ${EXTSTATESTATUS}'));
		$ext->add($c, 'check4', '', new \ext_gotoif('$["${EXTSTATESTATUS}" = "4"]','check4-1,1'));
		$ext->add($c, 'check4', '', new \ext_goto('s,return'));

		$ext->add($c, 'check4-1', '', new \ext_noop('Extension ${workingext} is unavailable so dont include in firstavailable hunt'));
		$ext->add($c, 'check4-1', '', new \ext_setvar('workingext',''));
		$ext->add($c, 'check4-1', '', new \ext_setvar('DIALSTATUS','BUSY'));
		$ext->add($c, 'check4-1', '', new \ext_goto('s,return'));
	}

	static function checkCFExtensions($ext) {
		$c = 'dialparties-checkcfextensions'; // Context
		$ext->add($c, 's', '', new \ext_setvar('I', '0'));
		$ext->add($c, 's', '', new \ext_while('$["${SET(workingext=${HASH(EXTLIST,${I})})}" != ""]'));
		$ext->add($c, 's', '', new \ext_gotoif('$["${workingext}"=""]','next'));
		$ext->add($c, 's', '', new \ext_noop('Working with extension ${workingext}'));

		// no point in doing if cf is enabled
		$ext->add($c, 's', '', new \ext_gotoif('$[${REGEX("#$" ${workingext})}]','skipcf'));

		$ext->add($c, 's', '', new \ext_setvar('CF','${DB(CF/${workingext})}'));
		$ext->add($c, 's', '', new \ext_gotoif('$["${CF}"=""]','nocf'));
		// Check for call forwarding first
		// If call forward is enabled, we use chan_local
		// Hacky. We should be using an associative array, shouldn't we?
		// More hacks: ignore CF if caller is the extension this extension is forwarded to.
		$ext->add($c, 's', '', new \ext_execif('$["${AMPUSER}" = ""]','Set','TMPAMPUSER=${CIDNUM}','Set','TMPAMPUSER=${AMPUSER}'));
		$ext->add($c, 's', '', new \ext_gotoif('$["${AMPUSER}" = "${CF}" | ${REALCALLERIDNUM} = ${CF} | "${CUT(BLINDTRANSFER,/${CF}-)}" != ""]','ignored'));
		$ext->add($c, 's', '', new \ext_gotoif('$["${CFIGNORE}" != ""]','cfignore'));
		// append a hash sign so we can send out on chan_local below unless cfingore is set
		$ext->add($c, 's', '', new \ext_setvar('HASH(EXTLIST,${I})','${CF}#'));
		$ext->add($c, 's', '', new \ext_noop('Extension ${workingext} has call forward set to ${CF}'));
		// This only really needs to be set if we are setting Diversion Headers, but it's not worth the hassle of
		// checking the amportal.conf settings here and there is no harm done in setting it other than minor overhead
		//
		// Set DIVERSION_REASON only when rmethod is none, otherwise it's a ringgroup/findmefollow and
		// if we want to set diversion headers they should be set by the group.
		//
		// For CF timer, we change the timer value if rgmethod is none meaning a single extension is being called.
		// CFB and CFU are handled in macro-exten-vm for single extensions. (And this script is being phased out in
		// favor of macro-dial-one for single extensions when function EXTENSION_STATE is available).
		//
		$ext->add($c, 's', '', new \ext_gotoif('$["${RGMETHOD}" != "none"]','next'));
		$ext->add($c, 's', '', new \ext_setvar('__DIVERSION_REASON','unconditional'));
		$ext->add($c, 's', '', new \ext_setvar('CFRT','${DB(AMPUSER/${workingext}/cfringtimer)}'));
		$ext->add($c, 's', '', new \ext_gotoif('$["${CFRT}" = ""]','next'));
		$ext->add($c, 's', '', new \ext_execif('$["${CFRT}" < "0"]','Set','CFRT='));
		$ext->add($c, 's', '', new \ext_noop('Ring timer changed to CF ringtimer value of ${CFRT}'));
		// if this is the primary extension and CF enabled, then cancel mastermode
		// whether it is or not, no need to check.
		//
		$ext->add($c, 's', '', new \ext_gotoif('$["${workingext}" != "${PRIMARY_EXT}"]','next'));
		$ext->add($c, 's', '', new \ext_setvar('MASTERMODE','0'));
		// not relevant if not mastermode, clear it so dnd doesn't propagate, and other
		$ext->add($c, 's', '', new \ext_setvar('PR_DIALSTATUS',''));
		$ext->add($c, 's', '', new \ext_goto('next'));

		$ext->add($c, 's', 'cfignore', new \ext_setvar('HASH(EXTLIST,${I})',''));
		$ext->add($c, 's', '', new \ext_noop('Extension ${workingext} has call forward set to ${CF}'));
		$ext->add($c, 's', '', new \ext_setvar('DIALSTATUS','NOANSWER'));
		$ext->add($c, 's', '', new \ext_gotoif('$["${workingext}" = "${CF}"]','loop'));
		$ext->add($c, 's', '', new \ext_goto('next'));

		$ext->add($c, 's', 'loop', new \ext_noop('Loop detected, extension ${workingext} cf is ignored'));
		$ext->add($c, 's', '', new \ext_goto('next'));

		$ext->add($c, 's', 'ignored', new \ext_noop('Extension ${workingext} cf is ignored'));
		$ext->add($c, 's', '', new \ext_goto('next'));

		$ext->add($c, 's', 'skipcf', new \ext_noop('Extension ${workingext} appears to be CF, skipping checks'));
		$ext->add($c, 's', '', new \ext_goto('next'));

		$ext->add($c, 's', 'nocf', new \ext_noop('Extension ${workingext} cf is disabled'));
		$ext->add($c, 's', 'next', new \ext_setvar('I','${MATH(${I}+1,int)}'));
		$ext->add($c, 's', '', new \ext_endwhile(''));

		$ext->add($c, 's', '', new \ext_return());
	}

	static function checkDNDExtensions($ext) {
		$c = 'dialparties-checkdndextensions'; // Context
		$ext->add($c, 's', '', new \ext_setvar('I', '0'));
		$ext->add($c, 's', '', new \ext_while('$["${SET(workingext=${HASH(EXTLIST,${I})})}" != ""]'));
		$ext->add($c, 's', '', new \ext_gotoif('$["${workingext}"=""]','next'));
		$ext->add($c, 's', '', new \ext_noop('Working with extension ${workingext}'));
		// no point in doing if cf is enabled
		$ext->add($c, 's', '', new \ext_gotoif('$[${REGEX("#$" ${workingext})}]','skipdnd'));
		$ext->add($c, 's', '', new \ext_setvar('DND','${DB(DND/${workingext})}'));
		$ext->add($c, 's', '', new \ext_gotoif('$["${DND}"!="" | "${PR_DIALSTATUS}"="BUSY"]','dnd','nodnd'));

		$ext->add($c, 's', 'dnd', new \ext_noop('Extension ${workingext} has do not disturb enabled, or followme pre-ring returned busy'));
		$ext->add($c, 's', '', new \ext_setvar('HASH(EXTLIST,${I})',''));
		$ext->add($c, 's', '', new \ext_setvar('DIALSTATUS','BUSY'));
		// if this is primary set skipremaining and figure out if needed below
		//
		$ext->add($c, 's', '', new \ext_gotoif('$["${PRIMARY_EXT}"="${workingext}" & "${MASTERMODE}"="1"]','skipremaining','primarycheck'));
		$ext->add($c, 's', 'skipremaining', new \ext_setvar('SKIPREMAINING', '1'));
		$ext->add($c, 's', '', new \ext_noop('Primary extension is DND, so if mastermode, all should be dnd'));
		$ext->add($c, 's', '', new \ext_goto('next'));

		$ext->add($c, 's', 'primarycheck', new \ext_gotoif('$["${PRIMARY_EXT}"!="${workingext}"]','next'));
		$ext->add($c, 's', '', new \ext_setvar('FMGRP_PRIMARYREMOVED', '1'));
		$ext->add($c, 's', '', new \ext_goto('next'));

		$ext->add($c, 's', 'skipdnd', new \ext_noop('Extension ${workingext} appears to be CF, skipping checks'));
		$ext->add($c, 's', '', new \ext_goto('next'));

		$ext->add($c, 's', 'nodnd', new \ext_noop('Extension ${workingext} do not disturb is disabled'));
		$ext->add($c, 's', 'next', new \ext_setvar('I','${MATH(${I}+1,int)}'));
		$ext->add($c, 's', '', new \ext_endwhile(''));
		$ext->add($c, 's', 'exit', new \ext_return());
	}

	static function callScreenCheck($ext) {
		$c = 'dialparties-callscreencheck'; // Context
		$ext->add($c, 's', '', new \ext_setvar('SCREEN_CALL','${DB(AMPUSER/${PRIMARY_EXT}/screen)}'));
		$ext->add($c, 's', '', new \ext_gotoif('$["${SCREEN_CALL}"!=""]','return'));
		// This can't go in the dialplan because macro-dial can get called multiple times
		// Do a security check, allow only alphanumeric callerid numbers, otherwise code could be injected in a cidnum field
		// that could result in an arbitrary command being executed in this remove operation.
		$ext->add($c, 's', '', new \ext_gotoif('$["${SCREEN_CALL}"="nomemory" & "${CIDNUM}"!="" & ${REGEX("[^ a-zA-Z\d]" ${CIDNUM})}]','return'));
		$ext->add($c, 's', '', new \ext_system('rm -f ${ASTVARLIBDIR}/sounds/priv-callerintros/${CIDNUM}.*'));
		$ext->add($c, 's', '', new \ext_setvar('SCREEN','TRUE'));
		$ext->add($c, 's', '', new \ext_setvar('__SCREEN','${SCREEN_CALL}'));
		$ext->add($c, 's', '', new \ext_setvar('__SCREEN_EXTEN','${PRIMARY_EXT}'));
		$ext->add($c, 's', '', new \ext_noop('Extension ${PRIMARY_EXT} has call screening on'));
		$ext->add($c, 's', '', new \ext_goto('exit'));
		$ext->add($c, 's', 'return', new \ext_noop('Extension ${PRIMARY_EXT} has call screening off'));
		$ext->add($c, 's', 'exit', new \ext_return());
	}

	static function preRingPrep($ext) {
		$c = 'dialparties-preringprep'; // Context
		$ext->add($c, 's', '', new \ext_setvar('FMGRP_PRIMARYREMOVED','0'));
		$ext->add($c, 's', '', new \ext_setvar('FMGRP_PRERING','${DB(AMPUSER/${FMGRP}/followme/prering)}'));
		$ext->add($c, 's', '', new \ext_execif('$["${FMGRP_PRERING}" <= "1"]','Set','FMGRP_PRERING=2'));

		$ext->add($c, 's', '', new \ext_setvar('FMGRP_GRPTIME','${DB(AMPUSER/${FMGRP}/followme/grptime)}'));
		$ext->add($c, 's', '', new \ext_noop('got fmgrp_prering: ${FMGRP_PRERING}, fmgrp_grptime: ${FMGRP_GRPTIME}'));

		$ext->add($c, 's', '', new \ext_setvar('FMGRP_TOTALPRERING','${MATH(${FMGRP_GRPTIME} + ${FMGRP_PRERING},int)}'));
		$ext->add($c, 's', '', new \ext_noop('fmgrp_totalprering: ${FMGRP_TOTALPRERING}'));

		//need to add fmgrp to begining of the hash if its in the hash
		$ext->add($c, 's', '', new \ext_setvar('FMGRP_REALPRERING','${FMGRP_TOTALPRERING}'));
		$ext->add($c, 's', '', new \ext_setvar('I', '1'));
		$ext->add($c, 's', '', new \ext_setvar('EXTLISTKEYS', '${HASHKEYS(EXTLIST)}'));
		$ext->add($c, 's', '', new \ext_while('$["${SET(extkey=${SHIFT(EXTLISTKEYS)})}" != ""]'));
		$ext->add($c, 's', '', new \ext_setvar('extval', '${HASH(EXTLIST,${extkey})}'));
		$ext->add($c, 's', '', new \ext_gotoif('$["${extval}"="${FMGRP}"]','found'));
		$ext->add($c, 's', '', new \ext_setvar('HASH(NEWEXTLIST,${I})','${extval}'));
		$ext->add($c, 's', '', new \ext_setvar('I','${MATH(${I}+1,int)}'));
		$ext->add($c, 's', '', new \ext_goto('next'));
		$ext->add($c, 's', 'found', new \ext_noop('found extension in pre-ring and array'));
		$ext->add($c, 's', '', new \ext_setvar('FMGRP_REALPRERING','${FMGRP_PRERING}'));
		$ext->add($c, 's', 'next', new \ext_endwhile(''));

		$ext->add($c, 's', '', new \ext_setvar('HASH(NEWEXTLIST,0)','${FMGRP}'));

		$ext->add($c, 's', '', new \ext_setvar('EXTLIST',''));
		$ext->add($c, 's', '', new \ext_setvar('EXTLISTKEYS', '${HASHKEYS(NEWEXTLIST)}'));
		$ext->add($c, 's', '', new \ext_while('$["${SET(extkey=${SHIFT(EXTLISTKEYS)})}" != ""]'));
		$ext->add($c, 's', '', new \ext_setvar('HASH(EXTLIST,${extkey})','${HASH(NEWEXTLIST,${extkey})}'));
		$ext->add($c, 's', '', new \ext_endwhile(''));
		$ext->add($c, 's', '', new \ext_setvar('NEWEXTLIST',''));

		$ext->add($c, 's', '', new \ext_noop('ringallv2 ring times: REALPRERING: ${FMGRP_REALPRERING}, PRERING: ${FMGRP_PRERING}'));
		$ext->add($c, 's', '', new \ext_return());
	}

	static function invalidRingMethod($ext) {
		$c = 'dialparties-invalidringmethod'; // Context
		$ext->add($c, 's', '', new \ext_noop('Unsupported RingMethod with Confirm Calls is set to True: ${RGMETHOD} resetting to ringall'));
		$ext->add($c, 's', '', new \ext_setvar('RGMETHOD','ringall'));
		$ext->add($c, 's', '', new \ext_return());
	}

	static function setModes($ext) {
		$c = 'dialparties-setmodes'; // Context
		$ext->add($c, 'ringall-prim', '', new \ext_setvar('RGMETHOD','ringall'));
		$ext->add($c, 'ringall-prim', '', new \ext_setvar('MASTERMODE','1'));
		$ext->add($c, 'ringall-prim', '', new \ext_return());

		$ext->add($c, 'hunt-prim', '', new \ext_setvar('RGMETHOD','hunt'));
		$ext->add($c, 'hunt-prim', '', new \ext_setvar('MASTERMODE','1'));
		$ext->add($c, 'hunt-prim', '', new \ext_return());

		$ext->add($c, 'memoryhunt-prim', '', new \ext_setvar('RGMETHOD','memoryhunt'));
		$ext->add($c, 'memoryhunt-prim', '', new \ext_setvar('MASTERMODE','1'));
		$ext->add($c, 'memoryhunt-prim', '', new \ext_return());

		$ext->add($c, 'ringallv2-prim', '', new \ext_setvar('RGMETHOD','ringallv2'));
		$ext->add($c, 'ringallv2-prim', '', new \ext_setvar('MASTERMODE','1'));
		$ext->add($c, 'ringallv2-prim', '', new \ext_return());

		$ext->add($c, 'default', '', new \ext_setvar('PR_DIALSTATUS','')); // not relevant if not mastermode, clear it so dnd doesn't propagate, and other
		$ext->add($c, 'default', '', new \ext_setvar('MASTERMODE','0'));
		$ext->add($c, 'default', '', new \ext_return());
	}

	static function setHeader($ext) {
		$c = 'dialparties-setheader'; // Context
		$ext->add($c, 's', '', new \ext_execif('$["${SIPADDHEADER}"!="" | "${ALERTINFO_SET_FLAG}"="1"]','Return'));
		$ext->add($c, 's', '', new \ext_setvar('HKEY','${CUT(SIPADDHEADER,:,1)}'));
		$ext->add($c, 's', '', new \ext_execif('$["${HKEY}"="Alert-Info" & "${ALERTINFO_SET_FLAG}"="1"]','Return'));
		$ext->add($c, 's', '', new \ext_setvar('HVAL','${CUT(SIPADDHEADER,:,2)}'));
		$ext->add($c, 's', '', new \ext_setvar('HASH(__SIPHEADERS,${HKEY})','${HVAL}'));
		$ext->add($c, 's', '', new \ext_return());
	}

	static function alertInfo($ext) {
		$c = 'dialparties-alertinfo'; // Context
		$ext->add($c, 's', '', new \ext_setvar('ALERTINFO_SET_FLAG','1'));
		$ext->add($c, 's', '', new \ext_noop('Setting Alert-Info:  ${ALERTINFO}'));
		$ext->add($c, 's', '', new \ext_execif('$["${RVOL}"!=""]','Set','ALERTINFO=${ALERTINFO}\;volume=${RVOL}','Set','ALERTINFO=${ALERTINFO}'));
		$ext->add($c, 's', '', new \ext_setvar('HASH(__SIPHEADERS,Alert-Info)','${ALERTINFO}'));
		$ext->add($c, 's', '', new \ext_return());
	}

	static function queueWait($ext) {
		$c = 'dialparties-queuewait'; // Context

		$ext->add($c, 's', '', new \ext_setvar('ELAPSED', '${ROUND(${MATH(${EPOCH}-${QUEUEWAIT},int)}))}'));
		$ext->add($c, 's', '', new \ext_gotoif('$["${SAVEDCIDNAME}"!=""]','skipsavedcidname'));

		$ext->add($c, 's', '', new \ext_setvar('__SAVEDCIDNAME','${CIDNAME}'));
		$ext->add($c, 's', '', new \ext_setvar('CALLERID(name)','M:${ELAPSED}:${CIDNAME}'));

		$ext->add($c, 's', 'skipsavedcidname', new \ext_setvar('CALLERID(name)','M:${ELAPSED}:${SAVEDCIDNAME}'));

		$ext->add($c, 's', '', new \ext_return());
	}
}
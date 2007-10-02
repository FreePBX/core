<?php

// The destinations this module provides
// returns a associative arrays with keys 'destination' and 'description'
function core_destinations() {
	//static destinations
	$extens = array();
	$category = 'Terminate Call';
	$extens[] = array('destination' => 'app-blackhole,hangup,1', 'description' => 'Hangup', 'category' => $category);
	$extens[] = array('destination' => 'app-blackhole,congestion,1', 'description' => 'Congestion', 'category' => $category);
	$extens[] = array('destination' => 'app-blackhole,busy,1', 'description' => 'Busy', 'category' => $category);
	$extens[] = array('destination' => 'app-blackhole,zapateller,1', 'description' => 'Play SIT Tone (Zapateller)', 'category' => $category);
	$extens[] = array('destination' => 'app-blackhole,musiconhold,1', 'description' => 'Put caller on hold forever', 'category' => $category);
	
	//get the list of meetmes
	$results = core_users_list();
	
	if (isset($results) && function_exists('voicemail_getVoicemail')) {
		//get voicemail
		$uservm = voicemail_getVoicemail();
		$vmcontexts = array_keys($uservm);
		foreach ($results as $thisext) {
			$extnum = $thisext[0];
			// search vm contexts for this extensions mailbox
			foreach ($vmcontexts as $vmcontext) {
				if(isset($uservm[$vmcontext][$extnum])){
					//$vmname = $uservm[$vmcontext][$extnum]['name'];
					//$vmboxes[$extnum] = array($extnum, '"' . $vmname . '" <' . $extnum . '>');
					$vmboxes[$extnum] = true;
				}
			}
		}
	}
	
	// return an associative array with destination and description
	// core provides both users and voicemail boxes as destinations
	if (isset($results)) {
		foreach($results as $result) {
			$extens[] = array('destination' => 'from-did-direct,'.$result['0'].',1', 'description' => ' <'.$result['0'].'> '.$result['1'], 'category' => 'Extensions');
			if(isset($vmboxes[$result['0']])) {
				$extens[] = array('destination' => 'ext-local,vmb'.$result['0'].',1', 'description' => '<'.$result[0].'> '.$result[1].' (busy)', 'category' => 'Voicemail');
				$extens[] = array('destination' => 'ext-local,vmu'.$result['0'].',1', 'description' => '<'.$result[0].'> '.$result[1].' (unavail)', 'category' => 'Voicemail');
				$extens[] = array('destination' => 'ext-local,vms'.$result['0'].',1', 'description' => '<'.$result[0].'> '.$result[1].' (no-msg)', 'category' => 'Voicemail');
			}
		}
	}
	
	if (isset($extens))
		return $extens;
	else
		return null;

}

/* 	Generates dialplan for "core" components (extensions & inbound routing)
	We call this with retrieve_conf
*/
function core_get_config($engine) {
	global $ext;  // is this the best way to pass this?
	global $version;  // this is not the best way to pass this, this should be passetd together with $engine
	global $amp_conf;

	$modulename = "core";
	
	switch($engine) {
		case "asterisk":
			// FeatureCodes
			$fcc = new featurecode($modulename, 'userlogon');
			$fc_userlogon = $fcc->getCodeActive();
			unset($fcc);

			$fcc = new featurecode($modulename, 'userlogoff');
			$fc_userlogoff = $fcc->getCodeActive();
			unset($fcc);

			$fcc = new featurecode($modulename, 'zapbarge');
			$fc_zapbarge = $fcc->getCodeActive();
			unset($fcc);

			$fcc = new featurecode($modulename, 'chanspy');
			$fc_chanspy = $fcc->getCodeActive();
			unset($fcc);

			$fcc = new featurecode($modulename, 'simu_pstn');
			$fc_simu_pstn = $fcc->getCodeActive();
			unset($fcc);

			$fcc = new featurecode($modulename, 'simu_fax');
			$fc_simu_fax = $fcc->getCodeActive();
			unset($fcc);

			$fcc = new featurecode($modulename, 'pickup');
			$fc_pickup = $fcc->getCodeActive();
			unset($fcc);

			// Log on / off -- all in one context
			if ($fc_userlogoff != '' || $fc_userlogon != '') {
				$ext->addInclude('from-internal-additional', 'app-userlogonoff'); // Add the include from from-internal
				
				if ($fc_userlogoff != '') {
					$ext->add('app-userlogonoff', $fc_userlogoff, '', new ext_macro('user-logoff'));
					$ext->add('app-userlogonoff', $fc_userlogoff, '', new ext_hangup(''));
				}
	
				if ($fc_userlogon != '') {
					$ext->add('app-userlogonoff', $fc_userlogon, '', new ext_macro('user-logon'));
					$ext->add('app-userlogonoff', $fc_userlogon, '', new ext_hangup(''));
					
					$clen = strlen($fc_userlogon);
					$fc_userlogon = "_$fc_userlogon.";
					$ext->add('app-userlogonoff', $fc_userlogon, '', new ext_macro('user-logon,${EXTEN:'.$clen.'}'));
					$ext->add('app-userlogonoff', $fc_userlogon, '', new ext_hangup(''));
				}
			}

			// Call pickup using app_pickup - Note that '**xtn' is hard-coded into the GXPs and SNOMs as a number to dial
			// when a user pushes a flashing BLF. 
			if ($fc_pickup != '') {
				$ext->addInclude('from-internal-additional', 'app-pickup');
				$fclen = strlen($fc_pickup);
				$ext->add('app-pickup', "_$fc_pickup.", '', new ext_NoOp('Attempt to Pickup ${EXTEN:'.$fclen.'} by ${CALLERID(num)}'));
				if (strstr($version, 'BRI')) 
					$ext->add('app-pickup', "_$fc_pickup.", '', new ext_dpickup('${EXTEN:'.$fclen.'}'));
				else
					$ext->add('app-pickup', "_$fc_pickup.", '', new ext_pickup('${EXTEN:'.$fclen.'}'));
			}
			
			
			// zap barge
			if ($fc_zapbarge != '') {
				$ext->addInclude('from-internal-additional', 'app-zapbarge'); // Add the include from from-internal
				
				$ext->add('app-zapbarge', $fc_zapbarge, '', new ext_macro('user-callerid'));
				$ext->add('app-zapbarge', $fc_zapbarge, '', new ext_setvar('GROUP()','${CALLERID(number)}'));
				$ext->add('app-zapbarge', $fc_zapbarge, '', new ext_answer(''));
				$ext->add('app-zapbarge', $fc_zapbarge, '', new ext_wait(1));
				$ext->add('app-zapbarge', $fc_zapbarge, '', new ext_zapbarge(''));
				$ext->add('app-zapbarge', $fc_zapbarge, '', new ext_hangup(''));
			}

			// chan spy
			if ($fc_chanspy != '') {
				$ext->addInclude('from-internal-additional', 'app-chanspy'); // Add the include from from-internal
				$ext->add('app-chanspy', $fc_chanspy, '', new ext_macro('user-callerid'));
				$ext->add('app-chanspy', $fc_chanspy, '', new ext_answer(''));
				$ext->add('app-chanspy', $fc_chanspy, '', new ext_wait(1));
				$ext->add('app-chanspy', $fc_chanspy, '', new ext_chanspy(''));
				$ext->add('app-chanspy', $fc_chanspy, '', new ext_hangup(''));
			}
			
			// Simulate options (ext-test)
			if ($fc_simu_pstn != '' || $fc_simu_fax != '') {
				$ext->addInclude('from-internal-additional', 'ext-test'); // Add the include from from-internal
				
				if ($fc_simu_pstn != '') {
					$ext->add('ext-test', $fc_simu_pstn, '', new ext_goto('1', 's', 'from-pstn'));
				}

				if ($fc_simu_fax != '') {
					$ext->add('ext-test', $fc_simu_fax, '', new ext_goto('1', 'in_fax', 'ext-fax'));
				}

				$ext->add('ext-test', 'h', '', new ext_macro('hangupcall'));
			}
			
			/* Always have Fax detection in ext-did, no matter what */
			$ext->add('ext-did', 'fax', '', new ext_goto('1','in_fax','ext-fax'));

			/* inbound routing extensions */
			$didlist = core_did_list();
			if(is_array($didlist)){
				$catchall = false;
				$catchall_context='ext-did-catchall';
				foreach($didlist as $item) {
					$did = core_did_get($item['extension'],$item['cidnum'],$item['channel']);
					$exten = $did['extension'];
					$cidnum = $did['cidnum'];
					$channel = $did['channel'];

					$exten = (empty($exten)?"s":$exten);
					$exten = $exten.(empty($cidnum)?"":"/".$cidnum); //if a CID num is defined, add it

					if (empty($channel))
						$context = "ext-did";
					else {
						$context = "macro-from-zaptel-{$channel}";
						if (!isset($zapchan[$channel])) {
							// create the macro-from-zaptel-$chan context and load up the
							// startup settings
							$ext->add($context, 'fax', '', new ext_goto('1','in_fax','ext-fax'));
							$ext->add($context, 's', '', new ext_noop('Entering '.$context.' with DID = ${DID}'));
							$zapchan[$channel] = "unfinished";
						}
					}

					// Start inbound processing. Unneeded line to be possibly overridden by something in 
					// extensions_custom.conf
					$ext->add($context, $exten, '', new ext_setvar('__FROM_DID','${EXTEN}'));
					// always set callerID name
					$ext->add($context, $exten, '', new ext_gotoif('$[ "${CALLERID(name)}" != "" ] ','cidok'));
					$ext->add($context, $exten, '', new ext_setvar('CALLERID(name)','${CALLERID(num)}'));
					$ext->add($context, $exten, 'cidok', new ext_noop('CallerID is ${CALLERID(all)}'));

					if (!empty($item['mohclass']) && trim($item['mohclass']) != 'default') {
						$ext->add($context, $exten, '', new ext_setmusiconhold($item['mohclass']));
						$ext->add($context, $exten, '', new ext_setvar('__MOHCLASS',$item['mohclass']));
					}

					// If we require RINGING, signal it as soon as we enter.
					if ($item['ringing'] === "CHECKED") {
						$ext->add($context, $exten, '', new ext_ringing(''));
					}

					if ($exten == "s" && $context == "ext-did") {  
						//if the exten is s, then also make a catchall for undefined DIDs if it's not a zaptel route
						$catchaccount = "_X.".(empty($cidnum)?"":"/".$cidnum);
						if ($catchaccount == "_X.") 
							$catchall = true;
						$ext->add($catchall_context, $catchaccount, '', new ext_NoOp('Catch-All DID Match - Found ${EXTEN} - You probably want a DID for this.'));
						$ext->add($catchall_context, $catchaccount, '', new ext_goto('1','s','ext-did'));
					}
					
					if ($item['faxexten'] != "default") {
						$ext->add($context, $exten, '', new ext_setvar('FAX_RX',$item['faxexten']));
					}
					if (!empty($item['faxemail'])) {
						$ext->add($context, $exten, '', new ext_setvar('FAX_RX_EMAIL',$item['faxemail']));
					}
					if ($item['answer'] == "1") {
						$ext->add($context, $exten, '', new ext_answer(''));
						$ext->add($context, $exten, '', new ext_wait($item['wait']));
					}
					if ($item['answer'] == "2") { // NVFaxDetect
						$ext->add($context, $exten, '', new ext_answer(''));
						$ext->add($context, $exten, '', new ext_playtones('ring'));
						$ext->add($context, $exten, '', new ext_nvfaxdetect($item['wait']));
					}
					if ($item['privacyman'] == "1") {
						$ext->add($context, $exten, '', new ext_macro('privacy-mgr'));
					}
					if (!empty($item['alertinfo'])) {
						$ext->add($context, $exten, '', new ext_setvar("__ALERT_INFO", str_replace(';', '\;', $item['alertinfo'])));
					}
					// Add CID prefix, no need to do checks for existing pre-pends, this is an incoming did so this should
					// be the first time the CID is manipulated. We set _RGPREFIX which is the same used throughout the different
					// modules.
					//
					// TODO: If/When RGPREFIX is added to trunks, then see code in ringgroups to strip prefix if added here.
					//
					// TODO: core FreePBX documentation about this standard. (and probably rename from RGPREFIX to CIDPREFIX)
					//
					if (!empty($item['grppre'])) {
						$ext->add($context, $exten, '', new ext_setvar('_RGPREFIX', $item['grppre']));
						$ext->add($context, $exten, '', new ext_setvar('CALLERID(name)','${RGPREFIX}${CALLERID(name)}'));
					}
					
					// If we're doing a zaptel route, now we need to do the gotos ONLY IF it's the first time round.
					// Except for the fact that this doesn't work. Not at all. Dial returns -1 and hangs up the 
					// call. This is fixed in 1.4 with TryExec(), but until then, we can't match on zap
					// _and_ anything else.  When we decide to say 'Only 1.4!' then we can reenable this
					// and use TryExec(Goto..) and then check ${TRYSTATUS} for FAILED or SUCCESS. I didn't
					// bother actually writing that, as the syntax may change.
					//if (isset($zapchan[$channel]) && $zapchan[$channel] == "unfinished") {
					//	$ext->add($context, 's', '', new ext_gotoif('$[ "${DID}" = "s" ]', 'nos', 'sok'));
					//	$ext->add($context, 's', 'nos', new ext_noop('Skipping ${DID} because it is s'));
					//	$ext->add($context, 's', '', new ext_goto("trycid"));
					//	$ext->add($context, 's', 'sok', new ext_noop('Trying ${DID}'));
					//	$ext->add($context, 's', '', new ext_goto("1", '${DID}'));
					//	$ext->add($context, 's', 'trycid', new ext_gotoif('$[ "${CALLERID(num)}" = "" ]', 'nocid', 'cidok'));
					//	$ext->add($context, 's', 'nocid', new ext_noop('Skipping empty CallerID Num'));
					//	$ext->add($context, 's', '', new ext_goto("end"));
					//	$ext->add($context, 's', 'cidok', new ext_noop('Trying ${DID}/${CALLERID(num)}'));
					//	$ext->add($context, 's', '', new ext_goto("1", '${DID}/${CALLERID(num)}'));
					//	$ext->add($context, 's', 'end', new ext_noop('End of macro init'));
						// Now set $zapchan[$channel] so we don't do this again
						$zapchan[$channel] = "set";
					//}
					//the goto destination
					// destination field in 'incoming' database is backwards from what ext_goto expects
					$goto_context = strtok($did['destination'],',');
					$goto_exten = strtok(',');
					$goto_pri = strtok(',');
					$ext->add($context, $exten, '', new ext_goto($goto_pri,$goto_exten,$goto_context));
					
				}
				// If there's not a catchall, make one with an error message
				if (!$catchall) {
					$ext->add($catchall_context, 's', '', new ext_noop("No DID or CID Match"));
					$ext->add($catchall_context, 's', '', new ext_answer(''));
					$ext->add($catchall_context, 's', '', new ext_wait('2'));
					$ext->add($catchall_context, 's', '', new ext_playback('ss-noservice'));
					$ext->add($catchall_context, 's', '', new ext_sayalpha('${FROM_DID}'));
					$ext->add($catchall_context, '_[*#X].', '', new ext_setvar('__FROM_DID', '${EXTEN}'));
					$ext->add($catchall_context, '_[*#X].', '', new ext_noop('Received an unknown call with DID set to ${EXTEN}'));
					$ext->add($catchall_context, '_[*#X].', '', new ext_goto('1','s','ext-did'));
				}
					
			}

			/* MODIFIED (PL)
			 *
			 * Add Direct DIDs
			 *
			 * This functions creates a new context, ext-did-direct, used to route an incoming DID directly to the specified user.
 			 * The purpose is to use when a user has a personal external DID. This keeps it clean and easy to administer.
 			 * Any conflict with those routes will depend on which of the two contexts are included first in the extensions.conf file.
 			 *
 			 * Calls are sent to context from-did-direct though this feature. You must create that context in extenions.conf or
 			 * in extensions_custom.conf and it should look something like:
 			 *
 			 * [from-did-direct]
 			 * include => ext-findmefollow
 			 * include => ext-local
 			 *
 			 * This is so that personal ring groups are used if they exist for the direct did and if not, then the local extension.
			 * If the module is not implented, it will just go to the users extension.
 			 */

			$directdidlist = core_directdid_list();
			if(is_array($directdidlist)){
				$context = "ext-did";
				if(!is_array($didlist)){
					/* if not set above, add one here */
					$ext->add($context, 'fax', '', new ext_goto('1','in_fax','ext-fax'));
				}
				foreach($directdidlist as $item) {
					$exten = $item['directdid'];
					$ext->add($context, $exten, '', new ext_setvar('__FROM_DID','${EXTEN}'));
					// always set callerID name
					$ext->add($context, $exten, '', new ext_gotoif('$[ "${CALLERID(name)}" != "" ] ','cidok'));
					$ext->add($context, $exten, '', new ext_setvar('CALLERID(name)','${CALLERID(num)}'));
					$ext->add($context, $exten, 'cidok', new ext_noop('CallerID is ${CALLERID(all)}'));

					if (!empty($item['mohclass']) && trim($item['mohclass']) != 'default') {
						$ext->add($context, $exten, '', new ext_setmusiconhold($item['mohclass']));
						$ext->add($context, $exten, '', new ext_setvar('__MOHCLASS',$item['mohclass']));
					}
					
					if ($item['faxexten'] != "default") {
						$ext->add($context, $exten, '', new ext_setvar('FAX_RX',$item['faxexten']));
					}
					if (!empty($item['faxemail'])) {
						$ext->add($context, $exten, '', new ext_setvar('FAX_RX_EMAIL',$item['faxemail']));
					}
					if ($item['answer'] == "1") {
						$ext->add($context, $exten, '', new ext_answer(''));
						$ext->add($context, $exten, '', new ext_wait($item['wait']));
					}
					if ($item['answer'] == "2") { // NVFaxDetect
						$ext->add($context, $exten, '', new ext_answer(''));
						$ext->add($context, $exten, '', new ext_playtones('ring'));
						$ext->add($context, $exten, '', new ext_nvfaxdetect($item['wait']));
					}
					if ($item['privacyman'] == "1") {
						$ext->add($context, $exten, '', new ext_macro('privacy-mgr'));
					}


					if (!empty($item['didalert'])) {
						$ext->add($context, $exten, '', new ext_setvar("_ALERT_INFO", str_replace(';', '\;', $item['didalert'])));
					}
					$goto_context = 'from-did-direct';
					$goto_exten = $item['extension'];
					$goto_pri = 1;
					$ext->add($context, $exten, '', new ext_goto($goto_pri,$goto_exten,$goto_context));

				}
			}

			
			/* user extensions */
			$ext->addInclude('from-internal-additional','ext-local');
			$userlist = core_users_list();
			if (is_array($userlist)) {
				foreach($userlist as $item) {
					$exten = core_users_get($item[0]);
					$vm = ($exten['voicemail'] == "novm" ? "novm" : $exten['extension']);

					if (isset($exten['ringtimer']) && $exten['ringtimer'] != 0)
						$ext->add('ext-local', $exten['extension'], '', new ext_setvar('__RINGTIMER',$exten['ringtimer']));
					
					$ext->add('ext-local', $exten['extension'], '', new ext_macro('exten-vm',$vm.",".$exten['extension']));
					$ext->add('ext-local', $exten['extension'], '', new ext_hangup(''));
					
					if($vm != "novm") {
						$ext->add('ext-local', '${VM_PREFIX}'.$exten['extension'], '', new ext_macro('vm',"$vm,DIRECTDIAL"));
						$ext->add('ext-local', '${VM_PREFIX}'.$exten['extension'], '', new ext_hangup(''));
						$ext->add('ext-local', 'vmb'.$exten['extension'], '', new ext_macro('vm',"$vm,BUSY"));
						$ext->add('ext-local', 'vmb'.$exten['extension'], '', new ext_hangup(''));
						$ext->add('ext-local', 'vmu'.$exten['extension'], '', new ext_macro('vm',"$vm,NOANSWER"));
						$ext->add('ext-local', 'vmu'.$exten['extension'], '', new ext_hangup(''));
						$ext->add('ext-local', 'vms'.$exten['extension'], '', new ext_macro('vm',"$vm,NOMESSAGE"));
						$ext->add('ext-local', 'vms'.$exten['extension'], '', new ext_hangup(''));
					}
						
					$hint = core_hint_get($exten['extension']);
					if (!empty($hint))
						$ext->addHint('ext-local', $exten['extension'], $hint);
					if ($exten['sipname']) {
						$ext->add('ext-local', $exten['sipname'], '', new ext_goto('1',$item[0],'from-internal'));
					}
				}
			}

			// create from-trunk context for each trunk that adds counts to channels
			//
			$trunklist = core_trunks_list(true);
			if (is_array($trunklist)) {
				foreach ($trunklist as $trunkprops) {
					if (trim($trunkprops['value']) == 'on') {
						// value of on is disabled and for zap we don't create a context
						continue;
					}
					switch ($trunkprops['tech']) {
						case 'IAX':
						case 'IAX2':
						case 'SIP':
							$trunkgroup = $trunkprops['globalvar'];
							$trunkcontext  = "from-trunk-".$trunkprops['name'];
							$ext->add($trunkcontext, '_.', '', new ext_setvar('GROUP()',$trunkgroup));
							$ext->add($trunkcontext, '_.', '', new ext_goto('1','${EXTEN}','from-trunk'));
							break;
						default:
					}
				}
			}

			/* dialplan globals */
			// modules should NOT use the globals table to store anything!
			// modules should use $ext->addGlobal("testvar","testval"); in their module_get_config() function instead
			// I'm cheating for core functionality - do as I say, not as I do ;-)		

			// Auto add these globals to give access to agi scripts and other needs, unless defined in the global table.
			//
			$amp_conf_globals = array( 
				"ASTETCDIR", 
				"ASTMODDIR", 
				"ASTVARLIBDIR", 
				"ASTAGIDIR", 
				"ASTSPOOLDIR", 
				"ASTRUNDIR", 
				"ASTLOGDIR",
				"CWINUSEBUSY",
				"AMPMGRUSER",
				"AMPMGRPASS"
			);

			$sql = "SELECT * FROM globals";
			$globals = sql($sql,"getAll",DB_FETCHMODE_ASSOC);
			foreach($globals as $global) {
				$ext->addGlobal($global['variable'],$global['value']);

				// now if for some reason we have a variable in the global table
				// that is in our $amp_conf_globals list, then remove it so we
				// don't duplicate, the sql table will take precedence
				//
				if (array_key_exists($global['variable'],$amp_conf_globals)) {
					$rm_keys = array_keys($amp_conf_globals,$global['variable']);
					foreach ($rm_keys as $index) {
						unset($amp_conf_globals[$index]);
					}
				}
			}
			foreach ($amp_conf_globals as $global) {
				if (isset($amp_conf[$global])) {
					$value = $amp_conf[$global];
					if ($value === true || $value === false) {
						$value = ($value) ? 'true':'false';
					}
					$ext->addGlobal($global, $value);
					out("Added to globals: $global = $value");
				}
			}
			
			/* outbound routes */
			// modules should use their own table for storage (and module_get_config() to add dialplan)
			// modules should NOT use the extension table to store anything!
			$sql = "SELECT application FROM extensions where context = 'outbound-allroutes' ORDER BY application";
			$outrts = sql($sql,"getAll",DB_FETCHMODE_ASSOC);
			$ext->addInclude('from-internal-additional','outbound-allroutes');
			$ext->add('outbound-allroutes', 'foo', '', new ext_noop('bar'));
			foreach($outrts as $outrt) {
				$ext->addInclude('outbound-allroutes',$outrt['application']);
				$sql = "SELECT * FROM extensions where context = '".$outrt['application']."' ORDER BY extension, CAST(priority AS UNSIGNED) ASC";
				$thisrt = sql($sql,"getAll",DB_FETCHMODE_ASSOC);
				foreach($thisrt as $exten) {
					//if emergencyroute, then set channel var
					if(strpos($exten['args'],"EMERGENCYROUTE") !== false)
						$ext->add($outrt['application'], $exten['extension'], '', new ext_setvar("EMERGENCYROUTE",substr($exten['args'],15)));
					if(strpos($exten['args'],"INTRACOMPANYROUTE") !== false)
						$ext->add($outrt['application'], $exten['extension'], '', new ext_setvar("INTRACOMPANYROUTE",substr($exten['args'],18)));
					// Don't set MOHCLASS if already set, threre may be a feature code that overrode it
					if(strpos($exten['args'],"MOHCLASS") !== false)
						$ext->add($outrt['application'], $exten['extension'], '', new ext_setvar("MOHCLASS", '${IF($["x${MOHCLASS}"="x"]?'.substr($exten['args'],9).':${MOHCLASS})}' ));
					if(strpos($exten['args'],"dialout-trunk") !== false)
						$ext->add($outrt['application'], $exten['extension'], '', new ext_macro($exten['args']));
					if(strpos($exten['args'],"dialout-enum") !== false)
						$ext->add($outrt['application'], $exten['extension'], '', new ext_macro($exten['args']));
					if(strpos($exten['args'],"outisbusy") !== false)
						$ext->add($outrt['application'], $exten['extension'], '', new ext_macro("outisbusy"));
				}
			}
			general_generate_indications();

			// "blackhole" destinations
			$ext->add('app-blackhole', 'hangup', '', new ext_noop('Blackhole Dest: Hangup'));
			$ext->add('app-blackhole', 'hangup', '', new ext_hangup());

			$ext->add('app-blackhole', 'zapateller', '', new ext_noop('Blackhole Dest: Play SIT Tone'));
			$ext->add('app-blackhole', 'zapateller', '', new ext_answer());
			$ext->add('app-blackhole', 'zapateller', '', new ext_zapateller());
			// Should hangup ?
			// $ext->add('app-blackhole', 'zapateller', '', new ext_hangup());
					
			$ext->add('app-blackhole', 'musiconhold', '', new ext_noop('Blackhole Dest: Put caller on hold forever'));
			$ext->add('app-blackhole', 'musiconhold', '', new ext_answer());
			$ext->add('app-blackhole', 'musiconhold', '', new ext_musiconhold());

			$ext->add('app-blackhole', 'congestion', '', new ext_noop('Blackhole Dest: Congestion'));
			$ext->add('app-blackhole', 'congestion', '', new ext_answer());
			$ext->add('app-blackhole', 'congestion', '', new ext_playtones('congestion'));
			$ext->add('app-blackhole', 'congestion', '', new ext_congestion());
			$ext->add('app-blackhole', 'congestion', '', new ext_hangup());

			$ext->add('app-blackhole', 'busy', '', new ext_noop('Blackhole Dest: Busy'));
			$ext->add('app-blackhole', 'busy', '', new ext_answer());
			$ext->add('app-blackhole', 'busy', '', new ext_playtones('busy'));
			$ext->add('app-blackhole', 'busy', '', new ext_busy());
			$ext->add('app-blackhole', 'busy', '', new ext_hangup());

			if ($amp_conf['AMPBADNUMBER'] !== false) {
				$context = 'bad-number';
				$exten = '_X.';
				$ext->add($context, $exten, '', new extension('ResetCDR()'));
				$ext->add($context, $exten, '', new extension('NoCDR()'));
				$ext->add($context, $exten, '', new ext_wait('1'));
				$ext->add($context, $exten, '', new ext_playback('silence/1&cannot-complete-as-dialed&check-number-dial-again,noanswer'));
				$ext->add($context, $exten, '', new ext_wait('1'));
				$ext->add($context, $exten, '', new ext_congestion('20'));
				$ext->add($context, $exten, '', new ext_hangup());

				$exten = '_*.';
				$ext->add($context, $exten, '', new extension('ResetCDR()'));
				$ext->add($context, $exten, '', new extension('NoCDR()'));
				$ext->add($context, $exten, '', new ext_wait('1'));
				$ext->add($context, $exten, '', new ext_playback('silence/1&feature-not-avail-line&silence/1&cannot-complete-as-dialed&check-number-dial-again,noanswer'));
				$ext->add($context, $exten, '', new ext_wait('1'));
				$ext->add($context, $exten, '', new ext_congestion('20'));
				$ext->add($context, $exten, '', new ext_hangup());
			}

		break;
	}
}











/* begin page.ampusers.php functions */

function core_ampusers_add($username, $password, $extension_low, $extension_high, $deptname, $sections) {
	$sql = "INSERT INTO ampusers (username, password, extension_low, extension_high, deptname, sections) VALUES (";
	$sql .= "'".$username."',";
	$sql .= "'".$password."',";
	$sql .= "'".$extension_low."',";
	$sql .= "'".$extension_high."',";
	$sql .= "'".$deptname."',";
	$sql .= "'".implode(";",$sections)."');";
	
	sql($sql,"query");
}

function core_ampusers_del($username) {
	$sql = "DELETE FROM ampusers WHERE username = '".$username."'";
	sql($sql,"query");
}

function core_ampusers_list() {
	$sql = "SELECT username FROM ampusers ORDER BY username";
	return sql($sql,"getAll");
}

/* end page.ampusers.php functions */









/* begin page.did.php functions */

function core_did_list($order='extension'){
	switch ($order) {
		case 'description':
			$sql = "SELECT * FROM incoming ORDER BY description,extension,cidnum";
			break;
		case 'extension':
		default:
			$sql = "SELECT * FROM incoming ORDER BY extension,cidnum";
	}
	return sql($sql,"getAll",DB_FETCHMODE_ASSOC);
}

function core_did_get($extension="",$cidnum="",$channel=""){
	$sql = "SELECT * FROM incoming WHERE cidnum = \"$cidnum\" AND extension = \"$extension\" AND channel = \"$channel\"";
	return sql($sql,"getRow",DB_FETCHMODE_ASSOC);
}

function core_did_del($extension,$cidnum, $channel){
	$sql="DELETE FROM incoming WHERE cidnum = \"$cidnum\" AND extension = \"$extension\" AND channel = \"$channel\"";
	sql($sql);
}

function core_did_edit($old_extension,$old_cidnum, $old_channel, $incoming){

	$old_extension = addslashes(trim($old_extension));
	$old_cidnum = addslashes(trim($old_cidnum));
	$old_channel = addslashes(trim($old_channel));

	$incoming['extension'] = trim($incoming['extension']);
	$incoming['cidnum'] = trim($incoming['cidnum']);
	$incoming['channel'] = trim($incoming['channel']);

	$extension = addslashes($incoming['extension']);
	$cidnum = addslashes($incoming['cidnum']);
	$channel = addslashes($incoming['channel']);

	// if did or cid changed, then check to make sure that this pair is not already being used.
	//
	if (($extension != $old_extension) || ($cidnum != $old_cidnum)) {
		$existing=core_did_get($extension,$cidnum,$channel);
		if (empty($existing) && (trim($cidnum) == "")) {
			$existing_directdid = core_users_directdid_get($extension);
		} else {
			$existing_directdid = "";
		}
	} else {
		$existing = $existing_directdid = "";
	}

	if (empty($existing) && empty($existing_directdid)) {
		core_did_del($old_extension,$old_cidnum,$old_channel);
		core_did_add($incoming);
		return true;
	} else {
		if (!empty($existing)) {
			echo "<script>javascript:alert('"._("A route for this DID/CID already exists!")." => ".$existing['extension']."/".$existing['cidnum']."')</script>";
		} else {
			echo "<script>javascript:alert('"._("A directdid for this DID is already associated with extension:")." ".$existing_directdid['extension']." (".$existing_directdid['name'].")')</script>";
		}
		return false;
	}
}

function core_did_add($incoming){
	foreach ($incoming as $key => $val) { ${$key} = addslashes($val); } // create variables from request

	// Check to make sure the did is not being used elsewhere
	//
	$existing=core_did_get($extension,$cidnum,$channel);
	if (empty($existing) && (trim($cidnum) == "")) {
		$existing_directdid = core_users_directdid_get($extension);
	} else {
		$existing_directdid = "";
	}

	if (empty($existing) && empty($existing_directdid)) {
		$destination=${$goto0.'0'};
		$sql="INSERT INTO incoming (cidnum,extension,destination,faxexten,faxemail,answer,wait,privacyman,alertinfo, channel, ringing, mohclass, description, grppre) values ('$cidnum','$extension','$destination','$faxexten','$faxemail','$answer','$wait','$privacyman','$alertinfo', '$channel', '$ringing', '$mohclass', '$description', '$grppre')";
		sql($sql);
		return true;
	} else {
		if (!empty($existing)) {
			echo "<script>javascript:alert('"._("A route for this DID/CID already exists!")." => ".$existing['extension']."/".$existing['cidnum']."')</script>";
		} else {
			echo "<script>javascript:alert('"._("A directdid for this DID is already associated with extension:")." ".$existing_directdid['extension']." (".$existing_directdid['name'].")')</script>";
		}
		return false;
	}
}

/* end page.did.php functions */







/* begin page.devices.php functions */

//get the existing devices
function core_devices_list($tech="all") {
	$sql = "SELECT id,description FROM devices";
	switch (strtoupper($tech)) {
		case "IAX":
			$sql .= " WHERE tech = 'iax2'";
			break;
		case "IAX2":
		case "SIP":
		case "ZAP":
			$sql .= " WHERE tech = '".strtolower($tech)."'";
			break;
		case "ALL":
		default:
	}
	$sql .= ' ORDER BY id';
	$results = sql($sql,"getAll");

	foreach($results as $result){
		if (checkRange($result[0])){
			$extens[] = array(
				0=>$result[0],  // for backwards compatibility
				1=>$result[1],
				'id'=>$result[0], // FETCHMODE_ASSOC emulation
				'description'=>$result[1],
			);
		}
	}
	if (isset($extens)) {
		return $extens;
	} else { 
		return null;
	}
}


function core_devices_add($id,$tech,$dial,$devicetype,$user,$description,$emergency_cid=null){
	global $amp_conf;
	global $currentFile;
	global $astman;

	$display = isset($_REQUEST['display'])?$_REQUEST['display']:'';
	
	//ensure this id is not already in use
	$devices = core_devices_list();
	if (is_array($devices)) {
		foreach($devices as $device) {
			if ($device[0] === $id) {
				if ($display <> 'extensions') echo "<script>javascript:alert('"._("This device id is already in use")."');</script>";
				return false;
			}
		}
	}
	//unless defined, $dial is TECH/id
	if ( $dial == '' ) {
		//zap is an exception
		if ( strtolower($tech) == "zap" ) {
			$zapchan = $_REQUEST['devinfo_channel'] != '' ? $_REQUEST['devinfo_channel'] : $_REQUEST['channel'];
			$dial = 'ZAP/'.$zapchan;
		} else {
			$dial = strtoupper($tech)."/".$id;
		}
	}
	//if (empty($dial) && strtolower($tech) == "zap")
	//	$dial = "ZAP/".($_REQUEST['channel'] != '' ? $_REQUEST['channel'] : $_REQUEST['devinfo_channel']);
	//if (empty($dial))
	//	$dial = strtoupper($tech)."/".$id;
	
	//check to see if we are requesting a new user
	if ($user == "new") {
		$user = $id;
		$jump = true;
	}
	
	if(!get_magic_quotes_gpc()) {
		if(!empty($emergency_cid))
			$emergency_cid = addslashes($emergency_cid);
		if(!empty($description))
			$description = addslashes($description);
	}
	
	//insert into devices table
	$sql="INSERT INTO devices (id,tech,dial,devicetype,user,description,emergency_cid) values (\"$id\",\"$tech\",\"$dial\",\"$devicetype\",\"$user\",\"$description\",\"$emergency_cid\")";
	sql($sql);
	
	//add details to astdb
	if ($astman) {
		$astman->database_put("DEVICE",$id."/dial",$dial);
		$astman->database_put("DEVICE",$id."/type",$devicetype);
		$astman->database_put("DEVICE",$id."/user",$user);
		if(!empty($emergency_cid))
			$astman->database_put("DEVICE",$id."/emergency_cid","\"".$emergency_cid."\"");
		if($user != "none") {
			$existingdevices = $astman->database_get("AMPUSER",$user."/device");
			if (empty($existingdevices)) {
				$astman->database_put("AMPUSER",$user."/device",$id);
			} else {
				$existingdevices .= "&";
				//only append device value if this id doesn't exist in it already
				if(strpos($existingdevices,$id."&") === false) // if not containing $id 
					$astman->database_put("AMPUSER",$user."/device",$existingdevices.$id);
			}
		}
	} else {
		fatal("Cannot connect to Asterisk Manager with ".$amp_conf["AMPMGRUSER"]."/".$amp_conf["AMPMGRPASS"]);
	}
	
	// create a voicemail symlink if needed
	$thisUser = core_users_get($user);
	if(isset($thisUser['voicemail']) && ($thisUser['voicemail'] != "novm")) {
		if(empty($thisUser['voicemail']))
			$vmcontext = "default";
		else 
			$vmcontext = $thisUser['voicemail'];
		
		//voicemail symlink
		exec("rm -f /var/spool/asterisk/voicemail/device/".$id);
		exec("/bin/ln -s /var/spool/asterisk/voicemail/".$vmcontext."/".$user."/ /var/spool/asterisk/voicemail/device/".$id);
	}
		
	//take care of sip/iax/zap config
	$funct = "core_devices_add".strtolower($tech);
	if(function_exists($funct)){
		$funct($id);
	}
	
/*	if($user != "none") {
		core_hint_add($user);
	}*/
	
	//if we are requesting a new user, let's jump to users.php
	if (isset($jump)) {
		echo("<script language=\"JavaScript\">window.location=\"config.php?display=users&extdisplay={$id}&name={$description}\";</script>");
	}
	return true;
}

function core_devices_del($account){
	global $amp_conf;
	global $currentFile;
	global $astman;
	
	//get all info about device
	$devinfo = core_devices_get($account);
	
	//delete details to astdb
	if ($astman) {
		// If a user was selected, remove this device from the user
		$deviceuser = $astman->database_get("DEVICE",$account."/user");
		if (isset($deviceuser) && $deviceuser != "none") {
			// Remove the device record from the user's device list
			$userdevices = $astman->database_get("AMPUSER",$deviceuser."/device");
			/*$userdevices = str_replace($account."&", "", $userdevices."&");
			
			// If there was more than one device, remove the extra "&" at the end.
			if (substr($userdevices, -1, 1) == "&") {
				$userdevices = substr($userdevices, 0, -1);
			}*/
			$userdevicesarr = explode("&", $userdevices);
			array_splice($userdevicesarr, array_search($account, $userdevicesarr), 1);
			$userdevices = implode("&", $userdevicesarr);
			
			if (empty($userdevices)) {
					$astman->database_del("AMPUSER",$deviceuser."/device");
			} else {
					$astman->database_put("AMPUSER",$deviceuser."/device",$userdevices);
			}
		}
		$astman->database_del("DEVICE",$account."/dial");
		$astman->database_del("DEVICE",$account."/type");
		$astman->database_del("DEVICE",$account."/user");
		$astman->database_del("DEVICE",$account."/emergency_cid");

		//delete from devices table
		$sql="DELETE FROM devices WHERE id = \"$account\"";
		sql($sql);

		//voicemail symlink
		exec("rm -f /var/spool/asterisk/voicemail/device/".$account);
	} else {
		fatal("Cannot connect to Asterisk Manager with ".$amp_conf["AMPMGRUSER"]."/".$amp_conf["AMPMGRPASS"]);
	}
	
	//take care of sip/iax/zap config
	$funct = "core_devices_del".strtolower($devinfo['tech']);
	if(function_exists($funct)){
		$funct($account);
	}
}

function core_devices_get($account){
	//get all the variables for the meetme
	$sql = "SELECT * FROM devices WHERE id = '$account'";
	$results = sql($sql,"getRow",DB_FETCHMODE_ASSOC);
	
	//take care of sip/iax/zap config
	$funct = "core_devices_get".strtolower($results['tech']);
	if (!empty($results['tech']) && function_exists($funct)) {
		$devtech = $funct($account);
		if (is_array($devtech)){
			$results = array_merge($results,$devtech);
		}
	}
	
	return $results;
}

// this function rebuilds the astdb based on device table contents
// used on devices.php if action=resetall
function core_devices2astdb(){
	global $astman;
	global $amp_conf;

	$sql = "SELECT * FROM devices";
	$devresults = sql($sql,"getAll",DB_FETCHMODE_ASSOC);

	//add details to astdb
	if ($astman) {
		$astman->database_deltree("DEVICE");
		foreach ($devresults as $dev) {
			extract($dev);	
			$astman->database_put("DEVICE",$id."/dial",$dial);
			$astman->database_put("DEVICE",$id."/type",$devicetype);
			$astman->database_put("DEVICE",$id."/user",$user);		
			// If a user is selected, add this device to the user
			if ($user != "none") {
					$existingdevices = $astman->database_get("AMPUSER",$user."/device");
					if (!empty($existingdevices)) {
							$existingdevices .= "&";
					}
					$astman->database_put("AMPUSER",$user."/device",$existingdevices.$id);
			}
			
			// create a voicemail symlink if needed
			$thisUser = core_users_get($user);
			if(isset($thisUser['voicemail']) && ($thisUser['voicemail'] != "novm")) {
				if(empty($thisUser['voicemail']))
					$vmcontext = "default";
				else 
					$vmcontext = $thisUser['voicemail'];
				//voicemail symlink
				exec("rm -f /var/spool/asterisk/voicemail/device/".$id);
				exec("/bin/ln -s /var/spool/asterisk/voicemail/".$vmcontext."/".$user."/ /var/spool/asterisk/voicemail/device/".$id);
			}
		}
		return true;
	} else {
		return false;
	}
}

// this function rebuilds the astdb based on users table contents
// used on devices.php if action=resetall
function core_users2astdb(){
	global $amp_conf;
	global $astman;

	$sql = "SELECT * FROM users";
	$userresults = sql($sql,"getAll",DB_FETCHMODE_ASSOC);
	
	//add details to astdb
	if ($astman) {
		foreach($userresults as $usr) {
			extract($usr);
			$astman->database_put("AMPUSER",$extension."/password",$password);
			$astman->database_put("AMPUSER",$extension."/ringtimer",$ringtimer);
			$astman->database_put("AMPUSER",$extension."/noanswer",$noanswer);
			$astman->database_put("AMPUSER",$extension."/recording",$recording);
			$astman->database_put("AMPUSER",$extension."/outboundcid","\"".addslashes($outboundcid)."\"");
			$astman->database_put("AMPUSER",$extension."/cidname","\"".addslashes($name)."\"");
			$astman->database_put("AMPUSER",$extension."/voicemail","\"".$voicemail."\"");
		}	
		return true;
	} else {
		return false;
	}

//	TODO: this was...	
// 	return $astman->disconnect();
//	is "true" the correct value...?
}

//add to sip table
function core_devices_addsip($account) {
	global $db;
	global $currentFile;

	foreach ($_REQUEST as $req=>$data) {
		if ( substr($req, 0, 8) == 'devinfo_' ) {
			$keyword = substr($req, 8);
			if ( $keyword == 'dial' && $data == '' ) {
				$sipfields[] = array($account, $keyword, 'SIP/'.$account);
			} elseif ($keyword == 'mailbox' && $data == '') {
				$sipfields[] = array($account,'mailbox',$account.'@device');
			} else {
				$sipfields[] = array($account, $keyword, $data);
			}
		}
	}
	
	if ( !is_array($sipfields) ) { // left for compatibilty....lord knows why !
		$sipfields = array(
			//array($account,'account',$account),
			array($account,'accountcode',(isset($_REQUEST['accountcode']))?$_REQUEST['accountcode']:''),
			array($account,'secret',(isset($_REQUEST['secret']))?$_REQUEST['secret']:''),
			array($account,'canreinvite',(isset($_REQUEST['canreinvite']))?$_REQUEST['canreinvite']:'no'),
			array($account,'context',(isset($_REQUEST['context']))?$_REQUEST['context']:'from-internal'),
			array($account,'dtmfmode',(isset($_REQUEST['dtmfmode']))?$_REQUEST['dtmfmode']:''),
			array($account,'host',(isset($_REQUEST['host']))?$_REQUEST['host']:'dynamic'),
			array($account,'type',(isset($_REQUEST['type']))?$_REQUEST['type']:'friend'),
			array($account,'mailbox',(isset($_REQUEST['mailbox']) && !empty($_REQUEST['mailbox']))?$_REQUEST['mailbox']:$account.'@device'),
			array($account,'username',(isset($_REQUEST['username']))?$_REQUEST['username']:$account),
			array($account,'nat',(isset($_REQUEST['nat']))?$_REQUEST['nat']:'yes'),
			array($account,'port',(isset($_REQUEST['port']))?$_REQUEST['port']:'5060'),
			array($account,'qualify',(isset($_REQUEST['qualify']))?$_REQUEST['qualify']:'yes'),
			array($account,'callgroup',(isset($_REQUEST['callgroup']))?$_REQUEST['callgroup']:''),
			array($account,'pickupgroup',(isset($_REQUEST['pickupgroup']))?$_REQUEST['pickupgroup']:''),
			array($account,'disallow',(isset($_REQUEST['disallow']))?$_REQUEST['disallow']:''),
			array($account,'allow',(isset($_REQUEST['allow']))?$_REQUEST['allow']:'')
			//array($account,'record_in',(isset($_REQUEST['record_in']))?$_REQUEST['record_in']:'On-Demand'),
			//array($account,'record_out',(isset($_REQUEST['record_out']))?$_REQUEST['record_out']:'On-Demand'),
			//array($account,'callerid',(isset($_REQUEST['description']))?$_REQUEST['description']." <".$account.'>':'device'." <".$account.'>')
		);
	}

	// Very bad
	$sipfields[] = array($account,'account',$account);	
	$sipfields[] = array($account,'callerid',(isset($_REQUEST['description']) && $_REQUEST['description'])?$_REQUEST['description']." <".$account.'>':'device'." <".$account.'>');
	
	// Where is this in the interface ??????
	$sipfields[] = array($account,'record_in',($_REQUEST['record_in'])?$_REQUEST['record_in']:'On-Demand');
	$sipfields[] = array($account,'record_out',($_REQUEST['record_out'])?$_REQUEST['record_out']:'On-Demand');

	$compiled = $db->prepare('INSERT INTO sip (id, keyword, data) values (?,?,?)');
	$result = $db->executeMultiple($compiled,$sipfields);
	if(DB::IsError($result)) {
		die_freepbx($result->getDebugInfo()."<br><br>".'error adding to SIP table');	
	}
}

function core_devices_delsip($account) {
	global $db;
	global $currentFile;
	
	$sql = "DELETE FROM sip WHERE id = '$account'";
	$result = $db->query($sql);
	
	if(DB::IsError($result)) {
		die_freepbx($result->getMessage().$sql);
	}
}

function core_devices_getsip($account) {
	global $db;
	$sql = "SELECT keyword,data FROM sip WHERE id = '$account'";
	$results = $db->getAssoc($sql);
	if(DB::IsError($results)) {
		$results = null;
	}
	
	return $results;
}

//add to iax table
function core_devices_addiax2($account) {
	global $db;
	global $currentFile;
	
	foreach ($_REQUEST as $req=>$data) {
		if ( substr($req, 0, 8) == 'devinfo_' ) {
			$keyword = substr($req, 8);
			if ( $keyword == 'dial' && $data == '' ) {
				$iaxfields[] = array($account, $keyword, 'IAX2/'.$account);
			} elseif ($keyword == 'mailbox' && $data == '') {
				$iaxfields[] = array($account,'mailbox',$account.'@device');
			} else {
				$iaxfields[] = array($account, $keyword, $data);
			}
		}
	}
	
	if ( !is_array($iaxfields) ) { // left for compatibilty....lord knows why !
		$iaxfields = array(
			//array($account,'account',$account),
			array($account,'secret',($_REQUEST['secret'])?$_REQUEST['secret']:''),
			array($account,'notransfer',($_REQUEST['notransfer'])?$_REQUEST['notransfer']:'yes'),
			array($account,'context',($_REQUEST['context'])?$_REQUEST['context']:'from-internal'),
			array($account,'host',($_REQUEST['host'])?$_REQUEST['host']:'dynamic'),
			array($account,'type',($_REQUEST['type'])?$_REQUEST['type']:'friend'),
			array($account,'mailbox',($_REQUEST['mailbox'])?$_REQUEST['mailbox']:$account.'@device'),
			array($account,'username',($_REQUEST['username'])?$_REQUEST['username']:$account),
			array($account,'port',($_REQUEST['port'])?$_REQUEST['port']:'4569'),
			array($account,'qualify',($_REQUEST['qualify'])?$_REQUEST['qualify']:'yes'),
			array($account,'disallow',($_REQUEST['disallow'])?$_REQUEST['disallow']:''),
			array($account,'allow',($_REQUEST['allow'])?$_REQUEST['allow']:''),
			array($account,'accountcode',($_REQUEST['accountcode'])?$_REQUEST['accountcode']:'')
			//array($account,'record_in',($_REQUEST['record_in'])?$_REQUEST['record_in']:'On-Demand'),
			//array($account,'record_out',($_REQUEST['record_out'])?$_REQUEST['record_out']:'On-Demand'),
			//array($account,'callerid',($_REQUEST['description'])?$_REQUEST['description']." <".$account.'>':'device'." <".$account.'>')
		);
	}

	// Very bad
	$iaxfields[] = array($account,'account',$account);	
	$iaxfields[] = array($account,'callerid',(isset($_REQUEST['description']) && $_REQUEST['description'] != '')?$_REQUEST['description']." <".$account.'>':'device'." <".$account.'>');
	// Asterisk treats no caller ID from an IAX device as 'hide callerid', and ignores the caller ID
	// set in iax.conf. As we rely on this for pretty much everything, we need to specify the 
	// callerid as a variable which gets picked up in macro-callerid.
	// Ref - http://bugs.digium.com/view.php?id=456
	$iaxfields[] = array($account,'setvar',"REALCALLERIDNUM=$account");
	
	// Where is this in the interface ??????
	$iaxfields[] = array($account,'record_in',($_REQUEST['record_in'])?$_REQUEST['record_in']:'On-Demand');
	$iaxfields[] = array($account,'record_out',($_REQUEST['record_out'])?$_REQUEST['record_out']:'On-Demand');
	
	$compiled = $db->prepare('INSERT INTO iax (id, keyword, data) values (?,?,?)');
	$result = $db->executeMultiple($compiled,$iaxfields);
	if(DB::IsError($result)) {
		die_freepbx($result->getMessage()."<br><br>error adding to IAX table");	
	}
}

function core_devices_deliax2($account) {
	global $db;
	global $currentFile;
	
	$sql = "DELETE FROM iax WHERE id = '$account'";
	$result = $db->query($sql);
	
	if(DB::IsError($result)) {
		die_freepbx($result->getMessage().$sql);
	}
}

function core_devices_getiax2($account) {
	global $db;
	$sql = "SELECT keyword,data FROM iax WHERE id = '$account'";
	$results = $db->getAssoc($sql);
	if(DB::IsError($results)) {
		$results = null;
	}
	
	return $results;
}

function core_devices_addzap($account) {
	global $db;
	global $currentFile;
	
	foreach ($_REQUEST as $req=>$data) {
		if ( substr($req, 0, 8) == 'devinfo_' ) {
			$keyword = substr($req, 8);
			if ( $keyword == 'dial' && $data == '' ) {
				$zapchan = $_REQUEST['devinfo_channel'] != '' ? $_REQUEST['devinfo_channel'] : $_REQUEST['channel'];
				$zapfields[] = array($account, $keyword, 'ZAP/'.$zapchan);
			} elseif ($keyword == 'mailbox' && $data == '') {
				$zapfields[] = array($account,'mailbox',$account.'@device');
			} else {
				$zapfields[] = array($account, $keyword, $data);
			}
		}
	}
	
	if ( !is_array($zapfields) ) { // left for compatibilty....lord knows why !
		$zapfields = array(
			//array($account,'account',$account),
			array($account,'context',($_REQUEST['context'])?$_REQUEST['context']:'from-internal'),
			array($account,'mailbox',($_REQUEST['mailbox'])?$_REQUEST['mailbox']:$account.'@device'),
			//array($account,'callerid',($_REQUEST['description'])?$_REQUEST['description']." <".$account.'>':'device'." <".$account.'>'),
			array($account,'immediate',($_REQUEST['immediate'])?$_REQUEST['immediate']:'no'),
			array($account,'signalling',($_REQUEST['signalling'])?$_REQUEST['signalling']:'fxo_ks'),
			array($account,'echocancel',($_REQUEST['echocancel'])?$_REQUEST['echocancel']:'yes'),
			array($account,'echocancelwhenbridged',($_REQUEST['echocancelwhenbridged'])?$_REQUEST['echocancelwhenbridged']:'no'),
			array($account,'immediate',($_REQUEST['immediate'])?$_REQUEST['immediate']:'no'),	
			array($account,'echotraining',($_REQUEST['echotraining'])?$_REQUEST['echotraining']:'800'),
			array($account,'busydetect',($_REQUEST['busydetect'])?$_REQUEST['busydetect']:'no'),
			array($account,'busycount',($_REQUEST['busycount'])?$_REQUEST['busycount']:'7'),
			array($account,'callprogress',($_REQUEST['callprogress'])?$_REQUEST['callprogress']:'no'),
			//array($account,'record_in',($_REQUEST['record_in'])?$_REQUEST['record_in']:'On-Demand'),	
			//array($account,'record_out',($_REQUEST['record_out'])?$_REQUEST['record_out']:'On-Demand'),
			array($account,'accountcode',(isset($_REQUEST['accountcode']))?$_REQUEST['accountcode']:''),
			array($account,'channel',($_REQUEST['channel'])?$_REQUEST['channel']:'')
		);
	}

	// Very bad
	$zapfields[] = array($account,'account',$account);	
	$zapfields[] = array($account,'callerid',($_REQUEST['description'])?$_REQUEST['description']." <".$account.'>':'device'." <".$account.'>');
	
	// Where is this in the interface ??????
	$zapfields[] = array($account,'record_in',($_REQUEST['record_in'])?$_REQUEST['record_in']:'On-Demand');
	$zapfields[] = array($account,'record_out',($_REQUEST['record_out'])?$_REQUEST['record_out']:'On-Demand');

	$compiled = $db->prepare('INSERT INTO zap (id, keyword, data) values (?,?,?)');
	$result = $db->executeMultiple($compiled,$zapfields);
	if(DB::IsError($result)) {
		die_freepbx($result->getMessage()."<br><br>error adding to ZAP table");	
	}
}

function core_devices_delzap($account) {
	global $db;
	global $currentFile;
	
	$sql = "DELETE FROM zap WHERE id = '$account'";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die_freepbx($result->getMessage().$sql);
	}
}

function core_devices_getzap($account) {
	global $db;
	$sql = "SELECT keyword,data FROM zap WHERE id = '$account'";
	$results = $db->getAssoc($sql);
	if(DB::IsError($results)) {
		$results = null;
	}
	return $results;
}
/* end page.devices.php functions */




function core_hint_get($account){
	//determine what devices this user is associated with
	$sql = "SELECT dial from devices where user = '{$account}'";
	$results = sql($sql,"getAll",DB_FETCHMODE_ASSOC);
	//print_r($results);
	
	//create an array of strings
	if (is_array($results)){
		foreach ($results as $result) {
			$dial[] = $result['dial'];
		}
	}
	
	//create a string with & delimiter
	if (isset($dial) && is_array($dial)){
		$hint = implode($dial,"&");
	} else {
		if (isset($results[0]['dial'])) {
			$hint = $results[0]['dial'];
		} else {
			$hint = null;
		}
	}
	
	return $hint;
}



/* begin page.users.php functions */

// get the existing extensions
// the returned arrays contain [0]:extension [1]:name
function core_users_list() {
	$results = sql("SELECT extension,name,voicemail FROM users ORDER BY extension","getAll");

	//only allow extensions that are within administrator's allowed range
	foreach($results as $result){
		if (checkRange($result[0])){
			$extens[] = array($result[0],$result[1]);
		}
	}
	
	if (isset($extens)) {
		sort($extens);
		return $extens;
	} else {
		return null;
	}
}

function core_sipname_check($sipname, $extension) {
	global $db;
	if (!isset($sipname) || trim($sipname)=='')
		return true;

	$sql = "SELECT sipname FROM users WHERE sipname = '$sipname' AND extension != '$extension'";
	$results = $db->getRow($sql,DB_FETCHMODE_ASSOC);
	if(DB::IsError($results)) {
        die_freepbx($results->getMessage().$sql);
	}
	
	if (isset($results['sipname']) && trim($results['sipname']) == $sipname) 
		return false;
	else
		return true;
}

function core_users_add($vars) {
	extract($vars);
	
	global $db;
	global $amp_conf;
	global $astman;

	$thisexten = isset($thisexten) ? $thisexten : '';

	//ensure this id is not already in use
	$extens = core_users_list();
	if(is_array($extens)) {
		foreach($extens as $exten) {
			if ($exten[0]===$extension) {
				echo "<script>javascript:alert('"._("This user")." ({$thisexten}) "._("or extension")." ({$extension}) "._("is already in use")."');</script>";
				return false;
			}
		}
	}

	// clean and check the did to make sure it is not being used by another extension or in did routing
	//
	$directdid = preg_replace("/[^0-9._XxNnZz\[\]\-\+]/" ,"", trim($directdid));
	if (trim($directdid) != "") {
		$existing=core_did_get($directdid,"","");
		$existing_directdid = empty($existing)?core_users_directdid_get($directdid):$existing;
		if (!empty($existing) || !empty($existing_directdid)) {
			if (!empty($existing)) {
				echo "<script>javascript:alert('"._("A route with this DID already exists:")." ".$existing['extension']."')</script>";
			} else {
				echo "<script>javascript:alert('"._("This DID is already associated with extension:")." ".$existing_directdid['extension']." (".$existing_directdid['name'].")')</script>";
			}
			return false;
		}
	}

	$sipname = preg_replace("/\s/" ,"", trim($sipname));
	if (! core_sipname_check($sipname, $extension)) {
		echo "<script>javascript:alert('"._("This sipname: {$sipname} is already in use")."');</script>";
		return false;
	}
	
	//build the recording variable
	$recording = "out=".$record_out."|in=".$record_in;
	
	//escape quotes and any other bad chars:
	if(!get_magic_quotes_gpc()) {
		$outboundcid = addslashes($outboundcid);
		$name = addslashes($name);
	}

	//if voicemail is enabled, set the box@context to use
	//havn't checked but why is voicemail needed on users anyway?  Doesn't exactly make it modular !
	if ( function_exists('voicemail_mailbox_get') ) {
		$vmbox = voicemail_mailbox_get($extension);
		if ( $vmbox == null ) {
			$voicemail = "novm";
			$vmx_state = "false";
		} else {
			$voicemail = $vmbox['vmcontext'];
		}
	}

	// MODIFICATION: (PL)
	// Added for directdid and didalert l for Alert Info distinctive ring)
	//
	// cleanup any non dial pattern characters prior to inserting into the database
	// then add directdid to the insert command.
	//
	// Clean replace any <> with () in display name - should have javascript stopping this but ...
	//
	$name = preg_replace(array('/</','/>/'), array('(',')'), trim($name));
	
	//insert into users table
	$sql="INSERT INTO users (extension,password,name,voicemail,ringtimer,noanswer,recording,outboundcid,directdid,didalert,faxexten,faxemail,answer,wait,privacyman,mohclass,sipname) values (\"";
	$sql.= "$extension\", \"";
	$sql.= isset($password)?$password:'';
	$sql.= "\", \"";
	$sql.= isset($name)?$name:'';
	$sql.= "\", \"";
	$sql.= isset($voicemail)?$voicemail:'default';
	$sql.= "\", \"";
	$sql.= isset($ringtimer)?$ringtimer:'';
	$sql.= "\", \"";
	$sql.= isset($noanswer)?$noanswer:'';
	$sql.= "\", \"";
	$sql.= isset($recording)?$recording:'';
	$sql.= "\", \"";
	$sql.= isset($outboundcid)?$outboundcid:'';
	$sql.= "\", \"";
	$sql.= isset($directdid)?$directdid:'';
	$sql.= "\", \"";
	$sql.= isset($didalert)?$didalert:'';

	$sql.= "\", \"";
	$sql.= isset($faxexten)?$faxexten:'';
	$sql.= "\", \"";
	$sql.= isset($faxemail)?$faxemail:'';
	$sql.= "\", \"";
	$sql.= isset($answer)?$answer:'';
	$sql.= "\", \"";
	$sql.= isset($wait)?$wait:'';
	$sql.= "\", \"";
	$sql.= isset($privacyman)?$privacyman:'';
	$sql.= "\", \"";
	$sql.= isset($mohclass)?$mohclass:'';
	$sql.= "\", \"";
	$sql.= isset($sipname)?$sipname:'';
	$sql.= "\")";
	sql($sql);

	//write to astdb
	if ($astman) {
		$cid_masquerade = (isset($cid_masquerade) && trim($cid_masquerade) != "")?trim($cid_masquerade):$extension;
		$astman->database_put("AMPUSER",$extension."/password",isset($password)?$password:'');
		$astman->database_put("AMPUSER",$extension."/ringtimer",isset($ringtimer)?$ringtimer:'');
		$astman->database_put("AMPUSER",$extension."/noanswer",isset($noanswer)?$noanswer:'');
		$astman->database_put("AMPUSER",$extension."/recording",isset($recording)?$recording:'');
		$astman->database_put("AMPUSER",$extension."/outboundcid",isset($outboundcid)?"\"".$outboundcid."\"":'');
		$astman->database_put("AMPUSER",$extension."/cidname",isset($name)?"\"".$name."\"":'');
		$astman->database_put("AMPUSER",$extension."/cidnum",$cid_masquerade);
		$astman->database_put("AMPUSER",$extension."/voicemail","\"".isset($voicemail)?$voicemail:''."\"");
		$astman->database_put("AMPUSER",$extension."/device","\"".((isset($device))?$device:'')."\"");

		if (trim($callwaiting) == 'enabled') {
			$astman->database_put("CW",$extension,"\"ENABLED\"");
		} else if (trim($callwaiting) == 'disabled') {
			$astman->database_del("CW",$extension);
		} else {
			echo "ERROR: this state should not exist<br>";
		}

		if ($vmx_state && $voicemail != "novm") {

			$unavail_mode="enabled";
			$busy_mode="disabled";
			$vmx_state=$astman->database_get("AMPUSER",$extension."/vmx/unavail/state");

			if (trim($vmx_state) == 'blocked') {

				$astman->database_put("AMPUSER", "$extension/vmx/unavail/state", "$unavail_mode");
				$astman->database_put("AMPUSER", "$extension/vmx/busy/state", "$busy_mode");

			} elseif (trim($vmx_state) != 'enabled' && trim($vmx_state) != 'disabled') {

				$repeat="1";
				$timeout="2";
				$vmxopts_timeout="";
				$loops="1";

				$mode="unavail";
				$astman->database_put("AMPUSER", "$extension/vmx/$mode/state", "$unavail_mode");
				$astman->database_put("AMPUSER", "$extension/vmx/$mode/repeat", "$repeat");
				$astman->database_put("AMPUSER", "$extension/vmx/$mode/timeout", "$timeout");
				$astman->database_put("AMPUSER", "$extension/vmx/$mode/vmxopts/timeout", "$vmxopts_timeout");
				$astman->database_put("AMPUSER", "$extension/vmx/$mode/loops", "$loops");

				$mode="busy";
				$astman->database_put("AMPUSER", "$extension/vmx/$mode/state", "$busy_mode");
				$astman->database_put("AMPUSER", "$extension/vmx/$mode/repeat", "$repeat");
				$astman->database_put("AMPUSER", "$extension/vmx/$mode/timeout", "$timeout");
				$astman->database_put("AMPUSER", "$extension/vmx/$mode/vmxopts/timeout", "$vmxopts_timeout");
				$astman->database_put("AMPUSER", "$extension/vmx/$mode/loops", "$loops");
				
			}
		} else {
			$vmx_state=$astman->database_get("AMPUSER",$extension."/vmx/unavail/state");
			if (trim($vmx_state) == 'enabled' || trim($vmx_state) == 'disabled' || trim($vmx_state) == 'blocked') {
				$astman->database_put("AMPUSER", "$extension/vmx/unavail/state", "blocked");
				$astman->database_put("AMPUSER", "$extension/vmx/busy/state", "blocked");
			}
		}
	} else {
		fatal("Cannot connect to Asterisk Manager with ".$amp_conf["AMPMGRUSER"]."/".$amp_conf["AMPMGRPASS"]);
	}
	return true;
}

function core_users_get($extension){
	global $db;
	global $amp_conf;
	global $astman;
	//get all the variables for the meetme
	$sql = "SELECT * FROM users WHERE extension = '$extension'";
	$results = $db->getRow($sql,DB_FETCHMODE_ASSOC);
	if(DB::IsError($results)) {
		die_freepbx($results->getMessage().$sql);
	}
	
	//explode recording vars
	$recording = explode("|",$results['recording']);
	if (isset($recording[1])) {
		$recout = substr($recording[0],4);
		$recin = substr($recording[1],3);
		$results['record_in']=$recin;
		$results['record_out']=$recout;
	} else {
		$results['record_in']='Adhoc';
		$results['record_out']='Adhoc';
	}
	if ($astman) {
		$cw = $astman->database_get("CW",$extension);
		$results['callwaiting'] = (trim($cw) == 'ENABLED') ? 'enabled' : 'disabled';
		$results['vmx_state']=$astman->database_get("AMPUSER",$extension."/vmx/unavail/state");
		$cid_masquerade=$astman->database_get("AMPUSER",$extension."/cidnum");
		$results['cid_masquerade'] = (trim($cid_masquerade) != "")?$cid_masquerade:$extension;
	} else {
		fatal("Cannot connect to Asterisk Manager with ".$amp_conf["AMPMGRUSER"]."/".$amp_conf["AMPMGRPASS"]);
	}

	return $results;
}

function core_users_del($extension){
	global $db;
	global $amp_conf;
	global $astman;
	
	//delete from devices table
	$sql="DELETE FROM users WHERE extension = \"$extension\"";
	$results = $db->query($sql);
	if(DB::IsError($results)) {
		die_freepbx($results->getMessage().$sql);
	}

	//delete details to astdb
	if ($astman) {
		$astman->database_del("AMPUSER",$extension."/password");
		$astman->database_del("AMPUSER",$extension."/ringtimer");
		$astman->database_del("AMPUSER",$extension."/noanswer");
		$astman->database_del("AMPUSER",$extension."/recording");
		$astman->database_del("AMPUSER",$extension."/outboundcid");
		$astman->database_del("AMPUSER",$extension."/cidname");
		$astman->database_del("AMPUSER",$extension."/cidnum");
		$astman->database_del("AMPUSER",$extension."/voicemail");
		$astman->database_del("AMPUSER",$extension."/device");
	} else {
		fatal("Cannot connect to Asterisk Manager with ".$amp_conf["AMPMGRUSER"]."/".$amp_conf["AMPMGRPASS"]);
	}
}

function core_users_directdid_get($directdid=""){
	if (empty($directdid)) {
		return array();
	} else {
		$sql = "SELECT * FROM users WHERE directdid = \"$directdid\"";
		return sql($sql,"getRow",DB_FETCHMODE_ASSOC);
	}
}

function core_users_cleanastdb($extension) {
	// This is called to remove any ASTDB traces of the user after a deletion. Otherwise,
	// call forwarding, call waiting settings could hang around and bite someone if they
	// recycle an extension. Is called from page.xtns and page.users.
	global $amp_conf;
	global $astman;

	if ($astman) {
		$astman->database_del("CW",$extension);
		$astman->database_del("CF",$extension);
		$astman->database_del("CFB",$extension);
		$astman->database_del("CFU",$extension);
		$astman->database_deltree("AMPUSER/".$extension."/vmx");

	} else {
		fatal("Cannot connect to Asterisk Manager with ".$amp_conf["AMPMGRUSER"]."/".$amp_conf["AMPMGRPASS"]);
	}
}

function core_users_edit($extension,$vars){
	global $db;
	global $amp_conf;
	global $astman;
	
	//I we are editing, we need to remember existing user<->device mapping, so we can delete and re-add
	if ($astman) {
		$ud = $astman->database_get("AMPUSER",$extension."/device");
		$vars['device'] = $ud;
	} else {
		fatal("Cannot connect to Asterisk Manager with ".$amp_conf["AMPMGRUSER"]."/".$amp_conf["AMPMGRPASS"]);
	}
	
	$directdid=$vars['directdid'];
	$directdid = preg_replace("/[^0-9._XxNnZz\[\]\-\+]/" ,"", trim($directdid));
	// clean and check the did to make sure it is not being used by another extension or in did routing
	//
	if (trim($directdid) != "") {
		$existing=core_did_get($directdid,"","");
		$existing_directdid = empty($existing)?core_users_directdid_get($directdid):$existing;
		if (!empty($existing) || (!empty($existing_directdid) && $existing_directdid['extension'] != $extension)) {
			if (!empty($existing)) {
				echo "<script>javascript:alert('"._("A route with this DID already exists:")." ".$existing['extension']."')</script>";
			} else {
				echo "<script>javascript:alert('"._("This DID is already associated with extension:")." ".$existing_directdid['extension']." (".$existing_directdid['name'].")')</script>";
			}
			return false;
		}
	}

	//delete and re-add
	if (core_sipname_check($vars['sipname'],$extension)) {
		core_users_del($extension);
		core_users_add($vars);
	}
	return true;
	
}

function core_directdid_list(){
	$sql = "SELECT extension, directdid, didalert, mohclass, faxexten, faxemail, answer, wait, privacyman FROM users WHERE directdid IS NOT NULL AND directdid != ''";
	return sql($sql,"getAll",DB_FETCHMODE_ASSOC);
}



/* end page.users.php functions */





/* begin page.trunks.php functions */

// we're adding ,don't require a $trunknum
function core_trunks_add($tech, $channelid, $dialoutprefix, $maxchans, $outcid, $peerdetails, $usercontext, $userconfig, $register, $keepcid, $failtrunk, $disabletrunk) {
	global $db;
	
	// find the next available ID
	$trunknum = 1;
	foreach(core_trunks_list() as $trunk) {
		if ($trunknum == ltrim($trunk[0],"OUT_")) { 
			$trunknum++;
		}
	}
	
	core_trunks_backendAdd($trunknum, $tech, $channelid, $dialoutprefix, $maxchans, $outcid, $peerdetails, $usercontext, $userconfig, $register, $keepcid, $failtrunk, $disabletrunk);
	
	return $trunknum;
}

function core_trunks_del($trunknum, $tech = null) {
	global $db;
	
	if ($tech === null) { // in EditTrunk, we get this info anyways
		$tech = core_trunks_getTrunkTech($trunknum);
	}

	//delete from globals table
	sql("DELETE FROM globals WHERE variable LIKE '%OUT_$trunknum' OR variable IN ('OUTCID_$trunknum','OUTMAXCHANS_$trunknum','OUTPREFIX_$trunknum','OUTKEEPCID_$trunknum','OUTFAIL_$trunknum','OUTDISABLE_$trunknum')");
	
	//write outids
	core_trunks_writeoutids();

	// conditionally, delete from iax or sip
	switch (strtolower($tech)) {
		case "iax":
		case "iax2":
			sql("DELETE FROM iax WHERE id = '9999$trunknum' OR id = '99999$trunknum' OR id = '9999999$trunknum'");
		break;
		case "sip": 
			sql("DELETE FROM sip WHERE id = '9999$trunknum' OR id = '99999$trunknum' OR id = '9999999$trunknum'");
		break;
	}
}

function core_trunks_edit($trunknum, $channelid, $dialoutprefix, $maxchans, $outcid, $peerdetails, $usercontext, $userconfig, $register, $keepcid, $failtrunk, $disabletrunk) {
	//echo "editTrunk($trunknum, $channelid, $dialoutprefix, $maxchans, $outcid, $peerdetails, $usercontext, $userconfig, $register)";
	$tech = core_trunks_getTrunkTech($trunknum);
	core_trunks_del($trunknum, $tech);
	core_trunks_backendAdd($trunknum, $tech, $channelid, $dialoutprefix, $maxchans, $outcid, $peerdetails, $usercontext, $userconfig, $register, $keepcid, $failtrunk, $disabletrunk);
}

// just used internally by addTrunk() and editTrunk()
//obsolete
function core_trunks_backendAdd($trunknum, $tech, $channelid, $dialoutprefix, $maxchans, $outcid, $peerdetails, $usercontext, $userconfig, $register, $keepcid, $failtrunk, $disabletrunk) {
	global $db;
	
	if  (is_null($dialoutprefix)) $dialoutprefix = ""; // can't be NULL
	
	//echo  "backendAddTrunk($trunknum, $tech, $channelid, $dialoutprefix, $maxchans, $outcid, $peerdetails, $usercontext, $userconfig, $register)";
	
	// change iax to "iax2" (only spot we actually store iax2, since its used by Dial()..)
	$techtemp = ((strtolower($tech) == "iax") ? "iax2" : $tech);
	$outval = (($techtemp == "custom") ? "AMP:".$channelid : strtoupper($techtemp).'/'.$channelid);
	
	$glofields = array(
			array('OUT_'.$trunknum, $outval),
			array('OUTPREFIX_'.$trunknum, $dialoutprefix),
			array('OUTMAXCHANS_'.$trunknum, $maxchans),
			array('OUTCID_'.$trunknum, $outcid),
			array('OUTKEEPCID_'.$trunknum, $keepcid),
			array('OUTFAIL_'.$trunknum, $failtrunk),
			array('OUTDISABLE_'.$trunknum, $disabletrunk),
			);
			
	unset($techtemp); 
	
	$compiled = $db->prepare('INSERT INTO globals (variable, value) values (?,?)');
	$result = $db->executeMultiple($compiled,$glofields);
	if(DB::IsError($result)) {
		die_freepbx($result->getMessage()."<br><br>".$sql);
	}
	
	core_trunks_writeoutids();

	$disable_flag = ($disabletrunk == "on")?1:0;
	
	switch (strtolower($tech)) {
		case "iax":
		case "iax2":
			core_trunks_addSipOrIax($peerdetails,'iax',$channelid,$trunknum,$disable_flag);
			if ($usercontext != ""){
				core_trunks_addSipOrIax($userconfig,'iax',$usercontext,'9'.$trunknum,$disable_flag);
			}
			if ($register != ""){
				core_trunks_addRegister($trunknum,'iax',$register,$disable_flag);
			}
		break;
		case "sip":
			core_trunks_addSipOrIax($peerdetails,'sip',$channelid,$trunknum,$disable_flag);
			if ($usercontext != ""){
				core_trunks_addSipOrIax($userconfig,'sip',$usercontext,'9'.$trunknum,$disable_flag);
			}
			if ($register != ""){
				core_trunks_addRegister($trunknum,'sip',$register,$disable_flag);
			}
		break;
	}	
}

function core_trunks_getTrunkTech($trunknum) {

	$results = sql("SELECT value FROM globals WHERE variable = 'OUT_".$trunknum."'","getAll");
	if (!$results) {
		return false;
	}
	if(strpos($results[0][0],"AMP:") === 0) {  //custom trunks begin with AMP:
		$tech = "custom";
	} else {
		$tech = strtolower( strtok($results[0][0],'/') ); // the technology.  ie: ZAP/g0 is ZAP
		
		if ($tech == "iax2") $tech = "iax"; // same thing, here
	}
	return $tech;
}

//add trunk info to sip or iax table
function core_trunks_addSipOrIax($config,$table,$channelid,$trunknum,$disable_flag=0) {
	global $db;
	
	$confitem['account'] = $channelid;
	$gimmieabreak = nl2br($config);
	$lines = split('<br />',$gimmieabreak);
	foreach ($lines as $line) {
		$line = trim($line);
		if (count(split('=',$line)) > 1) {
			$tmp = split('=',$line,2);
			$key=trim($tmp[0]);
			$value=trim($tmp[1]);
			if (isset($confitem[$key]) && !empty($confitem[$key]))
				$confitem[$key].="&".$value;
			else
				$confitem[$key]=$value;
		}
	}
	foreach($confitem as $k=>$v) {
		$dbconfitem[]=array($k,$v);
	}
	$compiled = $db->prepare("INSERT INTO $table (id, keyword, data, flags) values ('9999$trunknum',?,?,'$disable_flag')");
	$result = $db->executeMultiple($compiled,$dbconfitem);
	if(DB::IsError($result)) {
		die_freepbx($result->getMessage()."<br><br>INSERT INTO $table (id, keyword, data, flags) values ('9999$trunknum',?,?,'$disable_flag')");	
	}
}

//get unique trunks
function core_trunks_list($assoc = false) {
	// TODO: $assoc default to true, eventually..

	global $db;
	global $amp_conf;
	
	if ($amp_conf["AMPDBENGINE"] == "sqlite3")
	{
		// TODO: sqlite work arround - diego
		// TODO: WILL NOT WORK, need to remove the usage of SUBSTRING
		// need to reorder the trunks in PHP code
		$sqlstr  = "SELECT t.variable, t.value, d.value state FROM `globals` t ";
		$sqlstr .= "JOIN (SELECT x.variable, x.value FROM globals x WHERE x.variable LIKE 'OUTDISABLE\_%') d ";
		$sqlstr .= "ON substring(t.variable,5) = substring(d.variable,12) WHERE t.variable LIKE 'OUT\_%' ";
		$sqlstr .= "UNION ALL ";
		$sqlstr .= "SELECT v.variable, v.value, concat(substring(v.value,1,0),'off') state  FROM `globals` v ";
		$sqlstr .= "WHERE v.variable LIKE 'OUT\_%' AND concat('OUTDISABLE_',substring(v.variable,5)) NOT IN ";
		$sqlstr .= " ( SELECT variable from globals WHERE variable LIKE 'OUTDISABLE\_%' ) ";
		$sqlstr .= "ORDER BY variable";

		//$unique_trunks = sql("SELECT * FROM globals WHERE variable LIKE 'OUT_%' ORDER BY variable","getAll"); 
		$unique_trunks = sql($sqlstr,"getAll"); 
	}
	else
	{
		// we have to escape _ for mysql: normally a wildcard
		$sqlstr  = "SELECT t.variable, t.value, d.value state FROM `globals` t ";
		$sqlstr .= "JOIN (SELECT x.variable, x.value FROM globals x WHERE x.variable LIKE 'OUTDISABLE\\\_%') d ";
		$sqlstr .= "ON substring(t.variable,5) = substring(d.variable,12) WHERE t.variable LIKE 'OUT\\\_%' ";
		$sqlstr .= "UNION ALL ";
		$sqlstr .= "SELECT v.variable, v.value, concat(substring(v.value,1,0),'off') state  FROM `globals` v ";
		$sqlstr .= "WHERE v.variable LIKE 'OUT\\\_%' AND concat('OUTDISABLE_',substring(v.variable,5)) NOT IN ";
		$sqlstr .= " ( SELECT variable from globals WHERE variable LIKE 'OUTDISABLE\\\_%' ) ";
		$sqlstr .= "ORDER BY RIGHT( variable, LENGTH( variable ) - 4 )+0";

		//$unique_trunks = sql("SELECT * FROM globals WHERE variable LIKE 'OUT\\\_%' ORDER BY RIGHT( variable, LENGTH( variable ) - 4 )+0","getAll"); 
		$unique_trunks = sql($sqlstr,"getAll"); 
	}

	//if no trunks have ever been defined, then create the proper variables with the default zap trunk
	if (count($unique_trunks) == 0) 
	{
		//If all trunks have been deleted from admin, dialoutids might still exist
		sql("DELETE FROM globals WHERE variable = 'DIALOUTIDS'");
	
		$glofields = array(array('OUT_1','ZAP/g0'),
							array('DIAL_OUT_1','9'),
							array('DIALOUTIDS','1'));
		$compiled = $db->prepare('INSERT INTO globals (variable, value) values (?,?)');
		$result = $db->executeMultiple($compiled,$glofields);
		if(DB::IsError($result))
		{
			die_freepbx($result->getMessage()."<br><br>".$sql);	
		}
		$unique_trunks[] = array('OUT_1','ZAP/g0');
	}
	// asort($unique_trunks);

	if ($assoc) {
		$trunkinfo = array();

		foreach ($unique_trunks as $trunk) {
			list($tech,$name) = explode('/',$trunk[1]);
			$trunkinfo[$name] = array(
				'name' => $name,
				'tech' => $tech,
				'globalvar' => $trunk[0], // ick
				'value' => $trunk[2], // ??  no idea what this is.
			);	
		}
		return $trunkinfo;
	} else {
		return $unique_trunks;
	}
}

//write the OUTIDS global variable (used in dialparties.agi)
function core_trunks_writeoutids() {
	// we have to escape _ for mysql: normally a wildcard
	$unique_trunks = sql("SELECT variable FROM globals WHERE variable LIKE 'OUT\\\_%'","getAll"); 

	$outids = null; // Start off with nothing
	foreach ($unique_trunks as $unique_trunk) {
		$outid = strtok($unique_trunk[0],"_");
		$outid = strtok("_");
		$outids .= $outid ."/";
	}
	
	sql("UPDATE globals SET value = '$outids' WHERE variable = 'DIALOUTIDS'");
}

function core_trunks_addRegister($trunknum,$tech,$reg,$disable_flag=0) {
	sql("INSERT INTO $tech (id, keyword, data, flags) values ('9999999$trunknum','register','$reg','$disable_flag')");
}


function core_trunks_addDialRules($trunknum, $dialrules) {
	$values = array();
	$i = 1;
	foreach ($dialrules as $rule) {
		$values["rule".$i++] = $rule;
	}
	
	$conf = core_trunks_readDialRulesFile();
	
	// rewrite for this trunk
	$conf["trunk-".$trunknum] = $values;
	
	core_trunks_writeDialRulesFile($conf);
}

function core_trunks_readDialRulesFile() {
	global $amp_conf;
	$localPrefixFile = $amp_conf['ASTETCDIR']."/localprefixes.conf";
	
	core_trunks_parse_conf($localPrefixFile, $conf, $section);
	
	return $conf;
}

function core_trunks_writeDialRulesFile($conf) {
	global $amp_conf;
	$localPrefixFile = $amp_conf['ASTETCDIR']."/localprefixes.conf";
	
	$fd = fopen($localPrefixFile,"w");
	foreach ($conf as $section=>$values) {
		fwrite($fd, "[".$section."]\n");
		foreach ($values as $key=>$value) {
			fwrite($fd, $key."=".$value."\n");
		}
		fwrite($fd, "\n");
	}
	fclose($fd);
}

function core_trunks_parse_conf($filename, &$conf, &$section) {
	if (is_null($conf)) {
		$conf = array();
	}
	if (is_null($section)) {
		$section = "general";
	}
	
	if (file_exists($filename)) {
		$fd = fopen($filename, "r");
		while ($line = fgets($fd, 1024)) {
			if (preg_match("/^\s*([a-zA-Z0-9-_]+)\s*=\s*(.*?)\s*([;#].*)?$/",$line,$matches)) {
				// name = value
				// option line
				$conf[$section][ $matches[1] ] = $matches[2];
			} else if (preg_match("/^\s*\[(.+)\]/",$line,$matches)) {
				// section name
				$section = strtolower($matches[1]);
			} else if (preg_match("/^\s*#include\s+(.*)\s*([;#].*)?/",$line,$matches)) {
				// include another file
				
				if ($matches[1][0] == "/") {
					// absolute path
					$filename = $matches[1];
				} else {
					// relative path
					$filename =  dirname($filename)."/".$matches[1];
				}
				
				core_trunks_parse_conf($filename, $conf, $section);
			}
		}
	}
}

function core_trunks_getTrunkTrunkName($trunknum) {
	$results = sql("SELECT value FROM globals WHERE variable = 'OUT_".$trunknum."'","getAll");
	if (!$results) {
		return false;
	}
	
	if(strpos($results[0][0],"AMP:") === 0) {  //custom trunks begin with AMP:
		$tname = substr($results[0][0],4);
	} else {
	strtok($results[0][0],'/');
		$tname = strtok('/'); // the text _after_ technology.  ie: ZAP/g0 is g0
	}
	return $tname;
}

//get and print peer details (prefixed with 4 9's)
function core_trunks_getTrunkPeerDetails($trunknum) {
	global $db;
	
	$tech = core_trunks_getTrunkTech($trunknum);
	
	if ($tech == "zap") return ""; // zap has no details
	
	$results = sql("SELECT keyword,data FROM $tech WHERE id = '9999$trunknum' ORDER BY id","getAll");
	
	foreach ($results as $result) {
		if ($result[0] != 'account') {
			if (isset($confdetail))
				$confdetail .= $result[0] .'='. $result[1] . "\n";
			else
				$confdetail = $result[0] .'='. $result[1] . "\n";
		}
	}
	return $confdetail;
}

//get trunk user context (prefixed with 5 9's)
function core_trunks_getTrunkUserContext($trunknum) {
	$tech = core_trunks_getTrunkTech($trunknum);
	if ($tech == "zap") return ""; // zap has no account
	
	$results = sql("SELECT keyword,data FROM $tech WHERE id = '99999$trunknum' ORDER BY id","getAll");

	foreach ($results as $result) {
		if ($result[0] == 'account') {
			$account = $result[1];
		}
	}
	return isset($account)?$account:null;
}

//get and print user config (prefixed with 5 9's)
function core_trunks_getTrunkUserConfig($trunknum) {
	global $db;
	
	$tech = core_trunks_getTrunkTech($trunknum);
	
	if ($tech == "zap") return ""; // zap has no details
	
	$results = sql("SELECT keyword,data FROM $tech WHERE id = '99999$trunknum' ORDER BY id","getAll");

	foreach ($results as $result) {
		if ($result[0] != 'account') {
			if (isset($confdetail))
				$confdetail .= $result[0] .'='. $result[1] . "\n";
			else
				$confdetail = $result[0] .'='. $result[1] . "\n";
		}
	}
	return isset($confdetail)?$confdetail:null;
}

//get trunk account register string
function core_trunks_getTrunkRegister($trunknum) {
	$tech = core_trunks_getTrunkTech($trunknum);
	
	if ($tech == "zap") return ""; // zap has no register
	
	$results = sql("SELECT keyword,data FROM $tech WHERE id = '9999999$trunknum'","getAll");

	foreach ($results as $result) {
			$register = $result[1];
	}
	return isset($register)?$register:null;
}

function core_trunks_getDialRules($trunknum) {
	$conf = core_trunks_readDialRulesFile();
	if (isset($conf["trunk-".$trunknum])) {
		return $conf["trunk-".$trunknum];
	}
	return false;
}

//get outbound routes for a given trunk
function core_trunks_gettrunkroutes($trunknum) {
	global $amp_conf;

	if ($amp_conf["AMPDBENGINE"] == "sqlite3")
		$sql_code = "SELECT DISTINCT              context, priority FROM extensions WHERE context LIKE 'outrt-%' AND (args LIKE 'dialout-trunk,".$trunknum.",%' OR args LIKE 'dialout-enum,".$trunknum.",%') ORDER BY context";
	else
		$sql_code = "SELECT DISTINCT SUBSTRING(context,7), priority FROM extensions WHERE context LIKE 'outrt-%' AND (args LIKE 'dialout-trunk,".$trunknum.",%' OR args LIKE 'dialout-enum,".$trunknum.",%') ORDER BY context";

	$results = sql( $sql_code, "getAll" );

	foreach ($results as $row) {
		// original code was:
		// 	$routes[$row[0]] = $row[1];
		// but substring is not supported in sqlite3.
		// how about we remove the 2nd part of the "if"? and use the same code on all DB's?

		$t = ($amp_conf["AMPDBENGINE"] == "sqlite3") ? substr( $row[0], 7 ) : $row[0];
		$r = $row[1];
		$routes[ $r ] = $t;

	}
	// array(routename=>priority)
	return isset($routes)?$routes:null;
}

function core_trunks_deleteDialRules($trunknum) {
	$conf = core_trunks_readDialRulesFile();
	
	// remove rules for this trunk
	unset($conf["trunk-".$trunknum]);
	
	core_trunks_writeDialRulesFile($conf);
}

/* end page.trunks.php functions */


/* begin page.routing.php functions */

//get unique outbound route names
function core_routing_getroutenames() 
{
	global $amp_conf;
	
	if ($amp_conf["AMPDBENGINE"] == "sqlite3") 
	{
		// SUBSTRING is not supported under sqlite3, we need to filter
		// this in php. I am not sure why "6" and not "7"
		// but I don't really care -> it works :)
		$results = sql("SELECT DISTINCT context FROM extensions WHERE context LIKE 'outrt-%' ORDER BY context ","getAll");
		foreach( array_keys($results) as $idx )
		{
			 $results[$idx][0] = substr( $results[$idx][0], 6);
		}
	}
	else
	{
		// we SUBSTRING() to remove "outrt-"
		$results = sql("SELECT DISTINCT SUBSTRING(context,7) FROM extensions WHERE context LIKE 'outrt-%' ORDER BY context ","getAll");
	}


	if (count($results) == 0) {
		// see if they're still using the old dialprefix method
		$results = sql("SELECT variable,value FROM globals WHERE variable LIKE 'DIAL\\\_OUT\\\_%'","getAll");
		// we SUBSTRING() to remove "outrt-"
		
		if (count($results) > 0) {
			// yes, they are using old method, let's update
			
			// get the default trunk
			$results_def = sql("SELECT value FROM globals WHERE variable = 'OUT'","getAll");
			
			if (preg_match("/{OUT_(\d+)}/", $results_def[0][0], $matches)) {
				$def_trunk = $matches[1];
			} else {
				$def_trunk = "";
			}
			
			$default_patterns = array(	// default patterns that used to be in extensions.conf
						"NXXXXXX",
						"NXXNXXXXXX",
						"1800NXXXXXX",
						"1888NXXXXXX",
						"1877NXXXXXX",
						"1866NXXXXXX",
						"1NXXNXXXXXX",
						"011.",
						"911",
						"411",
						"311",
						);
			
			foreach ($results as $temp) {
				// temp[0] is "DIAL_OUT_1"
				// temp[1] is the dial prefix
				
				$trunknum = substr($temp[0],9);
				
				$name = "route".$trunknum;
				
				$trunks = array(1=>"OUT_".$trunknum); // only one trunk to use
				
				$patterns = array();
				foreach ($default_patterns as $pattern) {
					$patterns[] = $temp[1]."|".$pattern;
				}
				
				if ($trunknum == $def_trunk) {
					// this is the default trunk, add the patterns with no prefix
					$patterns = array_merge($patterns, $default_patterns);
				}
				
				// add this as a new route
				core_routing_add($name, $patterns, $trunks,"new");
			}
			
			
			// delete old values
			sql("DELETE FROM globals WHERE (variable LIKE 'DIAL\\\_OUT\\\_%') OR (variable = 'OUT') ");

			// we need to re-generate extensions_additional.conf
			// i'm not sure how to do this from here
			
			// re-run our query
			$results = sql("SELECT DISTINCT SUBSTRING(context,7) FROM extensions WHERE context LIKE 'outrt-%' ORDER BY context ","getAll");
			// we SUBSTRING() to remove "outrt-"
		}
		
	} // else, it just means they have no routes.
	
	return $results;
}

function core_routing_setroutepriority($routepriority, $reporoutedirection, $reporoutekey)
{
	global $db, $amp_conf;
	$counter=-1;
	foreach ($routepriority as $tresult) 
	{
		$counter++;
		if (($counter==($reporoutekey-1)) && ($reporoutedirection=="up")) {
			// swap this one with the one before (move up)
			$temproute = $routepriority[$counter];
			$routepriority[ $counter ] = $routepriority[ $counter+1 ];
			$routepriority[ $counter+1 ] = $temproute;
			
		} else if (($counter==($reporoutekey)) && ($reporoutedirection=="down")) {
			// swap this one with the one after (move down)
			$temproute = $routepriority[ $counter+1 ];
			$routepriority[ $counter+1 ] = $routepriority[ $counter ];
			$routepriority[ $counter ] = $temproute;
		}
	}
	unset($temptrunk);
	$routepriority = array_values($routepriority); // resequence our numbers
	$counter=0;
	foreach ($routepriority as $tresult) {
		$order=core_routing_setroutepriorityvalue($counter++);
		$sql = sprintf("Update extensions set context='outrt-%s-%s' WHERE context='outrt-%s'",$order,substr($tresult[0],4), $tresult[0]);
		$result = $db->query($sql); 
		if(DB::IsError($result)) {     
			die_freepbx($result->getMessage()); 
		}
	}
	
	// Delete and readd the outbound-allroutes entries
	$sql = "delete from  extensions WHERE context='outbound-allroutes'";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
        	die_freepbx($result->getMessage().$sql);
	}
	
	$sql = "SELECT DISTINCT context FROM extensions WHERE context like 'outrt-%' ORDER BY context";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
		die_freepbx($results->getMessage());
	}

	$priority_loops=1;	
	foreach ($results as $row) {
		$sql = "INSERT INTO extensions (context, extension, priority, application, args, descr, flags) VALUES ";
		$sql .= "('outbound-allroutes', ";
		$sql .= "'include', ";
		$sql .= "'".$priority_loops++."', ";
		$sql .= "'".$row[0]."', ";
		$sql .= "'', ";
		$sql .= "'', ";
		$sql .= "'2')";
	
		//$sql = sprintf("Update extensions set application='outrt-%s-%s' WHERE context='outbound-allroutes' and  application='outrt-%s'",$order,substr($tresult[0],4), $tresult[0]);
		$result = $db->query($sql); 
		if(DB::IsError($result)) {     
			die_freepbx($result->getMessage(). $sql); 
 		}
	}
	
	if ( $amp_conf["AMPDBENGINE"] == "sqlite3")
		$sql = "SELECT DISTINCT context FROM extensions WHERE context LIKE 'outrt-%' ORDER BY context ";
	else
		$sql = "SELECT DISTINCT SUBSTRING(context,7) FROM extensions WHERE context LIKE 'outrt-%' ORDER BY context ";

        // we SUBSTRING() to remove "outrt-"
        $routepriority = $db->getAll($sql);
        if(DB::IsError($routepriority)) {
                die_freepbx($routepriority->getMessage());
        }

	// TODO: strip the context on the sqlite3 backend
	// not sure where does it effects, since this is working on my setup...
	// welcome to funky town
        return ($routepriority);
}

function core_routing_setroutepriorityvalue($key)
{
	$key=$key+1;
	if ($key<10)
		$prefix = sprintf("00%d",$key);
	else if ((9<$key)&&($key<100))
		$prefix = sprintf("0%d",$key);
	else if ($key>100)
		$prefix = sprintf("%d",$key);
	return ($prefix);
}


function core_routing_add($name, $patterns, $trunks, $method, $pass, $emergency = "", $intracompany = "", $mohsilence = "") {

	global $db;

	$trunktech=array();

	//Retrieve each trunk tech for later lookup
	$sql="select * from globals WHERE variable LIKE 'OUT\\_%'";
        $result = $db->getAll($sql);
        if(DB::IsError($result)) {
		die_freepbx($result->getMessage());
	}
	foreach($result as $tr) {
		$tech = strtok($tr[1], "/");
		$trunktech[$tr[0]]=$tech;
	}
	
 	if ($method=="new") {	
		$sql="select DISTINCT context FROM extensions WHERE context LIKE 'outrt-%' ORDER BY context";
		$routepriority = $db->getAll($sql);
		if(DB::IsError($result)) {
			die_freepbx($result->getMessage());
		}
		$order=core_routing_setroutepriorityvalue(count($routepriority));
		$name = sprintf ("%s-%s",$order,$name);
	}
	$trunks = array_values($trunks); // probably already done, but it's important for our dialplan

	
	foreach ($patterns as $pattern) {
		if (false !== ($pos = strpos($pattern,"|"))) {
			// we have a | meaning to not pass the digits on
			// (ie, 9|NXXXXXX should use the pattern _9NXXXXXX but only pass NXXXXXX, not the leading 9)
			
			$pattern = str_replace("|","",$pattern); // remove all |'s
			$exten = "EXTEN:".$pos; // chop off leading digit
		} else {
			// we pass the full dialed number as-is
			$exten = "EXTEN"; 
		}
		
		if (!preg_match("/^[0-9*]+$/",$pattern)) { 
			// note # is not here, as asterisk doesn't recoginize it as a normal digit, thus it requires _ pattern matching
			
			// it's not strictly digits, so it must have patterns, so prepend a _
			$pattern = "_".$pattern;
		}
		
		// 1st priority is emergency dialing variable (if set)
		if(!empty($emergency)) {
			$startpriority = 1;
			$sql = "INSERT INTO extensions (context, extension, priority, application, args, descr) VALUES ";
			$sql .= "('outrt-".$name."', ";
			$sql .= "'".$pattern."', ";
			$sql .= "'".$startpriority."', ";
			$sql .= "'SetVar', ";
			$sql .= "'EMERGENCYROUTE=YES', ";
			$sql .= "'Use Emergency CID for device')";
			$result = $db->query($sql);
			if(DB::IsError($result)) {
				die_freepbx($result->getMessage());
			}
		} else {
			$startpriority = 0;
		}

		// Next Priority (either first or second depending on above)
		if(!empty($intracompany)) {
			   $startpriority += 1;
			   $sql = "INSERT INTO extensions (context, extension, priority, application, args, descr) VALUES ";
			   $sql .= "('outrt-".$name."', ";
			   $sql .= "'".$pattern."', ";
			   $sql .= "'".$startpriority."', ";
			   $sql .= "'SetVar', ";
			   $sql .= "'INTRACOMPANYROUTE=YES', ";
			   $sql .= "'Preserve Intenal CID Info')";
			   $result = $db->query($sql);
				if(DB::IsError($result)) {
					   die_freepbx($result->getMessage());
				}
		}

		// Next Priority (either first, second or third depending on above)
		if(!empty($mohsilence) && trim($mohsilence) != 'default') {
			   $startpriority += 1;
			   $sql = "INSERT INTO extensions (context, extension, priority, application, args, descr) VALUES ";
			   $sql .= "('outrt-".$name."', ";
			   $sql .= "'".$pattern."', ";
			   $sql .= "'".$startpriority."', ";
			   $sql .= "'SetVar', ";
			   $sql .= "'MOHCLASS=".$mohsilence."', ";
			   $sql .= "'Do not play moh on this route')";
			   $result = $db->query($sql);
				if(DB::IsError($result)) {
					   die_freepbx($result->getMessage());
				}
		}

		$first_trunk = 1;
		foreach ($trunks as $priority => $trunk) {
			$priority += $startpriority;
			$priority += 1; // since arrays are 0-based, but we want priorities to start at 1
			
			$sql = "INSERT INTO extensions (context, extension, priority, application, args) VALUES ";
			$sql .= "('outrt-".$name."', ";
			$sql .= "'".$pattern."', ";
			$sql .= "'".$priority."', ";
			$sql .= "'Macro', ";
			if ($first_trunk)
				$pass_str = $pass;
			else
				$pass_str = "";

			if ($trunktech[$trunk] == "ENUM")
				$sql .= "'dialout-enum,".substr($trunk,4).",\${".$exten."},".$pass_str."'"; // cut off OUT_ from $trunk
			else
				$sql .= "'dialout-trunk,".substr($trunk,4).",\${".$exten."},".$pass_str."'"; // cut off OUT_ from $trunk
			$sql .= ")";
			
			$result = $db->query($sql);
			if(DB::IsError($result)) {
				die_freepbx($result->getMessage());
			}
			//To identify the first trunk in a pattern
			//so that passwords are in the first trunk in
			//each pattern
			$first_trunk = 0;
		}
		
		$priority += 1;
		$sql = "INSERT INTO extensions (context, extension, priority, application, args, descr) VALUES ";
		$sql .= "('outrt-".$name."', ";
		$sql .= "'".$pattern."', ";
		$sql .= "'".$priority."', ";
		$sql .= "'Macro', ";
		$sql .= "'outisbusy', ";
		$sql .= "'No available circuits')";
		
		$result = $db->query($sql);
		if(DB::IsError($result)) {
			die_freepbx($result->getMessage());
		}
	}

	
	// add an include=>outrt-$name  to [outbound-allroutes]:
	
	// we have to find the first available priority.. priority doesn't really matter for the include, but
	// there is a unique index on (context,extension,priority) so if we don't do this we can't put more than
	// one route in the outbound-allroutes context.
	$sql = "SELECT priority FROM extensions WHERE context = 'outbound-allroutes' AND extension = 'include'";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
		die_freepbx($results->getMessage());
	}
	$priorities = array();
	foreach ($results as $row) {
		$priorities[] = $row[0];
	}
	for ($priority = 1; in_array($priority, $priorities); $priority++);
	
	// $priority should now be the lowest available number
	
	$sql = "INSERT INTO extensions (context, extension, priority, application, args, descr, flags) VALUES ";
	$sql .= "('outbound-allroutes', ";
	$sql .= "'include', ";
	$sql .= "'".$priority."', ";
	$sql .= "'outrt-".$name."', ";
	$sql .= "'', ";
	$sql .= "'', ";
	$sql .= "'2')";
	
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die_freepbx($priority.$result->getMessage());
	}
	
}

function core_routing_edit($name, $patterns, $trunks, $pass, $emergency="", $intracompany = "", $mohsilence="") {
	core_routing_del($name);
	core_routing_add($name, $patterns, $trunks,"edit", $pass, $emergency, $intracompany, $mohsilence);
}

function core_routing_del($name) {
	global $db;
	$sql = "DELETE FROM extensions WHERE context = 'outrt-".$name."'";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die_freepbx($result->getMessage());
	}
	
	$sql = "DELETE FROM extensions WHERE context = 'outbound-allroutes' AND application = 'outrt-".$name."' ";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die_freepbx($result->getMessage());
	}
	
	return $result;
}

function core_routing_rename($oldname, $newname) {
	global $db;

	$route_prefix=substr($oldname,0,4);
	$newname=$route_prefix.$newname;
	$sql = "SELECT context FROM extensions WHERE context = 'outrt-".$newname."'";
	$results = $db->getAll($sql);
	if (count($results) > 0) {
		// there's already a route with this name
		return false;
	}
	
	$sql = "UPDATE extensions SET context = 'outrt-".$newname."' WHERE context = 'outrt-".$oldname."'";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die_freepbx($result->getMessage());
	}
        $mypriority=sprintf("%d",$route_prefix);	
	$sql = "UPDATE extensions SET application = 'outrt-".$newname."', priority = '$mypriority' WHERE context = 'outbound-allroutes' AND application = 'outrt-".$oldname."' ";
	$result = $db->query($sql);
	if(DB::IsError($result)) {
		die_freepbx($result->getMessage());
	}
	
	return true;
}

//get unique outbound route patterns for a given context
function core_routing_getroutepatterns($route) {
	global $db;
	$sql = "SELECT extension, args FROM extensions WHERE context = 'outrt-".$route."' AND (args LIKE 'dialout-trunk%' OR args LIKE'dialout-enum%') ORDER BY extension ";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
		die_freepbx($results->getMessage());
	}
	
	$patterns = array();
	foreach ($results as $row) {
		if ($row[0][0] == "_") {
			// remove leading _
			$pattern = substr($row[0],1);
		} else {
			$pattern = $row[0];
		}
		
		if (preg_match("/{EXTEN:(\d+)}/", $row[1], $matches)) {
			// this has a digit offset, we need to insert a |
			$pattern = substr($pattern,0,$matches[1])."|".substr($pattern,$matches[1]);
		}
		
		$patterns[] = $pattern;
	}
	return array_unique($patterns);
}

//get unique outbound route trunks for a given context
function core_routing_getroutetrunks($route) {
	global $db;
	$sql = "SELECT DISTINCT args FROM extensions WHERE context = 'outrt-".$route."' AND (args LIKE 'dialout-trunk,%' OR args LIKE 'dialout-enum,%') ORDER BY CAST(priority as UNSIGNED) ";
	$results = $db->getAll($sql);
	if(DB::IsError($results)) {
		die_freepbx($results->getMessage());
	}
	
	$trunks = array();
	foreach ($results as $row) {
		if (preg_match('/^dialout-trunk,(\d+)/', $row[0], $matches)) {
			// check in_array -- even though we did distinct
			// we still might get ${EXTEN} and ${EXTEN:1} if they used | to split a pattern
			if (!in_array("OUT_".$matches[1], $trunks)) {
				$trunks[] = "OUT_".$matches[1];
			}
		} else if (preg_match('/^dialout-enum,(\d+)/', $row[0], $matches)) {
			if (!in_array("OUT_".$matches[1], $trunks)) {
				$trunks[] = "OUT_".$matches[1];
			}
		}
	}
	return $trunks;
}


//get password for this route
function core_routing_getroutepassword($route) {
	global $db;
	$sql = "SELECT DISTINCT args FROM extensions WHERE context = 'outrt-".$route."' AND (args LIKE 'dialout-trunk,%' OR args LIKE 'dialout-enum,%') ORDER BY CAST(priority as UNSIGNED) ";
	$results = $db->getOne($sql);
	if(DB::IsError($results)) {
		die_freepbx($results->getMessage());
	}
	if (preg_match('/^.*,.*,.*,(\d+|\/\S+)/', $results, $matches)) {
		$password = $matches[1];
	} else {
		$password = "";
	}
	
	return $password;
}

//get emergency state for this route
function core_routing_getrouteemergency($route) {
	global $db;
	$sql = "SELECT DISTINCT args FROM extensions WHERE context = 'outrt-".$route."' AND (args LIKE 'EMERGENCYROUTE%') ";
	$results = $db->getOne($sql);
	if(DB::IsError($results)) {
		die_freepbx($results->getMessage());
	}
	if (preg_match('/^.*=(.*)/', $results, $matches)) {
		$emergency = $matches[1];
	} else {
		$emergency = "";
	}
	
	return $emergency;
}

//get intracompany routing status for this route
function core_routing_getrouteintracompany($route) {

       global $db;
       $sql = "SELECT DISTINCT args FROM extensions WHERE context = 'outrt-".$route."' AND (args LIKE 'INTRACOMPANYROUTE%') ";
       $results = $db->getOne($sql);
       if(DB::IsError($results)) {
               die_freepbx($results->getMessage());
       }
       if (preg_match('/^.*=(.*)/', $results, $matches)) {
               $intracompany = $matches[1];
       } else {
               $intracompany = "";
       }
       return $intracompany;
}

//get mohsilence routing status for this route
function core_routing_getroutemohsilence($route) {

       global $db;
       $sql = "SELECT DISTINCT args FROM extensions WHERE context = 'outrt-".$route."' AND (args LIKE 'MOHCLASS%') ";
       $results = $db->getOne($sql);
       if(DB::IsError($results)) {
               die_freepbx($results->getMessage());
       }
       if (preg_match('/^.*=(.*)/', $results, $matches)) {
               $mohsilence = $matches[1];
       } else {
               $mohsilence = "";
       }
       return $mohsilence;
}

function general_get_zonelist() {
	return array(
 array ( "name" => "Austria",  "iso" => "at", "conf" => "ringcadence = 1000,5000\ndial = 420\nbusy = 420/400,0/400\nring = 420/1000,0/5000\ncongestion = 420/200,0/200\ncallwaiting = 420/40,0/1960\ndialrecall = 420\nrecord = 1400/80,0/14920\ninfo = 950/330,1450/330,1850/330,0/1000\nstutter = 380+420\n"),
 array ( "name" => "Australia",  "iso" => "au", "conf" => "ringcadence = 400,200,400,2000\ndial = 413+438\nbusy = 425/375,0/375\nring = 413+438/400,0/200,413+438/400,0/2000\ncongestion = 425/375,0/375,420/375,0/375\ncallwaiting = 425/200,0/200,425/200,0/4400\ndialrecall = 413+438\nrecord = !425/1000,!0/15000,425/360,0/15000\ninfo = 425/2500,0/500\nstd = !525/100,!0/100,!525/100,!0/100,!525/100,!0/100,!525/100,!0/100,!525/100\nfacility = 425\nstutter = 413+438/100,0/40\nringmobile = 400+450/400,0/200,400+450/400,0/2000\n"),
 array ( "name" => "Brazil",  "iso" => "br", "conf" => "ringcadence = 1000,4000\ndial = 425\nbusy = 425/250,0/250\nring = 425/1000,0/4000\ncongestion = 425/250,0/250,425/750,0/250\ncallwaiting = 425/50,0/1000\ndialrecall = 350+440\nrecord = 425/250,0/250\ninfo = 950/330,1400/330,1800/330\nstutter = 350+440\n"),
 array ( "name" => "Belgium",  "iso" => "be", "conf" => "ringcadence = 1000,3000\ndial = 425\nbusy = 425/500,0/500\nring = 425/1000,0/3000\ncongestion = 425/167,0/167\ncallwaiting = 1400/175,0/175,1400/175,0/3500\ndialrecall = !350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,350+440\nrecord = 1400/500,0/15000\ninfo = 900/330,1400/330,1800/330,0/1000\nstutter = 425/1000,0/250\n"),
 array ( "name" => "Switzerland",  "iso" => "ch", "conf" => "ringcadence = 1000,4000\ndial = 425\nbusy = 425/500,0/500\nring = 425/1000,0/4000\ncongestion = 425/200,0/200\ncallwaiting = 425/200,0/200,425/200,0/4000\ndialrecall = !425/100,!0/100,!425/100,!0/100,!425/100,!0/100,425\nrecord = 1400/80,0/15000\ninfo = 950/330,1400/330,1800/330,0/1000\nstutter = 425+340/1100,0/1100\n"),
 array ( "name" => "Chile",  "iso" => "cl", "conf" => "ringcadence = 1000,3000\ndial = 400\nbusy = 400/500,0/500\nring = 400/1000,0/3000\ncongestion = 400/200,0/200\ncallwaiting = 400/250,0/8750\ndialrecall = !400/100,!0/100,!400/100,!0/100,!400/100,!0/100,400\nrecord = 1400/500,0/15000\ninfo = 950/333,1400/333,1800/333,0/1000\nstutter = !400/100,!0/100,!400/100,!0/100,!400/100,!0/100,!400/100,!0/100,!400/100,!0/100,!400/100,!0/100,400\n"),
 array ( "name" => "China",  "iso" => "cn", "conf" => "ringcadence = 1000,4000\ndial = 450\nbusy = 450/350,0/350\nring = 450/1000,0/4000\ncongestion = 450/700,0/700\ncallwaiting = 450/400,0/4000\ndialrecall = 450\nrecord = 950/400,0/10000\ninfo = 450/100,0/100,450/100,0/100,450/100,0/100,450/400,0/400\nstutter = 450+425\n"),
 array ( "name" => "Czech Republic",  "iso" => "cz", "conf" => "ringcadence = 1000,4000\ndial = 425/330,0/330,425/660,0/660\nbusy = 425/330,0/330\nring = 425/1000,0/4000\ncongestion = 425/165,0/165\ncallwaiting = 425/330,0/9000\ndialrecall = !425/100,!0/100,!425/100,!0/100,!425/100,!0/100,425/330,0/330,425/660,0/660\nrecord = 1400/500,0/14000\ninfo = 950/330,0/30,1400/330,0/30,1800/330,0/1000\nstutter = 425/450,0/50\n"),
 array ( "name" => "Germany",  "iso" => "de", "conf" => "ringcadence = 1000,4000\ndial = 425\nbusy = 425/480,0/480\nring = 425/1000,0/4000\ncongestion = 425/240,0/240\ncallwaiting = !425/200,!0/200,!425/200,!0/5000,!425/200,!0/200,!425/200,!0/5000,!425/200,!0/200,!425/200,!0/5000,!425/200,!0/200,!425/200,!0/5000,!425/200,!0/200,!425/200,0\ndialrecall = !425/100,!0/100,!425/100,!0/100,!425/100,!0/100,425\nrecord = 1400/80,0/15000\ninfo = 950/330,1400/330,1800/330,0/1000\nstutter = 425+400\n"),
 array ( "name" => "Denmark",  "iso" => "dk", "conf" => "ringcadence = 1000,4000\ndial = 425\nbusy = 425/500,0/500\nring = 425/1000,0/4000\ncongestion = 425/200,0/200\ncallwaiting = !425/200,!0/600,!425/200,!0/3000,!425/200,!0/200,!425/200,0\ndialrecall = !425/100,!0/100,!425/100,!0/100,!425/100,!0/100,425\nrecord = 1400/80,0/15000\ninfo = 950/330,1400/330,1800/330,0/1000\nstutter = 425/450,0/50\n"),
 array ( "name" => "Estonia",  "iso" => "ee", "conf" => "ringcadence = 1000,4000\ndial = 425\nbusy = 425/300,0/300\nring = 425/1000,0/4000\ncongestion = 425/200,0/200\ncallwaiting = 950/650,0/325,950/325,0/30,1400/1300,0/2600\ndialrecall = 425/650,0/25\nrecord = 1400/500,0/15000\ninfo = 950/650,0/325,950/325,0/30,1400/1300,0/2600\nstutter = !425/100,!0/100,!425/100,!0/100,!425/100,!0/100,!425/100,!0/100,!425/100,!0/100,!425/100,!0/100,425\n"),
 array ( "name" => "Spain",  "iso" => "es", "conf" => "ringcadence = 1500,3000\ndial = 425\nbusy = 425/200,0/200\nring = 425/1500,0/3000\ncongestion = 425/200,0/200,425/200,0/200,425/200,0/600\ncallwaiting = 425/175,0/175,425/175,0/3500\ndialrecall = !425/200,!0/200,!425/200,!0/200,!425/200,!0/200,425\nrecord = 1400/500,0/15000\ninfo = 950/330,0/1000\ndialout = 500\n\n"),
 array ( "name" => "Finland",  "iso" => "fi", "conf" => "ringcadence = 1000,4000\ndial = 425\nbusy = 425/300,0/300\nring = 425/1000,0/4000\ncongestion = 425/200,0/200\ncallwaiting = 425/150,0/150,425/150,0/8000\ndialrecall = 425/650,0/25\nrecord = 1400/500,0/15000\ninfo = 950/650,0/325,950/325,0/30,1400/1300,0/2600\nstutter = 425/650,0/25\n"),
 array ( "name" => "France",  "iso" => "fr", "conf" => "ringcadence = 1500,3500\ndial = 440\nbusy = 440/500,0/500\nring = 440/1500,0/3500\ncongestion = 440/250,0/250\ncallwait = 440/300,0/10000\ndialrecall = !350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,350+440\nrecord = 1400/500,0/15000\ninfo = !950/330,!1400/330,!1800/330\nstutter = !440/100,!0/100,!440/100,!0/100,!440/100,!0/100,!440/100,!0/100,!440/100,!0/100,!440/100,!0/100,440\n"),
 array ( "name" => "Greece",  "iso" => "gr", "conf" => "ringcadence = 1000,4000\ndial = 425/200,0/300,425/700,0/800\nbusy = 425/300,0/300\nring = 425/1000,0/4000\ncongestion = 425/200,0/200\ncallwaiting = 425/150,0/150,425/150,0/8000\ndialrecall = 425/650,0/25\nrecord = 1400/400,0/15000\ninfo = !950/330,!1400/330,!1800/330,!0/1000,!950/330,!1400/330,!1800/330,!0/1000,!950/330,!1400/330,!1800/330,!0/1000,0\nstutter = 425/650,0/25\n"),
 array ( "name" => "Hungary",  "iso" => "hu", "conf" => "ringcadence = 1250,3750\ndial = 425\nbusy = 425/300,0/300\nring = 425/1250,0/3750\ncongestion = 425/300,0/300\ncallwaiting = 425/40,0/1960\ndialrecall = 425+450\nrecord = 1400/400,0/15000\ninfo = !950/330,!1400/330,!1800/330,!0/1000,!950/330,!1400/330,!1800/330,!0/1000,!950/330,!1400/330,!1800/330,!0/1000,0\nstutter = 350+375+400\n"),
 array ( "name" => "India",  "iso" => "in", "conf" => "ringcadence = 400,200,400,2000\ndial = 400*25\nbusy = 400/750,0/750\nring = 400*25/400,0/200,400*25/400,0/2000\ncongestion = 400/250,0/250\ncallwaiting = 400/200,0/100,400/200,0/7500\ndialrecall = !350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,350+440\nrecord = 1400/500,0/15000\ninfo = !950/330,!1400/330,!1800/330,0/1000\nstutter = !350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,350+440\n"),
 array ( "name" => "Israel",  "iso" => "il", "conf" => "ringcadence = 1000,3000\ndial = 414\nbusy = 414/500,0/500\nring = 414/1000,0/3000\ncongestion = 414/250,0/250\ncallwaiting = 414/100,0/100,414/100,0/100,414/600,0/3000 \ndialrecall = !414/100,!0/100,!414/100,!0/100,!414/100,!0/100,414\nrecord = 1400/500,0/15000\ninfo = 1000/330,1400/330,1800/330,0/1000\nstutter = !414/160,!0/160,!414/160,!0/160,!414/160,!0/160,!414/160,!0/160,!414/160,!0/160,!414/160,!0/160,!414/160,!0/160,!414/160,!0/160,!414/160,!0/160,!414/160,!0/160,414 \n"),
 array ( "name" => "Italy",  "iso" => "it", "conf" => "ringcadence = 1000,4000\ndial = 425/200,0/200,425/600,0/1000\nbusy = 425/500,0/500\nring = 425/1000,0/4000\ncongestion = 425/200,0/200\ncallwaiting = 425/400,0/100,425/250,0/100,425/150,0/14000\ndialrecall = 470/400,425/400\nrecord = 1400/400,0/15000\ninfo = !950/330,!1400/330,!1800/330,!0/1000,!950/330,!1400/330,!1800/330,!0/1000,!950/330,!1400/330,!1800/330,!0/1000,0\nstutter = 470/400,425/400\n"),
 array ( "name" => "Lithuania",  "iso" => "lt", "conf" => "ringcadence = 1000,4000\ndial = 425\nbusy = 425/350,0/350\nring = 425/1000,0/4000\ncongestion = 425/200,0/200\ncallwaiting = 425/150,0/150,425/150,0/4000\ndialrecall = 425/500,0/50\nrecord = 1400/500,0/15000\ninfo = !950/330,!1400/330,!1800/330,!0/1000,!950/330,!1400/330,!1800/330,!0/1000,!950/330,!1400/330,!1800/330,!0/1000,0\nstutter = !425/100,!0/100,!425/100,!0/100,!425/100,!0/100,!425/100,!0/100,!425/100,!0/100,!425/100,!0/100,425\n"),
 array ( "name" => "Mexico",  "iso" => "mx", "conf" => "ringcadence = 2000,4000\ndial = 425\nbusy = 425/250,0/250\nring = 425/1000,0/4000\ncongestion = 425/250,0/250\ncallwaiting = 425/200,0/600,425/200,0/10000\ndialrecall = !350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,350+440\nrecord = 1400/500,0/15000\ninfo = 950/330,0/30,1400/330,0/30,1800/330,0/1000\nstutter = !350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,350+440\n"),
 array ( "name" => "Netherlands",  "iso" => "nl", "conf" => "ringcadence = 1000,4000\ndial = 425\nbusy = 425/500,0/500\nring = 425/1000,0/4000\ncongestion = 425/250,0/250\ncallwaiting = 425/500,0/9500\ndialrecall = 425/500,0/50\nrecord = 1400/500,0/15000\ninfo = 950/330,1400/330,1800/330,0/1000\nstutter = 425/500,0/50\n"),
 array ( "name" => "Norway",  "iso" => "no", "conf" => "ringcadence = 1000,4000\ndial = 425\nbusy = 425/500,0/500\nring = 425/1000,0/4000\ncongestion = 425/200,0/200\ncallwaiting = 425/200,0/600,425/200,0/10000\ndialrecall = 470/400,425/400\nrecord = 1400/400,0/15000\ninfo = !950/330,!1400/330,!1800/330,!0/1000,!950/330,!1400/330,!1800/330,!0/1000,!950/330,!1400/330,!1800/330,!0/1000,0\nstutter = 470/400,425/400\n"),
 array ( "name" => "New Zealand",  "iso" => "nz", "conf" => "ringcadence = 400,200,400,2000\ndial = 400\nbusy = 400/250,0/250\nring = 400+450/400,0/200,400+450/400,0/2000\ncongestion = 400/375,0/375\ncallwaiting = !400/200,!0/3000,!400/200,!0/3000,!400/200,!0/3000,!400/200\ndialrecall = !400/100!0/100,!400/100,!0/100,!400/100,!0/100,400\nrecord = 1400/425,0/15000\ninfo = 400/750,0/100,400/750,0/100,400/750,0/100,400/750,0/400\nstutter = !400/100!0/100,!400/100,!0/100,!400/100,!0/100,!400/100!0/100,!400/100,!0/100,!400/100,!0/100,400\n"),
 array ( "name" => "Poland",  "iso" => "pl", "conf" => "ringcadence = 1000,4000\ndial = 425\nbusy = 425/500,0/500\nring = 425/1000,0/4000\ncongestion = 425/500,0/500\ncallwaiting = 425/150,0/150,425/150,0/4000\ndialrecall = 425/500,0/50\nrecord = 1400/500,0/15000\ninfo = !950/330,!1400/330,!1800/330,!0/1000,!950/330,!1400/330,!1800/330,!0/1000,!950/330,!1400/330,!1800/330,!0/1000\nstutter = !425/100,!0/100,!425/100,!0/100,!425/100,!0/100,!425/100,!0/100,!425/100,!0/100,!425/100,!0/100,425\n"),
 array ( "name" => "Portugal",  "iso" => "pt", "conf" => "ringcadence = 1000,5000\ndial = 425\nbusy = 425/500,0/500\nring = 425/1000,0/5000\ncongestion = 425/200,0/200\ncallwaiting = 440/300,0/10000\ndialrecall = 425/1000,0/200\nrecord = 1400/500,0/15000\ninfo = 950/330,1400/330,1800/330,0/1000\nstutter = !425/100,!0/100,!425/100,!0/100,!425/100,!0/100,!425/100,!0/100,!425/100,!0/100,!425/100,!0/100,425\n"),
 array ( "name" => "Russia / ex Soviet Union",  "iso" => "ru", "conf" => "ringcadence = 800,3200\ndial = 425\nbusy = 425/350,0/350\nring = 425/800,0/3200\ncongestion = 425/350,0/350\ncallwaiting = 425/200,0/5000\ndialrecall = !350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,350+440\nrecord = 1400/500,0/15000\ninfo = !950/330,!1400/330,!1800/330,0\n"),
 array ( "name" => "Singapore",  "iso" => "sg", "conf" => "ringcadence = 400,200,400,2000\ndial = 425\nring = 425*24/400,0/200,425*24/400,0/2000 ; modulation should be 100%, not 90%\nbusy = 425/750,0/750\ncongestion = 425/250,0/250\ncallwaiting = 425*24/300,0/200,425*24/300,0/3200\nstutter = !425/200,!0/200,!425/600,!0/200,!425/200,!0/200,!425/600,!0/200,!425/200,!0/200,!425/600,!0/200,!425/200,!0/200,!425/600,!0/200,425\ninfo = 950/330,1400/330,1800/330,0/1000 ; not currently in use acc. to reference\ndialrecall = 425*24/500,0/500,425/500,0/2500 ; unspecified in IDA reference, use repeating Holding Tone A,B\nrecord = 1400/500,0/15000 ; unspecified in IDA reference, use 0.5s tone every 15s\nnutone = 425/2500,0/500\nintrusion = 425/250,0/2000\nwarning = 425/624,0/4376 ; end of period tone, warning\nacceptance = 425/125,0/125\nholdinga = !425*24/500,!0/500 ; followed by holdingb\nholdingb = !425/500,!0/2500\n"),
 array ( "name" => "South Africa",  "iso" => "za", "conf" => "ringcadence = 400,200,400,2000\ndial = 400*33\nbusy = 400/500,0/500\nring = 400*33/400,0/200,400*33/400,0/2000\ncongestion = 400/250,0/250\ncallwaiting = 400*33/250,0/250,400*33/250,0/250,400*33/250,0/250,400*33/250,0/250\ndialrecall = 350+440\nrecord = 1400/500,0/10000\ninfo = 950/330,1400/330,1800/330,0/330\nstutter =!400*33/100,!0/100,!400*33/100,!0/100,!400*33/100,!0/100,!400*33/100,!0/100,!400*33/100,!0/100,!400*33/100,!0/100,400*33 \n"),
 array ( "name" => "Sweden",  "iso" => "se", "conf" => "ringcadence = 1000,5000\ndial = 425\nbusy = 425/250,0/250\nring = 425/1000,0/5000\ncongestion = 425/250,0/750\ncallwaiting = 425/200,0/500,425/200,0/9100\ndialrecall = !425/100,!0/100,!425/100,!0/100,!425/100,!0/100,425\nrecord = 1400/500,0/15000\ninfo = !950/332,!0/24,!1400/332,!0/24,!1800/332,!0/2024,!950/332,!0/24,!1400/332,!0/24,!1800/332,!0/2024,!950/332,!0/24,!1400/332,!0/24,!1800/332,!0/2024,!950/332,!0/24,!1400/332,!0/24,!1800/332,!0/2024,!950/332,!0/24,!1400/332,!0/24,!1800/332,0\nstutter = !425/100,!0/100,!425/100,!0/100,!425/100,!0/100,!425/100,!0/100,!425/100,!0/100,!425/100,!0/100,425\n"),
 array ( "name" => "United Kingdom",  "iso" => "uk", "conf" => "ringcadence = 400,200,400,2000\ndial = 350+440\nspecialdial = 350+440/750,440/750\nbusy = 400/375,0/375\ncongestion = 400/400,0/350,400/225,0/525\nspecialcongestion = 400/200,1004/300\nunobtainable = 400\nring = 400+450/400,0/200,400+450/400,0/2000\ncallwaiting = 400/100,0/4000\nspecialcallwaiting = 400/250,0/250,400/250,0/250,400/250,0/5000\ncreditexpired = 400/125,0/125\nconfirm = 1400\nswitching = 400/200,0/400,400/2000,0/400\ninfo = 950/330,0/15,1400/330,0/15,1800/330,0/1000\nrecord = 1400/500,0/60000\nstutter = 350+440/750,440/750\n"),
 array ( "name" => "United States / North America",  "iso" => "us", "conf" => "ringcadence = 2000,4000\ndial = 350+440\nbusy = 480+620/500,0/500\nring = 440+480/2000,0/4000\ncongestion = 480+620/250,0/250\ncallwaiting = 440/300,0/10000\ndialrecall = !350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,350+440\nrecord = 1400/500,0/15000\ninfo = !950/330,!1400/330,!1800/330,0\nstutter = !350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,350+440\n"),
 array ( "name" => "United States Circa 1950/ North America",  "iso" => "us-old", "conf" => "ringcadence = 2000,4000\ndial = 600*120\nbusy = 500*100/500,0/500\nring = 420*40/2000,0/4000\ncongestion = 500*100/250,0/250\ncallwaiting = 440/300,0/10000\ndialrecall = !600*120/100,!0/100,!600*120/100,!0/100,!600*120/100,!0/100,600*120\nrecord = 1400/500,0/15000\ninfo = !950/330,!1400/330,!1800/330,0\nstutter = !600*120/100,!0/100,!600*120/100,!0/100,!600*120/100,!0/100,!600*120/100,!0/100,!600*120/100,!0/100,!600*120/100,!0/100,600*120\n"),
 array ( "name" => "Taiwan",  "iso" => "tw", "conf" => "ringcadence = 1000,4000\ndial = 350+440\nbusy = 480+620/500,0/500\nring = 440+480/1000,0/2000\ncongestion = 480+620/250,0/250\ncallwaiting = 350+440/250,0/250,350+440/250,0/3250\ndialrecall = 300/1500,0/500\nrecord = 1400/500,0/15000\ninfo = !950/330,!1400/330,!1800/330,0\nstutter = !350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,!350+440/100,!0/100,350+440\n"),
 array ( "name" => "Venezuela / South America",  "iso" => "ve", "conf" => "; Tone definition source for ve found on\n; Reference: http://www.itu.int/ITU-T/inr/forms/files/tones-0203.pdf\nringcadence = 1000,4000\ndial = 425\nbusy = 425/500,0/500\nring = 425/1000,0/4000\ncongestion = 425/250,0/250\ncallwaiting = 400+450/300,0/6000\ndialrecall = 425\nrecord =  1400/500,0/15000\ninfo = !950/330,!1440/330,!1800/330,0/1000\n"),);
}

function general_display_zones($curzone) {
	$zonelist = general_get_zonelist();
	echo "<select name='TONEZONE'>\n";
	foreach ($zonelist as $zone) {
		if ($zone['iso'] == $curzone) 
			echo "<option selected value='{$zone['iso']}'>{$zone['name']}</option>\n";
		else	
			echo "<option value='{$zone['iso']}'>{$zone['name']}</option>\n";
	}
	echo "</select>";
	
}

function general_generate_indications() {
	global $db;
	global $asterisk_conf;

	$sql = "SELECT value FROM globals WHERE variable='TONEZONE'";
	$result = $db->getRow($sql,DB_FETCHMODE_ASSOC);

	$filename = isset($asterisk_conf["astetcdir"]) && $asterisk_conf["astetcdir"] != '' ? rtrim($asterisk_conf["astetcdir"],DIRECTORY_SEPARATOR) : "/etc/asterisk";
	$filename .= "/indications.conf";
	$fd = fopen($filename, "w");
	fwrite($fd, "[general]\ncountry=".$result['value']."\n\n");

	$zonelist = general_get_zonelist();
	foreach ($zonelist as $zone) {
		fwrite($fd, "[{$zone['iso']}]\n{$zone['conf']}\n\n");
	}
	fclose($fd);
}
/* end page.routing.php functions */



// init registered 'your' config load and config process functions
function core_users_configpageinit($dispnum) {
	global $currentcomponent;
	global $amp_conf;

	if ( $dispnum == 'users' || $dispnum == 'extensions' ) {
		// Setup option list we need
		$currentcomponent->addoptlistitem('recordoptions', 'Adhoc', _("On Demand"));
		$currentcomponent->addoptlistitem('recordoptions', 'Always', _("Always"));
		$currentcomponent->addoptlistitem('recordoptions', 'Never', _("Never"));
		$currentcomponent->setoptlistopts('recordoptions', 'sort', false);

		$currentcomponent->addoptlistitem('faxdetecttype', '0', _("None"));
		$currentcomponent->addoptlistitem('faxdetecttype', '1', 'Zaptel');
		$currentcomponent->addoptlistitem('faxdetecttype', '2', 'NVFax');
		$currentcomponent->setoptlistopts('faxdetecttype', 'sort', false);

		$currentcomponent->addoptlistitem('privyn', '0', _("No"));
		$currentcomponent->addoptlistitem('privyn', '1', _("Yes"));
		$currentcomponent->setoptlistopts('privyn', 'sort', false);

		$currentcomponent->addoptlistitem('callwaiting', 'enabled', _("Enable"));
		$currentcomponent->addoptlistitem('callwaiting', 'disabled', _("Disable"));
		$currentcomponent->setoptlistopts('callwaiting', 'sort', false);

		$currentcomponent->addoptlistitem('ringtime', '0', 'Default');
		for ($i=1; $i <= 120; $i++) {
			$currentcomponent->addoptlistitem('ringtime', "$i", "$i");
		}
		$currentcomponent->setoptlistopts('ringtime', 'sort', false);

		$currentcomponent->addoptlistitem('faxdestoptions', 'default', _("FreePBX default"));
		$currentcomponent->addoptlistitem('faxdestoptions', 'disabled', _("disabled"));
		$currentcomponent->addoptlistitem('faxdestoptions', 'system', _("system"));
		$currentcomponent->setoptlistopts('faxdestoptions', 'sort', false);

		if (function_exists('music_list')) {
				$tresults = music_list($amp_conf['ASTVARLIBDIR']."/mohmp3");
		    if (isset($tresults[0])) {
			foreach ($tresults as $tresult) {
			    $currentcomponent->addoptlistitem('mohclass', $tresult, $tresult);
			}
		    $currentcomponent->setoptlistopts('mohclass', 'sort', false);
		    }
		}

		//get unique devices to finishoff faxdestoptions list
		$devices = core_devices_list();
		if (isset($devices)) {
			foreach ($devices as $device) {
				$currentcomponent->addoptlistitem('faxdestoptions', $device[0], "$device[1] <$device[0]>");
			}
		}

		// Add the 'proces' functions
		$currentcomponent->addguifunc('core_users_configpageload');
		// Ensure users is called in middle order ($sortorder = 5), this is to allow
		// other modules to call stuff before / after the processing of users if needed
		// e.g. Voicemail module needs to create mailbox BEFORE the users as the mailbox
		// context is needed by the add users function
		$currentcomponent->addprocessfunc('core_users_configprocess', 5);			
	}
}

function core_users_configpageload() {
	global $currentcomponent;
	global $amp_conf;

	// Ensure variables possibly extracted later exist
	$name = $directdid = $didalert = $outboundcid = $answer = null;
	$record_in = $record_out = $faxexten = $faxemail = $mohclass = $sipname = $cid_masquerade = null;

	// Init vars from $_REQUEST[]
	$display = isset($_REQUEST['display'])?$_REQUEST['display']:null;;
	$action = isset($_REQUEST['action'])?$_REQUEST['action']:null;
	$extdisplay = isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:null;
	$tech_hardware = isset($_REQUEST['tech_hardware'])?$_REQUEST['tech_hardware']:null;

	if ( $action == 'del' ) { // Deleted

		$currentcomponent->addguielem('_top', new gui_subheading('del', $extdisplay.' '._("deleted"), false));

	} elseif ( $display == 'extensions' && ($extdisplay == '' && $tech_hardware == '') ) { // Adding

		// do nothing as you want the Devices to handle this bit

	} else {

		$delURL = $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'&action=del';
	
		if ( is_string($extdisplay) ) {	
			$extenInfo=core_users_get($extdisplay);
			extract($extenInfo);
			if (isset($deviceInfo) && is_array($deviceInfo))
				extract($deviceInfo);
	
			if ( $display == 'extensions' ) {
				$currentcomponent->addguielem('_top', new gui_pageheading('title', _("Extension").": $extdisplay", false), 0);
				$currentcomponent->addguielem('_top', new gui_link('del', _("Delete Extension")." $extdisplay", $delURL, true, false), 0);
			} else {
				$currentcomponent->addguielem('_top', new gui_pageheading('title', _("User").": $extdisplay", false), 0);
				$currentcomponent->addguielem('_top', new gui_link('del', _("Delete User")." $extdisplay", $delURL, true, false), 0);
			}

		} elseif ( $display != 'extensions' ) {
			$currentcomponent->addguielem('_top', new gui_pageheading('title', _("Add User/Extension")), 0);
		}
		
		// Setup vars for use in the gui later on							
		$fc_logon = featurecodes_getFeatureCode('core', 'userlogon');
		$fc_logoff = featurecodes_getFeatureCode('core', 'userlogoff');
		
		$msgInvalidExtNum = _("Please enter a valid extension number.");
		$msgInvalidCidNum = _("Please enter a valid CID Num Alias (must be a valid number).");
		$msgInvalidExtPwd = _("Please enter valid User Password using numbers only");
		$msgInvalidDispName = _("Please enter a valid Display Name");
		$msgInvalidOutboundCID = _("Please enter a valid Outbound CID");
		$msgInvalidPause = _("Please enter a valid pause time in seconds, using digits only");

		// This is the actual gui stuff
		$currentcomponent->addguielem('_top', new gui_hidden('action', ($extdisplay ? 'edit' : 'add')));
		$currentcomponent->addguielem('_top', new gui_hidden('extdisplay', $extdisplay));
		
		if ( $display == 'extensions' ) {
			$section = ($extdisplay ? _("Edit Extension") : _("Add Extension"));			
		} else {
			$section = ($extdisplay ? _("Edit User") : _("Add User"));
		}
		if ( $extdisplay ) {
			$currentcomponent->addguielem($section, new gui_hidden('extension', $extdisplay), 2);
		} else {
			$currentcomponent->addguielem($section, new gui_textbox('extension', $extdisplay, 'User Extension', 'The extension number to dial to reach this user.', '!isInteger()', $msgInvalidExtNum, false), 3);
		}
		if ( $display != 'extensions' ) {
			$currentcomponent->addguielem($section, new gui_password('password', $password, 'User Password', _("A user will enter this password when logging onto a device.").' '.$fc_logon.' '._("logs into a device.").' '.$fc_logoff.' '._("logs out of a device."), '!isInteger() && !isWhitespace()', $msgInvalidExtPwd, true));
			// extra JS function check required for blank password warning -- call last in the onsubmit() function
			$currentcomponent->addjsfunc('onsubmit()', "\treturn checkBlankUserPwd();\n", 9);
		}
		$currentcomponent->addguielem($section, new gui_textbox('name', $name, 'Display Name', 'The caller id name for calls from this user will be set to this name. Only enter the name, NOT the number.', '!isCallerID()', $msgInvalidDispName, false));
		$cid_masquerade = (trim($cid_masquerade) == $extdisplay)?"":$cid_masquerade;
		$currentcomponent->addguielem($section, new gui_textbox('cid_masquerade', $cid_masquerade, 'CID Num Alias', 'The CID Number to user for internal calls, if different then the extension number. This is used to masquerade as a different user. A common example is a team support persons who would like their internal callerid to display the general support number (a ringgroup or queue). There will be no efffect on external calls.', '!isWhitespace() && !isInteger()', $msgInvalidCidNum, false));
		$currentcomponent->addguielem($section, new gui_textbox('sipname', $sipname, 'SIP Alias', "If you want to support direct sip dialing of users internally or through anonymous sip calls, you can supply a friendly name that can be used in addition to the users extension to call them."));
		
		$section = 'Extension Options';
		$currentcomponent->addguielem($section, new gui_textbox('directdid', $directdid, 'Direct DID', "The direct DID that is associated with this extension. The DID should be in the same format as provided by the provider (e.g. full number, 4 digits for 10x4, etc).<br><br>Format should be: <b>XXXXXXXXXX</b><br><br>Leave this field blank to disable the direct DID feature for this extension. All non-numeric characters will be stripped."), 3);
		$currentcomponent->addguielem($section, new gui_textbox('didalert', $didalert, 'DID Alert Info', "Alert Info can be used for distinctive ring on SIP phones. Set this value to the desired Alert Info to be sent to the phone when this DID is called. Leave blank to use default values. Will have no effect if no Direct DID is set"));
		if (function_exists('music_list')) {
		    $currentcomponent->addguielem($section, new gui_selectbox('mohclass', $currentcomponent->getoptlist('mohclass'), $mohclass, 'Music on Hold', "Set the MoH class that will be used for calls that come in on this Direct DID. For example, choose a type appropriate for a originating country which may have announcements in their language. Only effects MoH class when the call came in from the Direct DID.", false));
		}
		$currentcomponent->addguielem($section, new gui_textbox('outboundcid', $outboundcid, 'Outbound CID', "Overrides the caller id when dialing out a trunk. Any setting here will override the common outbound caller id set in the Trunks admin.<br><br>Format: <b>\"caller name\" &lt;#######&gt;</b><br><br>Leave this field blank to disable the outbound callerid feature for this user.", '!isCallerID()', $msgInvalidOutboundCID, true));
		$ringtimer = (isset($ringtimer) ? $ringtimer : '0');
		$currentcomponent->addguielem($section, new gui_selectbox('ringtimer', $currentcomponent->getoptlist('ringtime'), $ringtimer, 'Ring Time', "Number of seconds to ring prior to going to voicemail. Default will use the value set in the General Tab. If no voicemail is configured this will be ignored.", false));
		if (!isset($callwaiting)) {
			if ($amp_conf['ENABLECW']) {
				$callwaiting = 'enabled';
			} else {
				$callwaiting = 'disabled';
			}
		}
		$currentcomponent->addguielem($section, new gui_selectbox('callwaiting', $currentcomponent->getoptlist('callwaiting'), $callwaiting, 'Call Waiting', "Set the initial/current Call Waiting state for this user's extension", false));

		$section = 'Recording Options';
		$currentcomponent->addguielem($section, new gui_selectbox('record_in', $currentcomponent->getoptlist('recordoptions'), $record_in, 'Record Incoming', "Record all inbound calls received at this extension.", false));
		$currentcomponent->addguielem($section, new gui_selectbox('record_out', $currentcomponent->getoptlist('recordoptions'), $record_out, 'Record Outgoing', "Record all outbound calls received at this extension.", false));

		$section = 'Fax Handling';
		$wait = (isset($wait) ? $wait : '0');
		$currentcomponent->addguielem($section, new gui_selectbox('faxexten', $currentcomponent->getoptlist('faxdestoptions'), $faxexten, 'Fax Extension', "Select 'system' to have the system receive and email faxes.<br><br>The FreePBX default is defined in General Settings.", false), 4);
		$currentcomponent->addguielem($section, new gui_textbox('faxemail', $faxemail, 'Fax Email', "Email address is used if 'system' has been chosen for the fax extension above.<br><br>Leave this blank to use the FreePBX default in General Settings"));
		$currentcomponent->addguielem($section, new gui_selectbox('answer', $currentcomponent->getoptlist('faxdetecttype'), $answer, 'Fax Detection Type', "Selecting Zaptel or NVFax will immediately answer the call and play ringing tones to the caller for the number of seconds in Pause below. Use NVFax on SIP or IAX trunks.", false));
		$currentcomponent->addguielem($section, new gui_textbox('wait', $wait, 'Pause after answer', 'The number of seconds we should wait after performing an Immediate Answer. The primary purpose of this is to pause and listen for a fax tone before allowing the call to proceed.', '!isInteger()', $msgInvalidPause, false));

		$section = 'Privacy';
		$privacyman = (isset($privacyman) ? $privacyman : '0');
		$currentcomponent->addguielem($section, new gui_selectbox('privacyman', $currentcomponent->getoptlist('privyn'), $privacyman, 'Privacy Manager', "If no Caller ID is sent, Privacy Manager will asks the caller to enter their 10 digit phone number. The caller is given 3 attempts.", false), 4);

	}
}

function core_users_configprocess() {
	if ( !class_exists('agi_asteriskmanager') )
		include 'common/php-asmanager.php';
	
	//create vars from the request
	extract($_REQUEST);
	
	//make sure we can connect to Asterisk Manager
	if (!checkAstMan()) {
		return false;
	}

	//check if the extension is within range for this user
	if (isset($extension) && !checkRange($extension)){
		echo "<script>javascript:alert('". _("Warning! Extension")." ".$extension." "._("is not allowed for your account").".');</script>";
	} else {
		//if submitting form, update database
		if (!isset($action)) $action = null;
		switch ($action) {
			case "add":
				if (core_users_add($_REQUEST)) {
					needreload();
					redirect_standard_continue();
				} else {
					// really bad hack - but if core_users_add fails, want to stop core_devices_add
					$GLOBALS['abort'] = true;
				}
			break;
			case "del":
				core_users_del($extdisplay);
				core_users_cleanastdb($extdisplay);
				if (function_exists('findmefollow_del')) {
				    findmefollow_del($extdisplay);
				}
				needreload();
				redirect_standard_continue();
			break;
			case "edit":
				if (core_users_edit($extdisplay,$_REQUEST)) {
					needreload();
					redirect_standard_continue('extdisplay');
				} else {
					// really bad hack - but if core_users_edit fails, want to stop core_devices_edit
					$GLOBALS['abort'] = true;
				}
			break;
		}
	}
	return true;
}


function core_devices_configpageinit($dispnum) {
	global $currentcomponent;

	if ( $dispnum == 'devices' || $dispnum == 'extensions' ) {
		// Setup arrays for device types
		$currentcomponent->addgeneralarray('devtechs');
		
		// Some errors for the validation bits
		$msgInvalidDTMFMODE = _("Please enter the dtmfmode for this device");
		$msgInvalidChannel = _("Please enter the channel for this device");
		$msgConfirmSecret = _("You have not entered a Secret for this device, although this is possible it is generally bad practice to not assign a Secret to a device.\n\nAre you sure you want to leave the Secret empty?");
		$msgInvalidSecret = _("Please enter a Secret for this device");
		
		// zap
		$tmparr = array();
		$tmparr['channel'] = array('value' => '', 'level' => 0, 'jsvalidation' => 'isEmpty()', 'failvalidationmsg' => $msgInvalidChannel);
		$tmparr['context'] = array('value' => 'from-internal', 'level' => 1);
		$tmparr['immediate'] = array('value' => 'no', 'level' => 1);
		$tmparr['signalling'] = array('value' => 'fxo_ks', 'level' => 1);
		$tmparr['echocancel'] = array('value' => 'yes', 'level' => 1);
		$tmparr['echocancelwhenbridged'] = array('value' => 'no', 'level' => 1);
		$tmparr['echotraining'] = array('value' => '800', 'level' => 1);
		$tmparr['busydetect'] = array('value' => 'no', 'level' => 1);
		$tmparr['busycount'] = array('value' => '7', 'level' => 1);
		$tmparr['callprogress'] = array('value' => 'no', 'level' => 1);
		$tmparr['dial'] = array('value' => '', 'level' => 1);
		$tmparr['accountcode'] = array('value' => '', 'level' => 1);
		$tmparr['mailbox'] = array('value' => '', 'level' => 1);
		$currentcomponent->addgeneralarrayitem('devtechs', 'zap', $tmparr);
		unset($tmparr);
		
		// iax2
		$tmparr = array();
		$tmparr['secret'] = array('value' => '', 'level' => 0, 'jsvalidation' => 'isEmpty() && !confirm("'.$msgConfirmSecret.'")', 'failvalidationmsg' => $msgInvalidSecret);
		$tmparr['notransfer'] = array('value' => 'yes', 'level' => 1);
		$tmparr['context'] = array('value' => 'from-internal', 'level' => 1);
		$tmparr['host'] = array('value' => 'dynamic', 'level' => 1);
		$tmparr['type'] = array('value' => 'friend', 'level' => 1);
		$tmparr['port'] = array('value' => '4569', 'level' => 1);
		$tmparr['qualify'] = array('value' => 'yes', 'level' => 1);
		$tmparr['disallow'] = array('value' => '', 'level' => 1);
		$tmparr['allow'] = array('value' => '', 'level' => 1);
		$tmparr['dial'] = array('value' => '', 'level' => 1);
		$tmparr['accountcode'] = array('value' => '', 'level' => 1);
		$tmparr['mailbox'] = array('value' => '', 'level' => 1);
		$currentcomponent->addgeneralarrayitem('devtechs', 'iax2', $tmparr);
		unset($tmparr);

		// sip
		$tmparr = array();
		$tmparr['secret'] = array('value' => '', 'level' => 0, 'jsvalidation' => 'isEmpty() && !confirm("'.$msgConfirmSecret.'")', 'failvalidationmsg' => $msgInvalidSecret);
		$tmparr['dtmfmode'] = array('value' => 'rfc2833', 'level' => 0, 'jsvalidation' => 'isEmpty()', 'failvalidationmsg' => $msgInvalidDTMFMODE );
		$tmparr['canreinvite'] = array('value' => 'no', 'level' => 1);
		$tmparr['context'] = array('value' => 'from-internal', 'level' => 1);
		$tmparr['host'] = array('value' => 'dynamic', 'level' => 1);
		$tmparr['type'] = array('value' => 'friend', 'level' => 1);
		$tmparr['nat'] = array('value' => 'yes', 'level' => 1);
		$tmparr['port'] = array('value' => '5060', 'level' => 1);
		$tmparr['qualify'] = array('value' => 'yes', 'level' => 1);
		$tmparr['callgroup'] = array('value' => '', 'level' => 1);
		$tmparr['pickupgroup'] = array('value' => '', 'level' => 1);
		$tmparr['disallow'] = array('value' => '', 'level' => 1);
		$tmparr['allow'] = array('value' => '', 'level' => 1);
		$tmparr['dial'] = array('value' => '', 'level' => 1);
		$tmparr['accountcode'] = array('value' => '', 'level' => 1);
		$tmparr['mailbox'] = array('value' => '', 'level' => 1);
		$currentcomponent->addgeneralarrayitem('devtechs', 'sip', $tmparr);
		unset($tmparr);

		// custom
		$tmparr = array();
		$tmparr['dial'] = array('value' => '', 'level' => 0);
		$currentcomponent->addgeneralarrayitem('devtechs', 'custom', $tmparr);
		unset($tmparr);
		
		// Devices list
		$currentcomponent->addoptlistitem('devicelist', 'sip_generic', _("Generic SIP Device"));
		$currentcomponent->addoptlistitem('devicelist', 'iax2_generic', _("Generic IAX2 Device"));
		$currentcomponent->addoptlistitem('devicelist', 'zap_generic', _("Generic ZAP Device"));
		$currentcomponent->addoptlistitem('devicelist', 'custom_custom', _("Other (Custom) Device"));
		$currentcomponent->setoptlistopts('devicelist', 'sort', false);


		// Option lists used by the gui
		$currentcomponent->addoptlistitem('devicetypelist', 'fixed', _("Fixed"));
		$currentcomponent->addoptlistitem('devicetypelist', 'adhoc', _("Adhoc"));
		$currentcomponent->setoptlistopts('devicetypelist', 'sort', false);
		
		$currentcomponent->addoptlistitem('deviceuserlist', 'none', _("none"));
		$currentcomponent->addoptlistitem('deviceuserlist', 'new', _("New User"));
		$users = core_users_list();
		if (isset($users)) {
			foreach ($users as $auser) {
				$currentcomponent->addoptlistitem('deviceuserlist', $auser[0], $auser[0]);
			}
		}
		$currentcomponent->setoptlistopts('deviceuserlist', 'sort', false);

		// Add the 'proces' functions
		$currentcomponent->addguifunc('core_devices_configpageload');
		$currentcomponent->addprocessfunc('core_devices_configprocess');
	}
}

function core_devices_configpageload() {
	global $currentcomponent;

	// Init vars from $_REQUEST[]
	$display = isset($_REQUEST['display'])?$_REQUEST['display']:null;;
	$action = isset($_REQUEST['action'])?$_REQUEST['action']:null;
	$extdisplay = isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:null;
	$tech_hardware = isset($_REQUEST['tech_hardware'])?$_REQUEST['tech_hardware']:null;
	
	if ( $action == 'del' ) { // Deleted

		if ( $display != 'extensions' )
			$currentcomponent->addguielem('_top', new gui_subheading('del', $extdisplay.' '._("deleted"), false));

	} elseif ( $extdisplay == '' && $tech_hardware == '' ) { // Adding

		if ( $display != 'extensions') {
			$currentcomponent->addguielem('_top', new gui_pageheading('title', _("Add Device")), 0);
		} else {
			$currentcomponent->addguielem('_top', new gui_pageheading('title', _("Add an Extension")), 0);
		}
		$currentcomponent->addguielem('_top', new gui_label('instructions', _("Please select your Device below then click Submit")));
		$currentcomponent->addguielem('Device', new gui_selectbox('tech_hardware', $currentcomponent->getoptlist('devicelist'), '', 'Device', '', false));

	} else {

		$deviceInfo = array();
		if ( $extdisplay ) { // Editing

			$deviceInfo = core_devices_get($extdisplay);

			if ( $display != 'extensions' ) {
				$currentcomponent->addguielem('_top', new gui_pageheading('title', _("Device").": $extdisplay", false), 0);

				$delURL = $_SERVER['PHP_SELF'].'?'.$_SERVER['QUERY_STRING'].'&action=del';
				$currentcomponent->addguielem('_top', new gui_link('del', _("Delete Device")." $extdisplay", $delURL, true, false), 0);
			}
	
		} else {

			$tmparr = explode('_', $tech_hardware);
			$deviceInfo['tech'] = $tmparr[0];
			$deviceInfo['hardware'] = $tmparr[1];
			unset($tmparr);
			
			if ( $display != 'extensions' ) {
				$currentcomponent->addguielem('_top', new gui_pageheading('title', _("Add").' '.strtoupper($deviceInfo['tech']).' '._("Device")), 0);
			} else {
				$currentcomponent->addguielem('_top', new gui_pageheading('title', _("Add").' '.strtoupper($deviceInfo['tech']).' '._("Extension")), 0);
			}

		}

		// Ensure they exist before the extract
		$devinfo_description = $devinfo_emergency_cid = null;
		$devinfo_devicetype = $devinfo_user = $devinfo_hardware = null;
		if ( is_array($deviceInfo) )
			extract($deviceInfo, EXTR_PREFIX_ALL, 'devinfo');

		// Setup vars for use in the gui later on							
		$fc_logon = featurecodes_getFeatureCode('core', 'userlogon');
		$fc_logoff = featurecodes_getFeatureCode('core', 'userlogoff');

		$msgInvalidDevID = _("Please enter a device id.");
		$msgInvalidDevDesc = _("Please enter a valid Description for this device");
		$msgInvalidEmergCID = _("Please enter a valid Emergency CID");
		$msgInvalidExtNum = _("Please enter a valid extension number.");
		
		// Actual gui
		$currentcomponent->addguielem('_top', new gui_hidden('action', ($extdisplay ? 'edit' : 'add')));
		$currentcomponent->addguielem('_top', new gui_hidden('extdisplay', $extdisplay));

		if ( $display != 'extensions' ) {
			$section = 'Device Info';
			if ( $extdisplay ) { // Editing
				$currentcomponent->addguielem($section, new gui_hidden('deviceid', $extdisplay));
			} else { // Adding
				$currentcomponent->addguielem($section, new gui_textbox('deviceid', $extdisplay, 'Device ID', 'Give your device a unique integer ID.  The device will use this ID to authenicate to the system.', '!isInteger()', $msgInvalidDevID, false));
			}
			$currentcomponent->addguielem($section, new gui_textbox('description', $devinfo_description, 'Description', 'The caller id name for this device will be set to this description until it is logged into.', '!isAlphanumeric() || isWhitespace()', $msgInvalidDevDesc, false));
			$currentcomponent->addguielem($section, new gui_textbox('emergency_cid', $devinfo_emergency_cid, 'Emergency CID', 'This caller id will always be set when dialing out an Outbound Route flagged as Emergency.  The Emergency CID overrides all other caller id settings.', '!isCallerID()', $msgInvalidEmergCID));
			$currentcomponent->addguielem($section, new gui_selectbox('devicetype', $currentcomponent->getoptlist('devicetypelist'), $devinfo_devicetype, 'Device Type', _("Devices can be fixed or adhoc. Fixed devices are always associated to the same extension/user. Adhoc devices can be logged into and logged out of by users.").' '.$fc_logon.' '._("logs into a device.").' '.$fc_logoff.' '._("logs out of a device."), false));
			$currentcomponent->addguielem($section, new gui_selectbox('deviceuser', $currentcomponent->getoptlist('deviceuserlist'), $devinfo_user, 'Default User', 'Fixed devices will always mapped to this user.  Adhoc devices will be mapped to this user by default.<br><br>If selecting "New User", a new User Extension of the same Device ID will be set as the Default User.', false));
		} else {
			$section = 'Extension Options';
			$currentcomponent->addguielem($section, new gui_textbox('emergency_cid', $devinfo_emergency_cid, 'Emergency CID', 'This caller id will always be set when dialing out an Outbound Route flagged as Emergency.  The Emergency CID overrides all other caller id settings.', '!isCallerID()', $msgInvalidEmergCID));
		}
		$currentcomponent->addguielem($section, new gui_hidden('tech', $devinfo_tech));
		$currentcomponent->addguielem($section, new gui_hidden('hardware', $devinfo_hardware));

		$section = 'Device Options';
		$currentcomponent->addguielem($section, new gui_label('techlabel', sprintf(_("This device uses %s technology."),$devinfo_tech)),4);
		$devopts = $currentcomponent->getgeneralarrayitem('devtechs', $devinfo_tech);
		foreach ($devopts as $devopt=>$devoptarr) {
			$devopname = 'devinfo_'.$devopt;
			$devoptcurrent = isset($$devopname) ? $$devopname : $devoptarr['value'];
			$devoptjs = isset($devoptarr['jsvalidation']) ? $devoptarr['jsvalidation'] : '';
			$devoptfailmsg = isset($devoptarr['failvalidationmsg']) ? $devoptarr['failvalidationmsg'] : '';
			
			if ( $devoptarr['level'] == 0 || ($extdisplay && $devoptarr['level'] == 1) ) { // editing to show advanced as well
				$currentcomponent->addguielem($section, new gui_textbox($devopname, $devoptcurrent, $devopt, '', $devoptjs, $devoptfailmsg), 4);
			} else { // add so only basic
				$currentcomponent->addguielem($section, new gui_hidden($devopname, $devoptcurrent), 4);
			}
		}
	}
}

function core_devices_configprocess() {
	if ( !class_exists('agi_asteriskmanager') )
		include 'common/php-asmanager.php';

	//make sure we can connect to Asterisk Manager
	if (!checkAstMan()) {
		return false;
	}
	
	//create vars from the request
	extract($_REQUEST);

	$extension = isset($extension)?$extension:null;
	$deviceid = isset($deviceid)?$deviceid:null;
	$name = isset($name)?$name:null;
	$action = isset($action)?$action:null;

	// fixed users only in extensions mode
	if ( $display == 'extensions' ) {
		$devicetype = 'fixed';
		$deviceid = $deviceuser = $extension;
        $description = $name;
	}
	
	//if submitting form, update database
	switch ($action) {
		case "add":
		// really bad hack - but if core_users_add fails, want to stop core_devices_add
		if (!isset($GLOBALS['abort']) || $GLOBALS['abort'] !== true) {
			if (core_devices_add($deviceid,$tech,$devinfo_dial,$devicetype,$deviceuser,$description,$emergency_cid)) {
				needreload();
				if ($deviceuser != 'new') {
					redirect_standard_continue();
				}
			}
		}
		break;
		case "del":
			core_devices_del($extdisplay);
			needreload();
			redirect_standard_continue();
		break;
		case "edit":  //just delete and re-add
			// really bad hack - but if core_users_edit fails, want to stop core_devices_edit
			if (!isset($GLOBALS['abort']) || $GLOBALS['abort'] !== true) {
				core_devices_del($extdisplay);
				core_devices_add($deviceid,$tech,$devinfo_dial,$devicetype,$deviceuser,$description,$emergency_cid);
				needreload();
				redirect_standard_continue('extdisplay');
			}
			break;
			case "resetall":  //form a url with this option to nuke the AMPUSER & DEVICE trees and start over.
				core_users2astdb();
				core_devices2astdb();
			break;
	}
	return true;
}

?>

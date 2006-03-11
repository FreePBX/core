<?php /* $Id$ */
// routing.php Copyright (C) 2004 Greg MacLellan (greg@mtechsolutions.ca)
// Asterisk Management Portal Copyright (C) 2004 Coalescent Systems Inc. (info@coalescentsystems.ca)
//
//This program is free software; you can redistribute it and/or
//modify it under the terms of the GNU General Public License
//as published by the Free Software Foundation; either version 2
//of the License, or (at your option) any later version.
//
//This program is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//GNU General Public License for more details.

$localPrefixFile = "/etc/asterisk/localprefixes.conf";


$display='trunks'; 
$extdisplay=isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:'';
$action = isset($_REQUEST['action'])?$_REQUEST['action']:'';
$tech = strtolower(isset($_REQUEST['tech'])?$_REQUEST['tech']:'');

$trunknum = ltrim($extdisplay,'OUT_');


// populate some global variables from the request string
$set_globals = array("outcid","maxchans","dialoutprefix","channelid","peerdetails","usercontext","userconfig","register");
foreach ($set_globals as $var) {
	if (isset($_REQUEST[$var])) {
		$$var = stripslashes( $_REQUEST[$var] );
	}
}

$dialrules = array();
if (isset($_REQUEST["dialrules"])) {
	//$dialpattern = $_REQUEST["dialpattern"];
	$dialrules = explode("\n",$_REQUEST["dialrules"]);

	if (!$dialrules) {
		$dialrules = array();
	}
	
	foreach (array_keys($dialrules) as $key) {
		//trim it
		$dialrules[$key] = trim($dialrules[$key]);
		
		// remove blanks
		if ($dialrules[$key] == "") unset($dialrules[$key]);
		
		// remove leading underscores (we do that on backend)
		if ($dialrules[$key][0] == "_") $dialrules[$key] = substr($dialrules[$key],1);
	}
	
	// check for duplicates, and re-sequence
	$dialrules = array_values(array_unique($dialrules));
}

//if submitting form, update database
switch ($action) {
	case "addtrunk":
		$trunknum = core_trunks_add($tech, $channelid, $dialoutprefix, $maxchans, $outcid, $peerdetails, $usercontext, $userconfig, $register, $dialrules);
		
		core_trunks_addDialRules($trunknum, $dialrules);
		needreload();
		
		$extdisplay = "OUT_".$trunknum; // make sure we're now editing the right trunk
	break;
	case "edittrunk":
		core_trunks_edit($trunknum, $channelid, $dialoutprefix, $maxchans, $outcid, $peerdetails, $usercontext, $userconfig, $register);
		
		/* //DIALRULES
		deleteTrunkRules($channelid);
		addTrunkRules($channelid, $dialrules);
		*/
		
		// this can rewrite too, so edit is the same
		core_trunks_addDialRules($trunknum, $dialrules);
		needreload();
	break;
	case "deltrunk":
	
		core_trunks_del($trunknum);
		
		/* //DIALRULES
		deleteTrunkRules($channelid);
		*/
		core_trunks_deleteDialRules($trunknum);
		needreload();
		
		$extdisplay = ''; // resets back to main screen
	break;
	case "populatenpanxx": 
		if (preg_match("/^([2-9]\d\d)-?([2-9]\d\d)$/", $_REQUEST["npanxx"], $matches)) {
			// first thing we do is grab the exch:
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_URL, "http://members.dandy.net/~czg/lca_prefix.php?npa=".$matches[1]."&nxx=".$matches[2]."&ocn=&pastdays=0&nextdays=0");
			curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Linux; Amportal Local Trunks Configuration)");
			$str = curl_exec($ch);
			curl_close($ch);
			
			if (preg_match("/exch=(\d+)/",$str, $matches)) {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_URL, "http://members.dandy.net/~czg/lprefix.php?exch=".$matches[1]);
				curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Linux; Amportal Local Trunks Configuration)");
				$str = curl_exec($ch);
				curl_close($ch);
				
				foreach (explode("\n", $str) as $line) {
					if (preg_match("/^(\d{3});(\d{3})/", $line, $matches)) {
						$dialrules[] = "1".$matches[1]."|".$matches[2]."XXXX";
						//$localprefixes[] = "1".$matches[1].$matches[2];
					}
				}
				
				// check for duplicates, and re-sequence
				$dialrules = array_values(array_unique($dialrules));
			} else {
				$errormsg = _("Error fetching prefix list for: "). $_REQUEST["npanxx"];
			}
			
		} else {
			// what a horrible error message... :p
			$errormsg = _("Invalid format for NPA-NXX code (must be format: NXXNXX)");
		}
		
		if (isset($errormsg)) {
			echo "<script language=\"javascript\">alert('".addslashes($errormsg)."');</script>";
			unset($errormsg);
		}
	break;
}
	

	
//get all rows from globals
$sql = "SELECT * FROM globals";
$globals = $db->getAll($sql);
if(DB::IsError($globals)) {
	die($globals->getMessage());
}

//create a set of variables that match the items in global[0]
foreach ($globals as $global) {
	${trim($global[0])} = htmlentities($global[1]);
}

?>
</div>

<div class="rnav">
    <li><a id="<?php  echo ($extdisplay=='' ? 'current':'') ?>" href="config.php?display=<?php echo urlencode($display)?>"><?php echo _("Add Trunk")?></a></li>

<?php 
//get existing trunk info
$tresults = core_trunks_list();

foreach ($tresults as $tresult) {
    echo "<li><a id=\"".($extdisplay==$tresult[0] ? 'current':'')."\" href=\"config.php?display=".urlencode($display)."&extdisplay=".urlencode($tresult[0])."\" title=\"".urlencode($tresult[1])."\">"._("Trunk")." ".substr(ltrim($tresult[1],"AMP:"),0,15)."</a></li>";
}

?>
</div>

<div class="content">

<?php 

if (!$tech && !$extdisplay) {
?>
	<h2><?php echo _("Add a Trunk")?></h2>
	<a href="<?php echo $_SERVER['PHP_SELF'].'?display='.urlencode($display); ?>&tech=ZAP"><?php echo _("Add ZAP Trunk")?></a><br><br>
	<a href="<?php echo $_SERVER['PHP_SELF'].'?display='.urlencode($display); ?>&tech=IAX2"><?php echo _("Add IAX2 Trunk")?></a><br><br>
	<a href="<?php echo $_SERVER['PHP_SELF'].'?display='.urlencode($display); ?>&tech=SIP"><?php echo _("Add SIP Trunk")?></a><br><br>
	<a href="<?php echo $_SERVER['PHP_SELF'].'?display='.urlencode($display); ?>&tech=ENUM"><?php echo _("Add ENUM Trunk")?></a><br><br>
	<a href="<?php echo $_SERVER['PHP_SELF'].'?display='.urlencode($display); ?>&tech=CUSTOM"><?php echo _("Add Custom Trunk")?></a><br><br>
<?php 
} else {
	if ($extdisplay) {
		//list($trunk_tech, $trunk_name) = explode("/",$tname);
		//if ($trunk_tech == "IAX2") $trunk_tech = "IAX"; // same thing
		$tech = core_trunks_getTrunkTech($trunknum);

		$outcid = ${"OUTCID_".$trunknum};
		$maxchans = ${"OUTMAXCHANS_".$trunknum};
		$dialoutprefix = ${"OUTPREFIX_".$trunknum};
		
		if ($tech!="enum") {
	
			if (!isset($channelid)) {
				$channelid = core_trunks_getTrunkTrunkName($trunknum); 
			}

			if ($tech!="custom") {  // custom trunks will not have user/peer details in database table
				// load from db
				if (!isset($peerdetails)) {	
					$peerdetails = core_trunks_getTrunkPeerDetails($trunknum);
				}
	
				if (!isset($usercontext)) {	
					$usercontext = core_trunks_getTrunkUserContext($trunknum); 
				}
	
				if (!isset($userconfig)) {	
					$userconfig = core_trunks_getTrunkUserConfig($trunknum);
				}
					
				if (!isset($register)) {	
					$register = core_trunks_getTrunkRegister($trunknum);
				}
			}
		}
		
		/* //DIALRULES
		if (!isset($_REQUEST["dialrules"])) { // we check REQUEST because dialrules() is always an array
			$dialrules = getTrunkDialRules($trunknum);
		}
		*/
		
		if (count($dialrules) == 0) {
			if ($temp = core_trunks_getDialRules($trunknum)) {
				foreach ($temp as $key=>$val) {
					// extract all ruleXX keys
					if (preg_match("/^rule\d+$/",$key)) {
						$dialrules[] = $val;
					}
				}
			}
			unset($temp);
		}
		
		echo "<h2>".sprintf(_("Edit %s Trunk"),strtoupper($tech))."</h2>";
?>
		<p><a title="<?php echo $channelid ?>" href="config.php?display=<?php echo urlencode($display) ?>&extdisplay=<?php echo urlencode($extdisplay) ?>&action=deltrunk"><?php echo _("Delete Trunk")?> <?php  echo substr($channelid,0,20); ?></a></p>
<?php 

		// find which routes use this trunk
		$routes = core_trunks_gettrunkroutes($trunknum);
		$num_routes = count($routes);
		if ($num_routes > 0) {
			echo "<a href=# class=\"info\">"._("In use by")." ".$num_routes." ".($num_routes == 1 ? _("route") : _("routes"))."<span>";
			foreach($routes as $route=>$priority) {
				echo _("Route")." <b>".$route."</b>: "._("Sequence")." <b>".$priority."</b><br>";
			}
			echo "</span></a>";
		} else {
			echo "<b>WARNING:</b> <a href=# class=\"info\">"._("This trunk is not used by any routes!")."<span>";
			echo _("This trunk will not be able to be used for outbound calls until a route is setup that uses it. Click on <b>Outbound Routes</b> to setup routing.");
			echo "</span></a>";
		}
		echo "<br><br>";

	} else {
		// set defaults
		$outcid = "";
		$maxchans = "";
		$dialoutprefix = "";
		
		if ($tech == "zap") {
			$channelid = "g0";
		} else {
			$channelid = "";
		}
		
		// only for iax2/sip
		$peerdetails = "host=***provider ip address***\nusername=***userid***\nsecret=***password***\ntype=peer";
		$usercontext = "";
		$userconfig = "secret=***password***\ntype=user\ncontext=from-pstn";
		$register = "";
		
		$localpattern = "NXXXXXX";
		$lddialprefix = "1";
		$areacode = "";
	
		echo "<h2>".sprintf("Add %s Trunk",strtoupper($tech))."</h2>";
	} 
?>
	
		<form name="trunkEdit" action="config.php" method="post">
			<input type="hidden" name="display" value="<?php echo $display?>"/>
			<input type="hidden" name="extdisplay" value="<?php echo $extdisplay ?>"/>
			<input type="hidden" name="action" value=""/>
			<input type="hidden" name="tech" value="<?php echo $tech?>"/>
			<table>
			<tr>
				<td colspan="2">
					<h4><?php echo _("General Settings")?></h4>
				</td>
			</tr>
			<tr>
				<td>
					<a href=# class="info"><?php echo _("Outbound Caller ID")?><span><br><?php echo _("Setting this option will override all clients' caller IDs for calls placed out this trunk<br><br>Format: <b>\"caller name\" &lt;#######&gt;</b><br><br>Leave this field blank to simply pass client caller IDs.")?><br><br></span></a>: 
				</td><td>
					<input type="text" size="20" name="outcid" value="<?php echo htmlspecialchars($outcid);?>"/>
				</td>
			</tr>
			<tr>
				<td>
					<a href=# class="info"><?php echo _("Maximum channels")?><span><?php echo _("Controls the maximum number of channels (simultaneous calls) that can be used on this trunk, including both incoming and outgoing calls. Leave blank to specify no maximum.")?></span></a>: 
				</td><td>
					<input type="text" size="3" name="maxchans" value="<?php echo htmlspecialchars($maxchans); ?>"/>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<br><h4><?php echo _("Outgoing Dial Rules")?></h4>
				</td>
			</tr>
			<tr>
				<td valign="top">
					<a href=# class="info"><?php echo _("Dial Rules")?><span><?php echo _("A Dial Rule controls how calls will be dialed on this trunk. It can be used to add or remove prefixes. Numbers that don't match any patterns defined here will be dialed as-is. Note that a pattern without a + or | (to add or remove a prefix) is useless.")?><br><br><b><?php echo _("Rules:")?></b><br>
	<strong>X</strong>&nbsp;&nbsp;&nbsp; <?php echo _("matches any digit from 0-9")?><br>
	<strong>Z</strong>&nbsp;&nbsp;&nbsp; <?php echo _("matches any digit from 1-9")?><br>
	<strong>N</strong>&nbsp;&nbsp;&nbsp; <?php echo _("matches any digit from 2-9")?><br>
	<strong>[1237-9]</strong>&nbsp;   <?php echo _("matches any digit or letter in the brackets (in this example, 1,2,3,7,8,9)")?><br>
	<strong>.</strong>&nbsp;&nbsp;&nbsp; <?php echo _("wildcard, matches one or more characters (not allowed before a | or +)")?><br>
	<strong>|</strong>&nbsp;&nbsp;&nbsp; <?php echo _("removes a dialing prefix from the number (for example, 613|NXXXXXX would match when some dialed \"6135551234\" but would only pass \"5551234\" to the trunk)")?>
	<strong>+</strong>&nbsp;&nbsp;&nbsp; <?php echo _("adds a dialing prefix from the number (for example, 1613+NXXXXXX would match when some dialed \"5551234\" and would pass \"16135551234\" to the trunk)")?>
					</span></a>:
				</td><td valign="top">&nbsp;
					<textarea id="dialrules" cols="20" rows="<?php  $rows = count($dialrules)+1; echo (($rows < 5) ? 5 : (($rows > 20) ? 20 : $rows) ); ?>" name="dialrules"><?php echo implode("\n",$dialrules);?></textarea><br>
					
					<input type="submit" style="font-size:10px;" value="<?php echo _("Clean & Remove duplicates")?>" />
				</td>
			</tr>
			<tr>
				<td>
					<a href=# class="info"><?php echo _("Dial rules wizards")?><span>
					<strong><?php echo _("Always add prefix to local numbers")?></strong> <?php echo _("is useful for VoIP trunks, where if a number is dialed as \"5551234\", it can be converted to \"16135551234\".")?><br>
					<strong><?php echo _("Remove prefix from local numbers")?></strong> <?php echo _("is useful for ZAP trunks, where if a local number is dialed as \"16135551234\", it can be converted to \"555-1234\".")?><br>
					<strong><?php echo _("Lookup and remove local prefixes")?></strong> <?php echo _("is the same as Remove prefix from local numbers, but uses the database at http://members.dandy.net/~czg/search.html to find your local calling area (NA-only)")?><br>
					</span></a>:
				</td><td valign="top">&nbsp;&nbsp;<select id="autopop" name="autopop" onChange="changeAutoPop(); ">
						<option value="" SELECTED><?php echo _("(pick one)")?></option>
						<option value="always"><?php echo _("Always add prefix to local numbers")?></option>
						<option value="remove"><?php echo _("Remove prefix from local numbers")?></option>
						<option value="lookup"><?php echo _("Lookup and remove local prefixes")?></option>
					</select>
				</td>
			</tr>
			<input id="npanxx" name="npanxx" type="hidden" />
			<script language="javascript">
			
			function populateLookup() {
<?php 
	if (function_exists("curl_init")) { // curl is installed
?>				
				//var npanxx = prompt("What is your areacode + prefix (NPA-NXX)?", document.getElementById('areacode').value);
				do {
					var npanxx = <?php echo 'prompt("'._("What is your areacode + prefix (NPA-NXX)?\\n\\n(Note: this database contains North American numbers only, and is not guaranteed to be 100% accurate. You will still have the option of modifying results.)\\n\\nThis may take a few seconds.".'")')?>;
					if (npanxx == null) return;
				} while (!npanxx.match("^[2-9][0-9][0-9][-]?[2-9][0-9][0-9]$") && <?php echo '!alert("'._("Invalid NPA-NXX. Must be of the format \'NXX-NXX\'").'")'?>);
				
				document.getElementById('npanxx').value = npanxx;
				trunkEdit.action.value = "populatenpanxx";
				trunkEdit.submit();
<?php  
	} else { // curl is not installed
?>
				<?php echo 'alert("'._("Error: Cannot continue!\\n\\nPrefix lookup requires cURL support in PHP on the server. Please install or enable cURL support in your PHP installation to use this function. See http://www.php.net/curl for more information.").'")'?>;
<?php 
	}
?>
			}
			
			function populateAlwaysAdd() {
				do {
					var localpattern = <?php echo 'prompt("'._("What is the local dialing pattern?\\n\\n(ie. NXXNXXXXXX for US/CAN 10-digit dialing, NXXXXXX for 7-digit)").'"'?>,"NXXXXXX");
					if (localpattern == null) return;
				} while (!localpattern.match('^[0-9#*ZXN\.]+$') && <?php echo '!alert("'._("Invalid pattern. Only 0-9, #, *, Z, N, X and . are allowed.").'")'?>);
				
				do {
					var localprefix = <?php echo 'prompt("'._("What prefix should be added to the dialing pattern?\\n\\n(ie. for US/CAN, 1+areacode, ie, \'1613\')?").'")'?>;
					if (localprefix == null) return;
				} while (!localprefix.match('^[0-9#*]+$') && <?php echo '!alert("'._("Invalid prefix. Only dialable characters (0-9, #, and *) are allowed.").'")'?>);

				dialrules = document.getElementById('dialrules');
				if (dialrules.value[dialrules.value.length-1] != '\n') {
					dialrules.value = dialrules.value + '\n';
				}
				dialrules.value = dialrules.value + localprefix + '+' + localpattern + '\n';
			}
			
			function populateRemove() {
				do {
					var localprefix = <?php echo 'prompt("'._("What prefix should be removed from the number?\\n\\n(ie. for US/CAN, 1+areacode, ie, \'1613\')").'")'?>;
					if (localprefix == null) return;
				} while (!localprefix.match('^[0-9#*ZXN\.]+$') && <?php echo '!alert("'._('Invalid prefix. Only 0-9, #, *, Z, N, and X are allowed.').'")'?>);
				
				do {
					var localpattern = <?php echo 'prompt("'._("What is the dialing pattern for local numbers after")?> "+localprefix+"? \n\n<?php echo _("(ie. NXXNXXXXXX for US/CAN 10-digit dialing, NXXXXXX for 7-digit)").'"'?>,"NXXXXXX");
					if (localpattern == null) return;
				} while (!localpattern.match('^[0-9#*ZXN\.]+$') && <?php echo '!alert("'._("Invalid pattern. Only 0-9, #, *, Z, N, X and . are allowed.").'")'?>);
				
				dialrules = document.getElementById('dialrules');
				if (dialrules.value[dialrules.value.length-1] != '\n') {
					dialrules.value = dialrules.value + '\n';
				}
				dialrules.value = dialrules.value + localprefix + '|' + localpattern + '\n';
			}
			
			function changeAutoPop() {
				switch(document.getElementById('autopop').value) {
					case "always":
						populateAlwaysAdd();
					break;
					case "remove":
						populateRemove();
					break;
					case "lookup":
						populateLookup();
					break;
				}
				document.getElementById('autopop').value = '';
			}
			</script>
<?php /* //DIALRULES
			<tr>
				<td>
					<a href=# class="info">Dial rules<span>The area code this trunk is in.</span></a>: 
				</td><td>&nbsp;
					<select id="dialrulestype" name="dialrulestype" onChange="changeRulesType();">
<?php 
					$rules = array( "asis" => "Don't change number",
							"always" => "Always dial prefix+areacode",
							"local" => "Local 7-digit dialing",
							"local10" => "Local 10-digit dialing");

					foreach ($rules as $value=>$display) {
						echo "<option value=\"".$value."\" ".(($value == $dialrulestype) ? "SELECTED" : "").">".$display."</option>";
					}
?>
					</select>
					
				</td>
			</tr>
			<tr>
				<td>
					<a href=# class="info"><?php echo _("Local dialing pattern<span>The dialing pattern to make a 'local' call.</span>")</a>: 
				</td><td>
					<input id="localpattern" type="text" size="10" maxlength="20" name="localpattern" value="<?php echo $localpattern ?>"/>
					
				</td>
			</tr>
			<tr>
				<td>
					<a href=# class="info"><?php echo _("Long-distance dial prefix<span>The prefix for dialing long-distance numbers. In north america, this should be \"1\".</span>")?></a>: 
				</td><td>
					<input id="lddialprefix" type="text" size="3" maxlength="6" name="lddialprefix" value="<?php echo $lddialprefix ?>"/>
					
				</td>
			</tr>
			<tr>
				<td>
					<a href=# class="info"><?php echo _("Local LD prefix<span>The area code this trunk is in. Any 7-digit numbers that don't match a number in the below list will have dialprefix+areacode added to them. </span>")?></a>: 
				</td><td>
					<input id="areacode" type="text" size="3" maxlength="6" name="areacode" value="<?php echo $areacode ?>"/>
					
				</td>
			</tr>
			<tr>
				<td valign="top">
					<a href=# class="info"><?php echo _("Local prefixes<span>This should be a list of local areacodes + prefixes to use for local dialing.</span>")?></a>: 
				</td><td valign="top">&nbsp;
					<textarea id="localprefixes" cols="8" rows="<?php  $rows = count($localprefixes)+1; echo (($rows < 5) ? 5 : (($rows > 20) ? 20 : $rows) ); ?>" name="localprefixes"><?php echo  implode("\n",$localprefixes);?></textarea><br>
					 
					<input id="npanxx" name="npanxx" type="hidden" /><br>
					<a href=# class="info"><?php echo _("Populate with local rules<span>Do a lookup from http://members.dandy.net/~czg/search.html to find all local-reachable area codes and phone numbers.</span>")?></a>: <input type="button" value="Go" onClick="checkPopulate();" />
					<br><br>
				</td>
			</tr>
			<script language="javascript">
			
			function checkPopulate() {
				//var npanxx = prompt("What is your areacode + prefix (NPA-NXX)?", document.getElementById('areacode').value);
				var npanxx = <?php echo 'prompt("'._("What is your areacode + prefix (NPA-NXX)?").'")'?>;
				
				if (npanxx.match("^[2-9][0-9][0-9][-]?[2-9][0-9][0-9]$")) {
					document.getElementById('npanxx').value = npanxx;
					trunkEdit.action.value = "populatenpanxx";
					trunkEdit.submit();
				} else if (npanxx != null) {
					<?php echo 'alert("'._("Invalid format for NPA-NXX code (must be format: NXXNXX)").'")'?>;
				}
			}
			
			function changeRulesType() {
				switch(document.getElementById('dialrulestype').value) {
					case "always":
						document.getElementById('lddialprefix').disabled = false;
						document.getElementById('areacode').disabled = false;
						document.getElementById('localprefixes').disabled = true;
					break;
					case "local":
					case "local10":
						document.getElementById('lddialprefix').disabled = false;
						document.getElementById('areacode').disabled = false;
						document.getElementById('localprefixes').disabled = false;
					break;
					case "asis":
					default:
						document.getElementById('lddialprefix').disabled = true;
						document.getElementById('areacode').disabled = true;
						document.getElementById('localprefixes').disabled = true;
					break;
				}
			}
			changeRulesType();
			</script>
*/?>
			<tr>
				<td>
					<a href=# class="info"><?php echo _("Outbound Dial Prefix")?><span><?php echo _("The outbound dialing prefix is used to prefix a dialing string to all outbound calls placed on this trunk. For example, if this trunk is behind another PBX or is a Centrex line, then you would put 9 here to access an outbound line.<br><br>Most users should leave this option blank.")?></span></a>: 
				</td><td>
					<input type="text" size="8" name="dialoutprefix" value="<?php echo htmlspecialchars($dialoutprefix) ?>"/>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<br><h4><?php echo _("Outgoing Settings")?></h4>
				</td>
			</tr>
	
	<?php 
	switch ($tech) {
		case "zap":
	?>
				<tr>
					<td>
						<a href=# class="info"><?php echo _("Zap Identifier (trunk name)")?><span><br><?php echo _("ZAP channels are referenced either by a group number or channel number (which is defined in zapata.conf).  <br><br>The default setting is <b>g0</b> (group zero).")?><br><br></span></a>: 
					</td><td>
						<input type="text" size="8" name="channelid" value="<?php echo htmlspecialchars($channelid) ?>"/>
						<input type="hidden" size="14" name="usercontext" value="notneeded"/>
					</td>
				</tr>
	<?php 
		break;
		case "enum":
		break;
		case "custom":
	?>
				<tr>
					<td>
						<a href=# class="info"><?php echo _("Custom Dial String")?><span><?php echo _("Define the custom Dial String.  Include the token")?> $OUTNUM$ <?php echo _("wherever the number to dial should go.<br><br><b>examples:</b><br><br>CAPI/XXXXXXXX:b")?>$OUTNUM$<?php echo _(",30,r<br>H323/")?>$OUTNUM$@XX.XX.XX.XX<br>OH323/$OUTNUM$@XX.XX.XX.XX:XXXX<br>vpb/1-1/$OUTNUM$</span></a>: 
					</td><td>
						<input type="text" size="35" maxlength="46" name="channelid" value="<?php echo htmlspecialchars($channelid) ?>"/>
						<input type="hidden" size="14" name="usercontext" value="notneeded"/>
					</td>
				</tr>	
	<?php
		break;
		default:
	?>
				<tr>
					<td>
						<a href=# class="info"><?php echo _("Trunk Name")?><span><br><?php echo _("Give this trunk a unique name.  Example: myiaxtel")?><br><br></span></a>: 
					</td><td>
						<input type="text" size="14" name="channelid" value="<?php echo htmlspecialchars($channelid) ?>"/>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<a href=# class="info"><?php echo _("PEER Details")?><span><br><?php echo _("Modify the default PEER connection parameters for your VoIP provider.<br><br>You may need to add to the default lines listed below, depending on your provider.")?><br><br></span></a>: 
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<textarea rows="10" cols="40" name="peerdetails"><?php echo htmlspecialchars($peerdetails) ?></textarea>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<br><h4><?php echo _("Incoming Settings")?></h4>
					</td>
				</tr>
				<tr>
					<td>
						<a href=# class="info"><?php echo _("USER Context")?><span><br><?php echo _("This is most often the account name or number your provider expects.<br><br>This USER Context will be used to define the below user details.")?></span></a>: 
					</td><td>
						<input type="text" size="14" name="usercontext" value="<?php echo htmlspecialchars($usercontext)  ?>"/>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<a href=# class="info"><?php echo _("USER Details")?><span><br><?php echo _("Modify the default USER connection parameters for your VoIP provider.")?><br><br><?php echo _("You may need to add to the default lines listed below, depending on your provider.")?><br><br></span></a>: 
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<textarea rows="10" cols="40" name="userconfig"><?php echo htmlspecialchars($userconfig); ?></textarea>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<br><h4><?php echo _("Registration")?></h4>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<a href=# class="info"><?php echo _("Register String")?><span><br><?php echo _("Most VoIP providers require your system to REGISTER with theirs. Enter the registration line here.<br><br>example:<br><br>username:password@switch.voipprovider.com")?><br><br></span></a>: 
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<input type="text" size="40" name="register" value="<?php echo htmlspecialchars($register) ?>"/>
					</td>
				</tr>
	<?php 
		break;
	}
	?>
				
			<tr>
				<td colspan="2">
					<h6><input name="Submit" type="button" value="<?php echo _("Submit Changes")?>" onclick="checkTrunk(trunkEdit, '<?php echo ($extdisplay ? "edittrunk" : "addtrunk") ?>')"></h6>
				</td>
			</tr>
			</table>
		</form>
<?php  
}
?>



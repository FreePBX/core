<h2><?php echo sprintf(_("%s %s Trunk"),(empty($extdisplay) ? _('Add'): _('Edit')),$upper_tech)?></h2>
<?php if(!empty($extdisplay)) {?>
	<p>
		<a href="config.php?display=trunks&amp;extdisplay=<?php echo urlencode($extdisplay) ?>&amp;action=deltrunk">
			<span>
				<img width="16" height="16" border="0" title="'.$tlabel.'" alt="" src="images/core_delete.png"/><?php echo sprintf(_('Delete Trunk %s'),substr($trunk_name,0,20))?>
			</span>
		</a>
	</p>
	<?php if ($num_routes > 0) { ?>
		<a href=# class="info"><?php echo _("In use by")." ".$num_routes." ".($num_routes == 1 ? _("route") : _("routes"))?>
			<span>
			<?php foreach($routes as $route=>$priority) { ?>
				<?php echo _("Route")?> <b><?php echo $route ?></b>: <?php echo _("Sequence")?> <b><?php echo $priority ?></b><br>
			<?php } ?>
			</span>
		</a>
	<?php } else { ?>
		<b><?php echo _("WARNING:")?></b>
		<a href=# class="info"><?php echo _("This trunk is not used by any routes!")?>
			<span>
				<?php echo _("This trunk will not be able to be used for outbound calls until a route is setup that uses it. Click on <b>Outbound Routes</b> to setup routing.") ?>
			</span>
		</a>
	<?php } ?>
<?php } ?>
<?php if(!empty($helptext)) {?>
	<div class="alert alert-warning"><?php echo $helptext?></div>
<?php } ?>
		<form enctype="multipart/form-data" name="trunkEdit" id="trunkEdit" action="config.php" method="post" onsubmit="return trunkEdit_onsubmit('<?php echo ($extdisplay ? "edittrunk" : "addtrunk") ?>');">
			<input type="hidden" name="display" value="<?php echo $display?>"/>
			<input type="hidden" name="extdisplay" value="<?php echo $extdisplay ?>"/>
			<input type="hidden" name="action" value="<?php echo ($extdisplay ? "edittrunk" : "addtrunk") ?>"/>
			<input type="hidden" name="tech" value="<?php echo $tech?>"/>
			<input type="hidden" name="provider" value="<?php echo $provider?>"/>
			<input type="hidden" name="sv_trunk_name" value="<?php echo $trunk_name?>"/>
			<input type="hidden" name="sv_usercontext" value="<?php echo $usercontext?>"/>
			<input type="hidden" name="sv_channelid" value="<?php echo $channelid?>"/>
			<input id="npanxx" name="npanxx" type="hidden" />
			<table>
			<tr>
				<td colspan="2">
					<h4><?php echo _("General Settings")?><hr></h4>
				</td>
			</tr>
			<tr>
				<td>
					<a href=# class="info"><?php echo _("Trunk Name")?><span><?php echo _("Descriptive Name for this Trunk")?></span></a>:
				</td><td>
					<input type="text" size="30" name="trunk_name" value="<?php echo $trunk_name;?>" tabindex="<?php echo ++$tabindex;?>"/>
				</td>
			</tr>
			<tr>
				<td>
					<a href=# class="info"><?php echo _("Outbound CallerID")?><span><?php echo _("CallerID for calls placed out on this trunk<br><br>Format: <b>&lt;#######&gt;</b>. You can also use the format: \"hidden\" <b>&lt;#######&gt;</b> to hide the CallerID sent out over Digital lines if supported (E1/T1/J1/BRI/SIP/IAX).")?></span></a>:
				</td><td>
					<input type="text" size="30" name="outcid" value="<?php echo $outcid;?>" tabindex="<?php echo ++$tabindex;?>"/>
				</td>
			</tr>
			<tr>

	    <tr>
				<td>
					<a href="#" class="info"><?php echo _("CID Options")?><span><?php echo _("Determines what CIDs will be allowed out this trunk. IMPORTANT: EMERGENCY CIDs defined on an extension/device will ALWAYS be used if this trunk is part of an EMERGENCY Route regardless of these settings.<br />Allow Any CID: all CIDs including foreign CIDS from forwarded external calls will be transmitted.<br />Block Foreign CIDs: blocks any CID that is the result of a forwarded call from off the system. CIDs defined for extensions/users are transmitted.<br />Remove CNAM: this will remove CNAM from any CID sent out this trunk<br />Force Trunk CID: Always use the CID defined for this trunk except if part of any EMERGENCY Route with an EMERGENCY CID defined for the extension/device.") . _("Intra-Company Routes will always trasmit an extension's internal number and name.");?></span></a>:
				</td><td>

			    <select name="keepcid" tabindex="<?php echo ++$tabindex;?>">
			    <?php
				    $default = (isset($keepcid) ? $keepcid : 'off');
				    echo '<option value="off"' . ($default == 'off'  ? ' SELECTED' : '').'>'._("Allow Any CID")."\n";
				    echo '<option value="on"'  . ($default == 'on'   ? ' SELECTED' : '').'>'._("Block Foreign CIDs")."\n";
				    echo '<option value="cnum"'. ($default == 'cnum' ? ' SELECTED' : '').'>'._("Remove CNAM")."\n";
				    echo '<option value="all"' . ($default == 'all'  ? ' SELECTED' : '').'>'._("Force Trunk CID")."\n";
			    ?>
			    </select>
				</td>
      </tr>

			<tr>
				<td>
<?php if ($tech == "sip" || substr($tech,0,3) == "iax") {
		$pr_tech = ($tech == "iax") ? "iax2":$tech; ?>
					<a href=# class="info"><?php echo _("Maximum Channels")?><span><?php echo sprintf(_("Controls the maximum number of outbound channels (simultaneous calls) that can be used on this trunk. To count inbound calls against this maximum, use the auto-generated context: %s as the inbound trunk's context. (see extensions_additional.conf) Leave blank to specify no maximum."),((isset($channelid) && trim($channelid)!="")?"from-trunk-$pr_tech-$channelid":"from-trunk-[trunkname]"))?></span></a>:
<?php } else { ?>
					<a href=# class="info"><?php echo _("Maximum Channels")?><span><?php echo _("Controls the maximum number of outbound channels (simultaneous calls) that can be used on this trunk. Inbound calls are not counted against the maximum. Leave blank to specify no maximum.")?></span></a>:
<?php } ?>
				</td><td>
					<input type="text" size="3" name="maxchans" value="<?php echo htmlspecialchars($maxchans); ?>" tabindex="<?php echo ++$tabindex;?>"/>
				</td>
			</tr>

<?php
	$data['name'] = $data['id'] = 'dialopts';
	if ($dialopts !== false) {
		$data['value'] = $dialopts;
	} else {
		$data['disabled'] = true;
	}
	$data['size'] = '20';
	$data['tabindex'] = $tabindex++;

	$dialopts_label = fpbx_label(_('Asterisk Trunk Dial Options'), _('Asterisk Dial command options to be used when calling out this trunk. To override the Advanced Settings default, check the box and then provide the required options for this trunk')) . "\n";
	$dialopts_box = fpbx_form_input_check($data, '', '', '<small>' . _('Override') . '</small>', $amp_conf['TRUNK_OPTIONS'], true) . "\n";
?>
			<tr>
				<td>
					<?php echo $dialopts_label; ?>
				</td><td>
					<?php echo $dialopts_box; ?>
				</td>
			</tr>

			<tr>
			    <td><a class="info" href="#"><?php echo _("Continue if Busy")?><span><?php echo _("Normally the next trunk is only tried upon a trunk being 'Congested' in some form, or unavailable. Checking this box will force a failed call to always continue to the next configured trunk or destination even when the channel reports BUSY or INVALID NUMBER.")?></span></a>:
			    </td>
			    <td>
				<input type='checkbox'  tabindex="<?php echo ++$tabindex;?>"name='continue' id="continue" <?php if ($continue=="on") { echo 'CHECKED'; }?> ><label for='continue'><small><?php echo _("Check to always try next trunk")?></small></label>
			    </td>
			</tr>

			<tr>
			    <td><a class="info" href="#"><?php echo _("Disable Trunk")?><span><?php echo _("Check this to disable this trunk in all routes where it is used.")?></span></a>:
			    </td>
			    <td>
				<input type='checkbox'  tabindex="<?php echo ++$tabindex;?>"name='disabletrunk' id="disabletrunk" <?php if ($disabletrunk=="on") { echo 'CHECKED'; }?> OnClick='disable_verify(disabletrunk); return true;'><label for='disabletrunk'><small><?php echo _("Disable")?></small></label>
			    </td>
			</tr>

<?php if ($failtrunk_enable && $failtrunk || $amp_conf['DISPLAY_MONITOR_TRUNK_FAILURES_FIELD']) { ?>
			<tr>
			    <td><a class="info" href="#"><?php echo _("Monitor Trunk Failures")?><span><?php echo _("If checked, supply the name of a custom AGI Script that will be called to report, log, email or otherwise take some action on trunk failures that are not caused by either NOANSWER or CANCEL.")?></span></a>:
			    </td>
			    <td>
				<input <?php if (!$failtrunk_enable) echo "disabled style='background: #DDD;'"?> type="text" size="20" name="failtrunk" value="<?php echo htmlspecialchars($failtrunk)?>"/>
				<input type='checkbox' tabindex="<?php echo ++$tabindex;?>" name='failtrunk_enable' id="failtrunk_enable" value='1' <?php if ($failtrunk_enable) { echo 'CHECKED'; }?> OnClick='disable_field(failtrunk,failtrunk_enable); return true;'><small><?php echo _("Enable")?></small>
			    </td>
			</tr>
<?php }
	if(!$amp_conf['ENABLEOLDDIALPATTERNS']) {
	?>
    <tr>
      <td colspan="2"><h4>
      <a href=# class="info"><?php echo _("Dial Number Manipulation Rules")?><span><?php echo _("A Dial Rule controls how calls will be dialed on this trunk. It can be used to add or remove prefixes. Numbers that don't match any patterns defined here will be dialed as-is. Note that a pattern without a + or | (to add or remove a prefix) will not make any changes but will create a match. Only the first matched rule will be executed and the remaining rules will not be acted on.")?><br /><br /><b><?php echo _("Rules:")?></b><br />
				<strong>X</strong>&nbsp;&nbsp;&nbsp; <?php echo _("matches any digit from 0-9")?><br />
				<strong>Z</strong>&nbsp;&nbsp;&nbsp; <?php echo _("matches any digit from 1-9")?><br />
				<strong>N</strong>&nbsp;&nbsp;&nbsp; <?php echo _("matches any digit from 2-9")?><br />
				<strong>[1237-9]</strong>&nbsp;   <?php echo _("matches any digit or letter in the brackets (in this example, 1,2,3,7,8,9)")?><br />
				<strong>.</strong>&nbsp;&nbsp;&nbsp; <?php echo _("wildcard, matches one or more characters (not allowed before a | or +)")?><br />
				<strong>|</strong>&nbsp;&nbsp;&nbsp; <?php echo _("removes a dialing prefix from the number (for example, 613|NXXXXXX would match when some dialed \"6135551234\" but would only pass \"5551234\" to the trunk)")?>
				<strong>+</strong>&nbsp;&nbsp;&nbsp; <?php echo _("adds a dialing prefix from the number (for example, 1613+NXXXXXX would match when some dialed \"5551234\" and would pass \"16135551234\" to the trunk)")?><br /><br />
				<?php echo _("You can also use both + and |, for example: 01+0|1ZXXXXXXXXX would match \"016065551234\" and dial it as \"0116065551234\" Note that the order does not matter, eg. 0|01+1ZXXXXXXXXX does the same thing."); ?></span></a>
      <hr></h4></td>
    </tr>
<?php
 } else {
	?>
	<tr>
		<td colspan="2"><h4>
			<a href=# class="info"><?php echo _("Dialed Number Manipulation Rules")?><span>
				<?php echo _("These rules can manipulate the dialed number before sending it out this trunk. If no rule applies, the number is not changed. The original dialed number is passed down from the route where some manipulation may have already occurred. This trunk has the option to further manipulate the number. If the number matches the combined values in the <b>prefix</b> plus the <b>match pattern</b> boxes, the rule will be applied and all subsequent rules ignored.<br/> Upon a match, the <b>prefix</b>, if defined, will be stripped. Next the <b>prepend</b> will be inserted in front of the <b>match pattern</b> and the resulting number will be sent to the trunk. All fields are optional.")?><br /><br /><b><?php echo _("Rules:")?></b><br />
					<b>X</b>&nbsp;&nbsp;&nbsp; <?php echo _("matches any digit from 0-9")?><br />
					<b>Z</b>&nbsp;&nbsp;&nbsp; <?php echo _("matches any digit from 1-9")?><br />
					<b>N</b>&nbsp;&nbsp;&nbsp; <?php echo _("matches any digit from 2-9")?><br />
					<b>[1237-9]</b>&nbsp;   <?php echo _("matches any digit in the brackets (example: 1,2,3,7,8,9)")?><br />
					<b>.</b>&nbsp;&nbsp;&nbsp; <?php echo _("wildcard, matches one or more dialed digits")?> <br />
				</span></a>
				<hr></h4></td>
			</tr>
	<?php
}
  $pp_tit = _("prepend");
  $pf_tit = _("prefix");
  $mp_tit = _("match pattern");
  if(!$amp_conf['ENABLEOLDDIALPATTERNS']) {
	  ?><tr><td colspan="2"><div class="dialpatterns"><table><?php
	  $dpt_title_class = 'dpt-title dpt-display';
	  foreach ($dialpattern_array as $idx => $pattern) {
		  $tabindex++;
		if ($idx == 50) {
			$dpt_title_class = 'dpt-title dpt-nodisplay';
		}
		$dpt_class = $pattern['prepend_digits'] == '' ? $dpt_title_class : 'dpt-value';
		?>
	    <tr>
	      <td colspan="2">
	        (<input placeholder="f<?php echo $pp_tit ?>" type="text" size="10" id="prepend_digit_<?php echo $idx?>" name="prepend_digit[<?php echo $idx?>]" class="dial-pattern <?php echo $dpt_class ?>" value="<?php echo $pattern['prepend_digits'] ?>" tabindex="<?php echo $tabindex++ ?>">) +
	    <?php
	    $dpt_class = $pattern['match_pattern_prefix'] == '' ? $dpt_title_class : 'dpt-value';
		?>
	        <input placeholder="<?php echo $pf_tit ?>" type="text" size="6" id="pattern_prefix_<?php echo $idx?>" name="pattern_prefix[<?php echo $idx?>]" class="<?php echo $dpt_class ?>" value="<?php echo $pattern['match_pattern_prefix'] ?>" tabindex="<?php echo $tabindex++ ?>"> |
	    <?php
	   $dpt_class = $pattern['match_pattern_pass'] == '' ? $dpt_title_class : 'dpt-value';
		?>
	    <input placeholder="<?php echo $mp_tit ?>" type="text" size="16" id="pattern_pass_<?php echo $idx?>" name="pattern_pass[<?php echo $idx?>]" class="<?php echo $dpt_class ?>" value="<?php echo $pattern['match_pattern_pass'] ?>" tabindex="<?php echo $tabindex++ ?>">
        <img src="images/core_add.png" style="cursor:pointer; float:none; margin-left:0px; margin-bottom:-3px;" alt="<?php echo _("insert")?>" title="<?php echo _('Click here to insert a new pattern before this pattern')?>" onclick="addCustomField('','','',$('#prepend_digit_<?php echo $idx?>').parent().parent(),false)">
		<img src="images/trash.png" style="cursor:pointer; float:none; margin-left:0px; margin-bottom:-3px;" alt="<?php echo _("remove")?>" title="<?php echo _('Click here to remove this pattern')?>" onclick="patternsRemove(<?php echo "$idx" ?>)">
      </td>
    </tr>
<?php
  }
  $next_idx = count($dialpattern_array);
  $idx = !empty($idx) ? $idx : $next_idx;
?>
    <tr>
      <td colspan="2">
        (<input placeholder="<?php echo $pp_tit?>" type="text" size="10" id="prepend_digit_<?php echo $next_idx?>" name="prepend_digit[<?php echo $next_idx?>]" class="dp-prepend dial-pattern dpt-title dpt-display" value="" tabindex="<?php echo ++$tabindex;?>">) +
        <input placeholder="<?php echo $pf_tit?>" type="text" size="6" id="pattern_prefix_<?php echo $next_idx?>" name="pattern_prefix[<?php echo $next_idx?>]" class="dp-prefix dpt-title dpt-display" value="" tabindex="<?php echo ++$tabindex;?>"> |
        <input placeholder="<?php echo $mp_tit?>" type="text" size="16" id="pattern_pass_<?php echo $next_idx?>" name="pattern_pass[<?php echo $next_idx?>]" class="dp-match dpt-title dpt-display" value="" tabindex="<?php echo ++$tabindex;?>">
        <img src="images/core_add.png" style="cursor:pointer; float:none; margin-left:0px; margin-bottom:-3px;" alt="<?php echo _("insert")?>" title="<?php echo _('Click here to insert a new pattern before this pattern')?>" onclick="addCustomField('','','',$('#prepend_digit_<?php echo $idx?>').parent().parent(),false)">
		<img src="images/trash.png" style="cursor:pointer; float:none; margin-left:0px; margin-bottom:-3px;" alt="<?php echo _("remove")?>" title="<?php echo _("Click here to remove this pattern")?>" onclick="patternsRemove(<?php echo "$next_idx" ?>)">

      </td>
    </tr>
    <tr id="last_row"></tr>
    </table>
</div>
<?php if(count($dialpattern_array) > 500) {?>
	<div class="alert alert-warning"><?php echo _('We have detected that you have more than 500 dial patterns, It is advised you turn on the <a href="config.php?display=advancedsettings" target="_as">Advanced Setting</a> called "Enable The Old Style FreePBX Dial Patterns Textarea" to turn this into a simple Text Area')?></div>
<?php } ?>
</tr>
<?php
  $tabindex += 2000; // make room for dynamic insertion of new fields
?>
    <tr><td colspan="2">
      <input type="button" id="dial-pattern-add"  value="<?php echo _("+ Add More Dial Pattern Fields")?>" />
      <input type="button" id="dial-pattern-clear"  value="<?php echo _("Clear all Fields")?>" />
    </td>
</tr>
<?php } else { ?>
	<tr>
		<td colspan="2">
			<textarea textarea name="bulk_patterns" id="bulk_patterns" rows="20" cols="70"><?php foreach ($dialpattern_array as $pattern) {
				$prepend = ($pattern['prepend_digits'] != '') ? $pattern['prepend_digits'].'+' : '';
				$match_pattern_prefix = ($pattern['match_pattern_prefix'] != '') ? $pattern['match_pattern_prefix'].'|' : '';
				$match_cid = ($pattern['match_cid'] != '') ? '/'.$pattern['match_cid'] : '';
				echo $prepend . $match_pattern_prefix . $pattern['match_pattern_pass'] . $match_cid."\n";
			}
			?></textarea>
		</td>
	</tr>
<?php } ?>
			<tr>
				<td>
					<a href=# class="info"><?php echo _("Dial Rules Wizards")?><span>
					<strong><?php echo _("Always dial with prefix")?></strong> <?php echo _("is useful for VoIP trunks, where if a number is dialed as \"5551234\", it can be converted to \"16135551234\".")?><br>
					<strong><?php echo _("Remove prefix from local numbers")?></strong> <?php echo _("is useful for ZAP and DAHDi trunks, where if a local number is dialed as \"6135551234\", it can be converted to \"555-1234\".")?><br>
					<strong><?php echo _("Setup directory assistance")?></strong> <?php echo _("is useful to translate a call to directory assistance")?><br>
					<strong><?php echo _("Lookup numbers for local trunk")?></strong> <?php echo _("This looks up your local number on www.localcallingguide.com (NA-only), and sets up so you can dial either 7 or 10 digits (regardless of what your PSTN is) on a local trunk (where you have to dial 1+area code for long distance, but only 5551234 (7-digit dialing) or 6135551234 (10-digit dialing) for local calls")?><br>
					<strong><?php echo _("Upload from CSV")?></strong> <?php echo sprintf(_("Upload patterns from a CSV file replacing existing entries. If there are no headers then the file must have 3 columns of patterns in the same order as in the GUI. You can also supply headers: %s, %s and %s in the first row. If there are less then 3 recognized headers then the remaining columns will be blank"),'<strong>prepend</strong>','<strong>prefix</strong>','<strong>match pattern</strong>')?><br>
					</span></a>:
				</td><td valign="top"><select id="autopop"  tabindex="<?php echo ++$tabindex;?>" name="autopop" onChange="changeAutoPop(); ">
						<option value="" SELECTED><?php echo _("(pick one)")?></option>
						<option value="always"><?php echo _("Always dial with prefix")?></option>
						<option value="remove"><?php echo _("Remove prefix from local numbers")?></option>
						<option value="directory"><?php echo _("Setup directory assistance")?></option>
						<option value="lookup7"><?php echo _("Lookup numbers for local trunk (7-digit dialing)")?></option>
						<option value="lookup10"><?php echo _("Lookup numbers for local trunk (10-digit dialing)")?></option>
            <option value="csv"><?php echo _("Upload from CSV")?></option>
					</select>
          <input type="file" name="pattern_file" id="pattern_file" tabindex="<?php echo ++$tabindex;?>"/>
				</td>
			</tr>
			<script language="javascript">

			function disable_field(field, field_enable) {
			    if (field_enable.checked) {
				field.style.backgroundColor = '#FFF';
				field.disabled = false;
			    }
			    else {
				field.style.backgroundColor = '#DDD';
				field.disabled = true;
			    }
			}

			function disable_verify(field) {
				if (field.checked) {
					var answer=confirm("<?php echo _("Are you sure you want to disable this trunk in all routes it is used?") ?>");
					if (!answer) {
						field.checked = false;
					}
				} else {
					alert("<?php echo _("You have enabled this trunk in all routes it is used") ?>");
				}
			}

			function populateLookup(digits) {
<?php
	if (function_exists("curl_init")) { // curl is installed
?>
				//var npanxx = prompt("What is your areacode + prefix (NPA-NXX)?", document.getElementById('areacode').value);
				do {
					var npanxx = <?php echo 'prompt("'._("What is your areacode + prefix (NPA-NXX)?\\n\\n(Note: this database contains North American numbers only, and is not guaranteed to be 100% accurate. You will still have the option of modifying results.)\\n\\nThis may take a few seconds.".'")')?>;
					if (npanxx == null) return;
				} while (!npanxx.match("^[2-9][0-9][0-9][-]?[2-9][0-9][0-9]$") && <?php echo '!alert("'._("Invalid NPA-NXX. Must be of the format \'NXX-NXX\'").'")'?>);

				document.getElementById('npanxx').value = npanxx;
				if (digits == 10) {
					document.trunkEdit.action.value = "populatenpanxx10";
				} else {
					document.trunkEdit.action.value = "populatenpanxx7";
				}
        clearPatterns();
				document.trunkEdit.submit();
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
          var localpattern = <?php echo 'prompt("'._("What is the local dialing pattern?\\n\\n(ie. NXXNXXXXXX for US/CAN 10-digit dialing, NXXXXXX for 7-digit)").'"'?>,"<?php echo _("NXXXXXX")?>");
					if (localpattern == null) return;
				} while (!localpattern.match('^[0-9#*ZXN\.]+$') && <?php echo '!alert("'._("Invalid pattern. Only 0-9, #, *, Z, N, X and . are allowed.").'")'?>);

				do {
					var localprefix = <?php echo 'prompt("'._("What prefix should be added to the dialing pattern?\\n\\n(ie. for US/CAN, 1+areacode, ie, \'1613\')?").'")'?>;
					if (localprefix == null) return;
				} while (!localprefix.match('^[0-9#*]+$') && <?php echo '!alert("'._("Invalid prefix. Only dialable characters (0-9, #, and *) are allowed.").'")'?>);

        return addCustomField(localprefix,'',localpattern,$("#last_row"));
			}

			function populateRemove() {
				do {
					var localprefix = <?php echo 'prompt("'._("What prefix should be removed from the number?\\n\\n(ie. for US/CAN, 1+areacode, ie, \'1613\')").'")'?>;
					if (localprefix == null) return;
				} while (!localprefix.match('^[0-9#*ZXN\.]+$') && <?php echo '!alert("'._('Invalid prefix. Only 0-9, #, *, Z, N, and X are allowed.').'")'?>);

				do {
          var localpattern = <?php echo 'prompt("'._("What is the dialing pattern for local numbers after")?> "+localprefix+"? \n\n<?php echo _("(ie. NXXNXXXXXX for US/CAN 10-digit dialing, NXXXXXX for 7-digit)").'"'?>,"<?php echo _("NXXXXXX")?>");
					if (localpattern == null) return;
				} while (!localpattern.match('^[0-9#*ZXN\.]+$') && <?php echo '!alert("'._("Invalid pattern. Only 0-9, #, *, Z, N, X and . are allowed.").'")'?>);

        return addCustomField('',localprefix,localpattern,$("#last_row"));
			}

			function populatedirectory() {
				do {
        var localprefix = <?php echo 'prompt("'._("What is the directory assistance number you will dial locally in the format that is passed to this trunk?").'"'?>,"<?php echo ""?>");
					if (localprefix == null) return;
				} while (!localprefix.match('^[0-9#*]+$') && <?php echo '!alert("'._("Invalid pattern. Only 0-9, #, *").'")'?>);
				do {

        var localprepend = <?php echo 'prompt("'._("Number to dial when calling directory assistance on this trunk").'"'?>,"<?php echo '' ?>");
					if (localprepend == null) return;
				} while (!localprepend.match('^[0-9#*]+$') && <?php echo '!alert("'._('Invalid number. Only 0-9, #,  and * are allowed.').'")'?>);

        return addCustomField(localprepend,localprefix,'',$("#last_row"));
			}

			function changeAutoPop() {
        var idx = false;
        // hide the file box if nothing was set
        if ($('#pattern_file').val() == '') {
          $('#pattern_file').hide();
        }
				switch(document.getElementById('autopop').value) {
					case "always":
						idx = populateAlwaysAdd();
            if (idx) {
              $('#pattern_prefix_'+idx).focus();
            }
					break;
					case "remove":
						idx = populateRemove();
            if (idx) {
              $('#prepend_digit_'+idx).focus();
            }
					break;
					case "directory":
						idx = populatedirectory();
            if (idx) {
              $('#pattern_pass_'+idx).focus();
            }
					break;
					case "lookup7":
						populateLookup(7);
					break;
					case "lookup10":
						populateLookup(10);
					break;
					case 'csv':
            $('#pattern_file').show().click();
            return true;
					break;
				}
				document.getElementById('autopop').value = '';
			}
			</script>

			<tr>
				<td>
					<a href=# class="info"><?php echo _("Outbound Dial Prefix")?><span><?php echo _("The outbound dialing prefix is used to prefix a dialing string to all outbound calls placed on this trunk. For example, if this trunk is behind another PBX or is a Centrex line, then you would put 9 here to access an outbound line. Another common use is to prefix calls with 'w' on a POTS line that need time to obtain dial tone to avoid eating digits.<br><br>Most users should leave this option blank.")?></span></a>:
				</td><td>
					<input type="text" size="8" name="dialoutprefix" id="dialoutprefix" value="<?php echo htmlspecialchars($dialoutprefix) ?>" tabindex="<?php echo ++$tabindex;?>"/>
				</td>
			</tr>
			<?php if (isset($extdisplay) && !empty($extdisplay) && !empty($dialpattern_array)) {?>
    		<tr>
    		    <td><a href=# class="info"><?php echo _("Export Dialplans as CSV")?><span><?php echo sprintf(_("Export patterns as a CSV file with headers listed as: %s, %s and %s in the first row."),'<strong>prepend</strong>','<strong>prefix</strong>','<strong>match pattern</strong>')?></span><a>:</td>
    		    <td><input type="button" onclick="parent.location='config.php?quietmode=1&amp;handler=file&amp;file=export.html.php&amp;module=core&amp;display=trunks&amp;extdisplay=<?php echo $extdisplay;?>'" value="Export"></td>
    		</tr>
    		<?php } ?>

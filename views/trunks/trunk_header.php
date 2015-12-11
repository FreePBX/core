<?php
$pageheading = sprintf(_("%s %s Trunk"),(empty($extdisplay) ? _('Add'): _('Edit')),$upper_tech);
if(!empty($extdisplay)){
	if ($num_routes > 0){
		$pageinfoinuse .= '<div class="panel panel-default" id="inusepanel">';
		$pageinfoinuse .= '<div class="panel-heading">';
		$pageinfoinuse .= '<h4>'._("In use by")." ".$num_routes." ".($num_routes == 1 ? _("route") : _("routes"));
		$pageinfoinuse .= '<a  class="pull-right" data-toggle="collapse" data-target="#inusetable" href="#inusetable">';
		$pageinfoinuse .= '<i class="fa fa-plus"></i>';
		$pageinfoinuse .= '</a>';
		$pageinfoinuse .= '</h4>';
		$pageinfoinuse .= '</div>';
		$pageinfoinuse .= '<div class="panel-body collapse" id="inusetable">';
		$pageinfoinuse .= '<table class="table table-striped table-bordered">';
		$pageinfoinuse .= '<tr><th>'._("Route").'</th><th>'._("Priority").'</th></tr>';
		foreach($routes as $route=>$priority) {
			$pageinfoinuse .= '<tr><td>'.$route.'</td><td>'.$priority.'</td></tr>';
		}
		$pageinfoinuse .= '</table>';
		$pageinfoinuse .= '</div>';
		$pageinfoinuse .= '</div>';
	}else{
		$pageinfoinuse .= '<div class="alert alert-warning"><h3>'._("WARNING: This trunk is not used by any routes!").'</h3>';
		$pageinfoinuse .= '<p>'._("This trunk will not be able to be used for outbound calls until a route is setup that uses it.").'</p>';
		$pageinfoinuse .= '<p>'._("Click on <a href='?display=routing'>Outbound Routes</a> to setup routing.").'</p>';
		$pageinfoinuse .= '</div>';
	}
}
if(!empty($helptext)) {
	$pageinfohelp .= '<div class="well well-info">';
	$pageinfohelp .= $helptext;
	$pageinfohelp .= '</div>';
}
$maxchanshelp = _("Controls the maximum number of outbound channels (simultaneous calls) that can be used on this trunk. Inbound calls are not counted against the maximum. Leave blank to specify no maximum.");
switch($tech){
	case "sip":
	case "iax":
	case "iax2":
		$pr_tech = ($tech == "iax") ? "iax2":$tech;
		$maxchanshelp = sprintf(_("Controls the maximum number of outbound channels (simultaneous calls) that can be used on this trunk. To count inbound calls against this maximum, use the auto-generated context: %s as the inbound trunk's context. (see extensions_additional.conf) Leave blank to specify no maximum."),((isset($channelid) && trim($channelid)!="")?"from-trunk-$pr_tech-$channelid":"from-trunk-[trunkname]"));
	break;
	default:
	break;
}
if($failtrunk_enable && $failtrunk || $amp_conf['DISPLAY_MONITOR_TRUNK_FAILURES_FIELD']){
	$mtfhtml = '<!--MONITOR TRUNK FAILURES-->';
	$mtfhtml .= '<div class="element-container">';
	$mtfhtml .= '	<div class="row">';
	$mtfhtml .= '		<div class="col-md-12">';
	$mtfhtml .= '			<div class="row">';
	$mtfhtml .= '				<div class="form-group">';
	$mtfhtml .= '					<div class="col-md-3">';
	$mtfhtml .= '						<label class="control-label" for="failtrunk">'._("Monitor Trunk Failures").'</label>';
	$mtfhtml .= '						<i class="fa fa-question-circle fpbx-help-icon" data-for="failtrunk"></i>';
	$mtfhtml .= '					</div>';
	$mtfhtml .= '					<div class="col-md-9">';
	if(!$failtrunk_enable){
		$mtfenable = "disabled";
	}else{
		$mtfcheck = "CHECKED";
	}
	$mtfhtml .= '						<input type="text" class="form-control" name="failtrunk" id="failtrunk" '.$mtfenable.' value="'.htmlspecialchars($failtrunk).'"/>';
	$mtfhtml .=	'
											<span class="radioset">
											<input type="radio" name="failtrunk_enable" id="failtrunk_enableyes" value="1" '. ($failtrunk_enable == "1"?"CHECKED":"").'>
											<label for="failtrunk_enableyes">'._("Yes").'</label>
											<input type="radio" name="failtrunk_enable" id="failtrunk_enableno" value="0" '.($failtrunk_enable == "1"?"":"CHECKED") .'>
											<label for="failtrunk_enableno">'. _("No").'</label>
											</span>
	';
	$mtfhtml .= '					</div>';
	$mtfhtml .= '				</div>';
	$mtfhtml .= '			</div>';
	$mtfhtml .= '		</div>';
	$mtfhtml .= '	</div>';
	$mtfhtml .= '	<div class="row">';
	$mtfhtml .= '		<div class="col-md-12">';
	$mtfhtml .= '			<span id="failtrunk-help" class="help-block fpbx-help-block">'._("If checked, supply the name of a custom AGI Script that will be called to report, log, email or otherwise take some action on trunk failures that are not caused by either NOANSWER or CANCEL.").'</span>';
	$mtfhtml .= '		</div>';
	$mtfhtml .= '	</div>';
	$mtfhtml .= '</div>';
	$mtfhtml .= '<!--END MONITOR TRUNK FAILURES-->';
}
$dpmrtop = _("These rules can manipulate the dialed number before sending it out this trunk. If no rule applies, the number is not changed. The original dialed number is passed down from the route where some manipulation may have already occurred. This trunk has the option to further manipulate the number. If the number matches the combined values in the <b>prefix</b> plus the <b>match pattern</b> boxes, the rule will be applied and all subsequent rules ignored.<br/> Upon a match, the <b>prefix</b>, if defined, will be stripped. Next the <b>prepend</b> will be inserted in front of the <b>match pattern</b> and the resulting number will be sent to the trunk. All fields are optional.").'<br /><br />';
$dpmrhtml .= '<b>' . _("Rules:") . '</b><br />';
$dpmrhtml .= '<strong>X</strong>&nbsp;&nbsp;&nbsp;' . _("matches any digit from 0-9") . '<br />';
$dpmrhtml .= '<strong>Z</strong>&nbsp;&nbsp;&nbsp;' . _("matches any digit from 1-9") . '<br />';
$dpmrhtml .= '<strong>N</strong>&nbsp;&nbsp;&nbsp;' . _("matches any digit from 2-9") . '<br />';
$dpmrhtml .= '<strong>[1237-9]</strong>&nbsp;'   . _("matches any digit or letter in the brackets (in this example, 1,2,3,7,8,9)").'<br />';
$dpmrhtml .= '<strong>.</strong>&nbsp;&nbsp;&nbsp;' . _("wildcard, matches one or more characters (not allowed before a | or +)").'<br />';
if($amp_conf['ENABLEOLDDIALPATTERNS']) {
	$dpmrtop = _("A Dial Rule controls how calls will be dialed on this trunk. It can be used to add or remove prefixes. Numbers that don't match any patterns defined here will be dialed as-is. Note that a pattern without a + or | (to add or remove a prefix) will not make any changes but will create a match. Only the first matched rule will be executed and the remaining rules will not be acted on.").'<br /><br />';
	$dpmrhtml .= '<strong>|</strong>&nbsp;&nbsp;&nbsp;' . _("removes a dialing prefix from the number (for example, 613|NXXXXXX would match when some dialed \"6135551234\" but would only pass \"5551234\" to the trunk");
	$dpmrhtml .= '<strong>+</strong>&nbsp;&nbsp;&nbsp;' . _("adds a dialing prefix from the number (for example, 1613+NXXXXXX would match when some dialed \"5551234\" and would pass \"16135551234\" to the trunk)").'<br /><br />';
	$dpmrhtml .= _("You can also use both + and |, for example: 01+0|1ZXXXXXXXXX would match \"016065551234\" and dial it as \"0116065551234\" Note that the order does not matter, eg. 0|01+1ZXXXXXXXXX does the same thing.");
}
$dpmrhtml .= '<br/><a href="#" class="btn btn-default btn-block" data-toggle="modal"	data-target="#dpwizard" id="wizmenu"><i class="fa fa-magic">&nbsp;'. _("Dial patterns wizards").'</i></a><br/><br/>';

$pp_tit = _("prepend");
$pf_tit = _("prefix");
$mp_tit = _("match pattern");
$ci_tit = _("CallerID");
//Dialpatterns Form field(s)
$dpinput = array();
if(!$amp_conf['ENABLEOLDDIALPATTERNS']) {
	foreach ($dialpattern_array as $idx => $pattern) {
		$tabindex++;
		if ($idx == 50) {
			$dpt_title_class = 'dpt-title dpt-nodisplay';
		}
		$dpinput[] = '<tr id = "dprow'.$idx.'">';
		$dpt_class = $pattern['prepend_digits'] == '' ? $dpt_title_class : 'dpt-value';
		$dpinput[] = '<td class="hidden-xs prepend">';
		$dpinput[] = '	<div class="input-group">';
		$dpinput[] = '		<span class="input-group-addon" id="basic-addon'.$idx.'1">(</span>';
		$dpinput[] = '		<input placeholder="' . $pp_tit . '" type="text" id="prepend_digit_'.$idx.'" name="prepend_digit['.$idx.']" class="form-control ' . $dpt_class.'" value="'. $pattern['prepend_digits'].'" tabindex="'.$tabindex++.'">';
		$dpinput[] = '		<span class="input-group-addon" id="basic-addon'.$idx.'2">)</span>';
		$dpinput[] = '	</div>';
		$dpinput[] = '</td>';
		$dpt_class = $pattern['match_pattern_prefix'] == '' ? $dpt_title_class : 'dpt-value';
		$dpinput[] = '<td class="prefix">';
		$dpinput[] = '	<div class="input-group">';
		$dpinput[] = '		<input placeholder="'. $pf_tit .'" type="text" id="pattern_prefix_'.$idx.'" name="pattern_prefix['.$idx.']" class="form-control '.$dpt_class.'" value="'.$pattern['match_pattern_prefix'].'" tabindex="'.$tabindex++.'"> ';
		$dpinput[] = '		<span class="input-group-addon" id="basic-addon'.$idx.'3">|</span>';
		$dpinput[] = '	</div>';
		$dpinput[] = '</td>';
		$dpt_class = $pattern['match_pattern_pass'] == '' ? $dpt_title_class : 'dpt-value';
		$dpinput[] = '<td class="match">';
		$dpinput[] = '	<div class="input-group">';
		$dpinput[] = '		<span class="input-group-addon" id="basic-addon'.$idx.'4">[</span>';
		$dpinput[] = '		<input placeholder="'.$mp_tit.'" type="text" id="pattern_pass_'.$idx.'" name="pattern_pass['.$idx.']" class="form-control '.$dpt_class.'" value="'.$pattern['match_pattern_pass'].'" tabindex="'.$tabindex++.'"> ';
		$dpinput[] = '		<span class="input-group-addon" id="basic-addon'.$idx.'5">/</span>';
		$dpinput[] = '	</div>';
		$dpinput[] = '</td>';
		$dpt_class = $pattern['match_cid'] == '' ? $dpt_title_class : 'dpt-value';
		$dpinput[] = '<td class="hidden-xs hidden-sm callerid">';
		$dpinput[] = '	<div class="input-group">';
		$dpinput[] = '		<input placeholder="'.$ci_tit.'" type="text" id="match_cid_'.$idx.'" name="match_cid['.$idx.']" class="form-control '.$dpt_class.'" value="'.$pattern['match_cid'].'" tabindex="'.$tabindex++.'">';
		$dpinput[] = '		<span class="input-group-addon" id="basic-addon'.$idx.'6">]</span>';
		$dpinput[] = '	</div>';
		$dpinput[] = '<td>';
		$dpinput[] = '		<a href="#"  id="rowadd'.$idx.'"><i class="fa fa-plus"></i></a>';
		$dpinput[] = '		<a href="#"  id="rowdel'.$idx.'"><i class="fa fa-trash"></i></a>';
		$dpinput[] = '</td>';
		$dpinput[] = '</tr>';
	}
	//Always an empty row incase there are no patterns....
	$next_idx = count($dialpattern_array);
	$idx = !empty($idx) ? $idx : $next_idx;
	$tabindex++;
	if ($idx == 50) {
		$dpt_title_class = 'dpt-title dpt-nodisplay';
	}
	$dpinput[] = '<tr id = "dprow'.$idx.'">';
	$dpt_class = $pattern['prepend_digits'] == '' ? $dpt_title_class : 'dpt-value';
	$dpinput[] = '<td class="hidden-xs prepend">';
	$dpinput[] = '	<div class="input-group">';
	$dpinput[] = '		<span class="input-group-addon" id="basic-addon'.$idx.'1">(</span>';
	$dpinput[] = '		<input placeholder="' . $pp_tit . '" type="text" id="prepend_digit_'.$idx.'" name="prepend_digit['.$idx.']" class="form-control ' . $dpt_class.'" value="" tabindex="'.$tabindex++.'">';
	$dpinput[] = '		<span class="input-group-addon" id="basic-addon'.$idx.'2">)</span>';
	$dpinput[] = '	</div>';
	$dpinput[] = '</td>';
	$dpt_class = $pattern['match_pattern_prefix'] == '' ? $dpt_title_class : 'dpt-value';
	$dpinput[] = '<td class="prefix">';
	$dpinput[] = '	<div class="input-group">';
	$dpinput[] = '		<input placeholder="'. $pf_tit .'" type="text" id="pattern_prefix_'.$idx.'" name="pattern_prefix['.$idx.']" class="form-control '.$dpt_class.'" value="" tabindex="'.$tabindex++.'"> ';
	$dpinput[] = '		<span class="input-group-addon" id="basic-addon'.$idx.'3">|</span>';
	$dpinput[] = '	</div>';
	$dpinput[] = '</td>';
	$dpt_class = $pattern['match_pattern_pass'] == '' ? $dpt_title_class : 'dpt-value';
	$dpinput[] = '<td class="match">';
	$dpinput[] = '	<div class="input-group">';
	$dpinput[] = '		<span class="input-group-addon" id="basic-addon'.$idx.'4">[</span>';
	$dpinput[] = '		<input placeholder="'.$mp_tit.'" type="text" id="pattern_pass_'.$idx.'" name="pattern_pass['.$idx.']" class="form-control '.$dpt_class.'" value="" tabindex="'.$tabindex++.'"> ';
	$dpinput[] = '		<span class="input-group-addon" id="basic-addon'.$idx.'5">/</span>';
	$dpinput[] = '	</div>';
	$dpinput[] = '</td>';
	$dpt_class = $pattern['match_cid'] == '' ? $dpt_title_class : 'dpt-value';
	$dpinput[] = '<td class="hidden-xs hidden-sm callerid">';
	$dpinput[] = '	<div class="input-group">';
	$dpinput[] = '		<input placeholder="'.$ci_tit.'" type="text" id="match_cid_'.$idx.'" name="match_cid['.$idx.']" class="form-control '.$dpt_class.'" value="" tabindex="'.$tabindex++.'">';
	$dpinput[] = '		<span class="input-group-addon" id="basic-addon'.$idx.'6">]</span>';
	$dpinput[] = '	</div>';
	$dpinput[] = '<td>';
	$dpinput[] = '		<a href="#"  id="rowadd'.$idx.'"><i class="fa fa-plus"></i></a>';
	$dpinput[] = '		<a href="#"  id="rowdel'.$idx.'"><i class="fa fa-trash"></i></a>';
	$dpinput[] = '</td>';
	$dpinput[] = '</tr>';
	$dprows = implode(PHP_EOL, $dpinput);
}else{
	$dpinput = array();
	$dpinput[] = '<textarea textarea name="bulk_patterns" class="form-control" id="bulk_patterns" rows="10" cols="70">';
	foreach ($dialpattern_array as $pattern){
		$prepend = ($pattern['prepend_digits'] != '') ? $pattern['prepend_digits'].'+' : '';
		$match_pattern_prefix = ($pattern['match_pattern_prefix'] != '') ? $pattern['match_pattern_prefix'].'|' : '';
		$match_cid = ($pattern['match_cid'] != '') ? '/'.$pattern['match_cid'] : '';
		$dpinput[] = $prepend . $match_pattern_prefix . $pattern['match_pattern_pass'] . $match_cid;
	}
	$dpinput[] = '</textarea>';
	$dprows = implode(PHP_EOL, $dpinput);

}

?>
<div class="container-fluid">
	<h1><?php echo $pageheading ?></h1>
	<?php echo $pageinfoinuse ?>
	<?php echo $pageinfohelp ?>
	<div class = "display full-border">
		<div class="row">
			<div class="col-sm-12">
				<div class="fpbx-container">
					<form enctype="multipart/form-data" class="fpbx-submit" name="trunkEdit" id="trunkEdit" action="config.php" method="post"  data-fpbx-delete="config.php?display=trunks&amp;extdisplay=<?php echo urlencode($extdisplay) ?>&amp;action=deltrunk">
						<input type="hidden" name="display" value="<?php echo $display?>"/>
						<input type="hidden" name="extdisplay" value="<?php echo $extdisplay ?>"/>
						<input type="hidden" name="action"  id="action" value="<?php echo ($extdisplay ? "edittrunk" : "addtrunk") ?>"/>
						<input type="hidden" name="tech" value="<?php echo $tech?>"/>
						<input type="hidden" name="provider" value="<?php echo $provider?>"/>
						<input type="hidden" name="sv_trunk_name" value="<?php echo $trunk_name?>"/>
						<input type="hidden" name="sv_usercontext" value="<?php echo $usercontext?>"/>
						<input type="hidden" name="sv_channelid" value="<?php echo $channelid?>"/>
						<input id="npanxx" name="npanxx" type="hidden" />
						<ul class="nav nav-tabs" role="tablist">
							<li role="presentation" data-name="tgeneral" class="change-tab active">
								<a href="#tgeneral" aria-controls="tgeneral" role="tab" data-toggle="tab">
									<?php echo _("General")?>
								</a>
							</li>
							<li role="presentation" data-name="tdialplan" class="change-tab">
								<a href="#tdialplan" aria-controls="tdialplan" role="tab" data-toggle="tab">
									<?php echo _("Dialplan Manipulation Rules")?>
								</a>
							</li>
							<li role="presentation" data-name="ttech" class="change-tab">
								<a href="#ttech" aria-controls="ttech" role="tab" data-toggle="tab">
									<?php echo $tech .' '. _("Settings")?>
								</a>
							</li>
						</ul>
						<div class="tab-content display">
							<div role="tabpanel" id="tgeneral" class="tab-pane active">
								<!--TRUNK NAME-->
								<div class="element-container">
									<div class="row">
										<div class="col-md-12">
											<div class="row">
												<div class="form-group">
													<div class="col-md-3">
														<label class="control-label" for="trunk_name"><?php echo _("Trunk Name") ?></label>
														<i class="fa fa-question-circle fpbx-help-icon" data-for="trunk_name"></i>
													</div>
													<div class="col-md-9">
														<input type="text" class="form-control" name="trunk_name" id="trunk_name" value="<?php echo $trunk_name;?>" />
													</div>
												</div>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-12">
											<span id="trunk_name-help" class="help-block fpbx-help-block"><?php echo _("Descriptive Name for this Trunk")?></span>
										</div>
									</div>
								</div>
								<!--END TRUNK NAME-->
								<!--Hide CallerID-->
								<div class="element-container">
									<div class="row">
										<div class="col-md-12">
											<div class="row">
												<div class="form-group">
													<div class="col-md-3">
														<label class="control-label" for="hcid"><?php echo _("Hide CallerID") ?></label>
														<i class="fa fa-question-circle fpbx-help-icon" data-for="hcid"></i>
													</div>
													<div class="col-md-9 radioset">
								            <input type="radio" name="hcid" id="hcidyes" value="yesvalue">
								            <label for="hcidyes"><?php echo _("Yes");?></label>
								            <input type="radio" name="hcid" id="hcidno">
								            <label for="hcidno"><?php echo _("No");?></label>
													</div>
												</div>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-12">
											<span id="hcid-help" class="help-block fpbx-help-block"><?php echo _("Hide the outbound Caller ID, The same as adding hidden to Outbound CID")?></span>
										</div>
									</div>
								</div>
								<!--END Hide CallerID-->
								<!--OUTBOUND CID-->
								<div class="element-container">
									<div class="row">
										<div class="col-md-12">
											<div class="row">
												<div class="form-group">
													<div class="col-md-3">
														<label class="control-label" for="outcid"><?php echo _("Outbound CallerID") ?></label>
															<i class="fa fa-question-circle fpbx-help-icon" data-for="outcid"></i>
													</div>
													<div class="col-md-9">
														<input type="text" class="form-control" name="outcid" id="outcid" value="<?php echo $outcid;?>" />
													</div>
												</div>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-12">
											<span id="outcid-help" class="help-block fpbx-help-block"><?php echo _("CallerID for calls placed out on this trunk<br><br>Format: <b>&lt;#######&gt;</b>. You can also use the format: \"hidden\" <b>&lt;#######&gt;</b> to hide the CallerID sent out over Digital lines if supported (E1/T1/J1/BRI/SIP/IAX).")?></span>
										</div>
									</div>
								</div>
								<!--END OUTBOUNDCID-->
								<!--KEEPCID-->
								<div class="element-container">
									<div class="row">
										<div class="col-md-12">
											<div class="row">
												<div class="form-group">
													<div class="col-md-3">
														<label class="control-label" for="keepcid"><?php echo _("CID Options") ?></label>
														<i class="fa fa-question-circle fpbx-help-icon" data-for="keepcid"></i>
													</div>
													<div class="col-md-9 radioset">
														<?php
														    $default = (isset($keepcid) ? $keepcid : 'off');
														?>
														<input type="radio" name="keepcid" id="keepcidoff" value="off" <?php echo ($default == "off"?"CHECKED":"") ?>>
														<label for="keepcidoff"><?php echo _("Allow Any CID");?></label>
														<input type="radio" name="keepcid" id="keepcidon" value="on" <?php echo ($default == "on"?"CHECKED":"") ?>>
														<label for="keepcidon"><?php echo _("Block Foreign CIDs");?></label>
														<input type="radio" name="keepcid" id="keepcidcnum" value="cnum" <?php echo ($default == "cnum"?"CHECKED":"") ?>>
														<label for="keepcidcnum"><?php echo _("Remove CNAM");?></label>
														<input type="radio" name="keepcid" id="keepcidall" value="all" <?php echo ($default == "all"?"CHECKED":"") ?>>
														<label for="keepcidall"><?php echo _("Force Trunk CID");?></label>
													</div>
												</div>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-12">
											<span id="keepcid-help" class="help-block fpbx-help-block"><?php echo _("Determines what CIDs will be allowed out this trunk. IMPORTANT: EMERGENCY CIDs defined on an extension/device will ALWAYS be used if this trunk is part of an EMERGENCY Route regardless of these settings.<br />Allow Any CID: all CIDs including foreign CIDS from forwarded external calls will be transmitted.<br />Block Foreign CIDs: blocks any CID that is the result of a forwarded call from off the system. CIDs defined for extensions/users are transmitted.<br />Remove CNAM: this will remove CNAM from any CID sent out this trunk<br />Force Trunk CID: Always use the CID defined for this trunk except if part of any EMERGENCY Route with an EMERGENCY CID defined for the extension/device.") . _("Intra-Company Routes will always trasmit an extension's internal number and name.")?></span>
										</div>
									</div>
								</div>
								<!--END KEEPCID-->
								<!--MAXIMUM CHANNELS-->
								<div class="element-container">
									<div class="row">
										<div class="col-md-12">
											<div class="row">
												<div class="form-group">
													<div class="col-md-3">
														<label class="control-label" for="maxchans"><?php echo _("Maximum Channels") ?></label>
														<i class="fa fa-question-circle fpbx-help-icon" data-for="maxchans"></i>
													</div>
													<div class="col-md-9">
														<input type="number" class="form-control" name="maxchans" id="maxchans" value="<?php echo htmlspecialchars($maxchans); ?>" />
													</div>
												</div>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-12">
											<span id="maxchans-help" class="help-block fpbx-help-block"><?php echo $maxchanshelp ?></span>
										</div>
									</div>
								</div>
								<!--END MAXIMUM CHANNELS-->
								<!--DIAL OPTS-->
								<div class="element-container">
									<div class="row">
										<div class="col-md-12">
											<div class="row">
												<div class="form-group">
													<div class="col-md-3">
														<label class="control-label" for="dialopts"><?php echo _('Asterisk Trunk Dial Options') ?></label>
														<i class="fa fa-question-circle fpbx-help-icon" data-for="dialopts"></i>
													</div>
													<div class="col-md-9">
														<input type="text" class="form-control" id="dialopts" name="dialopts" value="<?php echo $dialopts !== false?$dialopts:''?>" <?php echo $dialopts === false?'disabled':''?> placeholder="<?php echo $amp_conf['TRUNK_OPTIONS']?>">
														<span class="radioset">
														<input type="radio" name="dialoutopts_cb" id="dialoutopts_cbyes" value="or" <?php echo ($dialopts !== false?"CHECKED":"") ?>>
														<label for="dialoutopts_cbyes"><?php echo _("Override");?></label>
														<input type="radio" name="dialoutopts_cb" id="dialoutopts_cbno" value="sys" <?php echo ($dialopts !== false?"":"CHECKED") ?>>
														<label for="dialoutopts_cbno"><?php echo _("System");?></label>
														</span>
													</div>
												</div>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-12">
											<span id="dialopts-help" class="help-block fpbx-help-block"><?php echo _('Asterisk Dial command options to be used when calling out this trunk. To override the Advanced Settings default, check the box and then provide the required options for this trunk')?></span>
										</div>
									</div>
								</div>
								<!--END DIAL OPTS-->
								<!--CONTINUE IF BUSY-->
								<div class="element-container">
									<div class="row">
										<div class="col-md-12">
											<div class="row">
												<div class="form-group">
													<div class="col-md-3">
														<label class="control-label" for="continuew"><?php echo _("Continue if Busy") ?></label>
														<i class="fa fa-question-circle fpbx-help-icon" data-for="continuew"></i>
													</div>
													<div class="col-md-9 radioset">
														<input type="radio" name="continue" id="continueyes" value="on" <?php echo ($continue == "on"?"CHECKED":"") ?>>
														<label for="continueyes"><?php echo _("Yes");?></label>
														<input type="radio" name="continue" id="continueno" value="off" <?php echo ($continue == "on"?"":"CHECKED") ?>>
														<label for="continueno"><?php echo _("No");?></label>
													</div>
												</div>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-12">
											<span id="continuew-help" class="help-block fpbx-help-block"><?php echo _("Normally the next trunk is only tried upon a trunk being 'Congested' in some form, or unavailable. Checking this box will force a failed call to always continue to the next configured trunk or destination even when the channel reports BUSY or INVALID NUMBER.")?></span>
										</div>
									</div>
								</div>
								<!--END CONTINUE IF BUSY-->
								<!--DISABLE TRUNK-->
								<div class="element-container">
									<div class="row">
										<div class="col-md-12">
											<div class="row">
												<div class="form-group">
													<div class="col-md-3">
														<label class="control-label" for="disabletrunkw"><?php echo _("Disable Trunk")?></label>
														<i class="fa fa-question-circle fpbx-help-icon" data-for="disabletrunkw"></i>
													</div>
													<div class="col-md-9 radioset">
															<input type="radio" name="disabletrunk" id="disabletrunkyes" value="on" <?php echo ($disabletrunk == "on"?"CHECKED":"") ?>>
															<label for="disabletrunkyes"><?php echo _("Yes");?></label>
															<input type="radio" name="disabletrunk" id="disabletrunkno" value="off" <?php echo ($disabletrunk == "on"?"":"CHECKED") ?>>
															<label for="disabletrunkno"><?php echo _("No");?></label>
													</div>
												</div>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-12">
											<span id="disabletrunkw-help" class="help-block fpbx-help-block"><?php echo _("Check this to disable this trunk in all routes where it is used.")?></span>
										</div>
									</div>
								</div>
								<!--END DISABLE TRUNK-->
								<?php echo $mtfhtml ?>
							</div>
							<div role="tabpanel" id="tdialplan" class="tab-pane">
								<br/>
								<br/>
								<h3><?php echo _("Dial Number Manipulation Rules")?></h3>
								<?php echo $dpmrtop?>
								<?php echo $dpmrhtml?>
								<table class="table table-striped" id="dptable">
									<?php echo $dprows ?>
								</table>
								<!--Outbound Dial Prefix-->
								<div class="element-container">
									<div class="row">
										<div class="col-md-12">
											<div class="row">
												<div class="form-group">
													<div class="col-md-3">
														<label class="control-label" for="dialoutprefix"><?php echo _("Outbound Dial Prefix") ?></label>
														<i class="fa fa-question-circle fpbx-help-icon" data-for="dialoutprefix"></i>
													</div>
													<div class="col-md-9">
														<input type="text" class="form-control" id="dialoutprefix" name="dialoutprefix" value="<?php echo isset($dialoutprefix)?htmlentities($dialoutprefix,ENT_COMPAT | ENT_HTML401, "UTF-8"):''?>">
													</div>
												</div>
											</div>
										</div>
									</div>
									<div class="row">
										<div class="col-md-12">
											<span id="dialoutprefix-help" class="help-block fpbx-help-block"><?php echo _("The outbound dialing prefix is used to prefix a dialing string to all outbound calls placed on this trunk. For example, if this trunk is behind another PBX or is a Centrex line, then you would put 9 here to access an outbound line. Another common use is to prefix calls with 'w' on a POTS line that need time to obtain dial tone to avoid eating digits.<br><br>Most users should leave this option blank.")?></span>
										</div>
									</div>
								</div>
								<!--END Outbound Dial Prefix-->
							</div>
							<div role="tabpanel" id="ttech" class="tab-pane">
<!--End of trunk_header-->

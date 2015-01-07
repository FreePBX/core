<?php
global $amp_conf;
$formAction = '';
//Optional elements
if (function_exists('music_list')){
	$optionalelems = load_view(__DIR__.'/moh.php');
}

if (function_exists('timeconditions_timegroups_drawgroupselect')){
	$optionalelems .= load_view(__DIR__.'/timecond.php');
}
$routepriority = core_routing_list();
if ($route_seq != 0) {
	$routeseqopts = '<option value="0"'.($route_seq == 0 ? ' SELECTED' : '').'>'.sprintf(_('First before %s'),$routepriority[0]['name'])."</option>\n";
}
//Routing select box
foreach ($routepriority as $key => $route) {
	if ($key == 0 && $route_seq != 0) continue;
	if ($key == ($route_seq+1)) continue;
	if ($route_seq == $key) {
		$routeseqopts .= '				<option value="'.$key.'" SELECTED>'._('---No Change---')."</option>\n";
	} else {
		$routeseqopts .= '				<option value="'.$key.'">'.sprintf(_('Before %s'),$route['name'])."</option>\n";
	}
}
if ($extdisplay == '' | $route_seq != $last_seq) {
	$routeseqopts .= '<option value="bottom"'.($route_seq == count($routepriority) ? ' SELECTED' : '').'>'.sprintf(_('Last after %s'),$routepriority[$last_seq]['name'])."</option>\n";
}
//Hooks....
$module_hook = moduleHook::create();
if (!empty($module_hook->hookHtml)) {
	$hooktab = 	'<li role="presentation"><a href="#additionalsettings" data-toggle="tab">'._("Additional Settings").'</a></li>';
	
}
//Dialpattern Help
$dplabel = _("Dial Patterns that will use this Route");
$dphstring = array();
$dphstring[] = '<div class="panel panel-default">';
$dphstring[] = '	<div class="panel-heading">';
$dphstring[] = 			_("Pattern Help");
$dphstring[] = '<a data-toggle="collapse" href="#pathelp"><i class="fa fa-plus pull-right"></i></a>';
$dphstring[] = '	</div>';
$dphstring[] = '	<div class="panel-body collapse" id="pathelp">';
$dphstring[] = '		<p>'._("A Dial Pattern is a unique set of digits that will select this route and send the call to the designated trunks. If a dialed pattern matches this route, no subsequent routes will be tried. If Time Groups are enabled, subsequent routes will be checked for matches outside of the designated time(s).").'</p>';
$dphstring[] = '		<h4>'._("Rules:").'</h4>';
$dphstring[] = '		<table class="table">';
$dphstring[] = '			<tr><td><strong>X</strong></td><td>'. _("matches any digit from 0-9").'</td></tr>';
$dphstring[] = '			<tr><td><strong>Z</strong></td><td>'. _("matches any digit from 1-9").'</td></tr>';
$dphstring[] = '			<tr><td><strong>N</strong></td><td>'. _("matches any digit from 2-9").'</td></tr>';
$dphstring[] = '			<tr><td><strong>[1237-9]</strong></td><td>'. _("matches any digit or letter in the brackets (in this example, 1,2,3,7,8,9)").'</td></tr>';
$dphstring[] = '			<tr><td><strong>.</strong></td><td>;'. _("wildcard, matches one or more characters").'</td></tr>';

if($amp_conf['ENABLEOLDDIALPATTERNS']){
	$dphstring[] = '		<tr><td><strong>|</strong></td><td>'. _("separates a dialing prefix from the number (for example, 9|NXXXXXX would match when some dialed \"95551234\" but would only pass \"5551234\" to the trunks)").'</td></tr>';
	$dphstring[] = '		<tr><td><strong>/</strong></td><td>;'. _("appended to a dial pattern, matches a CallerID or CallerID pattern (for example, NXXXXXX/104 would match only if dialed by extension \"104\")").'</td></tr>';

}else{
	$dphstring[] = '		<tr><td><strong>'. _("prepend:"). '</strong></td><td>'. _("Digits to prepend to a successful match. If the dialed number matches the patterns specified by the subsequent columns, then this will be prepended before sending to the trunks.").'</td></tr>';
	$dphstring[] = '		<tr><td><strong>'. _("prefix:"). '</strong></td><td>'. _("Prefix to remove on a successful match. The dialed number is compared to this and the subsequent columns for a match. Upon a match, this prefix is removed from the dialed number before sending it to the trunks.").'</td></tr>';
	$dphstring[] = '		<tr><td><strong>'. _("match pattern:"). '</strong></td><td>'. _("The dialed number will be compared against the  prefix + this match pattern. Upon a match, the match pattern portion of the dialed number will be sent to the trunks").'</td></tr>';
	$dphstring[] = '		<tr><td><strong>'. _("CallerID:"). '</strong></td><td>'. _("If CallerID is supplied, the dialed number will only match the prefix + match pattern if the CallerID being transmitted matches this. When extensions make outbound calls, the CallerID will be their extension number and NOT their Outbound CID. The above special matching sequences can be used for CallerID matching similar to other number matches.").'</td></tr>';
}
$dphstring[] = '		</table>';

$dphstring[] = '</div>';
$dphstring[] = '</div>';
$dphelp = implode(PHP_EOL, $dphstring);

$pp_tit = _("prepend");
$pf_tit = _("prefix");
$mp_tit = _("match pattern");
$ci_tit = _("CallerID");
//Dialpatterns Form field(s)
$dpinput = array();
if($amp_conf['ENABLEOLDDIALPATTERNS']) {
	foreach ($dialpattern_array as $idx => $pattern) {
		$tabindex++;
		if ($idx == 50) {
			$dpt_title_class = 'dpt-title dpt-nodisplay';
		}
		$dpinput[] = '<tr id = "row'.$idx.'">';
		$dpt_class = $pattern['prepend_digits'] == '' ? $dpt_title_class : 'dpt-value';
		$dpinput[] = '<td>';
		$dpinput[] = '	<div class="input-group">';
		$dpinput[] = '		<span class="input-group-addon" id="basic-addon'.$idx.'1">(</span>';
		$dpinput[] = '		<input placeholder="' . $pp_tit . '" type="text" id="prepend_digit_'.$idx.'" name="prepend_digit['.$idx.']" class="form-control ' . $dpt_class.'" value="'. $pattern['prepend_digits'].'" tabindex="'.$tabindex++.'">';
		$dpinput[] = '		<span class="input-group-addon" id="basic-addon'.$idx.'2">)</span>';
		$dpinput[] = '	</div>';
		$dpinput[] = '</td>';
		$dpt_class = $pattern['match_pattern_prefix'] == '' ? $dpt_title_class : 'dpt-value';
		$dpinput[] = '<td>';
		$dpinput[] = '	<div class="input-group">';
		$dpinput[] = '		<input placeholder="'. $pf_tit .'" type="text" id="pattern_prefix_'.$idx.'" name="pattern_prefix['.$idx.']" class="form-control '.$dpt_class.'" value="'.$pattern['match_pattern_prefix'].'" tabindex="'.$tabindex++.'"> ';
		$dpinput[] = '		<span class="input-group-addon" id="basic-addon'.$idx.'3">|</span>';
		$dpinput[] = '	</div>';
		$dpinput[] = '</td>';
		$dpt_class = $pattern['match_pattern_pass'] == '' ? $dpt_title_class : 'dpt-value';
		$dpinput[] = '<td>';
		$dpinput[] = '	<div class="input-group">';
		$dpinput[] = '		<span class="input-group-addon" id="basic-addon'.$idx.'4">[</span>';	
		$dpinput[] = '		<input placeholder="'.$mp_tit.'" type="text" id="pattern_pass_'.$idx.'" name="pattern_pass['.$idx.']" class="form-control '.$dpt_class.'" value="'.$pattern['match_pattern_pass'].'" tabindex="'.$tabindex++.'"> ';
		$dpinput[] = '		<span class="input-group-addon" id="basic-addon'.$idx.'5">/</span>';
		$dpinput[] = '	</div>';
		$dpinput[] = '</td>';
		$dpt_class = $pattern['match_cid'] == '' ? $dpt_title_class : 'dpt-value';
		$dpinput[] = '<td>';
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
	$dpinput[] = '<tr id = "row'.$idx.'">';
	$dpt_class = $pattern['prepend_digits'] == '' ? $dpt_title_class : 'dpt-value';
	$dpinput[] = '<td>';
	$dpinput[] = '	<div class="input-group">';
	$dpinput[] = '		<span class="input-group-addon" id="basic-addon'.$idx.'1">(</span>';
	$dpinput[] = '		<input placeholder="' . $pp_tit . '" type="text" id="prepend_digit_'.$idx.'" name="prepend_digit['.$idx.']" class="form-control ' . $dpt_class.'" value="'. $pattern['prepend_digits'].'" tabindex="'.$tabindex++.'">';
	$dpinput[] = '		<span class="input-group-addon" id="basic-addon'.$idx.'2">)</span>';
	$dpinput[] = '	</div>';
	$dpinput[] = '</td>';
	$dpt_class = $pattern['match_pattern_prefix'] == '' ? $dpt_title_class : 'dpt-value';
	$dpinput[] = '<td>';
	$dpinput[] = '	<div class="input-group">';
	$dpinput[] = '		<input placeholder="'. $pf_tit .'" type="text" id="pattern_prefix_'.$idx.'" name="pattern_prefix['.$idx.']" class="form-control '.$dpt_class.'" value="'.$pattern['match_pattern_prefix'].'" tabindex="'.$tabindex++.'"> ';
	$dpinput[] = '		<span class="input-group-addon" id="basic-addon'.$idx.'3">|</span>';
	$dpinput[] = '	</div>';
	$dpinput[] = '</td>';
	$dpt_class = $pattern['match_pattern_pass'] == '' ? $dpt_title_class : 'dpt-value';
	$dpinput[] = '<td>';
	$dpinput[] = '	<div class="input-group">';
	$dpinput[] = '		<span class="input-group-addon" id="basic-addon'.$idx.'4">[</span>';	
	$dpinput[] = '		<input placeholder="'.$mp_tit.'" type="text" id="pattern_pass_'.$idx.'" name="pattern_pass['.$idx.']" class="form-control '.$dpt_class.'" value="'.$pattern['match_pattern_pass'].'" tabindex="'.$tabindex++.'"> ';
	$dpinput[] = '		<span class="input-group-addon" id="basic-addon'.$idx.'5">/</span>';
	$dpinput[] = '	</div>';
	$dpinput[] = '</td>';
	$dpt_class = $pattern['match_cid'] == '' ? $dpt_title_class : 'dpt-value';
	$dpinput[] = '<td>';
	$dpinput[] = '	<div class="input-group">';
	$dpinput[] = '		<input placeholder="'.$ci_tit.'" type="text" id="match_cid_'.$idx.'" name="match_cid['.$idx.']" class="form-control '.$dpt_class.'" value="'.$pattern['match_cid'].'" tabindex="'.$tabindex++.'">';
	$dpinput[] = '		<span class="input-group-addon" id="basic-addon'.$idx.'6">]</span>';
	$dpinput[] = '	</div>';
	$dpinput[] = '<td>';
	$dpinput[] = '		<a href="#"  id="rowadd'.$idx.'"><i class="fa fa-plus"></i></a>';
	$dpinput[] = '		<a href="#"  id="rowdel'.$idx.'"><i class="fa fa-trash"></i></a>';
	$dpinput[] = '</td>';
	$dpinput[] = '</tr>';
	$dprows = implode(PHP_EOL, $dpinput);
}


?>
<ul class="nav nav-tabs">
  <li role="presentation" class="active"><a href="#routesettings" data-toggle="tab"><?php echo _("Route Settings")?></a></li>
  <?php echo $hooktab ?>
  <li role="presentation"><a href="#dialpatterns" data-toggle="tab"><?php echo _("Dial Patterns")?></a></li>
</ul>
<div id="formtabs" class="tab-content">	
	<div class="tab-pane active" id="routesettings">
		<form enctype="multipart/form-data" class="fpbx-submit" autocomplete="off" id="routeEdit" name="routeEdit" action="config.php" method="POST" data-fpbx-delete="config.php?display=<?php echo urlencode($display) ?>&extdisplay=<?php echo urlencode($extdisplay) ?>&action=delroute">
			<input type="hidden" name="display" value="<?php echo $display?>"/>
			<input type="hidden" name="extdisplay" value="<?php echo $id ?>"/>
			<input type="hidden" id="action" name="action" value="<?php echo $formAction ?>"/>
			<input type="hidden" id="repotrunkdirection" name="repotrunkdirection" value="">
			<input type="hidden" id="repotrunkkey" name="repotrunkkey" value="">
			<input type="hidden" id="reporoutedirection" name="reporoutedirection" value="">
			<input type="hidden" id="reporoutekey" name="reporoutekey" value="">
			<!--ROUTE NAME-->
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="routename"><?php echo _("Route Name") ?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="routename"></i>
								</div>
								<div class="col-md-9">
									<input type="text" class="form-control" id="routename" name="routename" value="<?php echo htmlspecialchars($routename);?>">
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="routename-help" class="help-block fpbx-help-block"><?php echo _("Name of this route. Should be used to describe what type of calls this route matches (for example, 'local' or 'longdistance').")?></span>
					</div>
				</div>
			</div>
			<!--END ROUTE NAME-->
			<!--ROUTE CID-->
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="outcid"><?php echo _("Route CID") ?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="outcid"></i>
								</div>
								<div class="col-md-9">
									<input type="text" class="form-control" id="outcid" name="outcid" value="<?php echo htmlspecialchars($outcid);?>">
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="outcid-help" class="help-block fpbx-help-block"><?php echo _("Optional Route CID to be used for this route <br><br>Format: <b>&lt;#######&gt;</b>. You can also use the format: \"hidden\" <b>&lt;#######&gt;</b> to hide the CallerID sent out over Digital lines if supported (E1/T1/J1/BRI/SIP/IAX). <br/><br/>If set, this will override all CIDS specified except:<ul><li>extension/device EMERGENCY CIDs if this route is checked as an EMERGENCY Route</li><li>trunk CID if trunk is set to force it's CID</li><li>Forwarded call CIDs (CF, Follow Me, Ring Groups, etc)</li><li>Extension/User CIDs if checked</li></ul>")?></span>
					</div>
				</div>
			</div>
			<!--END ROUTE CID-->
			<!--OVERRIDE EXTENSION-->
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="outcid_mode_wrapper"><?php echo _("Override Extension") ?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="outcid_mode_wrapper"></i>
								</div>
								<div class="col-md-9 radioset">
									<input type='checkbox' name='outcid_mode' id="outcid_mode" value='override_extension' <?php if ($outcid_mode == 'override_extension') { echo 'CHECKED'; }?>>
									<label for="outcid_mode"><?php echo _("Override")?></label>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="outcid_mode_wrapper-help" class="help-block fpbx-help-block"><?php echo _("If checked the extension's Outbound CID will be ignored in favor of this CID. The extension's Emergency CID will still be used if the route is an Emergency Route and the Extension has a defined Emergency CID.")?></span>
					</div>
				</div>
			</div>
			<!--END OVERRIDE EXTENSION-->
			<!--ROUTE PASSWORD-->
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="routepass"><?php echo _("Route Password") ?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="routepass"></i>
								</div>
								<div class="col-md-9">
									<input type="password" class="form-control" id="routepass" name="routepass" value="<?php echo $routepass;?>">
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="routepass-help" class="help-block fpbx-help-block"><?php echo _("Optional: A route can prompt users for a password before allowing calls to progress.  This is useful for restricting calls to international destinations or 1-900 numbers.<br><br>A numerical password, or the path to an Authenticate password file can be used.<br><br>Leave this field blank to not prompt for password.")?></span>
					</div>
				</div>
			</div>
			<!--END ROUTE PASSWORD-->
			<!--ROUTE TYPE-->
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="routetype"><?php echo _("Route Type") ?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="routetype"></i>
								</div>
								<div class="col-md-9 radioset">
									<input type='checkbox' name='intracompany' id="emergency" value='YES' <?php echo ($emergency ? "CHECKED" : "") ?>>
									<label for="emergency"><?php echo _("Emergency")?></label>
									<input type='checkbox' name='intracompany' id="intracompany" value='YES' <?php echo ($intracompany ? "CHECKED" : "") ?>>
									<label for="intracompany"><?php echo _("Intra-Company")?></label>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="routetype-help" class="help-block fpbx-help-block"><?php echo _("Optional: Selecting Emergency will enforce the use of a device's Emergency CID setting (if set).  Select this option if this route is used for emergency dialing (ie: 911).").'<br />'._("Optional: Selecting Intra-Company will treat this route as an intra-company connection, preserving the internal CallerID information instead of the outbound CID of either the extension or trunk.")?></span>
					</div>
				</div>
			</div>
			<!--END ROUTE TYPE-->
			<!--OPTIONAL ELEMENTS-->
			<?php echo $optionalelems?>
			<!--END OPTIONAL ELEMENTS-->
			<!--ROUTE POSITION-->
			<div class="element-container">
				<div class="row form-inline">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="route_seq"><?php echo _("Route Position")?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="route_seq"></i>
								</div>
								<div class="col-md-9">
									<select name="route_seq" class="form-control" tabindex="<?php echo ++$tabindex;?>">
									<?php echo $routeseqopts ?>
									</select>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="route_seq-help" class="help-block fpbx-help-block"><?php echo _("Where to insert this route or relocate it relative to the other routes.")?></span>
					</div>
				</div>
			</div>
			<!--END ROUTE POSITION-->	
			</div>
			<!--Hooks in the "Additional Settings tab -->
			<div class="tab-pane" id="additionalsettings">
				<?php echo $module_hook->hookHtml; ?>
			</div>
			<!--End Hooks -->
			<!--Dial Patterns -->
			<div class="tab-pane" id="dialpatterns">
				<br/>
				<br/>
				<h3><?php echo $dplabel?></h3>
				<?php echo $dphelp?>
				<table class="table table-striped">
				<?php echo $dprows ?>
				</table>
			</div>
			<!--END DIALPATTERN INPUT(s)-->
	</form>
</div>



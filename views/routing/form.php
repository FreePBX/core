<?php
global $amp_conf;
$formAction = '';
//If we are passed data we will throw it in here
if(!empty($viewinfo) && is_array($viewinfo)) {
	extract($viewinfo);
}
//Set the page parameters...
$request = $_REQUEST;
$display = $request['display']?$request['display']:'routing';
$extdisplay = $request['id']?$request['id']:'';
$dpt_title_class = 'dpt-title';

if(!empty($request['id'])){
	$formAction = 'editroute';
}else{
	$formAction = 'addroute';
}
//Optional elements
if (function_exists('music_list')){
	$optionalelems = load_view(__DIR__.'/moh.php', array("mohsilence" => $mohsilence));
}

if (function_exists('timeconditions_timegroups_drawgroupselect')){
	$optionalelems .= load_view(__DIR__.'/timecond.php', array('time_group_id' => $time_group_id));
}
$routepriority = core_routing_list();
$routeseqopts = '';
//Routing select box
if ($route_seq != 0) {
	$routeseqopts .= '<option value="0"'.($route_seq == 0 ? ' SELECTED' : '').'>'.sprintf(_('First before %s'),$routepriority[0]['name'])."</option>\n";
}

$last_seq = isset($last_seq)?$last_seq:'';
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
//$module_hook = moduleHook::create();
//if (!empty($module_hook->hookHtml)) {
if (!empty($hooks['oldHooks'])) {
	$hooktab = 	'<li role="presentation" data-name="additionalsettings" class="change-tab"><a href="#additionalsettings" data-toggle="tab">'._("Additional Settings").'</a></li>';

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
if(!$amp_conf['ENABLEOLDDIALPATTERNS']) {
	foreach ($dialpattern_array as $idx => $pattern) {
		if ($idx == 50) {
			$dpt_title_class = 'dpt-title dpt-nodisplay';
		}
		$dpinput[] = '<tr id = "dprow'.$idx.'">';
		$dpt_class = $pattern['prepend_digits'] == '' ? $dpt_title_class : 'dpt-value';
		$dpinput[] = '<td class="hidden-xs prepend">';
		$dpinput[] = '	<div class="input-group">';
		$dpinput[] = '		<span class="input-group-addon" id="basic-addon'.$idx.'1">(</span>';
		$dpinput[] = '		<input placeholder="' . $pp_tit . '" type="text" id="prepend_digit_'.$idx.'" name="prepend_digit[]" class="form-control ' . $dpt_class.'" value="'. $pattern['prepend_digits'].'" >';
		$dpinput[] = '		<span class="input-group-addon" id="basic-addon'.$idx.'2">)</span>';
		$dpinput[] = '	</div>';
		$dpinput[] = '</td>';
		$dpt_class = $pattern['match_pattern_prefix'] == '' ? $dpt_title_class : 'dpt-value';
		$dpinput[] = '<td class="prefix">';
		$dpinput[] = '	<div class="input-group">';
		$dpinput[] = '		<input placeholder="'. $pf_tit .'" type="text" id="pattern_prefix_'.$idx.'" name="pattern_prefix[]" class="form-control '.$dpt_class.'" value="'.$pattern['match_pattern_prefix'].'" > ';
		$dpinput[] = '		<span class="input-group-addon" id="basic-addon'.$idx.'3">|</span>';
		$dpinput[] = '	</div>';
		$dpinput[] = '</td>';
		$dpt_class = $pattern['match_pattern_pass'] == '' ? $dpt_title_class : 'dpt-value';
		$dpinput[] = '<td class="match">';
		$dpinput[] = '	<div class="input-group">';
		$dpinput[] = '		<span class="input-group-addon" id="basic-addon'.$idx.'4">[</span>';
		$dpinput[] = '		<input placeholder="'.$mp_tit.'" type="text" id="pattern_pass_'.$idx.'" name="pattern_pass[]" class="form-control '.$dpt_class.'" value="'.$pattern['match_pattern_pass'].'" > ';
		$dpinput[] = '		<span class="input-group-addon" id="basic-addon'.$idx.'5">/</span>';
		$dpinput[] = '	</div>';
		$dpinput[] = '</td>';
		$dpt_class = $pattern['match_cid'] == '' ? $dpt_title_class : 'dpt-value';
		$dpinput[] = '<td class="hidden-xs hidden-sm callerid">';
		$dpinput[] = '	<div class="input-group">';
		$dpinput[] = '		<input placeholder="'.$ci_tit.'" type="text" id="match_cid_'.$idx.'" name="match_cid[]" class="form-control '.$dpt_class.'" value="'.$pattern['match_cid'].'" >';
		$dpinput[] = '		<span class="input-group-addon" id="basic-addon'.$idx.'6">]</span>';
		$dpinput[] = '	</div>';
		$dpinput[] = '<td>';
		$dpinput[] = '		<a href="#"  id="routerowadd'.$idx.'"><i class="fa fa-plus"></i></a>';
		$dpinput[] = '		<a href="#"  id="routerowdel'.$idx.'"><i class="fa fa-trash"></i></a>';
		$dpinput[] = '</td>';
		$dpinput[] = '</tr>';
	}
	//Always an empty row incase there are no patterns....
	$next_idx = count($dialpattern_array);
	$idx = !empty($idx) ? $idx : $next_idx;
	if ($idx == 50) {
		$dpt_title_class = 'dpt-title dpt-nodisplay';
	}
	$dpinput[] = '<tr id = "dprow'.$idx.'">';
	$dpt_class = $pattern['prepend_digits'] == '' ? $dpt_title_class : 'dpt-value';
	$dpinput[] = '<td>';
	$dpinput[] = '	<div class="input-group">';
	$dpinput[] = '		<span class="input-group-addon" id="basic-addon'.$idx.'1">(</span>';
	$dpinput[] = '		<input placeholder="' . $pp_tit . '" type="text" id="prepend_digit_'.$idx.'" name="prepend_digit[]" class="form-control ' . $dpt_class.'" value="">';
	$dpinput[] = '		<span class="input-group-addon" id="basic-addon'.$idx.'2">)</span>';
	$dpinput[] = '	</div>';
	$dpinput[] = '</td>';
	$dpt_class = $pattern['match_pattern_prefix'] == '' ? $dpt_title_class : 'dpt-value';
	$dpinput[] = '<td>';
	$dpinput[] = '	<div class="input-group">';
	$dpinput[] = '		<input placeholder="'. $pf_tit .'" type="text" id="pattern_prefix_'.$idx.'" name="pattern_prefix[]" class="form-control '.$dpt_class.'" value="" > ';
	$dpinput[] = '		<span class="input-group-addon" id="basic-addon'.$idx.'3">|</span>';
	$dpinput[] = '	</div>';
	$dpinput[] = '</td>';
	$dpt_class = $pattern['match_pattern_pass'] == '' ? $dpt_title_class : 'dpt-value';
	$dpinput[] = '<td>';
	$dpinput[] = '	<div class="input-group">';
	$dpinput[] = '		<span class="input-group-addon" id="basic-addon'.$idx.'4">[</span>';
	$dpinput[] = '		<input placeholder="'.$mp_tit.'" type="text" id="pattern_pass_'.$idx.'" name="pattern_pass[]" class="form-control '.$dpt_class.'" value=""> ';
	$dpinput[] = '		<span class="input-group-addon" id="basic-addon'.$idx.'5">/</span>';
	$dpinput[] = '	</div>';
	$dpinput[] = '</td>';
	$dpt_class = $pattern['match_cid'] == '' ? $dpt_title_class : 'dpt-value';
	$dpinput[] = '<td>';
	$dpinput[] = '	<div class="input-group">';
	$dpinput[] = '		<input placeholder="'.$ci_tit.'" type="text" id="match_cid_'.$idx.'" name="match_cid[]" class="form-control '.$dpt_class.'" value="">';
	$dpinput[] = '		<span class="input-group-addon" id="basic-addon'.$idx.'6">]</span>';
	$dpinput[] = '	</div>';
	$dpinput[] = '<td>';
	$dpinput[] = '		<a href="#"  id="routerowadd'.$idx.'"><i class="fa fa-plus"></i></a>';
	$dpinput[] = '		<a href="#"  id="routerowdel'.$idx.'"><i class="fa fa-trash"></i></a>';
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
//trunk html
$trunks = array();
foreach (core_trunks_listbyid() as $temp) {
	$trunks[$temp['trunkid']] = $temp['name'];
	$trunkstate[$temp['trunkid']] = $temp['disabled'];
}
$key = -1;
$positions=count($trunkpriority);
$trunkhtml = '<table class = "table">';
$trunkhtml .= '<tbody id="routetrunks">';
if(!empty($trunkpriority) && is_array($trunkpriority)) {
	foreach ($trunkpriority as $key=>$trunk) {
				$trunkhtml .= '<tr id=trunkrow'.$key.' data-id="'.$key.'">';
				$trunkhtml .= '<td>';
				$trunkhtml .= '<div class="input-group">';
				$trunkhtml .= '<span class="input-group-addon move" id="basic-addon'.$key.'"><i class="fa fa-arrows"></i></span>';
			    $trunkhtml .= '<select id="trunkpri'.$key.'" name="trunkpriority['.$key.']" class="form-control '. ($trunkstate[$trunk]=='off'?"":'text-danger').'">';
				$trunkhtml .= '<option value=""></option>';
				foreach ($trunks as $name=>$display_description) {
					if ($trunkstate[$name] == 'off') {
						$trunkhtml .= '<option id="trunk'.$key.'" name="trunk'.$key.'" value="'.$name.'" '.($name == $trunk ? "selected" : "").'>'.str_replace('AMP:', '', $display_description).'</option>';
					} else {
						$trunkhtml .= '<option id="trunk'.$key.'" class="text-danger" name="trunk'.$key.'" value="'.$name.'" '.($name == $trunk ? "selected" : "").'>'.str_replace('AMP:', '', $display_description).'</option>';
					}
				}

				$trunkhtml .= '</select>';
				$trunkhtml .= '</div>';
				$trunkhtml .= '</td>';
				$trunkhtml .= '</tr>';
	}
}
$key += 1;
$name = "";
$num_new_boxes = ($extdisplay ? 1 : ((count($trunks) > 3) ? 3 : count($trunks)));
for ($i=0; $i < $num_new_boxes; $i++) {

	$trunkhtml .= '<tr id=trunkrow'.$key.' data-id="'.$key.'">';
	$trunkhtml .= '<td>';
	$trunkhtml .= '<div class="input-group">';
	$trunkhtml .= '<span class="input-group-addon move" id="basic-addon'.$key.'"><i class="fa fa-arrows"></i></span>';
	$trunkhtml .= '<select id="trunkpri'.$key.'" name="trunkpriority['.$key.']" class="form-control">';
	$trunkhtml .= '<option value="" SELECTED></option>';
	foreach ($trunks as $name=>$display_description) {
		if ($trunkstate[$name] == 'off') {
			$trunkhtml .= '<option value="'.$name.'">'.str_replace('AMP:', '', $display_description).'</option>';
		} else {
			$trunkhtml .= '<option value="'.$name.'" class="text-danger" >*'.ltrim($display_description,"AMP:").'*</option>';
		}
	}
	$trunkhtml .= '</select>';
	$trunkhtml .= '</div>';
	$trunkhtml .= '</td>';
	$trunkhtml .= '</tr>';
	$key++;
}
	$trunkhtml .= '</tbody>';
	$trunkhtml .= '</table>';
?>
<form enctype="multipart/form-data" class="fpbx-submit" autocomplete="off" id="routeEdit" name="routeEdit" action="?display=routing" method="POST" data-fpbx-delete="config.php?display=<?php echo urlencode($display) ?>&id=<?php echo urlencode($extdisplay) ?>&action=delroute">
	<div style="display: none;">
		<input type="text" id="PreventChromeAutocomplete" name="PreventChromeAutocomplete" autocomplete="address-level4" aria-hidden="true" />
		<input type="password" id="PreventChromeAutocomplete2" name="PreventChromeAutocomplete2" autocomplete="address-level4" aria-hidden="true"/>
	</div>
	<input type="hidden" id="extdisplay" name="extdisplay" value="<?php echo $extdisplay ?>"/>
	<input type="hidden" id="id" name="id" value="<?php echo $extdisplay ?>"/>
	<input type="hidden" id="action" name="action" value="<?php echo $formAction ?>"/>
	<input type="hidden" id="repotrunkdirection" name="repotrunkdirection" value="">
	<input type="hidden" id="repotrunkkey" name="repotrunkkey" value="">
	<input type="hidden" id="reporoutedirection" name="reporoutedirection" value="">
	<input type="hidden" id="reporoutekey" name="reporoutekey" value="">
<ul class="nav nav-tabs">
  <li role="presentation" data-name="routesettings" class="change-tab active"><a href="#routesettings" data-toggle="tab"><?php echo _("Route Settings")?></a></li>
  <li role="presentation" data-name="dialpatterns" class="change-tab"><a href="#dialpatterns" data-toggle="tab"><?php echo _("Dial Patterns")?></a></li>
  <li role="presentation" data-name="importexport" class="change-tab"><a href="#importexport" data-toggle="tab"><?php echo _("Import/Export Patterns")?></a></li>
    <?php echo $hooks['hookTabs'] ?>
  <?php echo $hooktab ?>
</ul>
<div id="formtabs" class="tab-content display">
	<div class="tab-pane active" id="routesettings">
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
									<input type="text" class="form-control" id="routename" name="routename" value="<?php echo htmlspecialchars($routename);?>" required>
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
									<span class="radioset">
									<input type="radio" name="outcid_mode" id="outcid_modeyes" value="override_extension" <?php echo ($outcid_mode == "override_extension"?"CHECKED":"") ?>>
									<label for="outcid_modeyes"><?php echo _("Yes");?></label>
									<input type="radio" name="outcid_mode" id="outcid_modeno" value="" <?php echo ($outcid_mode == "override_extension"?"":"CHECKED") ?>>
									<label for="outcid_modeno"><?php echo _("No");?></label>
									</span>
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
									<div class="input-group">
										<input type="password" class="form-control toggle-password" id="routepass" name="routepass" value="<?php echo $routepass;?>">
										<span class="input-group-btn">
											<button data-id="routepass" class="btn btn-default toggle-password" type="button"><i class="fa fa-2x fa-eye" style="margin-top: -2px;"></i></button>
										</span>
									</div>
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
									<input type='checkbox' name='emergency' id="emergency" value='YES' <?php echo ($emergency ? "CHECKED" : "") ?>>
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
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="route_seq"><?php echo _("Route Position")?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="route_seq"></i>
								</div>
								<div class="col-md-9">
									<select name="route_seq" class="form-control">
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
			<!--TRUNK PRIORITY-->
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="trunkwrap"><?php echo _("Trunk Sequence for Matched Routes") ?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="trunkwrap"></i>
								</div>
								<div class="col-md-9">
									<?php echo $trunkhtml ?>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="trunkwrap-help" class="help-block fpbx-help-block"><?php echo _("The Trunk Sequence controls the order of trunks that will be used when the above Dial Patterns are matched. <br><br>For Dial Patterns that match long distance numbers, for example, you'd want to pick the cheapest routes for long distance (ie, VoIP trunks first) followed by more expensive routes (POTS lines).")?></span>
					</div>
				</div>
			</div>
			<!--END TRUNK PRIORITY-->
			<!--CONGESTION DESTINATION-->
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="goto0"><?php echo _("Optional Destination on Congestion") ?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="goto0"></i>
								</div>
								<div class="col-md-9">
									<?php echo drawselects(!empty($dest)?$dest:null,0,false,true,_("Normal Congestion"),false);?>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="goto0-help" class="help-block fpbx-help-block"><?php echo _("Optional Destination on Congestion") ?></span>
					</div>
				</div>
			</div>
			<!--END CONGESTION DESTINATION-->
			</div>
			<!--Dial Patterns -->
			<div class="tab-pane" id="dialpatterns">
				<br/>
				<br/>
				<h3><?php echo $dplabel?></h3>
				<?php echo $dphelp?>
				<br/>
				<a href="#" class="btn btn-default btn-block" data-toggle="modal"	data-target="#dpwizard" id="wizmenu"><i class="fa fa-magic">&nbsp; <?php echo _("Dial patterns wizards")?></i></a>
				<br/><br/>
				<table class="table table-striped" id="dptable">
				<?php echo $dprows ?>
				</table>
			</div>
			<!--END DIALPATTERN INPUT(s)-->
			<!--IMPORT/EXPORT-->
			<div class="tab-pane" id="importexport">
				<div class="element-container">
					<div class="row">
						<div class="col-md-12">
							<div class="row">
								<div class="form-group">
									<div class="col-md-3">
										<label class="control-label" for="importwrapper"><?php echo _("Upload from CSV") ?></label>
										<i class="fa fa-question-circle fpbx-help-icon" data-for="importwrapper"></i>
									</div>
									<div class="col-md-9">
										<span class="btn btn-default btn-file">
											<?php echo _("Choose File")?><input type="file" name="pattern_file" class="form-control" />
										</span>
										<span class="filename"></span>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<span id="importwrapper-help" class="help-block fpbx-help-block"><?php echo sprintf(_("Upload patterns from a CSV file replacing existing entries. If there are no headers then the file must have 4 columns of patterns in the same order as in the GUI. You can also supply headers: %s, %s, %s and %s in the first row. If there are less then 4 recognized headers then the remaining columns will be blank"),'<strong>prepend</strong>','<strong>prefix</strong>','<strong>match pattern</strong>','<strong>callerid</strong>')?></span>
						</div>
					</div>
				</div>
				<div class="element-container">
					<div class="row">
						<div class="col-md-12">
							<div class="row">
								<div class="form-group">
									<div class="col-md-3">
										<label class="control-label" for="export_file"><?php echo _("Export Dialplans as CSV")?></label>
										<i class="fa fa-question-circle fpbx-help-icon" data-for="export_file"></i>
									</div>
									<div class="col-md-9">
										<input type="button" class="btn btn-default" onclick="parent.location='config.php?quietmode=1&amp;handler=file&amp;file=export.html.php&amp;module=core&amp;display=routing&amp;extdisplay=<?php echo $extdisplay;?>'" value="Export">
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-12">
							<span id="export_file-help" class="help-block fpbx-help-block"><?php echo sprintf(_("Export patterns as a CSV file with headers listed as: %s, %s, %s and %s in the first row."),'<strong>prepend</strong>','<strong>prefix</strong>','<strong>match pattern</strong>','<strong>callerid</strong>')?></span>
						</div>
					</div>
				</div>
			</div>
			<!--END IMPORT/EXPORT-->
			<!--Hooks in the "Additional Settings tab -->
			<div class="tab-pane" id="additionalsettings">
				<?php //echo $module_hook->hookHtml; ?>
				<?php echo $hooks['oldHooks'] ?>
			</div>
			<!--End Hooks -->
	</form>
	<!-- Dialplan Wizard-->
	<div class="modal fade" id="dploading">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-body">
					<div class="row">
						<div class="col-md-3">
							<i class="fa fa-spin fa-spinner fa-3x"></i>
						</div>
						<div class="col-md-8">
							<h2><?php echo _("LOADING ROUTES")?></h2>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="modal fade" id="dpwizard">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title"><?php echo _("Dial patterns wizards")?></h4>
				</div>
				<div class="modal-body">
					<div class="well well-info">
						<p><?php echo _("These options provide a quick way to add outbound dialing rules. Follow the prompts for each.")?></p>
						<p></p>
						<p><strong><?php echo _("Download local prefixes")?></strong> <?php echo _("This looks up your local number on www.localcallingguide.com (NA-only), and sets up so you can dial either 7, 10 or 11 digits (5551234, 6135551234, 16135551234) as selected below to access this route. Please note this requires internet access and may take some time")?></p>
						<p><strong><?php echo _("Generate Buttons")?></strong><?php echo _("You may choose 7,10,11 digit patterns as your provider allows. If you do not choose 'Download' this will add a generic 7,10 or ll digit pattern")?></p>
						<p><strong><?php echo _("Generic Patterns")?></strong><?php echo _("You may select to allow toll free calls such as 800,877 etc as well as Directory assistance, International dialing and long distance")?></p>
					</div>

					<label for="lpwnpa">NPA</label>
					<input type="tel" id='lpwnpa' class="form-control">
					<label for="lpwnxx">NXX</label>
					<input type="tel" id='lpwnxx' class="form-control">
					<div class = "form-group radioset">
					<input type="checkbox" id="fwdownload">
					<label for="fwdownload"><?php echo _("Download Local Patterns");?></label>
					</div>
					<div class = "form-group radioset">
					<input type="checkbox" id="fw7" checked>
					<label for="fw7"><?php echo _("7 Digit Patterns")?></label>
					<input type="checkbox" id="fw10" checked>
					<label for="fw10"><?php echo _("10 Digit Patterns")?></label>
					<input type="checkbox" id="fw11">
					<label for="fw11"><?php echo _("11 Digit Patterns")?></label>
					</div>
					<div class = "form-group radioset">
					<input type="checkbox" id="fwtollfree">
					<label for="fwtollfree"><?php echo _("US Toll Free Patterns")?></label>
					<input type="checkbox" id="fwinfo">
					<label for="fwinfo"><?php echo _("US Information")?></label>
					<input type="checkbox" id="fwemergency" checked>
					<label for="fwemergency"><?php echo _("US Emergency")?></label>
					<input type="checkbox" id="fwint">
					<label for="fwint"><?php echo _("US International")?></label>
					<input type="checkbox" id="fwld">
					<label for="fwld"><?php echo _("Long Distance")?></label>
					</div>
					<div id ="lpresults"></div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _("Close")?></button>
					<button type="button" class="btn btn-primary" id="routinggetlocalprefixes"><?php echo _("Generate Routes")?></button>
				</div>
			</div><!-- /.modal-content -->
		</div><!-- /.modal-dialog -->
	</div><!-- /.modal -->
	<!-- END Dialplan Wizard-->

</div>

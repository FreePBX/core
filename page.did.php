<?php /* $Id$ */
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
$request = $_REQUEST;
$tabindex = 0;
if($action == 'delIncoming'){
	$message = 'Route '.$extdisplay.' '._("deleted").'!';
}
$formdata = array('extdisplay' => $extdisplay,
				'old_extdisplay' => $old_extdisplay,
				'dispnum' => $dispnum,
				'account' => $account,
				'goto' => $goto,
				'ringing' => $ringing,
				'reversal' => $reversal,
				'description' => $description,
				'privacyman' => $privacyman,
				'pmmaxretries' => $pmmaxretries,
				'$pmminlength' => $pmminlength,
				'alertinfo' => $alertinfo,
				'mohclass' => $mohclass,
				'grppre' => $grppre,
				'delay_answer' => $delay_answer,
				'pricid' => $pricid,
				'rnavsort' => $rnavsort,
				'didfilter' => $didfilter,
				'heading' => '<h2>'._("Add Incoming Route").'</h2>',
	);

if($extdisplay){
	$extarray=explode('/',$extdisplay,2);
	$ininfo=\core_did_get($extarray[0],$extarray[1]);
	if (is_array($ininfo) && !empty($ininfo)) {
		extract($ininfo);
	}
	$description = htmlspecialchars($description);
	$extension   = htmlspecialchars($extension);
	$cidnum      = htmlspecialchars($cidnum);
	$alertinfo   = htmlspecialchars($alertinfo);
	$grppre      = htmlspecialchars($grppre);
	$heading = '<h2>'._("Route").': ';
	if($description){
		$heading .= $description . '</h2>';
	}else{
		$heading .= $extdisplay . '</h2>';	
	}


	$formdata['heading'] = $heading;
	$formdata['description'] = $description;
	$formdata['extension'] = $extension;
	$formdata['cidnum'] = $cidnum;
	$formdata['alertinfo'] = $alertinfo;
	$formdata['grppre'] = $grppre;
	$formdata['destination'] = $destination;
	$formdata['pricid'] = $pricid;
	$formdata['alertinfo'] = $alertinfo;
	$formdata['ringing'] = $ringing;
	$formdata['reversal'] = $reversal;
	$formdata['delay_answer'] = $delay_answer;
	$formdata['privacyman'] = $privacyman;
	$formdata['pmmaxretries'] = $pmmaxretries;
	$formdata['pmminlength'] = $pmminlength;
	$formdata['pmminlength'] = $pmminlength;

}else{
	$extension = $extarray[0];
	$cidnum    = $extarray[1];
	$extdisplay = '';
	$heading = '<h2>'._("Add Incoming Route").'</h2>';

	$formdata['heading'] = $heading;
	$formdata['extension'] = $extension;
	$formdata['cidnum'] = $cidnum;
	$formdata['extdisplay'] = $extdisplay;
}

// If this is a direct did, e.g. from-did-direct,nnn,1 then make a link to the extension
//
$did_dest = explode(',',$destination);
if (isset($did_dest[0]) && $did_dest[0] == 'from-did-direct') {
	if (isset($amp_conf["AMPEXTENSIONS"]) && ($amp_conf["AMPEXTENSIONS"] == "deviceanduser")) {
		$editURL = '?display=users&extdisplay='.$did_dest[1];
		$EXTorUSER = _("User");
	}else{
		$editURL = '?display=extensions&extdisplay='.$did_dest[1];
		$EXTorUSER = _("Extension");
	}
	$result = core_users_get($did_dest[1]);
	$label = '<span><img width="16" height="16" border="0" title="'.sprintf(_("Edit %s"),$EXTorUSER).'" alt="" src="images/user_edit.png"/>&nbsp;'.sprintf(_("Edit %s %s (%s)"),$EXTorUSER, $did_dest[1],$result['name']).'</span>';
	$link= "<p><a href=".$editURL.">".$label."</a></p>";
	$formdata['userlink'] = $link;
}
$view = $request['view'];
switch($view){
	case "form":
		$content = load_view(__DIR__.'/views/did/routeform.php', $formdata);
	break;
	default:
		$content = load_view(__DIR__.'/views/did/didgrid.php');
	break;
}

?>
<div class="container-fluid">
	<h1><?php echo _('Inbound Routes')?></h1>
	<div class = "display full-border">
		<div class="row">
			<div class="col-sm-9">
				<div class="fpbx-container">
					<div class="display full-border">
						<?php echo $content ?>
					</div>
				</div>
			</div>
			<div class="col-sm-3 hidden-xs bootnav">
				<div class="list-group">
					<?php show_view(__DIR__.'/views/did/rnav.php');?>
				</div>
			</div>
		</div>
	</div>
</div>
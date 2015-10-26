<?php /* $Id$ */
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
$request = $_REQUEST;
$extdisplay = $_REQUEST['extdisplay'];
extract($request, EXTR_SKIP);
$tabindex = 0;
if($action == 'delIncoming'){
	$message = 'Route '.$extdisplay.' '._("deleted").'!';
}
$formdata = array('extdisplay' => $extdisplay,
				'old_extdisplay' => isset($old_extdisplay) ? $old_extdisplay : '',
				'dispnum' => isset($dispnum) ? $dispnum : '',
				'account' => isset($account) ? $account : '',
				'goto' => isset($goto) ? $goto : '',
				'ringing' => isset($ringing) ? $ringing : '',
				'reversal' => isset($reversal) ? $reversal : '',
				'description' => isset($description) ? $description : '',
				'privacyman' => isset($privacyman) ? $privacyman : '',
				'pmmaxretries' => isset($pmmaxretries) ? $pmmaxretries : '',
				'pmminlength' => isset($pmminlength) ? $pmminlength : '',
				'alertinfo' => isset($alertinfo) ? $alertinfo : '',
				'mohclass' => isset($mohclass) ? $mohclass : '',
				'grppre' => isset($grppre) ? $grppre : '',
				'delay_answer' => isset($delay_answer) ? $delay_answer : '',
				'pricid' => isset($pricid) ? $pricid : '',
				'rnavsort' => isset($rnavsort) ? $rnavsort : '',
				'didfilter' => isset($didfilter) ? $didfilter : '',
				'heading' => '<h2>'._("Add Incoming Route").'</h2>',
	);

if($extdisplay){
	$extarray=explode('/',$extdisplay,2);
	$ininfo=core_did_get($extarray[0],$extarray[1]);
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
	$formdata['mohclass'] = $mohclass;

}else{
	$extension = isset($extarray[0]) ? $extarray[0] : '';
	$cidnum    = isset($extarray[1]) ? $extarray[1] : '';
	$extdisplay = '';
	$heading = '<h2>'._("Add Incoming Route").'</h2>';

	$formdata['heading'] = $heading;
	$formdata['extension'] = $extension;
	$formdata['cidnum'] = $cidnum;
	$formdata['extdisplay'] = $extdisplay;
}

// If this is a direct did, e.g. from-did-direct,nnn,1 then make a link to the extension
//
$did_dest = !empty($destination) ? explode(',',$destination) : array();
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
$view = !empty($request['view']) ? $request['view'] : '';
switch($view){
	case "form":
		$content = load_view(__DIR__.'/views/did/routeform.php', $formdata);
		$mwidth = '9';
		$bootnav = '
		<div class="col-sm-3 hidden-xs bootnav">
			<div class="list-group">';
			$bootnav .=	load_view(__DIR__.'/views/did/rnav.php');
		$bootnav .= '
			</div>
		</div>
		';
	break;
	default:
		$content = load_view(__DIR__.'/views/did/didgrid.php');
		$bootnav = '';
		$mwidth = '12';
	break;
}

?>
<div class="container-fluid">
	<h1><?php echo _('Inbound Routes')?></h1>
	<div class = "display no-border">
		<div class="row">
			<div class="col-sm-12">
				<div class="fpbx-container">
					<div class="display no-border">
						<?php echo $content ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

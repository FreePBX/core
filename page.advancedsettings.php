<?php
$conf					= $freepbx_conf->get_conf_settings();
$display_level			= 10; // TOO confusing with multiple levels $conf['AS_DISPLAY_DETAIL_LEVEL']['value'];
$display_hidden			= $conf['AS_DISPLAY_HIDDEN_SETTINGS']['value'];
$display_readonly		= $conf['AS_DISPLAY_READONLY_SETTINGS']['value'];
$display_friendly_name	= $conf['AS_DISPLAY_FRIENDLY_NAME']['value'];

$current_category		= '';
$row					= 0;
$inputhtmltop = <<<HERE
	<div class="row">
		<div class="form-group">
			<div class="col-md-7">
HERE;
$inputhtmlmiddle = <<<HERE
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
HERE;
$inputhtmlend = <<<HERE
		</div>
	</div>
</div>
HERE;
$forminputs = '';
foreach ($conf as $c){
	if($c['hidden']){
		continue;
	}
	if(!empty($c['module'])) {
		\modgettext::push_textdomain(strtolower($c['module']));
	} else {
		\modgettext::pop_textdomain();
	}
	unset($true);
	unset($false);
	if($c['category'] != $current_category && $current_category != '' ){
		$forminputs .= '</div><br/>';
	}
	if($c['category'] != $current_category){
		$current_category = $c['category'];
		$catid = preg_replace('/\s+/', '', $current_category);
		$forminputs .= '<div class="section-title hidden" data-for="'.$catid.'">';
		$forminputs .= '<h2><i class="fa fa-minus"></i> '._($current_category).'</h2>';
		$forminputs .= '</div>';
		$forminputs .= '<div class="section hidden" data-id="'.$catid.'">';
	}
	$type = $c['type'];
	$iclasses = array('element-container');
	$inputclass = '';
	if($c['readonly']){
		$iclasses[] = 'setro';
		$inputclass = 'setro';
		if(!$display_readonly) {
			continue;
		}
	}
	switch($type){
		case 'bool':
			if($c['value']){
			 $true = 'checked';
			 $false = '';
			}else{
			 $true = '';
			 $false = 'checked';
			}
			$forminputs .= '<div class="'.implode(' ',$iclasses).'">';
			$forminputs .= $inputhtmltop;
			if($display_friendly_name == 1){
				$forminputs .= '<label class="control-label" for="' . $c['keyword'] . '">'._($c['name']).'</label>';
			}else{
				$forminputs .= '<label class="control-label" for="' . $c['keyword'] . '">'.$c['keyword'].'</label>';
			}
			$forminputs .= '<i class="fa fa-question-circle fpbx-help-icon" data-for="' . $c['keyword'] . '"></i>';
			$forminputs .= '&nbsp;';
			$forminputs .= '<a href="#" data-for="'.$c['keyword'].'" data-type="'.$c['type'].'" data-defval="'.$c['defaultval'].'" class="hidden defset"><i class="fa fa-refresh"></i></a>';
			$forminputs .= '</div>';
			$forminputs .= '<div class="col-md-5 radioset text-right">';
			$forminputs .= '<input type="hidden" id="'.$c['keyword'].'default" value="'.$c['defaultval'].'">';
			$forminputs .= '<input type="radio" class="'.$inputclass.'" id="' . $c['keyword'] . 'true" name="' . $c['keyword'] . '" value="true" '.$true.'>';
			$forminputs .= '<label for="'.$c['keyword'].'true">'._("Yes").'</label>';
			$forminputs .= '<input type="radio" class="'.$inputclass.'" id="' . $c['keyword'] . 'false" name="' . $c['keyword'] . '" value="false" '.$false.'>';
			$forminputs .= '<label for="'.$c['keyword'].'false">'._("No").'</label>';
			$forminputs .= '</div>';
			$forminputs .= $inputhtmlmiddle;
			if($display_friendly_name == 1){
				$forminputs .= '<span id="'.$c['keyword'].'-help" class="help-block fpbx-help-block">'._("KEYWORD").":".$c['keyword']."<br/>"._($c['description']).'</span>';
			}else{
				$forminputs .= '<span id="'.$c['keyword'].'-help" class="help-block fpbx-help-block">'._("Friendy Name").":".$c['name']."<br/>"._($c['description']).'</span>';
			}
			$forminputs .= $inputhtmlend;
		break;
		case 'int':
			$forminputs .= '<div class="'.implode(' ',$iclasses).'">';
			$forminputs .= $inputhtmltop;
			$opts = $c['options'];
			if($display_friendly_name == 1){
				$forminputs .= '<label class="control-label" for="' . $c['keyword'] . '">'._($c['name']).'</label>';
			}else{
				$forminputs .= '<label class="control-label" for="' . $c['keyword'] . '">'.$c['keyword'].'</label>';
			}
			$forminputs .= '<i class="fa fa-question-circle fpbx-help-icon" data-for="' . $c['keyword'] . '"></i>';
			$forminputs .= '&nbsp;';
			$forminputs .= '<a href="#" data-for="'.$c['keyword'].'" data-type="'.$c['type'].'" data-defval="'.$c['defaultval'].'" class="hidden defset"><i class="fa fa-refresh"></i></a>';
			$forminputs .= '</div>';
			$forminputs .= '<div class="col-md-5 text-right">';
			$forminputs .= '<input type="hidden" id="'.$c['keyword'].'default" value="'.$c['defaultval'].'">';
			if(!empty($c['options']) && preg_match('/,/',$c['options'])) {
				$parts = explode(",",$c['options']);
				$min = $parts[0];
				$max = $parts[1];
				$forminputs .= '<input type="number" class="form-control '.$inputclass.'" id="'.$c['keyword'].'" name="'.$c['keyword'].'" value="'.$c['value'].'" min="'.$min.'" max="'.$max.'">';
			} else {
				$forminputs .= '<input type="number" class="form-control '.$inputclass.'" id="'.$c['keyword'].'" name="'.$c['keyword'].'" value="'.$c['value'].'">';
			}
			$forminputs .= '</div>';
			$forminputs .= $inputhtmlmiddle;
			if($display_friendly_name == 1){
				$forminputs .= '<span id="'.$c['keyword'].'-help" class="help-block fpbx-help-block">'._("KEYWORD").":".$c['keyword']."<br/>"._($c['description']).'</span>';
			}else{
				$forminputs .= '<span id="'.$c['keyword'].'-help" class="help-block fpbx-help-block">'._("Friendly Name").":".$c['name']."<br/>"._($c['description']).'</span>';
			}
			$forminputs .= $inputhtmlend;
		break;
		case 'dir':
		case 'text':
			$forminputs .= '<div class="'.implode(' ',$iclasses).'">';
			$forminputs .= $inputhtmltop;
			if($display_friendly_name == 1){
				$forminputs .= '<label class="control-label" for="' . $c['keyword'] . '">'._($c['name']).'</label>';
			}else{
				$forminputs .= '<label class="control-label" for="' . $c['keyword'] . '">'.$c['keyword'].'</label>';
			}
			$forminputs .= '<i class="fa fa-question-circle fpbx-help-icon" data-for="' . $c['keyword'] . '"></i>';
			$forminputs .= '&nbsp;';
			$forminputs .= '<a href="#" data-for="'.$c['keyword'].'" data-type="'.$c['type'].'" data-defval="'.$c['defaultval'].'" class="hidden defset"><i class="fa fa-refresh"></i></a>';
			$forminputs .= '</div>';
			$forminputs .= '<div class="col-md-5 text-right">';
			$forminputs .= '<input type="hidden" id="'.$c['keyword'].'default" value="'.$c['defaultval'].'">';
			$forminputs .= '<input type="text" class="form-control '.$inputclass.'" id="'.$c['keyword'].'" name="'.$c['keyword'].'" value="'.$c['value'].'" >';
			$forminputs .= '</div>';
			$forminputs .= $inputhtmlmiddle;
			if($display_friendly_name == 1){
				$forminputs .= '<span id="'.$c['keyword'].'-help" class="help-block fpbx-help-block">'._("KEYWORD").":".$c['keyword']."<br/>"._($c['description']).'</span>';
			}else{
				$forminputs .= '<span id="'.$c['keyword'].'-help" class="help-block fpbx-help-block">'._("Friendly Name").":".$c['name']."<br/>"._($c['description']).'</span>';
			}
			$forminputs .= $inputhtmlend;
		break;
		case 'fselect':
			$forminputs .= '<div class="'.implode(' ',$iclasses).'">';
			$forminputs .= $inputhtmltop;
			if($display_friendly_name == 1){
				$forminputs .= '<label class="control-label" for="' . $c['keyword'] . '">'._($c['name']).'</label>';
			}else{
				$forminputs .= '<label class="control-label" for="' . $c['keyword'] . '">'.$c['keyword'].'</label>';
			}
			$forminputs .= '<i class="fa fa-question-circle fpbx-help-icon" data-for="' . $c['keyword'] . '"></i>';
			$forminputs .= '&nbsp;';
			$forminputs .= '<a href="#" data-for="'.$c['keyword'].'" data-type="'.$c['type'].'" data-defval="'.$c['defaultval'].'" class="hidden defset"><i class="fa fa-refresh"></i></a>';
			$forminputs .= '</div>';
			$forminputs .= '<div class="col-md-5 text-right">';
			$forminputs .= '<input type="hidden" id="'.$c['keyword'].'default" value="'.$c['defaultval'].'">';
			$forminputs .= '<select class="form-control '.$inputclass.'" id="'.$c['keyword'].'" name="'.$c['keyword'].'">';
			foreach($c['options'] as $k => $o) {
				$selected = ($amp_conf[$c['keyword']] == $k) ? ' selected ' : '';
				$forminputs .= '<option value="'.$k.'"'.$selected.'>'._($o).'</option>';
			}
			$forminputs .= '</select>';
			$forminputs .= '</div>';
			$forminputs .= $inputhtmlmiddle;
			if($display_friendly_name == 1){
				$forminputs .= '<span id="'.$c['keyword'].'-help" class="help-block fpbx-help-block">'._("KEYWORD").":".$c['keyword']."<br/>"._($c['description']).'</span>';
			}else{
				$forminputs .= '<span id="'.$c['keyword'].'-help" class="help-block fpbx-help-block">'._("Friendly Name").":".$c['name']."<br/>"._($c['description']).'</span>';
			}
			$forminputs .= $inputhtmlend;
		break;
		case 'cselect':
			$forminputs .= '<div class="'.implode(' ',$iclasses).'">';
			$forminputs .= $inputhtmltop;
			if($display_friendly_name == 1){
				$forminputs .= '<label class="control-label" for="' . $c['keyword'] . '">'._($c['name']).'</label>';
			}else{
				$forminputs .= '<label class="control-label" for="' . $c['keyword'] . '">'.$c['keyword'].'</label>';
			}
			$forminputs .= '<i class="fa fa-question-circle fpbx-help-icon" data-for="' . $c['keyword'] . '"></i>';
			$forminputs .= '&nbsp;';
			$forminputs .= '<a href="#" data-for="'.$c['keyword'].'" data-type="'.$c['type'].'" data-defval="'.$c['defaultval'].'" class="hidden defset"><i class="fa fa-refresh"></i></a>';
			$forminputs .= '</div>';
			$forminputs .= '<div class="col-md-5">';
			$forminputs .= '<input type="hidden" id="'.$c['keyword'].'default" value="'.$c['defaultval'].'">';
			$forminputs .= '<input id="'.$c['keyword'].'" type="search" name="'.$c['keyword'].'" placeholder="'._("Double-Click to see options or type freeform").'" class="form-control '.$inputclass.'" list="'.$c['keyword'].'-list" value="'.$amp_conf[$c['keyword']].'">';
			$forminputs .= '<datalist id="'.$c['keyword'].'-list">';
			$opt = explode(',',$c['options']);
			foreach($opt as $o) {
				$forminputs .= '<option label="'._($o).'" value="'.$o.'">'._($o).'</option>';
			}
			$forminputs .= '</datalist>';
			$forminputs .= '</div>';
			$forminputs .= $inputhtmlmiddle;
			if($display_friendly_name == 1){
				$forminputs .= '<span id="'.$c['keyword'].'-help" class="help-block fpbx-help-block">'._("KEYWORD").":".$c['keyword']."<br/>"._($c['description']).'</span>';
			}else{
				$forminputs .= '<span id="'.$c['keyword'].'-help" class="help-block fpbx-help-block">'._("Friendly Name").":".$c['name']."<br/>"._($c['description']).'</span>';
			}
			$forminputs .= $inputhtmlend;
		break;
		case 'select':
			$forminputs .= '<div class="'.implode(' ',$iclasses).'">';
			$forminputs .= $inputhtmltop;
			if($display_friendly_name == 1){
				$forminputs .= '<label class="control-label" for="' . $c['keyword'] . '">'._($c['name']).'</label>';
			}else{
				$forminputs .= '<label class="control-label" for="' . $c['keyword'] . '">'.$c['keyword'].'</label>';
			}
			$forminputs .= '<i class="fa fa-question-circle fpbx-help-icon" data-for="' . $c['keyword'] . '"></i>';
			$forminputs .= '&nbsp;';
			$forminputs .= '<a href="#" data-for="'.$c['keyword'].'" data-type="'.$c['type'].'" data-defval="'.$c['defaultval'].'" class="hidden defset"><i class="fa fa-refresh"></i></a>';
			$forminputs .= '</div>';
			$forminputs .= '<div class="col-md-5 text-right">';
			$forminputs .= '<input type="hidden" id="'.$c['keyword'].'default" value="'.$c['defaultval'].'">';
			$forminputs .= '<select class="form-control '.$inputclass.'" id="'.$c['keyword'].'" name="'.$c['keyword'].'">';
			$opt = explode(',',$c['options']);
			foreach($opt as $o) {
				$selected = ($amp_conf[$c['keyword']] == $o) ? ' selected ' : '';
				$forminputs .= '<option value="'.$o.'"'.$selected.'>'._($o).'</option>';
			}
			$forminputs .= '</select>';
			$forminputs .= '</div>';
			$forminputs .= $inputhtmlmiddle;
			if($display_friendly_name == 1){
				$forminputs .= '<span id="'.$c['keyword'].'-help" class="help-block fpbx-help-block">'._("KEYWORD").":".$c['keyword']."<br/>"._($c['description']).'</span>';
			}else{
				$forminputs .= '<span id="'.$c['keyword'].'-help" class="help-block fpbx-help-block">'._("Friendly Name").":".$c['name']."<br/>"._($c['description']).'</span>';
			}
			$forminputs .= $inputhtmlend;
		break;
		case 'textarea':
			$forminputs .= '<div class="'.implode(' ',$iclasses).'">';
			$forminputs .= $inputhtmltop;
			if($display_friendly_name == 1){
				$forminputs .= '<label class="control-label" for="' . $c['keyword'] . '">'._($c['name']).'</label>';
			}else{
				$forminputs .= '<label class="control-label" for="' . $c['keyword'] . '">'.$c['keyword'].'</label>';
			}
			$forminputs .= '<i class="fa fa-question-circle fpbx-help-icon" data-for="' . $c['keyword'] . '"></i>';
			$forminputs .= '&nbsp;';
			$forminputs .= '<a href="#" data-for="'.$c['keyword'].'" data-type="'.$c['type'].'" data-defval="'.$c['defaultval'].'" class="hidden defset"><i class="fa fa-refresh"></i></a>';
			$forminputs .= '</div>';
			$forminputs .= '<div class="col-md-5 text-right">';
			$forminputs .= '<input type="hidden" id="'.$c['keyword'].'default" value="'.$c['defaultval'].'">';
			$forminputs .= '<textarea class="form-control '.$inputclass.'" rows = "4" id="'.$c['keyword'].'" name="'.$c['keyword'].'">'.$c['value'].'</textarea>';
			$forminputs .= '</div>';
			$forminputs .= $inputhtmlmiddle;
			if($display_friendly_name == 1){
				$forminputs .= '<span id="'.$c['keyword'].'-help" class="help-block fpbx-help-block">'._("KEYWORD").":".$c['keyword']."<br/>"._($c['description']).'</span>';
			}else{
				$forminputs .= '<span id="'.$c['keyword'].'-help" class="help-block fpbx-help-block">'._("Friendly Name").":".$c['name']."<br/>"._($c['description']).'</span>';
			}
			$forminputs .= $inputhtmlend;
		break;
		default:
			dbug($c);
		break;
	}
	\modgettext::pop_textdomain();
}
$forminputs .= "</div> <!-- close last element -->\n";
?>

<div class="container-fluid">
	<h1><?php echo sprintf(_("%s Advanced Settings"),$conf['DASHBOARD_FREEPBX_BRAND']['value'])?></h1>
	<div class="alert alert-warning">
		<?php echo "<b>"._('IMPORTANT:')."</b>". _('Use extreme caution when making changes!')?>
	</div>
	<div class="well">
		<?php echo "<strong>"._("Some of these settings can render your system inoperable."). "</strong><br/>". _("You are urged to backup before making any changes.")."<br/>"._("Readonly settings are usually more volatile, they can be changed by changing 'Override Readonly Settings' to Yes.")."<br/>".sprintf(_("You can restore the default setting by clicking on the %s icon to the left of the values if they are not set as default"),"<i class='fa fa-refresh'></i>")."<br/>"._("Unlike previous versions of this module you now save changes as a group.");?>
	</div>
	<div class = "display full-border">
		<div class="fpbx-container">
			<div class="display full-border">
				<form class="fpbx-submit" name="submitSettings" action="" method="post">
					<input type="hidden" name="action" value="setkey">
					<?php echo $forminputs ?>
				</form>
			</div>
		</div>
	</div>
</div>
</br>
</br>
</br>

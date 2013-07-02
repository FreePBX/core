<?php /* $Id */
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

$getvars = array('action', 'keyword', 'value');
foreach ($getvars as $v){
	$var[$v] = isset($_POST[$v]) ? $_POST[$v] : 0;
}

if($var['action'] === 'setkey') {
	header("Content-type: application/json"); 
	$keyword = $var['keyword'];
	if ($freepbx_conf->conf_setting_exists($keyword)) {
		$freepbx_conf->set_conf_values(array($keyword => trim($var['value'])),true,$amp_conf['AS_OVERRIDE_READONLY']);
		$status = $freepbx_conf->get_last_update_status();
		if ($status[$keyword]['saved']) {
			freepbx_log(FPBX_LOG_INFO,sprintf(_("Advanced Settings changed freepbx_conf setting: [$keyword] => [%s]"),$var['value']));
			needreload();
		}
		echo json_encode($status[$keyword]);
		exit;
	}
	exit;
}
$amportal_canwrite = $freepbx_conf->amportal_canwrite() ? 'true' : 'false';
echo '<script type="text/javascript">';
echo 'can_write_amportalconf = ' . $amportal_canwrite . '; ';
echo 'amportalconf_error = "' . _("You must run 'amportal restart' from the Linux command line before you can save setting here.") . '";';
echo 'msgUnsavedChanges = "' . _("You have un-saved changes, press OK to disregard changes and reload page or Cancel to abort.") . '";';
echo 'msgChangesRefresh = "' . _("Your Display settings have been changed, click on 'Refresh Page' to view the affects of your changes once you have saved other outstanding changes that are still un-confirmed.") . '";';
echo '</script>';

echo '<div id="main_page">';
echo "<h2>"._("FreePBX Advanced Settings")."</h2>";
echo '<p>'._("<b>IMPORTANT:</b> Use extreme caution when making changes!").'</p>'._("Some of these settings can render your system inoperable. You are urged to backup before making any changes. Readonly settings are usually more volatile, they can be changed by changing 'Override Readonly Settings' to true. Once changed you must save the setting by checking the green check box that appears. You can restore the default setting by clicking on the icon to the right of the values if not set at default.");

$conf					= $freepbx_conf->get_conf_settings();

$display_level			= 10; // TO confusing with multiple levels $conf['AS_DISPLAY_DETAIL_LEVEL']['value'];
$display_hidden			= $conf['AS_DISPLAY_HIDDEN_SETTINGS']['value'];
$display_readonly		= $conf['AS_DISPLAY_READONLY_SETTINGS']['value'];
$display_friendly_name	= $conf['AS_DISPLAY_FRIENDLY_NAME']['value'];

$current_category		= '';
$row					= 0;

echo '<input type="image" src="images/spinner.gif" style="display:none">';
echo '<table class="alt_table">';
foreach ($conf as $c){
	if ($c['level'] > $display_level || $c['hidden'] && !$display_hidden || $c['readonly'] && !$display_readonly) {
		continue;
	}
	if ($current_category != $c['category']) {
		$current_category = $c['category'];

		// TODO: Temp fix until someone much better at styling then me can actually properly fix this :)
		//       it's only purpose is to get the headings so they are not shaded and so the stripped shading
		//       starts consistent for each section.
		//
		if ($row % 2) {
			echo '<tr></tr>';
			$row++;
		}
		$current_category_loc = modgettext::_($current_category, $c['module']);
		echo '<tr><td colspan="3"><br><h4 class="category">'._("$current_category_loc").'</h4></td></tr>';
		$row++;
	}

	$name_label_raw = $c['name'];
	$description_raw = $c['description'];
	$name_label = modgettext::_($name_label_raw, $c['module']);
	$tt_description = modgettext::_($description_raw, $c['module']);
	if (!$display_friendly_name) {
		$tr_friendly_name = $name_label;
		$name_label = $c['keyword'];
	}

	$row++;
	$dv = $c['type'] == CONF_TYPE_BOOL ? ($c['defaultval'] ? _("True") : _("False")) : $c['defaultval'];
	$default_val = $dv == '' ? _("No Default Provided") : sprintf(_("Default Value: %s"),$dv);
	if ($c['emptyok'] && $c['type'] != CONF_TYPE_BOOL && $c['type'] != CONF_TYPE_SELECT && $c['type'] != CONF_TYPE_FSELECT) {
		$default_val.= ', '._("field can be left blank");
	}
	if ($c['type'] == CONF_TYPE_INT && $c['options']) {
		$range = explode(',',$c['options']);
		$default_val .= '<br />'.sprintf(_("Acceptable Values: %s - %s"),$range[0],$range[1]);
	}
	if ($display_friendly_name) {
		$default_val .= '<br />'.sprintf(_("Internal Name: %s"),$c['keyword']);
	} else {
		$default_val .= '<br />'.sprintf(_("Friendly Name: %s"),$tr_friendly_name);
	}
	echo '<tr><td><a href="javascript:void(null)" class="info">'.$name_label.'<span>'.$tt_description.'<br /><br >'.$default_val.'</span></a></td>';
	echo '<td>';
	switch ($c['type']) {
		case CONF_TYPE_TEXT:
		case CONF_TYPE_DIR:
		case CONF_TYPE_INT:
			$readonly = !$c['readonly'] || $amp_conf['AS_OVERRIDE_READONLY'] && !$c['hidden'] ? '' : 'readonly="readonly"';
			echo '<input class="valueinput" id="'.$c['keyword'].'" type="text" size="60" value="'.htmlspecialchars($amp_conf[$c['keyword']]).'" data-valueinput-orig="'.$amp_conf[$c['keyword']].'" '.$readonly.'/>';
			break;
		case CONF_TYPE_SELECT:
			echo '<select class="valueinput" id="'.$c['keyword'].'" data-valueinput-orig="'.$amp_conf[$c['keyword']].'">';
				$opt = explode(',',$c['options']);
				foreach($opt as $o) {
					$selected = ($amp_conf[$c['keyword']] == $o) ? ' selected ' : '';
					echo '<option value="'.$o.'"'.$selected.'>'.$o.'</option>';
				}
			echo '</select>';
			break;
		case CONF_TYPE_FSELECT:
			echo '<select class="valueinput" id="'.$c['keyword'].'" data-valueinput-orig="'.$amp_conf[$c['keyword']].'">';
				$opt = $c['options'];
				foreach($opt as $o => $l) {
					$selected = ($amp_conf[$c['keyword']] == $o) ? ' selected ' : '';
					echo '<option value="'.$o.'"'.$selected.'>'.modgettext::_($l, $c['module']).'</option>';
				}
			echo '</select>';
			break;
		case CONF_TYPE_BOOL:
?>
			<span class="radioset"><input class="valueinput" data-valueinput-orig="<?php echo $amp_conf[$c['keyword']] ? 1 : 0 ?>" id="<?php echo $c['keyword'] ?>-true" type="radio" name="<?php echo $c['keyword'] ?>" value="1" <?php echo $amp_conf[$c['keyword']]?"checked=\"yes\"":""?>/>
			<label for="<?php echo $c['keyword'] ?>-true"><?php echo _("True") ?></label>
			<input class="valueinput" data-valueinput-orig="<?php echo $amp_conf[$c['keyword']] ? 1 : 0 ?>" id="<?php echo $c['keyword'] ?>-false" type="radio" name="<?php echo $c['keyword'] ?>" value="0" <?php echo !$amp_conf[$c['keyword']]?"checked=\"yes\"":""?>/>
			<label for="<?php echo $c['keyword'] ?>-false"><?php echo _("False") ?></label></span>
<?php
			break;
	}
	echo '</td>';
	if(!$c['readonly'] || $amp_conf['AS_OVERRIDE_READONLY'] && !$c['hidden']){
		echo '<td><input type="image" class="adv_set_default" src="images/default-option.png" data-key="'.$c['keyword'].'" data-default="'.$c['defaultval'].'" title="'._('Revert to Default').'"'
			. ' data-type="' . (($c['type'] == CONF_TYPE_BOOL) ? 'BOOL' : '') . '" ' 
			. (($amp_conf[$c['keyword']] == $c['defaultval']) ? ' style="display:none" ' : '') 
			.'></td>';
		echo '<td class="savetd"><input type="image" class="save" src="images/accept.png" data-key="'
			. $c['keyword'] 
			. '" title="' . _('Save') . '"'
			. ' data-type="' . (($c['type'] == CONF_TYPE_BOOL) ? 'BOOL' : '') . '" ' 
			. '></td>';
	}
	echo '</tr>';
}
echo '</table>';

// Provide enough padding at the bottom (<br />) so that the tooltip from the last setting does not get cut off.
?>
<br /><br /> <br />
<input type="button" id="page_reload" value="<?php echo _("Refresh Page");?>"/>
<br /><br /><br /><br /></div>

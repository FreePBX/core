<?php /* $Id */

// TODO: localize if needed
// TODO: Get rid of undefined in http log. Sigh

$getvars = array('level', 'action', 'keyword', 'value');
foreach ($getvars as $v){
	$var[$v] = isset($_REQUEST[$v]) ? $_REQUEST[$v] : 0;
}

if($var['action'] === 'setkey') {
	//set value, then return a value
	if (core_advancedsettings_set_keys($var['keyword'], $var['value'])) {
		echo 'ok';
	} else {
		echo 'error';
	}
	exit;
}
	//save all values if amportal.conf isnt writeable
  // TODO: why do we save them here? if we are suppose to save them, shouldn't it be somewhere more generic?
  //
	if (!$amp_conf['amportal_canwrite']) {
		foreach($amp_conf as $keyword => $val) {
			core_advancedsettings_set_keys($keyword, $val);
		}
	}
	echo '<script type="text/javascript">';
	echo 'can_write_amportalconf = "'.$amp_conf['amportal_canwrite'] .'"; ';
	echo 'amportalconf_error ="' . _('You must run \'amportal restart\' from the cli before you can save setting here.') . '"';
	echo '</script>';
	
	echo '<div id="main_page">';
    echo "<h2>"._("FreePBX Advanced Settings")."</h2>";
    echo "Use extreme caution! Changes here can render your system inoperable. You are urged to backup before making any changes.<br /><br />";


	//TODO: build table properly with all required tags
	$conf = core_advancedsettings_get_keys($var['level']);
	echo '<input type="image" src="images/spinner.gif" style="display:none">';
	echo '<table id="set_table">';
	foreach ($conf as $c){
		echo '<tr><td><a href="javascript:void(null)" class="info">'.$c['keyword'].'<span>'.$c['description'].'</span></a></td>';
		echo '<td>';
		switch ($c['type']) {
			case CONF_TYPE_TEXT:
			case CONF_TYPE_DIR:
			case CONF_TYPE_INT:
			case CONF_TYPE_UINT:
				echo '<input class="valueinput" id="'.$c['keyword'].'" type="text" size="60" value="'.$amp_conf[$c['keyword']].'" data-valueinput-orig="'.$amp_conf[$c['keyword']].'"/>';
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
			case CONF_TYPE_BOOL:
?>
  <input class="valueinput" data-valueinput-orig="<?php echo $amp_conf[$c['keyword']]?1:0 ?>" id="<?php echo $c['keyword'] ?>-true" type="radio" name="<?php echo $c['keyword'] ?>" value="1" tabindex="<?php echo ++$tabindex;?>"<?php echo $amp_conf[$c['keyword']]?"checked=\"yes\"":""?>/>
  <label for="<?php echo $c['keyword'] ?>-true"><?php echo _("True") ?></label>
  <input class="valueinput" data-valueinput-orig="<?php echo $amp_conf[$c['keyword']]?1:0 ?>" id="<?php echo $c['keyword'] ?>-false" type="radio" name="<?php echo $c['keyword'] ?>" value="0" tabindex="<?php echo ++$tabindex;?>"<?php echo !$amp_conf[$c['keyword']]?"checked=\"yes\"":""?>/>
  <label for="<?php echo $c['keyword'] ?>-false"><?php echo _("False") ?></label>
<?php
				break;



		}
		echo '</td>';
		if($c['readonly'] != 'true'){
			echo '<td><input type="image" class="adv_set_default" src="images/default-option.png" data-key="'.$c['keyword'].'" data-default="'.$c['defaultval'].'" name="default" title="'._('Revert to Default').'"></td>';
			echo '<td class="savetd"><input type="image" class="save" src="images/accept.png" name="save" data-key="'.$c['keyword'].'" title="'._('Save').'"></td>';
			//echo '<td><input type="image" class="delete"  src="images/trash.png" name="delete" data-key="'.$c['keyword'].'" title="'._('Delete').'"></td>'; 
		}
		echo '</tr>';
	}
	echo '</table>';

// Ugly, but I need to display the whole help text within the page    
echo "<br><br><br><br></div>";

?>

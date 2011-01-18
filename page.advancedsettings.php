<?php /* $Id */

  $getvars = array('action', 'keyword', 'value');
  foreach ($getvars as $v){
	  $var[$v] = isset($_POST[$v]) ? $_POST[$v] : 0;
  }

  // TODO: provide error info if errors were detected
  //       change to json so we can send back info like what it was updated to
  //       and other useful stuff
  //
  if($var['action'] === 'setkey') {
    if ($freepbx_conf->conf_setting_exists($var['keyword'])) {
		  $freepbx_conf->set_conf_values(array($var['keyword'] => $var['value']),true);
		  if ($freepbx_conf->get_conf_setting($var['keyword']) == $var['value']) {
			  echo 'ok';
		  }
      dbug($freepbx_conf->get_last_update_status());
    }
	  exit;
  }
	echo '<script type="text/javascript">';
	echo 'can_write_amportalconf = "'.$amp_conf['amportal_canwrite'] .'"; ';
	echo 'amportalconf_error ="' . _('You must run \'amportal restart\' from the cli before you can save setting here.') . '"';
	echo '</script>';
	
	echo '<div id="main_page">';
  echo "<h2>"._("FreePBX Advanced Settings")."</h2>";
  echo "Use extreme caution! Changes here can render your system inoperable. You are urged to backup before making any changes.<br /><br />";

	$conf = $freepbx_conf->get_conf_settings();

  $display_level = $conf['AS_DISPLAY_DETAIL_LEVEL']['value'];
  $display_hidden = $conf['AS_DISPLAY_HIDDEN_SETTINGS']['value'];
  $display_readonly = $conf['AS_DISPLAY_READONLY_SETTINGS']['value'];
  $current_category = '';
  $row = 0;

	echo '<input type="image" src="images/spinner.gif" style="display:none">';
	echo '<table id="set_table">';
	foreach ($conf as $c){

    // TODO: localization by module here, put in the gettext with the module info and try that first like other places
    //
    if ($c['level'] > $display_level || $c['hidden'] && !$display_hidden || $c['readonly'] && !$display_readonly) {
      continue;
    }
    if ($current_category != $c['category']) {
      $current_category = $c['category'];

      // TODO: Temp fix until someone much better at syling then me can actually properly fix this :)
      //       it's only purpose is to get the headings so they are not shaded and so the stripped shading
      //       starts consistent for each section.
      //
      if ($row % 2) {
        //echo '<tr><td colspan="3"><br></td></tr>';
        echo '<tr></tr>';
        $row++;
      }
      if ($c['module'] && extension_loaded('gettext') && is_dir("modules/".$c['module']."/i18n")) {
        bindtextdomain($name,"modules/".$c['module']."/i18n");
        bind_textdomain_codeset($c['module'], 'utf8');
        $current_category_loc = dgettext($c['module'],$current_category);
        if ($current_category_loc == $current_cateogry) {
          $current_cateogry_loc = _($current_category);
        }
      } else {
        $current_category_loc = _($current_category);
      }
      echo '<tr><td colspan="3"><br><h4 class="category">'._("$current_category_loc").'</h4></td></tr>';
      $row++;
    }
    $row++;
    $dv = $c['type'] == CONF_TYPE_BOOL ? ($c['defaultval'] ? _("True") : _("False")) : $c['defaultval'];
    $default_val = $dv == '' ? _("No Default Provided") : sprintf(_("Default Value: %s"),$dv);
    if ($c['emptyok'] && $c['type'] != CONF_TYPE_BOOL && $c['type'] != CONF_TYPE_SELECT) {
      $default_val.= ', '._("field can be left blank");
    }
    if ($c['type'] == CONF_TYPE_INT && $c['options']) {
      $range = explode(',',$c['options']);
      $default_val .= '<br />'.sprintf(_("Acceptable Values: %s - %s"),$range[0],$range[1]);
    }
		echo '<tr><td><a href="javascript:void(null)" class="info">'.$c['keyword'].'<span>'.$c['description'].'<br /><br >'.$default_val.'</span></a></td>';
		echo '<td>';
		switch ($c['type']) {
			case CONF_TYPE_TEXT:
			case CONF_TYPE_DIR:
			case CONF_TYPE_INT:
				$readonly = $c['readonly'] ? 'readonly="readonly"' : '';
				echo '<input class="valueinput" id="'.$c['keyword'].'" type="text" size="60" value="'.$amp_conf[$c['keyword']].'" data-valueinput-orig="'.$amp_conf[$c['keyword']].'" '.$readonly.'/>';
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
  <input class="valueinput" data-valueinput-orig="<?php echo $amp_conf[$c['keyword']] ? 1 : 0 ?>" id="<?php echo $c['keyword'] ?>-true" type="radio" name="<?php echo $c['keyword'] ?>" value="1" <?php echo $amp_conf[$c['keyword']]?"checked=\"yes\"":""?>/>
  <label for="<?php echo $c['keyword'] ?>-true"><?php echo _("True") ?></label>
  <input class="valueinput" data-valueinput-orig="<?php echo $amp_conf[$c['keyword']] ? 1 : 0 ?>" id="<?php echo $c['keyword'] ?>-false" type="radio" name="<?php echo $c['keyword'] ?>" value="0" <?php echo !$amp_conf[$c['keyword']]?"checked=\"yes\"":""?>/>
  <label for="<?php echo $c['keyword'] ?>-false"><?php echo _("False") ?></label>
<?php
				break;
		}
		echo '</td>';
		if(!$c['readonly']){
			//echo '<td><input type="image" class="adv_set_default" src="images/default-option.png" data-key="'.$c['keyword'].'" data-default="'.$c['defaultval'].'" name="default" title="'._('Revert to Default').'"></td>';
			echo '<td><input type="image" class="adv_set_default" src="images/default-option.png" data-key="'.$c['keyword'].'" data-default="'.$c['defaultval'].'" title="'._('Revert to Default').'"'
				. 'data-type="' . (($c['type'] == CONF_TYPE_BOOL) ? 'BOOL' : '') . '" ' 
				.'"></td>';
			echo '<td class="savetd"><input type="image" class="save" src="images/accept.png" data-key="'
				. $c['keyword'] 
				. '" title="' . _('Save') . '"'
				. 'data-type="' . (($c['type'] == CONF_TYPE_BOOL) ? 'BOOL' : '') . '" ' 
				. '></td>';
		}
		echo '</tr>';
	}
	echo '</table>';

// Ugly, but I need to display the whole help text within the page    
echo "<br><br><br><br></div>";

?>

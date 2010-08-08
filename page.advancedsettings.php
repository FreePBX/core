<?php /* $Id */

// TODO: localize if needed
// TODO: Get rid of undefined in http log. Sigh

$getvars = array('level', 'action', 'key', 'value');

foreach ($getvars as $v){
	$var[$v] = isset($_REQUEST[$v]) ? $_REQUEST[$v] : 0;
}

if($var['action'] === 'setkey') {
	//set value, then return a value
	if (core_advancedsettings_set_keys($var['key'], $var['value'])) {
		echo 'ok';
	} else {
		echo 'error';
	}
	exit;
}
	echo '<div id="page">';
    echo "<h2>"._("FreePBX Advanced Settings")."</h2>";
    echo "Use extreme caution! Changes here can render your system inoperable. You are urged to backup before making any changes.<br /><br />";


	//TODO: build table properly witl all required tags
	$conf = core_advancedsettings_get_keys($var['level']);
	echo '<input type="image" src="images/spinner.gif" style="display:none">';
	echo '<table id="set_table">';
	foreach ($conf as $c){
		echo '<tr><td><a href="javascript:void(null)" class="info">'.$c['key'].'<span>'.$c['description'].'</span></a></td>';
		echo '<td>';
		switch ($c['type']) {
			case 'text':
				echo '<input class="valueinput" id="'.$c['key'].'" type="text" value="'.$c['value'].'" data-valueinput-orig="'.$c['value'].'"/>';
				break;
			case 'select':
				echo '<select class="valueinput" id="'.$c['key'].'" data-valueinput-orig="'.$c['value'].'">';
					$opt = explode(',',$c['options']);
					foreach($opt as $o) {
						$selected = ($c['value'] == $o) ? ' selected ' : '';
						echo '<option value="'.$o.'"'.$selected.'>'.$o.'</option>';
					}
				echo '</select>';
				break;
		}
		echo '</td>';
		if($c['readonly'] != 'true'){
			echo '<td><input type="image" class="adv_set_default" src="images/default-option.png" data-key="'.$c['key'].'" data-default="'.$c['defaultval'].'" name="default" title="'._('Revert to Default').'"></td>';
			echo '<td class="savetd"><input type="image" class="save" src="images/accept.png" name="save" data-key="'.$c['key'].'" title="'._('Save').'"></td>';
			//echo '<td><input type="image" class="delete"  src="images/trash.png" name="delete" data-key="'.$c['key'].'" title="'._('Delete').'"></td>'; 
		}
		echo '</tr>';
	}
	echo '</table';

// Ugly, but I need to display the whole help text within the page    
echo "<br><br><br><br></div>";

?>

<?php
$view = !empty($_REQUEST['view']) ? $_REQUEST['view'] : '';
extract($_REQUEST);

switch($view){
	case "add":
		if ($extdisplay != '') {
			// load
			$row = core_dahdichandids_get($extdisplay);
			$description = $row['description'];
			$channel     = $row['channel'];
			$did         = $row['did'];
			$action = 'edit';
		}else{
			$action = 'add';
			$description = '';
			$channel = '';
			$did = '';
		}
		echo load_view(__DIR__."/views/dahdichandids/view.php", array('description' => $description, 'channel' => $channel, 'did' => $did, 'action' => $action));
	break;
	default:
		echo load_view(__DIR__."/views/dahdichandids/general.php");
	break;
}

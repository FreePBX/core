<?php /* $Id$ */
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2006-2014 Schmooze Com Inc.
//
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

$userdisplay = isset($_REQUEST['userdisplay'])?$_REQUEST['userdisplay']:'';
$action = isset($_REQUEST['action'])?$_REQUEST['action']:'';
$tech = isset($_REQUEST['tech'])?$_REQUEST['tech']:'';

$tabindex = 0;
// populate some global variables from the request string
$set_globals = array("username","password","extension_high","extension_low","deptname");
foreach ($set_globals as $var) {
	if (isset($_REQUEST[$var])) {
		$$var = stripslashes( $_REQUEST[$var] );
	}
}
$form_password_sha1 = stripslashes(isset($_REQUEST['password_sha1'])?$_REQUEST['password_sha1']:'');

//Search ALL active modules while generating admin access list
$active_modules = module_getinfo(false, MODULE_STATUS_ENABLED);

if(is_array($active_modules)){
	foreach($active_modules as $key => $module) {
		//create an array of module sections to display
		if (isset($module['items']) && is_array($module['items'])) {
			foreach($module['items'] as $itemKey => $item) {
				$listKey = (!empty($item['display']) ? $item['display'] : $itemKey);
				$item['rawname'] = $module['rawname'];
				$module_list[ $listKey ] = $item;
			}
		}
	}
}

// extensions vs device/users ... module_list setting
if (isset($amp_conf["AMPEXTENSIONS"]) && ($amp_conf["AMPEXTENSIONS"] == "deviceanduser")) {
       unset($module_list["extensions"]);
} else {
       unset($module_list["devices"]);
       unset($module_list["users"]);
}

// no more adding the APPLY Changes bar to module list because array_multisort messes up integer array keys
// $module_list['99'] = array('category' => NULL, 'name' => _("Apply Changes Bar"));

// changed from $module_name to $admin_module_name because the former is used by framework
foreach ($module_list as $key => $row) {
	$module_category[$key] = $row['category'];
	$admin_module_name[$key] = $row['name'];
}
array_multisort($module_category, SORT_ASC, $admin_module_name, SORT_ASC, $module_list);

$sections = array();
if (isset($_REQUEST["sections"])) {
	if (is_array($_REQUEST["sections"])) {
		$sections = $_REQUEST["sections"];
	} else {
		//TODO do we even need this??
		$sections = explode(";",$_REQUEST["sections"]);
	}
}

//if submitting form, update database
switch ($action) {
	case "addampuser":
		core_ampusers_add($username, $password, $extension_low, $extension_high, $deptname, $sections);
		redirect_standard();
	break;
	case "editampuser":
		// Check to make sure the hidden var is sane, and that they haven't changed the password field
		if (strlen($form_password_sha1)==40 && $password == "******") {
			// Password unchanged
			core_ampusers_del($userdisplay);
			core_ampusers_add($username, $form_password_sha1, $extension_low, $extension_high, $deptname, $sections);
		} elseif ($password != "******") {
			// Password has been changed
			core_ampusers_del($userdisplay);
			core_ampusers_add($username, $password, $extension_low, $extension_high, $deptname, $sections);
		}
		if(($userdisplay != $username) || (($username == $_SESSION['AMP_user']->username) && ($password != "******"))) {
			unset($_SESSION['AMP_user']);
		}
		redirect_standard('userdisplay');
	break;
	case "delampuser":
		core_ampusers_del($userdisplay);
		$userdisplay = ""; // go "add" screen
		redirect_standard();
	break;
}

?>
<div class="rnav">
<ul>
	<li><a <?php  echo ($userdisplay=='' ? 'class="current"':'') ?> href="config.php?display=<?php echo urlencode($display)?>"><?php echo _("Add User")?></a></li>
<?php
//get existing trunk info
$tresults = core_ampusers_list();

foreach ($tresults as $tresult) {
    echo "\t<li><a ".($userdisplay==$tresult[0] ? 'class="current"':'')." href=\"config.php?display=".urlencode($display)."&amp;userdisplay=".urlencode($tresult[0])."\">".$tresult[0]."</a></li>\n";
}
?>
</ul>
</div>

<?php

	if ($userdisplay) {
		echo "<h2>"._("Edit Administrator")."</h2>";

		$user = getAmpUser($userdisplay);

		$username = $user["username"];
		$password = "******";
		$password_sha1 = $user["password_sha1"];
		$extension_high = $user["extension_high"];
		$extension_low = $user["extension_low"];
		$deptname = $user["deptname"];
		$sections = $user["sections"];

	} else {
		// set defaults
		$username = "";
		$password = "";
		$deptname = "";

		$extension_low = "";
		$extension_high = "";

		$sections = array("*");


		echo "<h2>"._("Add Administrator")."</h2>";
	}
?>
<div class = "container-fluid" >
	<div class = "row">
<form role="form" autocomplete="off" class="fpbx-submit" name="ampuserEdit" action="config.php?display=ampusers" method="post" data-fpbx-delete="config.php?display=<?php echo urlencode($display) ?>&amp;userdisplay=<?php echo urlencode($userdisplay) ?>&amp;action=delampuser">
<input type="hidden" name="display" value="<?php echo $display?>"/>
<input type="hidden" name="userdisplay" value="<?php echo $userdisplay ?>"/>
<input type="hidden" name="action" value="<?php echo ($userdisplay ? "editampuser" : "addampuser"); ?>"/>
<input type="hidden" name="tech" value="<?php echo $tech?>"/>
<input type="hidden" name="password_sha1" value="<?php echo $password_sha1 ?>"/>
<input type="hidden" name="extension_low" value="<?php echo $extension_low ?>"/>
<input type="hidden" name="extension_high" value="<?php echo $extension_high ?>"/>
<input type="hidden" name="deptname" value="<?php echo $deptname ?>"/>
		<div class="col-xs-6 col-md-4">
			<h4><?php echo _("General Settings")?></h4>
		</div>
		<div class = "col-xs-12 col-md-8"></div>
	</div>
			
<?php
if (($amp_conf["AUTHTYPE"] != "database") && ($amp_conf["AUTHTYPE"] != "webserver")) { 
	$out = '<div class = "row">';
	$out .= '<div class = "col-xs-18 col-md-12">';
	$out .= '<p class="bg-danger">';
	$out .= '<b>'._("NOTE:").'</b>'._("Authorization Type is not set to 'database' in Advanced Setting - note that this module is not currently providing access control, and changing passwords here or adding users will have no effect unless Authorization Type is set to 'database'.") ."<br />";
	$out .= '</p>';
	$out .= '</div>';
	echo $out;
	unset($out);
}
$out = '';
$out .= '<div class = "row">';
$out .= '<div class = "form-group">';
$out .= '<label for = "username" class="col-sm-2 control-label">' . _("Username") . '  </label>';
$out .= '<div class="col-sm-10">';
$out .= '<input type = "text" name = "username" value = "' . $username . '" tabindex = "' . ++$tabindex . '"/>';
$out .= '<a href=# class="info"><span>' . _("Create a unique username for this user") . '</span></a>';
$out .= '</div>';
$out .= '</div>';
$out .= '<div class = "form-group">';
$out .= '<label for = "username" class="col-sm-2 control-label">' .  _("Password") . '  </label>';
$out .= '<div class="col-sm-10">';
$out .= '<input type = "password" name = "password" value = "' . $password . '" tabindex = "' . ++$tabindex . '"/>';
$out .= '<a href=# class="info"><span>' . _("Create a password for this new user") . '</span></a>';
$out .= '</div>';
$out .= '</div>';
$out .= '</div>';
$out .= '<div class = "row">';
$out .= '<div class="col-xs-6 col-md-4">';
$out .= '<h4>' . _("Access Restrictions") . '</h4>';
$out .= '</div>';
$out .= '<div class = "col-xs-12 col-md-8"></div>';
$out .= '</div>';
$out .= '<div class = "row">';
$out .= '<div class = "form-group">';
$out .= '<label for = "sections[]" class="col-sm-2 control-label">' . _("Admin Access") . '   </label>';
$out .= '<a href=# class="info"><span>' .  _("Select the Admin Sections this user should have access to.") . '</span></a>'; 
$out .= '<div class="col-sm-10">';
$out .= '<select miltiple name = "sections[]" tabindex = "' . ++$tabindex . '" size = "15">';
$prev_category = NULL;
foreach ($module_list as $key => $row) {
	if ($row['category'] != $prev_category) {
		if ($prev_category)
			$out .= "</optgroup>\n";
		$out .= "<optgroup label=\""._($row['category'])."\">\n";
		$prev_category = $row['category'];
	}

	$out .= "<option value=\"".$key."\"";
	if (in_array($key, $sections)) echo " SELECTED";
	$label = modgettext::_($row['name'],$row['rawname']);
	$out .= ">"._($row['name'])."</option>\n";
}
				$out .= "</optgroup>\n";

				// Apply Changes Bar
				$out .= "<option value=\"99\"";
				if (in_array("99", $sections)) $out .= " SELECTED";
				$out .= ">"._("Apply Changes Bar")."</option>\n";

				// Apply Changes Bar
				$out .= "<option value=\"999\"";
				if (in_array("999", $sections)) $out .= " SELECTED";
				$out .= ">".(($amp_conf['AMPEXTENSIONS'] == 'deviceanduser')?_("Add Device"):_("Add Extension"))."</option>\n";

				// All Sections
				$out .= "<option value=\"*\"";
				if (in_array("*", $sections)) $out .= " SELECTED";
				$out .= ">"._("ALL SECTIONS")."</option>\n";

$out .= '</select>';
$out .= '</div>';
$out .= '</form>';
$out .= '</div>';
$out .= '</div>';
$out .= '</div>';
echo $out;
?>
<script language="javascript">
<!--
$('#submit').click(function() {
	var theForm = $('.fpbx-submit').attr('name'),
		username = theForm.username.value,
		deptname = theForm.deptname.value;

	if (username == "") {
		<?php echo "alert('"._("Username must not be blank")."')"?>;
	} else if (!username.match('^[a-zA-Z][a-zA-Z0-9]+$')) {
		<?php echo "alert('"._("Username cannot start with a number, and can only contain letters and numbers")."')"?>;
	} else if (deptname == "default") {
		<?php echo "alert('"._("For security reasons, you cannot use the department name default")."')"?>;
	} else if (deptname != "" && !deptname.match('^[a-zA-Z0-9]+$')) {
		<?php echo "alert('"._("Department name cannot have a space")."')"?>;
	} else {
		return true;
	}
	return false;
});
//-->
</script>

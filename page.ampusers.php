<?php /* $Id$ */
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2006-2014 Schmooze Com Inc.
//
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

$userdisplay = isset($_REQUEST['userdisplay'])?$_REQUEST['userdisplay']:'';
$action = isset($_REQUEST['action'])?$_REQUEST['action']:'';
$tech = isset($_REQUEST['tech'])?$_REQUEST['tech']:'';
$display = isset($_REQUEST['display'])?$_REQUEST['display']:'ampusers';

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
//Generate bootnav
$bootnav = '';
if($userdisplay == ''){
	$bootnav .= '<a href="config.php?display=' . urlencode($display) . '" class="list-group-item active">' . _("Add User") .'</a>';
}else{
	$bootnav .= '<a href="config.php?display=' . urlencode($display) . '" class="list-group-item">' . _("Add User") .'</a>';
}
//get existing users
$tresults = core_ampusers_list();
foreach ($tresults as $tresult) {
	if($userdisplay == $tresult[0]){
		$bootnav .= '<a href="config.php?display=' . urlencode($display) . '&amp;userdisplay=' . urlencode($tresult[0]) . '" class="list-group-item active">' . $tresult[0] .'</a>';
	}else{
		$bootnav .= '<a href="config.php?display=' . urlencode($display) . '&amp;userdisplay=' . urlencode($tresult[0]) . '" class="list-group-item">' . $tresult[0] .'</a>';
	}
}
if ($userdisplay) {
	$title =  '<h2>' . _("Edit Administrator") . '</h2>';
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
	$title = '<h2>' . _("Add Administrator") . '</h2>';
	$username = "";
	$password = "";
	$deptname = "";

	$extension_low = "";
	$extension_high = "";

	$sections = array("*");
}
if (($amp_conf["AUTHTYPE"] != "database") && ($amp_conf["AUTHTYPE"] != "webserver")) {
	$out = '<div class = "row">';
	$out .= '<div class = "col-sm-12">';
	$out .= '<p class="bg-danger">';
	$out .= '<b>'._("NOTE:").'</b>'._("Authorization Type is not set to 'database' in Advanced Setting - note that this module is not currently providing access control, and changing passwords here or adding users will have no effect unless Authorization Type is set to 'database'.") ."<br />";
	$out .= '</p>';
	$out .= '</div>';
	$authtypewarn = $out;
	unset($out);
}
$prev_category = NULL;
foreach ($module_list as $key => $row) {
	if ($row['category'] != $prev_category) {
		if ($prev_category)
			$sectionOptions .= "</optgroup>\n";
		$sectionOptions .= "<optgroup label=\""._($row['category'])."\">\n";
		$prev_category = $row['category'];
	}

	$sectionOptions .= "<option value=\"".$key."\"";
	if (in_array($key, $sections)) echo " SELECTED";
	$label = modgettext::_($row['name'],$row['rawname']);
	$sectionOptions .= ">"._($row['name'])."</option>\n";
}
				$sectionOptions .= "</optgroup>\n";

				// Apply Changes Bar
				$sectionOptions .= "<option value=\"99\"";
				if (in_array("99", $sections)) $sectionOptions .= " SELECTED";
				$sectionOptions .= ">"._("Apply Changes Bar")."</option>\n";

				// Apply Changes Bar
				$sectionOptions .= "<option value=\"999\"";
				if (in_array("999", $sections)) $sectionOptions .= " SELECTED";
				$sectionOptions .= ">".(($amp_conf['AMPEXTENSIONS'] == 'deviceanduser')?_("Add Device"):_("Add Extension"))."</option>\n";

				// All Sections
				$sectionOptions .= "<option value=\"*\"";
				if (in_array("*", $sections)) $sectionOptions .= " SELECTED";
				$sectionOptions .= ">"._("ALL SECTIONS")."</option>\n";

?>
<div class="container-fluid">
	<?php if($authtypewarn){ echo $authtypewarn; } ?>
	<div class="row">
		<div class="col-sm-9">
			<div class="fpbx-container">
				<?php echo $title ?>
				<form role="form" autocomplete="off" class="fpbx-submit" name="ampuser" action="config.php?display=ampusers" method="post" data-fpbx-delete="config.php?display=<?php echo urlencode($display) ?>&amp;userdisplay=<?php echo urlencode($userdisplay) ?>&amp;action=delampuser">
					<input type="hidden" name="display" value="<?php echo $display?>"/>
					<input type="hidden" name="userdisplay" value="<?php echo $userdisplay ?>"/>
					<input type="hidden" name="action" value="<?php echo ($userdisplay ? "editampuser" : "addampuser"); ?>"/>
					<input type="hidden" name="tech" value="<?php echo $tech?>"/>
					<input type="hidden" name="password_sha1" value="<?php echo $password_sha1 ?>"/>
					<input type="hidden" name="extension_low" value="<?php echo $extension_low ?>"/>
					<input type="hidden" name="extension_high" value="<?php echo $extension_high ?>"/>
					<input type="hidden" name="deptname" value="<?php echo $deptname ?>"/>
					<div class="display full-border">
						<div class="section-title" data-for="general">
							<h3><i class="fa fa-minus"></i> <?php echo _("General Settings")?></h3>
						</div>
						<div class="section" data-id="general">
							<div class="element-container">
								<div class="row">
									<div class="col-md-12">
										<div class="row">
											<div class="form-group">
												<div class="col-md-3">
													<label class="control-label" for="username"><?php echo _("Username") ?></label>
													<i class="fa fa-question-circle fpbx-help-icon" data-for="username"></i>
												</div>
												<div class="col-md-9">
													<input type="text" class="form-control" id="username" name="username" value="<?php echo $username?>" tabindex="<?php ++$tabindex?>">
												</div>
											</div>
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-md-12">
										<span id="username-help" class="help-block fpbx-help-block"><?php echo _("Create a unique username for this user") ?></span>
									</div>
								</div>
							</div>
							<div class="element-container">
								<div class="row">
									<div class="col-md-12">
										<div class="row">
											<div class="form-group">
												<div class="col-md-3">
													<label class="control-label" for="password"><?php echo _("Password") ?></label>
													<i class="fa fa-question-circle fpbx-help-icon" data-for="password"></i>
												</div>
												<div class="col-md-9"><input type="password" class="form-control" id="password" name="password" value = "<?php echo $password ?>" tabindex = "<?php ++$tabindex?>">
												</div>
											</div>
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-md-12">
										<span id="password-help" class="help-block fpbx-help-block"><?php echo _("Create a password for this new user") ?></span>
									</div>
								</div>
							</div>
						</div>
						<div class="section-title" data-for="access">
							<h3><i class="fa fa-minus"></i> <?php echo _("Access Restrictions")?></h3>
						</div>
						<div class="section" data-id="access">
							<div class="element-container">
								<div class="row">
									<div class="col-md-12">
										<div class="form-group">
											<div class="col-md-3">
												<label class="control-label" for="sections"><?php echo _("Admin Access") ?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="sections"></i>
											</div>
											<div class="col-md-9">
												<select multiple class="form-control" id="sections" name="sections" tabindex = "<?php ++$tabindex?>">
													<?php echo $sectionOptions?>
												</select>
											</div>
										</div>
									</div>
								</div>
								<div class="row">
									<div class="col-md-12">
										<span id="sections-help" class="help-block fpbx-help-block"><?php echo _("Select the Admin Sections this user should have access to.") ?></span>
									</div>
								</div>
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>
		<div class="col-sm-3 hidden-xs bootnav">
			<div class="list-group">
				<?php echo $bootnav ?>
			</div>
		</div>
	</div>
</div>

<script language="javascript">
$('.fpbx-submit').submit(function() {
	var theForm = document.ampuser,
		username = theForm.username;

	if (username.value == "") {
		return warnInvalid(username, "<?php echo _("Username must not be blank")?>");
	} else if (!username.value.match('^[a-zA-Z][a-zA-Z0-9]+$')) {
		return warnInvalid(username, "<?php echo _("Username cannot start with a number, and can only contain letters and numbers")?>");
	}
	return true;
});
</script>

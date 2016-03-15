<?php /* $Id$ */
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2006-2014 Schmooze Com Inc.
//
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

$userdisplay = isset($_REQUEST['userdisplay'])?$_REQUEST['userdisplay']:'';
$action = isset($_REQUEST['action'])?$_REQUEST['action']:'';
$tech = isset($_REQUEST['tech'])?$_REQUEST['tech']:'';
$display = isset($_REQUEST['display'])?$_REQUEST['display']:'ampusers';

// populate some global variables from the request string
$set_globals = array("username","password","extension_high","extension_low");
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
		$ret = core_ampusers_add($username, $password, $extension_low, $extension_high, "", $sections);
		if(!$ret){
			echo '<div class="alert alert-danger">'.sprintf(_("The user name %s is already in use"),$username).'</div>';
		}
		echo '<script>window.location = "?display=ampusers&userdisplay='.$username.'";</script>';
		//redirect_standard();
	break;
	case "editampuser":
		// Check to make sure the hidden var is sane, and that they haven't changed the password field
		if (strlen($form_password_sha1)==40 && $password == "******") {
			// Password unchanged
			core_ampusers_del($userdisplay);
			core_ampusers_add($username, $form_password_sha1, $extension_low, $extension_high, "", $sections);
		} elseif ($password != "******") {
			// Password has been changed
			core_ampusers_del($userdisplay);
			core_ampusers_add($username, $password, $extension_low, $extension_high, "", $sections);
		}
		if(($userdisplay != $username) || (($username == $_SESSION['AMP_user']->username) && ($password != "******"))) {
			unset($_SESSION['AMP_user']);
		}
	//	redirect_standard('userdisplay');
	break;
	case "delampuser":
		core_ampusers_del($userdisplay);
		echo '<script>window.location = "?display=ampusers";</script>';
	//	redirect_standard();
	break;
}
// set defaults
$user = array(
	'sections' => isset($_REQUEST['sections'])?$_REQUEST['sections']:array("*"),
);
$title = '<h2>' . _("Add Administrator") . '</h2>';
$username = isset($_REQUEST['username'])?$_REQUEST['username']:"";
$password = isset($_REQUEST['password'])?$_REQUEST['password']:"";
$password_sha1 = isset($_REQUEST['password_sha1'])?$_REQUEST['password_sha1']:"";
$extension_low = isset($_REQUEST['extension_low'])?$_REQUEST['extension_low']:"";
$extension_high = isset($_REQUEST['extension_high'])?$_REQUEST['extension_high']:"";
$sections = $user["sections"];

if (!empty($userdisplay)) {
	$title =  '<h2>' . _("Edit Administrator") . '</h2>';
	$user = core_getAmpUser($userdisplay);
	$username = $user["username"];
	$password = "******";
	$password_sha1 = $user["password_sha1"];
	$extension_high = $user["extension_high"];
	$extension_low = $user["extension_low"];
	$sections = $user["sections"];
}


//if(FreePBX::Config()->get('AUTHTYPE') == "usermanager") {
	//echo _("User Manager is controlling these settings. Please look in User Manager or select 'database' for Authorization Type in Advanced Settings");
//} else {
if (($amp_conf["AUTHTYPE"] != "database") && ($amp_conf["AUTHTYPE"] != "usermanager") && ($amp_conf["AUTHTYPE"] != "webserver")) {
	$authtypewarn = '<div class="alert alert-danger" role="alert"><b>'._("NOTE").':</b> '._("Authorization Type is not set to 'database' in Advanced Settings - note that this module is not currently providing access control, and changing passwords here or adding users will have no effect unless Authorization Type is set to 'database'.").'</div>';
} elseif($amp_conf["AUTHTYPE"] == "usermanager") {
	$authtypewarn = '<div class="alert alert-danger" role="alert"><b>'._("NOTE").':</b> '._("Authorization Type is set to 'usermanager' in Advanced Settings - note that this module is not currently providing full access control and is only used as a failover, stop-gap until this pane is fully migrated to User Manager. You will still be able to login with the users below as long as their username does not exist in User Manager").'</div>';
}
$prev_category = NULL;

if(is_array($active_modules)){
	$dis = ($amp_conf['AMPEXTENSIONS'] == 'deviceanduser')?_("Add Device"):_("Add Extension");
	$active_modules['au']['items'][] = array('name' => _("Apply Changes Bar"), 'display' => '99');
	$active_modules['au']['items'][] = array('name' => $dis, 'display' => '999');

	foreach($active_modules as $key => $module) {
		//create an array of module sections to display
		if (isset($module['items']) && is_array($module['items'])) {
			foreach($module['items'] as $itemKey => $item) {
				if(!isset($module['rawname'])) {
					continue;
				}
				$listKey = (!empty($item['display']) ? $item['display'] : $itemKey);
				$item['rawname'] = $module['rawname'];
				$item['name'] = modgettext::_($item['name'],$module['rawname']);
				$module_list[ $listKey ] = $item;
			}
		}
	}
}
$module_list[99] = array(
	"name" => _("Apply Changes Bar")
);
$module_list[999] = array(
	"name" => (($amp_conf['AMPEXTENSIONS'] == 'deviceanduser')?_("Add Device"):_("Add Extension"))
);
$module_list['*'] = array(
	"name" => _("ALL SECTIONS")
);

uasort($module_list, function($a, $b) {
	return(strnatcmp($a['name'],$b['name']));
});

$selected = array();
$unselected = array();
foreach ($module_list as $key => $val) {
	if(!empty($user['sections']) && is_array($user['sections']) && (in_array($key,array_values($user['sections'])) || $user['sections'][0] == '*')){
		$selected[] = '<li data-id="'.$key.'" class="label label-info" style="display:inline-block">'.$val['name'].'</li>';
		$selected[] = '<input type="hidden" name="sections[]" value="'.$key.'">';
	}else{
		$unselected[] = '<li data-id="'.$key.'" class="label label-info" style="display:inline-block">'.$val['name'].'</li>';
	}
}
?>
<div class="container-fluid">
	<div class="row">
		<div class="col-sm-12">
			<?php if(isset($authtypewarn)){ echo $authtypewarn; } ?>
			<div class="fpbx-container">
				<?php echo $title ?>
				<form role="form" autocomplete="off" class="fpbx-submit" name="ampuser" id="ampuser" action="config.php?display=ampusers" method="post" data-fpbx-delete="config.php?display=<?php echo urlencode($display) ?>&amp;userdisplay=<?php echo urlencode($userdisplay) ?>&amp;action=delampuser">
					<input type="hidden" name="display" value="<?php echo $display?>"/>
					<input type="hidden" name="userdisplay" value="<?php echo $userdisplay ?>"/>
					<input type="hidden" name="action" value="<?php echo ($userdisplay ? "editampuser" : "addampuser"); ?>"/>
					<input type="hidden" name="tech" value="<?php echo $tech?>"/>
					<input type="hidden" name="password_sha1" value="<?php echo !empty($password_sha1) ? $password_sha1 : "" ?>"/>
					<input type="hidden" name="extension_low" value="<?php echo $extension_low ?>"/>
					<input type="hidden" name="extension_high" value="<?php echo $extension_high ?>"/>
					<div class="display no-border">
						<div class="section-title" data-for="general">
							<h3><i class="fa fa-minus"></i> <?php echo _("General Settings")?></h3>
						</div>
						<div class="section" data-id="general">
							<div class="element-container">
								<div class="row">
									<div class="col-md-12">
										<div class="row">
											<div class="form-group">
												<div class="col-md-3 control-label">
													<label for="username"><?php echo _("Username") ?></label>
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
												<div class="col-md-3 control-label">
													<label for="password"><?php echo _("Password") ?></label>
													<i class="fa fa-question-circle fpbx-help-icon" data-for="password"></i>
												</div>
												<div class="col-md-9"><input type="password" class="form-control clicktoedit" id="password" name="password" value = "<?php echo $password ?>">
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
											<div class="col-md-2 control-label">
												<label for="sections"><?php echo _("Admin Access") ?></label>
												<i class="fa fa-question-circle fpbx-help-icon" data-for="sections"></i>
											</div>
											<div class="col-md-10">
												<input type="hidden" name="sections[]" id="sections" value="">
												<div class="row">
													<div class="panel col-md-5">
  														<div class="panel-heading">
    														<h3 class="panel-title"><?php echo _("Selected")?></h3>
  														</div>
  														<div class="panel-body" style="height:500px;overflow-y: scroll;">
															<ol id="selected" class="well">
																<?php echo implode(PHP_EOL, $selected)?>
															</ol>
  														</div>
													</div>
													<div class="panel col-md-2">
														<div class="panel-heading"><h3 class="panel-title"><?php echo _("Action")?> </h3></div>
														<div class="panel-body" style="height:500px;">
															<button id="selectall"  type="button"><i class="fa fa-angle-double-left"></i></button><br/>
															<button id="unselectall"  type="button"><i class="fa fa-angle-double-right"></i></button>
														</div>
													</div>
													<div class="panel col-md-5">
  														<div class="panel-heading">
    														<h3 class="panel-title"><?php echo _("Not Selected")?></h3>
  														</div>
  														<div class="panel-body" style="height:500px;overflow-y: scroll;">
															<ol id="unselected" class="well">
																<?php echo implode(PHP_EOL, $unselected)?>
															</ol>
  														</div>
													</div>
												</div>
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
	</div>
</div>
<script type="text/javascript">
	//make the boxes the same height
	$(document).ready(function(){
		var $boxes = $('ol');
		var height = 0;
		$boxes.each(function () {
  		if ($(this).height() > height) {
    		height = $(this).height();
  		}
		});
		$boxes.height(height);
	});
</script>

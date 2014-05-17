<?php /* $Id$ */

if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
?>

<div class="rnav">
<?php 
// Eventually I recon the drawListMenu could be built into the new component class thus making
// the relevent page.php file unnessassary

$extens = core_users_list();
drawListMenu($extens, $skip, $type, $display, $extdisplay, _("User"));
?>
</div>

<?php
// Javascript functions could be put into the configpageinit function but I personally prefer
// to code JavaScript in a web page //
?>

<script language="javascript">
<!--

function checkBlankUserPwd() {
	msgConfirmBlankUserPwd = "<?php echo _('You have not entered a User Password.  While this is acceptable, this user will not be able to login to an AdHoc device.\n\nAre you sure you wish to leave the User Password empty?'); ?>";

	// check for password and warn if none entered
	if (isEmpty(theForm.password.value)) {
		var cnf = confirm(msgConfirmBlankUserPwd);
		if (!cnf) {
			theForm.password.focus();
			return false;
		}
	}
	return true;
}

//-->
</script>

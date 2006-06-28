<?php /* $Id$ */
//Copyright (C) 2004 Coalescent Systems Inc. (info@coalescentsystems.ca)
//
//This program is free software; you can redistribute it and/or
//modify it under the terms of the GNU General Public License
//as published by the Free Software Foundation; either version 2
//of the License, or (at your option) any later version.
//
//This program is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//GNU General Public License for more details.
?>

<div class="rnav">
<?php 
// Eventually I recon the drawListMenu could be built into the new component class thus making
// the relevent page.php file unnessassary

$extens = core_users_list();
drawListMenu($extens, $_REQUEST['skip'], $_REQUEST['display'], $_REQUEST['extdisplay'], _("User"));
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

function isVoiceMailEnabled(notused) {
	return (theForm.vm.value == "enabled");
}

//-->
</script>

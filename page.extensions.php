<?php /* $Id$ */

if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
?>

<div class="rnav">
<?php 
$extens = core_users_list();
$description = _("Extension");
drawListMenu($extens, $skip, $type, $display, $extdisplay, $description);
?>
	<br />
</div>
<?php
// If this is a popOver, we need to set it so the selection of device type does not result
// in the popover closing because config.php thinks it was the process function. Maybe
// the better way to do this would be to log an error or put some proper mechanism in place
// since this is a bit of a kludge
//
if (!empty($_REQUEST['fw_popover']) && empty($_REQUEST['tech_hardware'])) {
?>
	<script>
		$(document).ready(function(){
			$('[name="fw_popover_process"]').val('');
			$('<input>').attr({type: 'hidden', name: 'fw_popover'}).val('1').appendTo('.popover-form');
		});
	</script>
<?php
}

<?php /* $Id$ */
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
?>
<div class="container-fluid">
	<div class="row">
		<div class="col-sm-9">
			<?php
			// If this is a popOver, we need to set it so the selection of user type does not result
			// in the popover closing because config.php thinks it was the process function. Maybe
			// the better way to do this would be to log an error or put some proper mechanism in place
			// since this is a bit of a kludge
			//
			if (!empty($_REQUEST['fw_popover'])) {
			?>
				<script>
					$(document).ready(function(){
						$('[name="fw_popover_process"]').val('');
						$('<input>').attr({type: 'hidden', name: 'fw_popover'}).val('1').appendTo('.popover-form');
					});
				</script>
			<?php
			}

			$display = isset($_REQUEST['display'])?$_REQUEST['display']:null;
			$action = isset($_REQUEST['action'])?$_REQUEST['action']:null;
			$extdisplay = isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:null;

			global $currentcomponent;
			echo $currentcomponent->generateconfigpage(__DIR__."/views/users.php");
			?>
		</div>
		<div class="bootnav">
			<div class="col-sm-3 hidden-xs">
				<div class="list-group">
					<?php
						$extens = core_users_list();
						$description = _("user");
						$extdisplay = isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:null;
						?><a href="?display=users" class="list-group-item <?php echo empty($extdisplay) ? "active" : ""?>"><?php echo _("Add user")?></a><?php
						foreach($extens as $ext) {
							$active = (trim($extdisplay) == trim($ext[0])) ? 'active' : '';
							?><a href="?display=users&amp;extdisplay=<?php echo $ext[0]?>" class="list-group-item <?php echo $active?>"><?php echo $ext[1]?> &lt;<?php echo $ext[0]?>&gt;</a><?php
						}
					?>
				</div>
			</div>
		</div>
	</div>
</div>

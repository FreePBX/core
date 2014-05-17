<?php /* $Id$ */
//This file is part of FreePBX.
//
//    FreePBX is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 2 of the License, or
//    (at your option) any later version.
//
//    FreePBX is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with FreePBX.  If not, see <http://www.gnu.org/licenses/>.
//    Copyright (C) 2004 Coalescent Systems Inc. (info@coalescentsystems.ca)
//
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

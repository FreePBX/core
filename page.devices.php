<?php /* $Id$ */

if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
?>
<div class="rnav">
<?php 
$devices = core_devices_list();
$description = $_SESSION["AMP_user"]->checkSection('999') ? _("Device") : false;
drawListMenu($devices, $skip, $type, $display, $extdisplay, $description);
?>
</div>

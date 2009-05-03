<?php /* $Id$ */
// This file is part of FreePBX.
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
//
//    Copyright (C) 2004 Coalescent Systems Inc. (info@coalescentsystems.ca)
?>
<div class="rnav">
<?php 
$devices = core_devices_list();
$description = $_SESSION["AMP_user"]->checkSection('999') ? _("Device") : false;
drawListMenu($devices, $skip, $type, $display, $extdisplay, $description);
?>
</div>

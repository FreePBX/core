<?php
foreach($trunk_types as $type => $label) {
	$bootnav .= '<a href="config.php?display=trunks&amp;tech='.$type.'" class="list-group-item"><i class="fa fa-plus"></i>&nbsp;<strong>'.sprintf(_("Add %s Trunk"),$label).'</strong></a>';
}
echo $bootnav;

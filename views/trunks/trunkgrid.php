<?php 
	$trunkrows .= '<tr>';
	$trunkrows .= '<th>';
	$trunkrows .= _('Name');
	$trunkrows .= '</th>';
	$trunkrows .= '<th>';
	$trunkrows .= _('Tech');
	$trunkrows .= '</th>';
	$trunkrows .= '<th>';
	$trunkrows .= _('CallerID');	
	$trunkrows .= '</th>';
	$trunkrows .= '<th>';
	$trunkrows .= _('Status');
	$trunkrows .= '</th>';
	$trunkrows .= '<th>';
	$trunkrows .= _('Actions');
	$trunkrows .= '</th>';
	$trunkrows .= '</tr>';
foreach($trunks as $trunk) {
	$trunkrows .= '<tr id="'.$trunk['tresult']['trunkid'].'">';
	$trunkrows .= '<td>';
	$trunkrows .= $trunk['tresult']['name'];
	$trunkrows .= '</td>';
	$trunkrows .= '<td>';
	$trunkrows .= $trunk['tresult']['tech'];
	$trunkrows .= '</td>';
	$trunkrows .= '<td>';
	$trunkrows .= $trunk['tresult']['outcid'];
	$trunkrows .= '</td>';
	$trunkrows .= '<td>';
	$trunkrows .= $trunk['tresult']['disabled'] == 'on'?_("Disabled"):_("Enabled");
	$trunkrows .= '</td>';
	$trunkrows .= '<td>';
	$trunkrows .= '<a href="config.php?display=trunks&tech='.$trunk['tresult']['tech'].'&extdisplay=OUT_'.$trunk['tresult']['trunkid'].'">';
	$trunkrows .= '<i class="fa fa-edit"></i>&nbsp;&nbsp;';
	$trunkrows .= '</a>';
	$trunkrows .= '<a class="delAction" href="config.php?display=trunks&amp;extdisplay='.urlencode("OUT_".$trunk['tresult']['trunkid']).'&amp;action=deltrunk">';
	$trunkrows .= '<i class="fa fa-trash"></i>';
	$trunkrows .= '</a>';
	$trunkrows .= '</td>';
	$trunkrows .= '</tr>';
}
?>
<table class="table table-striped table-bordered">
<?php echo $trunkrows ?>
</table>

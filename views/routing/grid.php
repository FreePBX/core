<?php
$routerows ='';
foreach($routelist as $route){
	$id = $route['route_id'];
	$name = $route['name'];
	$outcid = $route['outcid'];
	//$outcid_mode = $route['outcid_mode'];
	$password = $route['password'];
	$emergency_route = $route['emergency_route'];
	$intracompany_route = $route['intracompany_route'];
	$dest = $route['dest'];
	$time_group_id = $route['time_group_id'];
	$seq = $route['seq'];
	$attributes = '';
	if($emergency_route != ''){
		$attributes .= '<i class="fa fa-lg fa-ambulance fa-border text-success" rel="Emergency Route"></i>&nbsp;';
	}else{
		$attributes .= '<i class="fa fa-lg fa-ambulance fa-border text-muted" rel="Emergency Route"></i>&nbsp;';
	}
	if($intracompany_route != ''){
		$attributes .= '<i class="fa fa-lg  fa-building fa-border text-success" rel="Intra Company Route"></i>&nbsp;';
	}else{
		$attributes .= '<i class="fa fa-lg  fa-building fa-border text-muted" rel="Intra Company Route"></i>&nbsp;';		
	}
	if($password != ''){
		$attributes .= '<i class="fa fa-lg fa-key fa-border text-success" rel="Password Set"></i>&nbsp;';
	}else{
		$attributes .= '<i class="fa fa-lg fa-key fa-border text-muted" rel="Password Set"></i>&nbsp;';		
	}
	if($time_group_id != ''){
		$attributes .= '<i class="fa fa-lg fa-clock-o fa-border text-success" rel="Time Group Assigned"></i>&nbsp;';
	}else{
		$attributes .= '<i class="fa fa-lg fa-clock-o fa-border text-muted" rel="Time Group Assigned"></i>&nbsp;';		
	}
	
$routerows .= <<<HERE
<tr id = "row$id"  data-id="$id" data-seq="$seq">
<td><i class="fa fa-arrows"></i>&nbsp $name</td>
<td>$outcid</td>
<td>$attributes</td>
<td><a href="config.php?display=routing&view=form&id=$id&extdisplay=$id">
	<i class="fa fa-edit"></i></a>&nbsp;&nbsp;
	<a href="#" id="del$id" data-id="$id" >
	<i class="fa fa-trash-o"></i></a>&nbsp;&nbsp;
</tr>
HERE;
	
}
?>
<style>
.text-grey:
</style>
<div class="table-responsive"> 
	<table class="table table-striped table-bordered" id="routes">
		<thead>
			<tr>
				<th data-field="name" data-sortable="true"><?php echo _("Name") ?></th>
				<th data-field="outboundcid" data-sortable="true"><?php echo _("Outbound CID") ?></th>
				<th data-field="attrs"><?php echo _("Attributes") ?></th>
				<th data-field="actions"><?php echo _("Actions") ?></th>
			</tr>
		</thead>
		<tbody id="outbound_routes">
			<?php echo $routerows ?>
		</tbody>
	</table>
</div>


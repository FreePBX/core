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
		$attributes .= '<i class="fa fa-lg fa-ambulance fa-border text-success" rel="Emergency Route" title="'._("Emergency Route").'"></i>&nbsp;';
	}else{
		$attributes .= '<i class="fa fa-lg fa-ambulance fa-border text-muted" rel="Emergency Route" title="'._("Emergency Route").'"></i>&nbsp;';
	}
	if($intracompany_route != ''){
		$attributes .= '<i class="fa fa-lg  fa-building fa-border text-success" rel="Intra Company Route" title="'._("Intra-Company Route").'"></i>&nbsp;';
	}else{
		$attributes .= '<i class="fa fa-lg  fa-building fa-border text-muted" rel="Intra Company Route" title="'._("Intra-Company Route").'"></i>&nbsp;';
	}
	if($password != ''){
		$attributes .= '<i class="fa fa-lg fa-key fa-border text-success" rel="Password Set" title="'._("Password Protected").'"></i>&nbsp;';
	}else{
		$attributes .= '<i class="fa fa-lg fa-key fa-border text-muted" rel="Password Set" title="'._("Password Protected").'"></i>&nbsp;';
	}
	if($time_group_id != ''){
		$attributes .= '<i class="fa fa-lg fa-clock-o fa-border text-success" rel="Time Group Assigned" title="'._("Time Group Assigned").'"></i>&nbsp;';
	}else{
		$attributes .= '<i class="fa fa-lg fa-clock-o fa-border text-muted" rel="Time Group Assigned" title="'._("Time Group Assigned").'"></i>&nbsp;';
	}
	$cidicon = '';
	if($route['outcid_mode'] == "override_extension"){
		$cidicon = '<i class="fa fa-lock" title="'._("Overrides Extension CID").'"></i>';
	}

$routerows .= <<<HERE
<tr id = "row$id"  data-id="$id" data-seq="$seq">
<td class="sort-handle"><i class="fa fa-arrows"></i>&nbsp $name</td>
<td>$outcid $cidicon</td>
<td>$attributes</td>
<td><a href="config.php?display=routing&view=form&id=$id">
	<i class="fa fa-edit"></i></a>&nbsp;&nbsp;
	<a id="del$id" data-id="$id" class="clickable delAction">
	<i class="fa fa-trash-o"></i></a>&nbsp;&nbsp;
</tr>
HERE;

}
?>
<style>
.text-grey:
</style>
<div id="toolbar-all">
<a href="config.php?display=routing&amp;view=form" class="btn btn-default"><i class="fa fa-plus"></i>&nbsp; <?php echo _("Add Outbound Route")?></a>
</div>
<div class="table-responsive">
<table id="routes"
			data-url="<?php echo $dataurl?>"
			data-cache="false"
			data-cookie="true"
			data-cookie-id-table="<must be a uniquely global name throughout all of freepbx>"
			data-toolbar="#toolbar-all"
			data-maintain-selected="true"
			data-toggle="table"
			class="table table-striped">
		<thead>
			<tr>
				<th data-field="name"><?php echo _("Name") ?></th>
				<th data-field="outboundcid"><?php echo _("Outbound CID") ?></th>
				<th data-field="attrs"><?php echo _("Attributes") ?></th>
				<th data-field="actions"><?php echo _("Actions") ?></th>
			</tr>
		</thead>
		<tbody id="outbound_routes">
			<?php echo $routerows ?>
		</tbody>
	</table>
</div>

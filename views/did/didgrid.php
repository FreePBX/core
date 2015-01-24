<?php
//directdid
$inroutes = core_did_list('directdid');
foreach ($inroutes as $key => $did_items) {
	$did_dest = explode(',',$did_items['destination']);
	if (!isset($did_dest[0]) || $did_dest[0] != 'from-did-direct') {
		unset($inroutes[$key]);
	}
}
$directdidroutes = $inroutes;
//incoming
$inroutes = core_did_list('incoming');
foreach ($inroutes as $key => $did_items) {
	$did_dest = explode(',',$did_items['destination']);
	if (!isset($did_dest[0]) || $did_dest[0] == 'from-did-direct') {
		unset($inroutes[$key]);
	}
}
$incomingroutes = $inroutes;
//unassigned
$inroutes = core_did_list('unassigned');
foreach ($inroutes as $key => $did_items) {
	if (isset($did_items['destination']) && $did_items['destination'] != '') {
		unset($inroutes[$key]);
	}
}
$unassignedroutes = $inroutes;
//All
$allroutes = core_did_list('');

foreach($allroutes as $route){
	$dest = explode(',',$route['destination']);
	$edis = urlencode($route['extension'].'/'.$route['cidnum']);
 	$displaydesc = ( (trim($route['description']) == "") ? _("None") : $route['description'] );
	$displaydid = ( (trim($route['extension']) == "") ? _("any DID") : $route['extension'] );
 	$displaycid = ( (trim($route['cidnum']) == "") ? _("any CID") : $route['cidnum'] );
	$allrrows .= '<tr><td><a href="config.php?display=did&view=form&extdisplay='.$edis.'">'.$displaydesc.'</a></td><td>'.$displaydid.'</td><td>'.$displaycid.'</td><td>'.$dest[1].'</td></tr>';
}
foreach($incomingroutes as $route){
	$dest = explode(',',$route['destination']);
	$edis = urlencode($route['extension'].'/'.$route['cidnum']);
 	$displaydesc = ( (trim($route['description']) == "") ? _("None") : $route['description'] );
	$displaydid = ( (trim($route['extension']) == "") ? _("any DID") : $route['extension'] );
 	$displaycid = ( (trim($route['cidnum']) == "") ? _("any CID") : $route['cidnum'] );
	$incrrows .= '<tr><td><a href="config.php?display=did&view=form&extdisplay='.$edis.'">'.$displaydesc.'</a></td><td>'.$displaydid.'</td><td>'.$displaycid.'</td><td>'.$dest[1].'</td></tr>';
}
foreach($unassignedroutes as $route){
	$dest = explode(',',$route['destination']);
	$edis = urlencode($route['extension'].'/'.$route['cidnum']);
 	$displaydesc = ( (trim($route['description']) == "") ? _("None") : $route['description'] );
	$displaydid = ( (trim($route['extension']) == "") ? _("any DID") : $route['extension'] );
 	$displaycid = ( (trim($route['cidnum']) == "") ? _("any CID") : $route['cidnum'] );
	$uarrows .= '<tr><td><a href="config.php?display=did&view=form&extdisplay='.$edis.'">'.$displaydesc.'</a></td><td>'.$displaydid.'</td><td>'.$displaycid.'</td><td>'.$dest[1].'</td></tr>';
}
foreach($directdidroutes as $route){
	$dest = explode(',',$route['destination']);
	$edis = urlencode($route['extension'].'/'.$route['cidnum']);
 	$displaydesc = ( (trim($route['description']) == "") ? _("None") : $route['description'] );
	$displaydid = ( (trim($route['extension']) == "") ? _("any DID") : $route['extension'] );
 	$displaycid = ( (trim($route['cidnum']) == "") ? _("any CID") : $route['cidnum'] );
	$didrrows .= '<tr><td><a href="config.php?display=did&view=form&extdisplay='.$edis.'"> '.$displaydesc.'</a></td><td>'.$displaydid.'</td><td>'.$displaycid.'</td><td>'.$dest[1].'</td></tr>';
}
?>
<ul class="nav nav-tabs" role="tablist">
	<li role="presentation" data-name="alldids" class="active">
		<a href="#alldids" aria-controls="alldids" role="tab" data-toggle="tab">
			<?php echo _("All DIDs")?>
		</a>
	</li>
	<li role="presentation" data-name="userdids" class="change-tab">
		<a href="#userdids" aria-controls="userdids" role="tab" data-toggle="tab">
			<?php echo _("User DIDs")?>
		</a>
	</li>	<li role="presentation" data-name="generaldids" class="change-tab">
		<a href="#generaldids" aria-controls="generaldids" role="tab" data-toggle="tab">
			<?php echo _("General DIDs")?>
		</a>
	</li>	<li role="presentation" data-name="unuseddids" class="change-tab">
		<a href="#unuseddids" aria-controls="unuseddids" role="tab" data-toggle="tab">
			<?php echo _("Unused DIDs")?>
		</a>
	</li>
</ul>
<div class="tab-content display">
	<div role="tabpanel" id="alldids" class="tab-pane active">
		<table class="table table-striped">
			<tr><th>Description</th><th>DID</th><th>CID</th><th>Destination</th></tr>
			<?php echo $allrrows ?>
		</table>
	</div>
	<div role="tabpanel" id="userdids" class="tab-pane">
		<table class="table table-striped">
			<tr><th>Description</th><th>DID</th><th>CID</th><th>Destination</th></tr>
			<?php echo $didrrows ?>
		</table>	
	</div>
	<div role="tabpanel" id="generaldids" class="tab-pane">
		<table class="table table-striped">
			<tr><th>Description</th><th>DID</th><th>CID</th><th>Destination</th></tr>
			<?php echo $incrrows ?>
		</table>		
	</div>
	<div role="tabpanel" id="unuseddids" class="tab-pane">
		<table class="table table-striped">
			<tr><th>Description</th><th>DID</th><th>CID</th><th>Destination</th></tr>
			<?php echo $uarrows ?>
		</table>		
	</div>
</div>
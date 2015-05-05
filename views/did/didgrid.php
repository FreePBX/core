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
$allrrows = $didrrows = $uarrows = $incrrows = '';
foreach($allroutes as $route){
	$dest = explode(',',$route['destination']);
	$edis = urlencode($route['extension'].'/'.$route['cidnum']);
 	$displaydesc = ( (trim($route['description']) == "") ? _("None") : $route['description'] );
	$displaydid = ( (trim($route['extension']) == "") ? _("any DID") : $route['extension'] );
 	$displaycid = ( (trim($route['cidnum']) == "") ? _("any CID") : $route['cidnum'] );
	$allrrows .= '<tr><td>'.$displaydesc.'</td><td>'.$displaydid.'</td><td>'.$displaycid.'</td><td>'.$dest[1].'</td><td class="actions"><a href="config.php?display=did&view=form&extdisplay='.$edis.'"><i class="fa fa-pencil-square-o"></i></a><i class="fa fa-times"></i></td></tr>';
}
foreach($incomingroutes as $route){
	$dest = explode(',',$route['destination']);
	$edis = urlencode($route['extension'].'/'.$route['cidnum']);
 	$displaydesc = ( (trim($route['description']) == "") ? _("None") : $route['description'] );
	$displaydid = ( (trim($route['extension']) == "") ? _("any DID") : $route['extension'] );
 	$displaycid = ( (trim($route['cidnum']) == "") ? _("any CID") : $route['cidnum'] );
	$incrrows .= '<tr><td>'.$displaydesc.'</td><td>'.$displaydid.'</td><td>'.$displaycid.'</td><td>'.$dest[1].'</td><td class="actions"><a href="config.php?display=did&view=form&extdisplay='.$edis.'"><i class="fa fa-pencil-square-o"></i></a><i class="fa fa-times"></i></td></tr>';
}
foreach($unassignedroutes as $route){
	$dest = explode(',',$route['destination']);
	$edis = urlencode($route['extension'].'/'.$route['cidnum']);
 	$displaydesc = ( (trim($route['description']) == "") ? _("None") : $route['description'] );
	$displaydid = ( (trim($route['extension']) == "") ? _("any DID") : $route['extension'] );
 	$displaycid = ( (trim($route['cidnum']) == "") ? _("any CID") : $route['cidnum'] );
	$uarrows .= '<tr><td>'.$displaydesc.'</td><td>'.$displaydid.'</td><td>'.$displaycid.'</td><td>'.$dest[1].'</td><td class="actions"><a href="config.php?display=did&view=form&extdisplay='.$edis.'"><i class="fa fa-pencil-square-o"></i></a><i class="fa fa-times"></i></td></tr>';
}
foreach($directdidroutes as $route){
	$dest = explode(',',$route['destination']);
	$edis = urlencode($route['extension'].'/'.$route['cidnum']);
 	$displaydesc = ( (trim($route['description']) == "") ? _("None") : $route['description'] );
	$displaydid = ( (trim($route['extension']) == "") ? _("any DID") : $route['extension'] );
 	$displaycid = ( (trim($route['cidnum']) == "") ? _("any CID") : $route['cidnum'] );
	$didrrows .= '<tr><td>'.$displaydesc.'</td><td>'.$displaydid.'</td><td>'.$displaycid.'</td><td>'.$dest[1].'</td><td class="actions"><a href="config.php?display=did&view=form&extdisplay='.$edis.'"><i class="fa fa-pencil-square-o"></i></a><i class="fa fa-times"></i></td></tr>';
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
		<div id="toolbar-all">
			<button id="remove-all" class="btn btn-danger btn-remove" data-type="all" disabled data-section="all">
				<i class="glyphicon glyphicon-remove"></i> <span><?php echo _('Delete')?></span>
			</button>
		</div>
		<table data-toolbar="#toolbar-all" data-toggle="table" data-pagination="true" data-search="true" class="table table-striped" id="table-all">
			<thead>
				<tr>
					<th data-sortable="true"><?php echo _('Description')?></th>
					<th data-sortable="true"><?php echo _('DID')?></th>
					<th data-sortable="true"><?php echo _('CID')?></th>
					<th data-sortable="true"><?php echo _('Destination')?></th>
					<th><?php echo _('Actions')?></th>
				</tr>
			</thead>
			<tbody>
				<?php echo $allrrows ?>
			</tbody>
		</table>
	</div>
	<div role="tabpanel" id="userdids" class="tab-pane">
		<div id="toolbar-user">
			<button id="remove-user" class="btn btn-danger btn-remove" data-type="user" disabled data-section="user">
				<i class="glyphicon glyphicon-remove"></i> <span><?php echo _('Delete')?></span>
			</button>
		</div>
		<table data-toolbar="#toolbar-user" data-toggle="table" data-pagination="true" data-search="true" class="table table-striped" id="table-all">
			<thead>
				<tr>
					<th data-sortable="true"><?php echo _('Description')?></th>
					<th data-sortable="true"><?php echo _('DID')?></th>
					<th data-sortable="true"><?php echo _('CID')?></th>
					<th data-sortable="true"><?php echo _('Destination')?></th>
					<th><?php echo _('Actions')?></th>
				</tr>
			</thead>
			<?php echo $didrrows ?>
		</table>
	</div>
	<div role="tabpanel" id="generaldids" class="tab-pane">
		<div id="toolbar-general">
			<button id="remove-general" class="btn btn-danger btn-remove" data-type="general" disabled data-section="general">
				<i class="glyphicon glyphicon-remove"></i> <span><?php echo _('Delete')?></span>
			</button>
		</div>
		<table data-toolbar="#toolbar-general" data-toggle="table" data-pagination="true" data-search="true" class="table table-striped" id="table-all">
			<thead>
				<tr>
					<th data-sortable="true"><?php echo _('Description')?></th>
					<th data-sortable="true"><?php echo _('DID')?></th>
					<th data-sortable="true"><?php echo _('CID')?></th>
					<th data-sortable="true"><?php echo _('Destination')?></th>
					<th><?php echo _('Actions')?></th>
				</tr>
			</thead>
			<?php echo $incrrows ?>
		</table>
	</div>
	<div role="tabpanel" id="unuseddids" class="tab-pane">
		<div id="toolbar-unused">
			<button id="remove-unused" class="btn btn-danger btn-remove" data-type="unused" disabled data-section="unused">
				<i class="glyphicon glyphicon-remove"></i> <span><?php echo _('Delete')?></span>
			</button>
		</div>
		<table data-toolbar="#toolbar-unused" data-toggle="table" data-pagination="true" data-search="true" class="table table-striped" id="table-all">
			<thead>
				<tr>
					<th data-sortable="true"><?php echo _('Description')?></th>
					<th data-sortable="true"><?php echo _('DID')?></th>
					<th data-sortable="true"><?php echo _('CID')?></th>
					<th data-sortable="true"><?php echo _('Destination')?></th>
					<th><?php echo _('Actions')?></th>
				</tr>
			</thead>
			<?php echo $uarrows ?>
		</table>
	</div>
</div>

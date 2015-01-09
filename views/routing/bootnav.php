<a href="config.php?display=routing" class="list-group-item <?php echo $_REQUEST['view'] == '' ?'hidden':''?>"><i class="fa fa-list">&nbsp; List Routes</i></a>
<a href="config.php?display=routing&view=form" class="list-group-item <?php echo $_REQUEST['view'] == 'form' ?'hidden':''?>"><i class="fa fa-plus">&nbsp; Add Outbound Route</i></a>
<a href="#" class="list-group-item hidden" data-toggle="modal"	data-target="#dpwizard" id="wizmenu"><i class="fa fa-magic">&nbsp; <?php echo _("Dial patterns wizards")?></i></a>

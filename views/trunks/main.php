<h2><?php echo _("Add a Trunk")?></h2>
<?php foreach($trunk_types as $type => $label) { ?>
	<span>
		<img width="16" height="16" border="0" title="<?php echo sprintf(_("Add %s Trunk"),$label)?>" alt="" src="images/core_add.png"/>
	</span>
	<a href="config.php?display=trunks&amp;tech=<?php echo $type?>"><?php echo sprintf(_("Add %s Trunk"),$label)?></a>
	<br />
	<br />
<?php } ?>
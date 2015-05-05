<div class="container-fluid">
	<h1><?php echo _('Trunks')?></h1>
	<div class="well well-info">
		<?php echo _('This page is used to manage various system trunks')?>
	</div>
	<div class = "display no-border">
		<div class="row">
			<div class="col-sm-9">
				<div class="fpbx-container">
					<div class="display no-border">
						<?php echo load_view(__DIR__.'/trunkgrid.php', array('trunks' => $trunks))?>
					</div>
				</div>
			</div>
			<div class="col-sm-3 bootnav">
				<div class="list-group">
					<?php echo load_view(__DIR__.'/bootnav.php', array('trunk_types' => $trunk_types))?>
				</div>
			</div>
		</div>
	</div>
</div>

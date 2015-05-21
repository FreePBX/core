<div class="container-fluid">
	<h1><?php echo _('Trunks')?></h1>
	<div class="well well-info">
		<?php echo _('This page is used to manage various system trunks')?>
	</div>
	<div class = "display no-border">
		<div class="row">
			<div class="col-sm-12">
				<div class="fpbx-container">
					<div class="display no-border">
						<?php echo load_view(__DIR__.'/trunkgrid.php', array('trunks' => $trunks, 'trunk_types' => $trunk_types))?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

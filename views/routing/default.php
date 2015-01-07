<div class="container-fluid">
	<h1><?php echo _('Outbound Routes')?></h1>
	<div class="well well-info">
		<?php echo _('This page is used to manage your outbound routing.')?>
	</div>
	<div class = "display full-border">
		<div class="row">
			<div class="col-sm-9">
				<div class="fpbx-container">
					<div class="display full-border">
						<?php echo load_view(__DIR__.'/grid.php', array('routelist' => $routelist));?>
					</div>
				</div>
			</div>
			<div class="col-sm-3 hidden-xs bootnav">
				<div class="list-group">
					<?php echo load_view(__DIR__.'/bootnav.php');?>
				</div>
			</div>
		</div>
	</div>
</div>
<script src="modules/core/assets/js/routing/routing.js">

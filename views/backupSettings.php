<!--Restore Advanced Settings-->
<div class="element-container">
	<div class="row">
		<div class="form-group">
			<div class="col-md-6">
				<label class="control-label" for="core_disabletrunks"><?php echo _("Disable Trunks on Restore?") ?></label>
			</div>
			<div class="col-md-6">
				<span class="radioset">
					<?php $core_disabletrunks=isset($core_disabletrunks)?$core_disabletrunks:'no'?>
					<input type="radio" name="core_disabletrunks" id="core_disabletrunksyes" value="yes" <?php echo ($core_disabletrunks == "yes"?"CHECKED":"") ?>>
					<label for="core_disabletrunksyes"><?php echo _("Yes");?></label>
					<input type="radio" name="core_disabletrunks" id="core_disabletrunksno" value="no" <?php echo ($core_disabletrunks == "yes"?"":"CHECKED") ?>>
					<label for="core_disabletrunksno"><?php echo _("No");?></label>
				</span>
			</div>
		</div>
	</div>
</div>
<!--END Restore Advanced Settings-->

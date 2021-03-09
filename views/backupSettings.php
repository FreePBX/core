<script type="text/javascript">
    $(document).ready(function () {
    	$("input[name='core_disabletrunks']").click(function(){
    	    let obj = {};
    	    obj = {
    	            'core': $("#modulesetting_core").serializeArray()
    	    }
            $('#backup_items').val(JSON.stringify(processItems(undefined, obj)));
        });
	    var dbValue = <?php echo json_encode($core_disabletrunks) ?>;
	    var items =  $("input[name='backup_items']").val();
        var mod = JSON.parse(items).find(item => item.modulename === "core")
        var toggle = mod && mod.settings.length > 0 ? mod.settings[0].value : dbValue;
        (toggle && toggle === "yes") ? $('#core_disabletrunksyes').attr('checked', true) : $('#core_disabletrunksno').attr('checked', true);
	});
</script>

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

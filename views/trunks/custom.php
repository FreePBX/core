<!--CUSTOM-->
<input type="hidden" name="usercontext" value="notneeded"/>	
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="channelid"><?php echo _("Custom Dial String") ?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="channelid"></i>
					</div>
					<div class="col-md-9">
						<input type="text" class="form-control" id="channelid" name="channelid" value="<?php echo htmlspecialchars($channelid) ?>" tabindex="<?php echo ++$tabindex;?>">
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="channelid-help" class="help-block fpbx-help-block"><?php echo _("Define the custom Dial String.  Include the token")?> $OUTNUM$ <?php echo _("wherever the number to dial should go.<br><br><b>examples:</b><br>")?>CAPI/XXXXXXXX/$OUTNUM$<br>H323/$OUTNUM$@XX.XX.XX.XX<br>OH323/$OUTNUM$@XX.XX.XX.XX:XXXX<br>vpb/1-1/$OUTNUM$</span>
		</div>
	</div>
</div>

<!--END CUSTOM-->

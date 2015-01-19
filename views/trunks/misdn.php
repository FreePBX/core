<?php 
$gps = misdn_groups_ports(); 
foreach($gps as $gp) { 
	$misdnopts .= "<option value='$gp'"; 
	if ($gp == $channelid){ 
		$misdnopts .= ' selected="1"';
	} 
	$misdnopts .= '>'.$gp.'</option>\n'; 
}
?> 

<!--mISDN-->
<input type="hidden" name="usercontext" value="notneeded"/>	
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="channelid"><?php echo _("mISDN Group/Port") ?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="channelid"></i>
					</div>
					<div class="col-md-9">
						<select name="channelid" id="channelid" class="form-control">
							<?php echo $misdnopts ?>
						</select>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="channelid-help" class="help-block fpbx-help-block"><?php echo _("mISDN Group/Port")?><span><br><?php echo _("mISDN channels are referenced either by a group name or channel number (use <i>mISDN Port Groups</i> to configure).")?></span>
		</div>
	</div>
</div>
<!--END mISDN-->
<?php
$tresults = music_list();
$cur = (isset($mohsilence) && $mohsilence != "" ? $mohsilence : 'default');
$optionhtml = '';
 if (isset($tresults[0])) {
	foreach ($tresults as $tresult) {
		$ttext = $tresult;
		if($tresult == 'none') $ttext = _("none");
		if($tresult == 'default') $ttext = _("default");
		$optionhtml .= '<option value="'.$tresult.'"'.($tresult == $cur ? ' SELECTED' : '').'>'.$ttext."</option>\n";
	}
}
?>

<!--MOH-->
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="mohsilence"><?php echo _("Music On Hold?") ?></label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="mohsilence"></i>
					</div>
					<div class="col-md-9">
						<select name="mohsilence" class="form-control">
							<?php echo $optionhtml ?>
						</select>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="mohsilence-help" class="help-block fpbx-help-block"><?php echo _("You can choose which music category to use. For example, choose a type appropriate for a destination country which may have announcements in the appropriate language.")?></span>
		</div>
	</div>
</div>
<!--MOH-->

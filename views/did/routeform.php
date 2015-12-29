<?php
if (function_exists('music_list')) {
	$tresults = music_list();
	$cur = (isset($mohclass) && $mohclass != "" ? $mohclass : 'default');
	if (isset($tresults[0])) {
		$mohopts = "";
		foreach ($tresults as $tresult) {
			($tresult == 'none' ? $ttext = _("No Music") : $ttext = $tresult);
			($tresult == 'default' ? $ttext = _("Default") : $ttext = $tresult);
			$mohopts .= '<option value="'.$tresult.'"'.($tresult == $cur ? ' SELECTED' : '').'>'._($ttext)."</option>\n";
		}
	}
	$mohlabel = _("Music On Hold");
	$mohhelp = _("Set the MoH class that will be used for calls that come in on this route. For example, choose a type appropriate for routes coming in from a country which may have announcements in their language.");
	$mohhtml = <<<HERE
<!--Music On Hold-->
<div class="element-container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div class="form-group">
					<div class="col-md-3">
						<label class="control-label" for="mohclass">$mohlabel</label>
						<i class="fa fa-question-circle fpbx-help-icon" data-for="mohclass"></i>
					</div>
					<div class="col-md-9">
						<select name="mohclass" class="form-control">
							$mohopts
						</select>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-md-12">
			<span id="mohclass-help" class="help-block fpbx-help-block">$mohhelp</span>
		</div>
	</div>
</div>
<!--END Music On Hold-->
HERE;
}
echo $heading;
echo isset($userlink)?$userlink:'';
$hooks = \FreePBX::Core()->hookTabs($_REQUEST['display']);
?>

<form name="editGRP" class="fpbx-submit" action="config.php?display=did" method="post"  data-fpbx-delete="?display=did&amp;extdisplay=<?php echo $extdisplay; ?>&amp;action=delIncoming&amp;didfilter=<?php echo $didfilter; ?>&amp;rnavsort=<?php echo $rnavsort; ?>">
	<input type="hidden" name="display" value="did">
	<input type="hidden" name="action" value="<?php echo ($extdisplay ? 'edtIncoming' : 'addIncoming') ?>">
	<input type="hidden" name="extdisplay" value="<?php echo $extdisplay ?>">
	<input type="hidden" name="old_extension" value="<?php echo $extension?>">
	<input type="hidden" name="old_cidnum" value="<?php echo $cidnum?>">
	<ul class="nav nav-tabs" role="tablist">
		<li role="presentation" data-name="didgeneral" class="active">
			<a href="#didgeneral" aria-controls="didgeneral" role="tab" data-toggle="tab">
				<?php echo _("General")?>
			</a>
		</li>
		<li role="presentation" data-name="didadvanced" class="change-tab">
			<a href="#didadvanced" aria-controls="didadvanced" role="tab" data-toggle="tab">
				<?php echo _("Advanced")?>
			</a>
		</li>
		<li role="presentation" data-name="didprivacy" class="change-tab">
			<a href="#didprivacy" aria-controls="didprivacy" role="tab" data-toggle="tab">
				<?php echo _("Privacy")?>
			</a>
		</li>
		<?php echo $hooks['hookTabs']?>
		<li role="presentation" data-name="didother" class="change-tab <?php echo (empty($hooks['oldHooks'])?'hidden':'')?>">
			<a href="#didother" aria-controls="didother" role="tab" data-toggle="tab">
				<?php echo _("Other")?>
			</a>
		</li>
	</ul>
	<div class="tab-content display">
		<div role="tabpanel" id="didgeneral" class="tab-pane active">
			<!--Description-->
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="description"><?php echo _("Description") ?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="description"></i>
								</div>
								<div class="col-md-9">
									<input type="text" class="form-control" id="description" name="description" value="<?php echo isset($description)?$description:''; ?>" ">
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="description-help" class="help-block fpbx-help-block"><?php echo _("Provide a meaningful description of what this incoming route is")?></span>
					</div>
				</div>
			</div>
			<!--END Description-->
			<!--DID Number-->
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="extension"><?php echo _("DID Number") ?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="extension"></i>
								</div>
								<div class="col-md-9">
									<input type="text" class="form-control" id="extension" name="extension" data-no-duplicate-check="true" value="<?php echo isset($extension)?$extension:''; ?>" placeholder="<?php echo _("ANY")?>">
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="extension-help" class="help-block fpbx-help-block"><?php echo _("Define the expected DID Number if your trunk passes DID on incoming calls. <br><br>Leave this blank to match calls with any or no DID info.<br><br>You can also use a pattern match (eg _2[345]X) to match a range of numbers")?></span>
					</div>
				</div>
			</div>
			<!--END DID Number-->
			<!--CallerID Number-->
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="cidnum"><?php echo _("CallerID Number") ?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="cidnum"></i>
								</div>
								<div class="col-md-9">
									<input type="text" class="form-control" id="cidnum" name="cidnum" value="<?php echo isset($cidnum)?$cidnum:'' ?>" placeholder="<?php echo _("ANY")?>">
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="cidnum-help" class="help-block fpbx-help-block"><?php echo _("Define the CallerID Number to be matched on incoming calls.<br><br>Leave this field blank to match any or no CID info. In addition to standard dial sequences, you can also put Private, Blocked, Unknown, Restricted, Anonymous and Unavailable in order to catch these special cases if the Telco transmits them.")?></span>
					</div>
				</div>
			</div>
			<!--END CallerID Number-->
			<!--CID Priority Route-->
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="pricidw"><?php echo _("CID Priority Route") ?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="pricidw"></i>
								</div>
								<div class="col-md-9 radioset">
									<input type="radio" name="pricid" id="pricidyes" value="CHECKED" <?php echo ($pricid == "CHECKED"?"CHECKED":"") ?>>
									<label for="pricidyes"><?php echo _("Yes");?></label>
									<input type="radio" name="pricid" id="pricidno" value="" <?php echo ($pricid == "CHECKED"?"":"CHECKED") ?>>
									<label for="pricidno"><?php echo _("No");?></label>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="pricidw-help" class="help-block fpbx-help-block"><?php echo _("This effects CID ONLY routes where no DID is specified. If checked, calls with this CID will be routed to this route, even if there is a route to the DID that was called. Normal behavior is for the DID route to take the calls. If there is a specific DID/CID route for this CID, that route will still take the call when that DID is called.")?></span>
					</div>
				</div>
			</div>
			<!--END CID Priority Route-->
			<!--Alert Info-->
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="alertinfo"><?php echo _("Alert Info") ?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="alertinfo"></i>
								</div>
								<div class="col-md-9">
									<?php echo FreePBX::View()->alertInfoDrawSelect("alertinfo",$alertinfo);?>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="alertinfo-help" class="help-block fpbx-help-block"><?php echo _("ALERT_INFO can be used for distinctive ring with SIP devices.")?></span>
					</div>
				</div>
			</div>
			<!--END Alert Info-->
			<!--CID name prefix-->
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="grppre"><?php echo _("CID name prefix") ?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="grppre"></i>
								</div>
								<div class="col-md-9">
									<input type="text" class="form-control" id="grppre" name="grppre" value="<?php echo $grppre ?>" ">
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="grppre-help" class="help-block fpbx-help-block"><?php echo _('You can optionally prefix the CallerID name. ie: If you prefix with "Sales:", a call from John Doe would display as "Sales:John Doe" on the extensions that ring.')?></span>
					</div>
				</div>
			</div>
			<!--END CID name prefix-->
			<?php echo $mohhtml ?>
			<!--Set Destination-->
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="goto0"><?php echo _("Set Destination") ?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="goto0"></i>
								</div>
								<div class="col-md-9">
									<?php echo drawselects(isset($destination)?$destination:null,0); ?>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="goto0-help" class="help-block fpbx-help-block"><?php echo _("Destination for route")?></span>
					</div>
				</div>
			</div>
			<!--END Set Destination-->
		</div>
		<div role="tabpanel" id="didadvanced" class="tab-pane">
			<!--Signal RINGING-->
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="ringing"><?php echo _("Signal RINGING") ?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="ringing"></i>
								</div>
								<div class="col-md-9 radioset">
									<input type="radio" name="ringing" id="ringingwyes" value="CHECKED" <?php echo ($ringing == "CHECKED"?"CHECKED":"") ?>>
									<label for="ringingwyes"><?php echo _("Yes");?></label>
									<input type="radio" name="ringing" id="ringingwno" value="" <?php echo ($ringing == "CHECKED"?"":"CHECKED") ?>>
									<label for="ringingwno"><?php echo _("No");?></label>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="ringing-help" class="help-block fpbx-help-block"><?php echo _("Some devices or providers require RINGING to be sent before ANSWER. You'll notice this happening if you can send calls directly to a phone, but if you send it to an IVR, it won't connect the call.")?></span>
					</div>
				</div>
			</div>
			<!--END Signal RINGING-->
			<!--Reject Reverse Charges-->
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="reversalw"><?php echo _("Reject Reverse Charges") ?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="reversalw"></i>
								</div>
								<div class="col-md-9 radioset">
									<input type="radio" name="reversal" id="reversalyes" value="CHECKED" <?php echo ($reversal == "CHECKED"?"CHECKED":"") ?>>
									<label for="reversalyes"><?php echo _("Yes");?></label>
									<input type="radio" name="reversal" id="reversalno" value="" <?php echo ($reversal == "CHECKED"?"":"CHECKED") ?>>
									<label for="reversalno"><?php echo _("No");?></label>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="reversalw-help" class="help-block fpbx-help-block"><?php echo _("On PRI channels the carrier will send a signal if the caller indicates a billing reversal. When checked this route will reject calls that indicate a billing reversal if supported")?></span>
					</div>
				</div>
			</div>
			<!--END Reject Reverse Charges-->
			<!--Pause Before Answer-->
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="delay_answer"><?php echo _("Pause Before Answer") ?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="delay_answer"></i>
								</div>
								<div class="col-md-9">
									<input type="number" class="form-control" id="delay_answer" name="delay_answer" value="<?php echo ($delay_answer != '0')?$delay_answer:'' ?>" ">
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="delay_answer-help" class="help-block fpbx-help-block"><?php echo _("An optional delay to wait before processing this route. Setting this value will delay the channel from answering the call. This may be handy if external fax equipment or security systems are installed in parallel and you would like them to be able to seize the line.")?></span>
					</div>
				</div>
			</div>
			<!--END Pause Before Answer-->

		</div>
		<div role="tabpanel" id="didprivacy" class="tab-pane">
			<!--Privacy Manager-->
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="privacyman"><?php echo _("Privacy Manager") ?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="privacyman"></i>
								</div>
								<div class="col-md-9 radioset">
									<input type="radio" name="privacyman" id="privacymanYES" value="1" <?php  echo ($privacyman === '1' ? 'CHECKED' : '')?>>
									<label for="privacymanYES"><?php echo _("Yes")?></label>
									<input type="radio" name="privacyman" id="privacymanNO" value="0" <?php  echo ($privacyman === '1' ? '' : 'CHECKED')?>>
									<label for="privacymanNO"><?php echo _("No")?></label>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="privacyman-help" class="help-block fpbx-help-block"><?php echo _("If no CallerID has been received, Privacy Manager will ask the caller to enter their phone number. If an user/extension has Call Screening enabled, the incoming caller will be be prompted to say their name when the call reaches the user/extension.")?></span>
					</div>
				</div>
			</div>
			<!--END Privacy Manager-->
			<!--Max attempts-->
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="pmmaxretries"><?php echo _("Max attempts") ?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="pmmaxretries"></i>
								</div>
								<div class="col-md-9">
									<?php if(!isset($pmmaxretries)||$pmmaxretries==''){$pmmaxretries=3;} ?>
									<input type="number" min="1" max="11" class="form-control" id="pmmaxretries" name="pmmaxretries" value="<?php echo $pmmaxretries ?>" <?php  echo ($privacyman == '0' ? 'disabled' : '')?>>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="pmmaxretries-help" class="help-block fpbx-help-block"><?php echo _("Number of attempts the caller has to enter a valid CallerID")?></span>
					</div>
				</div>
			</div>
			<!--END Max attempts-->
			<!--Min Length-->
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="pmminlength"><?php echo _("Min Length") ?></label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="pmminlength"></i>
								</div>
								<div class="col-md-9">
									<?php if( !isset($pmminlength) || $pmminlength=='' ){ $pmminlength = 10; }?>
									<input type="number" min="1" max="16" class="form-control" id="pmminlength" name="pmminlength" value="<?php echo $pmminlength ?>" <?php  echo ($privacyman == '0' ? 'disabled' : '')?>>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="pmminlength-help" class="help-block fpbx-help-block"><?php echo _("Minimum amount of digits CallerID needs to contain in order to be considered valid")?></span>
					</div>
				</div>
			</div>
			<!--END Min Length-->

		</div>
		<?php echo $hooks['hookContent']?>
		<div role="tabpanel" id="didother" class="tab-pane">
			<div>
				<!--HOOKS-->
				<?php
				$module_hook = moduleHook::create();
				echo $hooks['oldHooks'];
				?>
				<!--END HOOKS-->
			</div>
		</div>
	</div>
</form>
<script type="text/javascript">
$("[name='privacyman']").change(function(){
	if($(this).val() == "1"){
		$("#pmmaxretries").attr('disabled', false);
		$("#pmminlength").attr('disabled', false);
	}else{
		$("#pmmaxretries").attr('disabled', true);
		$("#pmminlength").attr('disabled', true);

	}
});
</script>

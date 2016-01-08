							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<?php
$pp_tit = _("prepend");
$pf_tit = _("prefix");
$mp_tit = _("match pattern");
?>
	<!-- Dialplan Wizard-->
	<div class="modal fade" id="dploading">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-body">
					<div class="row">
						<div class="col-md-3">
							<i class="fa fa-spin fa-spinner fa-3x"></i>
						</div>
						<div class="col-md-8">
							<h2><?php echo _("LOADING ROUTES")?></h2>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="modal fade" id="dpwizard">
		<div class="modal-dialog modal-lg">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
					<h4 class="modal-title"><?php echo _("Dial patterns wizards")?></h4>
				</div>
				<div class="modal-body">
					<div class="well well-info">
						<p><?php echo _("These options provide a quick way to add outbound dialing rules. Follow the prompts for each.")?></p>
						<p></p>
						<p><strong><?php echo _("Download local prefixes")?></strong> <?php echo _("This looks up your local number on www.localcallingguide.com (NA-only), and sets up so you can dial either 7, 10 or 11 digits (5551234, 6135551234, 16135551234) as selected below to access this route. Please note this requires internet access and may take some time")?></p>
						<p><strong><?php echo _("Generate Buttons")?></strong><?php echo _("You may choose 7,10,11 digit patterns as your provider allows. If you do not choose 'Download' this will add a generic 7,10 or ll digit pattern")?></p>
						<p><strong><?php echo _("Generic Patterns")?></strong><?php echo _("You may select to allow toll free calls such as 800,877 etc as well as Directory assistance, International dialing and long distance")?></p>
					</div>

					<label for="lpwnpa">NPA</label>
					<input type="tel" id='lpwnpa' class="form-control">
					<label for="lpwnxx">NXX</label>
					<input type="tel" id='lpwnxx' class="form-control">
					<div class = "form-group radioset">
					<input type="checkbox" id="fwdownload">
					<label for="fwdownload"><?php echo _("Download Local Patterns");?></label>
					</div>
					<div class = "form-group radioset">
					<input type="checkbox" id="fw7" checked>
					<label for="fw7"><?php echo _("7 Digit Patterns")?></label>
					<input type="checkbox" id="fw10" checked>
					<label for="fw10"><?php echo _("10 Digit Patterns")?></label>
					<input type="checkbox" id="fw11">
					<label for="fw11"><?php echo _("11 Digit Patterns")?></label>
					</div>
					<div class = "form-group radioset">
					<input type="checkbox" id="fwtollfree">
					<label for="fwtollfree"><?php echo _("US Toll Free Patterns")?></label>
					<input type="checkbox" id="fwinfo">
					<label for="fwinfo"><?php echo _("US Information")?></label>
					<input type="checkbox" id="fwemergency" checked>
					<label for="fwemergency"><?php echo _("US Emergency")?></label>
					<input type="checkbox" id="fwint">
					<label for="fwint"><?php echo _("US International")?></label>
					<input type="checkbox" id="fwld">
					<label for="fwld"><?php echo _("Long Distance")?></label>
					</div>
					<div id ="lpresults"></div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal"><?php echo _("Close")?></button>
					<button type="button" class="btn btn-primary" id="trunkgetlocalprefixes"><?php echo _("Generate Routes")?></button>
				</div>
			</div><!-- /.modal-content -->
		</div><!-- /.modal-dialog -->
	</div><!-- /.modal -->
	<!-- END Dialplan Wizard-->
<script>
var tech = '<?php echo !empty($tech) ? strtolower($tech) : strtolower($_REQUEST['tech']) ?>';
var msgInvalidOutboundCID = "<?php echo _('Invalid Outbound CallerID'); ?>";
var msgInvalidMaxChans = "<?php echo _('Invalid Maximum Channels'); ?>";
var msgInvalidDialRules = "<?php echo _('Invalid Dial Rules'); ?>";
var msgInvalidOutboundDialPrefix = "<?php echo _('The Outbound Dial Prefix contains non-standard characters. If these are intentional the press OK to continue.'); ?>";
var msgInvalidTrunkName = "<?php echo _('Invalid Trunk Name entered'); ?>";
var msgInvalidChannelName = "<?php echo _('Invalid Custom Dial String entered'); ?>";
var msgInvalidTrunkAndUserSame = "<?php echo _('Trunk Name and User Context cannot be set to the same value'); ?>";
var msgConfirmBlankContext = "<?php echo _('User Context was left blank and User Details will not be saved!'); ?>";
var msgCIDValueRequired = "<?php echo _('You must define an Outbound CallerID when Choosing this CID Options value'); ?>";
var msgCIDValueEmpty = "<?php echo _('It is highly recommended that you define an Outbound CallerID on all trunks, undefined behavior can result when nothing is specified. The CID Options can control when this CID is used. Do you still want to continue?'); ?>";
var msgInvalidServerURI = "<?php echo _('You Must define a Server URI')?>";
var msgInvalidClientURI = "<?php echo _('You must defined a Client URI')?>";
var msgInvalidAORContact = "<?php echo _('You must define a(n) AOR Contact')?>";
var msgInvalidSIPServer = "<?php echo _('You must define a SIP Server')?>";
var msgInvalidSIPServerPort = "<?php echo _('You must define a SIP Port')?>";
</script>

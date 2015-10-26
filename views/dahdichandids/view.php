<?php
$helptext = _("DAHDI Channel DIDs allow you to assign a DID to specific DAHDI Channels. You can supply the same DID to multiple channels. This would be a common scenario if you have multiple POTS lines that are on a hunt group from your provider. You MUST assign the channel's context to from-analog for these settings to have effect. It will be a line that looks like:<br /><br />context = from-analog<br /><br />in your chan_dahdi.conf configuration effecting the specified channel(s). Once you have assigned DIDs you can use standard Inbound Routes with the specified DIDs to route your calls.");
?>

<div class="container-fluid">
	<h1><?php echo _('DAHDI Channel DIDs')?></h1>
	<?php echo show_help($helptext, _('Module Information'),true)?>
	<div class = "display full-border">
		<div class="row">
			<div class="col-sm-12">
				<div class="fpbx-container">
					<div class="display full-border">
						<?php echo load_view(__DIR__.'/didForm.php', array('did'=> $did, 'description' => $description, 'channel' => $channel, 'action'	=>	$action))?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

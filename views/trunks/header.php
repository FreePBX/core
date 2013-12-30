<div class="rnav">
	<ul>
		<li><a <?php  echo ($extdisplay=='' ? 'class="current"':'') ?> href="config.php?display=<?php echo urlencode($display)?>"><?php echo _("Add Trunk")?></a></li>
	<?php foreach($trunks as $trunk) { ?>
		<li><a class="<?php echo ($trunknum==$trunk['tresult']['trunkid']) ? 'current':''?>" href="config.php?display=<?php echo urlencode($display)?>&amp;extdisplay=OUT_<?php echo urlencode($trunk['tresult']['trunkid'])?>" title="<?php echo urlencode($trunk['tresult']['name'])?>" style="background:<?php echo $trunk['background']?>"><?php echo $trunk['label']?></a></li>
	<?php } ?>
	</ul>
</div>
<div class="container-fluid">
 <?php if((isset($_REQUEST['fw_popover']) && empty($_REQUEST['tech']) )) { ?>
                <div class="row">
                        <?php foreach(FreePBX::Core()->listTrunkTypes() as  $type => $label) { ?>
                                <a class="btn btn-default" href="?fw_popover=1&amp;display=trunks&amp;tech=<?php echo $type?>" ><i class="fa fa-plus"></i> <strong><?php echo sprintf(_('Add New %s Trunk'),$label)?></strong></a></br>
                        <?php } ?>
                </div>
<?php }else{ ?>
 <h1><?php echo _('Trunks')?></h1>
        <div class="well well-info">
                <?php echo _('This page is used to manage various system trunks')?>
        </div>
        <div class = "display no-border">
                <div class="row">
                        <div class="col-sm-12">
                                <div class="fpbx-container">
                                        <div class="display no-border">
                                                <?php echo load_view(__DIR__.'/trunkgrid.php', array('trunks' => $trunks, 'trunk_types' => $trunk_types))?>
                                        </div>
                                </div>
                        </div>
                </div>
		</div>
<?php } ?>
</div>

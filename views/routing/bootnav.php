<div id="routing-rnav">
<a href="config.php?display=routing" class="btn btn-default"><i class="fa fa-list">&nbsp; <?php echo _("List Routes")?></i></a>
<a href="config.php?display=routing&amp;view=form" class="btn btn-default"><i class="fa fa-plus"></i>&nbsp; <?php echo _("Add Outbound Route")?></a>

</div>
<table data-url="ajax.php?module=core&amp;command=getJSON&amp;jdata=routingrnav" data-cache="false" data-toggle="table" data-search="true" data-toolbar="#routing-rnav" class="table" id="table-all-side">
    <thead>
        <tr>
            <th data-sortable="true" data-field="name"><?php echo _('Route')?></th>
        </tr>
    </thead>
</table>
<script type="text/javascript">
  $("#table-all-side").on('click-row.bs.table',function(e,row,elem){
    window.location = '?display=routing&view=form&id='+row['route_id'];
  })
</script>

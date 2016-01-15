<div id="dahdidid-rnav">
<a href="?display=dahdichandids" class="btn btn-default"><i class="fa fa-list"></i>&nbsp; <?php echo _("List DAHDi DIDs")?></a>
<a href="config.php?display=dahdichandids&amp;view=add" class="btn btn-default"><i class="fa fa-plus"></i>&nbsp; <?php echo _("Add DAHDi DID")?></a>
</div>
<table data-url="ajax.php?module=core&amp;command=getJSON&amp;jdata=dahdichannels"
  data-cache="false"
  data-toggle="table"
  data-search="true"
  data-toolbar="#dahdidid-rnav"
  class="table"
  id="dahdidid-side">
    <thead>
        <tr>
            <th data-sortable="true"  data-field="channel" class="col-xs-2"><?php echo _('DAHDI Channel')?></th>
            <th data-sortable="true"  data-field="did"><?php echo _('DID')?></th>
        </tr>
    </thead>
</table>
<script type="text/javascript">
  function dahdididFormatter(v,r){
    return '<a href="?display=dahdichandids&view=add&extdisplay='+r['channel']+'">'+v+'/'+r['did']+'</a>';
  }
  $("#dahdidid-side").on('click-row.bs.table',function(e,row,elem){
    window.location = '?display=dahdichandids&view=add&extdisplay='+row['channel'];
  })
</script>

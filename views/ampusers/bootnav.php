<div id="toolbar-ampusers">
  <a href="?display=ampusers" class="btn btn-default"><i class="fa fa-plus"></i>&nbsp;<?php echo _("Add User")?></a>
</div>

<table data-url="ajax.php?module=core&amp;command=getJSON&amp;jdata=ampusers" data-cache="false" data-toggle="table" data-search="true" class="table" id="table-all-side">
    <thead>
        <tr>
          <th data-sortable="true" data-formatter='ampuserformatter' data-field="username"><?php echo _('Username')?></th>
        </tr>
    </thead>
</table>
<script type="text/javascript">
  function ampuserformatter(v){
    var value = encodeURIComponent(v);
    return '<a href="?display=ampusers&userdisplay='+value+'">'+value+'</a>';
  }
</script>

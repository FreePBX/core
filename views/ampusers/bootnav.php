<div id="toolbar-ampusers">
  <a href="?display=ampusers" class="btn btn-default"><i class="fa fa-plus"></i>&nbsp;<?php echo _("Add User")?></a>
</div>

<table data-url="ajax.php?module=core&amp;command=getJSON&amp;jdata=ampusers"
  data-cache="false"
  data-toggle="table"
  data-search="true"
  class="table"
  data-toolbar="#toolbar-ampusers"
  id="table-all-side">
    <thead>
        <tr>
          <th data-sortable="true" data-field="username"><?php echo _('Username')?></th>
        </tr>
    </thead>
</table>
<script type="text/javascript">
  $("#table-all-side").on('click-row.bs.table',function(e,row,elem){
    window.location = '?display=ampusers&userdisplay='+row['username'];
  })
</script>

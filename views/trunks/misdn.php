<tr>
	<td colspan="2">
		<h4><?php echo _("Outgoing Settings")?><hr></h4>
	</td>
</tr>
<tr> 
	<td> 
		<a href=# class="info"><?php echo _("mISDN Group/Port")?><span><br><?php echo _("mISDN channels are referenced either by a group name or channel number (use <i>mISDN Port Groups</i> to configure).")?><br><br></span></a>:  
	</td> 
	<td> 
		<select name="channelid"> 
		<?php 
		      $gps = misdn_groups_ports(); 
		      foreach($gps as $gp) { 
		        echo "<option value='$gp'"; 
		        if ($gp == $channelid) 
		          echo ' selected="1"'; 
		          echo ">$gp</option>\n"; 
		        }
		?> 
		</select> 
		<input type="hidden" size="14" name="usercontext" value="notneeded"/> 
	</td> 
</tr> 
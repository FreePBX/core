<?php /* $Id$ */
// This file is part of FreePBX.
//
//    FreePBX is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 2 of the License, or
//    (at your option) any later version.
//
//    FreePBX is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with FreePBX.  If not, see <http://www.gnu.org/licenses/>.
//
//    Copyright (C) 2004 Greg MacLellan (greg@mtechsolutions.ca)
//    Copyright (C) 2005 Ron Hartmann (rhartmann@vercomsystems.com)
//    Copyright (C) 2004 Coalescent Systems Inc. (info@coalescentsystems.ca)
//
$display='routing'; 
$extdisplay=isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:'';
$action = isset($_REQUEST['action'])?$_REQUEST['action']:'';
// Now check if the Copy Route submit button was pressed, in which case we duplicate the route
//
if (isset($_REQUEST['copyroute'])) {
  $action = 'copyroute';
}

$tabindex = 0;

$repotrunkdirection = isset($_REQUEST['repotrunkdirection'])?$_REQUEST['repotrunkdirection']:'';

//this was effectively the sequence, now it becomes the route_id and the value past will have to change
$repotrunkkey = isset($_REQUEST['repotrunkkey'])?$_REQUEST['repotrunkkey']:'';

// Check if they uploaded a CSV file for their route patterns
//
if (isset($_FILES['pattern_file']) && $_FILES['pattern_file']['tmp_name'] != '') {
  $fh = fopen($_FILES['pattern_file']['tmp_name'], 'r');
  if ($fh !== false) {
    $csv_file = array();
    $index = array();

    // Check first row, ingoring empty rows and get indices setup
    //
    while (($row = fgetcsv($fh, 5000, ",", "\"")) !== false) {
      if (count($row) == 1 && $row[0] == '') {
        continue;
      } else {
        $count = count($row) > 4 ? 4 : count($row);
        for ($i=0;$i<$count;$i++) {
          switch (strtolower($row[$i])) {
          case 'prepend':
          case 'prefix':
          case 'match pattern':
          case 'callerid':
            $index[strtolower($row[$i])] = $i;
          break;
          default:
          break;
          }
        }
        // If no headers then assume standard order
        if (count($index) == 0) {
          $index['prepend'] = 0;
          $index['prefix'] = 1;
          $index['match pattern'] = 2;
          $index['callerid'] = 3;
          if ($count == 4) {
            $csv_file[] = $row;
          }
        }
        break;
      }
    }
    $row_count = count($index);
    while (($row = fgetcsv($fh, 5000, ",", "\"")) !== false) {
      if (count($row) == $row_count) {
        $csv_file[] = $row;
      }
    }
  }
}

//
// Use a hash of the value inserted to get rid of duplicates
$dialpattern_insert = array();
$p_idx = 0;
$n_idx = 0;

// If we have a CSV file it replaces any existing patterns
//
if (!empty($csv_file)) {
  foreach ($csv_file as $row) {
    $this_prepend = isset($index['prepend']) ? htmlspecialchars(trim($row[$index['prepend']])) : '';
    $this_prefix = isset($index['prefix']) ? htmlspecialchars(trim($row[$index['prefix']])) : '';
    $this_match_pattern = isset($index['match pattern']) ? htmlspecialchars(trim($row[$index['match pattern']])) : '';
    $this_callerid = isset($index['callerid']) ? htmlspecialchars(trim($row[$index['callerid']])) : '';

    if ($this_prepend != '' || $this_prefix  != '' || $this_match_pattern != '' || $this_callerid != '') {
      $dialpattern_insert[] = array(
        'prepend_digits' => $this_prepend,
        'match_pattern_prefix' => $this_prefix,
        'match_pattern_pass' => $this_match_pattern,
        'match_cid' => $this_callerid,
      );
    }
  }
} else if (isset($_POST["prepend_digit"])) {
  $prepend_digit = $_POST["prepend_digit"];
  $pattern_prefix = $_POST["pattern_prefix"];
  $pattern_pass = $_POST["pattern_pass"];
  $match_cid = $_POST["match_cid"];

  foreach (array_keys($prepend_digit) as $key) {
    if ($prepend_digit[$key]!='' || $pattern_prefix[$key]!='' || $pattern_pass[$key]!='' || $match_cid[$key]!='') {

      $dialpattern_insert[] = array(
        'prepend_digits' => htmlspecialchars(trim($prepend_digit[$key])),
        'match_pattern_prefix' => htmlspecialchars(trim($pattern_prefix[$key])),
        'match_pattern_pass' => htmlspecialchars(trim($pattern_pass[$key])),
        'match_cid' => htmlspecialchars(trim($match_cid[$key])),
      );
    }
  }
}

if ( isset($_REQUEST['reporoutedirection']) && $_REQUEST['reporoutedirection'] != '' && isset($_REQUEST['reporoutekey']) && $_REQUEST['reporoutekey'] != '') {
  $_REQUEST['route_seq'] = core_routing_setrouteorder($_REQUEST['reporoutekey'], $_REQUEST['reporoutedirection']);
}

$trunkpriority = array();
if (isset($_REQUEST["trunkpriority"])) {
	$trunkpriority = $_REQUEST["trunkpriority"];

	if (!$trunkpriority) {
		$trunkpriority = array();
	}
	
	// delete blank entries and reorder
	foreach (array_keys($trunkpriority) as $key) {
		if ($trunkpriority[$key] == '') {
			// delete this empty
			unset($trunkpriority[$key]);
			
		} else if (($key==($repotrunkkey-1)) && ($repotrunkdirection=="up")) {
			// swap this one with the one before (move up)
			$temptrunk = $trunkpriority[$key];
			$trunkpriority[ $key ] = $trunkpriority[ $key+1 ];
			$trunkpriority[ $key+1 ] = $temptrunk;
			
		} else if (($key==($repotrunkkey)) && ($repotrunkdirection=="down")) {
			// swap this one with the one after (move down)
			$temptrunk = $trunkpriority[ $key+1 ];
			$trunkpriority[ $key+1 ] = $trunkpriority[ $key ];
			$trunkpriority[ $key ] = $temptrunk;
		}
	}
	unset($temptrunk);
	$trunkpriority = array_unique(array_values($trunkpriority)); // resequence our numbers
  if ($action == '') {
    $action = "updatetrunks";
  }

}

$routename = isset($_REQUEST['routename']) ? $_REQUEST['routename'] : '';
$routepass = isset($_REQUEST['routepass']) ? $_REQUEST['routepass'] : '';
$emergency = isset($_REQUEST['emergency']) ? $_REQUEST['emergency'] : '';
$intracompany = isset($_REQUEST['intracompany']) ? $_REQUEST['intracompany'] : '';
$mohsilence = isset($_REQUEST['mohsilence']) ? $_REQUEST['mohsilence'] : '';
$outcid = isset($_REQUEST['outcid']) ? $_REQUEST['outcid'] : '';
$outcid_mode = isset($_REQUEST['outcid_mode']) ? $_REQUEST['outcid_mode'] : '';
$time_group_id = isset($_REQUEST['time_group_id']) ? $_REQUEST['time_group_id'] : '';
$route_seq = isset($_REQUEST['route_seq']) ? $_REQUEST['route_seq'] : '';

//if submitting form, update database
switch ($action) {
	case 'ajaxroutepos':
		core_routing_setrouteorder($repotrunkkey, $repotrunkdirection);
    needreload();
    include_once("common/json.inc.php");
    if ($_POST['send_reload'] == 'yes') {
      ob_start();
      include ('views/freepbx_reloadbar.php');
      $json_array['reload_bar'] = ob_get_clean();

      ob_start();
      include ('views/freepbx_reload.php');
      $json_array['reload_header'] = ob_get_clean();
    }
    $json_array['show_reload'] = 'yes';
    $json = new Services_JSON();
    header("Content-type: application/json"); 
    echo $json->encode($json_array);
		exit;

	break;
  case "copyroute":
    $routename .= "_copy_$extdisplay";
    $extdisplay='';
    $route_seq++;
  // Fallthrough to addtrunk now...
  //
	case "addroute":
    $extdisplay = core_routing_addbyid($routename, $outcid, $outcid_mode, $routepass, $emergency, $intracompany, $mohsilence, $time_group_id, $dialpattern_insert, $trunkpriority, $route_seq);
    $_REQUEST['extdisplay'] = $extdisplay; //have not idea if this is needed or useful
		needreload();
		redirect_standard('extdisplay');
	break;
	case "editroute":
		core_routing_editbyid($extdisplay, $routename, $outcid, $outcid_mode, $routepass, $emergency, $intracompany, $mohsilence, $time_group_id, $dialpattern_insert, $trunkpriority, $route_seq);
		needreload();
		redirect_standard('extdisplay');
	break;
	case "updatetrunks":
    core_routing_updatetrunks($extdisplay, $trunkpriority, true);
		needreload();
	break;
	case "delroute":
		core_routing_delbyid($extdisplay);
		// re-order the routes to make sure that there are no skipped numbers.
		// example if we have 001-test1, 002-test2, and 003-test3 then delete 002-test2
		// we do not want to have our routes as 001-test1, 003-test3 we need to reorder them
		// so we are left with 001-test1, 002-test3
		needreload();
		redirect_standard();
	break;
	case 'prioritizeroute':
		needreload();
	break;
	case 'populatenpanxx':
    $dialpattern_array = $dialpattern_insert;
		if (preg_match("/^([2-9]\d\d)-?([2-9]\d\d)$/", $_REQUEST["npanxx"], $matches)) {
			// first thing we do is grab the exch:
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_URL, "http://www.localcallingguide.com/xmllocalprefix.php?npa=".$matches[1]."&nxx=".$matches[2]);
			curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Linux; FreePBX Local Trunks Configuration)");
			$str = curl_exec($ch);
			curl_close($ch);

			// quick 'n dirty - nabbed from PEAR
			require_once($amp_conf['AMPWEBROOT'] . '/admin/modules/core/XML_Parser.php');
			require_once($amp_conf['AMPWEBROOT'] . '/admin/modules/core/XML_Unserializer.php');

			$xml = new xml_unserializer;
			$xml->unserialize($str);
			$xmldata = $xml->getUnserializedData();

      $hash_filter = array(); //avoid duplicates
			if (isset($xmldata['lca-data']['prefix'])) {
				// we do the loops separately so patterns are grouped together
				
				// match 1+NPA+NXX (dropping 1)
				foreach ($xmldata['lca-data']['prefix'] as $prefix) {
          if (isset($hash_filter['1'.$prefix['npa'].$prefix['nxx']])) {
            continue;
          } else {
            $hash_filter['1'.$prefix['npa'].$prefix['nxx']] = true;
          }
          $dialpattern_array[] = array(
            'prepend_digits' => '',
            'match_pattern_prefix' => '1',
            'match_pattern_pass' => htmlspecialchars($prefix['npa'].$prefix['nxx']).'XXXX',
            'match_cid' => '',
          );
				}
				// match NPA+NXX
				foreach ($xmldata['lca-data']['prefix'] as $prefix) {
          if (isset($hash_filter[$prefix['npa'].$prefix['nxx']])) {
            continue;
          } else {
            $hash_filter[$prefix['npa'].$prefix['nxx']] = true;
          }
          $dialpattern_array[] = array(
            'prepend_digits' => '',
            'match_pattern_prefix' => '',
            'match_pattern_pass' => htmlspecialchars($prefix['npa'].$prefix['nxx']).'XXXX',
            'match_cid' => '',
          );
				}
				// match 7-digits
				foreach ($xmldata['lca-data']['prefix'] as $prefix) {
          if (isset($hash_filter[$prefix['nxx']])) {
            continue;
          } else {
            $hash_filter[$prefix['nxx']] = true;
          }
          $dialpattern_array[] = array(
            'prepend_digits' => '',
            'match_pattern_prefix' => '',
            'match_pattern_pass' => htmlspecialchars($prefix['nxx']).'XXXX',
            'match_cid' => '',
          );
				}
        unset($hash_filter);
			} else {
				$errormsg = _("Error fetching prefix list for: "). $_REQUEST["npanxx"];
			}
			
		} else {
			// what a horrible error message... :p
			$errormsg = _("Invalid format for NPA-NXX code (must be format: NXXNXX)");
		}
		
		if (isset($errormsg)) {
			echo "<script language=\"javascript\">alert('".addslashes($errormsg)."');</script>";
			unset($errormsg);
		}
	break;
}

?>
</div>
<script type="text/javascript">
$(document).ready(function(){
  $("#routelist").sortable({ 
    items: 'li:gt(0)',
    cursor: 'move',
    update: function(event, ui){
      var repotrunkkey=+ui.item.attr('id').replace('routelist','');
      var repotrunkdirection=ui.item.index();repotrunkdirection--;
      var send_reload = '&send_reload='+($('#need_reload_block').size() == 0 ? 'yes':'no');
      $.ajax({
        type: 'POST',
        url: location.href,
        data: "action=ajaxroutepos&quietmode=1&skip_astman=1&restrictmods=core&repotrunkkey="+repotrunkkey+"&repotrunkdirection="+repotrunkdirection+send_reload,
        dataType: 'json',
        success: function(data) {
          /* if the reload_block is not there, we blindly insert the info we think we got because we told
             it in the ajax call to send it to use, should wo double check?
          */
          if ((data.show_reload == 'yes') && !$('#need_reload_block').fadeIn().size()) {
            $('#logo').after(data.reload_bar).fadeIn();
            $('#moduleBox').before(data.reload_header);
          }
          if (data.show_reload == 'yes') {
            $('#need_reload_block').fadeIn();
          }
        },
        error: function(data) {
          alert("<?php _("An unknown error occurred repositioning routes, refresh your browser to see the current correct route positions") ?>");
        }
      });
	  }
  }).disableSelection();
});
</script>
<div class="rnav">
<ul id="routelist">
	<li><a <?php  echo ($extdisplay=='' ? 'class="current"':'') ?> href="config.php?display=<?php echo urlencode($display)?>"><?php echo _("Add Route")?></a></li>
<?php 
$reporoutedirection = isset($_REQUEST['reporoutedirection'])?$_REQUEST['reporoutedirection']:'';
$reporoutekey = isset($_REQUEST['reporoutekey'])?$_REQUEST['reporoutekey']:'';
$routepriority = core_routing_list();
$positions=count($routepriority);
$drag_title = _("Drag up or down to reposition, click to choose");
foreach ($routepriority as $key => $tresult) {
	echo "\t<li id=\"routelist".$tresult['route_id'] ."\">\n\t\t<a " . ($extdisplay==$tresult['route_id'] ? 'class="current"':'') .
		" href=\"config.php?display=" . 
		urlencode($display)."&amp;extdisplay=" . 
		urlencode($tresult['route_id']) . "\" title=\"$drag_title\"><img src=\"images/arrow_up_down.png\" height=\"16\" width=\"16\" border=\"0\" alt=\"move\" style=\"float:none; margin-left:-6px; margin-bottom:-3px;cursor:move\" /> " . $tresult['name']."</a>\n";
		echo "\t</li>\n";
} // foreach
?>
</ul>
</div>

<div class="content">

<?php 
$last_seq = count($routepriority)-1;
if ($action == 'populatenpanxx') {
	echo "<h2>"._("Edit Route")."</h2>";
} else if ($extdisplay != '') {
	
	// load from db
  $route_info = core_routing_get($extdisplay);
  $dialpattern_array = core_routing_getroutepatternsbyid($extdisplay);
  $trunkpriority = core_routing_getroutetrunksbyid($extdisplay);
	
  $routepass = $route_info['password'];
  $emergency = $route_info['emergency_route'];
  $intracompany = $route_info['intracompany_route'];
  $mohsilence = $route_info['mohclass'];
  $outcid = $route_info['outcid'];
  $outcid_mode = $route_info['outcid_mode'];
  $time_group_id = $route_info['time_group_id'];
  $route_seq = $route_info['seq'];
  $routename = $route_info['name'];
	echo "<h2>"._("Edit Route")."</h2>";
} else {	
  $route_seq = $last_seq+1;
  if (!isset($dialpattern_array)) {
    $dialpattern_array = array();
  }
	echo "<h2>"._("Add Route")."</h2>";
}
//
// build trunks associative array
foreach (core_trunks_listbyid() as $temp) {
	$trunks[$temp['trunkid']] = $temp['name'];
	$trunkstate[$temp['trunkid']] = $temp['disabled'];
}

if ($extdisplay) { // editing
	$tlabel = sprintf(_("Delete Route %s"),$route_info['name']);
	$label = '<span><img width="16" height="16" border="0" title="'.$tlabel.'" alt="" src="images/core_delete.png"/>&nbsp;'.$tlabel.'</span>';
?>
	<p><a href="config.php?display=<?php echo urlencode($display) ?>&extdisplay=<?php echo urlencode($extdisplay) ?>&action=delroute"><?php echo $label ?></a></p>
<?php  
} 
?>
	<form enctype="multipart/form-data" autocomplete="off" id="routeEdit" name="routeEdit" action="config.php" method="POST" onsubmit="return routeEdit_onsubmit('<?php echo ($extdisplay != '' ? "editroute" : "addroute") ?>');">
		<input type="hidden" name="display" value="<?php echo $display?>"/>
		<input type="hidden" name="extdisplay" value="<?php echo $extdisplay ?>"/>
		<input type="hidden" id="action" name="action" value=""/>

		<input type="hidden" id="repotrunkdirection" name="repotrunkdirection" value="">
		<input type="hidden" id="repotrunkkey" name="repotrunkkey" value="">
		<input type="hidden" id="reporoutedirection" name="reporoutedirection" value="">
		<input type="hidden" id="reporoutekey" name="reporoutekey" value="">
		<table>
    <tr>
      <td colspan="2"><h5><?php echo _("Route Settings") ?><hr></h5></td>
    </tr>

		<tr>
			<td>
				<a href=# class="info"><?php echo _("Route Name")?><span><br><?php echo _("Name of this route. Should be used to describe what type of calls this route matches (for example, 'local' or 'longdistance').")?><br></span></a>: 
			</td>
			<td>
				<input type="text" size="20" name="routename" value="<?php echo htmlspecialchars($routename);?>" tabindex="<?php echo ++$tabindex;?>"/>
			</td>
		</tr>

		<tr>
      <td><a href=# class="info"><?php echo _("Route CID")?>:<span><?php echo _("Optional Route CID to be used for this route. If set, this will override all CIDS specified except:<ul><li>extension/device EMERGENCY CIDs if this route is checked as an EMERGENCY Route</li><li>trunk CID if trunk is set to force it's CID</li><li>Forwarded call CIDs (CF, Follow Me, Ring Groups, etc)</li><li>Extension/User CIDs if checked</li></ul>")?></span></a></td>
			<td>
        <input type="text" size="20" name="outcid" value="<?php echo htmlspecialchars($outcid)?>" tabindex="<?php echo ++$tabindex;?>"/>
        <input type='checkbox' tabindex="<?php echo ++$tabindex;?>" name='outcid_mode' id="outcid_mode" value='override_extension' <?php if ($outcid_mode == 'override_extension') { echo 'CHECKED'; }?>><a href=# class="info"><small><?php echo _("Override Extension")?></small><span><?php echo _("If checked the extension's Outbound CID will be ignored in favor of this CID. The extension's Emergency CID will still be used if the route is an Emergency Route and the Extension has a defined Emergency CID.")?></span></a>
      </td>
		</tr>

		<tr>
			<td><a href=# class="info"><?php echo _("Route Password")?>:<span><?php echo _("Optional: A route can prompt users for a password before allowing calls to progress.  This is useful for restricting calls to international destinations or 1-900 numbers.<br><br>A numerical password, or the path to an Authenticate password file can be used.<br><br>Leave this field blank to not prompt for password.")?></span></a></td>
			<td><input type="text" size="20" name="routepass" value="<?php echo $routepass;?>" tabindex="<?php echo ++$tabindex;?>"/></td>
		</tr>

		<tr>
      <td><a href=# class="info"><?php echo _("Route Type")?>:<span><?php echo _("Optional: Selecting Emergency will enforce the use of a device's Emergency CID setting (if set).  Select this option if this route is used for emergency dialing (ie: 911).").'<br />'._("Optional: Selecting Intra-Company will treat this route as an intra-company connection, preserving the internal CallerID information instead of the outbound CID of either the extension or trunk.")?></span></a></td>
      <td>
        <input type="checkbox" name="emergency" value="YES" <?php echo ($emergency ? "CHECKED" : "") ?>  tabindex="<?php echo ++$tabindex;?>"/><small><?php echo _("Emergency") ?></small>
        <input type="checkbox" name="intracompany" value="YES" <?php echo ($intracompany ? "CHECKED" : "") ?>  tabindex="<?php echo ++$tabindex;?>"/><small><?php echo _("Intra-Company") ?></small>
      </td>
		</tr>

<?php   if (function_exists('music_list')) { ?>
    <tr>
      <td><a href="#" class="info"><?php echo _("Music On Hold?")?><span><?php echo _("You can choose which music category to use. For example, choose a type appropriate for a destination country which may have announcements in the appropriate language.")?></span></a></td>
      <td>
        <select name="mohsilence" tabindex="<?php echo ++$tabindex;?>">
        <?php
          $tresults = music_list();
          $cur = (isset($mohsilence) && $mohsilence != "" ? $mohsilence : 'default');
          if (isset($tresults[0])) {
            foreach ($tresults as $tresult) {
              $ttext = $tresult;
              if($tresult == 'none') $ttext = _("none");
              if($tresult == 'default') $ttext = _("default");
              echo '<option value="'.$tresult.'"'.($tresult == $cur ? ' SELECTED' : '').'>'.$ttext."</option>\n";
            }
          }
        ?>		
        </select>		
      </td>
    </tr>
<?php } ?>

<?php if (function_exists('timeconditions_timegroups_drawgroupselect')) { ?>
    <tr>
      <td><a href="#" class="info"><?php echo _("Time Group:")?><span><?php echo _("If this route should only be available during certain times then Select a Time Group created under Time Groups. The route will be ignored outside of times specified in that Time Group. If left as default of Permanent Route then it will always be available.")?></span></a></td>
      <td><?php echo timeconditions_timegroups_drawgroupselect('time_group_id', $time_group_id, true, '', _('---Permanent Route---')); ?></td>
    </tr>
		<tr>
<?php } ?>

		<tr>
			<td><a href="#" class="info"><?php echo _("Route Position")?><span><?php echo _("Where to insert this route or relocate it relative to the other routes.")?></span></a></td>
			<td>
				<select name="route_seq" tabindex="<?php echo ++$tabindex;?>">
				<?php
          if ($route_seq != 0) {
            echo '<option value="0"'.($route_seq == 0 ? ' SELECTED' : '').'>'.sprintf(_('First before %s'),$routepriority[0]['name'])."</option>\n";
          }
          foreach ($routepriority as $key => $route) {
            if ($key == 0 && $route_seq != 0) continue;
            if ($key == ($route_seq+1)) continue;
            if ($route_seq == $key) {
              echo '<option value="'.$key.'" SELECTED>'._('---No Change---')."</option>\n";
            } else {
              echo '<option value="'.$key.'">'.sprintf(_('Before %s'),$route['name'])."</option>\n";
            }
          }
          if ($extdisplay == '' | $route_seq != $last_seq) {
            echo '<option value="bottom"'.($route_seq == count($routepriority) ? ' SELECTED' : '').'>'.sprintf(_('Last after %s'),$routepriority[$last_seq]['name'])."</option>\n";
          }
				?>		
				</select>		
			</td>
		</tr>
<?php
	if (!empty($module_hook->hookHtml)) {
?>
    <tr>
      <td colspan="2"><h5><?php echo _("Additional Settings") ?><hr></h5></td>
    </tr>
<?php
	  echo $module_hook->hookHtml;
  }
?>
    <tr>
      <td colspan="2"><h5>
      <a href=# class="info"><?php echo _("Dial Patterns that will use this Route")?><span>
      <?php echo _("A Dial Pattern is a unique set of digits that will select this route and send the call to the designated trunks. If a dialed pattern matches this route, no subsequent routes will be tried. If Time Groups are enabled, subsequent routes will be checked for matches outside of the designated time(s).")?><br /><br /><b><?php echo _("Rules:")?></b><br />
      <b>X</b>&nbsp;&nbsp;&nbsp; <?php echo _("matches any digit from 0-9")?><br />
      <b>Z</b>&nbsp;&nbsp;&nbsp; <?php echo _("matches any digit from 1-9")?><br />
      <b>N</b>&nbsp;&nbsp;&nbsp; <?php echo _("matches any digit from 2-9")?><br />
      <b>[1237-9]</b>&nbsp;   <?php echo _("matches any digit in the brackets (example: 1,2,3,7,8,9)")?><br />
      <b>.</b>&nbsp;&nbsp;&nbsp; <?php echo _("wildcard, matches one or more dialed digits")?> <br />
      <b><?php echo _("prepend:")?></b>&nbsp;&nbsp;&nbsp; <?php echo _("Digits to prepend to a successful match. If the dialed number matches the patterns specified by the subsequent columns, then this will be prepended before sending to the trunks.")?><br />
      <b><?php echo _("prefix:")?></b>&nbsp;&nbsp;&nbsp; <?php echo _("Prefix to remove on a successful match. The dialed number is compared to this and the subsequent columns for a match. Upon a match, this prefix is removed from the dialed number before sending it to the trunks.")?><br />
      <b><?php echo _("match pattern:")?></b>&nbsp;&nbsp;&nbsp; <?php echo _("The dialed number will be compared against the  prefix + this match pattern. Upon a match, the match pattern portion of the dialed number will be sent to the trunks")?><br />
      <b><?php echo _("CallerID:")?></b>&nbsp;&nbsp;&nbsp; <?php echo _("If CallerID is supplied, the dialed number will only match the prefix + match pattern if the CallerID being transmitted matches this. When extensions make outbound calls, the CallerID will be their extension number and NOT their Outbound CID. The above special matching sequences can be used for CallerID matching similar to other number matches.")?><br />
      </span></a>
      <hr></h5></td>
    </tr>

    <tr><td colspan="2"><div class="dialpatterns"><table>
<?php
  $pp_tit = _("prepend");
  $pf_tit = _("prefix");
  $mp_tit = _("match pattern");
  $ci_tit = _("CallerID");

  $dpt_title_class = 'dpt-title dpt-display';
  foreach ($dialpattern_array as $idx => $pattern) {
    $tabindex++;
    if ($idx == 50) {
      $dpt_title_class = 'dpt-title dpt-nodisplay';
    }
    $dpt_class = $pattern['prepend_digits'] == '' ? $dpt_title_class : 'dpt-value';
    echo <<< END
    <tr>
      <td colspan="2">
        (<input title="$pp_tit" type="text" size="8" id="prepend_digit_$idx" name="prepend_digit[$idx]" class="dial-pattern $dpt_class" value="{$pattern['prepend_digits']}" tabindex="$tabindex">) +
END;
    $tabindex++;
    $dpt_class = $pattern['match_pattern_prefix'] == '' ? $dpt_title_class : 'dpt-value';
    echo <<< END
        <input title="$pf_tit" type="text" size="6" id="pattern_prefix_$idx" name="pattern_prefix[$idx]" class="$dpt_class" value="{$pattern['match_pattern_prefix']}" tabindex="$tabindex"> |
END;
    $tabindex++;
    $dpt_class = $pattern['match_pattern_pass'] == '' ? $dpt_title_class : 'dpt-value';
    echo <<< END
        [<input title="$mp_tit" type="text" size="16" id="pattern_pass_$idx" name="pattern_pass[$idx]" class="$dpt_class" value="{$pattern['match_pattern_pass']}" tabindex="$tabindex"> /
END;
    $tabindex++;
    $dpt_class = $pattern['match_cid'] == '' ? $dpt_title_class : 'dpt-value';
    echo <<< END
        <input title="$ci_tit" type="text" size="10" id="match_cid_$idx" name="match_cid[$idx]" class="$dpt_class" value="{$pattern['match_cid']}" tabindex="$tabindex">]
END;
?>
        <img src="images/trash.png" style="float:none; margin-left:0px; margin-bottom:-3px; cursor:pointer;" alt="<?php echo _("remove")?>" title="<?php echo _('Click here to remove this pattern')?>" onclick="patternsRemove(<?php echo _("$idx") ?>)">
      </td>
    </tr>
<?php
  }
  $next_idx = count($dialpattern_array);
?>
    <tr>
      <td colspan="2">
        (<input title="<?php echo $pp_tit?>" type="text" size="8" id="prepend_digit_<?php echo $next_idx?>" name="prepend_digit[<?php echo $next_idx?>]" class="dial-pattern dpt-title dpt-display" value="" tabindex="<?php echo ++$tabindex;?>">) +
        <input title="<?php echo $pf_tit?>" type="text" size="6" id="pattern_prefix_<?php echo $next_idx?>" name="pattern_prefix[<?php echo $next_idx?>]" class="dpt-title dpt-display" value="" tabindex="<?php echo ++$tabindex;?>"> |
        [<input title="<?php echo $mp_tit?>" type="text" size="16" id="pattern_pass_<?php echo $next_idx?>" name="pattern_pass[<?php echo $next_idx?>]" class="dpt-title dpt-display" value="" tabindex="<?php echo ++$tabindex;?>"> /
        <input title="<?php echo $ci_tit?>" type="text" size="10" id="match_cid_<?php echo $next_idx?>" name="match_cid[<?php echo $next_idx?>]" class="dpt-title dpt-display" value="" tabindex="<?php echo ++$tabindex;?>">]
        <img src="images/trash.png" style="float:none; margin-left:0px; margin-bottom:-3px;cursor:pointer;" alt="<?php echo _("remove")?>" title="<?php echo _("Click here to remove this pattern")?>" onclick="patternsRemove(<?php echo _("$next_idx") ?>)">

      </td>
    </tr>
    <tr id="last_row"></tr> 
    </table></div></tr>
    <tr><td colspan="2">
      <input type="button" id="dial-pattern-add"  value="<?php echo _("+ Add More Dial Pattern Fields")?>" />
    </td></tr>
<?php
  $tabindex += 2000; // make room for dynamic insertion of new fields
?>
		<tr>
			<td>
			<a href=# class="info"><?php echo _("Dial patterns wizards")?><span>
					<?php echo _("These options provide a quick way to add outbound dialing rules. Follow the prompts for each.")?><br>
					<strong><?php echo _("Lookup local prefixes")?></strong> <?php echo _("This looks up your local number on www.localcallingguide.com (NA-only), and sets up so you can dial either 7, 10 or 11 digits (5551234, 6135551234, 16135551234) to access this route.")?><br>
					<strong><?php echo _("Upload from CSV")?></strong> <?php echo sprintf(_("Upload patterns from a CSV file replacing existing entries. If there are no headers then the file must have 4 columns of patterns in the same order as in the GUI. You can also supply headers: %s, %s, %s and %s in the first row. If there are less then 4 recognized headers then the remaining columns will be blank"),'<strong>prepend</strong>','<strong>prefix</strong>','<strong>match pattern</strong>','<strong>callerid</strong>')?><br>
					</span></a>:
			<input id="npanxx" name="npanxx" type="hidden" />
			<script language="javascript">
			
			function populateLookup() {
<?php 
	if (function_exists("curl_init")) { // curl is installed
?>				
				//var npanxx = prompt("What is your areacode + prefix (NPA-NXX)?", document.getElementById('areacode').value);
				do {
					var npanxx = <?php echo 'prompt("'._("What is your areacode + prefix (NPA-NXX)?\\n\\n(Note: this database contains North American numbers only, and is not guaranteed to be 100% accurate. You will still have the option of modifying results.)\\n\\nThis may take a few seconds.").'")' ?>;
					if (npanxx == null) return;
				} while (!npanxx.match("^[2-9][0-9][0-9][-]?[2-9][0-9][0-9]$") && <?php echo '!alert("'._("Invalid NPA-NXX. Must be of the format \'NXX-NXX\'").'")'?>);
				
				document.getElementById('npanxx').value = npanxx;
				document.getElementById('routeEdit').action.value = "populatenpanxx";
        clearPatterns();
				document.getElementById('routeEdit').submit();
<?php  
	} else { // curl is not installed
?>
				<?php echo "alert('"._("Error: Cannot continue!\\n\\nPrefix lookup requires cURL support in PHP on the server. Please install or enable cURL support in your PHP installation to use this function. See http://www.php.net/curl for more information.")."')"?>;
<?php 
	}
?>
			}

			function insertCode() {
        // hide the file box if nothing was set
        if ($('#pattern_file').val() == '') {
          $('#pattern_file').hide();
        }
				code = document.getElementById('inscode').value;
				insert = '';
				switch(code) {
					case "local":
            insert = '<?php echo _("NXXXXXX") ?>';
					break;
					case "local10":
            insert = '<?php echo _("NXXXXXX,NXXNXXXXXX") ?>';
					break;
					case 'tollfree':
            insert = '<?php echo _("1800NXXXXXX,1888NXXXXXX,1877NXXXXXX,1866NXXXXXX") ?>';
					break;
					case "ld":
            insert = '<?php echo _("1NXXNXXXXXX") ?>';
					break;
					case "int":
            insert = '<?php echo _("011.") ?>';
					break;
					case 'info':
            insert = '<?php echo _("411,311") ?>';
					break;
					case 'emerg':
            insert = '<?php echo _("911") ?>';
					break;
					case 'lookup':
						populateLookup();
						insert = '';
					break;
					case 'csv':
            $('#pattern_file').show().click();
            return true;
					break;
				}

        patterns = insert.split(',')
        for (var i in patterns) {
          addCustomField("","",patterns[i],"");
        }

				// reset element
				document.getElementById('inscode').value = '';
			}
			
			--></script>
			<td>
				<select onChange="insertCode();" id="inscode">
			<option value=""><?php echo _("(pick one)")?></option>
			<option value="local"><?php echo _("Local 7 digit")?></option>
			<option value="local10"><?php echo _("Local 7/10 digit")?></option>
			<option value="tollfree"><?php echo _("Toll-free")?></option>
			<option value="ld"><?php echo _("Long-distance")?></option>
			<option value="int"><?php echo _("International")?></option>
			<option value="info"><?php echo _("Information")?></option>
			<option value="emerg"><?php echo _("Emergency")?></option>
			<option value="lookup"><?php echo _("Lookup local prefixes")?></option>
			<option value="csv"><?php echo _("Upload from CSV")?></option>
				</select>
        <input type="file" name="pattern_file" id="pattern_file" tabindex="<?php echo ++$tabindex;?>"/>
			</td>
		</tr>

    <tr>
      <td colspan="2"><h5><a href=# class="info"><?php echo _("Trunk Sequence for Matched Routes")?><span><?php echo _("The Trunk Sequence controls the order of trunks that will be used when the above Dial Patterns are matched. <br><br>For Dial Patterns that match long distance numbers, for example, you'd want to pick the cheapest routes for long distance (ie, VoIP trunks first) followed by more expensive routes (POTS lines).")?><br></span></a><hr></h5></td>
    </tr>
<?php 
$key = -1;
$positions=count($trunkpriority);
foreach ($trunkpriority as $key=>$trunk) {
?>
		<tr>
			<td><?php echo $key; ?>&nbsp;&nbsp;
		    <select id='trunkpri<?php echo $key ?>' name="trunkpriority[<?php echo $key ?>]" style="background: <?php echo $trunkstate[$trunk]=="off"?"#FFF":"#DDD" ?> ;" onChange="showDisable(<?php echo $key ?>); return true;">
				<option value="" style="background: #FFF;"></option>
				<?php 
				foreach ($trunks as $name=>$display_description) {
					if ($trunkstate[$name] == 'off') {
						echo "<option id=\"trunk".$key."\" name=\"trunk".$key."\" value=\"".$name."\" style=\"background: #FFF;\" ".($name == $trunk ? "selected" : "").">".str_replace('AMP:', '', $display_description)."</option>";
					} else {
						echo "<option id=\"trunk".$key."\" name=\"trunk".$key."\" value=\"".$name."\" style=\"background: #DDD;\" ".($name == $trunk ? "selected" : "").">".str_replace('AMP:', '', $display_description)."</option>";
					}
				}
				?>
				</select>
				
				<img src="images/trash.png" style="cursor:pointer; float:none; margin-left:0px; margin-bottom:-3px;" title="Click here to remove this trunk" onclick="deleteTrunk(<?php echo $key ?>)">
			<?php   // move up
			if ($key > 0) {?>
				<img src="images/resultset_up.png" onclick="repositionTrunk('<?php echo $key ?>','up')" alt="<?php echo _("Move Up")?>" style="cursor:pointer; float:none; margin-left:0px; margin-bottom:0px;" width="12px" height="12px">
			<?php  } else { ?>
				<img src="images/blank.gif" style="float:none; margin-left:0px; margin-bottom:0px;" width="9" height="11">
			<?php  }
			
			// move down
			
			if ($key < ($positions-1)) {?>
				<img src="images/resultset_down.png" onclick="repositionTrunk('<?php echo $key ?>','down')" alt="<?php echo _("Move Down")?>"  style="cursor:pointer; float:none; margin-left:0px; margin-bottom:0px;" width="12px" height="12px">
			<?php  } else { ?>
				<img src="images/blank.gif" style="float:none; margin-left:0px; margin-bottom:0px;" width="9" height="11">
			<?php  } ?>
			</td>
		</tr>
<?php 
} // foreach

$key += 1; // this will be the next key value
$name = "";

// display 1 additional box if editing, or one for each trunk (to a max of 3)
$num_new_boxes = ($extdisplay ? 1 : ((count($trunks) > 3) ? 3 : count($trunks)));

for ($i=0; $i < $num_new_boxes; $i++) {
?>
		<tr>
			<td><?php echo $key; ?>&nbsp;&nbsp;
				<select id='trunkpri<?php echo $key ?>' name="trunkpriority[<?php echo $key ?>]">
				<option value="" SELECTED></option>
				<?php 
				foreach ($trunks as $name=>$display_description) {
					if ($trunkstate[$name] == 'off') {
					echo "<option value=\"".$name."\">".str_replace('AMP:', '', $display_description)."</option>";
					} else {
					echo "<option value=\"".$name."\" style=\"background: #DDD;\" >*".ltrim($display_description,"AMP:")."*</option>";
					}
				}
				?>
				</select>
			</td>
		</tr>
<?php 
	$key++;
} //for 0..$num_new_boxes ?>

<?php if ($extdisplay != ''): // editing ?>
		<tr>
			<td colspan="2">
				<input type="submit" value="<?php echo _("Add Trunk")?>">
			</td>
		</tr>
<?php endif; // if $extdisplay ?>

		<tr>
			<td colspan="2">
        <h6>
          <input name="Submit" type="submit" value="<?php echo _("Submit Changes")?>">
          <input name="copyroute" type="submit" value="<?php echo _("Duplicate Route");?>"/>
				</h6>
			</td>
		</tr>
		</table>
 
<script language="javascript">
<!--

$(document).ready(function(){
  /* Add a Custom Var / Val textbox */
  $("#dial-pattern-add").click(function(){
    addCustomField('','','','');
  });
  $('#pattern_file').hide();
  $(".dpt-display").toggleVal({
    populateFrom: "title",
    changedClass: "text-normal",
    focusClass: "text-normal"
  });
  $(".dpt-nodisplay").mouseover(function(){
    $(this).toggleVal({
      populateFrom: "title",
      changedClass: "text-normal",
      focusClass: "text-normal"
    }).removeClass('dpt-nodisplay').addClass('dpt-display').unbind('mouseover');
  });
}); 

function patternsRemove(idx) {
  $("#prepend_digit_"+idx).parent().parent().remove();
}

function addCustomField(prepend_digit, pattern_prefix, pattern_pass, match_cid) {
  var idx = $(".dial-pattern").size();
  var idxp = idx - 1;
  var tabindex = parseInt($("#match_cid_"+idxp).attr('tabindex')) + 1;
  var tabindex1 = tabindex + 2;
  var tabindex2 = tabindex + 3;
  var tabindex3 = tabindex + 4;
  var dpt_title = 'dpt-title dpt-display';
  var dpt_prepend_digit = prepend_digit == '' ? dpt_title : 'dpt-value';
  var dpt_pattern_prefix = pattern_prefix == '' ? dpt_title : 'dpt-value';
  var dpt_pattern_pass = pattern_pass == '' ? dpt_title : 'dpt-value';
  var dpt_match_cid = match_cid == '' ? dpt_title : 'dpt-value';

  var new_insert = $("#last_row").before('\
  <tr>\
    <td colspan="2">\
    (<input title="<?php echo $pp_tit?>" type="text" size="8" id="prepend_digit_'+idx+'" name="prepend_digit['+idx+']" class="dial-pattern '+dpt_prepend_digit+'" value="'+prepend_digit+'" tabindex="'+tabindex+'">) +\
    <input title="<?php echo $pf_tit?>" type="text" size="6" id="pattern_prefix_'+idx+'" name="pattern_prefix['+idx+']" class="'+dpt_pattern_prefix+'" value="'+pattern_prefix+'" tabindex="'+tabindex1+'"> |\
    [<input title="<?php echo $mp_tit?>" type="text" size="16" id="pattern_pass_'+idx+'" name="pattern_pass['+idx+']" class="'+dpt_pattern_pass+'" value="'+pattern_pass+'" tabindex="'+tabindex2+'"> /\
    <input title="<?php echo $ci_tit?>" type="text" size="10" id="match_cid_'+idx+'" name="match_cid['+idx+']" class="'+dpt_match_cid+'" value="'+match_cid+'" tabindex="'+tabindex3+'">]\
      <img src="images/trash.png" style="cursor:pointer; float:none; margin-left:0px; margin-bottom:-3px;" alt="<?php echo _("remove")?>" title="<?php echo _("Click here to remove this pattern")?>" onclick="patternsRemove('+idx+')">\
    </td>\
  </tr>\
  ').prev();

  new_insert.find(".dpt-title").toggleVal({
    populateFrom: "title",
    changedClass: "text-normal",
    focusClass: "text-normal"
  });

  return idx;
}

var theForm = document.routeEdit;

if (theForm.routename.value == "") {
	theForm.routename.focus();
} else {
	theForm.outcid.focus();
}

function showDisable(key) {
<?php
	$bgmap = 'bgc = {';
	foreach ($trunks as $name=>$display_description) {
		$bgmap .= " \"$name\":";
		$bgmap .= ($trunkstate[$name] == 'off')?'"#FFF",':'"#DDD",';
	}
	echo rtrim($bgmap,',')." };\n";
?>
	if (document.getElementById('trunkpri'+key).value =='') {
		document.getElementById('trunkpri'+key).style.background = '#FFF';
	} else {
		document.getElementById('trunkpri'+key).style.background = bgc[document.getElementById('trunkpri'+key).value];
	}
}

function routeEdit_onsubmit(act) {
	var msgInvalidRouteName = "<?php echo _('Route name is invalid, please try again'); ?>";
	var msgInvalidRoutePwd = "<?php echo _('Route password must be numeric or leave blank to disable'); ?>";
	var msgInvalidTrunkSelection = "<?php echo _('At least one trunk must be picked'); ?>";
	var msgInvalidOutboundCID = "<?php echo _('Invalid Outbound CallerID'); ?>";
	
	var rname = theForm.routename.value;
	if (!rname.match('^[a-zA-Z0-9][a-zA-Z0-9_\-]+$'))
		return warnInvalid(theForm.routename, msgInvalidRouteName);
	
	defaultEmptyOK = true;
	if (!isInteger(theForm.routepass.value))
		return warnInvalid(theForm.routepass, msgInvalidRoutePwd);
	if (!isCallerID(theForm.outcid.value))
		return warnInvalid(theForm.outcid, msgInvalidOutboundCID);
	
	defaultEmptyOK = false;
  /* TODO: get some sort of check in for dialpatterns
	if (!isDialpattern(theForm.dialpattern.value))
		return warnInvalid(theForm.dialpattern, msgInvalidDialPattern);
    */
		
	if (theForm.trunkpri0.value == "") { // should they all be checked ?
		theForm.trunkpri0.focus();
		alert(msgInvalidTrunkSelection);
		return false;
	}
	
	theForm.action.value = act;

  clearPatterns();
  return validatePatterns();
}

function clearPatterns() {
  $(".dpt-display").each(function() {
    if($(this).val() == $(this).data("defText")) {
      $(this).val("");
    }
  });
  return true;
}

function validatePatterns() {
  var one_good = false;
  var culprit;
  var msgInvalidDialPattern;
  defaultEmptyOK = false;

  $(".dpt-display, .dpt-value").each(function() {
    if ($.trim($(this).val()) == '') {
    } else if (!isDialpattern($(this).val())) {
      culprit = this;
      return false;
    } else {
      one_good = true;
    }
  });

  if (culprit == undefined && !one_good) {
    if ($('#inscode').val() == 'csv') {
      return true;
    }
    culprit = $('.toggleval:visible').get(0);
	  msgInvalidDialPattern = "<?php echo _('No dial pattern, there must be at least one'); ?>";
  } else {
	  msgInvalidDialPattern = "<?php echo _('Dial pattern is invalid'); ?>";
  }
  if (culprit != undefined) {
    // now we have to put it back...
    // do I have to turn it off first though?
    $(".dpt-display").each(function() {
      if ($.trim($(this).val()) == '') {
        $(this).toggleVal({
          populateFrom: "title",
          changedClass: "text-normal",
          focusClass: "text-normal"
        });
      }
    });
    return warnInvalid(culprit, msgInvalidDialPattern);
  } else {
    return true;
  }
}

//TODO: maybe add action vs. it being blank and assumed above?
function repositionTrunk(key,direction) {
  switch (direction) {
  case 'up':
  case 'down':
    document.getElementById('repotrunkdirection').value=direction;
    document.getElementById('repotrunkkey').value=key;
    clearPatterns();
    document.getElementById('routeEdit').submit();
    break;
  }
}

function deleteTrunk(key) {
	document.getElementById('trunkpri'+key).value = '';
  clearPatterns();
	document.getElementById('routeEdit').submit();
}

function repositionRoute(key,direction){
  switch (direction) {
  case 'up':
  case 'down':
  case 'top':
  case 'bottom':
    document.getElementById('reporoutedirection').value=direction;
    document.getElementById('reporoutekey').value=key;
    document.getElementById('action').value='prioritizeroute';
    clearPatterns();
    document.getElementById('routeEdit').submit();
    break;
  }
}

//-->
</script>

</form>

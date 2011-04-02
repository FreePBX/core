<?php /* $Id$ */
//This file is part of FreePBX.
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
//    Copyright (C) 2004 Coalescent Systems Inc. (info@coalescentsystems.ca)
//

$display='trunks'; 
$extdisplay=isset($_REQUEST['extdisplay'])?$_REQUEST['extdisplay']:'';
$trunknum = ltrim($extdisplay,'OUT_');

$action = isset($_REQUEST['action'])?$_REQUEST['action']:'';
// Now check if the Copy Trunks submit button was pressed, in which case we duplicate the trunk
//
if (isset($_REQUEST['copytrunk'])) {
  $action = 'copytrunk';
}

$tech         = strtolower(isset($_REQUEST['tech'])?htmlentities($_REQUEST['tech']):'');
$outcid       = isset($_REQUEST['outcid'])?$_REQUEST['outcid']:'';
$maxchans     = isset($_REQUEST['maxchans'])?$_REQUEST['maxchans']:'';
$dialoutprefix= isset($_REQUEST['dialoutprefix'])?$_REQUEST['dialoutprefix']:'';
$channelid    = isset($_REQUEST['channelid'])?$_REQUEST['channelid']:'';
$peerdetails  = isset($_REQUEST['peerdetails'])?$_REQUEST['peerdetails']:'';
$usercontext  = isset($_REQUEST['usercontext'])?$_REQUEST['usercontext']:'';
$userconfig   = isset($_REQUEST['userconfig'])?$_REQUEST['userconfig']:'';
$register     = isset($_REQUEST['register'])?$_REQUEST['register']:'';
$keepcid      = isset($_REQUEST['keepcid'])?$_REQUEST['keepcid']:'off';
$disabletrunk = isset($_REQUEST['disabletrunk'])?$_REQUEST['disabletrunk']:'off';
$provider     = isset($_REQUEST['provider'])?$_REQUEST['provider']:'';
$trunk_name   = isset($_REQUEST['trunk_name'])?$_REQUEST['trunk_name']:'';

$failtrunk    = isset($_REQUEST['failtrunk'])?$_REQUEST['failtrunk']:'';
$failtrunk_enable = ($failtrunk == "")?'':'CHECKED';

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
        $count = count($row) > 3 ? 3 : count($row);
        for ($i=0;$i<$count;$i++) {
          switch (strtolower($row[$i])) {
          case 'prepend':
          case 'prefix':
          case 'match pattern':
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
          if ($count == 3) {
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

    if ($this_prepend != '' || $this_prefix  != '' || $this_match_pattern != '') {
      $dialpattern_insert[] = array(
        'prepend_digits' => $this_prepend,
        'match_pattern_prefix' => $this_prefix,
        'match_pattern_pass' => $this_match_pattern,
      );
    }
  }
} else if (isset($_POST["prepend_digit"])) {
  $prepend_digit = $_POST["prepend_digit"];
  $pattern_prefix = $_POST["pattern_prefix"];
  $pattern_pass = $_POST["pattern_pass"];

  foreach (array_keys($prepend_digit) as $key) {
    if ($prepend_digit[$key]!='' || $pattern_prefix[$key]!='' || $pattern_pass[$key]!='') {

      $dialpattern_insert[] = array(
        'prepend_digits' => htmlspecialchars(trim($prepend_digit[$key])),
        'match_pattern_prefix' => htmlspecialchars(trim($pattern_prefix[$key])),
        'match_pattern_pass' => htmlspecialchars(trim($pattern_pass[$key])),
      );
    }
  }
}


// TODO: remember old name, if new one is different the don't rename
//
//if submitting form, update database
switch ($action) {
  case "copytrunk":

    $sv_channelid    = isset($_REQUEST['sv_channelid'])?$_REQUEST['sv_channelid']:'';
    $sv_trunk_name    = isset($_REQUEST['sv_trunk_name'])?$_REQUEST['sv_trunk_name']:'';
    $sv_usercontext    = isset($_REQUEST['sv_usercontext'])?$_REQUEST['sv_usercontext']:'';

    if ($trunk_name == $sv_trunk_name) {
      $trunk_name .= ($trunk_name == '' ? '' : '_') . "copy_$trunknum";
    }
    if ($channelid == $sv_channelid) {
      $channelid .= '_copy_' . $trunknum;
    }
    if ($usercontext != '' && $usercontext == $sv_usercontext) {
      $usercontext .= '_copy_' . $trunknum;
    }
    $disabletrunk = 'on';
    $trunknum = '';
    $extdisplay='';
  // Fallthrough to addtrunk now...
  //
	case "addtrunk":
		$trunknum = core_trunks_add($tech, $channelid, $dialoutprefix, $maxchans, $outcid, $peerdetails, $usercontext, $userconfig, $register, $keepcid, trim($failtrunk), $disabletrunk, $trunk_name, $provider);
		
    core_trunks_update_dialrules($trunknum, $dialpattern_insert);
		needreload();
		redirect_standard();
	break;
	case "edittrunk":
		core_trunks_edit($trunknum, $channelid, $dialoutprefix, $maxchans, $outcid, $peerdetails, $usercontext, $userconfig, $register, $keepcid, trim($failtrunk), $disabletrunk, $trunk_name, $provider);
		
		// this can rewrite too, so edit is the same
    core_trunks_update_dialrules($trunknum, $dialpattern_insert, true);
		needreload();
		redirect_standard('extdisplay');
	break;
	case "deltrunk":
	
		core_trunks_del($trunknum);
    core_trunks_delete_dialrules($trunknum);
    core_routing_trunk_delbyid($trunknum);
		needreload();
		redirect_standard();
	break;
	case "populatenpanxx7": 
	case "populatenpanxx10": 
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

			if (isset($xmldata['lca-data']['prefix'])) {
        $hash_filter = array(); //avoid duplicates
				if ($action == 'populatenpanxx10') {
					// 10 digit dialing
					// - add area code to 7 digits
					// - match local 10 digits
					// - add 1 to anything else
          $dialpattern_array[] = array(
            'prepend_digits' => '',
            'match_pattern_prefix' => '',
            'match_pattern_pass' => htmlspecialchars($matches[1].'NXXXXXX'),
          );
					// add NPA to 7-digits
					foreach ($xmldata['lca-data']['prefix'] as $prefix) {
            if (isset($hash_filter[$prefix['npa'].'+'.$prefix['nxx']])) {
              continue;
            } else {
              $hash_filter[$prefix['npa'].'+'.$prefix['nxx']] = true;
            }
            $dialpattern_array[] = array(
              'prepend_digits' =>  htmlspecialchars($prefix['npa']),
              'match_pattern_prefix' => '',
              'match_pattern_pass' => htmlspecialchars($prefix['nxx'].'XXXX'),
            );
					}
					foreach ($xmldata['lca-data']['prefix'] as $prefix) {
            if (isset($hash_filter[$prefix['npa'].$prefix['nxx']])) {
              continue;
            } else {
              $hash_filter[$prefix['npa'].$prefix['nxx']] = true;
            }
            $dialpattern_array[] = array(
              'prepend_digits' =>  '',
              'match_pattern_prefix' => '',
              'match_pattern_pass' => htmlspecialchars($prefix['npa'].$prefix['nxx'].'XXXX'),
            );
					}
					// if a number was not matched as local, dial it with '1' prefix
          $dialpattern_array[] = array(
            'prepend_digits' =>  '',
            'match_pattern_prefix' => '',
            'match_pattern_pass' => '1+NXXNXXXXXX',
          );
				} else {
					// 7 digit dialing
					// - drop area code from local numbers
					// - match local 7 digit numbers
					// - add 1 to everything else
					foreach ($xmldata['lca-data']['prefix'] as $prefix) {
            if (isset($hash_filter[$prefix['npa'].'|'.$prefix['nxx']])) {
              continue;
            } else {
              $hash_filter[$prefix['npa'].'|'.$prefix['nxx']] = true;
            }
            $dialpattern_array[] = array(
              'prepend_digits' =>  '',
              'match_pattern_prefix' => htmlspecialchars( $prefix['npa']),
              'match_pattern_pass' => htmlspecialchars($prefix['nxx'].'XXXX'),
            );
					}
					foreach ($xmldata['lca-data']['prefix'] as $prefix) {
            if (isset($hash_filter[$prefix['nxx']])) {
              continue;
            } else {
              $hash_filter[$prefix['nxx']] = true;
            }
            $dialpattern_array[] = array(
              'prepend_digits' =>  '',
              'match_pattern_prefix' => '',
              'match_pattern_pass' => htmlspecialchars($prefix['nxx'].'XXXX'),
            );
					}
          $dialpattern_array[] = array(
            'prepend_digits' =>  '1',
            'match_pattern_prefix' => '',
            'match_pattern_pass' => 'NXXNXXXXXX',
          );
          $dialpattern_array[] = array(
            'prepend_digits' => htmlspecialchars('1'.$matches[1]),
            'match_pattern_prefix' => '',
            'match_pattern_pass' => 'NXXXXXX',
          );
				}

				// check for duplicates, and re-sequence
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

<div class="rnav">
<ul>
	<li><a <?php  echo ($extdisplay=='' ? 'class="current"':'') ?> href="config.php?display=<?php echo urlencode($display)?>"><?php echo _("Add Trunk")?></a></li>
<?php 
//get existing trunk info
$tresults = core_trunks_getDetails();
//$tresults = core_trunks_list();

foreach ($tresults as $tresult) {
	$background = ($tresult['disabled'] == 'on')?'#DDD':'';
	switch ($tresult['tech']) {
		case 'enum':
			$label = substr($tresult['name'],0,15)." ENUM";
			break;
		case 'dundi':
			$label = substr($tresult['name'],0,15)." (DUNDi)";
			break;
		case 'iax2':
			$tresult['tech'] = 'iax';
		case 'zap':
		case 'dahdi':
			$label = substr($tresult['name'],0,15);
      if (trim($label) == '') {
        $label = sprintf(_('Channel %s'),substr($tresult['channelid'],0,15));
      }
			$label .= " (".$tresult['tech'].")";
			break;
		case 'sip':
		case 'iax':
		case 'custom':
		default:
			$label = substr($tresult['name'],0,15);
      if (trim($label) == '') {
        $label = substr($tresult['channelid'],0,15);
      }
			$label .= " (".$tresult['tech'].")";
			break;
	}
	echo "\t<li><a ".($trunknum==$tresult['trunkid'] ? 'class="current"':'')." href=\"config.php?display=".urlencode($display)."&amp;extdisplay=OUT_".urlencode($tresult['trunkid'])."\" title=\"".urlencode($tresult['name'])."\" style=\"background: $background;\" >".$label."</a></li>\n";
}

?>
</ul>
</div>

<div class="content">

<?php 
if (!$tech && !$extdisplay) {
?>
	<h2><?php echo _("Add a Trunk")?></h2>
<?php
	$baseURL   = $_SERVER['PHP_SELF'].'?display='.urlencode($display).'&';
  $trunks[] = array('url'=> $baseURL.'tech=SIP', 'tlabel' =>  _("Add SIP Trunk"));
  if (ast_with_dahdi()) {
    $trunks[] = array('url'=> $baseURL.'tech=DAHDI', 'tlabel' =>  _("Add DAHDi Trunk"));
  }
  $trunks[] = array('url'=> $baseURL.'tech=ZAP', 'tlabel' =>  _("Add Zap Trunk").(ast_with_dahdi()?" ("._("DAHDi compatibility mode").")":"" ));
  $trunks[] = array('url'=> $baseURL.'tech=IAX2', 'tlabel' =>  _("Add IAX2 Trunk"));
  //--------------------------------------------------------------------------------------
  // Added to enable the unsupported misdn module
  if (function_exists('misdn_ports_list_trunks') && count(misdn_ports_list_trunks())) {
    $trunks[] = array('url'=> $baseURL.'tech=MISDN', 'tlabel' =>  _("Add mISDN Trunk"));
  }
  //--------------------------------------------------------------------------------------
  $trunks[] = array('url'=> $baseURL.'tech=ENUM', 'tlabel' =>  _("Add ENUM Trunk"));
  $trunks[] = array('url'=> $baseURL.'tech=DUNDI', 'tlabel' =>  _("Add DUNDi Trunk"));
  $trunks[] = array('url'=> $baseURL.'tech=CUSTOM', 'tlabel' =>  _("Add Custom Trunk"));
	foreach ($trunks as $trunk) {
		$label = '<span><img width="16" height="16" border="0" title="'.$trunk['tlabel'].'" alt="" src="images/core_add.png"/>&nbsp;'.$trunk['tlabel'].'</span>';
		echo "<a href=".$trunk['url'].">".$label."</a><br /><br />";
	}
} else {
	if ($extdisplay) {

		$trunk_details = core_trunks_getDetails($trunknum);

		$tech = htmlentities($trunk_details['tech']);
		$outcid = htmlentities($trunk_details['outcid']);
		$maxchans = htmlentities($trunk_details['maxchans']);
		$dialoutprefix = htmlentities($trunk_details['dialoutprefix']);
		$keepcid = htmlentities($trunk_details['keepcid']);
		$failtrunk = htmlentities($trunk_details['failscript']);
		$failtrunk_enable = ($failtrunk == "")?'':'CHECKED';
		$disabletrunk = htmlentities($trunk_details['disabled']);
		$provider = $trunk_details['provider'];
		$trunk_name = htmlentities($trunk_details['name']);

		if ($tech!="enum") {
	
			$channelid = htmlentities($trunk_details['channelid']);

			if ($tech!="custom" && $tech!="dundi") {  // custom trunks will not have user/peer details in database table
				// load from db
				if (empty($peerdetails)) {	
					$peerdetails = core_trunks_getTrunkPeerDetails($trunknum);
				}
				if (empty($usercontext)) {	
					$usercontext = htmlentities($trunk_details['usercontext']);
				}
	
				if (empty($userconfig)) {	
					$userconfig = core_trunks_getTrunkUserConfig($trunknum);
				}
					
				if (empty($register)) {	
					$register = core_trunks_getTrunkRegister($trunknum);
				}
			}
		}
    if (count($dialpattern_array) == 0) {
      $dialpattern_array = core_trunks_get_dialrules($trunknum);
    }
		$upper_tech = strtoupper($tech);
    if (trim($trunk_name) == '') {
		  $trunk_name = ($upper_tech == 'ZAP'|$upper_tech == 'DAHDI'?sprintf(_('%s Channel %s'),$upper_tech,$channelid):$channelid);
    }
		echo "<h2>".sprintf(_("Edit %s Trunk"),$upper_tech).($upper_tech == 'ZAP' && ast_with_dahdi()?" ("._("DAHDi compatibility Mode").")":"")."</h2>";
		$tlabel = sprintf(_("Delete Trunk %s"),substr($trunk_name,0,20));
		$label = '<span><img width="16" height="16" border="0" title="'.$tlabel.'" alt="" src="images/core_delete.png"/>&nbsp;'.$tlabel.'</span>';
?>
		<p><a href="config.php?display=<?php echo urlencode($display) ?>&extdisplay=<?php echo urlencode($extdisplay) ?>&action=deltrunk"><?php echo $label ?></a></p>
<?php 

		// find which routes use this trunk
		$routes = core_trunks_gettrunkroutes($trunknum);
		$num_routes = count($routes);
		if ($num_routes > 0) {
			echo "<a href=# class=\"info\">&nbsp;"._("In use by")." ".$num_routes." ".($num_routes == 1 ? _("route") : _("routes"))."<span>";
			foreach($routes as $route=>$priority) {
				echo _("Route")." <b>".$route."</b>: "._("Sequence")." <b>".$priority."</b><br>";
			}
			echo "</span></a>";
		} else {
			echo "&nbsp;<b>"._("WARNING:")."</b> <a href=# class=\"info\">"._("This trunk is not used by any routes!")."<span>";
			echo _("This trunk will not be able to be used for outbound calls until a route is setup that uses it. Click on <b>Outbound Routes</b> to setup routing.");
			echo "</span></a>";
		}
		$usage_list = framework_display_destination_usage(core_getdest(ltrim($extdisplay,'OUT_')));
		if (!empty($usage_list)) {
		?>
			<a href="#" class="info"><?php echo $usage_list['text']?><span><?php echo $usage_list['tooltip']?></span></a>
		<?php
		}


	} else {
		// set defaults
		$outcid = "";
		$maxchans = "";
		$dialoutprefix = "";
		
		if ($tech == 'zap' || $tech == 'dahdi') {
			$channelid = 'g0';
		} else {
			$channelid = '';
		}
		
		// only for iax2/sip
		$peerdetails = "host=***provider ip address***\nusername=***userid***\nsecret=***password***\ntype=peer";
		$usercontext = "";
		$userconfig = "secret=***password***\ntype=user\ncontext=from-trunk";
		$register = "";
		
		$localpattern = "NXXXXXX";
		$lddialprefix = "1";
		$areacode = "";
	
		$upper_tech = strtoupper($tech);
		echo "<h2>".sprintf(_("Add %s Trunk"),$upper_tech).($upper_tech == 'ZAP' && ast_with_dahdi()?" ("._("DAHDi compatibility mode").")":"")."</h2>";
	} 
  if (!isset($dialpattern_array)) {
    $dialpattern_array = array();
  }
		
switch ($tech) {
	case 'dundi':
		$helptext = _('FreePBX offers limited support for DUNDi trunks and additional manual configuration is required. The trunk name should correspond to the [mappings] section of the remote dundi.conf systems. For example, you may have a mapping on the remote system, and corresponding configurations in dundi.conf locally, that looks as follows:<br /><br />[mappings]<br />priv => dundi-extens,0,IAX2,priv:${SECRET}@218.23.42.26/${NUMBER},nopartial<br /><br />In this example, you would create this trunk and name it priv. You would then create the corresponding IAX2 trunk with proper settings to work with DUNDi. This can be done by making an IAX2 trunk in FreePBX or by using the iax_custom.conf file.<br />The dundi-extens context in this example must be created in extensions_custom.conf. This can simply include contexts such as ext-local, ext-intercom-users, ext-paging and so forth to provide access to the corresponding extensions and features provided by these various contexts and generated by FreePBX.');
		break;
	case 'sip':
		break;
	default:
		$helptext = '';
}
if ($helptext != '') {
	if ($extdisplay) {
		echo "<br /><br />";
	}
	echo $helptext;
}
		
?>
	
		<form enctype="multipart/form-data" name="trunkEdit" action="config.php" method="post" onsubmit="return trunkEdit_onsubmit('<?php echo ($extdisplay ? "edittrunk" : "addtrunk") ?>');">
			<input type="hidden" name="display" value="<?php echo $display?>"/>
			<input type="hidden" name="extdisplay" value="<?php echo $extdisplay ?>"/>
			<input type="hidden" name="action" value=""/>
			<input type="hidden" name="tech" value="<?php echo $tech?>"/>
			<input type="hidden" name="provider" value="<?php echo $provider?>"/>
			<input type="hidden" name="sv_trunk_name" value="<?php echo $trunkname?>"/>
			<input type="hidden" name="sv_usercontext" value="<?php echo $usercontext?>"/>
			<input type="hidden" name="sv_channelid" value="<?php echo $channelid?>"/>
			<input id="npanxx" name="npanxx" type="hidden" />
			<table>
			<tr>
				<td colspan="2">
					<h4><?php echo _("General Settings")?><hr></h4>
				</td>
			</tr>
			<tr>
				<td>
					<a href=# class="info"><?php echo _("Trunk Name")?><span><?php echo _("Descriptive Name for this Trunk")?></span></a>: 
				</td><td>
					<input type="text" size="30" name="trunk_name" value="<?php echo $trunk_name;?>" tabindex="<?php echo ++$tabindex;?>"/>
				</td>
			</tr>
			<tr>
				<td>
					<a href=# class="info"><?php echo _("Outbound CallerID")?><span><?php echo _("CallerID for calls placed out on this trunk<br><br>Format: <b>&lt;#######&gt;</b>. You can also use the format: \"hidden\" <b>&lt;#######&gt;</b> to hide the CallerID sent out over Digital lines if supported (E1/T1/J1/BRI/SIP/IAX).")?></span></a>: 
				</td><td>
					<input type="text" size="30" name="outcid" value="<?php echo $outcid;?>" tabindex="<?php echo ++$tabindex;?>"/>
				</td>
			</tr>
			<tr>

	    <tr>
				<td>
					<a href="#" class="info"><?php echo _("CID Options")?><span><?php echo _("Determines what CIDs will be allowed out this trunk. IMPORTANT: EMERGENCY CIDs defined on an extension/device will ALWAYS be used if this trunk is part of an EMERGENCY Route regardless of these settings.<br />Allow Any CID: all CIDs including foreign CIDS from forwarded external calls will be transmitted.<br />Block Foreign CIDs: blocks any CID that is the result of a forwarded call from off the system. CIDs defined for extensions/users are transmitted.<br />Remove CNAM: this will remove CNAM from any CID sent out this trunk<br />Force Trunk CID: Always use the CID defined for this trunk except if part of any EMERGENCY Route with an EMERGENCY CID defined for the extension/device.");?></span></a>:
				</td><td>

			    <select name="keepcid" tabindex="<?php echo ++$tabindex;?>">
			    <?php
				    $default = (isset($keepcid) ? $keepcid : 'off');
				    echo '<option value="off"' . ($default == 'off'  ? ' SELECTED' : '').'>'._("Allow Any CID")."\n";
				    echo '<option value="on"'  . ($default == 'on'   ? ' SELECTED' : '').'>'._("Block Foreign CIDs")."\n";
				    echo '<option value="cnum"'. ($default == 'cnum' ? ' SELECTED' : '').'>'._("Remove CNAM")."\n";
				    echo '<option value="all"' . ($default == 'all'  ? ' SELECTED' : '').'>'._("Force Trunk CID")."\n";
			    ?>
			    </select>
				</td>
      </tr>

			<tr>
				<td>
<?php
	if ($tech == "sip" || substr($tech,0,3) == "iax") {
		$pr_tech = ($tech == "iax") ? "iax2":$tech;
?>
					<a href=# class="info"><?php echo _("Maximum Channels")?><span><?php echo sprintf(_("Controls the maximum number of outbound channels (simultaneous calls) that can be used on this trunk. To count inbound calls against this maximum, use the auto-generated context: %s as the inbound trunk's context. (see extensions_additional.conf) Leave blank to specify no maximum."),((isset($channelid) && trim($channelid)!="")?"from-trunk-$pr_tech-$channelid":"from-trunk-[trunkname]"))?></span></a>: 
<?php
	} else {
?>
					<a href=# class="info"><?php echo _("Maximum Channels")?><span><?php echo _("Controls the maximum number of outbound channels (simultaneous calls) that can be used on this trunk. Inbound calls are not counted against the maximum. Leave blank to specify no maximum.")?></span></a>: 
<?php
	}
?>
				</td><td>
					<input type="text" size="3" name="maxchans" value="<?php echo htmlspecialchars($maxchans); ?>" tabindex="<?php echo ++$tabindex;?>"/>
				</td>
			</tr>

			<tr>
			    <td><a class="info" href="#"><?php echo _("Disable Trunk")?><span><?php echo _("Check this to disable this trunk in all routes where it is used.")?></span></a>:
			    </td>
			    <td>
				<input type='checkbox'  tabindex="<?php echo ++$tabindex;?>"name='disabletrunk' id="disabletrunk" <?php if ($disabletrunk=="on") { echo 'CHECKED'; }?> OnClick='disable_verify(disabletrunk); return true;'><small><?php echo _("Disable")?></small>
			    </td>
			</tr>
			<tr>
			    <td><a class="info" href="#"><?php echo _("Monitor Trunk Failures")?><span><?php echo _("If checked, supply the name of a custom AGI Script that will be called to report, log, email or otherwise take some action on trunk failures that are not caused by either NOANSWER or CANCEL.")?></span></a>:
			    </td>
			    <td>
				<input <?php if (!$failtrunk_enable) echo "disabled style='background: #DDD;'"?> type="text" size="20" name="failtrunk" value="<?php echo htmlspecialchars($failtrunk)?>"/>
				<input type='checkbox' tabindex="<?php echo ++$tabindex;?>" name='failtrunk_enable' id="failtrunk_enable" value='1' <?php if ($failtrunk_enable) { echo 'CHECKED'; }?> OnClick='disable_field(failtrunk,failtrunk_enable); return true;'><small><?php echo _("Enable")?></small>
			    </td>
			</tr>

    <tr>
      <td colspan="2"><h4>
      <a href=# class="info"><?php echo _("Dialed Number Manipulation Rules")?><span>
      <?php echo _("These rules can manipulate the dialed number before sending it out this trunk. If no rule applies, the number is not changed. The original dialed number is passed down from the route where some manipulation may have already occurred. This trunk has the option to further manipulate the number. If the number matches the combined values in the <b>prefix</b> plus the <b>match pattern</b> boxes, the rule will be applied and all subsequent rules ignored.<br/> Upon a match, the <b>prefix</b>, if defined, will be stripped. Next the <b>prepend</b> will be inserted in front of the <b>match pattern</b> and the resulting number will be sent to the trunk. All fields are optional.")?><br /><br /><b><?php echo _("Rules:")?></b><br />
      <b>X</b>&nbsp;&nbsp;&nbsp; <?php echo _("matches any digit from 0-9")?><br />
      <b>Z</b>&nbsp;&nbsp;&nbsp; <?php echo _("matches any digit from 1-9")?><br />
      <b>N</b>&nbsp;&nbsp;&nbsp; <?php echo _("matches any digit from 2-9")?><br />
      <b>[1237-9]</b>&nbsp;   <?php echo _("matches any digit in the brackets (example: 1,2,3,7,8,9)")?><br />
      <b>.</b>&nbsp;&nbsp;&nbsp; <?php echo _("wildcard, matches one or more dialed digits")?> <br />
      <b><?php echo _("prepend:")?></b>&nbsp;&nbsp;&nbsp; <?php echo _("Digits to prepend upon a successful match. If the dialed number matches the patterns in the <b>prefix</b> and <b>match pattern</b> boxes, this will be prepended before sending to the trunk.")?><br />
      <b><?php echo _("prefix:")?></b>&nbsp;&nbsp;&nbsp; <?php echo _("Prefix to remove upon a successful match. If the dialed number matches this plus the <b>match pattern</b> box, this prefix is removed before adding the optional <b>prepend</b> box and sending the results to the trunk.")?><br />
      <b><?php echo _("match pattern:")?></b>&nbsp;&nbsp;&nbsp; <?php echo _("The dialed number will be compared against the <b>prefix</b> plus this pattern. Upon a match, this portion of the number will be sent to the trunks after removing the <b>prefix</b> and appending the <b>prepend</b> digits")?><br />
	    <?php echo _("You can completely replace a number by matching on the <b>prefix</b> only, replacing it with a <b>prepend</b> and leaving the <b>match pattern</b> blank."); ?>
      </span></a>
      <hr></h4></td>
    </tr>

    <tr><td colspan="2"><div class="dialpatterns"><table>
<?php
  $pp_tit = _("prepend");
  $pf_tit = _("prefix");
  $mp_tit = _("match pattern");
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
        (<input title="$pp_tit" type="text" size="10" id="prepend_digit_$idx" name="prepend_digit[$idx]" class="dial-pattern dp-prepend $dpt_class" value="{$pattern['prepend_digits']}" tabindex="$tabindex">) +
END;
    $tabindex++;
    $dpt_class = $pattern['match_pattern_prefix'] == '' ? $dpt_title_class : 'dpt-value';
    echo <<< END
        <input title="$pf_tit" type="text" size="6" id="pattern_prefix_$idx" name="pattern_prefix[$idx]" class="dp-prefix $dpt_class" value="{$pattern['match_pattern_prefix']}" tabindex="$tabindex"> |
END;
    $tabindex++;
    $dpt_class = $pattern['match_pattern_pass'] == '' ? $dpt_title_class : 'dpt-value';
    echo <<< END
        <input title="$mp_tit" type="text" size="16" id="pattern_pass_$idx" name="pattern_pass[$idx]" class="dp-match $dpt_class" value="{$pattern['match_pattern_pass']}" tabindex="$tabindex">
END;
?>
        <img src="images/core_add.png" style="cursor:pointer; float:none; margin-left:0px; margin-bottom:-3px;" alt="<?php echo _("insert")?>" title="<?php echo _('Click here to insert a new pattern')?>" onclick="addCustomField('','','',$('#prepend_digit_<?php echo $idx?>').parent().parent())">
        <img src="images/trash.png" style="cursor:pointer; float:none; margin-left:0px; margin-bottom:-3px;" alt="<?php echo _("remove")?>" title="<?php echo _('Click here to remove this pattern')?>" onclick="patternsRemove(<?php echo "$idx" ?>)">
      </td>
    </tr>
<?php
  }
  $next_idx = count($dialpattern_array);
?>
    <tr>
      <td colspan="2">
        (<input title="<?php echo $pp_tit?>" type="text" size="10" id="prepend_digit_<?php echo $next_idx?>" name="prepend_digit[<?php echo $next_idx?>]" class="dp-prepend dial-pattern dpt-title dpt-display" value="" tabindex="<?php echo ++$tabindex;?>">) +
        <input title="<?php echo $pf_tit?>" type="text" size="6" id="pattern_prefix_<?php echo $next_idx?>" name="pattern_prefix[<?php echo $next_idx?>]" class="dp-prefix dpt-title dpt-display" value="" tabindex="<?php echo ++$tabindex;?>"> |
        <input title="<?php echo $mp_tit?>" type="text" size="16" id="pattern_pass_<?php echo $next_idx?>" name="pattern_pass[<?php echo $next_idx?>]" class="dp-match dpt-title dpt-display" value="" tabindex="<?php echo ++$tabindex;?>">
        <img src="images/core_add.png" style="cursor:pointer; float:none; margin-left:0px; margin-bottom:-3px;" alt="<?php echo _("insert")?>" title="<?php echo _('Click here to insert a new pattern')?>" onclick="addCustomField('','','',$('#prepend_digit_<?php echo $idx?>').parent().parent())">
        <img src="images/trash.png" style="cursor:pointer; float:none; margin-left:0px; margin-bottom:-3px;" alt="<?php echo _("remove")?>" title="<?php echo _("Click here to remove this pattern")?>" onclick="patternsRemove(<?php echo "$next_idx" ?>)">

      </td>
    </tr>
    <tr id="last_row"></tr> 
    </table></div></tr>
<?php
  $tabindex += 2000; // make room for dynamic insertion of new fields
?>
    <tr><td colspan="2">
      <input type="button" id="dial-pattern-add"  value="<?php echo _("+ Add More Dial Pattern Fields")?>" />
      <input type="button" id="dial-pattern-clear"  value="<?php echo _("Clear all Fields")?>" />
    </td></tr>
			<tr>
				<td>
					<a href=# class="info"><?php echo _("Dial Rules Wizards")?><span>
					<strong><?php echo _("Always dial with prefix")?></strong> <?php echo _("is useful for VoIP trunks, where if a number is dialed as \"5551234\", it can be converted to \"16135551234\".")?><br>
					<strong><?php echo _("Remove prefix from local numbers")?></strong> <?php echo _("is useful for ZAP and DAHDi trunks, where if a local number is dialed as \"6135551234\", it can be converted to \"555-1234\".")?><br>
					<strong><?php echo _("Setup directory assistance")?></strong> <?php echo _("is useful to translate a call to directory assistance")?><br>
					<strong><?php echo _("Lookup numbers for local trunk")?></strong> <?php echo _("This looks up your local number on www.localcallingguide.com (NA-only), and sets up so you can dial either 7 or 10 digits (regardless of what your PSTN is) on a local trunk (where you have to dial 1+area code for long distance, but only 5551234 (7-digit dialing) or 6135551234 (10-digit dialing) for local calls")?><br>
					<strong><?php echo _("Upload from CSV")?></strong> <?php echo sprintf(_("Upload patterns from a CSV file replacing existing entries. If there are no headers then the file must have 3 columns of patterns in the same order as in the GUI. You can also supply headers: %s, %s and %s in the first row. If there are less then 3 recognized headers then the remaining columns will be blank"),'<strong>prepend</strong>','<strong>prefix</strong>','<strong>match pattern</strong>')?><br>
					</span></a>:
				</td><td valign="top"><select id="autopop"  tabindex="<?php echo ++$tabindex;?>" name="autopop" onChange="changeAutoPop(); ">
						<option value="" SELECTED><?php echo _("(pick one)")?></option>
						<option value="always"><?php echo _("Always dial with prefix")?></option>
						<option value="remove"><?php echo _("Remove prefix from local numbers")?></option>
						<option value="directory"><?php echo _("Setup directory assistance")?></option>
						<option value="lookup7"><?php echo _("Lookup numbers for local trunk (7-digit dialing)")?></option>
						<option value="lookup10"><?php echo _("Lookup numbers for local trunk (10-digit dialing)")?></option>
            <option value="csv"><?php echo _("Upload from CSV")?></option>
					</select>
          <input type="file" name="pattern_file" id="pattern_file" tabindex="<?php echo ++$tabindex;?>"/>
				</td>
			</tr>
			<script language="javascript">
			
			function disable_field(field, field_enable) {
			    if (field_enable.checked) {
				field.style.backgroundColor = '#FFF';
				field.disabled = false;
			    }
			    else {
				field.style.backgroundColor = '#DDD';
				field.disabled = true;
			    }
			}

			function disable_verify(field) {
				if (field.checked) {
					var answer=confirm("<?php echo _("Are you sure you want to disable this trunk in all routes it is used?") ?>");
					if (!answer) {
						field.checked = false;
					}
				} else {
					alert("<?php echo _("You have enabled this trunk in all routes it is used") ?>");
				}
			}

			function populateLookup(digits) {
<?php 
	if (function_exists("curl_init")) { // curl is installed
?>				
				//var npanxx = prompt("What is your areacode + prefix (NPA-NXX)?", document.getElementById('areacode').value);
				do {
					var npanxx = <?php echo 'prompt("'._("What is your areacode + prefix (NPA-NXX)?\\n\\n(Note: this database contains North American numbers only, and is not guaranteed to be 100% accurate. You will still have the option of modifying results.)\\n\\nThis may take a few seconds.".'")')?>;
					if (npanxx == null) return;
				} while (!npanxx.match("^[2-9][0-9][0-9][-]?[2-9][0-9][0-9]$") && <?php echo '!alert("'._("Invalid NPA-NXX. Must be of the format \'NXX-NXX\'").'")'?>);
				
				document.getElementById('npanxx').value = npanxx;
				if (digits == 10) {
					document.trunkEdit.action.value = "populatenpanxx10";
				} else {
					document.trunkEdit.action.value = "populatenpanxx7";
				}
        clearPatterns();
				document.trunkEdit.submit();
<?php  
	} else { // curl is not installed
?>
				<?php echo 'alert("'._("Error: Cannot continue!\\n\\nPrefix lookup requires cURL support in PHP on the server. Please install or enable cURL support in your PHP installation to use this function. See http://www.php.net/curl for more information.").'")'?>;
<?php 
	}
?>
			}
			
			function populateAlwaysAdd() {
				do {
          var localpattern = <?php echo 'prompt("'._("What is the local dialing pattern?\\n\\n(ie. NXXNXXXXXX for US/CAN 10-digit dialing, NXXXXXX for 7-digit)").'"'?>,"<?php echo _("NXXXXXX")?>");
					if (localpattern == null) return;
				} while (!localpattern.match('^[0-9#*ZXN\.]+$') && <?php echo '!alert("'._("Invalid pattern. Only 0-9, #, *, Z, N, X and . are allowed.").'")'?>);
				
				do {
					var localprefix = <?php echo 'prompt("'._("What prefix should be added to the dialing pattern?\\n\\n(ie. for US/CAN, 1+areacode, ie, \'1613\')?").'")'?>;
					if (localprefix == null) return;
				} while (!localprefix.match('^[0-9#*]+$') && <?php echo '!alert("'._("Invalid prefix. Only dialable characters (0-9, #, and *) are allowed.").'")'?>);

        return addCustomField(localprefix,'',localpattern,$("#last_row"));
			}
			
			function populateRemove() {
				do {
					var localprefix = <?php echo 'prompt("'._("What prefix should be removed from the number?\\n\\n(ie. for US/CAN, 1+areacode, ie, \'1613\')").'")'?>;
					if (localprefix == null) return;
				} while (!localprefix.match('^[0-9#*ZXN\.]+$') && <?php echo '!alert("'._('Invalid prefix. Only 0-9, #, *, Z, N, and X are allowed.').'")'?>);
				
				do {
          var localpattern = <?php echo 'prompt("'._("What is the dialing pattern for local numbers after")?> "+localprefix+"? \n\n<?php echo _("(ie. NXXNXXXXXX for US/CAN 10-digit dialing, NXXXXXX for 7-digit)").'"'?>,"<?php echo _("NXXXXXX")?>");
					if (localpattern == null) return;
				} while (!localpattern.match('^[0-9#*ZXN\.]+$') && <?php echo '!alert("'._("Invalid pattern. Only 0-9, #, *, Z, N, X and . are allowed.").'")'?>);
				
        return addCustomField('',localprefix,localpattern,$("#last_row"));
			}

			function populatedirectory() {
				do {
        var localprefix = <?php echo 'prompt("'._("What is the directory assistance number you will dial locally in the format that is passed to this trunk?").'"'?>,"<?php echo ""?>");
					if (localprefix == null) return;
				} while (!localprefix.match('^[0-9#*]+$') && <?php echo '!alert("'._("Invalid pattern. Only 0-9, #, *").'")'?>);
				do {

        var localprepend = <?php echo 'prompt("'._("Number to dial when calling directory assistance on this trunk").'"'?>,"<?php echo '' ?>");
					if (localprepend == null) return;
				} while (!localprepend.match('^[0-9#*]+$') && <?php echo '!alert("'._('Invalid number. Only 0-9, #,  and * are allowed.').'")'?>);
				
        return addCustomField(localprepend,localprefix,'',$("#last_row"));
			}
			
			function changeAutoPop() {
        var idx = false;
        // hide the file box if nothing was set
        if ($('#pattern_file').val() == '') {
          $('#pattern_file').hide();
        }
				switch(document.getElementById('autopop').value) {
					case "always":
						idx = populateAlwaysAdd();
            if (idx) {
              $('#pattern_prefix_'+idx).focus();
            }
					break;
					case "remove":
						idx = populateRemove();
            if (idx) {
              $('#prepend_digit_'+idx).focus();
            }
					break;
					case "directory":
						idx = populatedirectory();
            if (idx) {
              $('#pattern_pass_'+idx).focus();
            }
					break;
					case "lookup7":
						populateLookup(7);
					break;
					case "lookup10":
						populateLookup(10);
					break;
					case 'csv':
            $('#pattern_file').show().click();
            return true;
					break;
				}
				document.getElementById('autopop').value = '';
			}
			</script>

			<tr>
				<td>
					<a href=# class="info"><?php echo _("Outbound Dial Prefix")?><span><?php echo _("The outbound dialing prefix is used to prefix a dialing string to all outbound calls placed on this trunk. For example, if this trunk is behind another PBX or is a Centrex line, then you would put 9 here to access an outbound line. Another common use is to prefix calls with 'w' on a POTS line that need time to obtain dial tone to avoid eating digits.<br><br>Most users should leave this option blank.")?></span></a>: 
				</td><td>
					<input type="text" size="8" name="dialoutprefix" id="dialoutprefix" value="<?php echo htmlspecialchars($dialoutprefix) ?>" tabindex="<?php echo ++$tabindex;?>"/>
				</td>
			</tr>
			<?php if ($tech != "enum") { ?>
			<tr>
				<td colspan="2">
        <h4><?php echo _("Outgoing Settings")?><hr></h4>
				</td>
			</tr>
			<?php } ?>

	<?php 
	switch ($tech) {
		case "zap":
	?>
				<tr>
					<td>
						<a href=# class="info"><?php echo _("Zap Identifier")?><span><?php echo _("ZAP channels are referenced either by a group number or channel number (which is defined in zapata.conf).  <br><br>The default setting is <b>g0</b> (group zero).")?></span></a>: 
					</td><td>
						<input type="text" size="8" name="channelid" value="<?php echo htmlspecialchars($channelid) ?>" tabindex="<?php echo ++$tabindex;?>"/>
						<input type="hidden" size="14" name="usercontext" value="notneeded"/>
					</td>
				</tr>
	<?php 
		break;
		case "dahdi":
	?>
				<tr>
					<td>
						<a href=# class="info"><?php echo _("DAHDi Identifier")?><span><?php echo _("DAHDi channels are referenced either by a group number or channel number (which is defined in chan_dahdi.conf).  <br><br>The default setting is <b>g0</b> (group zero).")?></span></a>: 
					</td><td>
						<input type="text" size="8" name="channelid" value="<?php echo htmlspecialchars($channelid) ?>" tabindex="<?php echo ++$tabindex;?>"/>
						<input type="hidden" size="14" name="usercontext" value="notneeded"/>
					</td>
				</tr>
	<?php 
		break;
		case "enum":
		break;
    //--------------------------------------------------------------------------------------
    // Added to enable the unsupported misdn module
		case "misdn":
      if (function_exists('misdn_groups_ports')) {
  ?> 
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
  <?php  
      }
    break; 
    //--------------------------------------------------------------------------------------
		case "custom":
	?>
				<tr>
					<td>
						<a href=# class="info"><?php echo _("Custom Dial String")?><span><?php echo _("Define the custom Dial String.  Include the token")?> $OUTNUM$ <?php echo _("wherever the number to dial should go.<br><br><b>examples:</b><br>")?>CAPI/XXXXXXXX/$OUTNUM$<br>H323/$OUTNUM$@XX.XX.XX.XX<br>OH323/$OUTNUM$@XX.XX.XX.XX:XXXX<br>vpb/1-1/$OUTNUM$</span></a>: 
					</td><td>
						<input type="text" size="35" maxlength="46" name="channelid" value="<?php echo htmlspecialchars($channelid) ?>" tabindex="<?php echo ++$tabindex;?>"/>
						<input type="hidden" size="14" name="usercontext" value="notneeded"/>
					</td>
				</tr>	
	<?php
		break;
		case "dundi":
	?>
				<tr>
					<td>
						<a href=# class="info"><?php echo _("DUNDi Mapping")?><span><?php echo _("This is the name of the DUNDi mapping as defined in the [mappings] section of remote dundi.conf peers. This corresponds to the 'include' section of the peer details in the local dundi.conf file. This requires manual configuration of DUNDi to use this trunk.")?></span></a>: 
					</td><td>
						<input type="text" size="35" maxlength="46" name="channelid" value="<?php echo htmlspecialchars($channelid) ?>" tabindex="<?php echo ++$tabindex;?>"/>
						<input type="hidden" size="14" name="usercontext" value="notneeded"/>
					</td>
				</tr>	
	<?php
		break;
		default:
	?>
				<tr>
					<td>
						<a href=# class="info"><?php echo _("Trunk Name")?><span><?php echo _("Give this trunk a unique name.  Example: myiaxtel")?></span></a>: 
					</td><td>
						<input type="text" size="14" name="channelid" value="<?php echo htmlspecialchars($channelid) ?>" tabindex="<?php echo ++$tabindex;?>"/>
					</td>
				</tr>
				<tr>
					<td colspan="2">
					<a href=# class="info"><?php echo _("PEER Details")?><span><?php echo _("Modify the default PEER connection parameters for your VoIP provider.<br><br>You may need to add to the default lines listed below, depending on your provider.<br /><br />WARNING: Order is important as it will be retained. For example, if you use the \"allow/deny\" directives make sure deny comes first.")?></span></a>: 
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<textarea rows="10" cols="40" name="peerdetails" tabindex="<?php echo ++$tabindex;?>"><?php echo htmlspecialchars($peerdetails) ?></textarea>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<h4><?php echo _("Incoming Settings")?></h4>
					</td>
				</tr>
				<tr>
					<td>
						<a href=# class="info"><?php echo _("USER Context")?><span><?php echo _("This is most often the account name or number your provider expects.<br><br>This USER Context will be used to define the below user details.")?></span></a>: 
					</td><td>
						<input type="text" size="14" name="usercontext" value="<?php echo htmlspecialchars($usercontext)  ?>" tabindex="<?php echo ++$tabindex;?>"/>
					</td>
				</tr>
				<tr>
					<td colspan="2">
					<a href=# class="info"><?php echo _("USER Details")?><span><?php echo _("Modify the default USER connection parameters for your VoIP provider.")?><br><br><?php echo _("You may need to add to the default lines listed below, depending on your provider..<br /><br />WARNING: Order is important as it will be retained. For example, if you use the \"allow/deny\" directives make sure deny 
				comes first.")?></span></a>: 
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<textarea rows="10" cols="40" name="userconfig" tabindex="<?php echo ++$tabindex;?>"><?php echo htmlspecialchars($userconfig); ?></textarea>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<h4><?php echo _("Registration")?></h4>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<a href=# class="info"><?php echo _("Register String")?><span><?php echo _("Most VoIP providers require your system to REGISTER with theirs. Enter the registration line here.<br><br>example:<br><br>username:password@switch.voipprovider.com.<br><br>Many providers will require you to provide a DID number, ex: username:password@switch.voipprovider.com/didnumber in order for any DID matching to work.")?></span></a>: 
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<input type="text" size="40" name="register" value="<?php echo htmlspecialchars($register) ?>" tabindex="<?php echo ++$tabindex;?>" />
					</td>
				</tr>
	<?php 
		break;
	}
  // implementation of module hook
  // object was initialized in config.php
  echo $module_hook->hookHtml;
  ?>
			<tr>
				<td colspan="2">
          <h6>
            <input name="Submit" type="submit" value="<?php echo _("Submit Changes")?>" tabindex="<?php echo ++$tabindex;?>">
            <input name="copytrunk" type="submit" value="<?php echo _("Duplicate Trunk");?>"/>
            <!--input type="button" id="page_reload" value="<?php echo _("Refresh Page");?>"/-->
          </h6>
				</td>
			</tr>
			</table>

<script language="javascript">
<!--

$(document).ready(function(){
  /* Add a Custom Var / Val textbox */
  $("#dial-pattern-add").click(function(){
    addCustomField('','','',$("#last_row"));
  });
  $('#pattern_file').hide();
  $("#dial-pattern-clear").click(function(){
    clearAllPatterns();
  });
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

function addCustomField(prepend_digit, pattern_prefix, pattern_pass, start_loc) {
  var idx = $(".dial-pattern").size();
  var idxp = idx - 1;
  var tabindex = parseInt($("#pattern_pass_"+idxp).attr('tabindex')) + 1;
  var tabindex1 = tabindex + 2;
  var tabindex2 = tabindex + 3;
  var dpt_title = 'dpt-title dpt-display';
  var dpt_prepend_digit = prepend_digit == '' ? dpt_title : 'dpt-value';
  var dpt_pattern_prefix = pattern_prefix == '' ? dpt_title : 'dpt-value';
  var dpt_pattern_pass = pattern_pass == '' ? dpt_title : 'dpt-value';

  var new_insert = start_loc.before('\
  <tr>\
    <td colspan="2">\
    (<input title="<?php echo $pp_tit?>" type="text" size="10" id="prepend_digit_'+idx+'" name="prepend_digit['+idx+']" class="dp-prepend dial-pattern '+dpt_prepend_digit+'" value="'+prepend_digit+'" tabindex="'+tabindex+'">) +\
    <input title="<?php echo $pf_tit?>" type="text" size="6" id="pattern_prefix_'+idx+'" name="pattern_prefix['+idx+']" class="dp-prefix '+dpt_pattern_prefix+'" value="'+pattern_prefix+'" tabindex="'+tabindex1+'"> |\
    <input title="<?php echo $mp_tit?>" type="text" size="16" id="pattern_pass_'+idx+'" name="pattern_pass['+idx+']" class="dp-match '+dpt_pattern_pass+'" value="'+pattern_pass+'" tabindex="'+tabindex2+'">\
      <img src="images/core_add.png" style="cursor:pointer; float:none; margin-left:0px; margin-bottom:-3px;" alt="<?php echo _("insert")?>" title="<?php echo _("Click here to insert a new pattern")?>" onclick="addCustomField(\'\',\'\',\'\',$(\'#prepend_digit_'+idx+'\').parent().parent())">\
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

function clearPatterns() {
  $(".dpt-display").each(function() {
    if($(this).val() == $(this).data("defText")) {
      $(this).val("");
    }
  });
  return true;
}

function clearAllPatterns() {

  $(".dpt-value").addClass('dpt-title dpt-nodisplay').removeClass('dpt-value').mouseover(function(){
    $(this).toggleVal({
      populateFrom: "title",
      changedClass: "text-normal",
      focusClass: "text-normal"
    }).removeClass('dpt-nodisplay').addClass('dpt-display').unbind('mouseover');
  }).each(function(){
    $(this).val("");
  });

  return true;
}

// all blanks are ok
function validatePatterns() {
  var culprit;
  var msgInvalidDialPattern;
  defaultEmptyOK = true;

  // TODO: need to validate differently for prepend, prefix and match fields. The prepend
  //      must be a dialable digit. The prefix can be any pattern but not contain "." and
  //      the pattern can contain a "." also
  //$filter_prepend = '/[^0-9\+\*\#/';
  //$filter_match = '/[^0-9\-\+\*\#\.\[\]xXnNzZ]/';
  //$filter_prefix = '/[^0-9\-\+\*\#\[\]xXnNzZ]/';
	//defaultEmptyOK = false;
  /* TODO: get some sort of check in for dialpatterns
	if (!isDialpattern(theForm.dialpattern.value))
		return warnInvalid(theForm.dialpattern, msgInvalidDialPattern);
    */

  $(".dp-prepend").each(function() {
    if ($.trim(this.value) == '') {
    } else if (this.value.search('[^0-9*#+wW\s]+') >= 0) {
      culprit = this;
      return false;
    }
  });
  if (!culprit) {
    $(".dp-prefix").each(function() {
      if ($.trim($(this).val()) == '') {
      } else if (!isDialpattern(this.value) || this.value.search('[._]+') >= 0) {
        culprit = this;
        return false;
      }
    });
  }
  if (!culprit) {
    $(".dp-match").each(function() {
      if ($.trim(this.value) == '') {
      } else if (!isDialpattern(this.value) || this.value.search('[_]+') >= 0) {
        culprit = this;
        return false;
      }
    });
  }

  if (culprit != undefined) {
	  msgInvalidDialPattern = "<?php echo _('Dial pattern is invalid'); ?>";
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

document.trunkEdit.trunk_name.focus();

function trunkEdit_onsubmit(act) {
  var theForm = document.trunkEdit;

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

	defaultEmptyOK = true;

	if (isEmpty($.trim(theForm.outcid.value))) {
	  if (theForm.keepcid.value == 'on' || theForm.keepcid.value == 'all') {
		  return warnInvalid(theForm.outcid, msgCIDValueRequired);
      } else {
				if (confirm(msgCIDValueEmpty) == false) {
				  return false;
      }
    }
  }

	if (!isCallerID(theForm.outcid.value))
		return warnInvalid(theForm.outcid, msgInvalidOutboundCID);
	
	if (!isInteger(theForm.maxchans.value))
		return warnInvalid(theForm.maxchans, msgInvalidMaxChans);
	
	if (!isDialIdentifierSpecial(theForm.dialoutprefix.value)) {
    if (confirm(msgInvalidOutboundDialPrefix) == false) {
      $('#dialoutprefix').focus();
      return false;
    }
  }
	
	<?php if ($tech != "enum" && $tech != "custom" && $tech != "dundi") { ?>
	defaultEmptyOK = true;
	if (isEmpty(theForm.channelid.value) || isWhitespace(theForm.channelid.value))
		return warnInvalid(theForm.channelid, msgInvalidTrunkName);
	
	if (theForm.channelid.value == theForm.usercontext.value)
		return warnInvalid(theForm.usercontext, msgInvalidTrunkAndUserSame);
	<?php } else if ($tech == "custom" || $tech == "dundi") { ?> 
	if (isEmpty(theForm.channelid.value) || isWhitespace(theForm.channelid.value)) 
		return warnInvalid(theForm.channelid, msgInvalidChannelName); 

	if (theForm.channelid.value == theForm.usercontext.value) 
		return warnInvalid(theForm.usercontext, msgInvalidTrunkAndUserSame);
	<?php } ?>

	<?php if ($tech == "sip" || substr($tech,0,3) == "iax") { ?>
	if ((isEmpty(theForm.usercontext.value) || isWhitespace(theForm.usercontext.value)) && 
		(!isEmpty(theForm.userconfig.value) && !isWhitespace(theForm.userconfig.value)) &&
			(theForm.userconfig.value != "secret=***password***\ntype=user\ncontext=from-trunk")) {
				if (confirm(msgConfirmBlankContext) == false)
				return false;
			}
	<?php } ?>

  clearPatterns();
  if (validatePatterns()) {
	  theForm.action.value = act;
	  return true;
  } else {
    return false;
  }
}

function isDialIdentifierSpecial(s) { // special chars allowed in dial prefix (e.g. fwdOUT)
    var i;

    if (isEmpty(s)) 
       if (isDialIdentifierSpecial.arguments.length == 1) return defaultEmptyOK;
       else return (isDialIdentifierSpecial.arguments[1] == true);

    for (i = 0; i < s.length; i++)
    {   
        var c = s.charAt(i);

        if ( !isDialDigitChar(c) && (c != "w") && (c != "W") && (c != "q") && (c != "Q") && (c != "+") ) return false;
    }

    return true;
}
//-->
</script>

		</form>
<?php  
}
?>



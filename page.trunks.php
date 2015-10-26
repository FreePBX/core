<?php /* $Id$ */
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2006-2015 Schmooze Com Inc.
//
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
$request = $_REQUEST;
$display='trunks';
$extdisplay=isset($_REQUEST['extdisplay'])?$request['extdisplay']:'';
$trunknum = ltrim($extdisplay,'OUT_');

$action = isset($request['action'])?$request['action']:'';
// Now check if the Copy Trunks submit button was pressed, in which case we duplicate the trunk
//
if (isset($request['copytrunk'])) {
	$action = 'copytrunk';
}

$tech         = strtolower(isset($request['tech'])?htmlentities($request['tech'],ENT_COMPAT | ENT_HTML401, "UTF-8"):'');
$outcid       = isset($request['outcid'])?$request['outcid']:'';
$maxchans     = isset($request['maxchans'])?$request['maxchans']:'';
$dialoutprefix= isset($request['dialoutprefix'])?$request['dialoutprefix']:'';
$channelid    = isset($request['channelid'])?$request['channelid']:'';
$peerdetails  = isset($request['peerdetails'])?$request['peerdetails']:'';
$usercontext  = isset($request['usercontext'])?$request['usercontext']:'';
$userconfig   = isset($request['userconfig'])?$request['userconfig']:'';
$register     = isset($request['register'])?$request['register']:'';
$keepcid      = isset($request['keepcid'])?$request['keepcid']:'off';
$disabletrunk = isset($request['disabletrunk'])?$request['disabletrunk']:'off';
$continue     = isset($request['continue'])?$request['continue']:'off';
$provider     = isset($request['provider'])?$request['provider']:'';
$trunk_name   = isset($request['trunk_name'])?$request['trunk_name']:'';

$failtrunk    = isset($request['failtrunk'])?$request['failtrunk']:'';
$failtrunk_enable = ($failtrunk == "")?'':'CHECKED';

$dialopts     = isset($request['dialopts'])?$request['dialopts']:false;

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
} else if (isset($_POST["bulk_patterns"])) {
	$prepend = '/^([^+]*)\+/';
    $prefix = '/^([^|]*)\|/';
    $match_pattern = '/([^/]*)/';
    $callerid = '/\/(.*)$/';

	$data = explode("\n",$_POST['bulk_patterns']);
	foreach($data as $list) {
		if (preg_match('/^\s*$/', $list)) {
			continue;
		}

		$this_prepend = $this_prefix = $this_callerid = '';

		if (preg_match($prepend, $list, $matches)) {
			$this_prepend = $matches[1];
			$list = preg_replace($prepend, '', $list);
		}

		if (preg_match($prefix, $list, $matches)) {
			$this_prefix = $matches[1];
			$list = preg_replace($prefix, '', $list);
		}

		if (preg_match($callerid, $list, $matches)) {
			$this_callerid = $matches[1];
			$list = preg_replace($callerid, '', $list);
		}

		$dialpattern_insert[] = array(
			'prepend_digits' => htmlspecialchars(trim($this_prepend)),
			'match_pattern_prefix' => htmlspecialchars(trim($this_prefix)),
			'match_pattern_pass' => htmlspecialchars(trim($list)),
			'match_cid' => htmlspecialchars(trim($this_callerid)),
		);

		$i++;
	}
}


// TODO: remember old name, if new one is different the don't rename
//
//if submitting form, update database
switch ($action) {
	case "copytrunk":
		$sv_channelid    = isset($request['sv_channelid'])?$request['sv_channelid']:'';
		$sv_trunk_name    = isset($request['sv_trunk_name'])?$request['sv_trunk_name']:'';
		$sv_usercontext    = isset($request['sv_usercontext'])?$request['sv_usercontext']:'';

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
		$continue = 'on';
		$trunknum = '';
		$extdisplay='';
	// Fallthrough to addtrunk now...
	//
	case "addtrunk":
		if($tech == 'pjsip') {
			$channelid = !empty($request['trunk_name']) ? $request['trunk_name'] : '';
		}
		$trunknum = core_trunks_add($tech, $channelid, $dialoutprefix, $maxchans, $outcid, $peerdetails, $usercontext, $userconfig, $register, $keepcid, trim($failtrunk), $disabletrunk, $trunk_name, $provider, $continue, $dialopts);

		core_trunks_update_dialrules($trunknum, $dialpattern_insert);
		needreload();
		redirect_standard();
	break;
	case "edittrunk":
		if($tech == 'pjsip') {
			$channelid = !empty($request['trunk_name']) ? $request['trunk_name'] : '';
		}
		core_trunks_edit($trunknum, $channelid, $dialoutprefix, $maxchans, $outcid, $peerdetails, $usercontext, $userconfig, $register, $keepcid, trim($failtrunk), $disabletrunk, $trunk_name, $provider, $continue, $dialopts);

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
		if (preg_match("/^([2-9]\d\d)-?([2-9]\d\d)$/", $request["npanxx"], $matches)) {
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
				$errormsg = _("Error fetching prefix list for: "). $request["npanxx"];
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

//get existing trunk info
$tresults = core_trunks_getDetails();

$trunks = array();
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
		case 'pjsip':
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
	$trunks[] = array(
		'label' => $label,
		'background' => $background,
		'tresult' => $tresult
	);
}
$displayvars = array(
	'extdisplay' => $extdisplay,
	'display' => $display,
	'trunks' => $trunks,
	'trunknum' => $trunknum
);
show_view(dirname(__FILE__).'/views/trunks/header.php',$displayvars);

$sipdriver = FreePBX::create()->Config->get_conf_setting('ASTSIPDRIVER');


if (!$tech && !$extdisplay) {
	$trunk_types = \FreePBX::Core()->listTrunkTypes();

	$displayvars['trunk_types'] = $trunk_types;
	show_view(dirname(__FILE__).'/views/trunks/main.php',$displayvars);
} else {
	if ($extdisplay) {
		$trunk_details = core_trunks_getDetails($trunknum);

		$tech = htmlentities($trunk_details['tech'],ENT_COMPAT | ENT_HTML401, "UTF-8");
		$outcid = htmlentities($trunk_details['outcid'],ENT_COMPAT | ENT_HTML401, "UTF-8");
		$maxchans = htmlentities($trunk_details['maxchans'],ENT_COMPAT | ENT_HTML401, "UTF-8");
		$dialoutprefix = htmlentities($trunk_details['dialoutprefix'],ENT_COMPAT | ENT_HTML401, "UTF-8");
		$keepcid = htmlentities($trunk_details['keepcid'],ENT_COMPAT | ENT_HTML401, "UTF-8");
		$failtrunk = htmlentities($trunk_details['failscript'],ENT_COMPAT | ENT_HTML401, "UTF-8");
		$failtrunk_enable = ($failtrunk == "")?'':'CHECKED';
		$disabletrunk = htmlentities($trunk_details['disabled'],ENT_COMPAT | ENT_HTML401, "UTF-8");
		$continue = htmlentities($trunk_details['continue'],ENT_COMPAT | ENT_HTML401, "UTF-8");
		$provider = $trunk_details['provider'];
		$trunk_name = htmlentities($trunk_details['name'],ENT_COMPAT | ENT_HTML401, "UTF-8");
		$dialopts = $trunk_details['dialopts'] === false ? false : htmlentities($trunk_details['dialopts'],ENT_COMPAT | ENT_HTML401, "UTF-8");

		if ($tech!="enum") {

			$channelid = htmlentities($trunk_details['channelid'],ENT_COMPAT | ENT_HTML401, "UTF-8");

			if ($tech!="custom" && $tech!="dundi") {  // custom trunks will not have user/peer details in database table
				// load from db
				if (empty($peerdetails)) {
					$peerdetails = core_trunks_getTrunkPeerDetails($trunknum);
				}
				if (empty($usercontext)) {
					$usercontext = htmlentities($trunk_details['usercontext'],ENT_COMPAT | ENT_HTML401, "UTF-8");
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

		// find which routes use this trunk
		$routes = core_trunks_gettrunkroutes($trunknum);
		$num_routes = count($routes);
		if($num_routes > 0) {
			$usage_list = framework_display_destination_usage(core_getdest(ltrim($extdisplay,'OUT_')));
		}
	} else { // 'Add New Trunk' selected.
		// Generic defaults
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
	}
	if (!isset($dialpattern_array)) {
		$dialpattern_array = array();
	}

	switch ($tech) {
		case 'dundi':
			$helptext = _('FreePBX offers limited support for DUNDi trunks and additional manual configuration is required. The trunk name should correspond to the [mappings] section of the remote dundi.conf systems. For example, you may have a mapping on the remote system, and corresponding configurations in dundi.conf locally, that looks as follows:<br /><br />[mappings]<br />priv => dundi-extens,0,IAX2,priv:${SECRET}@218.23.42.26/${NUMBER},nopartial<br /><br />In this example, you would create this trunk and name it priv. You would then create the corresponding IAX2 trunk with proper settings to work with DUNDi. This can be done by making an IAX2 trunk in FreePBX or by using the iax_custom.conf file.<br />The dundi-extens context in this example must be created in extensions_custom.conf. This can simply include contexts such as ext-local, ext-intercom-users, ext-paging and so forth to provide access to the corresponding extensions and features provided by these various contexts and generated by FreePBX.');
		break;
		case 'pjsip':
		case 'sip':
		break;
		default:
		$helptext = '';
	}

	$displayvars = array(
		'extdisplay' => $extdisplay,
		'display' => $display,
		'tech' => $tech,
		'provider' => $provider,
		'usercontext' => $usercontext,
		'channelid' => $channelid,
		'trunk_name' => $trunk_name,
		'tabindex' => &$tabindex,
		'outcid' => $outcid,
		'keepcid' => $keepcid,
		'pr_tech' => $pr_tech,
		'maxchans' => $maxchans,
		'data' => $data,
		'dialopts' => $dialopts,
		'dialoutprefix' => $dialoutprefix,
		'amp_conf' => $amp_conf,
		'continue' => $continue,
		'disabletrunk' => $disabletrunk,
		'failtrunk_enable' => $failtrunk_enable,
		'failtrunk' => $failtrunk,
		'dialpattern_array' => $dialpattern_array,
		'dialoutprefix' => $dialoutprefix,
		'num_routes' => $num_routes,
		'routes' => $routes,
		'helptext' => $helptext
	);
	show_view(dirname(__FILE__).'/views/trunks/trunk_header.php',$displayvars);

	switch ($tech) {
		case "zap":
			show_view(dirname(__FILE__).'/views/trunks/zap.php',$displayvars);
			break;
		case "dahdi":
			show_view(dirname(__FILE__).'/views/trunks/dahdi.php',$displayvars);
			break;
		case "enum":
			break;
		//--------------------------------------------------------------------------------------
		// Added to enable the unsupported misdn module
		case "misdn":
			if (function_exists('misdn_groups_ports')) {
				show_view(dirname(__FILE__).'/views/trunks/misdn.php',$displayvars);
			}
			break;
		//--------------------------------------------------------------------------------------
		case "custom":
			show_view(dirname(__FILE__).'/views/trunks/custom.php',$displayvars);
			break;
		case "dundi":
			show_view(dirname(__FILE__).'/views/trunks/dundi.php',$displayvars);
			break;
		case "pjsip":
			$pjsip = FreePBX::Core()->getDriver('pjsip');
			if($pjsip !== false) {
				// Mangle displayvars if needed.
				$displayvars = $pjsip->getDisplayVars($extdisplay, $displayvars);
				show_view(dirname(__FILE__).'/views/trunks/pjsip.php',$displayvars);
			}
			break;
		case "iax":
		case "iax2":
		case "sip":
			$displayvars['peerdetails'] = $peerdetails;
			$displayvars['usercontext'] = $usercontext;
			$displayvars['userconfig'] = $userconfig;
			$displayvars['register'] = $register;
			$displayvars['peerdetails'] = $peerdetails;
			show_view(dirname(__FILE__).'/views/trunks/sip.php',$displayvars);
			break;
		default:
			break;
	}
	// implementation of module hook
	$module_hook == moduleHook::create();
	echo $module_hook->hookHtml;
	show_view(dirname(__FILE__).'/views/trunks/trunk_footer.php',$displayvars);
}

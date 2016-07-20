<?php
/**
 * Core Dial Pattern CSV Export
 */
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }

$extdisplay = isset($_REQUEST['extdisplay']) && !empty($_REQUEST['extdisplay']) ? $_REQUEST['extdisplay'] : '';
$display = isset($_REQUEST['display']) && !empty($_REQUEST['display']) ? $_REQUEST['display'] : '';

$csvdata = array();
$header = array();
global $db;
switch($display) {
    case "routing":
        //Setup Column headers.
        $header[0] = array("prepend", "prefix", "match pattern" , "callerid");

        //Get route name
        if(!empty($extdisplay)) {
					$sql = "SELECT name FROM outbound_routes WHERE `route_id` = '".$db->escapeSimple($extdisplay)."'";
					$name = sql($sql,'getOne');
					if(isset($name) && !empty($name)) {
							//Get all dial patterns for this route
							$sql = "SELECT prepend_digits, match_pattern_prefix, match_pattern_pass, match_cid  FROM outbound_route_patterns WHERE `route_id` = '".$db->escapeSimple($extdisplay)."'";
							$csvdata = sql($sql,'getAll');
					}
				} else {
					$csvdata = array();
				}
        break;
    case "trunks":
        $extdisplay = str_replace('OUT_','',$extdisplay);
        //Setup Column headers.
        $header[0] = array("prepend", "prefix", "match pattern");

				if(!empty($extdisplay)) {
	        //Get trunk name
	        $sql = "SELECT name FROM trunks WHERE `trunkid` = '".$db->escapeSimple($extdisplay)."'";
	        $name = sql($sql,'getOne');
	        if(isset($name) && !empty($name)) {
	            //Get all dial patterns for this trunk
	            $sql = "SELECT prepend_digits, match_pattern_prefix, match_pattern_pass  FROM trunk_dialpatterns WHERE `trunkid` = '".$db->escapeSimple($extdisplay)."'";
	            $csvdata = sql($sql,'getAll');
	        }
				} else {
					$csvdata = array();
				}
        break;
}

if(!empty($header)) {
    $final_data = array_merge($header,$csvdata); //Merge headers and data

    header("Content-type: text/csv"); //Declare to browser this is a CSV file
    header('Content-Disposition: attachment; filename="'.$name.'_'.$display.'_dial_patterns.csv"'); //Tell the browser it's an attachment (meaning don't display it)
    header("Cache-Control: no-cache, must-revalidate"); //No caching HTML 1.1 uhh who uses that!?
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); //No Caching set date to past.....

    $outstream = fopen("php://output",'w'); //Set PHP output to stream

    foreach ($final_data as $fields) {
        fputcsv($outstream, $fields); //Stream each line out
    }

    fclose($outstream); //Close stream
}

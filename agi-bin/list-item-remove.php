#!/usr/bin/php -q
<?php

/*
Removes an item from a character delimited list.

Usage: list-item-remove.php list item varname [listseparator]

list: The list of strings separated by a character (example: 1&2&3)
item: The value of the item to remove
varname: The variable to return the new list in
listseparator: The separator.  This defaults to "&" if it is not specified.
*/

define("AGIBIN_DIR", dirname(__FILE__));

include(AGIBIN_DIR."/phpagi.php");

$agi = new AGI;

if (!isset($argv[1])) {
        $agi->verbose("Missing list");
        exit(1);
}

if (!isset($argv[2])) {
        $agi->verbose("Missing item");
        exit(1);
}

if (!isset($argv[3])) {
        $agi->verbose("Missing return var name");
        exit(1);
}

$arglist = $argv[1];
$argitem = $argv[2];
$argvarname = $argv[3];

if (isset($argv[4])) {
        $argsep = "&";
} else {
        $argsep = $argv[4];
}

$newlist = str_replace($argitem.$argsep, "", $arglist.$argsep);

if (substr($newlist, -1, 1) == $argsep) {
        $newlist = substr($newlist, 0, -1);
}

$agi->set_variable($argvarname, $newlist);
?>

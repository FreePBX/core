<?php

$fcc = new featurecode('core', 'userlogon');
$fcc->setDescription('User Logon');
$fcc->setDefault('*11');
$fcc->update();
unset($fcc);

$fcc = new featurecode('core', 'userlogoff');
$fcc->setDescription('User Logoff');
$fcc->setDefault('*12');
$fcc->update();
unset($fcc);

$fcc = new featurecode('core', 'zapbarge');
$fcc->setDescription('ZapBarge');
$fcc->setDefault('888');
$fcc->update();
unset($fcc);

$fcc = new featurecode('core', 'simu_pstn');
$fcc->setDescription('Simulate Incoming Call');
$fcc->setDefault('7777');
$fcc->update();
unset($fcc);

$fcc = new featurecode('core', 'simu_fax');
$fcc->setDescription('Simulate Incoming FAX Call');
$fcc->setDefault('666');
$fcc->update();
unset($fcc);

?>

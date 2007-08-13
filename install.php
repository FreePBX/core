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

$fcc = new featurecode('core', 'chanspy');
$fcc->setDescription('ChanSpy');
$fcc->setDefault('555');
$fcc->update();
unset($fcc);

$fcc = new featurecode('core', 'simu_pstn');
$fcc->setDescription('Simulate Incoming Call');
$fcc->setDefault('7777');
$fcc->update();
unset($fcc);

$fcc = new featurecode('core', 'simu_fax');
$fcc->setDescription('Dial System FAX');
$fcc->setDefault('666');
$fcc->update();
unset($fcc);

$fcc = new featurecode('core', 'pickup');
$fcc->setDescription('Call Pickup (Can be used with GXP-2000)');
$fcc->setDefault('**');
$fcc->update();
unset($fcc);

?>

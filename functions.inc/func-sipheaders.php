<?php

/*
 * Set a SIP Header to be used in the next call.
 */

$c = 'func-set-sipheader'; // Context
$e = 's'; // Exten

$ext->add($c,$e,'', new ext_noop('Sip Add Header function called. Adding ${ARG1} = ${ARG2}'));
$ext->add($c,$e,'', new ext_set('HASH(_SIPHEADERS,${ARG1})', '${ARG2}'));
$ext->add($c,$e,'', new ext_return());

/*
 * Apply a SIP Header to the call that's about to be made
 */

$c = 'func-apply-sipheaders';

$ext->add($c,$e,'', new ext_noop('Applying SIP Headers to channel'));
$ext->add($c,$e,'', new ext_set('SIPHEADERKEYS', '${HASHKEYS(SIPHEADERS)}'));
// TODO: Possibly put some code in here to detect if pjsip or not?  This
// may NOT be a good idea though, if multiple channels are part of the dial
// string, some may be PJSIP, some may be SIP..
$ext->add($c,$e,'', new ext_while('$["${SET(sipkey=${SHIFT(SIPHEADERKEYS)})}" != ""]'));
$ext->add($c,$e,'', new ext_set('sipheader', '${HASH(SIPHEADERS,${sipkey})}'));
$ext->add($c,$e,'', new ext_sipaddheader('${sipkey}', '${sipheader}'));
$ext->add($c,$e,'', new ext_set('PJSIP_HEADER(add,${sipkey})', '${sipheader}'));
$ext->add($c,$e,'', new ext_endwhile(''));
$ext->add($c,$e,'', new ext_return());


<?php
namespace FreePBX\modules\Core\Dialplan;

class funcSipheaders{
	static function add($ext){
		/*
		* Set a SIP Header to be used in the next call.
		*/

		$c = 'func-set-sipheader'; // Context
		$e = 's'; // Exten

		$ext->add($c,$e,'', new \ext_noop('Sip Add Header function called. Adding ${ARG1} = ${ARG2}'));
		$ext->add($c,$e,'', new \ext_set('HASH(__SIPHEADERS,${ARG1})', '${ARG2}'));
		$ext->add($c,$e,'', new \ext_return());

		/*
		* Apply a SIP Header to the call that's about to be made
		*/

		$c = 'func-apply-sipheaders';

		$ext->add($c,$e,'', new \ext_noop('Applying SIP Headers to channel ${CHANNEL}'));
		$ext->add($c,$e,'', new \ext_set('Dchan','${CUT(CHANNEL,/,2)}'));
		$ext->add($c,$e,'', new \ext_set('TECH', '${CUT(CHANNEL,/,1)}'));
		$ext->add($c,$e,'', new \ext_set('SIPHEADERKEYS', '${HASHKEYS(SIPHEADERS)}'));
		$ext->add($c,$e,'', new \ext_while('$["${SET(sipkey=${SHIFT(SIPHEADERKEYS)})}" != ""]'));
		$ext->add($c,$e,'', new \ext_set('sipheader', '${HASH(SIPHEADERS,${sipkey})}'));
		$driver = \FreePBX::Config()->get("ASTSIPDRIVER");
		if (in_array($driver,array("both","chan_sip"))) {
			$ext->add($c,$e,'', new \ext_execif('$["${sipheader}" = "unset" & "${TECH}" = "SIP"]','SIPRemoveHeader','${sipkey}:'));
		}
		if (in_array($driver,array("both","chan_pjsip"))) {
			$ext->add($c,$e,'', new \ext_execif('$["${sipheader}" = "unset" & "${TECH}" = "PJSIP"]','Set','PJSIP_HEADER(remove,${sipkey})='));
		}

		if(\FreePBX::Config()->get('RFC7462')) {
			$ext->add($c,$e,'', new \ext_execif('$["${sipheader}" != "unset" & "${sipkey}" = "Alert-Info" & ${REGEX("^<[^>]*>" ${sipheader})} != 1 & ${REGEX("\;info=" ${sipheader})} != 1]', 'Set', 'sipheader=<http://127.0.0.1>\;info=${sipheader}'));
			$ext->add($c,$e,'', new \ext_execif('$["${sipheader}" != "unset" & "${sipkey}" = "Alert-Info" & ${REGEX("^<[^>]*>" ${sipheader})} != 1]', 'Set', 'sipheader=<http://127.0.0.1>${sipheader}'));
		}

		if(in_array($driver,array("both","chan_sip"))) {
			$ext->add($c,$e,'', new \ext_execif('$["${TECH}" = "SIP" & "${sipheader}" != "unset"]','SIPAddHeader','${sipkey}:${sipheader}'));
		}
		if(in_array($driver,array("both","chan_pjsip"))) {
			$ext->add($c,$e,'', new \ext_execif('$["${TECH}" = "PJSIP" & "${sipheader}" != "unset"]','Set','PJSIP_HEADER(add,${sipkey})=${sipheader}'));
		}
		$ext->add($c,$e,'', new \ext_endwhile(''));
		$ext->add($c,$e,'', new \ext_return());

	}
}

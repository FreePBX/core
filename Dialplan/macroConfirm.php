<?php
namespace FreePBX\modules\Core\Dialplan;

class macroConfirm{
    static function add($ext){
        $config = \FreePBX::Config();
        /*
        ;------------------------------------------------------------------------
        ; [macro-confirm]
        ;------------------------------------------------------------------------
        ; CONTEXT:      macro-confirm
        ; PURPOSE:      added default message if none supplied
        ;
        ; Follom-Me and Ringgroups provide an option to supply a message to be
        ; played as part of the confirmation. These changes have added a default
        ; message if none is supplied.
        ;
        ;------------------------------------------------------------------------
        */
        $context = 'macro-confirm';
        $exten = 's';

        $ext->add($context, $exten, '', new \ext_setvar('LOOPCOUNT','0'));
        $ext->add($context, $exten, '', new \ext_setvar('__MACRO_RESULT','ABORT'));
        //FREEPBX-15217 QUEUE call confirm -> default voice prompt can not override the findmefollowme confirm file
        //if the {ALT_CONFIRM_MSG}= default| then we should play default msg
        $ext->add($context, $exten, '', new \ext_noop('${ALT_CONFIRM_MSG} and arv= ${ARG1}'));
        $ext->add($context, $exten, '', new \ext_execif('$["${ALT_CONFIRM_MSG}"="default"]', 'Set', 'ARG1='));
        $ext->add($context, $exten, '', new \ext_execif('$["${ALT_CONFIRM_MSG}"="default"]', 'Set', 'ALT_CONFIRM_MSG='));
        $ext->add($context, $exten, '', new \ext_execif('$["${CBKLANGUAGE}"=""]', 'Set', '__CBKLANGUAGE=${CHANNEL(language)}'));
        $ext->add($context, $exten, '', new \ext_setvar('MSG1','${IF($["${ARG1}${ALT_CONFIRM_MSG}"=""]?incoming-call-1-accept-2-decline:${IF($[${LEN(${ALT_CONFIRM_MSG})}>0]?${ALT_CONFIRM_MSG}:${ARG1})})}'));
        $ext->add($context, $exten, 'start', new \ext_background('${MSG1},m,${CBKLANGUAGE},macro-confirm'));
        $ext->add($context, $exten, '', new \ext_read('INPUT', '', 1, '', '', 4));
        $ext->add($context, $exten, '', new \ext_gotoif('$[${LEN(${INPUT})} > 0]', '${INPUT},1', 't,1'));

        $exten = '1';
        if ($config->get('AST_FUNC_SHARED')) {
		$ext->add($context, $exten, '', new \ext_gotoif('$["${DB_EXISTS(RG/${ARG3}/${UNIQCHAN})}"="0"  & "${SHARED(ANSWER_STATUS,${FORCE_CONFIRM})}"=""]', 'toolate,1'));
		$ext->add($context, $exten, '', new \ext_gotoif('$["${BLKVM_CHANNEL}" !="" & "${DB_EXISTS(RG/${ARG3}/${UNIQCHAN})}"="0" & "${SHARED(ANSWER_STATUS,${BLKVM_CHANNEL})}"=""]', 'toolate,1'));
		$ext->add($context, $exten, '', new \ext_setvar("cfchannel", '${IF($[${REGEX("/from-queue/" ${UNIQCHAN})}]?${UNIQCHAN}:${BLKVM_CHANNEL})}'));
		$ext->add($context, $exten, '', new \ext_gotoif('$["${SHARED(BLKVM,${cfchannel})}"="" & "${QCALLBACK}"=""]', 'toolate,1'));
        } else {
            $ext->add($context, $exten, '', new \ext_gotoif('$["${FORCE_CONFIRM}" != ""]', 'skip'));
            $ext->add($context, $exten, '', new \ext_gotoif('$["${DB_EXISTS(RG/${ARG3}/${UNIQCHAN})}"="0"]', 'toolate,1'));
        }
        $ext->add($context, $exten, '', new \ext_dbdel('RG/${ARG3}/${UNIQCHAN}'));
        $ext->add($context, $exten, '', new \ext_macro('blkvm-clr'));
        if ($config->get('AST_FUNC_SHARED')) {
            $ext->add($context, $exten, '', new \ext_setvar('SHARED(ANSWER_STATUS,${FORCE_CONFIRM})',''));
        }
        $ext->add($context, $exten, 'skip', new \ext_setvar('__MACRO_RESULT',''));
        $ext->add($context, $exten, '', new \ext_execif('$[("${MOHCLASS}"!="default") & ("${MOHCLASS}"!="")]', 'Set', 'CHANNEL(musicclass)=${MOHCLASS}'));
        $ext->add($context, $exten, 'exitopt1', new \ext_macroexit());

        $exten = '2';
        $ext->add($context, $exten, '', new \ext_goto(1, 'noanswer'));

        $exten = '3';
        $ext->add($context, $exten, '', new \ext_saydigits('${CALLCONFIRMCID}'));
        if ($config->get('AST_FUNC_SHARED')) {
            $ext->add($context, $exten, '', new \ext_gotoif('$["${DB_EXISTS(RG/${ARG3}/${UNIQCHAN})}"="0" & "${SHARED(ANSWER_STATUS,${FORCE_CONFIRM})}"=""]', 'toolate,1','s,start'));
        } else {
            $ext->add($context, $exten, '', new \ext_gotoif('$["${DB_EXISTS(RG/${ARG3}/${UNIQCHAN})}"="0" & "${FORCE_CONFIRM}"=""]', 'toolate,1','s,start'));
        }

        $exten = 't';
        if ($config->get('AST_FUNC_SHARED')) {
            $ext->add($context, $exten, '', new \ext_gotoif('$["${DB_EXISTS(RG/${ARG3}/${UNIQCHAN})}"="0" & "${SHARED(ANSWER_STATUS,${FORCE_CONFIRM})}"=""]', 'toolate,1'));
        } else {
            $ext->add($context, $exten, '', new \ext_gotoif('$["${DB_EXISTS(RG/${ARG3}/${UNIQCHAN})}"="0" & "${FORCE_CONFIRM}"=""]', 'toolate,1'));
        }
        $ext->add($context, $exten, '', new \ext_setvar('LOOPCOUNT','$[ ${LOOPCOUNT} + 1 ]'));
        $ext->add($context, $exten, '', new \ext_gotoif('$[ ${LOOPCOUNT} < 5 ]', 's,start','noanswer,1'));

        $exten = '_X';

        $ext->add($context, $exten, '', new \ext_background('invalid,m,${CHANNEL(language)},macro-confirm'));

        if ($config->get('AST_FUNC_SHARED')) {
            $ext->add($context, $exten, '', new \ext_gotoif('$["${DB_EXISTS(RG/${ARG3}/${UNIQCHAN})}"="0" | "${SHARED(ANSWER_STATUS,${FORCE_CONFIRM})}"=""]', 'toolate,1'));
        } else {
            $ext->add($context, $exten, '', new \ext_gotoif('$["${DB_EXISTS(RG/${ARG3}/${UNIQCHAN})}"="0" & "${FORCE_CONFIRM}"=""]', 'toolate,1'));
        }
        $ext->add($context, $exten, '', new \ext_setvar('LOOPCOUNT','$[ ${LOOPCOUNT} + 1 ]'));
        $ext->add($context, $exten, '', new \ext_gotoif('$[ ${LOOPCOUNT} < 5 ]', 's,start','noanswer,1'));

        $exten = 'noanswer';
        $ext->add($context, $exten, '', new \ext_setvar('__MACRO_RESULT','ABORT'));
        $ext->add($context, $exten, 'exitnoanswer', new \ext_macroexit());

        $exten = 'toolate';
        $ext->add($context, $exten, '', new \ext_setvar('MSG2','${IF($["foo${ARG2}" != "foo"]?${ARG2}:"incoming-call-no-longer-avail")}'));
        $ext->add($context, $exten, '', new \ext_playback('${MSG2}'));
        $ext->add($context, $exten, '', new \ext_setvar('__MACRO_RESULT','ABORT'));
        $ext->add($context, $exten, 'exittoolate', new \ext_macroexit());

        $exten = 'h';
        $ext->add($context, $exten, '', new \ext_macro('hangupcall'));
    }
}

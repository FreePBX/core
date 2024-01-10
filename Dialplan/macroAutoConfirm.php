<?php
namespace FreePBX\modules\Core\Dialplan;

class macroAutoConfirm{
    static function add($ext){
        $config = \FreePBX::Config();

        /*
        ;------------------------------------------------------------------------
        ; [macro-auto-confirm]
        ;------------------------------------------------------------------------
        ; This macro is called from ext-local-confirm to auto-confirm a call so that other extensions
        ; are aware that the call has been answered.
        ;
        ;------------------------------------------------------------------------
        */
        $context = 'macro-auto-confirm';
        $exten = 's';
        $ext->add($context, $exten, '', new \ext_setvar('__MACRO_RESULT',''));
        $ext->add($context, $exten, '', new \ext_set('CFIGNORE',''));
        $ext->add($context, $exten, '', new \ext_set('MASTER_CHANNEL(CFIGNORE)',''));
        $ext->add($context, $exten, '', new \ext_set('FORWARD_CONTEXT','from-internal'));
        $ext->add($context, $exten, '', new \ext_set('MASTER_CHANNEL(FORWARD_CONTEXT)','from-internal'));
        $ext->add($context, $exten, '', new \ext_macro('blkvm-clr'));
        $ext->add($context, $exten, '', new \ext_dbdel('RG/${ARG1}/${UNIQCHAN}'));
        $ext->add($context, $exten, '', new \ext_noop_trace('DIALEDPEERNUMBER: ${DIALEDPEERNUMBER} CID: ${CALLERID(all)}'));
        if ($config->get('AST_FUNC_MASTER_CHANNEL') && $config->get('AST_FUNC_CONNECTEDLINE')) {
            // Check that it is numeric so we don't pollute it with odd dialplan stuff like FMGL-blah from followme
            $ext->add($context, $exten, '', new \ext_execif('$[!${REGEX("[^0-9]" ${DIALEDPEERNUMBER})} && "${DB(AMPUSER/${AMPUSER}/cidname)}" != ""]', 'Set', 'MASTER_CHANNEL(CONNECTEDLINE(num))=${DIALEDPEERNUMBER}'));
            $ext->add($context, $exten, '', new \ext_execif('$[!${REGEX("[^0-9]" ${DIALEDPEERNUMBER})} && "${DB(AMPUSER/${AMPUSER}/cidname)}" != ""]', 'Set', 'MASTER_CHANNEL(CONNECTEDLINE(name))=${DB(AMPUSER/${DIALEDPEERNUMBER}/cidname)}'));
	}
	$ext->add($context, $exten, '', new \ext_return());

    }
    
}

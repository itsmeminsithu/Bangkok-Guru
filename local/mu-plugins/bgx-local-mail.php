<?php
/** Route local test messages to Mailpit. Never loaded on production. */
if(!defined('ABSPATH'))exit;
add_action('phpmailer_init',function($mailer){
    if(function_exists('wp_get_environment_type') && wp_get_environment_type()==='local'){
        $mailer->isSMTP();$mailer->Host='mailpit';$mailer->Port=1025;
        $mailer->SMTPAuth=false;$mailer->SMTPSecure='';
        $mailer->From='demo@bangkok-guru.local';$mailer->FromName='Bangkok Guru Local';
    }
});

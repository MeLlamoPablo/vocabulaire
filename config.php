<?php

//The app version
$GLOBAL_CONFIG['version'] = '2.5';

//The max session length in seconds. If an user is inactive more than that time, their session will be destroyed.
$GLOBAL_CONFIG['max_session_length'] = 60*60;

//The maximum of allowed failed login attempts. After that, a captcha will be prompted to the user.
$GLOBAL_CONFIG['max_login_attempts'] = 1;
//The minimum tiem after wich failed login attempts are ignored.
$GLOBAL_CONFIG['failed_login_attempt_timeout'] = 30*60;

//ReCaptcha config
$GLOBAL_CONFIG['ReCaptcha']['enabled']     = FALSE;
$GLOBAL_CONFIG['ReCaptcha']['site_key']    = '';
$GLOBAL_CONFIG['ReCaptcha']['secret_key']  = '';
?>
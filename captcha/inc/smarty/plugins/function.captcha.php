<?php
function smarty_function_captcha($params, &$smarty)
{
    // Check theme
    $theme = isset($params['theme']) ? $params['theme'] : "red";
    if(!in_array($theme, array("red", "white", "blackglass", "clean")))
        $theme = "red";
    
    // Check language
    $lang = isset($params['lang']) ? $params['lang'] : (isset(kryn::$page['lang']) ? kryn::$page['lang'] : "en");
    if(!in_array($lang, array("en", "nl", "fr", "de", "pt", "ru", "es", "tr")))
        $lang = "en";
    
    // Check public key
    $pubkey = isset($params['publickey']) ? $params['publickey'] : null;
    if($pubkey == null)
    {
        kLog('captcha', 'No public key was provided in {captcha}, no captcha will be shown.');
        return '<div class="error">Captcha error: no public key was provided!</div>';
    }
    
    return '
        <script type="text/javascript">var RecaptchaOptions = { theme: \''.$theme.'\', lang: \''.$lang.'\' };</script>
        <script type="text/javascript" src="http://www.google.com/recaptcha/api/challenge?k='.$pubkey.'"></script>
        <noscript>
            <iframe src="http://www.google.com/recaptcha/api/noscript?k='.$pubkey.'" height="300" width="500" frameborder="0"></iframe><br>
            <textarea name="recaptcha_challenge_field" rows="3" cols="40"></textarea>
            <input type="hidden" name="recaptcha_response_field" value="manual_challenge">
        </noscript>
    ';
}
?>
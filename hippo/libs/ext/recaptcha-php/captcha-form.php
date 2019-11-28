<?php
require(LIBS.'/ext/recaptcha-php/recaptchalib.php');

function getCaptcha($button="vai", $action=null)
{
  $publickey = "6LdatdMSAAAAAFYyd9lwacS5971NtDb4YKOKag4c"; // you got this from the signup page
  $captcha = recaptcha_get_html($publickey);
//   $captcha = "captcha test";
  if($action != null){$action = " action='$action'";}else{$action="";}
  $html = "<form method='post'$action>
        $captcha
        <input type='submit' value='$button' style='margin-left: 3px; width: 313px !important; background-color: #7c0000; border: 0px solid black; color: white; border-radius: 5px; -moz-border-radius: 5px;'/>
      </form><br>
  ";
  return $html;
}

function checkCaptcha()
{
  $captchaok = false;
  if(isset($_POST["recaptcha_challenge_field"]) && isset($_POST["recaptcha_response_field"]))
  {
    $privatekey = "...";
    $resp = recaptcha_check_answer ($privatekey,
                                  $_SERVER["REMOTE_ADDR"],
                                  $_POST["recaptcha_challenge_field"],
                                  $_POST["recaptcha_response_field"]);

    if (!$resp->is_valid) {
      // What happens when the CAPTCHA was entered incorrectly
      $captchaok = false;
    } else {
      // Your code here to handle a successful verification
      $captchaok = true;
    }
  }
  return $captchaok;
}
?>
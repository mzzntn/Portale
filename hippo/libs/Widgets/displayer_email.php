<?

class Displayer_email extends Displayer{
  var $recipient;
  var $subject;
  var $headers;

  function display(){
    ob_start();
    parent::display();
#    $mail = ob_get_flush();
    $mail = ob_get_contents();
    ob_end_clean();
    mail($this->recipient, $this->subject, $mail, $this->headers);
  }

}
?>

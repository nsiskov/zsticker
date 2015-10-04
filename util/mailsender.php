<?php 
require 'lib/phpmailer/class.phpmailer.php';

define('smtpHost', $GLOBALS['config']->smtpHost);
define('smtpUsername', $GLOBALS['config']->smtpUser);
define('smtpPassword', $GLOBALS['config']->smtpPassword);

class MailSender {

	static function sendMail($to, $subject, $message) {
		$mail = new PHPMailer;
		$mail->isSMTP();                                // Set mailer to use SMTP
		$mail->Host = smtpHost;  // Specify main and backup server
		$mail->SMTPAuth = true;                               // Enable SMTP authentication
		$mail->Username = smtpUsername;                            // SMTP username
		$mail->Password = smtpPassword;                           // SMTP password
		$mail->Port=25;
		#$mail->SMTPSecure = 'tls';                            // Enable encryption, 'ssl' also accepted
		
		$mail->From = "service@zsticker.com";
		$mail->FromName = 'zSticker Mailer';
		$mail->addAddress($to);               // Name is optional
		$mail->addReplyTo("info@zsticker.com", 'Information');
		
		$mail->WordWrap = 50;                                 // Set word wrap to 50 characters
		#$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
		#$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
		$mail->isHTML(true);                                  // Set email format to HTML
		
		$mail->Subject = $subject;
		$mail->Body    = $message;
		//$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
		
		if(!$mail->send()) {
		   echo 'Message could not be sent.';
		   echo 'Mailer Error: ' . $mail->ErrorInfo;
		   return false;
		} else {
			return true;
		}
	}
}
?>
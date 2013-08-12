<?php

namespace helpers;

/**
 * Helper class for authenticate user
 *
 * @package    helpers
 * @copyright  Copyright (c) Tobias Zeising (http://www.aditu.de)
 * @license    GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @author     Tobias Zeising <tobias.zeising@aditu.de>
 */
class Email {
	private static $smtp_host;
	private static $smtp_port;
	private static $smtp_user;
	private static $smtp_passwd;
	public static function init() {
		self::$smtp_host = \F3::get('smtp_service');
		self::$smtp_user = \F3::get('smtp_user');
		self::$smtp_passwd = \F3::get('smtp_passwd');	
		self::$smtp_port = \F3::get('smtp_port');		
	}
	public function __construct()
	{
		include_once 'libs/PHPMailer/class.phpmailer.php';
	}
	public static function sent_file_to_user($filename, $username,$user_addr, $email_src)
	{
		//Create a new PHPMailer instance
		$mail = new \PHPMailer();
		//Tell PHPMailer to use SMTP
		$mail->IsSMTP();
		//Enable SMTP debugging
		// 0 = off (for production use)
		// 1 = client messages
		// 2 = client and server messages
		$mail->SMTPDebug  = 2;
		//Ask for HTML-friendly debug output
		$mail->Debugoutput = 'html';
		//Set the hostname of the mail server
		$mail->Host       = self::$smtp_host;
		//Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
		$mail->Port       = self::$smtp_port;
		//Set the encryption system to use - ssl (deprecated) or tls
		$mail->SMTPSecure = 'tls';
		//Whether to use SMTP authentication
		$mail->SMTPAuth   = true;
		//Username to use for SMTP authentication - use full email address for gmail
		$mail->Username   = self::$smtp_user;
		//Password to use for SMTP authentication
		$mail->Password   = self::$smtp_passwd;
		//Set who the message is to be sent from
		$mail->SetFrom($email_src, 'offlineread');
		//Set an alternative reply-to address
		$mail->AddReplyTo(self::$smtp_user,'offlineread');
		//Set who the message is to be sent to
		$mail->AddAddress($user_addr, $username);
		//Set the subject line
		$mail->Subject = 'offlineread sent';
		//Replace the plain text body with one created manually
		$body="<html><body>nobody</body></html>";
		$mail->MsgHTML($body);
		$mail->AltBody = 'message body is useless';
		//Attach the epub file
		$mail->AddAttachment($filename);
		self::logLine("send to:".$username." by ".$user_addr . " from ".$email_src);
		//Send the message, check for errors
		if(!$mail->Send()) {
			self::logLine("Mailer Error: " . $mail->ErrorInfo);
			return true;
		} else {
			self::logLine("Message sent!");
			return false;
		}
		
	}
	public static function logLine($line) {
		\F3::get('logger')->log($line, \DEBUG);
	}
	
}
Email::init();

?>
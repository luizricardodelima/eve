<?php

require 'lib/phpmailer/PHPMailerAutoload.php';
require_once 'eve.class.php';

class EveMail
{
	private $eve;
	private $mail_settings;
	public  $phpmailer_error_info;
	public  $log;
	
	// $placeholders_map can be null
	function send_mail($emailaddress, $placeholders_map, $subject, $message_html, $message_plain = null)
	{
		$mail = new PHPMailer();
		$mail->CharSet = 'UTF-8';
		$mail->IsSMTP();
		$mail->SMTPAuth = 	$this->mail_settings['smtpauth'];
		$mail->SMTPSecure = $this->mail_settings['smtpsecure'];
		$mail->Port = 		$this->mail_settings['port'];
		$mail->SMTPDebug  = $this->mail_settings['smtpdebug'];
		$mail->Host = 		$this->mail_settings['host'];
		$mail->Username = 	$this->mail_settings['username'];
		$mail->Password = 	$this->mail_settings['password'];
		$mail->FromName = 	$this->mail_settings['fromname'];
		$mail->Debugoutput = function($str, $level) {
			$this->log .= "$level: $str\n";
		};
		$mail->AddAddress($emailaddress); 
		$mail->IsHTML(true);
		if ($placeholders_map != null)
		{
			$mail->Subject = str_replace(array_keys($placeholders_map), array_values($placeholders_map), $subject);
			$mail->Body    = str_replace(array_keys($placeholders_map), array_values($placeholders_map), $message_html);
			if ($message_plain) $mail->AltBody = str_replace(array_keys($placeholders_map), array_values($placeholders_map), $message_plain);
		}
		else
		{
			$mail->Subject = $subject;
			$mail->Body    = $message_html;
			if ($message_plain) $mail->AltBody = $message_plain;
		}

		if($mail->Send())
		{
			return true;
		}
		else
		{
			$phpmailer_error_info = $mail->ErrorInfo;
			return false;
		}
	}
	
	function __construct(Eve $eve)
	{
		$this->eve = $eve;		
		$this->mail_settings = array
		(
			'host' => 		$this->eve->getSetting('phpmailer_host'),
			'username' => 	$this->eve->getSetting('phpmailer_username'),
			'password' => 	$this->eve->getSetting('phpmailer_password'),
			'fromname' => 	$this->eve->getSetting('phpmailer_fromname'),
			'smtpauth' => 	$this->eve->getSetting('phpmailer_smtpauth'),
			'smtpsecure' => $this->eve->getSetting('phpmailer_smtpsecure'),
			'port' => 		$this->eve->getSetting('phpmailer_port'),
			'smtpdebug' => 	$this->eve->getSetting('phpmailer_smtpdebug')
		);
		$this->log = "";
	}
}

?>

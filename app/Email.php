<?php
namespace App;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;


class Email
{
	public $debug_email = 'mumtaz_ahmad@mentor.com';
	public $recipients = [];
	function __construct($replyto = 'mumtaz_ahmad@mentor.com')
	{
		$this->mail = new PHPMailer(true);	
		//$this->mail->SMTPDebug = 4; 
		$this->mail->isSMTP();     
		//$this->mail->Host = "email-na.mentorg.com";//$host;
		$this->mail->Host = 'localhost';
		//$this->mail->SMTPAuth = true;
		$this->mail->SMTPAuth = false;
		//$this->mail->SMTPAutoTLS = true; 
		$this->mail->SMTPAutoTLS = false; 
		//$this->mail->Port = 587;//25; 
		$this->mail->Port = 25; 
		//$this->mail->Username   = 'mgc\\mahmad';//$user[0]; 
		//$this->mail->Password  ='E395120@gsmp6000';
		//$this->mail->setFrom('Mumtaz_Ahmad@mentor.com', 'Mumtaz Ahmad');
		$this->mail->setFrom('support-bot@mentorg.com', 'Support Bot');
		$this->mail->addReplyTo($replyto);
		$this->mail->isHTML(true);  
	}
	function AddAttachement($file)
	{
		$this->mail->addAttachment($file);
	}
	function AddTo($to)
	{
		if(is_array($to))
		{
			foreach($to as $t)
			{
				$this->recipients[strtolower($t)]=$t;
				$this->mail->addAddress($t);
			}
		}
		else
		{
			$this->recipients[strtolower($to)]=$to;
			$this->mail->addAddress($to);
		}
	}
	function AddBCC($bcc)
	{
		if(is_array($bcc))
		{
			foreach($bcc as $bc)
			{
				$this->recipients[strtolower($bc)]=$bc;
				$this->mail->addBCC($bc);
			}
		}
		else
		{
			$this->recipients[strtolower($bcc)]=$bcc;
			$this->mail->addBCC($bcc);
		}
	}
	function AddCC($cc)
	{
		if(is_array($cc))
		{
			foreach($cc as $c)
			{
				$this->recipients[strtolower($c)]=$c;
				$this->mail->addCC($c);
			}
		}
		else
		{
			$this->recipients[strtolower($cc)]=$cc;
			$this->mail->addCC($cc);
		}
	}
	function Send($option,$subject,$msg,$to=null,$cc=null)
	{
		if($to != null)
			$this->AddTo($to);
		if($cc != null)
		{
			$this->AddCC($cc);
		}
		if($option == 0)
		{
			dump("Email option is off");
			return;
		}
		if($option == 2)
		{
			$subject = '[MODERATED] '.$subject;
			$this->mail->ClearAllRecipients( );
			$this->mail->addAddress($this->debug_email);
			
			$msg .= '<br><small>Distribution List</small>';
			foreach($this->recipients as $recipient)
				$msg .= '<li><small>'.$recipient.'</small></li>'; 
			$msg .= '';
		}

		$this->mail->Subject = $subject;	
		$this->mail->Body= $msg;
		try {
			$this->mail->send();
		} 
		catch (phpmailerException $e) 
		{
			echo $e->errorMessage(); //Pretty error messages from PHPMailer
		} 
		catch (Exception $e) {
			echo $e->getMessage(); //Boring error messages from anything else!
		}
	}
}
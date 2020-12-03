<?php
namespace App;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;


class Email
{
	function __construct($host='localhost',$user = ['support-bot@mentorg.com', 'Support Bot'],$replyto = 'mumtaz_ahmad@mentor.com')
	{
		$this->mail = new PHPMailer(true);	
		//$this->mail->SMTPDebug = 4; 
		$this->mail->isSMTP();     
		//$this->mail->Host = "email-na.mentorg.com";//$host;
		$this->mail->Host = $host;
		//$this->mail->SMTPAuth = true;
		$this->mail->SMTPAuth = false;
		//$this->mail->SMTPAutoTLS = true; 
		$this->mail->SMTPAutoTLS = false; 
		//$this->mail->Port = 587;//25; 
		$this->mail->Port = 25; 
		//$this->mail->Username   = 'mgc\\mahmad';//$user[0]; 
		//$this->mail->Password  ='E395120@gsmp6000';
		//$this->mail->setFrom('Mumtaz_Ahmad@mentor.com', 'Mumtaz Ahmad');
		$this->mail->setFrom($user[0], $user[1]);
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
				$this->mail->addAddress($t);
		}
		else
			$this->mail->addAddress($to);
	}
	function AddBCC($bcc)
	{
		if(is_array($bcc))
		{
			foreach($bcc as $bc)
				$this->mail->addBCC($bc);
		}
		else
			$this->mail->addBCC($bc);
		
	}
	function AddCC($cc)
	{
		if(is_array($cc))
		{
			foreach($cc as $c)
			{
				dump($c);
				$this->mail->addCC($c);
			}
		}
		else
			$this->mail->addCC($cc);
	}
	function Send($subject,$msg,$to=null,$cc=null)
	{
		if($to != null)
			$this->AddTo($to);
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
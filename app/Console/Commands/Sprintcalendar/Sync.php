<?php

namespace App\Console\Commands\Sprintcalendar;

use Illuminate\Console\Command;
use App\Apps\Sprintcalendar\Sprintcalendar;
use App\Libs\Jira\Fields;
use App\Libs\Jira\Jira;
use Carbon\Carbon;

class Sync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
protected $signature = 'sprintcalendar:sync {--rebuild=0} {--force=0} {--beat=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
	
    public function __construct()
    {
		parent::__construct();
    }
    public function Permission()
	{
		$this->rebuild = $this->option('rebuild');
		$this->force = $this->option('force');
		$start = Carbon::now();

		$start->subDays(3);
		$end = Carbon::now();
		$end=  $end->addDays(40);
		$this->app = new Sprintcalendar($start,$end);
		$now = Carbon::now($this->app->timezone); 
		$lastemailsenton = $this->app->Read('lastemailsenton');
		if( ($lastemailsenton  != $now->format('Y-m-d'))||($this->force==1))
		{
			if(($now->isFriday() || $now->isMonday())&&($now->format('H')>9)); 
				return true;
		}
		return true;
		return false;
	}
	public function Preprocess()
	{
		
	}
	public function Postprocess()
	{
		$this->app->SaveUpdateTime();
		$this->app->Save(['sync_requested'=>0]);
	}
	function HTMLReminder($sprint)
	{
		if($start)
			$this->mail->Subject = 'Notification Sprint '.$sprint_name.': Starts today';
		else
			$this->mail->Subject = 'Notification Sprint '.$sprint_name.' Close today';
			
		$msg = $this->mail->Subject.'<br>';
		
		$msg .= '<br>';
		$msg .= '<br>';
		$msg .= "This is an auto generated notification so please donot reply to this email<br>";
		$msg .= "If you are not interested in these notifications, please send an email to mumtaz_ahmad@mentor.com".'<br>';
		$msg .= '<br>';
		$msg .= "For complete sprint calender please <a href='https://sos.pkl.mentorg.com/sprintcalendar'>click here</a>";
        $this->mail->Body= $msg;
		//$this->mail->AltBody =$msg;
		//echo $msg;
		foreach($this->addresses as $address)
		{
		
			$this->mail->ClearAllRecipients( );
			$this->mail->addAddress($address);     // Add a recipient
	
			try {
				//$this->mail->send();
			} 
			catch (phpmailerException $e) 
			{
				echo $e->errorMessage(); //Pretty error messages from PHPMailer
			} 
			catch (Exception $e) {
				echo $e->getMessage(); //Boring error messages from anything else!
			}
			echo "Sending notification to ".$address."\r\n";
		}
		//echo "Email sent for Time to resolution  alert for ".$ticket->key."\n";
		//echo 'MRC Approval mail  sent';
	}
    public function handle()
    {
		if(!$this->Permission())
		{
			return;
		}

		$app = $this->app;
		$now = Carbon::now($app->timezone); 
		$sstart = Carbon::parse($app->GetCurrentSprintStart()->date);
		$send =   Carbon::parse($app->GetCurrentSprintEnd()->date);
		$send->subDays(2);///since sprint closes on friday and now sunday
		$sprint = $app->GetCurrentSprint();
		
		if($sstart->IsToday())
		{
			$subject = 'Notification Sprint '.$sprint.' Start';
			//$email =  new Email();
			//$email->SendSprintReminder($sprint,1);
			$lastemailsenton = $this->app->Save(['lastemailsenton'=>$now->format('Y-m-d')]);
			echo "Sent email eminder for start of sprint ".$sprint;
		}
		if($send->IsToday())
		{
			$subject = 'Notification Sprint '.$sprint.' Close';
			$lastemailsenton = $this->app->Save(['lastemailsenton'=>$now->format('Y-m-d')]);
			echo "Sent email eminder for closure of sprint ".$sprint;

		}
		$this->Preprocess();
		$this->Postprocess();
    }
}

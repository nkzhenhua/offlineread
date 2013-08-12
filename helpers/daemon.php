<?php
namespace helpers;

// the job will run even user closed his exploer
class daemon {
		
	public function __construct() {
		ignore_user_abort ();
		if(!file_exists('data/cache/daemon_cmd.txt'))
		{
			file_put_contents ( 'data/cache/daemon_cmd.txt', "" );
		}
		if(!file_exists('data/cache/daemon_status.txt'))
		{
			file_put_contents ( 'data/cache/daemon_status.txt', "" );
		}
		
	}
	public function stop() {
		file_put_contents ( 'data/cache/daemon_cmd.txt', "stop" );
		echo "stop the daemon";
	}
	public function start() {
		$status = file_get_contents ( "data/cache/daemon_status.txt" );
		if ($status == "started!") {
			echo "job started already";
		} else {
			file_put_contents ( "data/cache/daemon_status.txt", "started!" );
			file_put_contents ( 'data/cache/daemon_cmd.txt', "start" );
			echo "start the daemo, please close the exploer";
			$this->run();
		}
	}
	// set the time out
	public function run() {
		set_time_limit ( 0 );
		$interval = 60 * 60*24;
		do {
			$cmd = file_get_contents ( 'data/cache/daemon_cmd.txt' );
			if ($cmd == "stop") {
				file_put_contents ( "data/cache/daemon_status", "stop!" );
				exit ();
			}
			$user=new \daos\User();
			$users=$user->getAlluser();
			$epub=new EPubCreater();
			foreach( $users as $cur_user)
			{
				if( $cur_user['deliver_enable'] == 1 && !empty($cur_user['deliver_email']))
				{
					$epub->genEpubAndDeliver($cur_user['username']);
				}
			}
			sleep ( $interval );
		} while ( true );
	}
}
?>

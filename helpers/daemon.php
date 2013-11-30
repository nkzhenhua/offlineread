<?php
namespace helpers;

// the job will run even user closed his exploer
class daemon {		
	public function __construct() {
		echo "new daemon";
		if(!file_exists('data/cache/daemon_status.txt'))
		{
			file_put_contents ( 'data/cache/daemon_status.txt', "" );
		}	
	}
	public function stop() {
		$status = file_get_contents ( "data/cache/daemon_status.txt" );
		$status = trim(chop($status));
		if (!empty($status) ) {
			system("kill job $status");
			file_put_contents ( "data/cache/daemon_status.txt", "" );
			\F3::get('logger')->log( "kill job $status", \DEBUG);
		}
		\F3::get('logger')->log( "stop the daemon", \DEBUG);
		echo "stop daemon done";
	}
	public function start() {
		$status = file_get_contents ( "data/cache/daemon_status.txt" );
		$status = trim(chop($status));
		if (!empty($status)) {
				\F3::get('logger')->log( "job started already",\DEBUG);
				echo "another job is running";
		} else {
			System("nohup php ./helpers/daemon_exe.php > data/cache/logs.txt 2>&1 &; echo $! > data/cache/daemon_status.txt");
//			System(" ls &;echo \"aaa\" > data/cache/daemon_status.txt");		
			$status = file_get_contents ( "data/cache/daemon_status.txt");
			\F3::get('logger')->log("start daemon $status", \DEBUG);
			echo "daemon started $status";
		}
	}
	// set the time out
	public function workthread() {
		echo "in workthread";
		return;
		set_time_limit ( 0 );
		$interval = 60 * 60*24;
		do {
			\F3::get('logger')->log('daemon running...', \DEBUG);				
			$cmd = file_get_contents ( 'data/cache/daemon_cmd.txt' );
			if (chop($cmd) == "stop") {
				file_put_contents ( "data/cache/daemon_status", "stop!" );
				\F3::get('logger')->log('daemon exit', \DEBUG);
				exit ();
			}
			$user=new \daos\User();
			$users=$user->getAlluser();
			$epub=new EPubCreater();
			foreach( $users as $cur_user)
			{
				$update = new ContentLoader();
				$update->updateUser($cur_user['username']);
				if( $cur_user['deliver_enable'] == 1 && !empty($cur_user['deliver_email']))
				{
					\F3::get('logger')->log('gen epub for :'.$cur_user['deliver_enable'], \DEBUG);
					$epub->genEpubAndDeliver($cur_user['username']);
				}
			}
			\F3::get('logger')->log('sleep :'.$interval, \DEBUG);
			sleep ( $interval );
		} while ( true );
	}
}
?>

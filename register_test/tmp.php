<?php
header("Content-Type:text/html;charset=UTF-8");
ignore_user_abort();                //忽略用户是否断开客户端连接
set_time_limit(0);                        //该程序无限制执行时间        
//检测文件是否仍在执行
$work_mark = file_exists('settimeout.txt')?file_get_contents('settimeout.txt'):null;
if($work_mark=='start'){
	echo 'PHP定时脚本已经在执行当中...<br/>';
}elseif ($work_mark == 'stop'){
	echo 'Php定时脚本已经停止执行...<br/>';
}

$status = $_GET['action'];        //操作状态
switch ($status){
case 'start':{
	echo 'Php脚本开始执行了，你现在可以关闭浏览器，程序仍将继续运行!!!';
	file_put_contents('settimeMark.txt',$status);                        
	break;
}
case 'stop':{
	file_put_contents('settimeMark.txt',$status);
	echo 'Php脚本停止执行了!!!';
	break;
}
default:{
	echo '让程序开始执行请加上action参数:action=start or action=stop!!';
}
}

while('start' == file_exists('settimeMark.txt')?file_get_contents('settimeMark.txt'):null){
	//每5秒执行一次记录时间操作
	if (file_exists('recoredTime.txt')){
		$record = file_get_contents('recoredTime.txt');
		file_put_contents('recoredTime.txt',$record."\r\n".date('Y-m-d H:m:s'));
	}else {
		file_put_contents('recoredTime.txt',date('Y-m-d H:m:s'));
	}
	sleep(5);    //每5秒执行一次记录时间
	/*
	 * 这里也可以调用PHP文件执行
	 * if (date('H:m')=='05:00'){
		 include 'xx.php';//你要执行的PHP代码
}
sleep(30);
	 */
}

echo "ehllo";
?>

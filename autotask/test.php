<?php
//the job will run even user closed his exploer
ignore_user_abort();
//set the time out
set_time_limit(0);
$interval=60*30;
do{
    sleep($interval);
}while(true);
?>

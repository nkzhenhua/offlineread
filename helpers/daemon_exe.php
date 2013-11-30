<?php
echo "start run";
echo "start run";
//ignore_user_abort ();
//set_time_limit(0);
require_once './helpers/daemon.php';
$exe = new \helpers\daemon();
echo "started";
$exe->workthread();
?>

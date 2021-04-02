<?php
// tasks/backupTasks.php

// 01 crontab 등록 (매분마다 실행할거 있는지 돌기)
// * * * * * cd /data/www/d.s/crunz && /data/www/lib/php/vendor/bin/crunz schedule:run /data/www/d.s/crunz/tasks/
// "/data/www/d.s/crunz" 이 경로가 crunz.yml파일 위치임


// 02 설정파일 생성
// /project/vendor/bin/crunz publish:config
// The configuration file was generated successfully
// [crunz.yml] 설정값중 "source: tasks"가 소스파일들 위치임
// [crunz.yml] 설정값중 "suffix: demo_crunz.php"가 실행할 소스 파일 명임

// 03 설정정보 올바른지 테스트 하기
// /data/www/lib/php/vendor/bin/crunz task:debug 1

// 04 설정된 task 목록 보기
// /data/www/lib/php/vendor/bin/crunz schedule:list


$CFG = require_once(__DIR__ . '/../../../common/include/incConfig.php');//CG CONFIG

//exit;
if(!require_once(__DIR__ . "/../../../common/include/incUtil.php"))die("require incUtil fail.");
if(!require_once(__DIR__ . "/../../../common/include/incSec.php"))die("require incSec fail.");
if(!require_once(__DIR__ . "/../../../common/include/incDB.php"))die("require incDB fail.");

require_once(__DIR__ . "/../../../lib/php/vendor/autoload.php");
//print_r($CFG["CFG_DB"]["RDCOMMON"]);

use Crunz\Schedule;
use Symfony\Component\Lock\Store\FlockStore;
$schedule = new Schedule();
$task = $schedule->run(PHP_BINARY . ' ' . 'mail_job.php');       
$task
    ->in("/data/www/b.t/mailsend")
    ->cron("* * * * * *")
    ->preventOverlapping();
?>
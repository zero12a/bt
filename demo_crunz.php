<?php
// tasks/backupTasks.php

// 01 crontab 등록 (매분마다 실행할거 있는지 돌기)
// * * * * * cd /data/www/d.s/crunz && /data/www/lib/php/vendor/bin/crunz schedule:run
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


require_once("/data/www/lib/php/vendor/autoload.php");

use Crunz\Schedule;

$schedule = new Schedule();
$task = $schedule->run('cp 1.txt 2.txt');       
$task
    ->in("/data/www/d.s/crunz")
    ->at("11:15")
    ->from('06:30 2021-03-24')
    ->to('23:55 2021-03-24')
    ->appendOutputTo('crunz.log')
    ->preventOverlapping();

$task = $schedule->run('cp 1.txt 3.txt');       
$task
    ->in("/data/www/d.s/crunz")
    ->cron("15,25 22 * * *")
    ->from('06:30 2021-03-24')
    ->to('11:26 2021-04-24') //23분까지 실행이면, 22분 스케줄은 동작하고 23분 스케줄은 동작 안함.
    ->appendOutputTo('crunz.log')
    ->preventOverlapping();
return $schedule;
?>
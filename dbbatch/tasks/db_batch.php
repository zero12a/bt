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



//DB연결 정보 생성
$obj = $CFG["CFG_DB"]["RDCOMMON"];
$obj["PW"] = aesDecrypt($obj["PW"],$CFG["CFG_SEC_KEY"],$CFG["CFG_SEC_IV"]);  

$db = getDbConnPlain($obj);
//$db = getDbConn($CFG["CFG_DB"]["RDCOMMON"]);

//print_r($db);

$sql = "
select 
    BATCH_SEQ, SOURCE_SVRID, SOURCE_SQL, TARGET_SVRID, TARGET_SQL, CRON, START_DT, END_DT 
from
    CMN_BATCH
where USE_YN='Y' ";
$stmt = makeStmt($db,$sql,$coltype="",$REQ);
if(!$stmt)ServerMsg("500","300","SQL makeStmt 실패 했습니다.". $db->errno . " -> " . $db->error);
if(!$stmt->execute())ServerMsg("500","100","stmt 실행 실패" . $stmt->errno . " -> " . $stmt->error);
$tArr =  getStmtArray($stmt);
closeStmt($stmt);





require_once("/data/www/lib/php/vendor/autoload.php");

use Crunz\Schedule;
use Symfony\Component\Lock\Store\FlockStore;

$schedule = new Schedule();

//$lockStoreArray = array();

for($i=0;$i<count($tArr);$i++){
    $x = $tArr[$i];
    print_r($x);
    //echo PHP_BINARY;
    
    $lockFolder = "locks" . $x["BATCH_SEQ"];
    $lockFullPath = __DIR__ . "/" . $lockFolder;
    if(!is_dir($lockFullPath)){
        echo "폴더 생성함" . PHP_EOL;
        mkdir($lockFullPath, 0700, false);
    }else{
        echo "폴더 생성 안함" . PHP_EOL;
    }
    $lockStore = new FlockStore($lockFullPath);

    //$task = $schedule->run(PHP_BINARY . ' ' . './tasks/demo_crunz_job.php',["BATCH_SEQ" => $x["BATCH_SEQ"]]);       
    $task = $schedule->run(PHP_BINARY . ' ' . './tasks/db_job.php BATCH_SEQ=' . $x["BATCH_SEQ"] );     
    $x["START_DT2"] = 
        substr($x["START_DT"],9,2)  . ":" . substr($x["START_DT"],11,2) 
        . " " . substr($x["START_DT"],0,4) . "-" . substr($x["START_DT"],4,2) . "-" . substr($x["START_DT"],6,2); 
    $x["END_DT2"] = 
        substr($x["END_DT"],9,2)  . ":" . substr($x["END_DT"],11,2) 
        . " " . substr($x["END_DT"],0,4) . "-" . substr($x["END_DT"],4,2) . "-" . substr($x["END_DT"],6,2) ;
    //echo "START_DT2 = " . $x["START_DT2"] . PHP_EOL;
    //echo "END_DT2 = " . $x["END_DT2"] . PHP_EOL;
    $task
        ->in("/data/www/b.t/dbbatch")
        ->cron($x["CRON"])
        ->from($x["START_DT2"])
        ->to($x["END_DT2"]) //23분까지 실행이면, 22분 스케줄은 동작하고 23분 스케줄은 동작 안함.
        ->before(function()use($db,$x){ 
            // Do something else
            $stmt = $db->prepare("
            INSERT INTO CMN_BATCH_LOG (
                BATCH_SEQ, MSG, ADD_DT, ADD_ID
            ) VALUES (
                ?, ?, date_format(sysdate(),'%Y%m%d%H%i%s'), 0
            )");
            /* Bind variables to parameters */
            $stmt->bind_param("is", $var1, $var2);
            $var1 = $x["BATCH_SEQ"];
            $var2 = "BATCH START";
            $stmt->execute();
            $stmt->close();
        })
        ->after(function()use($db,$x){ 
            // After the task is run
            // Do something else
            $stmt = $db->prepare("
            INSERT INTO CMN_BATCH_LOG (
                BATCH_SEQ, MSG, ADD_DT, ADD_ID
            ) VALUES (
                ?, ?, date_format(sysdate(),'%Y%m%d%H%i%s'), 0
            )");
            /* Bind variables to parameters */
            $stmt->bind_param("is",  $var1, $var2);
            $var1 = $x["BATCH_SEQ"];
            $var2 = "BATCH END";
            $stmt->execute();
            $stmt->close();            
        })
        ->preventOverlapping($lockStore); //오버레팅 실행 방지 잘 동작함 ( 파라미터는 다르더라도 같은 PHP 파일이면 중북 실행이 안됨 )
}

closeDb($db);

return $schedule;
?>
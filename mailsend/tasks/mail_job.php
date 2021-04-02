<?php

//외부 변수
$BATCH_SEQ = intval( explode("=", $argv[1])[1] );
if(!is_numeric($BATCH_SEQ))exit;

echo 111 . PHP_EOL;

$CFG = require_once(__DIR__ . '/../../../common/include/incConfig.php');//CG CONFIG

print_r($CFG);
//exit;
if(!require_once(__DIR__ . "/../../../common/include/incUtil.php"))die("require incUtil fail.");
if(!require_once(__DIR__ . "/../../../common/include/incSec.php"))die("require incSec fail.");
if(!require_once(__DIR__ . "/../../../common/include/incDB.php"))die("require incDB fail.");
if(!require_once(__DIR__ . "/../../../common/include/incMail.php"))die("require incMail fail.");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once(__DIR__ . "/../../../lib/php/vendor/autoload.php");

//배치 정보 불러오기
$obj = $CFG["CFG_DB"]["RDCOMMON"];
$obj["PW"] = aesDecrypt($obj["PW"],$CFG["CFG_SEC_KEY"],$CFG["CFG_SEC_IV"]);  

//print_r($obj);
$db = getDbConnPlain($obj);

//print_r($db);

//MSG 요청 목록 불러오기
$REQ["BATCH_SEQ"] = $BATCH_SEQ;
$sql = "
select 
    a.USR_SEQ, a.USR_ID, a.ADD_DT as REQUEST_DT, a.CAMPAIGN_SEQ, b.CAMPAIGN_NM
    , b.CONTENT_SVRID, b.CONTENT_SQL, b.CONTENT_IN_COLTYPES, b.CAHNNEL, b.TITLE, b.BODY
    , c.EMAIL, c.USR_NM
from CMN_MSG_REQUEST a 
    join CMN_CAMPAIGN b on a.CAMPAIGN_SEQ = b.CAMPAIGN_SEQ
    join CMN_USR c on a.USR_SEQ = c.USR_SEQ
";

echo "batchinfoInMap-------------------------------------";
print_r($REQ);

$stmt = makeStmt($db,$sql,$coltype="i",$REQ);

//print_r($stmt);

if(!$stmt)ServerMsg("500","300","SQL makeStmt 실패 했습니다.". $db->errno . " -> " . $db->error);
if(!$stmt->execute())ServerMsg("500","100","stmt 실행 실패" . $stmt->errno . " -> " . $stmt->error);
$x =  getStmtArray($stmt)[0];
echo "x-------------------------------------";
print_r($x);
closeStmt($stmt);


//개인화 데이터 불러오기
$obj = $CFG["CFG_DB"][$x["CONTENT_SVRID"]];
$obj["PW"] = aesDecrypt($obj["PW"],$CFG["CFG_SEC_KEY"],$CFG["CFG_SEC_IV"]);  

//print_r($obj);
$contentDb = getDbConnPlain($obj);

//print_r($db);

//MSG 요청 목록 불러오기
$sql = $x["CONTENT_SQL"];

echo "batchinfoInMap-------------------------------------";
$REQ["USR_SEQ"] = $x["USR_SEQ"];
$REQ["USR_ID"] = $x["USR_ID"];
$REQ["REQUEST_DT"] = $x["REQUEST_DT"];

print_r($REQ);

$coltype = str_replace(" ", "", str_replace(",","", $x["CONTENT_IN_COLTYPES"])); // "콤마 구분자 데이터 소스"

$contentStmt = makeStmt($contentDb,$sql,$coltype,$REQ);

//print_r($stmt);

if(!$contentStmt)ServerMsg("500","300","SQL makeStmt 실패 했습니다.". $db->errno . " -> " . $db->error);
if(!$contentStmt->execute())ServerMsg("500","100","stmt 실행 실패" . $stmt->errno . " -> " . $stmt->error);
$contentArray =  getStmtArray($contentStmt)[0];
echo "x-------------------------------------";
print_r($contentArray);
closeStmt($contentStmt);
closeDb($contentDb);

$contentArray = json_decode($contentArray["JSON_DATA"],true);
print_r($contentArray);

//컨텐츠와 개인화 항목 랜더링하기
$m = new Mustache_Engine(array('entity_flags' => ENT_QUOTES));
$title = $m->render($x["TITLE"], $contentArray);
$body = $m->render($x["BODY"], $contentArray);

echo "<pre>TITLE:" . $title; // "Hello World!"

//$contentArray["LOGIN_LIST"] =  json_decode($contentArray["LOGIN_LIST"],true);
echo "<pre>BODY:" . $body; // "Hello World!"


//메일 보내기
$mailObj = new mailObject($CFG);

$db_start = microtime_float();    
//for($i=0;$i<1000;$i++){
//    echo $i . " ";
echo $mailObj->sendDaum($x["EMAIL"],$x["USR_NM"],$title,$body);  //($t_to_email,$t_to_name,$t_subject,$t_message)
//}
$db_end = microtime_float();

echo "execute time = " . number_format($db_end - $db_start,2);

closeDb($db);
?>
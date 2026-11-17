<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit("Not Found\n"); }
require_once dirname(__DIR__).'/config/database.php';
$pdo=db();$since=date('Y-m-d H:i:s',time()-86400);$today=date('Y-m-d');$findings=[];
// 1. IP with at least 10 failures in the last 10 minutes.
$q=$pdo->prepare("SELECT ip_address,COUNT(*) n FROM security_logs WHERE event_type IN ('LOGIN_FAILED','OTP_FAILED','SECURITY_ANSWER_FAILED') AND created_at>=DATE_SUB(NOW(),INTERVAL 10 MINUTE) AND ip_address IS NOT NULL GROUP BY ip_address HAVING COUNT(*)>=10");$q->execute();foreach($q->fetchAll() as $r)$findings[]=['ip_10_failures',$r['ip_address'],'critical','IP '.$r['ip_address'].' recorded '.$r['n'].' authentication failures in the last 10 minutes.'];
// 2. One account targeted from at least three distinct IP addresses.
$q=$pdo->prepare("SELECT username_typed,COUNT(DISTINCT ip_address) ips,COUNT(*) n FROM security_logs WHERE event_type IN ('LOGIN_FAILED','SECURITY_ANSWER_FAILED','OTP_FAILED') AND created_at>=? AND username_typed IS NOT NULL GROUP BY username_typed HAVING COUNT(DISTINCT ip_address)>=3");$q->execute([$since]);foreach($q->fetchAll() as $r)$findings[]=['account_multi_ip',$r['username_typed'],'high','Account '.$r['username_typed'].' received '.$r['n'].' failures from '.$r['ips'].' different IP addresses.'];
// 3. Five or more OTP failures for an account in any rolling ten-minute window.
$q=$pdo->prepare("SELECT username_typed,COUNT(*) n FROM security_logs WHERE event_type='OTP_FAILED' AND created_at>=DATE_SUB(NOW(),INTERVAL 10 MINUTE) AND username_typed IS NOT NULL GROUP BY username_typed HAVING COUNT(*)>=5");$q->execute();foreach($q->fetchAll() as $r)$findings[]=['otp_guessing',$r['username_typed'],'critical','Possible OTP guessing: '.$r['n'].' failed OTP attempts for account '.$r['username_typed'].' in 10 minutes.'];
// 4. Successful login between midnight and 5 AM Philippine time.
$q=$pdo->prepare("SELECT username_typed,ip_address,created_at FROM security_logs WHERE event_type='LOGIN_SUCCESS' AND created_at>=? AND HOUR(created_at) BETWEEN 0 AND 4");$q->execute([$since]);foreach($q->fetchAll() as $r)$findings[]=['off_hours_login',(string)$r['username_typed'].'@'.(string)$r['ip_address'],'medium','Successful off-hours login for '.$r['username_typed'].' from '.$r['ip_address'].' at '.$r['created_at'].' (Philippine time).'];
$insert=$pdo->prepare('INSERT IGNORE INTO security_flags(pattern_key,target_value,severity,description,detection_date) VALUES(?,?,?,?,?)');$saved=0;foreach($findings as $f){$insert->execute([$f[0],substr($f[1],0,100),$f[2],substr($f[3],0,500),$today]);$saved+=$insert->rowCount();}
echo "School Tuition Security Monitor\nScanned logs since: $since\nFindings detected: ".count($findings)."\nNew flags saved: $saved\n";foreach($findings as $f)echo '['.strtoupper($f[2]).'] '.$f[3]."\n";

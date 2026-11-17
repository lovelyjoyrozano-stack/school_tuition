<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';

function e(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function public_error_message(Throwable $error, string $fallback='Something went wrong. Please try again.'): string {
    if ($error instanceof PDOException) { error_log('[database operation] '.$error->getMessage()); return $fallback; }
    if ($error instanceof RuntimeException) return $error->getMessage();
    error_log('[application operation] '.$error->getMessage());
    return $fallback;
}
function go(string $page, array $params=[]): never { $q = http_build_query(['page'=>$page] + $params); header('Location: index.php?'.$q); exit; }
function csrf(): string { if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32)); return $_SESSION['csrf_token']; }
function verify_csrf(): void { if (!hash_equals(csrf(), (string)($_POST['csrf_token'] ?? ''))) { log_event('CSRF_REJECTED','A POST request was rejected because its CSRF token was invalid.'); http_response_code(419); exit('Your session expired. Refresh the page and try again.'); } }
function client_ip(): string { return substr((string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'),0,45); }
function browser_agent(): string { return substr((string)($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'),0,255); }
function log_event(string $event, string $description='', ?int $userId=null, ?string $username=null, array $details=[]): void {
    try { $s=db()->prepare('INSERT INTO security_logs(user_id,username_typed,event_type,event_description,details,ip_address,user_agent) VALUES(?,?,?,?,?,?,?)'); $s->execute([$userId,$username,$event,substr($description,0,500),$details ? json_encode($details,JSON_UNESCAPED_SLASHES) : null,client_ip(),browser_agent()]); }
    catch(Throwable $e) { error_log('[security log] '.$e->getMessage()); }
}
function is_ip_blocked(): bool { $s=db()->prepare('SELECT 1 FROM ip_blocks WHERE ip_address=? AND blocked_until>NOW() ORDER BY blocked_until DESC LIMIT 1'); $s->execute([client_ip()]); return (bool)$s->fetchColumn(); }
function count_recent_ip_failures(): int { $s=db()->prepare("SELECT COUNT(*) FROM security_logs WHERE ip_address=? AND event_type IN ('LOGIN_FAILED','OTP_FAILED','SECURITY_ANSWER_FAILED') AND created_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)"); $s->execute([client_ip()]); return (int)$s->fetchColumn(); }
function increment_account_failure(array $user, string $event, string $username): void {
    $n=(int)$user['failed_login_count']+1; $duration=$n>=9?60:($n>=6?30:($n>=3?15:0));
    db()->prepare('UPDATE users SET failed_login_count=?,last_failed_login_at=NOW(),locked_until=? WHERE user_id=?')->execute([$n,$duration?date('Y-m-d H:i:s',time()+$duration):null,$user['user_id']]);
    if(in_array($n,[3,6,9],true)){ $tier=(int)($n/3); log_event('LOCKOUT_TIER_'.$tier,'Account locked for '.$duration.' seconds after '.$n.' failed attempts',(int)$user['user_id'],$username); }
    record_failure($event,(int)$user['user_id'],$username);
    if(in_array($n,[3,6,9],true)){try { send_lock_alert($user,$n,$duration); } catch(Throwable $e) { error_log('[lock alert] '.$e->getMessage()); }}
}
function record_failure(string $event, ?int $userId=null, ?string $username=null): void {
    log_event($event,'Authentication attempt failed',$userId,$username);
    if (count_recent_ip_failures() >= 10) {
        $s=db()->prepare('INSERT INTO ip_blocks(ip_address,reason,blocked_until) VALUES(?,?,DATE_ADD(NOW(),INTERVAL 10 MINUTE))');
        $s->execute([client_ip(),'10 authentication failures within 10 minutes']);
        log_event('IP_BLOCKED','IP temporarily blocked for 10 minutes',$userId,$username);
    }
}
function encryption_key(): string {
    $raw = base64_decode((string)envv('APP_ENCRYPTION_KEY',''), true);
    if ($raw === false || strlen($raw)!==32) throw new RuntimeException('Set APP_ENCRYPTION_KEY to a Base64-encoded 32-byte key in .env.');
    return $raw;
}
function encrypt_value(string $plain): array {
    $iv=random_bytes(12); $tag=''; $cipher=openssl_encrypt($plain,'aes-256-gcm',encryption_key(),OPENSSL_RAW_DATA,$iv,$tag);
    if ($cipher===false) throw new RuntimeException('Unable to encrypt sensitive data.');
    return [$cipher.$tag,$iv];
}

function decrypt_value(string $stored, string $iv): string {
    if (strlen($stored) < 16) {
        throw new RuntimeException('Invalid encrypted data.');
    }

    $tag = substr($stored, -16);
    $cipher = substr($stored, 0, -16);

    $plain = openssl_decrypt(
        $cipher,
        'aes-256-gcm',
        encryption_key(),
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );

    if ($plain === false) {
        throw new RuntimeException('Unable to decrypt sensitive data.');
    }

    return $plain;
}

function send_otp_email(string $email,string $purpose,string $code,string $expiresAt): void {
    $autoload=dirname(__DIR__).'/vendor/autoload.php';
    if (!is_file($autoload)) throw new RuntimeException('PHPMailer is not installed. Run composer install, then configure SMTP.');
    require_once $autoload;
    $mail=new PHPMailer\PHPMailer\PHPMailer(true);
    try {
       $mail->isSMTP();
$mail->Host = (string)envv('SMTP_HOST', '127.0.0.1');
$mail->Port = (int)envv('SMTP_PORT', '1025');
$mail->SMTPAuth = false;
$mail->SMTPDebug = 0;
$mail->Debugoutput = function ($message, $level) {
    error_log('[SMTP DEBUG] ' . trim($message));
};
        $mail->SMTPAuth=(string)envv('SMTP_USER','')!==''; if($mail->SMTPAuth){$mail->Username=(string)envv('SMTP_USER');$mail->Password=(string)envv('SMTP_PASS');}
        $enc=(string)envv('SMTP_ENCRYPTION',''); if($enc!=='') $mail->SMTPSecure=$enc;
        $mail->setFrom((string)envv('SMTP_FROM','no-reply@example.test'),(string)envv('SMTP_FROM_NAME',APP_NAME));
        $mail->addAddress($email); $mail->isHTML(true); $mail->Subject=APP_NAME.' — '.ucfirst($purpose).' verification code';
        $safe=e($code); $mins=5; $expiryLabel=e($expiresAt).' (Philippine time)';
        $mail->Body='<h2>'.e(APP_NAME).'</h2><p>Purpose: '.e($purpose).'</p><p>Your one-time verification code is:</p><h1>'.$safe.'</h1><p>This code expires in '.$mins.' minutes, at '.$expiryLabel.'.</p><p>If you did not request this code, ignore this email and consider changing your password.</p>';
        $mail->AltBody=APP_NAME."\nPurpose: $purpose\nCode: $code\nExpires in 5 minutes, at $expiresAt Philippine time.\nIf you did not request this code, ignore this email and consider changing your password.";
        $mail->send();
    } catch(Throwable $e) { error_log('[SMTP] '.$e->getMessage()); throw new RuntimeException('Unable to send the verification email. Check SMTP settings.'); }
}
function issue_otp(int $userId,string $purpose,string $email,bool $isResend=false): void {
    $pdo=db();
    $limit=$pdo->prepare('SELECT COUNT(*) FROM email_resend_limits WHERE user_id=? AND purpose=? AND is_resend=1 AND sent_at>=DATE_SUB(NOW(),INTERVAL 15 MINUTE)');
    $limit->execute([$userId,$purpose]); if($isResend && (int)$limit->fetchColumn()>=3) throw new RuntimeException('You have reached the maximum of 3 OTP resends in 15 minutes. Please try again later.');
    $recent=$pdo->prepare('SELECT sent_at FROM email_resend_limits WHERE user_id=? AND purpose=? ORDER BY sent_at DESC LIMIT 1'); $recent->execute([$userId,$purpose]); $last=$recent->fetchColumn();
    if($last && (time()-strtotime((string)$last))<60) throw new RuntimeException('Please wait 60 seconds before requesting another code.');
    $code=(string)random_int(100000,999999);
    $pdo->prepare('UPDATE otp_challenges SET verified_at=NOW() WHERE user_id=? AND purpose=? AND verified_at IS NULL')->execute([$userId,$purpose]);
    $expiresAt=date('Y-m-d H:i:s',time()+300);
    $pdo->prepare('INSERT INTO otp_challenges(user_id,otp_hash,purpose,expires_at,attempts) VALUES(?,?,?,?,0)')->execute([$userId,password_hash($code,PASSWORD_DEFAULT),$purpose,$expiresAt]);
    $pdo->prepare('INSERT INTO email_resend_limits(user_id,purpose,is_resend) VALUES(?,?,?)')->execute([$userId,$purpose,$isResend?1:0]);
    send_otp_email($email,$purpose,$code,$expiresAt);
    $name=$pdo->prepare('SELECT username FROM users WHERE user_id=?'); $name->execute([$userId]);
    log_event('OTP_SENT','OTP email sent for '.$purpose,$userId,(string)$name->fetchColumn());
}
function current_user(): ?array { if(empty($_SESSION['user_id'])) return null; $s=db()->prepare('SELECT user_id,username,email,first_name,family_name,role,account_status FROM users WHERE user_id=?'); $s->execute([(int)$_SESSION['user_id']]); return $s->fetch() ?: null; }

function require_user(): array {
    if (empty($_SESSION['user_id'])) {
        go('login');
    }

    $last = (int)($_SESSION['last_activity'] ?? time());

    if (time() - $last >= 900) {
        log_event(
            'SESSION_IDLE_TIMEOUT',
            'Session expired due to 15-minute inactivity',
            (int)$_SESSION['user_id']
        );

        $_SESSION = [];
        session_destroy();
        go('login', ['expired' => '1']);
    }

    $user = current_user();

    if (!$user || $user['account_status'] !== 'active') {
        if ($user) {
            log_event(
                'INACTIVE_ACCOUNT_ACCESS',
                'Inactive account attempted to access a protected page',
                (int)$user['user_id'],
                $user['username']
            );
        }

        $_SESSION = [];
        session_destroy();
        go('login', ['disabled' => '1']);
    }

    $_SESSION['last_activity'] = time();

    return $user;
}
function require_role(string ...$roles): array { $u=require_user(); if(!in_array($u['role'],$roles,true)){log_event($u['role']==='admin'?'ADMIN_ACCESS_DENIED':'ACCESS_DENIED','Role access denied',(int)$u['user_id'],$u['username']);http_response_code(403);exit('Access denied.');} return $u; }
function normalize_answer(string $answer): string { $answer=trim($answer); return function_exists('mb_strtolower')?mb_strtolower($answer,'UTF-8'):strtolower($answer); }
function text_length(string $value): int { if(function_exists('mb_strlen'))return mb_strlen($value,'UTF-8');$count=preg_match_all('/./us',$value);return $count===false?strlen($value):$count; }
function password_strength(string $p): int { $score=0; if(strlen($p)>=8)$score++; if(preg_match('/[a-z]/',$p))$score++; if(preg_match('/[A-Z]/',$p))$score++; if(preg_match('/[0-9]/',$p))$score++; if(preg_match('/[^A-Za-z0-9]/',$p))$score++; return $score; }
function captcha_valid(): bool { $given=strtolower(trim((string)($_POST['captcha']??''))); $expected=strtolower((string)($_SESSION['captcha_answer']??'')); unset($_SESSION['captcha_answer']); return $given!=='' && $expected!=='' && hash_equals($expected,$given); }
function create_captcha_image(): never {
    if(!extension_loaded('gd')){http_response_code(503);exit;}
    $chars='ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; $answer=''; for($i=0;$i<5;$i++)$answer.=$chars[random_int(0,strlen($chars)-1)]; $_SESSION['captcha_answer']=$answer;
    $im=imagecreatetruecolor(160,48); $bg=imagecolorallocate($im,245,249,245); imagefill($im,0,0,$bg);
    for($i=0;$i<7;$i++){ $c=imagecolorallocate($im,random_int(120,200),random_int(120,200),random_int(120,200)); imageline($im,random_int(0,160),random_int(0,48),random_int(0,160),random_int(0,48),$c); }
    for($i=0;$i<45;$i++){ $noise=imagecolorallocate($im,random_int(150,220),random_int(150,220),random_int(150,220)); imagefilledellipse($im,random_int(0,160),random_int(0,48),2,2,$noise); }
    $ink=imagecolorallocate($im,25,75,45); imagestring($im,5,28,16,$answer,$ink); header('Content-Type:image/png'); header('Cache-Control:no-store'); imagepng($im); imagedestroy($im); exit;
}
function page_header(string $title, ?array $user=null): void { ?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=e($title)?> | <?=e(APP_NAME)?></title><link rel="stylesheet" href="assets/css/app.css"><script defer src="assets/js/app.js"></script></head><body data-authenticated="<?= $user ? '1' : '0' ?>" data-admin="<?= ($user && $user['role']==='admin') ? '1' : '0' ?>" data-lock-until="<?=e($_SESSION['login_lock_until']??'')?>"><div class="system-title"><?=e(APP_NAME)?></div><header class="top"><a class="brand" href="index.php"><?=e(APP_NAME)?></a><nav><a href="index.php">Home</a><?php if(!$user):?><?php if($title!=='Student Registration'):?><a class="register-link" href="index.php?page=register">Register</a><?php endif;?><?php if($title!=='Log-in'):?><a href="index.php?page=login">Log-in</a><?php endif;?><?php else:?><form method="post" action="index.php?page=logout" class="nav-logout"><input type="hidden" name="csrf_token" value="<?=e(csrf())?>"><button type="submit">Log-out</button></form><?php endif;?></nav></header><main class="wrap"><h1><?=e($title)?></h1><?php }
function page_footer(): void { ?></main><footer>© <?=date('Y')?> <?=e(APP_NAME)?> · Personal data is handled under RA 10173.</footer></body></html><?php }
function flash(string $message,string $type='info'): void { $_SESSION['flash']=['message'=>$message,'type'=>$type]; }
function show_flash(): void { if(!empty($_SESSION['flash'])){$f=$_SESSION['flash'];unset($_SESSION['flash']);echo '<div class="alert '.e($f['type']).'">'.e($f['message']).'</div>';}}

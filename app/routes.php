<?php
declare(strict_types=1);
require_once __DIR__.'/helpers.php';

function render_form_start(string $action): void { echo '<form method="post" action="index.php?page='.e($action).'" class="panel-form">'; echo '<input type="hidden" name="csrf_token" value="'.e(csrf()).'">'; }
function field(string $label,string $name,string $type='text',string $value='',string $extra=''): void { global $fieldErrors; $labelHtml=e($label); if(str_ends_with($label,' *'))$labelHtml=e(substr($label,0,-2)).' <span class="required">*</span>'; $labelHtml=str_replace('(optional)','<span class="optional">(optional)</span>',$labelHtml); echo '<label>'.$labelHtml.' <input type="'.e($type).'" name="'.e($name).'" value="'.e($value).'" '.$extra.'>'; if(!empty($fieldErrors[$name]))echo '<small class="field-error">'.e($fieldErrors[$name]).'</small>'; echo '</label>'; }
function err(string $message): void { echo '<div class="alert error">'.e($message).'</div>'; }
function register_page(): void {
    $GLOBALS['fieldErrors']=[]; $error='';
    if($_SERVER['REQUEST_METHOD']==='POST'){
        verify_csrf();
        try {
            if(trim((string)($_POST['website']??''))!==''){log_event('HONEYPOT_TRIGGERED','Registration honeypot was filled');go('register');}
            if(!captcha_valid()){log_event('CAPTCHA_FAILED','CAPTCHA failed during registration',null,(string)($_POST['username']??''));throw new RuntimeException('CAPTCHA answer is incorrect. Please try again.');}
            $p=$_POST; $required=['id_number','first_name','family_name','birthdate','street','barangay','municipality_city','province','country','zip_code','email','username','password','confirm_password','student_number','course_program','year_level','enrollment_semester','guardian_contact'];
            foreach($required as $f) if(trim((string)($p[$f]??''))==='') throw new RuntimeException(ucwords(str_replace('_',' ',$f)).' is required.');
            $id=trim($p['id_number']); if(!preg_match('/^[0-9]{4}-[0-9]{4}$/',$id))throw new RuntimeException('ID Number must follow the format xxxx-xxxx.');
            $nameFields=['first_name'=>'First Name','middle_name'=>'Middle Name','family_name'=>'Family Name'];
            foreach($nameFields as $f=>$label){
                $v=trim((string)($p[$f]??''));
                if($f==='middle_name'&&$v==='')continue;
                $len=preg_match_all('/./us',$v);
                $nameFail=function(string $message) use($f): never {
                    $GLOBALS['fieldErrors'][$f]=$message;
                    throw new RuntimeException($message);
                };
                if($len<2||$len>50)$nameFail($label.' must contain 2–50 characters.');
                if(preg_match('/[0-9]/',$v))$nameFail('Numbers are not allowed in '.$label.'.');
                if(preg_match('/[^\p{L} ]/u',$v))$nameFail('Special characters are not allowed in '.$label.'.');
                if(str_contains($v,'  '))$nameFail('Double space is not allowed.');
                if(preg_match('/(.)\1\1/u',$v))$nameFail('Three consecutive identical letters are not allowed.');
                if((function_exists('mb_strtoupper')?mb_strtoupper($v,'UTF-8'):strtoupper($v))===$v)$nameFail('Do not use all capital letters.');
                if(!preg_match('/^\p{Lu}/u',$v))$nameFail('First letter of your name must be capital.');
                $title=function_exists('mb_convert_case')?mb_convert_case(mb_strtolower($v,'UTF-8'),MB_CASE_TITLE,'UTF-8'):ucwords(strtolower($v));
                if($v!==$title)$nameFail('Capitalize the first letter of each name.');
            }
            $ext=trim((string)($p['name_extension']??''));if($ext==='Other')$ext=trim((string)($p['name_extension_other']??''));if($ext!==''&&!in_array($ext,['Jr.','Sr.','I','II','III','IV','IX','X'],true))throw new RuntimeException('Name Extension must be Jr., Sr., or Other with I, II, III, IV, IX, or X.');
            $bd=DateTime::createFromFormat('!Y-m-d',(string)$p['birthdate']);$age=$bd?(int)$bd->diff(new DateTime('today'))->y:0;if(!$bd||$bd->format('Y-m-d')!==$p['birthdate']||$bd>=new DateTime('today'))throw new RuntimeException('Birthdate must be a valid date in the past.');if($age<18)throw new RuntimeException('You must be at least 18 years old to register.');
            foreach(['street'=>'Purok/Street'] as $f=>$label){$v=trim((string)$p[$f]);if(strlen($v)<3||strlen($v)>50||!preg_match('/^[\p{L}0-9]+(?: [\p{L}0-9]+)*$/u',$v))throw new RuntimeException($label.' must be 3–50 letters, numbers, and single spaces.');}
            foreach(['barangay'=>'Barangay','municipality_city'=>'City/Municipality','province'=>'Province','country'=>'Country'] as $f=>$label){$v=trim((string)$p[$f]);if(strlen($v)<2||strlen($v)>50||!preg_match('/^\p{L}+(?: \p{L}+)*$/u',$v))throw new RuntimeException($label.' must contain 2–50 letters and single spaces only.');$title= function_exists('mb_convert_case')?mb_convert_case(mb_strtolower($v,'UTF-8'),MB_CASE_TITLE,'UTF-8'):ucwords(strtolower($v));if($v!==$title)throw new RuntimeException($label.' must use Title Case.');}
            if(!preg_match('/^[0-9]{4}$/',(string)$p['zip_code']))throw new RuntimeException('ZIP Code must contain exactly 4 digits.');
            $email=strtolower(trim($p['email']));if(strlen($email)>100||str_contains($email,' ')||!filter_var($email,FILTER_VALIDATE_EMAIL))throw new RuntimeException('Please enter a valid email address.');
            $username=trim($p['username']);if(!preg_match('/^[A-Za-z][A-Za-z0-9]{5,19}$/',$username))throw new RuntimeException('Username must be 6–20 letters or numbers and start with a letter.');
            $pw=(string)$p['password'];if(strlen($pw)<8||strlen($pw)>64)throw new RuntimeException('Password must contain 8–64 characters.');if(password_strength($pw)<=2)throw new RuntimeException('Password is Weak. Include uppercase, lowercase, number, and special character.');if($pw!==$p['confirm_password'])throw new RuntimeException('Mismatch Password.');
            if(!preg_match('/^[0-9]{4}-[0-9]{4}$/',(string)$p['student_number']))throw new RuntimeException('Student Number must follow the format XXXX-XXXX.');
            if(!preg_match('/^09[0-9]{9}$/',(string)$p['guardian_contact']))throw new RuntimeException('Guardian Contact Number must contain 11 digits and start with 09.');
            $qids=$_POST['security_question_id']??[];$answers=$_POST['security_answer']??[];if(!is_array($qids)||!is_array($answers)||count($qids)!==3||count($answers)!==3)throw new RuntimeException('Select three different security questions and provide all three answers.');$qids=array_values($qids);$answers=array_values($answers);foreach($qids as $qid){if(!is_scalar($qid))throw new RuntimeException('Select valid security questions from the question bank.');}$qidStrings=array_map('strval',$qids);if(count(array_unique($qidStrings))!==3)throw new RuntimeException('Select three different security questions and provide all three answers.');$qCheck=$pdo??db();$activeIds=array_map('strval',$qCheck->query('SELECT question_id FROM security_questions WHERE is_active=1')->fetchAll(PDO::FETCH_COLUMN));foreach($qids as $qid){if(!in_array((string)$qid,$activeIds,true))throw new RuntimeException('Select valid security questions from the question bank.');}foreach($answers as $a){if(!is_scalar($a))throw new RuntimeException('Each security answer must be 2–50 letters with single spaces only.');$a=trim((string)$a);if(!preg_match('/^[\p{L}]+(?: [\p{L}]+)*$/u',$a)||preg_match('/(.)\1\1/u',$a)||text_length($a)<2||text_length($a)>50)throw new RuntimeException('Each security answer must be 2–50 letters with single spaces only.');}
            if(empty($p['privacy_consent']))throw new RuntimeException('You must agree to the Privacy Notice to register.');
            $pdo=db();$pdo->beginTransaction();
            $check=$pdo->prepare('SELECT id_number,email,username FROM users WHERE id_number=? OR email=? OR username=?');$check->execute([$id,$email,$username]);$existing=$check->fetchAll();foreach($existing as $x){if($x['id_number']===$id)throw new RuntimeException('This ID Number is already registered.');if($x['email']===$email)throw new RuntimeException('This Email Address is already registered.');if($x['username']===$username)throw new RuntimeException('This Username is already taken.');}
            $s=$pdo->prepare('SELECT student_id FROM student_profiles WHERE student_number=?');$s->execute([$p['student_number']]);if($s->fetch())throw new RuntimeException('This Student Number is already registered.');
            [$cipher,$iv]=encrypt_value(trim($p['guardian_contact']));
            
            $ins=$pdo->prepare('INSERT INTO users(id_number,first_name,middle_name,family_name,name_extension,birthdate,street,barangay,municipality_city,province,country,zip_code,email,username,password_hash,role,privacy_consent,privacy_consent_at,account_status) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,\'student\',1,NOW(),\'pending_verification\')');
            $ins->execute([$id,trim($p['first_name']),trim((string)($p['middle_name']??''))?:null,trim($p['family_name']),$ext?:null,$p['birthdate'],trim($p['street']),trim($p['barangay']),trim($p['municipality_city']),trim($p['province']),trim($p['country']),$p['zip_code'],$email,$username,password_hash($pw,PASSWORD_DEFAULT)]);$uid=(int)$pdo->lastInsertId();
            $st=$pdo->prepare('INSERT INTO student_profiles(user_id,student_number,course_program,year_level,enrollment_semester,guardian_contact_ciphertext,guardian_contact_iv,encryption_key_version) VALUES(?,?,?,?,?,?,?,\'v1\')');$st->execute([$uid,$p['student_number'],$p['course_program'],$p['year_level'],$p['enrollment_semester'],$cipher,$iv]);
            $ans=$pdo->prepare('INSERT INTO user_security_answers(user_id,question_id,answer_hash) VALUES(?,?,?)');foreach(array_values($qids) as $i=>$qid){$answer=normalize_answer((string)$answers[$i]);$ans->execute([$uid,(int)$qid,password_hash($answer,PASSWORD_DEFAULT)]);}
            $pdo->commit();log_event('REGISTERED','Student registration created; awaiting email verification',$uid,$username);$_SESSION['pending_user_id']=$uid;issue_otp($uid,'register',$email);flash('Registration saved. Enter the 6-digit code sent to your email.','success');go('otp',['purpose'=>'register']);
        }catch(Throwable $ex){if(isset($pdo)&&$pdo instanceof PDO&&$pdo->inTransaction())$pdo->rollBack();$error=public_error_message($ex);$fieldMap=['ID Number'=>'id_number','First Name'=>'first_name','Middle Name'=>'middle_name','Family Name'=>'family_name','Name Extension'=>'name_extension','Birthdate'=>'birthdate','Purok/Street'=>'street','Barangay'=>'barangay','City/Municipality'=>'municipality_city','Province'=>'province','Country'=>'country','ZIP Code'=>'zip_code','Email Address'=>'email','email address'=>'email','Username'=>'username','Password'=>'password','Re-enter Password'=>'confirm_password','Student Number'=>'student_number','Guardian Contact Number'=>'guardian_contact','Guardian Contact'=>'guardian_contact','Mismatch Password'=>'confirm_password','Course/Program'=>'course_program','year level'=>'year_level','semester'=>'enrollment_semester','course or program'=>'course_program','available semester'=>'enrollment_semester','at least 18'=>'birthdate','Municipality City'=>'municipality_city','CAPTCHA'=>'captcha'];foreach($fieldMap as $needle=>$fieldName){if(stripos($error,$needle)!==false){$GLOBALS['fieldErrors'][$fieldName]=$error;break;}}}
    }
    page_header('Student Registration');show_flash();if($error)err($error);if(!empty($_SESSION['pending_user_id']))echo '<p class="alert warning">Your account was created but the email code may not have been delivered. <a href="index.php?page=otp&amp;purpose=register">Continue to email verification</a>.</p>'; 
    ?><form method="post" class="panel-form" id="registerForm"><input type="hidden" name="csrf_token" value="<?=e(csrf())?>"><input class="hp" name="website" tabindex="-1" autocomplete="off"><h2>Personal Information</h2><div class="grid">
    <?php field('ID Number *','id_number','text',$_POST['id_number']??'','placeholder="xxxx-xxxx" maxlength="9" required');field('First Name *','first_name','text',$_POST['first_name']??'','maxlength="50" required');field('Middle Name (optional)','middle_name','text',$_POST['middle_name']??'','maxlength="50"');field('Family Name *','family_name','text',$_POST['family_name']??'','maxlength="50" required');?><label>Name Extension <span class="optional">(optional)</span><select name="name_extension"><option value="">None</option><?php foreach(['Jr.','Sr.','Other'] as $x):?><option><?=e($x)?></option><?php endforeach;?></select></label><label id="extensionOtherWrap" hidden>Other Name Extension<input name="name_extension_other" maxlength="5" placeholder="I, II, III, IV, IX, X"></label><?php field('Birthdate *','birthdate','date',$_POST['birthdate']??'','max="'.date('Y-m-d',strtotime('-18 years')).'" required');?><label>Age (automatic/read-only)<input id="agePreview" readonly placeholder="Calculated from birthdate"></label></div>
    <h2>Address</h2><div class="grid"><?php foreach(['street'=>'Purok/Street','barangay'=>'Barangay','municipality_city'=>'Municipality/City','province'=>'Province','country'=>'Country'] as $k=>$v)field($v.' *',$k,'text',$_POST[$k]??'','maxlength="50" required');?><?php field('ZIP Code *','zip_code','text',$_POST['zip_code']??'','maxlength="4" inputmode="numeric" required');?></div>
    <h2>Account Information</h2><div class="grid"><?php field('Email Address *','email','email',$_POST['email']??'','maxlength="100" required');field('Username *','username','text',$_POST['username']??'','id="usernameField" minlength="6" maxlength="20" required');echo '<small id="usernameStatus" aria-live="polite"></small>'; ?><label>Password *<input type="password" name="password" id="password" minlength="8" maxlength="64" required><small id="strengthText">Password strength: —</small><?php if(!empty($GLOBALS['fieldErrors']['password'])):?><small class="field-error"><?=e($GLOBALS['fieldErrors']['password'])?></small><?php endif;?></label><?php field('Re-enter Password *','confirm_password','password','','minlength="8" maxlength="64" required');?></div>
    <h2>School Information</h2><div class="grid"><?php field('Student Number *','student_number','text',$_POST['student_number']??'','placeholder="2026-0001" maxlength="9" required');?><label>Course/Program *<select name="course_program" required><option value="">Select</option><?php foreach(['BSIT','BSCS','BSIS','BSA','BSBA','Other'] as $x):?><option><?=e($x)?></option><?php endforeach;?></select></label><label>Year Level *<select name="year_level" required><option value="">Select</option><?php foreach(['1st Year','2nd Year','3rd Year','4th Year','5th Year'] as $x):?><option><?=e($x)?></option><?php endforeach;?></select></label><label>Enrollment Semester *<select name="enrollment_semester" required><option value="">Select</option><?php foreach(['1st Semester','2nd Semester','Summer'] as $x):?><option><?=e($x)?></option><?php endforeach;?></select></label><?php field('Parent/Guardian Contact * (encrypted)','guardian_contact','tel',$_POST['guardian_contact']??'','placeholder="09XXXXXXXXX" maxlength="11" required');?></div>
    <h2>Security Questions</h2><p>Select three different questions and provide an answer for each.</p><?php $qs=db()->query('SELECT question_id,question_text FROM security_questions WHERE is_active=1 ORDER BY question_id')->fetchAll();for($i=0;$i<3;$i++):?><div class="security-question-row"><label>Security Question <?=($i+1)?> *<select name="security_question_id[]" class="security-question-select" required><option value="">Choose a question</option><?php foreach($qs as $q):?><option value="<?= (int)$q['question_id'] ?>"><?=e($q['question_text'])?></option><?php endforeach;?></select></label><?php field('Answer *','security_answer[]','text','','minlength="2" maxlength="50" required'); ?></div><?php endfor;?>
    <h2>Privacy Notice</h2><p class="notice">We collect your identity, contact, address, school and guardian contact details to create and secure your student account, prepare tuition assessments, monitor payments, and provide account recovery. Guardian contact is encrypted using AES-256-GCM. Passwords and security answers are hashed. Access is limited by role. Records are retained only as required for school operations and applicable law. For privacy concerns, contact the school system administrator.</p><label class="check"><input type="checkbox" name="privacy_consent" value="1" required><span>I have read the Privacy Notice and agree to the collection and processing of my personal data in accordance with the Data Privacy Act of 2012 (RA 10173).</span></label>
    <div class="captcha"><img src="index.php?page=captcha" alt="CAPTCHA"><button type="button" class="secondary refresh-captcha">Refresh CAPTCHA</button></div><?php field('Enter CAPTCHA *','captcha','text','','maxlength="5" required autocomplete="off"');?>
    <button type="submit">Create Student Account</button><p class="small">Already registered? <a href="index.php?page=login">Log in</a></p></form><?php page_footer();
}

function login_page(): void {
    $error=''; if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();try{if(is_ip_blocked())throw new RuntimeException('This IP address is temporarily blocked. Please try again after 10 minutes.');if(!captcha_valid()){log_event('CAPTCHA_FAILED','CAPTCHA failed during login',null,(string)($_POST['username']??''));throw new RuntimeException('CAPTCHA answer is incorrect.');}$typedUsername=(string)($_POST['username']??'');$u=trim($typedUsername);$s=db()->prepare('SELECT * FROM users WHERE username=? LIMIT 1');$s->execute([$u]);$user=$s->fetch();if($user&&!empty($user['locked_until'])&&strtotime((string)$user['locked_until'])>time()){$_SESSION['login_lock_until']=strtotime((string)$user['locked_until']);throw new RuntimeException('Invalid username or password.');}if(!$user||!password_verify((string)($_POST['password']??''),(string)$user['password_hash'])||$user['account_status']!=='active'){if($user){$newFailureCount=(int)$user['failed_login_count']+1;increment_account_failure($user,'LOGIN_FAILED',$typedUsername);if(in_array($newFailureCount,[3,6,9],true)){$dur=$newFailureCount>=9?60:($newFailureCount>=6?30:15);$_SESSION['login_lock_until']=time()+$dur;}else{unset($_SESSION['login_lock_until']);}}else{record_failure('LOGIN_FAILED',null,$typedUsername);}$_SESSION['login_fail_count']=($_SESSION['login_fail_count']??0)+1;throw new RuntimeException('Invalid username or password.');}$_SESSION['pending_user_id']=(int)$user['user_id'];issue_otp((int)$user['user_id'],'login',$user['email']);go('otp',['purpose'=>'login']);}catch(Throwable $e){$error=public_error_message($e);}}
    page_header('Log-in');show_flash();if($error)err($error);if(!empty($_SESSION['pending_user_id']))echo '<p class="alert warning"><a href="index.php?page=otp&amp;purpose=login">Continue to email OTP verification</a></p>';?><form method="post" class="panel-form narrow"><input type="hidden" name="csrf_token" value="<?=e(csrf())?>"><?php field('Username *','username','text','','required autocomplete="username"');?><label>Password *<input type="password" name="password" id="loginPassword" required autocomplete="current-password"></label><label class="check"><input type="checkbox" data-toggle-password="loginPassword"> Show Password</label><div class="captcha"><img src="index.php?page=captcha" alt="CAPTCHA"><button type="button" class="secondary refresh-captcha">Refresh</button></div><?php field('CAPTCHA *','captcha','text','','maxlength="5" required autocomplete="off"');?><button type="submit" id="loginSubmit">Continue</button><div id="lockCountdown" class="alert warning" hidden></div><?php if(($_SESSION['login_fail_count']??0)>=2):?><p><a href="index.php?page=forgot">Forgot Password?</a></p><?php endif;?><p class="small">No account? <a class="register-link" href="index.php?page=register">Please register here</a></p></form><?php page_footer();
}
function send_lock_alert(array $user,int $count,int $seconds): void { try{$subject=APP_NAME.' — Account security alert';$body='<h2>Account temporarily locked</h2><p>Failed attempts: '.(int)$count.'</p><p>Time (Philippine time): '.e(date('Y-m-d H:i:s')).'</p><p>IP: '.e(client_ip()).'</p><p>Browser: '.e(browser_agent()).'</p><p>Lock duration: '.(int)$seconds.' seconds. If this was not you, reset your password now. Use Forgot Password: '.e(APP_URL.'/index.php?page=forgot').'</p>';send_plain_security_email($user['email'],$subject,$body);log_event('ALERT_EMAIL_SENT','Lockout alert sent',(int)$user['user_id'],$user['username']);}catch(Throwable $e){log_event('ALERT_EMAIL_FAILED','Lockout alert email could not be sent',(int)$user['user_id'],$user['username']);error_log('[lock alert] '.$e->getMessage());}}
function send_plain_security_email(string $to,string $subject,string $html): void { $autoload=dirname(__DIR__).'/vendor/autoload.php';if(!is_file($autoload))throw new RuntimeException('PHPMailer unavailable');require_once $autoload;$m=new PHPMailer\PHPMailer\PHPMailer(true);$m->isSMTP();$m->Host=(string)envv('SMTP_HOST','127.0.0.1');$m->Port=(int)envv('SMTP_PORT','1025');$secure=(string)envv('SMTP_ENCRYPTION','');if($secure!=='')$m->SMTPSecure=$secure;$m->SMTPAuth=(string)envv('SMTP_USER','')!=='';if($m->SMTPAuth){$m->Username=(string)envv('SMTP_USER');$m->Password=(string)envv('SMTP_PASS');}$m->setFrom((string)envv('SMTP_FROM','no-reply@example.test'),(string)envv('SMTP_FROM_NAME',APP_NAME));$m->addAddress($to);$m->isHTML(true);$m->Subject=$subject;$m->Body=$html;$m->send();}
function otp_page(): void {
    $purpose=(string)($_GET['purpose']??'login');if(!in_array($purpose,['register','login','reset'],true))go('login');$error='';$uid=(int)($_SESSION['pending_user_id']??0);if(!$uid)go('login');
    if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();try{if(is_ip_blocked())throw new RuntimeException('This IP address is temporarily blocked. Please try again after 10 minutes.');$code=trim((string)($_POST['otp']??''));if(!preg_match('/^[0-9]{6}$/',$code))throw new RuntimeException('Enter the 6-digit verification code.');$s=db()->prepare('SELECT * FROM otp_challenges WHERE user_id=? AND purpose=? AND verified_at IS NULL ORDER BY otp_id DESC LIMIT 1');$s->execute([$uid,$purpose]);$otp=$s->fetch();if(!$otp||strtotime($otp['expires_at'])<=time()||(int)$otp['attempts']>=3)throw new RuntimeException('The code is expired or no longer valid. Request a new code.');if(!password_verify($code,$otp['otp_hash'])){db()->prepare('UPDATE otp_challenges SET attempts=attempts+1 WHERE otp_id=?')->execute([$otp['otp_id']]);$nm=db()->prepare('SELECT username FROM users WHERE user_id=?');$nm->execute([$uid]);record_failure('OTP_FAILED',$uid,(string)$nm->fetchColumn());if((int)$otp['attempts']+1>=3)throw new RuntimeException('Three incorrect attempts. This code has been cancelled. Request a new code.');throw new RuntimeException('Incorrect verification code.');}db()->prepare('UPDATE otp_challenges SET verified_at=NOW() WHERE otp_id=?')->execute([$otp['otp_id']]);if($purpose==='register'){db()->prepare('UPDATE users SET email_verified_at=NOW(),account_status=\'active\' WHERE user_id=?')->execute([$uid]);$nm=db()->prepare('SELECT username FROM users WHERE user_id=?');$nm->execute([$uid]);log_event('ACCOUNT_VERIFIED','Student email address verified',$uid,(string)$nm->fetchColumn());unset($_SESSION['pending_user_id']);flash('Email verified. You can now log in.','success');go('login');}if($purpose==='login'){session_regenerate_id(true);$_SESSION['user_id']=$uid;$_SESSION['last_activity']=time();db()->prepare('UPDATE users SET failed_login_count=0,locked_until=NULL,last_login_at=NOW() WHERE user_id=?')->execute([$uid]);$user=db()->prepare('SELECT username FROM users WHERE user_id=?');$user->execute([$uid]);log_event('LOGIN_SUCCESS','User completed OTP login',$uid,(string)$user->fetchColumn());unset($_SESSION['pending_user_id'],$_SESSION['login_fail_count'],$_SESSION['login_lock_until']);go('dashboard');}if($purpose==='reset'){$_SESSION['reset_authorized_user_id']=$uid;unset($_SESSION['pending_user_id']);go('change_password');}}catch(Throwable $e){$error=public_error_message($e);}}
    $lim=db()->prepare('SELECT sent_at FROM email_resend_limits WHERE user_id=? AND purpose=? ORDER BY sent_at DESC LIMIT 1');$lim->execute([$uid,$purpose]);$lastSent=$lim->fetchColumn();$remaining=$lastSent?max(0,60-(time()-strtotime((string)$lastSent))):0;$countQ=db()->prepare('SELECT COUNT(*) FROM email_resend_limits WHERE user_id=? AND purpose=? AND is_resend=1 AND sent_at>=DATE_SUB(NOW(),INTERVAL 15 MINUTE)');$countQ->execute([$uid,$purpose]);$limitReached=(int)$countQ->fetchColumn()>=3;
    page_header('Email Verification');show_flash();if($error)err($error);?><form method="post" class="panel-form narrow"><input type="hidden" name="csrf_token" value="<?=e(csrf())?>"><p>Enter the 6-digit code sent to your registered email. It expires in 5 minutes.</p><?php field('Verification Code *','otp','text','','inputmode="numeric" maxlength="6" required');?><button>Verify Code</button></form><form method="post" action="index.php?page=resend_otp&amp;purpose=<?=e($purpose)?>" class="panel-form narrow"><input type="hidden" name="csrf_token" value="<?=e(csrf())?>"><button class="secondary" id="resendOtpButton" type="submit" <?=$remaining>0||$limitReached?'disabled':''?>>Resend code</button><small id="resendOtpCountdown" data-remaining="<?=$remaining?>" data-limit="<?=$limitReached?'1':'0'?>"><?=$limitReached?'Maximum 3 OTP resends reached in 15 minutes.':($remaining>0?'You can resend in '.$remaining.' seconds.':'You may request another code.')?></small></form><?php page_footer();
}
function resend_otp_page(): never {
    $uid=(int)($_SESSION['pending_user_id']??0);$purpose=(string)($_GET['purpose']??'login');if(!$uid||!in_array($purpose,['register','login','reset'],true))go('login');
    if($_SERVER['REQUEST_METHOD']!=='POST')go('otp',['purpose'=>$purpose]);verify_csrf();
    $s=db()->prepare('SELECT email FROM users WHERE user_id=?');$s->execute([$uid]);$email=(string)$s->fetchColumn();
    try{if(is_ip_blocked())throw new RuntimeException('This IP address is temporarily blocked. Please try again after 10 minutes.');issue_otp($uid,$purpose,$email,true);flash('A new verification code has been sent.','success');}catch(Throwable $e){flash(public_error_message($e),'error');}go('otp',['purpose'=>$purpose]);
}

function forgot_page(): void {
    $error='';
    $step=(string)($_SESSION['forgot_step']??'username');
    $user=null;
    $questions=[];
    if($step==='answers' && !empty($_SESSION['forgot_username'])){
        $find=db()->prepare('SELECT user_id,email,username,locked_until,failed_login_count FROM users WHERE username=? LIMIT 1');
        $find->execute([(string)$_SESSION['forgot_username']]);
        $user=$find->fetch() ?: null;
        if($user){
            $q=db()->prepare('SELECT sq.question_id,sq.question_text,usa.answer_hash FROM user_security_answers usa JOIN security_questions sq ON sq.question_id=usa.question_id WHERE usa.user_id=? ORDER BY usa.question_id');
            $q->execute([(int)$user['user_id']]);
            $questions=$q->fetchAll();
        }
        if(count($questions)!==3){
            $fallback=db()->query("SELECT question_id,question_text,'' AS answer_hash FROM security_questions WHERE is_active=1")->fetchAll();
            shuffle($fallback);
            $questions=array_slice($fallback,0,3);
        }
    }
    if($_SERVER['REQUEST_METHOD']==='POST'){
        verify_csrf();
        try{
            if(is_ip_blocked()) throw new RuntimeException('The supplied information could not be verified.');
            if($step==='username'){
                if(!captcha_valid()){
                    log_event('CAPTCHA_FAILED','CAPTCHA failed during password recovery',null,(string)($_POST['username']??''));
                    throw new RuntimeException('The supplied information could not be verified.');
                }
                $_SESSION['forgot_username']=trim((string)($_POST['username']??''));
                log_event('RESET_REQUESTED','Password recovery was requested',null,(string)($_POST['username']??''));
                $_SESSION['forgot_step']='answers';
                go('forgot');
            }
            if($step==='answers'){
                $accountLocked=$user && !empty($user['locked_until']) && strtotime((string)$user['locked_until'])>time();
                $ok=(bool)$user && count($questions)===3 && !$accountLocked;
                $answers=$_POST['security_answer']??[];
                $confirmAnswers=$_POST['security_answer_confirm']??[];
                $postedIds=$_POST['security_question_id']??[];
                if(!is_array($answers)||!is_array($confirmAnswers)||!is_array($postedIds)) $ok=false;
                foreach($questions as $i=>$question){
                    $rawAnswer=$answers[$i]??null;
                    $rawConfirm=$confirmAnswers[$i]??null;
                    $rawQuestionId=$postedIds[$i]??null;
                    if(!is_scalar($rawAnswer)||!is_scalar($rawConfirm)||!is_scalar($rawQuestionId)){$ok=false;continue;}
                    $answer=normalize_answer((string)$rawAnswer);
                    $confirm=normalize_answer((string)$rawConfirm);
                    $validFormat=preg_match('/^[\p{L}]+(?: [\p{L}]+)*$/u',$answer) && !preg_match('/(.)\1\1/u',$answer) && text_length($answer)>=2 && text_length($answer)<=50;
                    if((int)$rawQuestionId !== (int)$question['question_id'] || !$validFormat || $answer!==$confirm || !password_verify($answer,(string)$question['answer_hash'])) $ok=false;
                }
                if(!$ok){
                    if($user && !$accountLocked){increment_account_failure($user,'SECURITY_ANSWER_FAILED',(string)$user['username']);}
                    elseif(!$user){record_failure('SECURITY_ANSWER_FAILED',null,(string)($_SESSION['forgot_username']??''));}
                    throw new RuntimeException('The supplied information could not be verified.');
                }
                $_SESSION['pending_user_id']=(int)$user['user_id'];
                $_SESSION['forgot_step']='otp';
                issue_otp((int)$user['user_id'],'reset',(string)$user['email']);
                go('otp',['purpose'=>'reset']);
            }
        }catch(Throwable $e){$error=public_error_message($e);}
    }
    page_header('Forgot Password');
    show_flash();
    if($error) err($error);
    if(!empty($_SESSION['pending_user_id']) && $step==='otp') echo '<p class="alert warning"><a href="index.php?page=otp&amp;purpose=reset">Continue to reset OTP verification</a></p>';
    if($step==='username'){
        ?><form method="post" class="panel-form narrow"><input type="hidden" name="csrf_token" value="<?=e(csrf())?>"><?php field('Username *','username','text','','required');?><div class="captcha"><img src="index.php?page=captcha" alt="CAPTCHA"><button type="button" class="secondary refresh-captcha">Refresh</button></div><?php field('CAPTCHA *','captcha','text','','maxlength="5" required');?><button>Continue</button></form><?php
    }else{
        ?><form method="post" class="panel-form narrow"><input type="hidden" name="csrf_token" value="<?=e(csrf())?>"><p>Choose the following questions registered to your account and provide matching answers. If the account cannot be verified, the same generic response will be shown.</p><?php
        if(count($questions)===3){foreach($questions as $i=>$question):?><p><strong>Choose the following question:</strong> <?=e($question['question_text'])?><input type="hidden" name="security_question_id[]" value="<?= (int)$question['question_id'] ?>"></p><label>Your Answer *<input name="security_answer[]" placeholder="Your Answer" required minlength="2" maxlength="50"></label><label>Re-enter Answer *<input name="security_answer_confirm[]" placeholder="Re-enter Answer" required minlength="2" maxlength="50"></label><?php endforeach;}else{for($i=1;$i<=3;$i++):?><p><strong>Choose the following question:</strong> Registered security question <?=$i?></p><label>Your Answer *<input name="security_answer[]" placeholder="Your Answer" required minlength="2" maxlength="50"></label><label>Re-enter Answer *<input name="security_answer_confirm[]" placeholder="Re-enter Answer" required minlength="2" maxlength="50"></label><?php endfor;}
        ?><button>Verify Answers</button></form><?php
    }
    page_footer();
}

function change_password_page(): void {
    $uid=(int)($_SESSION['reset_authorized_user_id']??0);if(!$uid){http_response_code(403);exit('Access denied. Complete the Forgot Password verification first.');}$error='';if($_SERVER['REQUEST_METHOD']==='POST'){verify_csrf();try{$pw=(string)($_POST['password']??'');if(strlen($pw)<8||strlen($pw)>64||password_strength($pw)<=2)throw new RuntimeException('Password is Weak. Add a mix of uppercase, lowercase, numbers, and special characters.');if($pw!==($_POST['confirm_password']??''))throw new RuntimeException('Mismatch Password.');$pdo=db();$s=$pdo->prepare('SELECT password_hash,email,username FROM users WHERE user_id=?');$s->execute([$uid]);$user=$s->fetch();$hist=$pdo->prepare('SELECT password_hash FROM password_history WHERE user_id=? ORDER BY created_at DESC LIMIT 2');$hist->execute([$uid]);$old=$hist->fetchAll();if(password_verify($pw,$user['password_hash']))throw new RuntimeException('You cannot reuse a recent password.');foreach($old as $h)if(password_verify($pw,$h['password_hash']))throw new RuntimeException('You cannot reuse a recent password.');$pdo->beginTransaction();$pdo->prepare('INSERT INTO password_history(user_id,password_hash) VALUES(?,?)')->execute([$uid,$user['password_hash']]);$pdo->prepare('UPDATE users SET password_hash=?,failed_login_count=0,locked_until=NULL WHERE user_id=?')->execute([password_hash($pw,PASSWORD_DEFAULT),$uid]);$pdo->commit();log_event('PASSWORD_CHANGED','Password changed after recovery',$uid,$user['username']);$mailNotice='';try{send_plain_security_email($user['email'],APP_NAME.' — Password changed','<p>Successfully Change Password.</p><p>If this was not you, contact the school administrator immediately.</p>');log_event('ALERT_EMAIL_SENT','Password-changed alert email sent',$uid,$user['username']);}catch(Throwable $mailError){log_event('ALERT_EMAIL_FAILED','Password changed but alert email could not be sent',$uid,$user['username']);$mailNotice=' The password was changed, but the email alert could not be sent; contact the administrator.';}unset($_SESSION['reset_authorized_user_id'],$_SESSION['forgot_step'],$_SESSION['forgot_username']);flash('Successfully Change Password.'.$mailNotice,$mailNotice?'warning':'success');go('login');}catch(Throwable $e){if(isset($pdo)&&$pdo instanceof PDO&&$pdo->inTransaction())$pdo->rollBack();$error=public_error_message($e);}}
    page_header('Change Password');if($error)err($error);?><form method="post" class="panel-form narrow"><input type="hidden" name="csrf_token" value="<?=e(csrf())?>"><label>Enter Password *<input type="password" name="password" id="changePassword" minlength="8" maxlength="64" required autocomplete="new-password"><small id="changeStrengthText">Password strength: —</small></label><?php field('Re-enter Password *','confirm_password','password','','minlength="8" maxlength="64" required autocomplete="new-password"');?><button>Save New Password</button></form><?php page_footer();
}

function dashboard_page(): void {
    $u=require_user();page_header('Dashboard',$u);show_flash();
    if($u['role']==='admin'){admin_dashboard($u);page_footer();return;}
    if($u['role']==='cashier'){cashier_dashboard($u);page_footer();return;}
    $s=db()->prepare('SELECT sp.student_id,sp.student_number,sp.course_program,sp.year_level,sp.guardian_contact_ciphertext,sp.guardian_contact_iv FROM student_profiles sp WHERE sp.user_id=?');$s->execute([$u['user_id']]);$student=$s->fetch();
    if(!$student){echo '<div class="alert warning">Student profile is not available.</div>';page_footer();return;}
    $guardianContact=decrypt_value((string)$student['guardian_contact_ciphertext'],(string)$student['guardian_contact_iv']);
    $a=db()->prepare("SELECT ta.*,COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.assessment_id=ta.assessment_id AND p.status='verified'),0) AS paid FROM tuition_assessments ta WHERE ta.student_id=? ORDER BY ta.created_at DESC");$a->execute([$student['student_id']]);$assessments=$a->fetchAll();     ?>
    <section class="cards"><article class="card"><h3>Student</h3><p><?=e($u['first_name'].' '.$u['family_name'])?></p><small><?=e($student['student_number'])?> · <?=e($student['course_program'])?> · <?=e($student['year_level'])?></small><small>Parent/Guardian Contact: <?=e($guardianContact)?></small></article><article class="card"><h3>Assessments</h3><p><?=count($assessments)?></p></article><article class="card"><h3>Total Outstanding</h3><p>₱<?=number_format(array_sum(array_map(fn($x)=>(float)$x['total_assessed']-(float)$x['paid'],$assessments)),2)?></p></article></section>
    <section class="panel"><h2>Tuition Assessments</h2><?php if(!$assessments):?><p>No tuition assessment has been posted yet.</p><?php else:?><div class="table-wrap"><table><tr><th>School Year</th><th>Semester</th><th>Assessed</th><th>Verified Paid</th><th>Balance</th><th>Status</th></tr><?php foreach($assessments as $x):?><tr><td><?=e($x['school_year'])?></td><td><?=e($x['semester'])?></td><td>₱<?=number_format((float)$x['total_assessed'],2)?></td><td>₱<?=number_format((float)$x['paid'],2)?></td><td>₱<?=number_format(max(0,(float)$x['total_assessed']-(float)$x['paid']),2)?></td><td><?=e($x['assessment_status'])?></td></tr><?php endforeach;?></table></div><?php endif;?></section>
    <section class="panel"><h2>Submit Payment</h2><form method="post" action="index.php?page=payment_submit" enctype="multipart/form-data" class="panel-form"><input type="hidden" name="csrf_token" value="<?=e(csrf())?>"><label>Tuition Assessment *<select name="assessment_id" required><option value="">Select assessment</option><?php foreach($assessments as $x):?><option value="<?=$x['assessment_id']?>"><?=e($x['school_year'].' / '.$x['semester'].' — Balance ₱'.number_format(max(0,(float)$x['total_assessed']-(float)$x['paid']),2))?></option><?php endforeach;?></select></label><?php field('Amount *','amount','number','','min="0.01" step="0.01" required');?><label>Payment Method *<select name="payment_method" required><option value="cash">Cash</option><option value="bank_transfer">Bank Transfer</option><option value="gcash">GCash</option><option value="other">Other</option></select></label><?php field('Reference Number (optional)','reference_number');field('Payment Date *','payment_date','date',date('Y-m-d'),'required');?><label>Proof of Payment (JPG/PNG/PDF, max 3MB)<input type="file" name="proof" accept="image/jpeg,image/png,application/pdf"></label><button>Submit Payment for Review</button></form></section>
    <section class="panel"><h2>Payment History</h2><?php $p=db()->prepare('SELECT p.*,ta.school_year,ta.semester FROM payments p JOIN tuition_assessments ta ON ta.assessment_id=p.assessment_id WHERE ta.student_id=? ORDER BY p.submitted_at DESC');$p->execute([$student['student_id']]);$payments=$p->fetchAll();if(!$payments):?><p>No payment submissions yet.</p><?php else:?><div class="table-wrap"><table><tr><th>Date</th><th>Amount</th><th>Method</th><th>Status</th><th>Review note</th></tr><?php foreach($payments as $x):?><tr><td><?=e($x['payment_date'])?></td><td>₱<?=number_format((float)$x['amount'],2)?></td><td><?=e($x['payment_method'])?></td><td><?=e($x['status'])?></td><td><?=e($x['rejection_reason']??'—')?></td></tr><?php endforeach;?></table></div><?php endif;?></section><?php page_footer();
}
function payment_submit_page(): never {
    $u=require_role('student');if($_SERVER['REQUEST_METHOD']!=='POST'){go('dashboard');}verify_csrf();$s=db()->prepare('SELECT student_id FROM student_profiles WHERE user_id=?');$s->execute([$u['user_id']]);$student=$s->fetch();if(!$student)go('dashboard');
    try{$assessment=(int)($_POST['assessment_id']??0);$a=db()->prepare('SELECT assessment_id FROM tuition_assessments WHERE assessment_id=? AND student_id=?');$a->execute([$assessment,$student['student_id']]);if(!$a->fetch())throw new RuntimeException('Please select a valid tuition assessment.');$amount=(float)($_POST['amount']??0);if($amount<=0)throw new RuntimeException('Payment amount must be greater than zero.');$method=(string)($_POST['payment_method']??'');if(!in_array($method,['cash','bank_transfer','gcash','other'],true))throw new RuntimeException('Select a valid payment method.');$date=(string)($_POST['payment_date']??'');if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date))throw new RuntimeException('Enter a valid payment date.');$path=null;if(!empty($_FILES['proof']['name'])){$f=$_FILES['proof'];if($f['error']!==UPLOAD_ERR_OK||$f['size']>3*1024*1024)throw new RuntimeException('Proof file must be 3MB or smaller.');$mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);$ext=['image/jpeg'=>'jpg','image/png'=>'png','application/pdf'=>'pdf'][$mime]??null;if(!$ext)throw new RuntimeException('Proof must be a JPG, PNG, or PDF file.');$name=bin2hex(random_bytes(16)).'.'.$ext;$dest=dirname(__DIR__).'/storage/uploads/'.$name;if(!move_uploaded_file($f['tmp_name'],$dest))throw new RuntimeException('Unable to save the payment proof.');$path=$name;}$ins=db()->prepare('INSERT INTO payments(assessment_id,amount,payment_method,reference_number,proof_file_path,payment_date,status) VALUES(?,?,?,?,?,?,\'pending\')');$ins->execute([$assessment,$amount,$method,trim((string)($_POST['reference_number']??''))?:null,$path,$date]);log_event('PAYMENT_SUBMITTED','Student submitted a payment for review',(int)$u['user_id'],$u['username']);flash('Payment submitted. It will count toward your balance only after cashier verification.','success');}catch(Throwable $e){flash(public_error_message($e),'error');}go('dashboard');
}
function payment_proof_page(): never {
    $u=require_role('cashier','admin');$id=(int)($_GET['id']??0);$q=db()->prepare('SELECT proof_file_path FROM payments WHERE payment_id=?');$q->execute([$id]);$name=(string)$q->fetchColumn();if($name===''){http_response_code(404);exit('Proof not found.');}$name=basename($name);$path=dirname(__DIR__).'/storage/uploads/'.$name;if(!is_file($path)){http_response_code(404);exit('Proof file not found.');}$ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));$types=['jpg'=>'image/jpeg','png'=>'image/png','pdf'=>'application/pdf'];if(!isset($types[$ext])){http_response_code(415);exit('Unsupported proof file.');}header('Content-Type: '.$types[$ext]);header('Content-Disposition: inline; filename="payment-proof.'.$ext.'"');header('X-Content-Type-Options: nosniff');header('Cache-Control: private, no-store');readfile($path);exit;
}
function cashier_dashboard(array $u): void {
    if($_SERVER['REQUEST_METHOD']==='POST'&&($_POST['action']??'')==='create_assessment'){verify_csrf();try{$studentNumber=trim((string)$_POST['student_number']);$s=db()->prepare('SELECT student_id FROM student_profiles WHERE student_number=?');$s->execute([$studentNumber]);$student=$s->fetch();if(!$student)throw new RuntimeException('Student number not found.');$year=trim((string)$_POST['school_year']);$sem=(string)$_POST['semester'];$total=(float)$_POST['total_assessed'];if($total<=0)throw new RuntimeException('Assessment amount must be greater than zero.');$ins=db()->prepare('INSERT INTO tuition_assessments(student_id,school_year,semester,total_assessed,due_date,created_by) VALUES(?,?,?,?,?,?)');$ins->execute([$student['student_id'],$year,$sem,$total,$_POST['due_date']?:null,$u['user_id']]);log_event('ASSESSMENT_CREATED','Cashier created tuition assessment',(int)$u['user_id'],$u['username']);flash('Tuition assessment created.','success');}catch(Throwable $e){flash(public_error_message($e),'error');}go('dashboard');}
    if($_SERVER['REQUEST_METHOD']==='POST'&&($_POST['action']??'')==='review_payment'){verify_csrf();try{$id=(int)$_POST['payment_id'];$status=(string)$_POST['decision'];if(!in_array($status,['verified','rejected'],true))throw new RuntimeException('Invalid review decision.');$reason=trim((string)($_POST['rejection_reason']??''));if($status==='rejected'&&$reason==='')throw new RuntimeException('Provide a reason when rejecting a payment.');db()->prepare('UPDATE payments SET status=?,reviewed_by=?,reviewed_at=NOW(),rejection_reason=? WHERE payment_id=? AND status=\'pending\'')->execute([$status,$u['user_id'],$status==='rejected'?$reason:null,$id]);$s=db()->prepare('SELECT assessment_id FROM payments WHERE payment_id=?');$s->execute([$id]);$aid=(int)$s->fetchColumn();$sum=db()->prepare("SELECT total_assessed,(SELECT COALESCE(SUM(amount),0) FROM payments WHERE assessment_id=? AND status='verified') AS paid FROM tuition_assessments WHERE assessment_id=?");$sum->execute([$aid,$aid]);$row=$sum->fetch();$new=(float)$row['paid']>=(float)$row['total_assessed']?'paid':((float)$row['paid']>0?'partially_paid':'unpaid');db()->prepare('UPDATE tuition_assessments SET assessment_status=? WHERE assessment_id=?')->execute([$new,$aid]);log_event($status==='verified'?'PAYMENT_VERIFIED':'PAYMENT_REJECTED','Cashier reviewed payment #'.$id,(int)$u['user_id'],$u['username']);flash('Payment review saved.','success');}catch(Throwable $e){flash(public_error_message($e),'error');}go('dashboard');}
    show_flash();?><section class="panel"><h2>Create Tuition Assessment</h2><form method="post" class="panel-form"><input type="hidden" name="csrf_token" value="<?=e(csrf())?>"><input type="hidden" name="action" value="create_assessment"><?php field('Student Number *','student_number');field('School Year *','school_year','text','2026-2027');?><label>Semester *<select name="semester"><option>1st Semester</option><option>2nd Semester</option><option>Summer</option></select></label><?php field('Total Assessed (₱) *','total_assessed','number','','min="0.01" step="0.01" required');field('Due Date','due_date','date');?><button>Create Assessment</button></form></section>
    <section class="panel"><h2>Pending Payments</h2><?php $p=db()->query("SELECT p.*,ta.school_year,ta.semester,u.username,sp.student_number FROM payments p JOIN tuition_assessments ta ON ta.assessment_id=p.assessment_id JOIN student_profiles sp ON sp.student_id=ta.student_id JOIN users u ON u.user_id=sp.user_id WHERE p.status='pending' ORDER BY p.submitted_at")->fetchAll();if(!$p):?><p>No pending payments.</p><?php else:?><div class="table-wrap"><table><tr><th>Student</th><th>Assessment</th><th>Amount</th><th>Method</th><th>Reference</th><th>Proof</th><th>Review</th></tr><?php foreach($p as $x):?><tr><td><?=e($x['username'].' / '.$x['student_number'])?></td><td><?=e($x['school_year'].' '.$x['semester'])?></td><td>₱<?=number_format((float)$x['amount'],2)?></td><td><?=e($x['payment_method'])?></td><td><?=e($x['reference_number']??'—')?></td><td><?=$x['proof_file_path']?'<a href="index.php?page=payment_proof&amp;id='.(int)$x['payment_id'].'">View proof</a>':'—'?></td><td><form method="post" class="inline-form"><input type="hidden" name="csrf_token" value="<?=e(csrf())?>"><input type="hidden" name="action" value="review_payment"><input type="hidden" name="payment_id" value="<?=$x['payment_id']?>"><input name="rejection_reason" placeholder="Reason if rejected"><button name="decision" value="verified">Verify</button><button class="danger" name="decision" value="rejected">Reject</button></form></td></tr><?php endforeach;?></table></div><?php endif;?></section><?php
}
function admin_dashboard(array $u): void {
    log_event('ADMIN_VIEWED_DASHBOARD','Admin opened the security monitoring dashboard',(int)$u['user_id'],$u['username']);
    $pdo=db();$today=$pdo->query("SELECT event_type,COUNT(*) c FROM security_logs WHERE DATE(created_at)=CURDATE() GROUP BY event_type")->fetchAll();$counts=[];foreach($today as $x)$counts[$x['event_type']]=(int)$x['c'];$locked=(int)$pdo->query("SELECT COUNT(*) FROM users WHERE locked_until>NOW()")->fetchColumn();$blocked=(int)$pdo->query("SELECT COUNT(DISTINCT ip_address) FROM ip_blocks WHERE blocked_until>NOW()")->fetchColumn();
    ?>
<section class="panel">
    <h2>Account Management</h2>
    <p>Manage student and cashier accounts.</p>
    <a class="button-link" href="index.php?page=admin_accounts">
        Manage Student and Cashier Accounts
    </a>
</section>
<?php
    ?><section class="cards"><article class="card"><h3>Successful Logins Today</h3><p><?=($counts['LOGIN_SUCCESS']??0)?></p></article><article class="card"><h3>Failed Logins Today</h3><p><?=($counts['LOGIN_FAILED']??0)?></p></article><article class="card"><h3>Accounts Locked</h3><p><?=$locked?></p></article><article class="card"><h3>IPs Blocked</h3><p><?=$blocked?></p></article><article class="card"><h3>OTP Sent / Failed</h3><p><?=($counts['OTP_SENT']??0)?> / <?=($counts['OTP_FAILED']??0)?></p></article><article class="card"><h3>Alert Emails Sent</h3><p><?=($counts['ALERT_EMAIL_SENT']??0)?></p></article></section>
    <section class="panel"><h2>Failed Logins per Hour — Last 24 Hours</h2><div class="bar-chart"><?php $rows=$pdo->query("SELECT DATE_FORMAT(created_at,'%H:00') hr,COUNT(*) c FROM security_logs WHERE event_type='LOGIN_FAILED' AND created_at>=DATE_SUB(NOW(),INTERVAL 24 HOUR) GROUP BY DATE_FORMAT(created_at,'%H:00') ORDER BY MIN(created_at)")->fetchAll();$max=max(1,...array_map(fn($r)=>(int)$r['c'],$rows));foreach($rows as $r):?><div class="bar-row"><span><?=e($r['hr'])?></span><progress max="<?=$max?>" value="<?=e($r['c'])?>"></progress><strong><?=e($r['c'])?></strong></div><?php endforeach;?></div></section>
    <section class="panel"><h2>Top 5 IPs with Failed Attempts</h2><?php $ips=$pdo->query("SELECT ip_address,COUNT(*) failures FROM security_logs WHERE event_type IN ('LOGIN_FAILED','OTP_FAILED','SECURITY_ANSWER_FAILED') AND created_at>=DATE_SUB(NOW(),INTERVAL 24 HOUR) GROUP BY ip_address ORDER BY failures DESC LIMIT 5")->fetchAll();?><div class="table-wrap"><table><tr><th>IP Address</th><th>Failures</th><th>Blocked</th></tr><?php foreach($ips as $ip):$s=$pdo->prepare('SELECT COUNT(*) FROM ip_blocks WHERE ip_address=? AND blocked_until>NOW()');$s->execute([$ip['ip_address']]);?><tr><td><?=e($ip['ip_address'])?></td><td><?=$ip['failures']?></td><td><?=$s->fetchColumn()?'Yes':'No'?></td></tr><?php endforeach;?></table></div></section>
    <section class="panel"><h2>Monitoring Flags</h2><?php $flags=$pdo->query('SELECT * FROM security_flags ORDER BY detected_at DESC LIMIT 20')->fetchAll();if(!$flags):?><p>No flags recorded by the monitoring script.</p><?php else:?><ul><?php foreach($flags as $f):?><li><strong><?=e($f['severity'])?></strong> — <?=e($f['description'])?> (<?=e($f['detected_at'])?>)</li><?php endforeach;?></ul><?php endif;?></section>
    <section class="panel"><h2>Latest Security Events</h2><form method="get" class="filters"><input type="hidden" name="page" value="dashboard"><input name="event" placeholder="Event type" value="<?=e($_GET['event']??'')?>"><input name="username" placeholder="Username" value="<?=e($_GET['username']??'')?>"><input name="ip" placeholder="IP address" value="<?=e($_GET['ip']??'')?>"><input type="date" name="from" value="<?=e($_GET['from']??'')?>"><input type="date" name="to" value="<?=e($_GET['to']??'')?>"><button>Filter</button></form><?php $sql='SELECT * FROM security_logs WHERE 1=1';$params=[];foreach(['event'=>'event_type','username'=>'username_typed','ip'=>'ip_address'] as $key=>$col){if(!empty($_GET[$key])){$sql.=" AND $col LIKE ?";$params[]='%'.$_GET[$key].'%';}}if(!empty($_GET['from'])){$sql.=' AND DATE(created_at)>=?';$params[]=$_GET['from'];}if(!empty($_GET['to'])){$sql.=' AND DATE(created_at)<=?';$params[]=$_GET['to'];}$sql.=' ORDER BY created_at DESC LIMIT 50';$st=$pdo->prepare($sql);$st->execute($params);$events=$st->fetchAll();?><div class="table-wrap" id="adminEventsContainer"><table><thead><tr><th>Time</th><th>Event</th><th>Username</th><th>IP</th><th>Description</th></tr></thead><tbody><?php foreach($events as $x):?><tr><td><?=e($x['created_at'])?></td><td><?=e($x['event_type'])?></td><td><?=e($x['username_typed']??'—')?></td><td><?=e($x['ip_address']??'—')?></td><td><?=e($x['event_description'])?></td></tr><?php endforeach;?></tbody></table></div><p class="small">Read-only monitoring view. Security events cannot be edited or deleted here. Auto-refresh: 60 seconds.</p></section><?php
}


function admin_accounts_page(): never {
    $u = require_role('admin');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();

        try {
            $id = (int)($_POST['user_id'] ?? 0);
            $action = (string)($_POST['account_action'] ?? '');

            $q = db()->prepare(
                "SELECT user_id, username, role, account_status,
                        email_verified_at
                 FROM users WHERE user_id = ? LIMIT 1"
            );
            $q->execute([$id]);
            $target = $q->fetch();

            if (!$target || !in_array($target['role'], ['student', 'cashier'], true)) {
                throw new RuntimeException('Student or cashier account not found.');
            }

            $changes = [
                'activate' => ['active', 'ADMIN_ACCOUNT_ACTIVATED', 'disabled'],
                'disable'  => ['disabled', 'ADMIN_ACCOUNT_DISABLED', 'active'],
                'lock'     => ['locked', 'ADMIN_ACCOUNT_LOCKED', 'active'],
                'unlock'   => ['active', 'ADMIN_ACCOUNT_UNLOCKED', 'locked'],
            ];

            if (!isset($changes[$action])) {
                throw new RuntimeException('Invalid account action.');
            }

            [$newStatus, $event, $requiredStatus] = $changes[$action];

            if ($target['account_status'] !== $requiredStatus) {
                throw new RuntimeException('This action is not allowed for the current account status.');
            }

            if (
                in_array($action, ['activate', 'unlock'], true) &&
                empty($target['email_verified_at'])
            ) {
                throw new RuntimeException('Cannot activate an account until its email is verified.');
            }

            db()->prepare(
                'UPDATE users
                 SET account_status = ?, locked_until = NULL,
                     failed_login_count = 0
                 WHERE user_id = ? AND role IN ("student", "cashier")'
            )->execute([$newStatus, $id]);

            log_event(
                $event,
                'Admin changed account status for ' . $target['username'] .
                ' to ' . $newStatus,
                (int)$u['user_id'],
                $u['username']
            );

            flash('Account status updated successfully.', 'success');

        } catch (Throwable $e) {
            flash(public_error_message($e), 'error');
        }

        go('admin_accounts');
    }

    page_header('Account Management', $u);
    show_flash();

    $search = trim((string)($_GET['search'] ?? ''));

    $sql = "SELECT u.user_id, u.username, u.email, u.role,
                   u.account_status, u.created_at,
                   u.email_verified_at,
                   sp.student_number
            FROM users u
            LEFT JOIN student_profiles sp ON sp.user_id = u.user_id
            WHERE u.role IN ('student', 'cashier')";

    $params = [];

    if ($search !== '') {
        $sql .= " AND (
            u.username LIKE ? OR u.email LIKE ?
            OR sp.student_number LIKE ?
        )";
        $term = '%' . $search . '%';
        $params = [$term, $term, $term];
    }

    $sql .= ' ORDER BY u.created_at DESC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $accounts = $stmt->fetchAll();

    ?>
    <section class="panel">
        <h2>Student and Cashier Accounts</h2>

        <form method="get" class="filters">
            <input type="hidden" name="page" value="admin_accounts">
            <input name="search"
                   placeholder="Search username, email or student number"
                   value="<?=e($search)?>">
            <button type="submit">Search</button>
            <a href="index.php?page=admin_accounts">Clear</a>
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Student Number</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$accounts): ?>
                    <tr><td colspan="6">No accounts found.</td></tr>
                <?php else: ?>
                    <?php foreach ($accounts as $a): ?>
                        <tr>
                            <td><?=e($a['username'])?></td>
                            <td><?=e($a['student_number'] ?? '—')?></td>
                            <td><?=e($a['email'])?></td>
                            <td><?=e(ucfirst($a['role']))?></td>
                            <td>
                                <?=e($a['account_status'])?>
                                <?php if (empty($a['email_verified_at'])): ?>
                                    <br><small>Email not verified</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($a['account_status'] === 'active'): ?>
                                    <form method="post" class="inline-form">
                                        <input type="hidden" name="csrf_token"
                                               value="<?=e(csrf())?>">
                                        <input type="hidden" name="user_id"
                                               value="<?=$a['user_id']?>">
                                        <button class="danger"
                                                name="account_action"
                                                value="disable"
                                                type="submit">
                                            Disable
                                        </button>
                                        <button name="account_action"
                                                value="lock"
                                                type="submit">
                                            Lock
                                        </button>
                                    </form>

                                <?php elseif ($a['account_status'] === 'disabled'): ?>
                                    <?php if (!empty($a['email_verified_at'])): ?>
                                        <form method="post" class="inline-form">
                                            <input type="hidden" name="csrf_token"
                                                   value="<?=e(csrf())?>">
                                            <input type="hidden" name="user_id"
                                                   value="<?=$a['user_id']?>">
                                            <button name="account_action"
                                                    value="activate"
                                                    type="submit">
                                                Activate
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <small>Cannot activate: verify email first.</small>
                                    <?php endif; ?>

                                <?php elseif ($a['account_status'] === 'locked'): ?>
                                    <?php if (!empty($a['email_verified_at'])): ?>
                                        <form method="post" class="inline-form">
                                            <input type="hidden" name="csrf_token"
                                                   value="<?=e(csrf())?>">
                                            <input type="hidden" name="user_id"
                                                   value="<?=$a['user_id']?>">
                                            <button name="account_action"
                                                    value="unlock"
                                                    type="submit">
                                                Unlock
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <small>Cannot unlock: verify email first.</small>
                                    <?php endif; ?>

                                <?php else: ?>
                                    <small>Pending email verification</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php

    page_footer();
    exit;
}


function admin_events_json_page(): never {
    if(empty($_SESSION['user_id'])){http_response_code(401);header('Content-Type: application/json; charset=UTF-8');echo json_encode(['events'=>[],'error'=>'authentication_required']);exit;} $last=(int)($_SESSION['last_activity']??time());if(time()-$last>=900){log_event('SESSION_IDLE_TIMEOUT','Admin session expired due to 15-minute inactivity',(int)$_SESSION['user_id']);$_SESSION=[];session_destroy();http_response_code(401);header('Content-Type: application/json; charset=UTF-8');echo json_encode(['events'=>[],'error'=>'session_expired']);exit;} $admin=current_user(); if(!$admin || $admin['account_status']!=='active'){http_response_code(401);header('Content-Type: application/json; charset=UTF-8');echo json_encode(['events'=>[],'error'=>'authentication_required']);exit;} if($admin['role']!=='admin'){log_event('ADMIN_ACCESS_DENIED','Non-admin attempted to access admin security events',(int)$admin['user_id'],$admin['username']);http_response_code(403);exit('Access denied.');} header('Content-Type: application/json; charset=UTF-8');
    $sql='SELECT created_at,event_type,username_typed,ip_address,event_description FROM security_logs WHERE 1=1';$params=[];
    foreach(['event'=>'event_type','username'=>'username_typed','ip'=>'ip_address'] as $key=>$col){if(!empty($_GET[$key])){$sql.=' AND '.$col.' LIKE ?';$params[]='%'.$_GET[$key].'%';}}
    if(!empty($_GET['from'])){$sql.=' AND DATE(created_at)>=?';$params[]=$_GET['from'];}if(!empty($_GET['to'])){$sql.=' AND DATE(created_at)<=?';$params[]=$_GET['to'];}
    $sql.=' ORDER BY created_at DESC LIMIT 50';$q=db()->prepare($sql);$q->execute($params);echo json_encode(['events'=>$q->fetchAll()],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit;
}

function admin_security_dashboard_page(): never {
    $u = require_user();

    if ($u['role'] !== 'admin') {
        log_event(
            'ADMIN_ACCESS_DENIED',
            'Non-admin attempted to access the admin dashboard',
            (int)$u['user_id'],
            $u['username']
        );
        http_response_code(403);
        exit('Access denied.');
    }

    page_header('Security Monitoring Dashboard', $u);
    admin_dashboard($u);
    page_footer();
    exit;
}

function keepalive_page(): never { require_user(); header('Content-Type: application/json; charset=UTF-8'); echo json_encode(['ok'=>true]); exit; }
function home_page(): void { $u=current_user();page_header('Home',$u);show_flash();?><section class="hero"><h2>Secure tuition and payment monitoring for students</h2><p>Students can review tuition assessments and submit payment records. Cashiers verify submitted payments. Administrators monitor security events.</p><?php if(!$u):?><a class="button-link" href="index.php?page=register">Create Student Account</a> <a class="button-link secondary-link" href="index.php?page=login">Log in</a><?php else:?><a class="button-link" href="index.php?page=dashboard">Open Dashboard</a><?php endif;?></section><section class="panel"><h2>Privacy and Security</h2><p>Passwords and security answers are stored as hashes. Guardian contact numbers are encrypted at rest. Login and recovery require email OTP verification.</p><a href="index.php?page=privacy">Read the Privacy Notice</a></section><?php page_footer(); }
function privacy_page(): void {page_header('Privacy Notice',current_user());?><section class="panel"><h2>Data Privacy Notice</h2><p>This system collects student identity details, contact information, address, school information, account credentials, guardian contact information, tuition assessments, and payment submissions.</p><p>Data is used to register and verify student accounts, prepare tuition assessments, monitor payments, provide account recovery, prevent unauthorized access, and maintain audit records.</p><p>Passwords and security answers are hashed. Guardian contact information is encrypted using AES-256-GCM. Access is limited according to student, cashier, and administrator roles. Security logs are append-only in the application.</p><p>Records are retained for school administrative, financial, security, and legal requirements, then disposed of according to the school retention policy. For access, correction, or privacy concerns, contact the school system administrator.</p><p>Consent is recorded with a timestamp. This notice is provided in accordance with the Data Privacy Act of 2012 (RA 10173).</p></section><?php page_footer();}
function logout_page(): never { if($_SERVER['REQUEST_METHOD']!=='POST')go('dashboard');verify_csrf();if(!empty($_SESSION['user_id']))log_event('LOGOUT','User logged out',(int)$_SESSION['user_id']);$_SESSION=[];if(ini_get('session.use_cookies')){$p=session_get_cookie_params();setcookie(session_name(),'',time()-42000,$p['path'],$p['domain'],$p['secure'],$p['httponly']);}session_destroy();header('Cache-Control: no-store, no-cache, must-revalidate');go('login'); }
function route_app(): void {
    $page=(string)($_GET['page']??'home');
    if($page==='captcha')create_captcha_image();
    if($page==='check_username'){header('Content-Type: application/json; charset=UTF-8');$name=trim((string)($_GET['username']??''));$valid=(bool)preg_match('/^[A-Za-z][A-Za-z0-9]{5,19}$/',$name);$exists=false;if($valid){$q=db()->prepare('SELECT 1 FROM users WHERE username=? LIMIT 1');$q->execute([$name]);$exists=(bool)$q->fetchColumn();}echo json_encode(['valid'=>$valid,'available'=>$valid&&!$exists,'message'=>!$valid?'Use 6–20 letters or numbers and start with a letter.':($exists?'Username is already taken.':'Username is available.')]);exit;}
    if($page==='admin_events_json')admin_events_json_page();
    if($page==='admin_dashboard')admin_security_dashboard_page();
    if($page==='admin_accounts')admin_accounts_page();
    if($page==='keepalive')keepalive_page();
    if($page==='logout')logout_page();
    if($page==='register'){register_page();return;}if($page==='login'){login_page();return;}if($page==='otp'){otp_page();return;}if($page==='resend_otp'){resend_otp_page();return;}if($page==='forgot'){forgot_page();return;}if($page==='change_password'){change_password_page();return;}if($page==='dashboard'){dashboard_page();return;}if($page==='payment_submit'){payment_submit_page();}
    if($page==='payment_proof'){payment_proof_page();}
    if($page==='privacy'){privacy_page();return;}home_page();
}

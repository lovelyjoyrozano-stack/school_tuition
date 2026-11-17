function refreshCaptcha(){const img=document.querySelector('.captcha img');if(img)img.src='index.php?page=captcha&t='+Date.now();}
function togglePassword(id){const el=document.getElementById(id);if(el)el.type=el.type==='password'?'text':'password';}
document.addEventListener('DOMContentLoaded',()=>{
 const birth=document.querySelector('input[name="birthdate"]');const age=document.getElementById('agePreview');
 function calcAge(){if(!birth||!age||!birth.value)return;const d=new Date(birth.value+'T00:00:00');const now=new Date();let n=now.getFullYear()-d.getFullYear();if(now.getMonth()<d.getMonth()||(now.getMonth()===d.getMonth()&&now.getDate()<d.getDate()))n--;age.value=n+' years old';}
 birth?.addEventListener('change',calcAge);calcAge();
 [['password','strengthText'],['changePassword','changeStrengthText']].forEach(([inputId,meterId])=>{const pw=document.getElementById(inputId);const meter=document.getElementById(meterId);pw?.addEventListener('input',()=>{const v=pw.value;let n=0;if(v.length>=8)n++;if(/[a-z]/.test(v))n++;if(/[A-Z]/.test(v))n++;if(/[0-9]/.test(v))n++;if(/[^A-Za-z0-9]/.test(v))n++;if(meter)meter.textContent='Password strength: '+(n<=2?'Weak':n<=4?'Medium':'Strong');});});
});
// Client-side idle warning; the server enforces the 15-minute idle timeout.
if(document.body?.dataset.authenticated==='1'){
 let lastActivity=Date.now(),lastPing=Date.now(),warningTimer,logoutTimer;
 const ping=()=>fetch('index.php?page=keepalive',{cache:'no-store',headers:{'Accept':'application/json'}}).catch(()=>{});
 const schedule=()=>{clearTimeout(warningTimer);clearTimeout(logoutTimer);warningTimer=setTimeout(()=>{const stay=confirm('Your session will expire in 1 minute because of inactivity. Press OK to stay logged in.');if(stay){lastActivity=Date.now();lastPing=Date.now();ping();schedule();}else{logoutTimer=setTimeout(()=>{const form=document.querySelector('form.nav-logout');if(form)form.requestSubmit();else window.location.assign('index.php?page=login&expired=1');},60000);}},840000)};
 const activity=()=>{lastActivity=Date.now();schedule();if(Date.now()-lastPing>=60000){lastPing=Date.now();ping();}};
 ['click','keydown','mousemove','scroll','touchstart'].forEach(ev=>document.addEventListener(ev,activity,{passive:true}));
 schedule();
}
// Live username availability check. Server-side validation remains authoritative.
const usernameInput=document.getElementById('usernameField');
usernameInput?.addEventListener('input',()=>{
 const out=document.getElementById('usernameStatus');const value=usernameInput.value.trim();if(!out)return;
 if(value.length===0){out.textContent='';return;}
 fetch('index.php?page=check_username&username='+encodeURIComponent(value),{headers:{'Accept':'application/json'},cache:'no-store'})
  .then(r=>r.json()).then(d=>{out.textContent=d.message;out.className=d.available?'available':'unavailable';})
  .catch(()=>{out.textContent='Username availability could not be checked right now.';});
});
// Refresh only the admin event table every 60 seconds through a protected JSON endpoint.
if(document.body?.dataset.admin==='1'){
 const container=document.getElementById('adminEventsContainer');
 const refreshEvents=()=>{if(!container)return;const params=new URLSearchParams(window.location.search);params.set('page','admin_events_json');fetch('index.php?'+params.toString(),{headers:{'Accept':'application/json'},cache:'no-store'}).then(r=>{if(!r.ok)throw new Error('Not authorized');return r.json();}).then(data=>{const table=document.createElement('table');table.innerHTML='<thead><tr><th>Time</th><th>Event</th><th>Username</th><th>IP</th><th>Description</th></tr></thead>';const tbody=document.createElement('tbody');for(const item of data.events||[]){const tr=document.createElement('tr');for(const key of ['created_at','event_type','username_typed','ip_address','event_description']){const td=document.createElement('td');td.textContent=item[key]||'—';tr.appendChild(td);}tbody.appendChild(tr);}table.appendChild(tbody);container.replaceChildren(table);}).catch(()=>{});};refreshEvents();setInterval(refreshEvents,60000);
}
// Lockout countdown is displayed from a server-provided timestamp; server checks the lock independently.
const lockUntil=Number(document.body?.dataset.lockUntil||0);
if(lockUntil>0){const box=document.getElementById('lockCountdown');const submit=document.getElementById('loginSubmit');const registerLinks=document.querySelectorAll('.register-link');const tick=()=>{const left=Math.max(0,lockUntil-Math.floor(Date.now()/1000));if(box){box.hidden=false;box.textContent=left>0?'Account temporarily locked. Try again in '+left+' seconds.':'Lockout ended. You may try again.';}if(submit)submit.disabled=left>0;registerLinks.forEach(a=>{a.setAttribute('aria-disabled',left>0?'true':'false');a.classList.toggle('disabled-link',left>0);});if(left>0)setTimeout(tick,1000);};tick();}
// Name extension's Other field is shown only when selected.
const extensionSelect=document.querySelector('select[name="name_extension"]');const extensionOther=document.getElementById('extensionOtherWrap');const syncExtension=()=>{if(extensionOther){extensionOther.hidden=extensionSelect?.value!=='Other';}};extensionSelect?.addEventListener('change',syncExtension);syncExtension();

// Buttons use external JavaScript rather than inline event handlers.
document.querySelectorAll('.refresh-captcha').forEach(b=>b.addEventListener('click',refreshCaptcha));
document.querySelectorAll('[data-toggle-password]').forEach(b=>b.addEventListener('change',()=>togglePassword(b.dataset.togglePassword)));
const resendInfo=document.getElementById('resendOtpCountdown');
if(resendInfo){let seconds=Number(resendInfo.dataset.remaining||0);const button=document.getElementById('resendOtpButton');const maxed=resendInfo.dataset.limit==='1';if(maxed&&button)button.disabled=true;else if(seconds>0){if(button)button.disabled=true;const tick=()=>{resendInfo.textContent='You can resend in '+seconds+' seconds.';if(seconds>0){seconds--;setTimeout(tick,1000);}else{resendInfo.textContent='You may request another code.';if(button)button.disabled=false;}};tick();}}

// Registration: prevent selecting the same authentication question more than once.
const securityQuestionSelects=[...document.querySelectorAll('.security-question-select')];
function syncSecurityQuestionOptions(){
 const chosen=securityQuestionSelects.map(s=>s.value).filter(Boolean);
 securityQuestionSelects.forEach(select=>{
  [...select.options].forEach(option=>{
   if(!option.value)return;
   option.disabled=chosen.includes(option.value)&&select.value!==option.value;
  });
 });
}
securityQuestionSelects.forEach(select=>select.addEventListener('change',syncSecurityQuestionOptions));
syncSecurityQuestionOptions();

{{--
  重置密码页面 — 第二步：输入验证码 + 新密码

  【作用】用户从邮件收到 6 位验证码后，在此页面完成密码重置。
  【提交目标】route('password.reset') → ForgotPasswordController@reset
  【功能】
    - 6 个独立数字输入框（验证码）
    - 新密码输入 + 实时强度指示器（长度/大小写/数字）
    - 确认密码输入 + 不匹配提示
    - 提交按钮在所有条件满足后才启用
    - 成功/失败弹窗（绿色成功 + 红色失败模态框）
  【IDOR 防护】session 中的 reset_token 必须与 DB 中的 verification_token 匹配
--}}
{{--
  Reset Password Page — Step 2: Enter verification code + new password

  【Purpose】After the user receives the 6-digit code via email, they complete
  the password reset on this page.
  【Target】route('password.reset') → ForgotPasswordController@reset
  【Features】
    - 6 individual digit input boxes (verification code)
    - New password input + real-time strength indicator (length / case / digit)
    - Confirm password input + mismatch hint
    - Submit button enabled only after all conditions are met
    - Success / failure popups (green success + red failure modal)
  【IDOR Protection】The reset_token in session must match the
  verification_token in the database.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <title>Reset Password | FoodShare</title>
  <link href="https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Raleway:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <style>
    :root {
      --primary: #059669; --primary-dark: #047857; --accent: #D97706;
      --text: #1C1917; --muted: #78716C; --danger: #DC2626;
      --radius: 16px; --radius-xl: 28px; --radius-sm: 10px;
      --shadow-lg: 0 20px 56px rgba(28,25,23,0.10);
      --ease-spring: cubic-bezier(0.34,1.56,0.64,1);
      --font-heading: 'Lora',ui-serif,Georgia,serif;
      --font-body: 'Raleway',ui-sans-serif,system-ui,sans-serif;
    }
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    body{
      min-height:100dvh;font-family:var(--font-body);
      background:radial-gradient(ellipse 80% 60% at 12% 8%,rgba(217,119,6,0.06),transparent 35%),radial-gradient(ellipse 70% 50% at 88% 92%,rgba(5,150,105,0.08),transparent 35%),linear-gradient(175deg,#FEFAF5 0%,#F8F5F0 40%,#F0F5F1 100%);
      display:grid;place-items:center;padding:32px 20px;-webkit-font-smoothing:antialiased;
    }
    .card{
      width:min(440px,100%);background:#fff;border-radius:var(--radius-xl);
      box-shadow:var(--shadow-lg);overflow:hidden;
    }
    .card-header{
      background:linear-gradient(135deg,var(--primary-dark),var(--primary));
      padding:36px 32px 28px;text-align:center;color:#fff;
    }
    .card-header h1{font-family:var(--font-heading);font-size:1.5rem;margin-bottom:4px}
    .card-header .email-badge{
      display:inline-block;margin-top:8px;padding:4px 14px;
      background:rgba(255,255,255,0.15);border-radius:20px;font-size:0.82rem;
    }

    .card-body{padding:32px}

    .field{margin-bottom:20px}
    .field label{display:block;font-weight:600;font-size:0.87rem;color:var(--text);margin-bottom:6px}
    .field input{
      width:100%;height:48px;border:1.5px solid #E7E0D8;border-radius:var(--radius-sm);
      padding:0 16px;font-family:var(--font-body);font-size:0.95rem;outline:none;
      transition:border-color .15s,box-shadow .15s;
    }
    .field input:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(5,150,105,0.12)}

    /* 6-digit code inputs */
    .code-inputs{display:flex;gap:10px;justify-content:center;margin-bottom:24px}
    .code-inputs input{
      width:48px;height:56px;border:2px solid #E7E0D8;border-radius:var(--radius-sm);
      text-align:center;font-size:1.4rem;font-weight:700;
      font-family:'Courier New',monospace;color:var(--text);outline:none;
      transition:border-color .15s,box-shadow .15s;
    }
    .code-inputs input:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(5,150,105,0.12)}

    .alert{padding:12px 16px;border-radius:var(--radius-sm);margin-bottom:20px;font-size:0.87rem;display:flex;align-items:center;gap:10px}
    .alert-error{background:#FEF2F2;color:#991B1B;border:1px solid #FECACA}
    .alert-success{background:#ECFDF5;color:#064E3B;border:1px solid #A7F3D0}

    .btn{
      width:100%;padding:14px;border:0;border-radius:14px;font-family:var(--font-body);
      font-weight:700;font-size:1rem;cursor:pointer;color:#fff;
      background:linear-gradient(135deg,var(--primary),var(--primary-dark));
      box-shadow:0 12px 24px rgba(5,150,105,0.22);transition:all .2s;
      display:flex;align-items:center;justify-content:center;gap:8px;
    }
    .btn:hover{transform:translateY(-1px);box-shadow:0 16px 30px rgba(5,150,105,0.30)}
    .btn:disabled{opacity:0.45;cursor:not-allowed;transform:none;box-shadow:none}

    .text-link{text-align:center;margin-top:20px;font-size:0.88rem;color:var(--muted)}
    .text-link a{color:var(--primary);font-weight:600;text-decoration:none}
    .text-link a:hover{text-decoration:underline}

    .timer{text-align:center;font-family:'Courier New',monospace;color:var(--accent);font-size:0.9rem;font-weight:600;margin-top:16px}
    .timer.expired{color:var(--danger)}

    /* 实时验证指示器 */
    /* Real-time validation indicators */
    .req-list{list-style:none;padding:0;margin-top:8px;font-size:0.78rem}
    .req-list li{display:flex;align-items:center;gap:6px;padding:2px 0;color:var(--muted);transition:color .2s}
    .req-list li .dot{width:6px;height:6px;border-radius:50%;flex-shrink:0;background:#D1D5DB;transition:background .2s}
    .req-list li.met{color:var(--primary)}
    .req-list li.met .dot{background:var(--primary)}
    .req-list li.unmet{color:var(--danger)}
    .req-list li.unmet .dot{background:var(--danger)}
    .field input.input-valid{border-color:var(--primary)}
    .field input.input-invalid{border-color:var(--danger)}
    .code-inputs input.input-valid{border-color:var(--primary)}

    /* 成功弹窗 */
    /* Success modal */
    .modal-overlay{position:fixed;inset:0;z-index:100;background:rgba(28,25,23,0.45);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;animation:fadeIn .25s ease}
    .modal-card{width:min(400px,90vw);background:#fff;border-radius:24px;overflow:hidden;box-shadow:0 24px 64px rgba(28,25,23,0.18);animation:popIn .4s var(--ease-spring)}
    .modal-header{background:linear-gradient(135deg,var(--primary-dark),var(--primary));padding:36px 28px 28px;text-align:center;color:#fff}
    .modal-header .check-circle{width:64px;height:64px;border-radius:50%;background:rgba(255,255,255,0.18);display:inline-flex;align-items:center;justify-content:center;margin-bottom:14px}
    .modal-header h2{font-family:var(--font-heading);font-size:1.4rem}
    .modal-body{padding:28px 32px 32px;text-align:center}
    .modal-body p{color:var(--text);font-size:0.95rem;line-height:1.6;margin-bottom:24px}
    @keyframes fadeIn{from{opacity:0}to{opacity:1}}
    @keyframes popIn{from{opacity:0;transform:scale(.85) translateY(20px)}to{opacity:1;transform:scale(1) translateY(0)}}
  </style>
</head>
<body>

  @if (session('password_reset_done'))
  {{-- 成功弹窗 --}}
  {{-- Success modal --}}
  <div class="modal-overlay">
    <div class="modal-card">
      <div class="modal-header">
        <div class="check-circle">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <h2>Password Reset Successful!</h2>
      </div>
      <div class="modal-body">
        <p>Your password has been changed. Please sign in with your new password.</p>
        <a href="{{ route('login') }}" class="btn">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
          Go to Login
        </a>
      </div>
    </div>
  </div>
  @endif

  @if (session('password_reset_failed'))
  {{-- 失败弹窗 --}}
  {{-- Failure modal --}}
  <div class="modal-overlay">
    <div class="modal-card">
      <div class="modal-header" style="background:linear-gradient(135deg,#DC2626,#B91C1C);">
        <div class="check-circle" style="background:rgba(255,255,255,0.18);">
          <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        </div>
        <h2>Reset Failed</h2>
      </div>
      <div class="modal-body">
        <p>{{ session('password_reset_failed') }}</p>
        <button onclick="this.closest('.modal-overlay').remove()" class="btn" style="background:linear-gradient(135deg,#DC2626,#B91C1C);box-shadow:0 12px 24px rgba(220,38,38,0.22);">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="M6 6l12 12"/></svg>
          Try Again
        </button>
      </div>
    </div>
  </div>
  @endif

  <div class="card">
    <div class="card-header">
      <h1>Reset Password</h1>
      <p>Enter the code sent to</p>
      <span class="email-badge">{{ $email }}</span>
    </div>
    <div class="card-body">
      @if (session('success'))
        <div class="alert alert-success">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          {{ session('success') }}
        </div>
      @endif
      @if ($errors->any())
        @foreach ($errors->all() as $error)
          <div class="alert alert-error">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            {{ $error }}
          </div>
        @endforeach
      @endif

      <form method="POST" action="{{ route('password.reset') }}" id="resetForm">
        @csrf
        <input type="hidden" name="email" value="{{ $email }}" />

        <p style="text-align:center;font-size:0.9rem;color:var(--muted);margin-bottom:14px">
          Enter the 6-digit reset code
        </p>

        <div class="code-inputs" id="codeInputs">
          <input type="text" name="code[]" maxlength="1" inputmode="numeric" pattern="[0-9]" required />
          <input type="text" name="code[]" maxlength="1" inputmode="numeric" pattern="[0-9]" required />
          <input type="text" name="code[]" maxlength="1" inputmode="numeric" pattern="[0-9]" required />
          <input type="text" name="code[]" maxlength="1" inputmode="numeric" pattern="[0-9]" required />
          <input type="text" name="code[]" maxlength="1" inputmode="numeric" pattern="[0-9]" required />
          <input type="text" name="code[]" maxlength="1" inputmode="numeric" pattern="[0-9]" required />
        </div>

        <div class="field">
          <label for="password">New Password</label>
          <input type="password" id="password" name="password"
                 placeholder="At least 8 characters" autocomplete="new-password" required />
          <ul class="req-list" id="pwdReqs">
            <li data-req="length"><span class="dot"></span> At least 8 characters</li>
            <li data-req="lower"><span class="dot"></span> One lowercase letter</li>
            <li data-req="upper"><span class="dot"></span> One uppercase letter</li>
            <li data-req="digit"><span class="dot"></span> One number</li>
          </ul>
        </div>

        <div class="field">
          <label for="password_confirmation">Confirm New Password</label>
          <input type="password" id="password_confirmation" name="password_confirmation"
                 placeholder="Re-enter new password" autocomplete="new-password" required />
          <p id="confirmMatchHint" style="display:none;color:var(--danger);font-size:0.78rem;margin-top:4px;">Passwords do not match</p>
        </div>

        <button type="submit" class="btn" id="submitBtn" disabled>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
          Reset Password
        </button>
      </form>

      <p class="timer" id="countdown">
        Code expires in <span id="timerDisplay">15:00</span>
      </p>

      <p class="text-link">
        <a href="{{ route('password.forgot') }}">&larr; Try a different email</a>
      </p>
    </div>
  </div>

  <script>
    (function () {
      /* ---- Digit inputs ---- */
      var inputs = document.querySelectorAll('#codeInputs input');
      inputs.forEach(function(inp, idx){
        inp.addEventListener('input',function(){
          var v=this.value.replace(/[^0-9]/g,'');this.value=v.slice(0,1);
          if(v&&idx<inputs.length-1) inputs[idx+1].focus();
        });
        inp.addEventListener('keydown',function(e){
          if(e.key==='Backspace'&&!this.value&&idx>0) inputs[idx-1].focus();
        });
        inp.addEventListener('paste',function(e){
          e.preventDefault();
          var d=(e.clipboardData||window.clipboardData).getData('text').replace(/[^0-9]/g,'').slice(0,6);
          d.split('').forEach(function(ch,i){if(inputs[i])inputs[i].value=ch;});
          inputs[Math.min(d.length,inputs.length-1)].focus();
        });
      });

      /* ---- Countdown ---- */
      var sec=15*60, td=document.getElementById('timerDisplay'), cnt=document.getElementById('countdown');
      function tick(){if(sec<=0){cnt.classList.add('expired');td.textContent='Expired';return}var m=Math.floor(sec/60),s=sec%60;td.textContent=String(m).padStart(2,'0')+':'+String(s).padStart(2,'0');sec--}
      tick();setInterval(tick,1000);

      /* ---- Real-time validation ---- */
      var pwdInp    = document.getElementById("password");
      var confInp   = document.getElementById("password_confirmation");
      var submitBtn = document.getElementById("submitBtn");
      var pwdReqs   = document.getElementById("pwdReqs");

      function allDigitsFilled() {
        var ok = true;
        inputs.forEach(function(i){ if (!i.value) ok = false; });
        return ok;
      }

      function checkReset() {
        var codeOk = allDigitsFilled();
        var pwLen  = pwdInp.value.length >= 8;
        var pwLow  = /[a-z]/.test(pwdInp.value);
        var pwUp   = /[A-Z]/.test(pwdInp.value);
        var pwDig  = /[0-9]/.test(pwdInp.value);
        var pwOk   = pwLen && pwLow && pwUp && pwDig;
        var cfOk   = confInp.value.length > 0 && pwdInp.value === confInp.value;
        var allOk  = codeOk && pwOk && cfOk;

        // 密码条件指示器
        // Password requirement indicators
        if (pwdReqs && pwdInp.value.length > 0) {
          pwdReqs.querySelector("[data-req=length]").className = pwLen ? "met" : "unmet";
          pwdReqs.querySelector("[data-req=lower]").className  = pwLow ? "met" : "unmet";
          pwdReqs.querySelector("[data-req=upper]").className  = pwUp  ? "met" : "unmet";
          pwdReqs.querySelector("[data-req=digit]").className  = pwDig ? "met" : "unmet";
        } else if (pwdReqs) {
          pwdReqs.querySelectorAll("li").forEach(function(li){ li.className = ""; });
        }

        // 输入框边框反馈
        // Input border feedback
        pwdInp.classList.toggle("input-valid", pwOk);
        pwdInp.classList.toggle("input-invalid", pwdInp.value && !pwOk);
        confInp.classList.toggle("input-valid", cfOk);
        confInp.classList.toggle("input-invalid", confInp.value && !cfOk);
        var matchHint = document.getElementById("confirmMatchHint");
        if (matchHint) {
          matchHint.style.display = (confInp.value && pwdInp.value !== confInp.value) ? "block" : "none";
        }

        // 验证码输入框边框
        // Verification code input borders
        if (allDigitsFilled()) {
          inputs.forEach(function(i){ i.classList.add("input-valid"); });
        }

        submitBtn.disabled = !allOk;
      }

      inputs.forEach(function(inp){ inp.addEventListener("input", checkReset); });
      pwdInp.addEventListener("input", checkReset);
      confInp.addEventListener("input", checkReset);
      checkReset();

      /* ---- Form submit: combine code digits ---- */
      document.getElementById('resetForm').addEventListener('submit',function(){
        var code='';inputs.forEach(function(i){code+=i.value;i.name=''});
        var h=document.createElement('input');h.type='hidden';h.name='code';h.value=code;
        this.appendChild(h);
        submitBtn.disabled=true;submitBtn.textContent='Resetting...';
      });
    })();
  </script>
</body>
</html>

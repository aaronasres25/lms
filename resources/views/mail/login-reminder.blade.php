<div style="font-family:Arial,sans-serif;background:#fff5f5;padding:20px">
<table width="600" align="center" style="background:#fff;border:2px solid #ff4d4d;border-radius:10px;overflow:hidden">
<tr><td style="background:#ff4d4d;padding:24px;text-align:center"><h1 style="color:#fff;margin:0;font-size:22px">⚠️ ATTENDANCE ALERT</h1><p style="color:#ffeaea;margin:5px 0 0">{{ config('lms.company_name') }} - Daily Login Requirement</p></td></tr>
<tr><td style="padding:32px"><p>Hello <b>{{ $user->name }}</b>,</p>
<p>Our system shows you have <b>not logged in</b> to the library platform today.</p>
<div style="background:#fff0f0;border-left:5px solid #ff4d4d;padding:16px;margin:18px 0"><b style="color:#b30000">Why is this important?</b><br>Even if no new documents are published, all staff must check the catalog daily for system updates and policy notices.</div>
<p style="text-align:center"><a href="{{ route('login') }}" style="background:#d32f2f;color:#fff;padding:14px 28px;text-decoration:none;border-radius:5px;font-weight:bold">Login now</a></p></td></tr></table></div>

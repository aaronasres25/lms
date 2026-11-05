<div style="font-family:Arial,sans-serif;background:#f4f4f4;padding:20px">
<table width="600" align="center" style="background:#fff;border-radius:8px;overflow:hidden">
<tr><td style="background:#5b21b6;padding:28px;text-align:center"><h1 style="color:#fff;margin:0;font-size:24px">{{ strtoupper(config('lms.company_name')) }}</h1>
<p style="color:#bbdefb;margin:5px 0 0;font-size:13px">LIBRARY MANAGEMENT SYSTEM</p></td></tr>
<tr><td style="padding:32px">
<h2 style="color:#8B0000;margin-top:0">New Document Released</h2>
<p>Hello <b>{{ $user->name }}</b>,</p><p>A new official document has been published in the library:</p>
<div style="padding:10px;background:#e3f2fd;border-left:4px solid #5b21b6;color:#5b21b6;font-weight:bold">{{ $book->name }} (v{{ $book->version }})</div>
<p>Please log in to view the full content.</p>
<p style="text-align:center;margin-top:28px"><a href="{{ route('ebook.open', $book) }}" style="background:#5b21b6;color:#fff;padding:14px 28px;text-decoration:none;border-radius:5px;font-weight:bold">Open document</a></p></td></tr>
<tr><td style="background:#eee;padding:16px;text-align:center;color:#888;font-size:12px">&copy; {{ date('Y') }} {{ config('lms.company_name') }} - automated notification, do not reply.</td></tr></table></div>

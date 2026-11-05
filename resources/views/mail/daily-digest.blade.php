<div style="background:#fcfcfc;padding:20px;font-family:sans-serif">
<div style="max-width:500px;margin:auto;background:#721c24;color:#f8d7da;border-radius:15px;padding:30px;border-left:10px solid #f5c6cb">
<h2 style="margin-top:0;border-bottom:2px solid #f8d7da;padding-bottom:10px">📚 Daily Library Update</h2>
<p>Hello {{ $user->name }}, new publications released in the last 24 hours:</p>
@foreach ($books as $b)
<div style="padding:10px;border-bottom:1px solid rgba(248,215,218,.2)"><strong>📖 {{ $b->name }}</strong><br><span style="font-size:.85rem;opacity:.8">Dept: {{ $b->department->name ?? '' }} | Version: {{ $b->version }}</span></div>
@endforeach
<p style="font-style:italic;font-size:.9rem">Total new items: {{ $books->count() }}</p>
<p style="text-align:center;margin-top:28px"><a href="{{ route('catalog') }}" style="background:#f8d7da;color:#721c24;padding:12px 25px;border-radius:10px;text-decoration:none;font-weight:bold">View all in catalog</a></p></div></div>

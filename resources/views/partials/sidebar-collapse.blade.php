{{-- Collapsible sidebar (desktop). State is remembered in localStorage; mobile keeps its slide-in drawer. Include inside <head>. --}}
<script>
    try { if (localStorage.getItem('lms.sb') === '1') document.documentElement.classList.add('sb-collapsed'); } catch (e) {}
    document.addEventListener('click', function (e) {
        var b = e.target.closest && e.target.closest('#collapseBtn');
        if (!b) return;
        var collapsed = document.documentElement.classList.toggle('sb-collapsed');
        try { localStorage.setItem('lms.sb', collapsed ? '1' : '0'); } catch (err) {}
    });

    // accordion: opening a menu group closes the others; clicking the open group closes it
    document.addEventListener('click', function (e) {
        var head = e.target.closest && e.target.closest('#sidebar .sb-toggle');
        if (!head) return;
        var group = head.closest('.sb-group');
        var wasOpen = group.classList.contains('is-open');
        document.querySelectorAll('#sidebar .sb-group.is-open').forEach(function (g) { g.classList.remove('is-open'); });
        if (!wasOpen) group.classList.add('is-open');
    });
</script>
<style>
    #sidebar { overflow-x: hidden; transition: width .2s ease, transform .2s ease; }
    #mainWrap { transition: padding-left .2s ease; }

    /* accordion groups */
    #sidebar .sb-group { border-top: 1px solid rgba(255, 255, 255, .08); padding-top: .3rem; margin-top: .3rem; }
    #sidebar .sb-nest { margin-left: .9rem; padding-left: .5rem; border-left: 1px solid rgba(255, 255, 255, .12); }
    #sidebar .sb-group-body { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .25s ease; }
    #sidebar .sb-group.is-open .sb-group-body { grid-template-rows: 1fr; }
    #sidebar .sb-group-body > div { overflow: hidden; min-height: 0; }
    #sidebar .sb-chev { transition: transform .2s ease; }
    #sidebar .sb-group.is-open .sb-chev { transform: rotate(180deg); }
    #sidebar .sb-group.is-open .sb-hdr-badge { display: none; }          /* count badge only while the group is closed */
    @media (min-width: 1024px) {
        html.sb-collapsed #sidebar { width: 4.5rem; }
        html.sb-collapsed #mainWrap { padding-left: 4.5rem; }
        html.sb-collapsed #sidebar .sb-label { display: none; }
        html.sb-collapsed #sidebar nav a, html.sb-collapsed #sidebar form button { justify-content: center; padding-left: 0; padding-right: 0; }
        html.sb-collapsed #sidebar > div:first-child { justify-content: center; padding-left: .5rem; padding-right: .5rem; }
        html.sb-collapsed #sidebar .sb-toggle { display: none; }
        html.sb-collapsed #sidebar .sb-nest { margin-left: 0; padding-left: 0; border-left: 0; }
        html.sb-collapsed #sidebar .sb-group-body { grid-template-rows: 1fr; }   /* icon rail: every icon stays visible */
        html.sb-collapsed #sidebar > div:last-child > div { justify-content: center; }
        html.sb-collapsed #collapseBtn svg { transform: rotate(180deg); }
    }
</style>

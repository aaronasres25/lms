<style>
        body { font-family: 'Poppins', ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif; }
        summary { list-style: none; cursor: pointer; } summary::-webkit-details-marker { display: none; }
        .no-scrollbar::-webkit-scrollbar { display: none; } .no-scrollbar { scrollbar-width: none; }
        /* ---------- data tables ---------- */
        .tbl-wrap { background: #fff; border-radius: 1.25rem; overflow: hidden; box-shadow: 0 1px 3px rgba(76,29,149,.08), 0 12px 30px -16px rgba(76,29,149,.25); }
        .tbl-scroll { overflow-x: auto; }
        .tbl { width: 100%; border-collapse: collapse; font-size: .875rem; }
        .tbl thead th { background: linear-gradient(90deg, #5b21b6, #7c3aed); color: #fff; font-weight: 600; font-size: .7rem; letter-spacing: .07em; text-transform: uppercase; text-align: left; padding: .9rem 1rem; white-space: nowrap; }
        .tbl tbody td { padding: .8rem 1rem; border-bottom: 1px solid #f1ecfd; vertical-align: middle; }
        .tbl tbody tr:nth-child(even) { background: #faf8ff; }
        .tbl tbody tr { transition: background .15s; }
        .tbl tbody tr:hover { background: #f1ebff; }
        .tbl tbody tr:last-child td { border-bottom: 0; }
        .tbl .num { width: 3.25rem; text-align: center; }
        .tbl tbody td.num { color: #a78bfa; font-weight: 700; font-size: .75rem; }
        .tbl-empty { padding: 3rem 1rem; text-align: center; color: #94a3b8; }
        .card { background: #fff; border-radius: 1.25rem; box-shadow: 0 1px 3px rgba(76,29,149,.08), 0 12px 30px -16px rgba(76,29,149,.25); }
        .filter-bar { background: #fff; border-radius: 1.25rem; padding: .75rem; box-shadow: 0 1px 3px rgba(76,29,149,.08); }
        .filter-bar input, .filter-bar select { border: 1px solid #e7e0fb; border-radius: .7rem; padding: .5rem .75rem; font-size: .875rem; background: #fff; outline: none; }
        .filter-bar input:focus, .filter-bar select:focus { border-color: #a78bfa; }
        .btn { display: inline-flex; align-items: center; gap: .4rem; border-radius: .8rem; padding: .55rem 1.1rem; font-weight: 600; font-size: .875rem; transition: all .15s; }
        .btn-primary { background: linear-gradient(90deg, #6d28d9, #7c3aed); color: #fff; box-shadow: 0 6px 14px -6px rgba(109,40,217,.6); } .btn-primary:hover { filter: brightness(1.1); }
        .btn-outline { border: 1px solid #ddd6fe; background: #fff; color: #5b21b6; } .btn-outline:hover { background: #f5f3ff; }
        #sidebar nav::-webkit-scrollbar { width: 6px; } #sidebar nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,.2); border-radius: 3px; }
    </style>

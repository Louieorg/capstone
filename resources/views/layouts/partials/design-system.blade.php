<style>
  /* ═══════════════════════════════════════════════════════════
     LIKHA DESIGN SYSTEM — Canonical styles consolidated from:
     - layouts/partials/styles.blade.php (CSS variables)
     - problems/category.blade.php (lk-card, lk-badge, components)
     - admin/dashboard.blade.php (s-card, dash-card, admin badges)
     ═══════════════════════════════════════════════════════════ */

  /* ── CSS Custom Properties (from styles.blade.php + category.blade.php) ── */
  :root {
    --bg:         #f8f8fb;
    --bg2:        #f1f1f5;
    --surface:    #ffffff;
    --surface2:   #f5f5f8;
    --border:     rgba(0,0,0,0.08);
    --border-h:   rgba(186,117,23,0.35);
    --amber:      #b57318;
    --amber-dim:  rgba(186,117,23,0.08);
    --amber-mid:  rgba(186,117,23,0.18);
    --amber-glow: rgba(186,117,23,0.25);
    --text:       #111014;
    --text2:      #5a5870;
    --text3:      #9a97b0;
    --green:      #15803d;
    --green-bg:   rgba(22,163,74,0.08);
    --green-b:    rgba(22,163,74,0.20);
    --red:        #dc2626;
    --red-bg:     rgba(220,38,38,0.08);
    --red-b:      rgba(220,38,38,0.20);
    --blue:       #1d4ed8;
    --blue-bg:    rgba(29,78,216,0.08);
    --blue-b:     rgba(29,78,216,0.18);
  }

  html.dark {
    --bg:         #0a0b0f;
    --bg2:        #0e0f14;
    --surface:    #13141a;
    --surface2:   #1a1b23;
    --border:     rgba(255,255,255,0.06);
    --border-h:   rgba(251,176,52,0.28);
    --amber:      #fbb034;
    --amber-dim:  rgba(251,176,52,0.10);
    --amber-mid:  rgba(251,176,52,0.22);
    --amber-glow: rgba(251,176,52,0.32);
    --text:       #f0f0f5;
    --text2:      #9a9bb0;
    --text3:      #5e6175;
    --green:      #5fcd8a;
    --green-bg:   rgba(95,205,138,0.10);
    --green-b:    rgba(95,205,138,0.22);
    --red:        #f87171;
    --red-bg:     rgba(248,113,113,0.10);
    --red-b:      rgba(248,113,113,0.22);
    --blue:       #60a5fa;
    --blue-bg:    rgba(96,165,250,0.10);
    --blue-b:     rgba(96,165,250,0.20);
  }

  /* ── Base Card (.lk-card) ── */
  .lk-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 16px;
    transition: border-color .2s, transform .2s, box-shadow .2s;
  }
  .lk-card:hover {
    border-color: var(--border-h);
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.08);
  }
  html.dark .lk-card:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,0.3);
  }

  /* Card highlight state (for featured/selected items) */
  .lk-card.idea-highlight,
  .lk-card.featured {
    border-color: var(--amber-mid);
    box-shadow: 0 0 0 3px var(--amber-dim);
  }

  /* ── Badge System (.lk-badge) ── */
  .lk-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
    border: 1px solid;
    white-space: nowrap;
  }

  /* Semantic badge variants */
  .badge-amber { background: var(--amber-dim); color: var(--amber); border-color: var(--amber-mid); }
  .badge-green { background: var(--green-bg); color: var(--green); border-color: var(--green-b); }
  .badge-red   { background: var(--red-bg);   color: var(--red);   border-color: var(--red-b); }
  .badge-blue  { background: var(--blue-bg);  color: var(--blue);  border-color: var(--blue-b); }
  .badge-muted {
    background: var(--amber-dim);
    border-color: var(--border);
    color: var(--text3);
  }

  /* Admin-style badge variants (from admin/dashboard.blade.php) */
  .b-amber { background: var(--amber-dim); color: var(--amber); border-color: var(--amber-mid); }
  .b-green { background: var(--green-bg); color: var(--green); border-color: var(--green-b); }
  .b-red   { background: var(--red-bg);   color: var(--red);   border-color: var(--red-b); }
  .b-blue  { background: var(--blue-bg);  color: var(--blue);  border-color: var(--blue-b); }

  /* ── Section Heading ── */
  .co-section-head {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
  }
  .co-section-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: var(--amber); flex-shrink: 0;
  }
  .co-section-title {
    font-family: 'Sora', sans-serif;
    font-size: 14px; font-weight: 700; color: var(--text);
  }
  .co-section-sub { font-size: 12px; color: var(--text3); }

  /* ── Hero / Page Identity ── */
  .co-hero {
    position: relative; overflow: hidden;
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 18px;
    padding: 26px 28px;
  }
  .co-hero::before {
    content: ''; position: absolute; left: 0; top: 0; right: 0; height: 3px;
    background: linear-gradient(to right, var(--amber), #f97316);
  }
  .co-hero-eyebrow {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 5px 13px; border-radius: 999px;
    background: var(--amber-dim); border: 1px solid var(--amber-mid);
    font-size: 10.5px; font-weight: 700; letter-spacing: .1em;
    text-transform: uppercase; color: var(--amber);
  }
  .co-hero-title {
    font-family: 'Sora', sans-serif;
    font-size: clamp(20px, 3vw, 28px); font-weight: 800;
    line-height: 1.15; color: var(--text); margin: 14px 0 8px;
  }
  .co-hero-copy {
    font-size: 13.5px; color: var(--text2); line-height: 1.7; max-width: 720px;
  }
  @media (max-width: 640px) {
    .co-hero { padding: 20px 18px; }
  }

  /* ── Stat Cards (from admin/dashboard) ── */
  .stat-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
  }
  @media (max-width: 900px) { .stat-grid { grid-template-columns: repeat(2,1fr); } }
  @media (max-width: 500px) { .stat-grid { grid-template-columns: 1fr; } }

  .s-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 14px; padding: 20px;
    position: relative; overflow: hidden;
    transition: border-color .2s, transform .2s;
  }
  .s-card:hover { border-color: var(--border-h); transform: translateY(-2px); }
  .s-card.featured { border-color: var(--amber-mid); }
  .s-icon {
    width: 34px; height: 34px; border-radius: 9px;
    background: var(--amber-dim); border: 1px solid var(--amber-mid);
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 14px; color: var(--amber);
  }
  .s-icon.blue  { background: var(--blue-bg);  border-color: var(--blue-b);  color: var(--blue); }
  .s-icon.green { background: var(--green-bg); border-color: var(--green-b); color: var(--green); }
  .s-icon.red   { background: var(--red-bg);   border-color: var(--red-b);   color: var(--red); }
  .s-label {
    font-size: 10.5px; font-weight: 600; letter-spacing: .08em;
    text-transform: uppercase; color: var(--text3); margin-bottom: 7px;
  }
  .s-val {
    font-family: 'Sora', sans-serif; font-size: 34px; font-weight: 800;
    line-height: 1; color: var(--amber); margin-bottom: 4px;
  }
  .s-card:not(.featured) .s-val { color: var(--text); }
  .s-hint { font-size: 11.5px; color: var(--text3); }

  /* ── Dash Card (admin dashboard style) ── */
  .dash-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 14px; overflow: hidden;
    transition: border-color .2s;
  }
  .dash-card:hover { border-color: var(--border-h); }
  .dc-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 18px; border-bottom: 1px solid var(--border);
  }
  .dc-title {
    font-family: 'Sora', sans-serif; font-size: 14px;
    font-weight: 700; color: var(--text);
  }
  .dc-sub { font-size: 11.5px; color: var(--text3); }
  .dc-body { padding: 16px 18px; }

  /* ── Row Items (admin dashboard trending/pending) ── */
  .row-item {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 12px; border-radius: 10px;
    border: 1px solid var(--border);
    transition: border-color .15s, background .15s;
    margin-bottom: 6px;
  }
  .row-item:last-child { margin-bottom: 0; }
  .row-item:hover { border-color: var(--amber-mid); background: var(--amber-dim); }
  .ri-rank {
    font-family: 'Sora', sans-serif; font-size: 12px;
    font-weight: 700; color: var(--amber); width: 20px; flex-shrink: 0;
  }
  .ri-text { flex: 1; font-size: 13px; color: var(--text2); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .ri-vote {
    font-size: 11.5px; font-weight: 600; color: var(--amber);
    padding: 2px 9px; border-radius: 7px;
    background: var(--amber-dim); border: 1px solid var(--amber-mid); flex-shrink: 0;
  }

  /* ── Priority Row (admin/priority style) ── */
  .pp-row {
    background: var(--surface); border: 1px solid var(--border); border-radius: 14px;
    padding: 16px 18px; transition: border-color .15s;
  }
  .pp-row:hover { border-color: var(--amber-mid); }
  .pp-top { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; flex-wrap: wrap; margin-bottom: 8px; }
  .pp-title { font-family: 'Sora', sans-serif; font-size: 14.5px; font-weight: 700; color: var(--text); }
  .pp-meta { font-size: 11.5px; color: var(--text3); margin-top: 2px; }
  .pp-desc {
    font-size: 13px; color: var(--text3); line-height: 1.6; margin-bottom: 12px;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
  }
  .pp-foot { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
  .pp-info { font-size: 11.5px; color: var(--text3); }
  .pp-actions { display: flex; gap: 8px; }

  /* ── Buttons ── */
  .btn-amber {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 9px 20px; border-radius: 10px;
    background: var(--amber); color: #0a0b0f;
    font-size: 13px; font-weight: 600; font-family: 'DM Sans', sans-serif;
    border: none; cursor: pointer; text-decoration: none;
    transition: all .18s;
  }
  html.dark .btn-amber { background: #fbb034; }
  .btn-amber:hover { filter: brightness(1.1); transform: translateY(-1px); }

  .btn-ghost {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 9px 20px; border-radius: 10px;
    background: var(--surface2); border: 1px solid var(--border);
    color: var(--text2); font-size: 13px; font-weight: 600;
    font-family: 'DM Sans', sans-serif; cursor: pointer;
    transition: all .18s;
  }
  .btn-ghost:hover { border-color: var(--amber-mid); color: var(--text); }

  .btn-approve {
    padding: 7px 16px; border-radius: 9px;
    font-size: 12px; font-weight: 600;
    background: var(--green-bg); color: var(--green);
    border: 1px solid var(--green-b); cursor: pointer;
    font-family: 'DM Sans', sans-serif; transition: all .15s;
  }
  .btn-approve:hover { filter: brightness(1.1); transform: translateY(-1px); }

  .btn-reject {
    padding: 7px 16px; border-radius: 9px;
    font-size: 12px; font-weight: 600;
    background: transparent; color: var(--red);
    border: 1px solid var(--red-b); cursor: pointer;
    font-family: 'DM Sans', sans-serif; transition: all .15s;
  }
  .btn-reject:hover { background: var(--red-bg); transform: translateY(-1px); }

  .btn-take {
    padding: 6px 14px; border-radius: 9px; font-size: 12px; font-weight: 600;
    background: var(--blue-bg); color: var(--blue); border: 1px solid var(--blue-b);
    cursor: pointer; font-family: 'DM Sans', sans-serif;
  }

  .btn-resolve {
    padding: 6px 14px; border-radius: 9px; font-size: 12px; font-weight: 600;
    background: var(--green-bg); color: var(--green); border: 1px solid var(--green-b);
    cursor: pointer; font-family: 'DM Sans', sans-serif;
  }

  .btn-reopen {
    padding: 6px 14px; border-radius: 9px; font-size: 12px; font-weight: 600;
    background: transparent; color: var(--text3); border: 1px solid var(--border);
    cursor: pointer; font-family: 'DM Sans', sans-serif;
  }

  /* ── Form Inputs ── */
  .lk-input {
    width: 100%; background: var(--surface2);
    border: 1px solid var(--border); border-radius: 10px;
    padding: 9px 13px; font-size: 13px; color: var(--text);
    font-family: 'DM Sans', sans-serif; outline: none;
    transition: border-color .2s, box-shadow .2s;
  }
  .lk-input::placeholder { color: var(--text3); }
  .lk-input:focus {
    border-color: var(--amber-mid);
    box-shadow: 0 0 0 3px var(--amber-dim);
  }
  select.lk-input {
    background: var(--surface2);
    color: var(--text);
    color-scheme: light dark;
  }
  select.lk-input option {
    background: var(--surface);
    color: var(--text);
  }

  /* ── Tabs (admin/priority style) ── */
  .pp-tabs { display: flex; flex-wrap: wrap; gap: 8px; }
  .pp-tab {
    padding: 7px 14px; border-radius: 999px; font-size: 12px; font-weight: 600;
    border: 1px solid var(--border); color: var(--text3); background: var(--surface);
    text-decoration: none; display: inline-flex; gap: 6px; align-items: center;
  }
  .pp-tab:hover { border-color: var(--amber-mid); color: var(--text); }
  .pp-tab.active { background: var(--amber-dim); border-color: var(--amber-mid); color: var(--amber); }
  .pp-tab-count { font-size: 11px; padding: 1px 6px; border-radius: 999px; background: rgba(0,0,0,0.08); color: inherit; }
  html.dark .pp-tab-count { background: rgba(255,255,255,0.12); }

  /* ── Toolbar (admin/priority style) ── */
  .pp-toolbar {
    background: var(--surface); border: 1px solid var(--border); border-radius: 14px;
    padding: 16px 18px; display: flex; flex-wrap: wrap; gap: 10px; align-items: center;
  }
  .pp-select {
    background: var(--surface2); border: 1px solid var(--border); border-radius: 10px; padding: 8px 12px;
    font-size: 13px; color: var(--text); font-family: 'DM Sans', sans-serif;
  }
  .pp-btn {
    padding: 8px 16px; border-radius: 10px; font-size: 12.5px; font-weight: 600;
    background: var(--amber-dim); color: var(--amber); border: 1px solid var(--amber-mid);
    cursor: pointer; font-family: 'DM Sans', sans-serif;
  }

  /* ── Animations ── */
  @keyframes fadeInUp {
    from { opacity:0; transform:translateY(16px); }
    to   { opacity:1; transform:translateY(0); }
  }
  .anim-1 { animation: fadeInUp .45s ease both; }
  .anim-2 { animation: fadeInUp .45s .08s ease both; }
  .anim-3 { animation: fadeInUp .45s .16s ease both; }
  .anim-4 { animation: fadeInUp .45s .24s ease both; }
  .anim-5 { animation: fadeInUp .45s .32s ease both; }

  /* ── Accordion ── */
  .accordion-btn {
    display: flex; justify-content: space-between; align-items: center;
    width: 100%; padding: 16px 20px; background: none; border: none;
    cursor: pointer; text-align: left; font-size: 13.5px; font-weight: 600;
    color: var(--text); font-family: 'DM Sans', sans-serif;
    transition: background .15s;
  }
  .accordion-btn:hover { background: var(--amber-dim); }
  .accordion-icon {
    width: 16px; height: 16px; flex-shrink: 0;
    color: var(--text3); transition: transform .25s;
  }
  .accordion-btn[aria-expanded="true"] .accordion-icon { transform: rotate(180deg); }
  .accordion-body {
    padding: 16px 20px 20px;
    border-top: 1px solid var(--border);
    background: var(--surface2);
  }

  /* ── Factor Bars ── */
  .factor-bar {
    height: 5px; border-radius: 999px;
    background: var(--border); overflow: hidden; margin-top: 5px;
  }
  .factor-fill {
    height: 100%; border-radius: 999px;
    background: linear-gradient(to right, var(--amber), #f97316);
    transition: width .6s ease;
  }

  /* ── Evidence Stat Grid ── */
  .co-evidence {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 10px; margin-bottom: 16px;
  }
  .co-evidence-item {
    background: var(--surface2); border: 1px solid var(--border);
    border-radius: 12px; padding: 12px 14px;
  }
  .co-evidence-value {
    font-family: 'Sora', sans-serif; font-size: 18px; font-weight: 700;
    color: var(--text); line-height: 1.1;
  }
  .co-evidence-label {
    margin-top: 4px; font-size: 10.5px; font-weight: 600;
    letter-spacing: .06em; text-transform: uppercase; color: var(--text3);
  }

  /* ── Empty State ── */
  .empty-state { padding: 32px; text-align: center; }
  .empty-ico {
    width: 36px; height: 36px; border-radius: 10px;
    background: var(--amber-dim); border: 1px solid var(--amber-mid);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 10px; color: var(--amber);
  }
  .empty-text { font-size: 13px; color: var(--text3); }

  /* ── Report Row ── */
  .report-row {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 14px; padding: 18px 20px;
    transition: border-color .2s, transform .2s;
  }
  .report-row:hover { border-color: var(--border-h); transform: translateY(-2px); }

  /* ── Comparison Table ── */
  .cmp-table th {
    font-size: 10.5px; text-transform: uppercase; letter-spacing: .07em;
    font-weight: 600; padding: 10px 16px; color: var(--text3);
    background: var(--surface2); border-bottom: 1px solid var(--border);
  }
  .cmp-table td {
    padding: 10px 16px; font-size: 13px;
    border-bottom: 1px solid var(--border); color: var(--text2);
  }
  .cmp-table tr:last-child td { border-bottom: none; }
  .cmp-table tr:hover td { background: var(--amber-dim); }
  .cmp-winner { color: var(--green); font-weight: 700; }

  /* ── Steps / Next Steps ── */
  .co-steps {
    display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;
  }
  @media (max-width: 640px) {
    .co-steps { grid-template-columns: 1fr; }
  }
  .co-step {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 14px; padding: 18px 18px 16px;
  }
  .co-step-num {
    font-family: 'Sora', sans-serif; font-size: 12px; font-weight: 700;
    letter-spacing: .1em; color: var(--amber); margin-bottom: 8px;
  }
  .co-step-title {
    font-family: 'Sora', sans-serif; font-size: 14px; font-weight: 700;
    color: var(--text); margin-bottom: 6px;
  }
  .co-step-copy { font-size: 12.5px; color: var(--text2); line-height: 1.65; }

  /* ── AI Wording Assistance Box (optional, secondary to DSS results) ── */
  .co-ai {
    margin-top: 20px; padding: 14px 16px 16px; border-radius: 12px;
    background: var(--surface2); border: 1px solid var(--border);
    border-left: 2px solid var(--amber-mid);
  }
  .co-ai-head {
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 8px; margin-bottom: 10px;
  }
  .co-ai-note { font-size: 11.5px; color: var(--text3); line-height: 1.6; margin-bottom: 12px; }
  .co-ai-toggle {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 11px; border-radius: 999px;
    background: var(--amber-dim); border: 1px solid var(--amber-mid);
    color: var(--amber); font-family: 'DM Sans', sans-serif;
    font-size: 11.5px; font-weight: 600; line-height: 1; cursor: pointer;
    transition: background .15s;
  }
  .co-ai-toggle:hover { background: var(--amber-mid); }
  .co-ai-toggle-icon { width: 13px; height: 13px; flex-shrink: 0; transition: transform .25s; }
  .co-ai-toggle[aria-expanded="true"] .co-ai-toggle-icon { transform: rotate(180deg); }

  /* ── Adviser Review Box ── */
  .co-adviser {
    margin: 0 28px 28px; padding: 16px 20px; border-radius: 12px;
    background: var(--blue-bg); border: 1px solid var(--blue-b);
  }
  .co-adviser-kicker {
    font-size: 12px; font-weight: 600; color: var(--blue);
    margin-bottom: 6px; display: flex; align-items: center; gap: 6px;
  }
  .co-adviser-dot {
    width: 7px; height: 7px; border-radius: 50%; background: var(--blue); display: inline-block;
  }

  /* ── Review Form ── */
  .review-form {
    margin: 0 28px 28px;
    padding: 20px 22px;
    border-radius: 14px;
    background: var(--surface2);
    border: 1px solid var(--border);
  }
  .review-form-head {
    display: flex; align-items: center; gap: 8px;
    margin-bottom: 16px;
    font-size: 11px; font-weight: 700; letter-spacing: .08em;
    text-transform: uppercase; color: var(--amber);
  }
  .review-form-head-dot {
    width: 7px; height: 7px; border-radius: 50%; background: var(--amber); flex-shrink: 0;
  }
  .review-form-copy {
    font-size: 12.5px; color: var(--text3); line-height: 1.6; margin-bottom: 16px;
  }
  .review-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 12px;
  }
  @media (max-width: 640px) { .review-grid { grid-template-columns: 1fr; } }
  .review-field-label {
    display: block; margin-bottom: 5px; font-size: 11.5px; color: var(--text3);
  }

  /* Nested insets are desktop-sized; drop them on phones so the form keeps a
     usable measure instead of stacking margins on card + page gutters. */
  @media (max-width: 640px) {
    .review-form { margin: 0 0 16px; padding: 16px 14px; }
    .co-adviser { margin: 0 0 16px; padding: 14px; }
  }

  /* ── Sort Pills ── */
  .sort-pills {
    display: flex; flex-wrap: gap-1.5;
    gap: 12px;
  }
  .sort-pill {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 8px 16px; border-radius: 999px;
    font-size: 13px; font-weight: 600;
    background: var(--surface); border: 1px solid var(--border);
    color: var(--text2); text-decoration: none;
    transition: all .15s;
  }
  .sort-pill:hover { border-color: var(--amber-mid); color: var(--amber); background: var(--amber-dim); }
  .sort-pill.active { background: var(--amber-dim); border-color: var(--amber-mid); color: var(--amber); }
  .sort-pill i { width: 16px; height: 16px; }

  /* ── Filter Tags ── */
  .filter-tags {
    display: flex; flex-wrap: wrap; gap: 8px; align-items: center;
  }
  .filter-tag {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 12px; border-radius: 999px;
    font-size: 11px; font-weight: 600;
    background: var(--amber-dim); border: 1px solid var(--amber-mid);
    color: var(--amber);
  }
  .filter-tag-remove {
    width: 22px; height: 22px; border-radius: 50%;
    margin: -3px; display: flex; align-items: center; justify-content: center;
    color: var(--amber); cursor: pointer;
    touch-action: manipulation;
    transition: background .15s;
  }
  .filter-tag-remove:hover { background: var(--amber-mid); }

  /* ── Utilities ── */
  .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0; }
  [x-cloak] { display: none !important; }
  *, *::before, *::after { box-sizing: border-box; }
</style>
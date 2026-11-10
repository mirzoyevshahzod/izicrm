@extends('panel.layout')

@section('title', __('Davomat'))
@section('permission', 'attendance.view')

@section('content')
    <div class="page-head">
        <div class="tabs" id="view-tabs">
            <button type="button" data-view="daily" class="active">{{ __('Kunlik holat') }}</button>
            <button type="button" data-view="letters">{{ __('Tushuntirish xatlari') }}</button>
        </div>
    </div>

    {{-- ================= KUNLIK HOLAT ================= --}}
    <section id="view-daily">
        <div class="card att-bar">
            <div class="date-nav">
                <button class="btn ghost icon-only" id="d-prev" type="button" aria-label="{{ __('Oldingi kun') }}" title="{{ __('Oldingi kun') }}">
                    <svg class="icon"><use href="#i-chevron-left"></use></svg>
                </button>
                <input class="input" id="d-date" type="date">
                <button class="btn ghost icon-only" id="d-next" type="button" aria-label="{{ __('Keyingi kun') }}" title="{{ __('Keyingi kun') }}">
                    <svg class="icon"><use href="#i-chevron-right"></use></svg>
                </button>
                <button class="btn secondary" id="d-today" type="button">{{ __('Bugun') }}</button>
            </div>
            <div class="att-meta" id="d-meta"></div>
            <button class="btn ghost" id="d-refresh" type="button">
                <svg class="icon"><use href="#i-refresh"></use></svg> {{ __('Yangilash') }}
            </button>
        </div>

        <div id="d-notice"></div>

        <div class="grid stats" id="d-stats"></div>

        <div class="card">
            <div class="toolbar">
                <label class="field grow">
                    <span>{{ __('Xodim') }}</span>
                    <div class="input-icon">
                        <svg class="icon"><use href="#i-search"></use></svg>
                        <input class="input" id="d-search" type="search" placeholder="{{ __('Ism, familiya yoki ID') }}">
                    </div>
                </label>
                <label class="field">
                    <span>{{ __('Bo‘lim') }}</span>
                    <select class="select" id="d-dept">
                        <option value="">{{ __('Barcha bo‘limlar') }}</option>
                    </select>
                </label>
                <span class="spacer"></span>
                <span class="badge" id="d-filter-label" hidden></span>
                <button class="btn secondary" id="d-export" type="button">
                    <svg class="icon"><use href="#i-download"></use></svg> Excel
                </button>
            </div>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th style="width:56px">#</th>
                        <th>{{ __('Xodim') }}</th>
                        <th>{{ __('Holat') }}</th>
                        <th>{{ __('Keldi') }}</th>
                        <th>{{ __('Kechikish') }}</th>
                        <th>{{ __('Ketdi') }}</th>
                        <th>{{ __('Izoh') }}</th>
                    </tr>
                    </thead>
                    <tbody id="d-rows"></tbody>
                </table>
            </div>

            <div class="table-footer">
                <span id="d-summary"></span>
            </div>
        </div>
    </section>

    {{-- ================= TUSHUNTIRISH XATLARI ================= --}}
    <section id="view-letters" hidden>
        <div class="card">
            <div class="toolbar">
                <label class="field grow">
                    <span>{{ __('Xodim') }} <span class="hint">— {{ __('bir nechtasini vergul bilan') }}</span></span>
                    <div class="input-icon">
                        <svg class="icon"><use href="#i-search"></use></svg>
                        <input class="input" id="f-fio" type="search" placeholder="{{ __('Ism yoki familiya') }}">
                    </div>
                </label>
                <label class="field">
                    <span>{{ __('Kun') }}</span>
                    <input class="input" id="f-date" type="date">
                </label>
                <label class="field">
                    <span>{{ __('Oy') }}</span>
                    <input class="input" id="f-month" type="month">
                </label>
                <button class="btn ghost" id="f-reset" type="button">{{ __('Tozalash') }}</button>
                <span class="spacer"></span>
                <button class="btn secondary" id="export" type="button">
                    <svg class="icon"><use href="#i-download"></use></svg> Excel
                </button>
            </div>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th style="width:56px">#</th>
                        <th>{{ __('Xodim') }}</th>
                        <th>{{ __('Kechikish sababi') }}</th>
                        <th>{{ __('Kechikish') }}</th>
                        <th>{{ __('Sana') }}</th>
                    </tr>
                    </thead>
                    <tbody id="rows"></tbody>
                </table>
            </div>

            <div class="table-footer">
                <span id="summary"></span>
                <div class="pagination" id="pagination"></div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script>
Panel.ready.then(() => {
    const { api, esc, fmt, icon, pagination, debounce, download, toast, setLoading, t } = Panel;

    const lateBadge = (min) => {
        const cls = min >= 60 ? 'red' : min >= 15 ? 'amber' : 'primary';
        return `<span class="badge ${cls}">${esc(fmt.minutes(min))}</span>`;
    };

    // ---------- Bo'limlar (tablar) ----------
    const views = { daily: document.getElementById('view-daily'), letters: document.getElementById('view-letters') };
    let lettersReady = false;

    function showView(name) {
        if (!views[name]) name = 'daily';
        Object.entries(views).forEach(([k, el]) => { el.hidden = k !== name; });
        document.querySelectorAll('#view-tabs button').forEach((b) => b.classList.toggle('active', b.dataset.view === name));
        history.replaceState(null, '', name === 'daily' ? location.pathname : '#' + name);
        if (name === 'letters' && !lettersReady) { lettersReady = true; initLetters(); }
    }

    document.getElementById('view-tabs').addEventListener('click', (e) => {
        const b = e.target.closest('button[data-view]');
        if (b) showView(b.dataset.view);
    });

    // =====================================================
    // KUNLIK HOLAT
    // =====================================================
    const d = {
        date: document.getElementById('d-date'),
        search: document.getElementById('d-search'),
        dept: document.getElementById('d-dept'),
        rows: document.getElementById('d-rows'),
        stats: document.getElementById('d-stats'),
        meta: document.getElementById('d-meta'),
        notice: document.getElementById('d-notice'),
        label: document.getElementById('d-filter-label'),
    };

    const pad = (n) => String(n).padStart(2, '0');
    const isoDate = (dt) => `${dt.getFullYear()}-${pad(dt.getMonth() + 1)}-${pad(dt.getDate())}`;
    const todayIso = () => isoDate(new Date());
    const shiftDate = (iso, days) => { const dt = new Date(iso + 'T12:00:00'); dt.setDate(dt.getDate() + days); return isoDate(dt); };

    const STATUS = {
        on_time: { label: t('Vaqtida'), cls: 'green' },
        late: { label: t('Kech qoldi'), cls: 'amber' },
        not_arrived: { label: t('Hali kelmagan'), cls: '' },
        absent: { label: t('Kelmagan'), cls: 'red' },
        excused: { label: t('Sababli'), cls: 'primary' },
        day_off: { label: t('Dam olish'), cls: '' },
    };

    const FILTERS = {
        all: () => true,
        present: (r) => r.status === 'on_time' || r.status === 'late',
        late: (r) => r.status === 'late',
        missing: (r) => r.status === 'absent' || r.status === 'not_arrived',
        excused: (r) => r.status === 'excused',
        early: (r) => r.early_minutes > 0,
    };

    let report = null;
    let filter = 'all';
    let timer = null;
    let reqId = 0;

    d.date.max = todayIso();
    d.date.value = todayIso();

    function statCard(key, iconName, color, label, value, sub) {
        return `
            <button type="button" class="card stat stat-btn ${filter === key ? 'active' : ''}" data-filter="${key}">
                <span class="stat-icon ${color}">${icon(iconName)}</span>
                <div>
                    <div class="stat-label">${esc(label)}</div>
                    <div class="stat-value">${esc(fmt.number(value))}</div>
                    ${sub ? `<div class="stat-sub">${esc(sub)}</div>` : ''}
                </div>
            </button>`;
    }

    function filterLabel(key) {
        if (!report) return '';
        return {
            present: t('Kelgan'),
            late: t('Kech qolgan'),
            missing: report.day_over ? t('Kelmagan') : t('Hali kelmagan'),
            excused: t('Sababli'),
            early: t('Erta ketgan'),
        }[key] || '';
    }

    function renderStats() {
        const s = report.summary;
        const missing = s.absent + s.not_arrived;

        d.stats.innerHTML = [
            statCard('all', 'users', '', t('Jami xodimlar'), s.total, t('Kelgan: :n', { n: fmt.number(s.present) })),
            statCard('present', 'check', 'green', t('Kelgan'), s.present, t('Vaqtida: :n', { n: fmt.number(s.on_time) })),
            statCard('late', 'clock', 'amber', t('Kech qolgan'), s.late,
                s.late ? t('Jami :v', { v: fmt.minutes(s.late_minutes) }) : t('Kechikish yo‘q')),
            statCard('missing', 'alert', 'red', report.day_over ? t('Kelmagan') : t('Hali yetib kelmagan'), missing,
                report.day_over ? t('Sababsiz') : t('Ish vaqti tugaguncha (:time)', { time: report.work_end })),
            statCard('excused', 'file-text', '', t('Sababli (botda yozgan)'), s.excused, t('“Bugun kela olmayman”')),
            statCard('early', 'logout', 'amber', t('Erta ketgan'), s.early_left,
                report.day_over ? t(':time dan oldin', { time: report.work_end }) : t(':time dan keyin aniqlanadi', { time: report.work_end })),
        ].join('');
    }

    function renderNotice() {
        const parts = [];
        if (!report.is_workday) {
            parts.push(`<div class="notice">${icon('calendar')}<div>${esc(t('Yakshanba — dam olish kuni. Kelganlar faqat ma’lumot uchun ko‘rsatiladi.'))}</div></div>`);
        } else if (report.is_today && !report.day_over) {
            parts.push(`<div class="notice info">${icon('clock')}<div>${esc(t('Ish vaqti hali tugamagan: kelmaganlar :time gacha «Hali yetib kelmagan» deb ko‘rsatiladi, keyin «Kelmagan» deb belgilanadi. Erta ketganlar ham :time dan keyin aniqlanadi.', { time: report.work_end }))}</div></div>`);
        }
        if (!report.live) {
            parts.push(`<div class="notice">${icon('alert')}<div>${esc(t('HikCentral bazasiga ulanib bo‘lmadi — kelish/ketish vaqtlari to‘liq ko‘rsatilmasligi mumkin.'))}</div></div>`);
        }
        d.notice.innerHTML = parts.join('');
    }

    function renderDepartments() {
        const current = d.dept.value;
        const depts = [...new Set(report.employees.map((r) => r.department).filter(Boolean))].sort();
        d.dept.innerHTML = `<option value="">${esc(t('Barcha bo‘limlar'))}</option>`
            + depts.map((x) => `<option value="${esc(x)}">${esc(x)}</option>`).join('');
        d.dept.value = depts.includes(current) ? current : '';
    }

    function noteCell(r) {
        if (r.excuse_reason) {
            const prefix = r.status === 'excused' ? '' : t('Oldindan yozgan:') + ' ';
            return `<div class="truncate" title="${esc(r.excuse_reason)}">${icon('file-text')} ${esc(prefix + r.excuse_reason)}</div>
                    <div class="cell-sub">${esc(t('Botda :time da yozilgan', { time: r.excuse_at }))}</div>`;
        }
        if (r.status === 'late') {
            if (r.late_reason) return `<div class="truncate" title="${esc(r.late_reason)}">${esc(r.late_reason)}</div>`;
            if (!r.reason_required) return `<span class="muted">${esc(t('Sabab so‘ralmaydi (:n daq gacha)', { n: report.late_grace_minutes }))}</span>`;
            return `<span class="muted">${esc(r.registered ? t('Sabab hali yozilmagan') : t('Botda ro‘yxatdan o‘tmagan'))}</span>`;
        }
        if ((r.status === 'absent' || r.status === 'not_arrived') && !r.registered) {
            return `<span class="muted">${esc(t('Botda ro‘yxatdan o‘tmagan'))}</span>`;
        }
        return '<span class="muted">—</span>';
    }

    function renderRows() {
        const q = d.search.value.trim().toLowerCase();
        const dept = d.dept.value;
        const list = report.employees.filter((r) => FILTERS[filter](r)
            && (!dept || r.department === dept)
            && (!q || r.name.toLowerCase().includes(q) || String(r.person_id).includes(q)));

        const label = filterLabel(filter);
        d.label.hidden = !label;
        d.label.innerHTML = label ? `${esc(label)} <button type="button" class="badge-x" aria-label="${esc(t('Filtrni olib tashlash'))}">${icon('x')}</button>` : '';

        if (!list.length) {
            d.rows.innerHTML = `<tr><td colspan="7"><div class="state">${icon('search')}<div class="title">${t('Hech narsa topilmadi')}</div></div></td></tr>`;
        } else {
            d.rows.innerHTML = list.map((r, i) => {
                const st = STATUS[r.status] || { label: r.status, cls: '' };
                const left = r.early_minutes > 0
                    ? `<div class="nowrap">${esc(r.left_at || '—')}</div><span class="badge red">${esc(t(':v erta', { v: fmt.minutes(r.early_minutes) }))}</span>`
                    : `<span class="${r.left_at ? '' : 'muted'}">${esc(r.left_at || '—')}</span>`;
                return `
                    <tr>
                        <td class="muted">${i + 1}</td>
                        <td>
                            <div class="cell-main">${esc(r.name || '—')}</div>
                            <div class="cell-sub">${esc(r.department || '—')} · ID ${esc(r.person_id)}</div>
                        </td>
                        <td><span class="badge ${st.cls}">${esc(st.label)}</span></td>
                        <td class="nowrap ${r.arrived_at ? '' : 'muted'}">${esc(r.arrived_at || '—')}</td>
                        <td>${r.late_minutes > 0 ? lateBadge(r.late_minutes) : '<span class="muted">—</span>'}</td>
                        <td>${left}</td>
                        <td>${noteCell(r)}</td>
                    </tr>`;
            }).join('');
        }

        document.getElementById('d-summary').textContent = list.length === report.employees.length
            ? t('Jami: :n ta xodim', { n: fmt.number(list.length) })
            : t(':n / :total ta xodim', { n: fmt.number(list.length), total: fmt.number(report.employees.length) });
    }

    function render() {
        d.meta.innerHTML = `
            <span>${icon('calendar')} ${esc(fmt.longDate(report.date + 'T12:00:00'))}</span>
            ${report.is_workday ? `<span>${icon('clock')} ${esc(t('Ish vaqti: :from–:to', { from: report.work_start, to: report.work_end }))}</span>` : ''}
            ${report.is_today ? `<span class="muted">${esc(t('Yangilandi: :time', { time: report.generated_at }))}</span>` : ''}`;
        renderNotice();
        renderStats();
        renderDepartments();
        renderRows();
    }

    async function loadDaily({ silent = false } = {}) {
        const id = ++reqId;
        const date = d.date.value || todayIso();
        document.getElementById('d-next').disabled = date >= todayIso();
        document.getElementById('d-today').disabled = date === todayIso();

        if (!silent) {
            d.rows.innerHTML = `<tr><td colspan="7"><div class="skeleton"></div></td></tr>`.repeat(6);
        }
        try {
            const data = await api('/attendances/daily', { query: { date } });
            if (id !== reqId) return;
            report = data;
            render();
        } catch (e) {
            if (id !== reqId) return;
            if (silent) return;
            d.stats.innerHTML = '';
            d.rows.innerHTML = `<tr><td colspan="7"><div class="state">${icon('alert')}<div class="title">${esc(e.message)}</div></div></td></tr>`;
        }

        clearTimeout(timer);
        if (report && report.is_today) {
            // Bugungi kun — har daqiqada jim yangilanadi (sahifa ko'rinib turgan bo'lsa)
            const tick = () => {
                if (document.hidden || views.daily.hidden) timer = setTimeout(tick, 60000);
                else loadDaily({ silent: true });
            };
            timer = setTimeout(tick, 60000);
        }
    }

    const go = (iso) => {
        if (iso > todayIso()) iso = todayIso();
        d.date.value = iso;
        loadDaily();
    };

    d.date.addEventListener('change', () => go(d.date.value || todayIso()));
    document.getElementById('d-prev').addEventListener('click', () => go(shiftDate(d.date.value || todayIso(), -1)));
    document.getElementById('d-next').addEventListener('click', () => go(shiftDate(d.date.value || todayIso(), 1)));
    document.getElementById('d-today').addEventListener('click', () => go(todayIso()));
    document.getElementById('d-refresh').addEventListener('click', () => loadDaily());
    d.search.addEventListener('input', debounce(() => report && renderRows(), 150));
    d.dept.addEventListener('change', () => report && renderRows());

    // Excel — ekranda ko'rinib turgan ro'yxat (sana, kartochka, bo'lim, qidiruv bo'yicha)
    document.getElementById('d-export').addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        const date = d.date.value || todayIso();
        setLoading(btn, true);
        try {
            await download('/attendances/daily/export', {
                date,
                filter: filter === 'all' ? '' : filter,
                department: d.dept.value,
                q: d.search.value.trim(),
            }, `${t('davomat')}_${date}.xlsx`);
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            setLoading(btn, false);
        }
    });

    d.stats.addEventListener('click', (e) => {
        const b = e.target.closest('[data-filter]');
        if (!b || !report) return;
        filter = filter === b.dataset.filter ? 'all' : b.dataset.filter;
        renderStats();
        renderRows();
    });
    d.label.addEventListener('click', (e) => {
        if (!e.target.closest('.badge-x')) return;
        filter = 'all';
        renderStats();
        renderRows();
    });

    // =====================================================
    // TUSHUNTIRISH XATLARI (oldingi ro'yxat)
    // =====================================================
    function initLetters() {
        const rows = document.getElementById('rows');
        const f = { fio: document.getElementById('f-fio'), date: document.getElementById('f-date'), month: document.getElementById('f-month') };

        function filters() {
            const q = { fio: f.fio.value.trim() };
            if (f.date.value) {
                const [y, m, dd] = f.date.value.split('-');
                Object.assign(q, { year: +y, month: +m, day: +dd });
            } else if (f.month.value) {
                const [y, m] = f.month.value.split('-');
                Object.assign(q, { year: +y, month: +m });
            }
            return q;
        }

        async function load(page = 1) {
            rows.innerHTML = `<tr><td colspan="5"><div class="skeleton"></div></td></tr>`.repeat(5);
            try {
                const { data } = await api('/attendances/search', { query: { ...filters(), page } });
                if (!data.data.length) {
                    rows.innerHTML = `<tr><td colspan="5"><div class="state">${icon('search')}<div class="title">${t('Hech narsa topilmadi')}</div><div>${t('Filtrlarni o‘zgartirib ko‘ring')}</div></div></td></tr>`;
                } else {
                    rows.innerHTML = data.data.map((r, i) => `
                        <tr>
                            <td class="muted">${data.from + i}</td>
                            <td>
                                <div class="cell-main">${esc(r.fio || '—')}</div>
                                <div class="cell-sub">${esc(r.company || '')}${r.company && r.chat_id ? ' · ' : ''}${r.chat_id ? 'ID ' + esc(r.chat_id) : ''}</div>
                            </td>
                            <td><div class="truncate" title="${esc(r.reason || '')}">${esc(r.reason || '—')}</div></td>
                            <td>${lateBadge(Number(r.late_minutes || 0))}</td>
                            <td class="nowrap">${String(r.day).padStart(2, '0')}.${String(r.month).padStart(2, '0')}.${r.year}</td>
                        </tr>`).join('');
                }
                document.getElementById('summary').textContent = t('Jami: :n ta yozuv', { n: fmt.number(data.total) });
                pagination(document.getElementById('pagination'), data.current_page, data.last_page, load);
            } catch (e) {
                rows.innerHTML = `<tr><td colspan="5"><div class="state">${icon('alert')}<div class="title">${esc(e.message)}</div></div></td></tr>`;
            }
        }

        f.fio.addEventListener('input', debounce(() => load(1)));
        f.date.addEventListener('change', () => { if (f.date.value) f.month.value = ''; load(1); });
        f.month.addEventListener('change', () => { if (f.month.value) f.date.value = ''; load(1); });
        document.getElementById('f-reset').addEventListener('click', () => { f.fio.value = f.date.value = f.month.value = ''; load(1); });

        document.getElementById('export').addEventListener('click', async (e) => {
            const btn = e.currentTarget;
            setLoading(btn, true);
            try {
                await download('/attendances/export', filters(), t('davomat') + '.xlsx');
            } catch (err) {
                toast(err.message, 'error');
            } finally {
                setLoading(btn, false);
            }
        });

        load(1);
    }

    showView(location.hash.slice(1));
    loadDaily();
});
</script>
@endpush

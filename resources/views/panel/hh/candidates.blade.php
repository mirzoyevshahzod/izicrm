@extends('panel.layout')

@section('title', 'Nomzodlar')
@section('permission', 'hh.view')

@section('content')
    <div class="page-head">
        <div>
            <a href="{{ route('panel.hh', [], false) }}" class="muted" style="display:inline-flex;align-items:center;gap:4px">
                <svg class="icon"><use href="#i-chevron-left"></use></svg> Qidiruvlar
            </a>
            <h2 id="search-name" style="margin-top:6px">&nbsp;</h2>
            <p id="search-sub"></p>
        </div>
        <div class="row">
            <button class="btn secondary" id="run"><svg class="icon"><use href="#i-refresh"></use></svg> Hozir tekshirish</button>
            <a class="btn secondary" id="edit-link" href="#"><svg class="icon"><use href="#i-filter"></use></svg> Filtr</a>
        </div>
    </div>

    <div class="row" style="justify-content:space-between; margin-bottom:16px">
        <div class="tabs" id="tabs"></div>
        <div class="row">
            <select class="select" id="min-score" style="width:auto">
                <option value="">Har qanday foiz</option>
                <option value="50">50% dan yuqori</option>
                <option value="70">70% dan yuqori</option>
                <option value="85">85% dan yuqori</option>
            </select>
            <select class="select" id="sort" style="width:auto">
                <option value="score">Moslik bo‘yicha</option>
                <option value="date">Yangilangan sana bo‘yicha</option>
            </select>
        </div>
    </div>

    <div class="candidates" id="list"></div>

    <div class="table-footer card" style="margin-top:16px">
        <span id="summary"></span>
        <div class="pagination" id="pagination"></div>
    </div>
@endsection

@push('scripts')
<script>
Panel.ready.then(() => {
    const { api, esc, fmt, icon, toast, pagination, setLoading } = Panel;
    const searchId = location.pathname.match(/\/hh\/searches\/(\d+)/)[1];
    const list = document.getElementById('list');
    const STATUS = {
        '': 'Hammasi',
        new: 'Yangi',
        viewed: 'Ko‘rilgan',
        selected: 'Tanlangan',
        rejected: 'Rad etilgan',
    };
    let status = '';
    let items = [];
    let page = 1;

    document.getElementById('edit-link').href = `/panel/hh/searches/${searchId}/edit`;

    const ringClass = (s) => s >= 75 ? 'high' : s >= 50 ? 'mid' : 'low';
    const monthName = (d) => {
        if (!d) return 'hozirgacha';
        const [y, m] = d.split('-');
        return `${m}.${y}`;
    };
    const months = (n) => {
        if (n === null || n === undefined) return null;
        const y = Math.floor(n / 12), m = n % 12;
        return [y ? `${y} yil` : '', m ? `${m} oy` : ''].filter(Boolean).join(' ') || '1 oydan kam';
    };

    function renderTabs(counts) {
        const total = Object.values(counts || {}).reduce((a, b) => a + Number(b), 0);
        document.getElementById('tabs').innerHTML = Object.entries(STATUS).map(([key, label]) => `
            <button type="button" data-tab="${key}" class="${key === status ? 'active' : ''}">${label}<span class="count">${fmt.number(key ? (counts?.[key] || 0) : total)}</span></button>`).join('');
    }

    function card(c, i) {
        const r = c.resume;
        const meta = [
            r.age ? `<span>${r.age} yosh${r.gender ? ', ' + esc(r.gender.toLowerCase()) : ''}</span>` : (r.gender ? `<span>${esc(r.gender)}</span>` : ''),
            r.area ? `<span>${icon('map-pin')}${esc(r.area)}</span>` : '',
            months(r.experience_months) ? `<span>${icon('briefcase')}Tajriba: ${esc(months(r.experience_months))}</span>` : '<span>Tajriba yo‘q</span>',
            r.salary ? `<span class="salary">${esc(fmt.number(r.salary.amount))} ${esc(r.salary.currency)}</span>` : '<span>Maosh ko‘rsatilmagan</span>',
        ].filter(Boolean).join('');

        const jobs = r.jobs.length ? `<ul class="jobs">${r.jobs.map((j) => `
            <li><span class="period">${esc(monthName(j.start))} – ${esc(monthName(j.end))}</span>
            <span><strong>${esc(j.position || '—')}</strong>${j.company ? ' · ' + esc(j.company) : ''}</span></li>`).join('')}</ul>` : '';

        const edu = r.education.level ? `<div class="edu">${icon('graduation')}${esc(r.education.level)}${r.education.place ? ' · ' + esc(r.education.place) : ''}${r.education.year ? ' (' + r.education.year + ')' : ''}</div>` : '';

        const breakdown = (c.details || []).map((d) => `
            <div class="item">
                <span>${esc(d.label)}</span>
                <span class="bar"><i style="width:${Math.round(d.score * 100)}%"></i></span>
                <span class="muted">${Math.round(d.score * 100)}%</span>
                <span class="why-note">${esc(d.note)}</span>
            </div>`).join('');

        const statusBadge = { new: '<span class="badge primary">Yangi</span>', viewed: '<span class="badge">Ko‘rilgan</span>', selected: '<span class="badge green">Tanlangan</span>', rejected: '<span class="badge red">Rad etilgan</span>' }[c.status];

        return `
        <div class="card candidate ${c.status === 'rejected' ? 'rejected' : ''}">
            <div class="score-ring ${ringClass(c.score)}" style="--p:${c.score}" title="Moslik"><b>${c.score}<small>%</small></b></div>
            <div style="min-width:0">
                <div class="row" style="gap:8px">
                    <a class="title" href="${esc(r.url || '#')}" target="_blank" rel="noopener" data-open="${i}">${esc(r.title || 'Lavozim ko‘rsatilmagan')}</a>
                    ${statusBadge}
                    ${r.job_search_status ? `<span class="badge amber">${esc(r.job_search_status)}</span>` : ''}
                </div>
                <div class="meta">${meta}</div>
                ${jobs}
                ${edu}
                <div class="why">
                    <button type="button" class="why-toggle" data-why="${i}">Nega ${c.score}%?</button>
                    <div class="breakdown" hidden>${breakdown}</div>
                </div>
            </div>
            <div class="side">
                <a class="btn" href="${esc(r.url || '#')}" target="_blank" rel="noopener" data-open="${i}">${icon('external')} hh.uz da ochish</a>
                <button class="btn secondary" data-status="selected" data-i="${i}" ${c.status === 'selected' ? 'disabled' : ''}>${icon('thumbs-up')} Tanlash</button>
                <button class="btn ghost" data-status="rejected" data-i="${i}" ${c.status === 'rejected' ? 'disabled' : ''}>${icon('thumbs-down')} Rad etish</button>
                <div class="cell-sub" style="text-align:center">Yangilangan: ${esc(fmt.date(r.updated_at))}</div>
            </div>
        </div>`;
    }

    async function load(p = 1) {
        page = p;
        list.innerHTML = '<div class="card candidate"><div class="skeleton" style="height:64px;border-radius:50%"></div><div><div class="skeleton"></div><div class="skeleton" style="margin-top:10px;width:60%"></div></div></div>'.repeat(3);
        try {
            const res = await api(`/hh/searches/${searchId}/candidates`, {
                query: { status, page, min_score: document.getElementById('min-score').value, sort: document.getElementById('sort').value },
            });
            const s = res.search;
            document.getElementById('search-name').textContent = s.name;
            document.getElementById('search-sub').innerHTML = [
                s.last_found !== null ? `hh.uz da hozir ${fmt.number(s.last_found)} ta mos rezyume` : '',
                s.last_run_at ? `oxirgi tekshiruv ${esc(fmt.dateTime(s.last_run_at))}` : 'hali tekshirilmagan',
                s.is_active ? 'har soatda yangilanadi' : '<span class="badge">To‘xtatilgan</span>',
                s.last_error ? `<span class="badge red" title="${esc(s.last_error)}">Oxirgi tekshiruvda xato</span>` : '',
            ].filter(Boolean).join(' · ');
            renderTabs(res.counts);

            items = res.data;
            list.innerHTML = items.length ? items.map(card).join('') : `<div class="card"><div class="state">${icon('briefcase')}
                <div class="title">${status ? 'Bu bo‘limda nomzod yo‘q' : 'Hali nomzodlar yo‘q'}</div>
                <div>${status ? 'Boshqa bo‘limni tanlang' : '“Hozir tekshirish” tugmasini bosing yoki filtrni kengaytiring'}</div></div></div>`;
            document.getElementById('summary').textContent = `Jami: ${fmt.number(res.total)} ta nomzod`;
            pagination(document.getElementById('pagination'), res.current_page, res.last_page, (n) => { load(n); scrollTo({ top: 0, behavior: 'smooth' }); });
        } catch (e) {
            list.innerHTML = `<div class="card"><div class="state">${icon('alert')}<div class="title">${esc(e.message)}</div></div></div>`;
        }
    }

    async function setStatus(c, value) {
        try {
            await api(`/hh/candidates/${c.id}`, { method: 'PATCH', body: { status: value } });
            c.status = value;
            return true;
        } catch (e) {
            toast(e.message, 'error');
            return false;
        }
    }

    list.addEventListener('click', async (e) => {
        const why = e.target.closest('[data-why]');
        if (why) {
            const box = why.nextElementSibling;
            box.hidden = !box.hidden;
            return;
        }

        const open = e.target.closest('[data-open]');
        if (open) {
            const c = items[+open.dataset.open];
            if (c.status === 'new') setStatus(c, 'viewed').then(() => load(page));
            return;
        }

        const st = e.target.closest('[data-status]');
        if (st) {
            const c = items[+st.dataset.i];
            if (await setStatus(c, st.dataset.status)) {
                toast(st.dataset.status === 'selected' ? 'Nomzod tanlanganlarga qo‘shildi' : 'Nomzod rad etildi', 'success');
                load(page);
            }
        }
    });

    document.getElementById('tabs').addEventListener('click', (e) => {
        const t = e.target.closest('[data-tab]');
        if (!t) return;
        status = t.dataset.tab;
        load(1);
    });
    document.getElementById('min-score').addEventListener('change', () => load(1));
    document.getElementById('sort').addEventListener('change', () => load(1));

    document.getElementById('run').addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        setLoading(btn, true);
        try {
            const r = await api(`/hh/searches/${searchId}/run`, { method: 'POST' });
            toast(`Yangi nomzodlar: ${fmt.number(r.new)}`, 'success');
            load(1);
        } catch (err) {
            toast(err.message, 'error');
        } finally {
            setLoading(btn, false);
        }
    });

    load(1);
});
</script>
@endpush

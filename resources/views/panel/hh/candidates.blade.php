@extends('panel.layout')

@section('title', __('Nomzodlar'))
@section('permission', 'hh.view')

@section('content')
    <div class="page-head">
        <div>
            <a href="{{ route('panel.hh', [], false) }}" class="muted" style="display:inline-flex;align-items:center;gap:4px">
                <svg class="icon"><use href="#i-chevron-left"></use></svg> {{ __('Qidiruvlar') }}
            </a>
            <h2 id="search-name" style="margin-top:6px">&nbsp;</h2>
            <p id="search-sub"></p>
        </div>
        <div class="row">
            <button class="btn secondary" id="run"><svg class="icon"><use href="#i-refresh"></use></svg> {{ __('Hozir tekshirish') }}</button>
            <a class="btn secondary" id="edit-link" href="#"><svg class="icon"><use href="#i-filter"></use></svg> {{ __('Filtr') }}</a>
        </div>
    </div>

    <div class="row" style="justify-content:space-between; margin-bottom:16px">
        <div class="tabs" id="tabs"></div>
        <div class="row">
            <select class="select" id="min-score" style="width:auto">
                <option value="">{{ __('Har qanday foiz') }}</option>
                <option value="50">{{ __(':n% dan yuqori', ['n' => 50]) }}</option>
                <option value="70">{{ __(':n% dan yuqori', ['n' => 70]) }}</option>
                <option value="85">{{ __(':n% dan yuqori', ['n' => 85]) }}</option>
            </select>
            <select class="select" id="sort" style="width:auto">
                <option value="score">{{ __('Moslik bo‘yicha') }}</option>
                <option value="date">{{ __('Yangilangan sana bo‘yicha') }}</option>
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
Panel.ready.then(async () => {
    const { api, esc, fmt, icon, toast, pagination, setLoading, t } = Panel;
    const searchId = location.pathname.match(/\/hh\/searches\/(\d+)/)[1];
    const list = document.getElementById('list');
    const STATUS = {
        '': t('Hammasi'),
        new: t('Yangi'),
        viewed: t('Ko‘rilgan'),
        selected: t('Tanlangan'),
        rejected: t('Rad etilgan'),
    };
    const LABELS = {
        title: t('Lavozim nomi'),
        positions: t('Oldingi lavozimlar'),
        experience: t('Tajriba'),
        salary: t('Maosh'),
        age: t('Yosh'),
        education: t('Ta’lim'),
        area: t('Hudud'),
        fresh: t('Yangilangan'),
    };
    let status = '';
    let items = [];
    let page = 1;

    // hh spravochniklari panel tilida — rezyumedagi id lar bo'yicha nom olamiz
    let dict = null;
    try { dict = await api('/hh/dictionaries'); } catch (e) { /* nomlar zaxiradan olinadi */ }
    const nameOf = (list, id, fallback) => (id && dict?.[list]?.find((x) => x.id === id)?.name) || fallback || '';

    document.getElementById('edit-link').href = `/panel/hh/searches/${searchId}/edit`;

    const ringClass = (s) => s >= 75 ? 'high' : s >= 50 ? 'mid' : 'low';
    const monthName = (d) => {
        if (!d) return t('hozirgacha');
        const [y, m] = d.split('-');
        return `${m}.${y}`;
    };
    const months = (n) => {
        if (n === null || n === undefined) return null;
        const y = Math.floor(n / 12), m = n % 12;
        return [y ? t(':n yil', { n: y }) : '', m ? t(':n oy', { n: m }) : ''].filter(Boolean).join(' ') || t('1 oydan kam');
    };

    // Ball izohi: scorer kod + qiymat saqlaydi, matnni shu yerda tanlangan tilda yasaymiz
    function noteText(d) {
        const a = d.args || {};
        if (!d.args && d.label) return d.note; // eski formatdagi yozuvlar
        const unknown = {
            experience: t('Tajriba ko‘rsatilmagan'), salary: t('Maosh ko‘rsatilmagan'), age: t('Yosh ko‘rsatilmagan'),
            education: t('Ta’lim ko‘rsatilmagan'), fresh: '—',
        };
        const money = () => `${fmt.number(a.amount)} ${a.currency}`;
        switch (d.note) {
            case 'title_hits': return t('Sarlavhada: :terms', { terms: a.terms });
            case 'title_none': return t('Kalit so‘zlar sarlavhada yo‘q');
            case 'pos_recent': return t('Oxirgi ish joyida ham mos');
            case 'pos_earlier': return t('Avvalgi ish joylarida mos');
            case 'pos_none': return t('Ish tajribasida uchramadi');
            case 'months': return months(a.months);
            case 'salary': return money();
            case 'salary_over': return money() + ' — ' + t('byudjetdan yuqori');
            case 'salary_low': return money() + ' — ' + t('kutilganidan past');
            case 'age': return t(':n yosh', { n: a.age });
            case 'education': return nameOf('education_level', a.level, a.level);
            case 'area': return nameOf('areas', a.area_id, a.name);
            case 'area_relocate': return nameOf('areas', a.area_id, a.name) + ' (' + t('ko‘chib o‘tishga tayyor') + ')';
            case 'days': return a.days === 0 ? t('Bugun') : t(':n kun oldin', { n: a.days });
            case 'unknown': return unknown[d.key] || '—';
            default: return d.note || '';
        }
    }

    function renderTabs(counts) {
        const total = Object.values(counts || {}).reduce((a, b) => a + Number(b), 0);
        document.getElementById('tabs').innerHTML = Object.entries(STATUS).map(([key, label]) => `
            <button type="button" data-tab="${key}" class="${key === status ? 'active' : ''}">${label}<span class="count">${fmt.number(key ? (counts?.[key] || 0) : total)}</span></button>`).join('');
    }

    function card(c, i) {
        const r = c.resume;
        const gender = nameOf('gender', r.gender_id, r.gender);
        const area = nameOf('areas', r.area_id, r.area);
        const meta = [
            r.age ? `<span>${esc(t(':n yosh', { n: r.age }))}${gender ? ', ' + esc(gender.toLowerCase()) : ''}</span>` : (gender ? `<span>${esc(gender)}</span>` : ''),
            area ? `<span>${icon('map-pin')}${esc(area)}</span>` : '',
            months(r.experience_months) ? `<span>${icon('briefcase')}${t('Tajriba')}: ${esc(months(r.experience_months))}</span>` : `<span>${t('Tajriba yo‘q')}</span>`,
            r.salary ? `<span class="salary">${esc(fmt.number(r.salary.amount))} ${esc(r.salary.currency)}</span>` : `<span>${t('Maosh ko‘rsatilmagan')}</span>`,
        ].filter(Boolean).join('');

        const jobs = r.jobs.length ? `<ul class="jobs">${r.jobs.map((j) => `
            <li><span class="period">${esc(monthName(j.start))} – ${esc(monthName(j.end))}</span>
            <span><strong>${esc(j.position || '—')}</strong>${j.company ? ' · ' + esc(j.company) : ''}</span></li>`).join('')}</ul>` : '';

        const eduLevel = nameOf('education_level', r.education.level_id, r.education.level);
        const edu = eduLevel ? `<div class="edu">${icon('graduation')}${esc(eduLevel)}${r.education.place ? ' · ' + esc(r.education.place) : ''}${r.education.year ? ' (' + r.education.year + ')' : ''}</div>` : '';

        const breakdown = (c.details || []).map((d) => `
            <div class="item">
                <span>${esc(LABELS[d.key] || d.label || d.key)}</span>
                <span class="bar"><i style="width:${Math.round(d.score * 100)}%"></i></span>
                <span class="muted">${Math.round(d.score * 100)}%</span>
                <span class="why-note">${esc(noteText(d))}</span>
            </div>`).join('');

        const statusBadge = {
            new: `<span class="badge primary">${t('Yangi')}</span>`,
            viewed: `<span class="badge">${t('Ko‘rilgan')}</span>`,
            selected: `<span class="badge green">${t('Tanlangan')}</span>`,
            rejected: `<span class="badge red">${t('Rad etilgan')}</span>`,
        }[c.status];
        const searchStatus = nameOf('job_search_status', r.job_search_status_id, r.job_search_status);

        return `
        <div class="card candidate ${c.status === 'rejected' ? 'rejected' : ''}">
            <div class="score-ring ${ringClass(c.score)}" style="--p:${c.score}" title="${t('Moslik')}"><b>${c.score}<small>%</small></b></div>
            <div style="min-width:0">
                <div class="row" style="gap:8px">
                    <a class="title" href="${esc(r.url || '#')}" target="_blank" rel="noopener" data-open="${i}">${esc(r.title || t('Lavozim ko‘rsatilmagan'))}</a>
                    ${statusBadge}
                    ${searchStatus ? `<span class="badge amber">${esc(searchStatus)}</span>` : ''}
                </div>
                <div class="meta">${meta}</div>
                ${jobs}
                ${edu}
                <div class="why">
                    <button type="button" class="why-toggle" data-why="${i}">${t('Nega :n%?', { n: c.score })}</button>
                    <div class="breakdown" hidden>${breakdown}</div>
                </div>
            </div>
            <div class="side">
                <a class="btn" href="${esc(r.url || '#')}" target="_blank" rel="noopener" data-open="${i}">${icon('external')} ${t('hh.uz da ochish')}</a>
                <button class="btn secondary" data-status="selected" data-i="${i}" ${c.status === 'selected' ? 'disabled' : ''}>${icon('thumbs-up')} ${t('Tanlash')}</button>
                <button class="btn ghost" data-status="rejected" data-i="${i}" ${c.status === 'rejected' ? 'disabled' : ''}>${icon('thumbs-down')} ${t('Rad etish')}</button>
                <div class="cell-sub" style="text-align:center">${t('Yangilangan')}: ${esc(fmt.date(r.updated_at))}</div>
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
                s.last_found !== null ? esc(t('hh.uz da hozir :n ta mos rezyume', { n: fmt.number(s.last_found) })) : '',
                s.last_run_at ? esc(t('oxirgi tekshiruv :date', { date: fmt.dateTime(s.last_run_at) })) : esc(t('hali tekshirilmagan')),
                s.is_active ? esc(t('har soatda yangilanadi')) : `<span class="badge">${t('To‘xtatilgan')}</span>`,
                s.last_error ? `<span class="badge red" title="${esc(s.last_error)}">${t('Oxirgi tekshiruvda xato')}</span>` : '',
            ].filter(Boolean).join(' · ');
            renderTabs(res.counts);

            items = res.data;
            list.innerHTML = items.length ? items.map(card).join('') : `<div class="card"><div class="state">${icon('briefcase')}
                <div class="title">${status ? t('Bu bo‘limda nomzod yo‘q') : t('Hali nomzodlar yo‘q')}</div>
                <div>${status ? t('Boshqa bo‘limni tanlang') : t('“Hozir tekshirish” tugmasini bosing yoki filtrni kengaytiring')}</div></div></div>`;
            document.getElementById('summary').textContent = t('Jami: :n ta nomzod', { n: fmt.number(res.total) });
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
                toast(st.dataset.status === 'selected' ? t('Nomzod tanlanganlarga qo‘shildi') : t('Nomzod rad etildi'), 'success');
                load(page);
            }
        }
    });

    document.getElementById('tabs').addEventListener('click', (e) => {
        const el = e.target.closest('[data-tab]');
        if (!el) return;
        status = el.dataset.tab;
        load(1);
    });
    document.getElementById('min-score').addEventListener('change', () => load(1));
    document.getElementById('sort').addEventListener('change', () => load(1));

    document.getElementById('run').addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        setLoading(btn, true);
        try {
            const r = await api(`/hh/searches/${searchId}/run`, { method: 'POST' });
            toast(t('Yangi nomzodlar: :n', { n: fmt.number(r.new) }), 'success');
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

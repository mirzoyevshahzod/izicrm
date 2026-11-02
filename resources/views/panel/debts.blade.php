@extends('panel.layout')

@section('title', 'Qarzdorlik')
@section('permission', 'debts.view')

@section('content')
    <div class="grid stats">
        <div class="card stat">
            <span class="stat-icon red"><svg class="icon"><use href="#i-wallet"></use></svg></span>
            <div><div class="stat-label">Jami summa</div><div class="stat-value" id="s-total">—</div><div class="stat-sub">Tanlangan filtr bo‘yicha</div></div>
        </div>
        <div class="card stat">
            <span class="stat-icon"><svg class="icon"><use href="#i-file-text"></use></svg></span>
            <div><div class="stat-label">Yozuvlar</div><div class="stat-value" id="s-count">—</div></div>
        </div>
    </div>

    <div class="card">
        <div class="toolbar">
            <label class="field grow">
                <span>Qidiruv</span>
                <div class="input-icon">
                    <svg class="icon"><use href="#i-search"></use></svg>
                    <input class="input" id="f-name" type="search" placeholder="Xodim yoki kompaniya nomi">
                </div>
            </label>
            <label class="field">
                <span>Davr</span>
                <select class="select" id="f-period">
                    <option value="">Barcha vaqt</option>
                    <option value="7">Oxirgi 7 kun</option>
                    <option value="30">Oxirgi 30 kun</option>
                    <option value="90">Oxirgi 3 oy</option>
                    <option value="365">Oxirgi 1 yil</option>
                </select>
            </label>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    <th>Kompaniya</th>
                    <th>Xodim</th>
                    <th class="num">Summa</th>
                    <th>Sabab</th>
                    <th>Ilovalar</th>
                    <th>Sana</th>
                    <th></th>
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
@endsection

@push('modals')
    <div class="modal" id="detail-modal">
        <div class="modal-dialog lg" role="dialog" aria-modal="true" aria-labelledby="detail-title">
            <div class="modal-head">
                <div>
                    <h3 id="detail-title"></h3>
                    <div class="cell-sub" id="detail-sub"></div>
                </div>
                <button class="btn ghost icon-only" data-close aria-label="Yopish"><svg class="icon"><use href="#i-x"></use></svg></button>
            </div>
            <div class="modal-body" id="detail-body"></div>
        </div>
    </div>
@endpush

@push('scripts')
<script>
Panel.ready.then(() => {
    const { api, esc, fmt, icon, pagination, debounce, openModal } = Panel;
    const rows = document.getElementById('rows');
    const fName = document.getElementById('f-name');
    const fPeriod = document.getElementById('f-period');
    let items = [];

    const iso = (d) => d.toISOString().slice(0, 10);

    function filters() {
        const q = { employee_name: fName.value.trim() };
        if (fPeriod.value) {
            const from = new Date();
            from.setDate(from.getDate() - Number(fPeriod.value));
            Object.assign(q, { from: iso(from), to: iso(new Date()) });
        }
        return q;
    }

    async function load(page = 1) {
        rows.innerHTML = `<tr><td colspan="7"><div class="skeleton"></div></td></tr>`.repeat(5);
        try {
            const res = await api('/debts/search', { query: { ...filters(), page } });
            items = res.data;
            document.getElementById('s-total').textContent = fmt.money(res.total_amount);
            document.getElementById('s-count').textContent = fmt.number(res.total);
            document.getElementById('summary').textContent = `Jami: ${fmt.number(res.total)} ta yozuv`;

            if (!items.length) {
                rows.innerHTML = `<tr><td colspan="7"><div class="state">${icon('search')}<div class="title">Hech narsa topilmadi</div></div></td></tr>`;
            } else {
                rows.innerHTML = items.map((d, i) => {
                    const r = d.reasons;
                    const text = r.texts[0]?.text;
                    return `
                    <tr>
                        <td><div class="cell-main">${esc(d.company_name || '—')}</div></td>
                        <td>${esc(d.employee_name || '—')}</td>
                        <td class="num"><strong>${esc(fmt.money(d.total_amount))}</strong></td>
                        <td><div class="truncate" title="${esc(text || '')}">${text ? esc(text) : '<span class="muted">Izoh yo‘q</span>'}</div></td>
                        <td>
                            <div class="badges">
                                ${r.texts.length > 1 ? `<span class="badge">${icon('file-text')} ${r.texts.length}</span>` : ''}
                                ${r.photos.length ? `<span class="badge primary">${icon('image')} ${r.photos.length}</span>` : ''}
                                ${r.voices.length ? `<span class="badge green">${icon('mic')} ${r.voices.length}</span>` : ''}
                                ${!r.photos.length && !r.voices.length && r.texts.length <= 1 ? '<span class="muted">—</span>' : ''}
                            </div>
                        </td>
                        <td class="nowrap muted">${esc(fmt.dateTime(d.updated_at || d.created_at))}</td>
                        <td class="actions"><button class="btn secondary sm" data-detail="${i}">Batafsil</button></td>
                    </tr>`;
                }).join('');
            }
            pagination(document.getElementById('pagination'), res.current_page, res.last_page, load);
        } catch (e) {
            rows.innerHTML = `<tr><td colspan="7"><div class="state">${icon('alert')}<div class="title">${esc(e.message)}</div></div></td></tr>`;
        }
    }

    function showDetail(d) {
        const r = d.reasons;
        document.getElementById('detail-title').textContent = d.employee_name || '—';
        document.getElementById('detail-sub').textContent = `${d.company_name || ''} · ${fmt.money(d.total_amount)}`;

        const media = (path) => '/storage/' + String(path).split('/').map(encodeURIComponent).join('/');
        const section = (title, html) => `<div class="detail-section"><h4>${title}</h4>${html}</div>`;
        let html = '';
        html += section('Izohlar', r.texts.length
            ? r.texts.map((t) => `<div class="note">${esc(t.text)}<div class="cell-sub">${esc(fmt.dateTime(t.created_at))}</div></div>`).join('')
            : '<div class="muted">Matnli izoh yo‘q</div>');
        if (r.photos.length) {
            html += section('Rasmlar', `<div class="media-grid">${r.photos.map((p) =>
                `<a href="${media(p.file)}" target="_blank" rel="noopener"><img src="${media(p.file)}" alt="Rasm" loading="lazy"></a>`).join('')}</div>`);
        }
        if (r.voices.length) {
            html += section('Ovozli xabarlar', r.voices.map((v) =>
                `<div class="note"><audio controls preload="none" src="${media(v.file)}"></audio><div class="cell-sub">${esc(fmt.dateTime(v.created_at))}</div></div>`).join(''));
        }
        document.getElementById('detail-body').innerHTML = html;
        openModal('detail-modal');
    }

    rows.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-detail]');
        if (btn) showDetail(items[+btn.dataset.detail]);
    });
    fName.addEventListener('input', debounce(() => load(1)));
    fPeriod.addEventListener('change', () => load(1));

    load(1);
});
</script>
@endpush

@extends('panel.layout')

@section('title', 'Davomat')
@section('permission', 'attendance.view')

@section('content')
    <div class="card">
        <div class="toolbar">
            <label class="field grow">
                <span>Xodim <span class="hint">— bir nechtasini vergul bilan</span></span>
                <div class="input-icon">
                    <svg class="icon"><use href="#i-search"></use></svg>
                    <input class="input" id="f-fio" type="search" placeholder="Ism yoki familiya">
                </div>
            </label>
            <label class="field">
                <span>Kun</span>
                <input class="input" id="f-date" type="date">
            </label>
            <label class="field">
                <span>Oy</span>
                <input class="input" id="f-month" type="month">
            </label>
            <button class="btn ghost" id="f-reset" type="button">Tozalash</button>
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
                    <th>Xodim</th>
                    <th>Kechikish sababi</th>
                    <th>Kechikish</th>
                    <th>Sana</th>
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

@push('scripts')
<script>
Panel.ready.then(() => {
    const { api, esc, fmt, icon, pagination, debounce, download, toast, setLoading } = Panel;
    const rows = document.getElementById('rows');
    const f = { fio: document.getElementById('f-fio'), date: document.getElementById('f-date'), month: document.getElementById('f-month') };
    let page = 1;

    function filters() {
        const q = { fio: f.fio.value.trim() };
        if (f.date.value) {
            const [y, m, d] = f.date.value.split('-');
            Object.assign(q, { year: +y, month: +m, day: +d });
        } else if (f.month.value) {
            const [y, m] = f.month.value.split('-');
            Object.assign(q, { year: +y, month: +m });
        }
        return q;
    }

    function lateBadge(min) {
        const cls = min >= 60 ? 'red' : min >= 15 ? 'amber' : 'primary';
        return `<span class="badge ${cls}">${esc(fmt.minutes(min))}</span>`;
    }

    async function load(p = 1) {
        page = p;
        rows.innerHTML = `<tr><td colspan="5"><div class="skeleton"></div></td></tr>`.repeat(5);
        try {
            const { data } = await api('/attendances/search', { query: { ...filters(), page } });
            if (!data.data.length) {
                rows.innerHTML = `<tr><td colspan="5"><div class="state">${icon('search')}<div class="title">Hech narsa topilmadi</div><div>Filtrlarni o‘zgartirib ko‘ring</div></div></td></tr>`;
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
            document.getElementById('summary').textContent = `Jami: ${fmt.number(data.total)} ta yozuv`;
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
            await download('/attendances/export', filters(), 'davomat.xlsx');
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

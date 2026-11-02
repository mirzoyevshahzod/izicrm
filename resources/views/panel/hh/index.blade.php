@extends('panel.layout')

@section('title', 'HH nomzodlar')
@section('permission', 'hh.view')

@section('content')
    <div class="page-head">
        <div>
            <h2>Qidiruvlar</h2>
            <p>Har bir qidiruv hh.uz dan har soatda yangi rezyumelarni olib, moslik foizi bilan saralab beradi.</p>
        </div>
        <a class="btn" href="{{ route('panel.hh.create', [], false) }}"><svg class="icon"><use href="#i-plus"></use></svg> Yangi qidiruv</a>
    </div>

    <div id="notice"></div>
    <div class="search-grid" id="list"></div>
@endsection

@push('scripts')
<script>
Panel.ready.then(async () => {
    const { api, esc, fmt, icon, toast, confirm, setLoading } = Panel;
    const list = document.getElementById('list');
    let searches = [];
    let dict = null;

    const ago = (v) => {
        if (!v) return 'hali ishga tushmagan';
        const min = Math.round((Date.now() - new Date(v)) / 60000);
        if (min < 1) return 'hozirgina';
        if (min < 60) return `${min} daqiqa oldin`;
        if (min < 1440) return `${Math.round(min / 60)} soat oldin`;
        return fmt.dateTime(v);
    };

    function summary(p) {
        const chips = [];
        (p.keywords || []).forEach((k) => chips.push((k.logic === 'except' ? '− ' : '') + k.text));
        const areas = (p.area || []).map((id) => dict?.areas.find((a) => a.id === id)?.name).filter(Boolean);
        chips.push(areas.length ? areas.slice(0, 2).join(', ') + (areas.length > 2 ? ` +${areas.length - 2}` : '') : 'Butun O‘zbekiston');
        (p.experience || []).forEach((e) => chips.push(dict?.experience.find((x) => x.id === e)?.name || e));
        if (p.salary_to) chips.push('≤ ' + fmt.number(p.salary_to) + ' ' + (p.currency || ''));
        if (p.age_from || p.age_to) chips.push(`${p.age_from || '…'}–${p.age_to || '…'} yosh`);
        (p.skill || []).slice(0, 3).forEach((s) => chips.push(s.text));
        return chips.map((c) => `<span class="chip plain">${esc(c)}</span>`).join('');
    }

    function render() {
        if (!searches.length) {
            list.innerHTML = `<div class="card" style="grid-column:1/-1"><div class="state">${icon('search')}
                <div class="title">Hali qidiruv yo‘q</div>
                <div>“Yangi qidiruv” tugmasini bosib, hh.uz dagi kabi filtr yarating.</div></div></div>`;
            return;
        }
        list.innerHTML = searches.map((s, i) => `
            <div class="card search-card">
                <div class="card-body">
                    <div class="head">
                        <div>
                            <h3>${esc(s.name)}</h3>
                            <div class="cell-sub">${s.last_error
                                ? `<span class="badge red" title="${esc(s.last_error)}">${icon('alert')} Xato</span> `
                                : ''}Oxirgi tekshiruv: ${esc(ago(s.last_run_at))}</div>
                        </div>
                        <label class="switch" title="Har soatda avtomatik tekshirish">
                            <input type="checkbox" data-toggle="${i}" ${s.is_active ? 'checked' : ''}><span class="track"></span>
                        </label>
                    </div>
                    <div class="chips">${summary(s.params)}</div>
                    <div class="mini-stats">
                        <div><div class="v">${fmt.number(s.new_count)}</div><div class="l">Yangi</div></div>
                        <div><div class="v">${fmt.number(s.selected_count)}</div><div class="l">Tanlangan</div></div>
                        <div><div class="v">${fmt.number(s.results_count)}</div><div class="l">Jami</div></div>
                    </div>
                </div>
                <div class="foot">
                    <a class="btn" href="/panel/hh/searches/${s.id}">Nomzodlar</a>
                    <button class="btn secondary icon-only" data-run="${i}" title="Hozir tekshirish" aria-label="Hozir tekshirish">${icon('refresh')}</button>
                    <a class="btn secondary icon-only" href="/panel/hh/searches/${s.id}/edit" title="Tahrirlash" aria-label="Tahrirlash">${icon('edit')}</a>
                    <button class="btn secondary icon-only" data-delete="${i}" title="O‘chirish" aria-label="O‘chirish">${icon('trash')}</button>
                </div>
            </div>`).join('');
    }

    async function load() {
        list.innerHTML = '<div class="card"><div class="card-body"><div class="skeleton"></div></div></div>'.repeat(2);
        try {
            const [res, d] = await Promise.all([api('/hh/searches'), dict ? Promise.resolve(dict) : api('/hh/dictionaries')]);
            dict = d;
            searches = res.searches;
            document.getElementById('notice').innerHTML = res.connected ? '' :
                `<div class="notice">${icon('alert')}<div><strong>hh.uz ulanmagan.</strong> Serverda <code>php artisan hh:auth-url</code> bilan ulang — ungacha qidiruvlar ishlamaydi.</div></div>`;
            render();
        } catch (e) {
            list.innerHTML = `<div class="card" style="grid-column:1/-1"><div class="state">${icon('alert')}<div class="title">${esc(e.message)}</div></div></div>`;
        }
    }

    list.addEventListener('change', async (e) => {
        const t = e.target.closest('[data-toggle]');
        if (!t) return;
        const s = searches[+t.dataset.toggle];
        try {
            await api(`/hh/searches/${s.id}`, { method: 'PUT', body: { name: s.name, params: s.params, is_active: t.checked } });
            s.is_active = t.checked;
            toast(t.checked ? 'Avtomatik tekshirish yoqildi' : 'Qidiruv to‘xtatildi', 'success');
        } catch (err) {
            t.checked = !t.checked;
            toast(err.message, 'error');
        }
    });

    list.addEventListener('click', async (e) => {
        const run = e.target.closest('[data-run]');
        if (run) {
            const s = searches[+run.dataset.run];
            setLoading(run, true);
            try {
                const r = await api(`/hh/searches/${s.id}/run`, { method: 'POST' });
                toast(`hh.uz da ${fmt.number(r.found)} ta, yangi nomzodlar: ${fmt.number(r.new)}`, 'success');
                load();
            } catch (err) {
                toast(err.message, 'error');
                setLoading(run, false);
            }
            return;
        }

        const del = e.target.closest('[data-delete]');
        if (del) {
            const s = searches[+del.dataset.delete];
            const ok = await confirm(`"${s.name}" va unga tegishli nomzodlar ro‘yxati o‘chiriladi.`, { title: 'Qidiruvni o‘chirish', okText: 'O‘chirish', danger: true });
            if (!ok) return;
            try {
                await api(`/hh/searches/${s.id}`, { method: 'DELETE' });
                toast('Qidiruv o‘chirildi', 'success');
                load();
            } catch (err) {
                toast(err.message, 'error');
            }
        }
    });

    load();
});
</script>
@endpush

@extends('panel.layout')

@section('title', __('HH nomzodlar'))
@section('permission', 'hh.view')

@section('content')
    <div class="page-head">
        <div>
            <h2>{{ __('Qidiruvlar') }}</h2>
            <p>{{ __('Har bir qidiruv hh.uz dan har soatda yangi rezyumelarni olib, moslik foizi bilan saralab beradi.') }}</p>
        </div>
        <a class="btn" href="{{ route('panel.hh.create', [], false) }}"><svg class="icon"><use href="#i-plus"></use></svg> {{ __('Yangi qidiruv') }}</a>
    </div>

    <div id="notice"></div>
    <div class="search-grid" id="list"></div>

    <div class="card" id="bot-card" style="margin-top:24px" hidden>
        <div class="card-header">
            <h2>{{ __('Telegram xabarnomalar') }}</h2>
            <button class="btn secondary sm" id="bot-test"><svg class="icon"><use href="#i-check"></use></svg> {{ __('Sinov xabari') }}</button>
        </div>
        <div class="card-body" id="bot-body"></div>
    </div>
@endsection

@push('scripts')
<script>
Panel.ready.then(async () => {
    const { api, esc, fmt, icon, toast, confirm, setLoading, t } = Panel;
    const list = document.getElementById('list');
    let searches = [];
    let dict = null;

    const ago = (v) => {
        if (!v) return t('hali ishga tushmagan');
        const min = Math.round((Date.now() - new Date(v)) / 60000);
        if (min < 1) return t('hozirgina');
        if (min < 60) return t(':n daqiqa oldin', { n: min });
        if (min < 1440) return t(':n soat oldin', { n: Math.round(min / 60) });
        return fmt.dateTime(v);
    };

    function summary(p) {
        const chips = [];
        (p.keywords || []).forEach((k) => chips.push((k.logic === 'except' ? '− ' : '') + k.text));
        const areas = (p.area || []).map((id) => dict?.areas.find((a) => a.id === id)?.name).filter(Boolean);
        chips.push(areas.length ? areas.slice(0, 2).join(', ') + (areas.length > 2 ? ` +${areas.length - 2}` : '') : t('Butun O‘zbekiston'));
        (p.experience || []).forEach((e) => chips.push(dict?.experience.find((x) => x.id === e)?.name || e));
        if (p.salary_to) chips.push('≤ ' + fmt.number(p.salary_to) + ' ' + (p.currency || ''));
        if (p.age_from || p.age_to) chips.push(t(':from–:to yosh', { from: p.age_from || '…', to: p.age_to || '…' }));
        (p.skill || []).slice(0, 3).forEach((s) => chips.push(s.text));
        return chips.map((c) => `<span class="chip plain">${esc(c)}</span>`).join('');
    }

    function render() {
        if (!searches.length) {
            list.innerHTML = `<div class="card" style="grid-column:1/-1"><div class="state">${icon('search')}
                <div class="title">${t('Hali qidiruv yo‘q')}</div>
                <div>${t('“Yangi qidiruv” tugmasini bosib, hh.uz dagi kabi filtr yarating.')}</div></div></div>`;
            return;
        }
        list.innerHTML = searches.map((s, i) => `
            <div class="card search-card">
                <div class="card-body">
                    <div class="head">
                        <div>
                            <h3>${esc(s.name)}</h3>
                            <div class="cell-sub">${s.last_error
                                ? `<span class="badge red" title="${esc(s.last_error)}">${icon('alert')} ${t('Xato')}</span> `
                                : ''}${t('Oxirgi tekshiruv')}: ${esc(ago(s.last_run_at))}</div>
                        </div>
                        <label class="switch" title="${t('Har soatda avtomatik tekshirish')}">
                            <input type="checkbox" data-toggle="${i}" ${s.is_active ? 'checked' : ''}><span class="track"></span>
                        </label>
                    </div>
                    <div class="chips">${summary(s.params)}</div>
                    <div class="mini-stats">
                        <div><div class="v">${fmt.number(s.new_count)}</div><div class="l">${t('Yangi')}</div></div>
                        <div><div class="v">${fmt.number(s.selected_count)}</div><div class="l">${t('Tanlangan')}</div></div>
                        <div><div class="v">${fmt.number(s.results_count)}</div><div class="l">${t('Jami')}</div></div>
                    </div>
                </div>
                <div class="foot">
                    <a class="btn" href="/panel/hh/searches/${s.id}">${t('Nomzodlar')}</a>
                    <button class="btn secondary icon-only" data-run="${i}" title="${t('Hozir tekshirish')}" aria-label="${t('Hozir tekshirish')}">${icon('refresh')}</button>
                    <a class="btn secondary icon-only" href="/panel/hh/searches/${s.id}/edit" title="${t('Tahrirlash')}" aria-label="${t('Tahrirlash')}">${icon('edit')}</a>
                    <button class="btn secondary icon-only" data-delete="${i}" title="${t('O‘chirish')}" aria-label="${t('O‘chirish')}">${icon('trash')}</button>
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
                `<div class="notice">${icon('alert')}<div><strong>${t('hh.uz ulanmagan.')}</strong> ${t('Serverda :cmd bilan ulang — ungacha qidiruvlar ishlamaydi.', { cmd: '<code>php artisan hh:auth-url</code>' })}</div></div>`;
            render();
        } catch (e) {
            list.innerHTML = `<div class="card" style="grid-column:1/-1"><div class="state">${icon('alert')}<div class="title">${esc(e.message)}</div></div></div>`;
        }
    }

    list.addEventListener('change', async (e) => {
        const el = e.target.closest('[data-toggle]');
        if (!el) return;
        const s = searches[+el.dataset.toggle];
        try {
            await api(`/hh/searches/${s.id}`, { method: 'PUT', body: { name: s.name, params: s.params, is_active: el.checked } });
            s.is_active = el.checked;
            toast(el.checked ? t('Avtomatik tekshirish yoqildi') : t('Qidiruv to‘xtatildi'), 'success');
        } catch (err) {
            el.checked = !el.checked;
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
                toast(t('hh.uz da :found ta, yangi nomzodlar: :new', { found: fmt.number(r.found), new: fmt.number(r.new) }), 'success');
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
            const ok = await confirm(t('":name" va unga tegishli nomzodlar ro‘yxati o‘chiriladi.', { name: s.name }), { title: t('Qidiruvni o‘chirish'), okText: t('O‘chirish'), danger: true });
            if (!ok) return;
            try {
                await api(`/hh/searches/${s.id}`, { method: 'DELETE' });
                toast(t('Qidiruv o‘chirildi'), 'success');
                load();
            } catch (err) {
                toast(err.message, 'error');
            }
        }
    });

    // ---------- Telegram: kim xabar oladi ----------
    const botBody = document.getElementById('bot-body');
    let botChats = [];

    async function loadBot() {
        let res;
        try { res = await api('/hh/bot/chats'); } catch (e) { return; }
        document.getElementById('bot-card').hidden = false;
        document.getElementById('bot-test').hidden = !res.configured;
        if (!res.configured) {
            botBody.innerHTML = `<div class="muted">${t('Bot sozlanmagan: serverdagi .env ga TELEGRAM_HH_BOT_TOKEN qo‘shing.')}</div>`;
            return;
        }
        botChats = res.chats;
        const bot = res.bot ? `<a href="https://t.me/${esc(res.bot)}" target="_blank" rel="noopener">@${esc(res.bot)}</a>` : 'HH bot';
        const rows = botChats.map((c, i) => `
            <div class="row" style="justify-content:space-between; padding:12px 0; border-bottom:1px solid var(--border)">
                <div class="user-cell">
                    <span class="avatar">${icon(c.type === 'private' ? 'user' : 'users')}</span>
                    <div>
                        <div class="cell-main">${esc(c.type === 'private' ? (c.user?.name || c.title || '—') : (c.title || t('Guruh')))}</div>
                        <div class="cell-sub">${c.type === 'private'
                            ? t('Shaxsiy chat') + (c.username ? ' · @' + esc(c.username) : '')
                            : t('Guruh')}</div>
                    </div>
                </div>
                <div class="row">
                    <label class="switch" title="${t('Xabar yuborish')}"><input type="checkbox" data-chat-toggle="${i}" ${c.is_active ? 'checked' : ''}><span class="track"></span></label>
                    <button class="btn ghost icon-only" data-chat-delete="${i}" aria-label="${t('O‘chirish')}" title="${t('O‘chirish')}">${icon('trash')}</button>
                </div>
            </div>`).join('');
        botBody.innerHTML = (rows || `<div class="muted" style="padding-bottom:12px">${t('Hali hech kim ulanmagan.')}</div>`) + `
            <div class="cell-sub" style="margin-top:14px; display:grid; gap:4px">
                <div>• ${t('HR lar: panelda Profil → Telegram’ni ulash.')}</div>
                <div>• ${t('Guruh: :bot ni guruhga qo‘shing, u shu ro‘yxatda paydo bo‘ladi — keyin yoqing.', { bot })}</div>
            </div>`;
    }

    botBody.addEventListener('change', async (e) => {
        const el = e.target.closest('[data-chat-toggle]');
        if (!el) return;
        const c = botChats[+el.dataset.chatToggle];
        try {
            await api(`/hh/bot/chats/${c.id}`, { method: 'PATCH', body: { is_active: el.checked } });
            c.is_active = el.checked;
        } catch (err) {
            el.checked = !el.checked;
            toast(err.message, 'error');
        }
    });
    botBody.addEventListener('click', async (e) => {
        const del = e.target.closest('[data-chat-delete]');
        if (!del) return;
        const c = botChats[+del.dataset.chatDelete];
        if (!await confirm(t('Bu chatga xabarlar boshqa yuborilmaydi.'), { title: t('Chatni o‘chirish'), okText: t('O‘chirish'), danger: true })) return;
        try {
            await api(`/hh/bot/chats/${c.id}`, { method: 'DELETE' });
            loadBot();
        } catch (err) { toast(err.message, 'error'); }
    });
    document.getElementById('bot-test').addEventListener('click', async (e) => {
        const btn = e.currentTarget;
        setLoading(btn, true);
        try {
            const r = await api('/hh/bot/test', { method: 'POST' });
            toast(t('Sinov xabari yuborildi: :sent / :total', { sent: r.sent, total: r.total }), r.sent ? 'success' : 'error');
        } catch (err) { toast(err.message, 'error'); }
        finally { setLoading(btn, false); }
    });

    load();
    loadBot();
});
</script>
@endpush

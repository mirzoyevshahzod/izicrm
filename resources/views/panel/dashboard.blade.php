@extends('panel.layout')

@section('title', __('Bosh sahifa'))

@section('content')
    <div class="hero">
        <h2>{{ __('Assalomu alaykum') }}, <span id="greet-name"></span> 👋</h2>
        <p id="today"></p>
        <svg class="icon hero-icon"><use href="#i-home"></use></svg>
    </div>

    <div class="grid stats" id="stats"></div>

    <div class="card">
        <div class="card-header"><h2>{{ __('Bo‘limlar') }}</h2></div>
        <div class="card-body">
            <div class="grid quick-links">
                <a class="card quick-link" href="{{ route('panel.attendance', [], false) }}" data-permission="attendance.view" hidden>
                    <span class="stat-icon amber"><svg class="icon"><use href="#i-clock"></use></svg></span>
                    <div><div class="title">{{ __('Davomat') }}</div><div class="desc">{{ __('Kunlik holat, kechikishlar, sabablar') }}</div></div>
                    <svg class="icon arrow"><use href="#i-arrow-right"></use></svg>
                </a>
                <a class="card quick-link" href="{{ route('panel.debts', [], false) }}" data-permission="debts.view" hidden>
                    <span class="stat-icon red"><svg class="icon"><use href="#i-wallet"></use></svg></span>
                    <div><div class="title">{{ __('Qarzdorlik') }}</div><div class="desc">{{ __('Qarzlar va xodim izohlari') }}</div></div>
                    <svg class="icon arrow"><use href="#i-arrow-right"></use></svg>
                </a>
                <a class="card quick-link" href="{{ route('panel.hh', [], false) }}" data-permission="hh.view" hidden>
                    <span class="stat-icon"><svg class="icon"><use href="#i-briefcase"></use></svg></span>
                    <div><div class="title">{{ __('HH nomzodlar') }}</div><div class="desc">{{ __('hh.uz dan mos rezyumelar') }}</div></div>
                    <svg class="icon arrow"><use href="#i-arrow-right"></use></svg>
                </a>
                <a class="card quick-link" href="{{ route('panel.users', [], false) }}" data-permission="users.manage" hidden>
                    <span class="stat-icon"><svg class="icon"><use href="#i-users"></use></svg></span>
                    <div><div class="title">{{ __('Foydalanuvchilar') }}</div><div class="desc">{{ __('Panelga kirish va ruxsatlar') }}</div></div>
                    <svg class="icon arrow"><use href="#i-arrow-right"></use></svg>
                </a>
                <a class="card quick-link" href="{{ route('panel.profile', [], false) }}">
                    <span class="stat-icon green"><svg class="icon"><use href="#i-user"></use></svg></span>
                    <div><div class="title">{{ __('Profil') }}</div><div class="desc">{{ __('Ma’lumotlar va parol') }}</div></div>
                    <svg class="icon arrow"><use href="#i-arrow-right"></use></svg>
                </a>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
Panel.ready.then(async (user) => {
    const { api, fmt, esc, icon, t } = Panel;
    document.getElementById('greet-name').textContent = user.name.split(' ')[0];
    document.getElementById('today').textContent = fmt.longDate();

    const box = document.getElementById('stats');
    const card = (iconName, color, label, value, sub) => `
        <div class="card stat">
            <span class="stat-icon ${color}">${icon(iconName)}</span>
            <div>
                <div class="stat-label">${esc(label)}</div>
                <div class="stat-value">${esc(value)}</div>
                ${sub ? `<div class="stat-sub">${esc(sub)}</div>` : ''}
            </div>
        </div>`;

    try {
        const s = await api('/dashboard');
        let html = '';
        if (s.attendance) {
            html += card('clock', 'amber', t('Bugun kechikkanlar'), fmt.number(s.attendance.today),
                s.attendance.today ? t('Jami :v', { v: fmt.minutes(s.attendance.today_minutes) }) : t('Hozircha kechikish yo‘q'));
            html += card('calendar', '', t('Shu oyda kechikishlar'), fmt.number(s.attendance.month));
        }
        if (s.debts) {
            html += card('wallet', 'red', t('Qarzdorlik summasi'), fmt.money(s.debts.total), t(':n ta yozuv', { n: fmt.number(s.debts.count) }));
        }
        if (s.hh) {
            html += card('briefcase', '', t('Yangi nomzodlar (hh.uz)'), fmt.number(s.hh.new), t(':n ta tanlangan', { n: fmt.number(s.hh.selected) }));
        }
        if (s.users) {
            html += card('users', 'green', t('Panel foydalanuvchilari'), fmt.number(s.users.count));
        }
        box.innerHTML = html;
        box.hidden = !html;
    } catch (e) {
        box.hidden = true;
    }
});
</script>
@endpush

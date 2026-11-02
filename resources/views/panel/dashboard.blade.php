@extends('panel.layout')

@section('title', 'Bosh sahifa')

@section('content')
    <div class="page-head">
        <div>
            <h2>Assalomu alaykum, <span id="greet-name"></span></h2>
            <p id="today"></p>
        </div>
    </div>

    <div class="grid stats" id="stats"></div>

    <div class="card">
        <div class="card-header"><h2>Bo‘limlar</h2></div>
        <div class="card-body">
            <div class="grid quick-links">
                <a class="card quick-link" href="{{ route('panel.attendance') }}" data-permission="attendance.view" hidden>
                    <span class="stat-icon amber"><svg class="icon"><use href="#i-clock"></use></svg></span>
                    <div><div class="title">Davomat</div><div class="desc">Kechikishlar, sabablar, Excel</div></div>
                    <svg class="icon arrow"><use href="#i-arrow-right"></use></svg>
                </a>
                <a class="card quick-link" href="{{ route('panel.debts') }}" data-permission="debts.view" hidden>
                    <span class="stat-icon red"><svg class="icon"><use href="#i-wallet"></use></svg></span>
                    <div><div class="title">Qarzdorlik</div><div class="desc">Qarzlar va xodim izohlari</div></div>
                    <svg class="icon arrow"><use href="#i-arrow-right"></use></svg>
                </a>
                <a class="card quick-link" href="{{ route('panel.users') }}" data-permission="users.manage" hidden>
                    <span class="stat-icon"><svg class="icon"><use href="#i-users"></use></svg></span>
                    <div><div class="title">Foydalanuvchilar</div><div class="desc">Panelga kirish va ruxsatlar</div></div>
                    <svg class="icon arrow"><use href="#i-arrow-right"></use></svg>
                </a>
                <a class="card quick-link" href="{{ route('panel.profile') }}">
                    <span class="stat-icon green"><svg class="icon"><use href="#i-user"></use></svg></span>
                    <div><div class="title">Profil</div><div class="desc">Ma’lumotlar va parol</div></div>
                    <svg class="icon arrow"><use href="#i-arrow-right"></use></svg>
                </a>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
Panel.ready.then(async (user) => {
    const { api, fmt, esc, icon } = Panel;
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
            html += card('clock', 'amber', 'Bugun kechikkanlar', fmt.number(s.attendance.today),
                s.attendance.today ? 'Jami ' + fmt.minutes(s.attendance.today_minutes) : 'Hozircha kechikish yo‘q');
            html += card('calendar', '', 'Shu oyda kechikishlar', fmt.number(s.attendance.month));
        }
        if (s.debts) {
            html += card('wallet', 'red', 'Qarzdorlik summasi', fmt.money(s.debts.total), fmt.number(s.debts.count) + ' ta yozuv');
        }
        if (s.users) {
            html += card('users', 'green', 'Panel foydalanuvchilari', fmt.number(s.users.count));
        }
        box.innerHTML = html;
        box.hidden = !html;
    } catch (e) {
        box.hidden = true;
    }
});
</script>
@endpush

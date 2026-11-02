@extends('panel.layout')

@section('title', __('Profil'))

@section('content')
    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); align-items: start">
        <div class="card">
            <div class="card-body" style="display:flex; gap:16px; align-items:center">
                <span class="avatar" style="width:56px;height:56px;font-size:18px" data-user-initials></span>
                <div style="min-width:0">
                    <div class="cell-main" style="font-size:17px" data-user-name></div>
                    <div class="muted" id="p-email"></div>
                </div>
            </div>
            <div class="card-body" style="border-top:1px solid var(--border); display:grid; gap:14px">
                <div><div class="stat-label">{{ __('Telefon') }}</div><div id="p-phone">—</div></div>
                <div><div class="stat-label">{{ __('Kirish huquqi') }}</div><div class="badges" id="p-access" style="margin-top:4px"></div></div>
                <div><div class="stat-label">{{ __('Ro‘yxatdan o‘tgan') }}</div><div id="p-created">—</div></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2>{{ __('Parolni o‘zgartirish') }}</h2></div>
            <form class="card-body" id="password-form" style="display:grid; gap:14px" novalidate>
                <div class="alert" id="pw-alert" hidden></div>
                <label class="field"><span>{{ __('Joriy parol') }}</span><input class="input" type="password" name="current_password" autocomplete="current-password" required></label>
                <label class="field"><span>{{ __('Yangi parol') }} <span class="hint">— {{ __('kamida 8 belgi') }}</span></span><input class="input" type="password" name="password" autocomplete="new-password" minlength="8" required></label>
                <label class="field"><span>{{ __('Yangi parolni takrorlang') }}</span><input class="input" type="password" name="password_confirmation" autocomplete="new-password" required></label>
                <div><button class="btn" type="submit"><svg class="icon"><use href="#i-key"></use></svg> {{ __('Parolni yangilash') }}</button></div>
                <p class="muted" style="margin:0; font-size:12px">{{ __('Parol yangilangach, boshqa qurilmalardagi sessiyalar yopiladi.') }}</p>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
Panel.ready.then((user) => {
    const { api, esc, fmt, icon, setLoading, showFieldErrors, toast, t } = Panel;
    const labels = { 'attendance.view': t('Davomat'), 'debts.view': t('Qarzdorlik'), 'hh.view': t('HH nomzodlar'), 'users.manage': t('Foydalanuvchilar') };

    document.getElementById('p-email').textContent = user.email;
    document.getElementById('p-phone').textContent = user.phone_number || '—';
    document.getElementById('p-created').textContent = fmt.date(user.created_at);
    document.getElementById('p-access').innerHTML = user.is_admin
        ? `<span class="badge primary">${icon('shield')} ${t('Administrator')}</span>`
        : (user.permissions.map((p) => `<span class="badge">${esc(labels[p] || p)}</span>`).join('') || '<span class="muted">—</span>');

    const form = document.getElementById('password-form');
    const alertBox = document.getElementById('pw-alert');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        alertBox.hidden = true;
        showFieldErrors(form, {});

        const btn = form.querySelector('button[type=submit]');
        setLoading(btn, true);
        try {
            const res = await api('/me/password', {
                method: 'PUT',
                body: {
                    current_password: form.elements.current_password.value,
                    password: form.elements.password.value,
                    password_confirmation: form.elements.password_confirmation.value,
                },
            });
            form.reset();
            toast(res.message, 'success');
        } catch (err) {
            showFieldErrors(form, err.errors);
            if (!Object.keys(err.errors).length) {
                alertBox.textContent = err.message;
                alertBox.hidden = false;
            }
        } finally {
            setLoading(btn, false);
        }
    });
});
</script>
@endpush

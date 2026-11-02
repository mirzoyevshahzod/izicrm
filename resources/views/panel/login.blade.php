{{-- Nisbiy manzillar: sayt webhook.izicrm.uz orqali ochilganda Laravel Host'ni izicrm.uz deb ko'radi --}}
@php($asset = fn ($path) => '/' . $path . '?v=' . @filemtime(public_path($path)))
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Kirish') }} · IziCRM</title>
    <link rel="icon" href="/favicon.ico">
    <script>try{var t=localStorage.getItem('panel_theme');if(t)document.documentElement.dataset.theme=t;}catch(e){}</script>
    <link rel="stylesheet" href="{{ $asset('assets/panel/panel.css') }}">
    @include('panel.i18n')
</head>
<body>
@include('panel.icons')

<div class="auth-theme">@include('panel.lang-switch')<button class="btn ghost icon-only theme-toggle" data-theme-toggle aria-label="{{ __('Kun / tun rejimi') }}" title="{{ __('Kun / tun rejimi') }}">
        <svg class="icon i-moon"><use href="#i-moon"></use></svg>
        <svg class="icon i-sun"><use href="#i-sun"></use></svg>
    </button></div>

<div class="auth">
    <aside class="auth-aside">
        <div class="brand"><span class="brand-mark">iz</span> IziCRM</div>
        <div style="position:relative;z-index:1">
            <h2>{{ __('Kompaniya jarayonlari bitta panelda') }}</h2>
            <p>{{ __('Davomat, qarzdorlik va xodimlar bilan ishlash — Telegram botlar orqali yig‘ilgan ma’lumotlar shu yerda.') }}</p>
        </div>
        <div class="auth-features">
            <div><svg class="icon"><use href="#i-clock"></use></svg> {{ __('Kechikishlar va sabablar') }}</div>
            <div><svg class="icon"><use href="#i-wallet"></use></svg> {{ __('Qarzdorlik va izohlar') }}</div>
            <div><svg class="icon"><use href="#i-shield"></use></svg> {{ __('Har kimga faqat o‘z bo‘limi') }}</div>
        </div>
    </aside>

    <main class="auth-main">
        <div class="auth-card">
            <div class="brand auth-mobile-brand"><span class="brand-mark">iz</span> IziCRM</div>
            <h1>{{ __('Xush kelibsiz') }}</h1>
            <p>{{ __('Davom etish uchun hisobingizga kiring') }}</p>

            <form id="login-form" novalidate>
                <div class="alert" id="login-error" hidden></div>

                <label class="field">
                    <span>Email</span>
                    <input class="input" type="email" name="email" autocomplete="username" required autofocus placeholder="{{ __('siz@kompaniya.uz') }}">
                </label>

                <label class="field">
                    <span>{{ __('Parol') }}</span>
                    <div class="password-wrap">
                        <input class="input" type="password" name="password" autocomplete="current-password" required placeholder="••••••••">
                        <button type="button" class="btn ghost icon-only" id="toggle-password" aria-label="{{ __('Parolni ko‘rsatish') }}">
                            <svg class="icon"><use href="#i-eye"></use></svg>
                        </button>
                    </div>
                </label>

                <button class="btn block" type="submit">{{ __('Kirish') }}</button>
            </form>
        </div>
    </main>
</div>

<script src="{{ $asset('assets/panel/panel.js') }}"></script>
<script>
(function () {
    const { api, token, setLoading, t } = Panel;
    const params = new URLSearchParams(location.search);
    const next = params.get('next');
    // Faqat shu saytdagi sahifaga qaytaramiz
    const target = next && next.startsWith('/') && !next.startsWith('//') ? next : '/panel';

    // Token allaqachon bo'lsa — to'g'ridan-to'g'ri panelga
    if (token.get()) {
        api('/me').then(() => location.replace(target)).catch(() => token.clear());
    }

    const form = document.getElementById('login-form');
    const error = document.getElementById('login-error');
    const pass = form.elements.password;

    document.getElementById('toggle-password').addEventListener('click', (e) => {
        const show = pass.type === 'password';
        pass.type = show ? 'text' : 'password';
        e.currentTarget.querySelector('use').setAttribute('href', show ? '#i-eye-off' : '#i-eye');
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        error.hidden = true;

        const email = form.elements.email.value.trim();
        if (!email || !pass.value) {
            error.textContent = t('Email va parolni kiriting.');
            error.hidden = false;
            return;
        }

        const btn = form.querySelector('button[type=submit]');
        setLoading(btn, true);
        try {
            const data = await api('/login', { method: 'POST', body: { email, password: pass.value } });
            token.set(data.access_token);
            location.replace(target);
        } catch (err) {
            error.textContent = err.status === 401 || err.status === 422 ? t('Email yoki parol noto‘g‘ri.') : err.message;
            error.hidden = false;
            setLoading(btn, false);
        }
    });
})();
</script>
</body>
</html>

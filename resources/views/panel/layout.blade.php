{{-- Nisbiy manzillar: sayt webhook.izicrm.uz orqali ochilganda Laravel Host'ni izicrm.uz deb ko'radi --}}
@php($asset = fn ($path) => '/' . $path . '?v=' . @filemtime(public_path($path)))
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · IziCRM</title>
    <link rel="icon" href="/favicon.ico">
    <script>try{var t=localStorage.getItem('panel_theme');if(t)document.documentElement.dataset.theme=t;}catch(e){}</script>
    <link rel="stylesheet" href="{{ $asset('assets/panel/panel.css') }}">
    @include('panel.i18n')
</head>
<body class="panel booting" data-page-permission="@yield('permission')">
@include('panel.icons')

<div class="shell">
    <aside class="sidebar" id="sidebar">
        <div class="brand"><span class="brand-mark">iz</span> IziCRM</div>

        <nav class="nav" aria-label="{{ __('Asosiy menyu') }}">
            <a href="{{ route('panel', [], false) }}" class="nav-item @if(request()->routeIs('panel')) active @endif">
                <svg class="icon"><use href="#i-home"></use></svg> {{ __('Bosh sahifa') }}
            </a>

            <div class="nav-label">{{ __('Bo‘limlar') }}</div>
            <a href="{{ route('panel.attendance', [], false) }}" data-permission="attendance.view" hidden
               class="nav-item @if(request()->routeIs('panel.attendance')) active @endif">
                <svg class="icon"><use href="#i-clock"></use></svg> {{ __('Davomat') }}
            </a>
            <a href="{{ route('panel.debts', [], false) }}" data-permission="debts.view" hidden
               class="nav-item @if(request()->routeIs('panel.debts')) active @endif">
                <svg class="icon"><use href="#i-wallet"></use></svg> {{ __('Qarzdorlik') }}
            </a>

            <a href="{{ route('panel.hh', [], false) }}" data-permission="hh.view" hidden
               class="nav-item @if(request()->routeIs('panel.hh*')) active @endif">
                <svg class="icon"><use href="#i-briefcase"></use></svg> {{ __('HH nomzodlar') }}
            </a>

            <div class="nav-label" data-permission="users.manage" hidden>{{ __('Boshqaruv') }}</div>
            <a href="{{ route('panel.users', [], false) }}" data-permission="users.manage" hidden
               class="nav-item @if(request()->routeIs('panel.users')) active @endif">
                <svg class="icon"><use href="#i-users"></use></svg> {{ __('Foydalanuvchilar') }}
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <span class="avatar" data-user-initials></span>
                <div style="min-width:0">
                    <div class="name" data-user-name>&nbsp;</div>
                    <div class="role" data-user-role></div>
                </div>
            </div>
        </div>
    </aside>
    <div class="sidebar-backdrop"></div>

    <div class="main">
        <header class="topbar">
            <button class="btn ghost icon-only menu-toggle" aria-label="{{ __('Menyu') }}">
                <svg class="icon"><use href="#i-menu"></use></svg>
            </button>
            <h1>@yield('title')</h1>

            @include('panel.lang-switch')

            <button class="btn ghost icon-only theme-toggle" data-theme-toggle aria-label="{{ __('Kun / tun rejimi') }}" title="{{ __('Kun / tun rejimi') }}">
                <svg class="icon i-moon"><use href="#i-moon"></use></svg>
                <svg class="icon i-sun"><use href="#i-sun"></use></svg>
            </button>

            <div class="user-menu">
                <button id="user-menu-btn" aria-haspopup="true">
                    <span class="avatar" data-user-initials></span>
                    <span class="name" data-user-name></span>
                </button>
                <div class="dropdown" id="user-dropdown">
                    <a href="{{ route('panel.profile', [], false) }}"><svg class="icon"><use href="#i-user"></use></svg> {{ __('Profil') }}</a>
                    <button class="danger" data-logout><svg class="icon"><use href="#i-logout"></use></svg> {{ __('Chiqish') }}</button>
                </div>
            </div>
        </header>

        <main class="content">
            @yield('content')
        </main>
    </div>
</div>

@stack('modals')

<script src="{{ $asset('assets/panel/panel.js') }}"></script>
@stack('scripts')
</body>
</html>

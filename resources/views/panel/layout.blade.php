@php($asset = fn ($path) => asset($path) . '?v=' . @filemtime(public_path($path)))
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · IziCRM</title>
    <link rel="icon" href="/favicon.ico">
    <link rel="stylesheet" href="{{ $asset('assets/panel/panel.css') }}">
</head>
<body class="panel booting" data-page-permission="@yield('permission')">
@include('panel.icons')

<div class="shell">
    <aside class="sidebar" id="sidebar">
        <div class="brand"><span class="brand-mark">iz</span> IziCRM</div>

        <nav class="nav" aria-label="Asosiy menyu">
            <a href="{{ route('panel') }}" class="nav-item @if(request()->routeIs('panel')) active @endif">
                <svg class="icon"><use href="#i-home"></use></svg> Bosh sahifa
            </a>

            <div class="nav-label">Bo‘limlar</div>
            <a href="{{ route('panel.attendance') }}" data-permission="attendance.view" hidden
               class="nav-item @if(request()->routeIs('panel.attendance')) active @endif">
                <svg class="icon"><use href="#i-clock"></use></svg> Davomat
            </a>
            <a href="{{ route('panel.debts') }}" data-permission="debts.view" hidden
               class="nav-item @if(request()->routeIs('panel.debts')) active @endif">
                <svg class="icon"><use href="#i-wallet"></use></svg> Qarzdorlik
            </a>

            <div class="nav-label" data-permission="users.manage" hidden>Boshqaruv</div>
            <a href="{{ route('panel.users') }}" data-permission="users.manage" hidden
               class="nav-item @if(request()->routeIs('panel.users')) active @endif">
                <svg class="icon"><use href="#i-users"></use></svg> Foydalanuvchilar
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
            <button class="btn ghost icon-only menu-toggle" aria-label="Menyu">
                <svg class="icon"><use href="#i-menu"></use></svg>
            </button>
            <h1>@yield('title')</h1>

            <div class="user-menu">
                <button id="user-menu-btn" aria-haspopup="true">
                    <span class="avatar" data-user-initials></span>
                    <span class="name" data-user-name></span>
                </button>
                <div class="dropdown" id="user-dropdown">
                    <a href="{{ route('panel.profile') }}"><svg class="icon"><use href="#i-user"></use></svg> Profil</a>
                    <button class="danger" data-logout><svg class="icon"><use href="#i-logout"></use></svg> Chiqish</button>
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

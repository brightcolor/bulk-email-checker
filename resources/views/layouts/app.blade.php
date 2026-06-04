<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">

<div class="app-wrapper">

    {{-- ── Navbar ─────────────────────────────────────────────────────────── --}}
    <nav class="app-header navbar navbar-expand bg-body">
        <div class="container-fluid">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                        <i class="bi bi-list"></i>
                    </a>
                </li>
            </ul>

            <div class="ms-2 fw-semibold text-muted small d-none d-sm-block">
                @if(app()->bound('current_tenant'))
                    <span class="badge bg-secondary me-1">{{ app('current_tenant')->name }}</span>
                @endif
            </div>

            <ul class="navbar-nav ms-auto">
                {{-- Tenant switch --}}
                @if(auth()->check())
                    @php $userTenants = auth()->user()->tenants()->get(); @endphp
                    @if($userTenants->count() > 1)
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#">
                            <i class="fas fa-building me-1"></i>
                            {{ app()->bound('current_tenant') ? app('current_tenant')->name : 'Switch' }}
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            @foreach($userTenants as $t)
                            <form method="POST" action="{{ route('tenant.switch') }}">
                                @csrf
                                <input type="hidden" name="tenant_id" value="{{ $t->id }}">
                                <button type="submit" class="dropdown-item @if(app()->bound('current_tenant') && app('current_tenant')->id === $t->id) active @endif">
                                    {{ $t->name }}
                                    <small class="text-muted ms-1">({{ $t->pivot->role }})</small>
                                </button>
                            </form>
                            @endforeach
                        </div>
                    </li>
                    @endif

                    {{-- User menu --}}
                    <li class="nav-item dropdown user-menu">
                        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                            <span class="d-none d-md-inline me-1">{{ auth()->user()->name }}</span>
                            <i class="fas fa-user-circle"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li class="dropdown-item dropdown-header">
                                <strong>{{ auth()->user()->name }}</strong><br>
                                <small class="text-muted">{{ auth()->user()->email }}</small>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="fas fa-sign-out-alt me-1"></i> Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                @endif
            </ul>
        </div>
    </nav>

    {{-- ── Sidebar ─────────────────────────────────────────────────────────── --}}
    <aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
        <div class="sidebar-brand">
            <a href="{{ route('dashboard') }}" class="brand-link">
                <i class="fas fa-envelope-open-text brand-image"></i>
                <span class="brand-text fw-semibold">{{ config('app.name') }}</span>
            </a>
        </div>

        <div class="sidebar-wrapper">
            <nav class="mt-2">
                <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu">

                    <li class="nav-item">
                        <a href="{{ route('dashboard') }}" class="nav-link @if(request()->routeIs('dashboard')) active @endif">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>

                    <li class="nav-item @if(request()->routeIs('bulk-jobs.*')) menu-open @endif">
                        <a href="#" class="nav-link @if(request()->routeIs('bulk-jobs.*')) active @endif">
                            <i class="nav-icon fas fa-tasks"></i>
                            <p>Bulk Checks <i class="nav-arrow fas fa-angle-right"></i></p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route('bulk-jobs.index') }}" class="nav-link @if(request()->routeIs('bulk-jobs.index')) active @endif">
                                    <i class="nav-icon far fa-circle"></i><p>Jobs</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('bulk-jobs.create') }}" class="nav-link @if(request()->routeIs('bulk-jobs.create')) active @endif">
                                    <i class="nav-icon far fa-circle"></i><p>New Upload</p>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('teams.index') }}" class="nav-link @if(request()->routeIs('teams.*')) active @endif">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Teams</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('members.index') }}" class="nav-link @if(request()->routeIs('members.*')) active @endif">
                            <i class="nav-icon fas fa-user-friends"></i>
                            <p>Members</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('invitations.index') }}" class="nav-link @if(request()->routeIs('invitations.*')) active @endif">
                            <i class="nav-icon fas fa-envelope"></i>
                            <p>Invitations</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('api-keys.index') }}" class="nav-link @if(request()->routeIs('api-keys.*')) active @endif">
                            <i class="nav-icon fas fa-key"></i>
                            <p>API Keys</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('settings.edit') }}" class="nav-link @if(request()->routeIs('settings.*')) active @endif">
                            <i class="nav-icon fas fa-cog"></i>
                            <p>Workspace Settings</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('audit-log.index') }}" class="nav-link @if(request()->routeIs('audit-log.*')) active @endif">
                            <i class="nav-icon fas fa-history"></i>
                            <p>Audit Log</p>
                        </a>
                    </li>

                    <li class="nav-header mt-2">ACCOUNT</li>

                    <li class="nav-item">
                        <a href="{{ route('tenant.select') }}" class="nav-link">
                            <i class="nav-icon fas fa-exchange-alt"></i>
                            <p>Switch Workspace</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="nav-link btn btn-link text-start w-100 border-0 bg-transparent">
                                <i class="nav-icon fas fa-sign-out-alt"></i>
                                <p>Logout</p>
                            </button>
                        </form>
                    </li>

                </ul>
            </nav>
        </div>
    </aside>

    {{-- ── Main Content ─────────────────────────────────────────────────────── --}}
    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-6">
                        <h3 class="mb-0">@yield('page-title', 'Dashboard')</h3>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-end">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                            @yield('breadcrumbs')
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <div class="app-content">
            <div class="container-fluid">

                {{-- Flash messages --}}
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible alert-autohide fade show" role="alert">
                        <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if(session('info'))
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="fas fa-info-circle me-1"></i> {{ session('info') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @yield('content')
            </div>
        </div>
    </main>

    <footer class="app-footer">
        <strong>{{ config('app.name') }}</strong> &copy; {{ date('Y') }}
        <div class="float-end d-none d-sm-inline-block">
            <small class="text-muted">Email verification results are indicative only.</small>
        </div>
    </footer>

</div>
</body>
</html>

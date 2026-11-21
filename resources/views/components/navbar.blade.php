{{-- navigasi utama kolabora (phase 3): satu header untuk semua peran, label bahasa indonesia (D-02).
     desktop mulai 1024px; di bawahnya menu mobile berisi navigasi, notifikasi, akun dan keluar. --}}
@php
    $user = auth()->user();

    // [label, route, pola routeIs untuk status aktif]
    $navItems = [];
    if ($user?->isStudent()) {
        $navItems = [
            ['Dashboard', 'student.dashboard', 'student.dashboard'],
            ['Telusuri Masalah', 'student.browse-problems.index', 'student.browse-problems.*'],
            ['Aplikasi Saya', 'student.applications.index', 'student.applications.*'],
            ['Proyek Saya', 'student.projects.index', 'student.projects.*'],
            ['Repositori', 'student.repository.index', 'student.repository.*'],
            ['Lowongan', 'student.jobs.index', 'student.jobs.*'],
        ];
    } elseif ($user?->isInstitution()) {
        $navItems = [
            ['Dashboard', 'institution.dashboard', 'institution.dashboard'],
            ['Masalah', 'institution.problems.index', 'institution.problems.*'],
            ['Aplikasi', 'institution.applications.index', 'institution.applications.*'],
            ['Proyek', 'institution.projects.index', 'institution.projects.*'],
            ['Ulasan', 'institution.reviews.index', 'institution.reviews.*'],
        ];
    } elseif ($user?->isCompany()) {
        $navItems = [
            ['Dashboard', 'company.dashboard', 'company.dashboard'],
            ['Talenta', 'company.talents.index', 'company.talents.index'],
            ['Lowongan', 'company.jobs.index', 'company.jobs.*'],
            ['Lamaran', 'company.applications.index', 'company.applications.*'],
            ['Talenta Tersimpan', 'company.talents.saved', 'company.talents.saved'],
        ];
    } elseif (! $user) {
        $navItems = [
            ['Tentang Kami', 'about', 'about'],
            ['Kontak', 'contact', 'contact'],
        ];
    }

    // menu akun: [label, route, pola aktif, ikon]
    $accountItems = [];
    if ($user?->isStudent()) {
        $accountItems = [
            ['Profil', 'student.profile.index', 'student.profile.*', 'user'],
            ['Wishlist', 'student.wishlist.index', 'student.wishlist.*', 'bookmark'],
            ['Jaringan', 'student.friends.index', 'student.friends.*', 'users'],
        ];
    } elseif ($user?->isInstitution()) {
        $accountItems = [['Profil', 'institution.profile.index', 'institution.profile.*', 'building-2']];
    } elseif ($user?->isCompany()) {
        $accountItems = [['Profil', 'company.profile.index', 'company.profile.*', 'briefcase']];
    }

    $role = match ($user?->user_type) {
        'student' => ['Mahasiswa', 'graduation-cap'],
        'institution' => ['Instansi', 'building-2'],
        'company' => ['Perusahaan', 'briefcase'],
        'admin' => ['Admin', 'shield-check'],
        default => null,
    };

    // beranda (AD-11): header transparan di atas video pembuka, padat setelah pembuka lewat (hero-video.js)
    $overHero = request()->routeIs('home');
@endphp

<header @class(['kb-header', 'kb-header--hero is-over-hero' => $overHero]) x-data="kbMobileNav" @keydown.escape="close(true)"
        @if ($overHero) data-kb-header :class="{ 'is-menu-open': open }" @endif>
    <div class="kb-container kb-header__bar">
        <a href="{{ route('home') }}" class="kb-header__brand" aria-label="Kolabora, ke beranda">
            <img class="kb-header__logo" src="{{ asset('assets/brand/kolabora-logo.svg') }}" alt="" width="99" height="40">
            @if ($overHero)
                <img class="kb-header__logo-on-dark" src="{{ asset('assets/brand/kolabora-logo-on-dark.svg') }}" alt="" width="99" height="40">
            @endif
        </a>

        @if ($navItems)
            <nav class="kb-nav" aria-label="Navigasi utama">
                <ul class="kb-nav__list">
                    @foreach ($navItems as [$label, $routeName, $pattern])
                        <li>
                            <a href="{{ route($routeName) }}" class="kb-nav__link" @if (request()->routeIs($pattern)) aria-current="page" @endif>{{ $label }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif

        <div class="kb-header__end">
            @auth
                {{-- notifikasi: satu lonceng untuk semua lebar layar --}}
                @include('components.notification-dropdown')

                {{-- menu akun (desktop) --}}
                <div class="kb-header__desktop">
                    <div class="kb-dropdown" x-data="kbDisclosure" @keydown.escape.stop="close(true)" @click.outside="close()" @focusout="onFocusOut($event)"
                         @keydown.arrow-down="move($event, 1)" @keydown.arrow-up="move($event, -1)">
                        <button type="button" class="kb-account__btn" x-ref="trigger" @click="toggle()"
                                aria-expanded="false" :aria-expanded="open.toString()" aria-controls="kb-account-menu"
                                aria-label="Menu akun {{ $user->display_name }}">
                            <x-ui.avatar :src="$user->profile_photo_url" :name="$user->display_name" size="sm" :square="! $user->isStudent()" decorative />
                            <span class="kb-account__name">{{ $user->display_name }}</span>
                            <x-icon name="chevron-down" size="sm" class="kb-chevron" />
                        </button>

                        <div id="kb-account-menu" class="kb-overlay kb-menu" x-ref="panel" x-show="open" x-cloak>
                            <div class="kb-menu__head">
                                <p class="kb-menu__title">{{ $user->display_name }}</p>
                                <p class="kb-menu__meta">{{ $user->email }}</p>
                                @if ($role)
                                    <x-ui.role-badge :role="$user->user_type" />
                                @endif
                            </div>
                            <ul class="kb-menu__list">
                                @foreach ($accountItems as [$label, $routeName, $pattern, $icon])
                                    <li><x-ui.menu-item :href="route($routeName)" :icon="$icon" :current="request()->routeIs($pattern)">{{ $label }}</x-ui.menu-item></li>
                                @endforeach
                                <li><x-ui.menu-item :href="route('notifications.index')" icon="bell" :current="request()->routeIs('notifications.*')">Notifikasi</x-ui.menu-item></li>
                            </ul>
                            <hr class="kb-menu__sep">
                            <x-ui.menu-item :href="route('logout')" method="post" icon="log-out" danger>Keluar</x-ui.menu-item>
                        </div>
                    </div>
                </div>
            @else
                <div class="kb-header__desktop">
                    <a href="{{ route('login') }}" class="kb-btn kb-btn--tertiary">Masuk</a>
                    <a href="{{ route('register') }}" class="kb-btn kb-btn--primary">Daftar</a>
                </div>
            @endauth

            <button type="button" class="kb-icon-btn kb-header__toggle" x-ref="toggle" @click="toggle()"
                    aria-expanded="false" :aria-expanded="open.toString()" aria-controls="kb-mobile-nav"
                    aria-label="Buka menu" :aria-label="open ? 'Tutup menu' : 'Buka menu'">
                <x-icon name="menu" x-show="! open" />
                <x-icon name="x" x-show="open" x-cloak />
            </button>
        </div>
    </div>

    {{-- menu mobile (< 1024px) --}}
    <div id="kb-mobile-nav" class="kb-mobile-nav" x-show="open" x-cloak>
        @auth
            <div class="kb-mobile-nav__account">
                <x-ui.avatar :src="$user->profile_photo_url" :name="$user->display_name" :square="! $user->isStudent()" decorative />
                <div class="min-w-0">
                    <p class="kb-menu__title">{{ $user->display_name }}</p>
                    <p class="kb-menu__meta">{{ $user->email }}</p>
                    @if ($role)
                        <x-ui.role-badge :role="$user->user_type" />
                    @endif
                </div>
            </div>
        @endauth

        @if ($navItems)
            <nav aria-label="Navigasi utama (seluler)">
                <ul class="kb-mobile-nav__section">
                    @foreach ($navItems as [$label, $routeName, $pattern])
                        <li>
                            <a href="{{ route($routeName) }}" class="kb-mobile-nav__link" @if (request()->routeIs($pattern)) aria-current="page" @endif>{{ $label }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        @endif

        @auth
            <ul class="kb-mobile-nav__section" aria-label="Akun">
                <li x-data="{ unread: 0 }" @kb-notifications.window="unread = $event.detail.unread">
                    <a href="{{ route('notifications.index') }}" class="kb-mobile-nav__link" @if (request()->routeIs('notifications.*')) aria-current="page" @endif>
                        <x-icon name="bell" />
                        <span>Notifikasi<span class="sr-only" x-text="unread > 0 ? ', ' + unread + ' belum dibaca' : ''"></span></span>
                        <span class="kb-count kb-count--inline" x-show="unread > 0" x-text="unread > 99 ? '99+' : unread" aria-hidden="true" x-cloak></span>
                    </a>
                </li>
                @foreach ($accountItems as [$label, $routeName, $pattern, $icon])
                    <li>
                        <a href="{{ route($routeName) }}" class="kb-mobile-nav__link" @if (request()->routeIs($pattern)) aria-current="page" @endif>
                            <x-icon :name="$icon" />
                            <span>{{ $label }}</span>
                        </a>
                    </li>
                @endforeach
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="kb-mobile-nav__link kb-mobile-nav__link--danger">
                            <x-icon name="log-out" />
                            <span>Keluar</span>
                        </button>
                    </form>
                </li>
            </ul>
        @else
            <div class="kb-mobile-nav__actions">
                <a href="{{ route('login') }}" class="kb-btn kb-btn--secondary kb-btn--lg">Masuk</a>
                <a href="{{ route('register') }}" class="kb-btn kb-btn--primary kb-btn--lg">Daftar</a>
            </div>
        @endauth
    </div>
</header>

{{-- components/notification-dropdown.blade.php (phase 3)
     endpoint sama seperti sebelumnya: notifications.latest (5 terbaru + unread_count, polling 30 detik),
     POST /notifications/{id}/read saat item diklik, lalu buka action_url bila ada, notifications.read-all.
     logika ada di Alpine.data('kbNotifications') -> resources/js/kolabora/alpine.js --}}
<div class="kb-dropdown"
     x-data="kbNotifications({
        latestUrl: @js(route('notifications.latest')),
        readAllUrl: @js(route('notifications.read-all')),
        readUrl: @js(url('/notifications/__ID__/read')),
        sprite: @js(asset('assets/icons/ui/sprite.svg')),
     })"
     @keydown.escape.stop="close(true)" @click.outside="close()" @focusout="onFocusOut($event)">
    <button type="button" class="kb-icon-btn" x-ref="trigger" @click="toggle()"
            aria-expanded="false" :aria-expanded="open.toString()" aria-controls="kb-notification-panel"
            aria-label="Notifikasi" :aria-label="label">
        <x-icon name="bell" />
        <span class="kb-count" x-show="unreadCount > 0" x-text="unreadCount > 99 ? '99+' : unreadCount" aria-hidden="true" x-cloak></span>
    </button>
    {{-- diumumkan pembaca layar hanya saat jumlah berubah --}}
    <span class="sr-only" aria-live="polite" x-text="liveText"></span>

    <div id="kb-notification-panel" class="kb-overlay kb-menu kb-notif" x-ref="panel" x-show="open" x-cloak
         role="region" aria-labelledby="kb-notification-title">
        <div class="kb-notif__head">
            <h2 id="kb-notification-title">Notifikasi</h2>
            <button type="button" class="kb-btn kb-btn--tertiary" x-show="unreadCount > 0" @click="markAllAsRead()">
                Tandai semua dibaca
            </button>
        </div>

        <template x-if="loaded && notifications.length === 0">
            <div class="kb-notif__empty">
                <img src="{{ asset('assets/illustrations/empty-states/no-notifications.svg') }}" alt="" width="96" height="72">
                <p>Belum ada notifikasi.</p>
            </div>
        </template>

        <ul class="kb-notif__list" x-show="notifications.length > 0">
            <template x-for="notification in notifications" :key="notification.id">
                <li>
                    <button type="button" class="kb-notif__item" :data-unread="notification.is_read ? null : ''"
                            @click="handleNotificationClick(notification)">
                        <svg class="kb-icon kb-notif__icon" aria-hidden="true" focusable="false"><use :href="icon(notification.type)"></use></svg>
                        <span>
                            <span class="kb-notif__title" x-text="notification.title"></span>
                            <span class="kb-notif__msg" x-text="notification.message"></span>
                            <span class="kb-notif__time" x-text="formatTime(notification.created_at)"></span>
                            <span class="sr-only" x-show="! notification.is_read">Belum dibaca. Pilih untuk menandai dibaca.</span>
                        </span>
                        <span class="kb-notif__dot" x-show="! notification.is_read" aria-hidden="true"></span>
                    </button>
                </li>
            </template>
        </ul>

        <div class="kb-notif__foot">
            <a href="{{ route('notifications.index') }}" class="kb-btn kb-btn--tertiary">Lihat semua notifikasi</a>
        </div>
    </div>
</div>

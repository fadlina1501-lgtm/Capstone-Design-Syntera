(function () {
    'use strict';

    const actionableSelector = 'a[href], button:not([disabled]), input[type="submit"], input[type="button"], [role="button"], [data-ripple]';

    // Add the bubble/ripple feedback at the exact pointer or touch location.
    document.addEventListener('click', function (event) {
        const origin = event.target instanceof Element ? event.target : event.target.parentElement;
        const target = origin ? origin.closest(actionableSelector) : null;
        if (!target || target.hasAttribute('disabled')) return;
        if (target.matches('a[href]') && target.getAttribute('href') === '#') event.preventDefault();

        target.classList.add('has-ripple');
        const rect = target.getBoundingClientRect();
        const x = Number.isFinite(event.clientX) && event.clientX !== 0 ? event.clientX - rect.left : rect.width / 2;
        const y = Number.isFinite(event.clientY) && event.clientY !== 0 ? event.clientY - rect.top : rect.height / 2;
        const diameter = Math.max(24, Math.ceil(Math.max(rect.width, rect.height) * 1.35));
        const bubble = document.createElement('span');
        bubble.className = 'click-bubble';
        bubble.setAttribute('aria-hidden', 'true');
        bubble.style.width = diameter + 'px';
        bubble.style.height = diameter + 'px';
        bubble.style.left = (x - diameter / 2) + 'px';
        bubble.style.top = (y - diameter / 2) + 'px';
        target.appendChild(bubble);
        window.setTimeout(function () { bubble.remove(); }, 700);
    }, true);

    // Demo account chips are functional: click a role to fill both login fields.
    document.addEventListener('click', function (event) {
        const origin = event.target instanceof Element ? event.target : event.target.parentElement;

        // Global notification bell: toggle popover and close it on outside clicks.
        const bell = origin ? origin.closest('[data-notification-bell]') : null;
        const notificationWidget = document.querySelector('.notification-widget');
        const popover = document.querySelector('[data-notification-popover]');
        if (bell && popover) {
            const willOpen = popover.hidden;
            popover.hidden = !willOpen;
            bell.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            return;
        }
        if (popover && notificationWidget && !notificationWidget.contains(origin)) {
            popover.hidden = true;
            const bellButton = notificationWidget.querySelector('[data-notification-bell]');
            if (bellButton) bellButton.setAttribute('aria-expanded', 'false');
        }

        // Demo account chips fill both inputs with the selected demo credentials.
        const demoButton = origin ? origin.closest('[data-demo-user]') : null;
        if (demoButton) {
            const usernameInput = document.getElementById('username');
            const passwordInput = document.getElementById('password');
            const feedback = document.getElementById('demo-feedback');
            if (usernameInput && passwordInput) {
                usernameInput.value = demoButton.dataset.demoUser || '';
                passwordInput.value = demoButton.dataset.demoPassword || '';
                document.querySelectorAll('[data-demo-user]').forEach(function (button) {
                    const selected = button === demoButton;
                    button.classList.toggle('is-selected', selected);
                    button.setAttribute('aria-pressed', selected ? 'true' : 'false');
                });
                if (feedback) feedback.textContent = 'Akun ' + demoButton.querySelector('strong').textContent + ' dipilih. Username dan password sudah diisi.';

                // Bawa formulir login ke layar agar pengguna langsung melihat nilai yang terisi.
                const loginForm = document.querySelector('.login-form');
                if (loginForm) {
                    loginForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    usernameInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                window.setTimeout(function () {
                    usernameInput.focus({ preventScroll: true });
                }, 350);
            }
            return;
        }

        const toggle = origin ? origin.closest('[data-password-toggle]') : null;
        if (toggle) {
            const input = document.getElementById(toggle.dataset.passwordToggle);
            if (!input) return;
            const revealing = input.type === 'password';
            input.type = revealing ? 'text' : 'password';
            toggle.setAttribute('aria-label', revealing ? 'Sembunyikan password' : 'Tampilkan password');
            const feedback = document.getElementById('demo-feedback');
            if (feedback) feedback.textContent = revealing ? 'Password ditampilkan.' : 'Password disembunyikan.';
            return;
        }

        const copyButton = origin ? origin.closest('[data-copy-text]') : null;
        if (copyButton) {
            const text = copyButton.dataset.copyText || '';
            const feedback = document.getElementById('demo-feedback');
            const finish = function () {
                const old = copyButton.textContent;
                copyButton.textContent = 'Tersalin ✓';
                if (feedback) feedback.textContent = 'Password demo berhasil disalin ke clipboard.';
                window.setTimeout(function () { copyButton.textContent = old; }, 1500);
            };
            const fail = function () {
                if (feedback) feedback.textContent = 'Clipboard tidak dapat diakses. Pilih dan salin password yang ditampilkan secara manual.';
                copyButton.textContent = 'Coba lagi';
                window.setTimeout(function () { copyButton.textContent = 'Salin'; }, 1500);
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(finish).catch(function () {
                    if (!fallbackCopy(text, finish)) fail();
                });
            } else if (!fallbackCopy(text, finish)) {
                fail();
            }
        }
    });

    // Poll unread notification count so the badge appears without reloading the page.
    const notificationBell = document.querySelector('[data-notification-bell]');
    if (notificationBell && notificationBell.dataset.unreadUrl) {
        let previousUnread = Number((document.querySelector('[data-notification-count]') || {}).textContent || 0);
        const countText = document.querySelector('[data-notification-unread-text]');

        function updateNotificationBadge(count) {
            count = Math.max(0, Number(count) || 0);
            let badge = notificationBell.querySelector('[data-notification-count]');
            if (count > 0 && !badge) {
                badge = document.createElement('span');
                badge.className = 'notification-badge';
                badge.setAttribute('data-notification-count', '');
                notificationBell.appendChild(badge);
            }
            if (badge) {
                if (count > 0) badge.textContent = count > 9 ? '9+' : String(count);
                else badge.remove();
            }
            notificationBell.setAttribute('aria-label', 'Notifikasi' + (count > 0 ? ', ' + count + ' belum dibaca' : ''));
            if (countText) countText.textContent = String(count);
            if (count > previousUnread) {
                notificationBell.classList.remove('notification-pulse');
                void notificationBell.offsetWidth;
                notificationBell.classList.add('notification-pulse');
                window.setTimeout(function () { notificationBell.classList.remove('notification-pulse'); }, 1000);
            }
            previousUnread = count;
        }

        function pollNotificationCount() {
            fetch(notificationBell.dataset.unreadUrl, {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (response) {
                if (!response.ok) throw new Error('Notifikasi tidak dapat diperbarui');
                return response.json();
            }).then(function (data) {
                updateNotificationBadge(data.unread_count);
            }).catch(function () {
                // Keep the server-rendered badge visible if polling temporarily fails.
            });
        }

        window.setTimeout(pollNotificationCount, 1500);
        window.setInterval(pollNotificationCount, 30000);
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            const popover = document.querySelector('[data-notification-popover]');
            const bell = document.querySelector('[data-notification-bell]');
            if (popover && !popover.hidden) {
                popover.hidden = true;
                if (bell) bell.setAttribute('aria-expanded', 'false');
                if (bell) bell.focus();
            }
        }
    });
    function fallbackCopy(text, done) {
        const temp = document.createElement('textarea');
        temp.value = text;
        temp.setAttribute('readonly', '');
        temp.style.position = 'fixed';
        temp.style.opacity = '0';
        document.body.appendChild(temp);
        temp.select();
        let copied = false;
        try { copied = document.execCommand('copy'); } catch (error) { copied = false; }
        temp.remove();
        if (copied && typeof done === 'function') done();
        return copied;
    }
})();

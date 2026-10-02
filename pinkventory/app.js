/* Pinkventory shared UI behaviour (all signed-in pages). Exposes window.PV. */
(function () {
    'use strict';

    var body = document.body;
    var iconsUrl = body.dataset.icons || 'icons.svg';

    function icon(name, extra) {
        return '<svg class="icon ' + (extra || '') + '" aria-hidden="true" focusable="false"><use href="' + iconsUrl + '#' + name + '"></use></svg>';
    }

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    /* ---------- Toasts ---------- */

    var toastIcons = { success: 'check-circle-2', error: 'alert-circle', info: 'info' };
    var toastBox = document.getElementById('toasts');

    function dismissToast(toast) {
        if (!toast || toast.classList.contains('is-leaving')) return;
        toast.classList.add('is-leaving');
        setTimeout(function () { toast.remove(); }, 220);
    }

    function armToast(toast) {
        var timer = setTimeout(function () { dismissToast(toast); }, toast.classList.contains('toast-error') ? 8000 : 5000);
        toast.addEventListener('mouseenter', function () { clearTimeout(timer); });
        toast.addEventListener('mouseleave', function () { timer = setTimeout(function () { dismissToast(toast); }, 2500); });
        var close = toast.querySelector('.toast-close');
        if (close) close.addEventListener('click', function () { dismissToast(toast); });
    }

    function toast(type, message) {
        if (!toastBox) return;
        type = toastIcons[type] ? type : 'info';
        var el = document.createElement('div');
        el.className = 'toast toast-' + type;
        el.setAttribute('role', type === 'error' ? 'alert' : 'status');
        el.innerHTML = icon(toastIcons[type]) + '<p></p><button type="button" class="toast-close" aria-label="Dismiss">' + icon('x', 'icon-sm') + '</button>';
        el.querySelector('p').textContent = message;
        toastBox.appendChild(el);
        armToast(el);
    }

    if (toastBox) toastBox.querySelectorAll('.toast').forEach(armToast);

    /* ---------- Confirm dialog ---------- */

    var confirmDialog = document.getElementById('confirmDialog');

    function confirmAction(options) {
        options = options || {};
        if (!confirmDialog || typeof confirmDialog.showModal !== 'function') {
            return Promise.resolve(window.confirm(options.message || 'Are you sure?'));
        }
        document.getElementById('confirmTitle').textContent = options.title || 'Are you sure?';
        document.getElementById('confirmMessage').textContent = options.message || '';
        document.getElementById('confirmOk').textContent = options.confirmLabel || 'Delete';
        confirmDialog.returnValue = '';
        confirmDialog.showModal();
        document.getElementById('confirmOk').focus();
        return new Promise(function (resolve) {
            confirmDialog.addEventListener('close', function onClose() {
                confirmDialog.removeEventListener('close', onClose);
                resolve(confirmDialog.returnValue === 'confirm');
            });
        });
    }

    /* ---------- Forms: confirmation + loading state ---------- */

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (form.method === 'dialog') return;

        if (form.dataset.confirm && !form.dataset.confirmed) {
            event.preventDefault();
            confirmAction({
                title: form.dataset.confirmTitle,
                message: form.dataset.confirm,
                confirmLabel: form.dataset.confirmLabel
            }).then(function (ok) {
                if (!ok) return;
                form.dataset.confirmed = '1';
                if (form.requestSubmit) form.requestSubmit(); else form.submit();
            });
            return;
        }

        if (form.dataset.submitting) {
            event.preventDefault();
            return;
        }
        form.dataset.submitting = '1';
        var button = event.submitter || form.querySelector('[type="submit"]');
        if (button && button.classList.contains('btn')) {
            button.classList.add('is-loading');
            button.setAttribute('aria-busy', 'true');
        }
    });

    // Restore buttons when the page is shown again from the back/forward cache.
    window.addEventListener('pageshow', function () {
        document.querySelectorAll('form[data-submitting]').forEach(function (form) {
            delete form.dataset.submitting;
            delete form.dataset.confirmed;
            form.querySelectorAll('.is-loading').forEach(function (b) { b.classList.remove('is-loading'); b.removeAttribute('aria-busy'); });
        });
    });

    /* ---------- Password visibility + rules ---------- */

    document.querySelectorAll('.toggle-pass').forEach(function (button) {
        button.addEventListener('click', function () {
            var input = button.parentElement.querySelector('input');
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.classList.toggle('is-shown', show);
            button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            button.setAttribute('aria-pressed', String(show));
        });
    });

    var rules = {
        length: function (v) { return v.length >= 8; },
        upper: function (v) { return /[A-Z]/.test(v); },
        number: function (v) { return /[0-9]/.test(v); },
        symbol: function (v) { return /[^A-Za-z0-9]/.test(v); }
    };
    document.querySelectorAll('[data-rules-for]').forEach(function (list) {
        var input = document.getElementById(list.dataset.rulesFor);
        if (!input) return;
        input.addEventListener('input', function () {
            list.querySelectorAll('li[data-rule]').forEach(function (li) {
                li.classList.toggle('met', rules[li.dataset.rule](input.value));
            });
        });
    });

    /* ---------- Mobile navigation drawer ---------- */

    var openBtn = document.querySelector('[data-nav-open]');

    function setNav(open) {
        body.classList.toggle('nav-open', open);
        if (openBtn) openBtn.setAttribute('aria-expanded', String(open));
        if (open) {
            var first = document.querySelector('#sidebar .nav-item');
            if (first) first.focus();
        } else if (openBtn && document.activeElement && document.getElementById('sidebar').contains(document.activeElement)) {
            openBtn.focus();
        }
    }

    if (openBtn) openBtn.addEventListener('click', function () { setNav(true); });
    document.querySelectorAll('[data-nav-close]').forEach(function (el) {
        el.addEventListener('click', function () { setNav(false); });
    });

    /* ---------- User menu ---------- */

    var trigger = document.querySelector('.user-trigger');
    var menu = document.getElementById('userMenu');

    function setMenu(open) {
        if (!menu) return;
        menu.hidden = !open;
        trigger.setAttribute('aria-expanded', String(open));
    }

    if (trigger) {
        trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            setMenu(menu.hidden);
        });
        document.addEventListener('click', function (e) {
            if (!menu.hidden && !menu.contains(e.target)) setMenu(false);
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            if (menu && !menu.hidden) { setMenu(false); trigger.focus(); }
            if (body.classList.contains('nav-open')) setNav(false);
        }
    });

    /* ---------- Global product search ---------- */

    var searchForm = document.querySelector('.global-search');
    if (searchForm) {
        var input = searchForm.querySelector('input');
        var list = searchForm.querySelector('.suggest');
        var spinner = searchForm.querySelector('.spinner');
        var active = -1;
        var latest = 0;
        var debounce;

        var closeList = function () {
            list.hidden = true;
            input.setAttribute('aria-expanded', 'false');
            active = -1;
        };

        var highlight = function (index) {
            var links = list.querySelectorAll('a');
            links.forEach(function (a, i) {
                a.classList.toggle('active', i === index);
                a.setAttribute('aria-selected', String(i === index));
            });
            active = index;
            if (links[index]) input.setAttribute('aria-activedescendant', links[index].id);
        };

        var render = function (items, query) {
            list.innerHTML = '';
            if (!items.length) {
                var empty = document.createElement('li');
                empty.className = 'suggest-empty';
                empty.textContent = 'No products match “' + query + '”';
                list.appendChild(empty);
            }
            items.slice(0, 6).forEach(function (name, i) {
                var li = document.createElement('li');
                var a = document.createElement('a');
                a.id = 'suggest-' + i;
                a.setAttribute('role', 'option');
                a.href = 'inventory.php?q=' + encodeURIComponent(name);
                a.innerHTML = icon('package', 'icon-sm');
                a.appendChild(document.createTextNode(name));
                li.appendChild(a);
                list.appendChild(li);
            });
            list.hidden = false;
            input.setAttribute('aria-expanded', 'true');
            active = -1;
        };

        input.addEventListener('input', function () {
            var query = input.value.trim();
            clearTimeout(debounce);
            if (query.length < 3) {
                closeList();
                spinner.hidden = true;
                return;
            }
            debounce = setTimeout(function () {
                var id = ++latest;
                spinner.hidden = false;
                fetch('search_api.php?q=' + encodeURIComponent(query), { credentials: 'same-origin' })
                    .then(function (r) { return r.ok ? r.json() : []; })
                    .then(function (items) { if (id === latest) render(items, query); })
                    .catch(closeList)
                    .finally(function () { if (id === latest) spinner.hidden = true; });
            }, 180);
        });

        input.addEventListener('keydown', function (e) {
            var links = list.querySelectorAll('a');
            if (list.hidden || !links.length) return;
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                highlight((active + 1) % links.length);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                highlight((active - 1 + links.length) % links.length);
            } else if (e.key === 'Enter' && active >= 0) {
                e.preventDefault();
                window.location.href = links[active].href;
            } else if (e.key === 'Escape') {
                closeList();
            }
        });

        document.addEventListener('click', function (e) {
            if (!searchForm.contains(e.target)) closeList();
        });

        // "/" focuses search from anywhere (except while typing in a field)
        document.addEventListener('keydown', function (e) {
            var tag = (e.target.tagName || '').toLowerCase();
            if (e.key === '/' && !e.ctrlKey && !e.metaKey && tag !== 'input' && tag !== 'textarea' && tag !== 'select' && !e.target.isContentEditable) {
                e.preventDefault();
                input.focus();
                input.select();
            }
        });
    }

    /* ---------- Inactivity sign-out ----------
       The server ends sessions after SESSION_IDLE_TIMEOUT seconds without a request.
       While the user is active we ping the server now and then so the session stays
       alive; after real inactivity we warn 60s before signing out. */

    var idleSeconds = parseInt(body.dataset.idleTimeout, 10);
    var logoutUrl = body.dataset.logoutUrl;
    var idleDialog = document.getElementById('idleDialog');

    if (idleSeconds && logoutUrl && idleDialog && typeof idleDialog.showModal === 'function') {
        var warnAfter = Math.max(idleSeconds - 60, 30) * 1000;
        var lastServerTouch = Date.now();
        var warnTimer, countdownTimer;

        var keepAlive = function () {
            lastServerTouch = Date.now();
            fetch('search_api.php', { credentials: 'same-origin' }).catch(function () {});
        };

        var startCountdown = function () {
            var left = 60;
            var out = document.getElementById('idleCountdown');
            out.textContent = left;
            idleDialog.showModal();
            countdownTimer = setInterval(function () {
                left -= 1;
                out.textContent = left;
                if (left <= 0) {
                    clearInterval(countdownTimer);
                    window.location.href = logoutUrl;
                }
            }, 1000);
        };

        var resetIdle = function () {
            if (idleDialog.open) return;
            clearTimeout(warnTimer);
            warnTimer = setTimeout(startCountdown, warnAfter);
            if (Date.now() - lastServerTouch > 5 * 60 * 1000) keepAlive();
        };

        idleDialog.addEventListener('close', function () {
            clearInterval(countdownTimer);
            if (idleDialog.returnValue === 'logout') {
                window.location.href = logoutUrl;
                return;
            }
            keepAlive();
            resetIdle();
        });

        ['pointerdown', 'keydown', 'scroll', 'mousemove'].forEach(function (evt) {
            document.addEventListener(evt, resetIdle, { passive: true });
        });
        resetIdle();
    }

    window.PV = { toast: toast, confirm: confirmAction, icon: icon, csrfToken: csrfToken };
})();

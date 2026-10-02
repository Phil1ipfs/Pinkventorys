// Sign in / sign up / first-admin pages.
document.addEventListener('DOMContentLoaded', function () {
    'use strict';

    // User / Admin switch
    var switches = document.querySelectorAll('.role-switch button');
    switches.forEach(function (button) {
        button.addEventListener('click', function () {
            var isAdmin = button.dataset.role === 'admin';
            switches.forEach(function (b) { b.setAttribute('aria-pressed', String(b === button)); });
            document.getElementById('userLoginForm').hidden = isAdmin;
            document.getElementById('adminLoginForm').hidden = !isAdmin;
            document.getElementById(isAdmin ? 'admin_username' : 'email').focus();
        });
    });

    // Show / hide password
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

    // Live password rules
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

    // "Passwords match" hint on sign up
    var repeat = document.getElementById('repeat_password');
    var hint = document.getElementById('matchHint');
    var password = document.getElementById('password');
    if (repeat && hint && password) {
        var check = function () {
            if (!repeat.value) {
                hint.textContent = '';
                hint.className = 'hint';
            } else if (repeat.value === password.value) {
                hint.textContent = 'Passwords match';
                hint.className = 'hint ok';
            } else {
                hint.textContent = 'Passwords do not match yet';
                hint.className = 'hint bad';
            }
        };
        password.addEventListener('input', check);
        repeat.addEventListener('input', check);
    }

    // Loading state on submit (prevents double submits)
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (form.dataset.submitting) {
                e.preventDefault();
                return;
            }
            form.dataset.submitting = '1';
            var button = form.querySelector('[type="submit"]');
            if (button) button.classList.add('is-loading');
        });
    });
    window.addEventListener('pageshow', function () {
        document.querySelectorAll('form[data-submitting]').forEach(function (form) {
            delete form.dataset.submitting;
            form.querySelectorAll('.is-loading').forEach(function (b) { b.classList.remove('is-loading'); });
        });
    });
});

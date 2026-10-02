// Users page: instant filter and delete (via delete_user.php, CSRF-protected).
(function () {
    'use strict';

    var table = document.getElementById('usersTable');
    if (!table) return;

    var filter = document.getElementById('userFilter');
    var count = document.getElementById('userCount');
    var noMatches = document.getElementById('noUserMatches');

    function rows() {
        return Array.prototype.slice.call(table.tBodies[0].rows);
    }

    function applyFilter() {
        var term = filter.value.trim().toLowerCase();
        var visible = 0;
        rows().forEach(function (row) {
            var show = !term || row.dataset.search.indexOf(term) !== -1;
            row.hidden = !show;
            if (show) visible++;
        });
        count.textContent = visible + (visible === 1 ? ' user' : ' users');
        noMatches.hidden = visible > 0;
        table.parentElement.hidden = visible === 0;
    }

    filter.addEventListener('input', applyFilter);

    table.addEventListener('click', function (e) {
        var button = e.target.closest('[data-delete-user]');
        if (!button) return;
        var id = button.dataset.deleteUser;
        var name = button.dataset.name;

        PV.confirm({
            title: 'Delete this user?',
            message: name + ' will lose access immediately. This can’t be undone.',
            confirmLabel: 'Delete user'
        }).then(function (ok) {
            if (!ok) return;
            button.classList.add('is-loading');
            var body = new URLSearchParams({ id: id, csrf_token: PV.csrfToken() });
            fetch('delete_user.php', { method: 'POST', body: body, credentials: 'same-origin' })
                .then(function (r) {
                    if (!r.ok) throw new Error(r.status);
                    var row = button.closest('tr');
                    row.remove();
                    applyFilter();
                    PV.toast('success', name + ' was deleted.');
                    if (!rows().length) window.location.reload();
                })
                .catch(function () {
                    button.classList.remove('is-loading');
                    PV.toast('error', 'Couldn’t delete ' + name + '. Please refresh and try again.');
                });
        });
    });
})();

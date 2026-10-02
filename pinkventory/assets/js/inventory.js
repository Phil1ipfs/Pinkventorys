// Inventory filters: apply the category filter as soon as it changes.
document.querySelectorAll('select[data-autosubmit]').forEach(function (select) {
    select.addEventListener('change', function () {
        if (select.form.requestSubmit) select.form.requestSubmit(); else select.form.submit();
    });
});

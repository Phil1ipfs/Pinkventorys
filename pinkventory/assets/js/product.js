// Product form: suggest a stock status from the quantity until the user picks one.
(function () {
    var form = document.querySelector('[data-product-form]');
    if (!form) return;
    var stock = form.querySelector('#stock');
    var hint = form.querySelector('#statusHint');
    var radios = form.querySelectorAll('input[name="status"]');
    var userPicked = false;
    var threshold = parseInt(form.dataset.lowThreshold || '10', 10);

    radios.forEach(function (radio) {
        radio.addEventListener('change', function (e) {
            if (e.isTrusted) {
                userPicked = true;
                hint.textContent = 'Status set manually.';
            }
        });
    });

    stock.addEventListener('input', function () {
        if (userPicked || stock.value === '') return;
        var qty = parseInt(stock.value, 10);
        if (isNaN(qty)) return;
        var value = qty <= 0 ? 'out-of-stock' : (qty <= threshold ? 'low-stock' : 'in-stock');
        radios.forEach(function (radio) { radio.checked = radio.value === value; });
        hint.textContent = 'Suggested from the quantity: ' + (qty <= 0 ? 'out of stock' : (qty <= threshold ? 'low stock' : 'in stock')) + '.';
    });
})();

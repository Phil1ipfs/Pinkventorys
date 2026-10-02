// Customer storefront: filters, quantity steppers, cart and checkout pages.
(function () {
    'use strict';

    var PV = window.PV;
    if (!PV) return;

    var money = function (n) {
        return '$' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    // Filters apply as soon as a select changes
    document.querySelectorAll('select[data-autosubmit]').forEach(function (select) {
        select.addEventListener('change', function () {
            if (select.form.requestSubmit) select.form.requestSubmit(); else select.form.submit();
        });
    });

    // Home: number of items in the cart
    document.querySelectorAll('[data-cart-total-count]').forEach(function (el) {
        el.textContent = PV.cart.count();
    });

    // Quantity steppers (product page)
    document.querySelectorAll('[data-stepper]').forEach(function (stepper) {
        var input = stepper.querySelector('input');
        var max = parseInt(input.max, 10) || 1;
        var clamp = function () {
            var v = parseInt(input.value, 10);
            input.value = Math.min(max, Math.max(1, isNaN(v) ? 1 : v));
        };
        stepper.querySelectorAll('[data-step]').forEach(function (button) {
            button.addEventListener('click', function () {
                input.value = (parseInt(input.value, 10) || 1) + parseInt(button.dataset.step, 10);
                clamp();
            });
        });
        input.addEventListener('change', clamp);
    });

    /* ---------- Cart & checkout ---------- */

    var page = document.querySelector('[data-cart-page]');
    if (!page) return;

    var isCheckout = page.hasAttribute('data-checkout');
    var taxRate = parseFloat(page.dataset.taxRate) || 0;
    var $ = function (sel) { return page.querySelector(sel); };
    var products = {};   // id -> current product data from the server

    function notice(type, text) {
        var box = $('[data-cart-notices]');
        var div = document.createElement('div');
        div.className = 'alert alert-' + (type === 'error' ? 'error' : 'info') + ' alert-inline';
        div.innerHTML = PV.icon(type === 'error' ? 'alert-circle' : 'info');
        var p = document.createElement('div');
        p.textContent = text;
        div.appendChild(p);
        box.appendChild(div);
    }

    // Bring the stored cart in line with current prices and stock.
    function reconcile(lines) {
        var kept = [];
        lines.forEach(function (line) {
            var p = products[line.id];
            if (!p) {
                notice('error', (line.name || 'A product') + ' is no longer available and was removed from your cart.');
                return;
            }
            if (p.level === 'out' || p.stock <= 0) {
                notice('error', p.name + ' is out of stock and was removed from your cart.');
                return;
            }
            if (line.qty > p.stock) {
                notice('info', 'Only ' + p.stock + ' × ' + p.name + ' available, so your quantity was updated.');
                line.qty = p.stock;
            }
            if (line.price !== undefined && Math.abs(line.price - p.price) > 0.001) {
                notice('info', 'The price of ' + p.name + ' changed from ' + money(line.price) + ' to ' + money(p.price) + '.');
            }
            line.name = p.name;
            line.price = p.price;
            kept.push(line);
        });
        PV.cart.write(kept);
        return kept;
    }

    function cell(label, cls) {
        var td = document.createElement('td');
        if (label) td.dataset.label = label;
        if (cls) td.className = cls;
        return td;
    }

    function art(p) {
        return '<span class="thumb tone-' + p.tone + '">' + PV.icon(p.icon) + '</span>';
    }

    function setQty(id, qty) {
        var lines = PV.cart.read();
        var line = lines.find(function (l) { return l.id === id; });
        if (!line) return;
        var p = products[id];
        line.qty = Math.min(p ? p.stock : line.qty, qty);
        if (line.qty <= 0) lines = lines.filter(function (l) { return l.id !== id; });
        PV.cart.write(lines);
        render();
    }

    function renderCartRows(lines) {
        var tbody = $('[data-cart-items]');
        tbody.innerHTML = '';
        lines.forEach(function (line) {
            var p = products[line.id];
            var tr = document.createElement('tr');

            var product = cell('', 'cell-first');
            product.innerHTML = '<div class="cell-main">' + art(p) + '<div><strong><a></a></strong><span></span></div></div>';
            var link = product.querySelector('a');
            link.href = 'product.php?id=' + p.id;
            link.textContent = p.name;
            product.querySelector('.cell-main > div > span').textContent = p.category + ' · ' + p.stock + ' available';

            var price = cell('Price', 'right num');
            price.textContent = money(p.price);

            var qty = cell('Quantity');
            qty.innerHTML = '<div class="qty" role="group">' +
                '<button type="button" data-act="dec">' + PV.icon('minus', 'icon-sm') + '</button>' +
                '<output></output>' +
                '<button type="button" data-act="inc">' + PV.icon('plus', 'icon-sm') + '</button></div>';
            qty.querySelector('.qty').setAttribute('aria-label', 'Quantity of ' + p.name);
            qty.querySelector('output').textContent = line.qty;
            var dec = qty.querySelector('[data-act="dec"]');
            var inc = qty.querySelector('[data-act="inc"]');
            dec.setAttribute('aria-label', 'Remove one ' + p.name);
            inc.setAttribute('aria-label', 'Add one ' + p.name);
            if (line.qty >= p.stock) {
                inc.disabled = true;
                inc.title = 'No more in stock';
            }
            dec.addEventListener('click', function () { setQty(p.id, line.qty - 1); });
            inc.addEventListener('click', function () { setQty(p.id, line.qty + 1); });

            var subtotal = cell('Subtotal', 'right num');
            subtotal.innerHTML = '<strong></strong>';
            subtotal.firstChild.textContent = money(p.price * line.qty);

            var actions = cell('', 'actions');
            actions.innerHTML = '<button type="button" class="btn btn-danger-soft btn-icon btn-sm">' + PV.icon('trash-2', 'icon-sm') + '</button>';
            actions.firstChild.setAttribute('aria-label', 'Remove ' + p.name);
            actions.firstChild.addEventListener('click', function () {
                setQty(p.id, 0);
                PV.toast('info', p.name + ' was removed from your cart.');
            });

            [product, price, qty, subtotal, actions].forEach(function (td) { tr.appendChild(td); });
            tbody.appendChild(tr);
        });
    }

    function renderCheckoutRows(lines) {
        var list = $('[data-checkout-items]');
        list.innerHTML = '';
        lines.forEach(function (line) {
            var p = products[line.id];
            var li = document.createElement('li');
            li.className = 'row-item';
            li.innerHTML = art(p) + '<div class="grow"><strong></strong><span></span></div><strong class="num"></strong>';
            li.querySelector('.grow strong').textContent = p.name;
            li.querySelector('.grow span').textContent = line.qty + ' × ' + money(p.price);
            li.querySelector('.num').textContent = money(p.price * line.qty);
            list.appendChild(li);
        });
    }

    function render() {
        var lines = PV.cart.read().filter(function (l) { return products[l.id]; });
        var has = lines.length > 0;

        $('[data-cart-empty]').hidden = has;
        if (isCheckout) {
            renderCheckoutRows(lines);
        } else {
            $('[data-cart-table]').hidden = !has;
            renderCartRows(lines);
        }

        var subtotal = lines.reduce(function (s, l) { return s + products[l.id].price * l.qty; }, 0);
        var tax = Math.round(subtotal * taxRate * 100) / 100;
        var count = lines.reduce(function (s, l) { return s + l.qty; }, 0);
        $('[data-summary-count]').textContent = count;
        $('[data-summary-subtotal]').textContent = money(subtotal);
        $('[data-summary-tax]').textContent = money(tax);
        $('[data-summary-total]').textContent = money(subtotal + tax);

        var checkoutLink = $('[data-checkout-link]');
        if (checkoutLink) checkoutLink.setAttribute('aria-disabled', String(!has));
        var printBtn = $('[data-print]');
        if (printBtn) printBtn.disabled = !has;
    }

    var stored = PV.cart.read();
    var ids = stored.map(function (l) { return l.id; });
    var load = ids.length
        ? fetch('search_api.php?ids=' + ids.join(','), { credentials: 'same-origin' }).then(function (r) {
            if (!r.ok) throw new Error(r.status);
            return r.json();
        })
        : Promise.resolve([]);

    load.then(function (rows) {
        rows.forEach(function (p) { products[p.id] = p; });
        reconcile(stored);
        render();
    }).catch(function () {
        notice('error', 'We couldn’t check current prices. Please refresh the page.');
    }).finally(function () {
        $('[data-cart-loading]').hidden = true;
    });

    var checkoutLink = $('[data-checkout-link]');
    if (checkoutLink) {
        checkoutLink.addEventListener('click', function (e) {
            if (checkoutLink.getAttribute('aria-disabled') === 'true') e.preventDefault();
        });
    }

    var printBtn = $('[data-print]');
    if (printBtn) {
        printBtn.addEventListener('click', function () {
            var date = $('[data-receipt-date]');
            if (date) date.textContent = new Date().toLocaleString();
            window.print();
        });
    }
})();

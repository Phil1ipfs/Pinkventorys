// Checkout: look up real inventory products, build a cart, print a receipt.
(function () {
    'use strict';

    var pos = document.querySelector('.pos');
    if (!pos) return;

    var taxRate = parseFloat(pos.dataset.taxRate) || 0;
    var form = document.getElementById('lookupForm');
    var input = document.getElementById('lookup');
    var results = document.getElementById('lookupResults');
    var tbody = document.getElementById('cartItems');
    var emptyState = document.getElementById('cartEmpty');
    var tableWrap = document.getElementById('cartTableWrap');
    var STORAGE_KEY = 'pinkventory-cart';

    var money = function (n) {
        return '$' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };
    var icon = function (name, cls) { return window.PV ? PV.icon(name, cls) : ''; };
    var notify = function (type, msg) { if (window.PV) PV.toast(type, msg); };

    var cart = [];
    try {
        cart = JSON.parse(sessionStorage.getItem(STORAGE_KEY) || '[]') || [];
    } catch (e) {
        cart = [];
    }

    function save() {
        try { sessionStorage.setItem(STORAGE_KEY, JSON.stringify(cart)); } catch (e) { /* storage unavailable */ }
    }

    /* ---------- Cart ---------- */

    function addToCart(product) {
        if (product.stock <= 0) {
            notify('error', product.name + ' is out of stock.');
            return;
        }
        var line = cart.find(function (l) { return l.id === product.id; });
        if (line) {
            if (line.qty >= product.stock) {
                notify('info', 'Only ' + product.stock + ' × ' + product.name + ' in stock.');
                return;
            }
            line.qty += 1;
            line.stock = product.stock;
            line.price = product.price;
        } else {
            cart.push({ id: product.id, name: product.name, category: product.category, price: product.price, stock: product.stock, qty: 1 });
        }
        save();
        render();
        notify('success', product.name + ' added to the cart.');
    }

    function changeQty(id, delta) {
        var line = cart.find(function (l) { return l.id === id; });
        if (!line) return;
        line.qty = Math.min(line.stock, line.qty + delta);
        if (line.qty <= 0) cart = cart.filter(function (l) { return l.id !== id; });
        save();
        render();
    }

    function removeLine(id) {
        cart = cart.filter(function (l) { return l.id !== id; });
        save();
        render();
    }

    function cell(label, cls) {
        var td = document.createElement('td');
        if (label) td.dataset.label = label;
        if (cls) td.className = cls;
        return td;
    }

    function render() {
        tbody.innerHTML = '';
        cart.forEach(function (line) {
            var tr = document.createElement('tr');

            var product = cell('', 'cell-first');
            product.innerHTML = '<div class="cell-main"><span class="thumb">' + icon('package') + '</span><div><strong></strong><span></span></div></div>';
            product.querySelector('strong').textContent = line.name;
            product.querySelector('span:not(.thumb)').textContent = '#' + line.id + ' · ' + line.category;

            var price = cell('Price', 'right num');
            price.textContent = money(line.price);

            var qty = cell('Quantity');
            qty.innerHTML = '<div class="qty" role="group">' +
                '<button type="button" data-act="dec">' + icon('minus', 'icon-sm') + '</button>' +
                '<output></output>' +
                '<button type="button" data-act="inc">' + icon('plus', 'icon-sm') + '</button></div>';
            qty.querySelector('.qty').setAttribute('aria-label', 'Quantity of ' + line.name);
            qty.querySelector('output').textContent = line.qty;
            qty.querySelector('[data-act="dec"]').setAttribute('aria-label', 'Remove one ' + line.name);
            var inc = qty.querySelector('[data-act="inc"]');
            inc.setAttribute('aria-label', 'Add one ' + line.name);
            if (line.qty >= line.stock) {
                inc.disabled = true;
                inc.title = 'No more in stock';
            }
            qty.querySelector('[data-act="dec"]').addEventListener('click', function () { changeQty(line.id, -1); });
            inc.addEventListener('click', function () { changeQty(line.id, 1); });

            var total = cell('Total', 'right num');
            total.innerHTML = '<strong></strong>';
            total.firstChild.textContent = money(line.price * line.qty);

            var actions = cell('', 'actions no-print');
            actions.innerHTML = '<button type="button" class="btn btn-ghost btn-icon btn-sm">' + icon('trash-2', 'icon-sm') + '</button>';
            actions.firstChild.setAttribute('aria-label', 'Remove ' + line.name);
            actions.firstChild.addEventListener('click', function () { removeLine(line.id); });

            [product, price, qty, total, actions].forEach(function (td) { tr.appendChild(td); });
            tbody.appendChild(tr);
        });

        var subtotal = cart.reduce(function (sum, l) { return sum + l.price * l.qty; }, 0);
        var tax = subtotal * taxRate;
        var count = cart.reduce(function (sum, l) { return sum + l.qty; }, 0);
        document.getElementById('subtotal').textContent = money(subtotal);
        document.getElementById('tax').textContent = money(tax);
        document.getElementById('total').textContent = money(subtotal + tax);
        document.getElementById('itemCount').textContent = count;

        var hasItems = cart.length > 0;
        emptyState.hidden = hasItems;
        tableWrap.hidden = !hasItems;
        document.getElementById('printReceipt').disabled = !hasItems;
        document.getElementById('clearCart').disabled = !hasItems;
    }

    /* ---------- Product lookup ---------- */

    var found = [];
    var active = -1;
    var latest = 0;
    var debounce;

    function closeResults() {
        results.hidden = true;
        input.setAttribute('aria-expanded', 'false');
        active = -1;
    }

    function showResults(items, query) {
        found = items;
        results.innerHTML = '';
        if (!items.length) {
            var li = document.createElement('li');
            li.className = 'suggest-empty';
            li.style.padding = '10px';
            li.textContent = 'No product matches “' + query + '”.';
            results.appendChild(li);
        }
        items.forEach(function (p, i) {
            var li = document.createElement('li');
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'result';
            btn.id = 'result-' + i;
            btn.setAttribute('role', 'option');
            btn.innerHTML = '<span class="thumb">' + icon('package') + '</span><span class="grow"><strong></strong><span></span></span><strong class="num"></strong>';
            btn.querySelector('.grow strong').textContent = p.name;
            btn.querySelector('.grow span').textContent = '#' + p.id + ' · ' + p.category + ' · ' + (p.stock > 0 ? p.stock + ' in stock' : 'Out of stock');
            btn.querySelector('.num').textContent = money(p.price);
            if (p.stock <= 0) btn.disabled = true;
            btn.addEventListener('click', function () { pick(p); });
            li.appendChild(btn);
            results.appendChild(li);
        });
        results.hidden = false;
        input.setAttribute('aria-expanded', 'true');
        active = -1;
    }

    function highlight(index) {
        var buttons = results.querySelectorAll('.result');
        buttons.forEach(function (b, i) { b.classList.toggle('active', i === index); b.setAttribute('aria-selected', String(i === index)); });
        active = index;
        if (buttons[index]) {
            input.setAttribute('aria-activedescendant', buttons[index].id);
            buttons[index].scrollIntoView({ block: 'nearest' });
        }
    }

    function pick(product) {
        addToCart(product);
        input.value = '';
        closeResults();
        input.focus();
    }

    function lookup(query) {
        var id = ++latest;
        return fetch('search_api.php?full=1&q=' + encodeURIComponent(query), { credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.json() : []; })
            .then(function (items) { return id === latest ? items : null; });
    }

    input.addEventListener('input', function () {
        var query = input.value.trim();
        clearTimeout(debounce);
        if (!query) {
            closeResults();
            return;
        }
        debounce = setTimeout(function () {
            lookup(query).then(function (items) { if (items) showResults(items, query); }).catch(closeResults);
        }, 200);
    });

    input.addEventListener('keydown', function (e) {
        var buttons = results.querySelectorAll('.result');
        if (e.key === 'ArrowDown' && buttons.length) {
            e.preventDefault();
            highlight((active + 1) % buttons.length);
        } else if (e.key === 'ArrowUp' && buttons.length) {
            e.preventDefault();
            highlight((active - 1 + buttons.length) % buttons.length);
        } else if (e.key === 'Escape') {
            closeResults();
        }
    });

    // Enter (or a barcode scanner's trailing Enter) adds the best match.
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var query = input.value.trim();
        if (!query) return;
        if (active >= 0 && found[active]) {
            pick(found[active]);
            return;
        }
        clearTimeout(debounce);
        lookup(query).then(function (items) {
            if (!items) return;
            var exact = items.find(function (p) { return String(p.id) === query || p.name.toLowerCase() === query.toLowerCase(); });
            var available = items.filter(function (p) { return p.stock > 0; });
            if (exact) pick(exact);
            else if (available.length === 1) pick(available[0]);
            else showResults(items, query);
        });
    });

    document.addEventListener('click', function (e) {
        if (!form.contains(e.target)) closeResults();
    });

    /* ---------- Actions ---------- */

    document.getElementById('clearCart').addEventListener('click', function () {
        var reset = function () {
            cart = [];
            save();
            render();
            input.focus();
        };
        if (!window.PV) return reset();
        PV.confirm({ title: 'Start a new sale?', message: 'The current cart will be cleared.', confirmLabel: 'Clear cart' })
            .then(function (ok) { if (ok) reset(); });
    });

    document.getElementById('printReceipt').addEventListener('click', function () {
        document.getElementById('receiptDate').textContent = new Date().toLocaleString();
        window.print();
    });

    render();
})();

<?php
require_once 'bootstrap.php';
require_once 'layout.php';
require_login();

const TAX_RATE = 0.10;

layout_start('Checkout', 'checkout');
?>

<div class="page-head">
    <div>
        <h1>Checkout</h1>
        <p>Scan a product ID or search by name to build the customer's cart.</p>
    </div>
    <div class="page-actions">
        <button type="button" class="btn btn-secondary" id="clearCart" disabled><?php echo icon('refresh-cw'); ?> New sale</button>
    </div>
</div>

<div class="pos" data-tax-rate="<?php echo TAX_RATE; ?>">
    <section class="card" aria-labelledby="cartTitle">
        <h2 id="cartTitle" class="sr-only">Cart</h2>
        <div class="receipt-head">
            <h2>Pinkventory</h2>
            <p class="muted">Receipt · <span id="receiptDate"></span></p>
        </div>

        <form class="pos-search no-print" id="lookupForm" role="search" autocomplete="off">
            <label for="lookup" class="sr-only">Product ID or name</label>
            <div class="control">
                <?php echo icon('scan-barcode'); ?>
                <input type="search" id="lookup" class="input" placeholder="Scan or type a product ID or name, then press Enter"
                       role="combobox" aria-expanded="false" aria-controls="lookupResults" aria-autocomplete="list" autofocus>
            </div>
            <ul class="results" id="lookupResults" role="listbox" hidden></ul>
        </form>

        <div id="cartEmpty" class="empty">
            <span class="empty-icon"><?php echo icon('shopping-cart'); ?></span>
            <h3>The cart is empty</h3>
            <p>Scan a product ID or search for a product above to add it to this sale.</p>
        </div>

        <div class="table-wrap" id="cartTableWrap" hidden>
            <table class="table table-cards">
                <thead>
                    <tr>
                        <th scope="col">Product</th>
                        <th scope="col" class="right">Price</th>
                        <th scope="col">Quantity</th>
                        <th scope="col" class="right">Total</th>
                        <th scope="col" class="actions no-print"><span class="sr-only">Remove</span></th>
                    </tr>
                </thead>
                <tbody id="cartItems"></tbody>
            </table>
        </div>
    </section>

    <aside class="card" aria-labelledby="summaryTitle">
        <div class="card-head">
            <div>
                <h2 id="summaryTitle">Order summary</h2>
                <p><span id="itemCount">0</span> items</p>
            </div>
        </div>
        <div class="card-body">
            <div class="summary-row"><span>Subtotal</span><span id="subtotal">$0.00</span></div>
            <div class="summary-row"><span>Tax (<?php echo TAX_RATE * 100; ?>%)</span><span id="tax">$0.00</span></div>
            <div class="summary-row summary-total"><span>Total</span><span id="total">$0.00</span></div>

            <div class="pos-actions no-print">
                <div class="pair">
                    <button type="button" class="btn btn-secondary" disabled aria-describedby="paymentNote"><?php echo icon('credit-card'); ?> Card</button>
                    <button type="button" class="btn btn-secondary" disabled aria-describedby="paymentNote"><?php echo icon('banknote'); ?> Cash</button>
                </div>
                <button type="button" class="btn btn-primary btn-lg" id="printReceipt" disabled><?php echo icon('printer'); ?> Print receipt</button>
            </div>
            <p class="note no-print" id="paymentNote">
                <?php echo icon('info'); ?>
                <span>Payments and sales history aren't connected yet: completing a sale here doesn't charge a card or change stock levels.</span>
            </p>
        </div>
    </aside>
</div>

<?php layout_end(['checkout.js']); ?>

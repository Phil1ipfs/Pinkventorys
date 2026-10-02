<?php
/**
 * Product form shared by add_product.php and update_product.php.
 * Expects: $values (field => display value), $errors (field => message),
 * $categories, $formAction, $submitLabel, and optionally $productId.
 */
if (!defined('SESSION_IDLE_TIMEOUT')) {
    http_response_code(404);
    exit;
}

$fieldError = function (string $field) use ($errors): string {
    return isset($errors[$field])
        ? '<p class="field-error" id="' . $field . 'Error">' . icon('alert-circle') . e($errors[$field]) . '</p>'
        : '';
};
$describedBy = fn(string $field, string $help = '') => trim(($help ? $help . ' ' : '') . (isset($errors[$field]) ? $field . 'Error' : ''));
$statusChoices = ['in-stock' => ['In stock', 'ok'], 'low-stock' => ['Low stock', 'low'], 'out-of-stock' => ['Out of stock', 'out']];
?>
<?php if ($errors): ?>
    <div class="alert alert-error" role="alert">
        <?php echo icon('alert-circle'); ?>
        <div>
            <strong>Please fix <?php echo count($errors) === 1 ? 'this' : 'these ' . count($errors); ?> before saving:</strong>
            <ul><?php foreach ($errors as $message) echo '<li>' . e($message) . '</li>'; ?></ul>
        </div>
    </div>
<?php endif; ?>

<form method="post" action="<?php echo e($formAction); ?>" class="card form-card" novalidate data-product-form data-low-threshold="<?php echo LOW_STOCK_THRESHOLD; ?>">
    <?php echo csrf_field(); ?>
    <?php if (!empty($productId)): ?>
        <input type="hidden" name="id" value="<?php echo (int) $productId; ?>">
    <?php endif; ?>

    <div class="form-section">
        <div class="form-section-intro">
            <h2>Product details</h2>
            <p>How the product appears in your inventory and at checkout.</p>
        </div>
        <div class="fields">
            <div class="field <?php echo isset($errors['product_name']) ? 'has-error' : ''; ?>">
                <label for="product_name">Product name</label>
                <input type="text" id="product_name" name="product_name" class="input" maxlength="150" required
                       value="<?php echo e($values['product_name']); ?>" placeholder="e.g. Pink Hoodie"
                       <?php echo isset($errors['product_name']) ? 'aria-invalid="true"' : ''; ?> aria-describedby="<?php echo $describedBy('product_name'); ?>">
                <?php echo $fieldError('product_name'); ?>
            </div>
            <div class="field <?php echo isset($errors['category']) ? 'has-error' : ''; ?>">
                <label for="category">Category</label>
                <input type="text" id="category" name="category" class="input" maxlength="50" required list="categoryList"
                       value="<?php echo e($values['category']); ?>" placeholder="Choose or type a category"
                       <?php echo isset($errors['category']) ? 'aria-invalid="true"' : ''; ?> aria-describedby="<?php echo $describedBy('category', 'categoryHelp'); ?>">
                <datalist id="categoryList">
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo e($cat); ?>"><?php echo e(ucfirst($cat)); ?></option>
                    <?php endforeach; ?>
                </datalist>
                <p class="help" id="categoryHelp">Pick an existing category or type a new one.</p>
                <?php echo $fieldError('category'); ?>
            </div>
        </div>
    </div>

    <div class="form-section">
        <div class="form-section-intro">
            <h2>Stock &amp; pricing</h2>
            <p>Low-stock alerts start at <?php echo LOW_STOCK_THRESHOLD; ?> units or fewer.</p>
        </div>
        <div class="fields">
            <div class="fields-2">
                <div class="field <?php echo isset($errors['stock']) ? 'has-error' : ''; ?>">
                    <label for="stock">Quantity in stock</label>
                    <div class="control">
                        <?php echo icon('boxes', 'icon-sm'); ?>
                        <input type="number" id="stock" name="stock" class="input num" min="0" step="1" inputmode="numeric" required
                               value="<?php echo e($values['stock']); ?>" placeholder="0"
                               <?php echo isset($errors['stock']) ? 'aria-invalid="true"' : ''; ?> aria-describedby="<?php echo $describedBy('stock'); ?>">
                    </div>
                    <?php echo $fieldError('stock'); ?>
                </div>
                <div class="field <?php echo isset($errors['price']) ? 'has-error' : ''; ?>">
                    <label for="price">Unit price</label>
                    <div class="control">
                        <span class="prefix">$</span>
                        <input type="number" id="price" name="price" class="input num" min="0" step="0.01" inputmode="decimal" required
                               value="<?php echo e($values['price']); ?>" placeholder="0.00"
                               <?php echo isset($errors['price']) ? 'aria-invalid="true"' : ''; ?> aria-describedby="<?php echo $describedBy('price'); ?>">
                    </div>
                    <?php echo $fieldError('price'); ?>
                </div>
            </div>
            <fieldset class="field <?php echo isset($errors['status']) ? 'has-error' : ''; ?>">
                <legend class="label">Stock status</legend>
                <div class="choices">
                    <?php foreach ($statusChoices as $value => [$label, $dot]): ?>
                        <label class="choice">
                            <input type="radio" name="status" value="<?php echo $value; ?>" <?php echo $values['status'] === $value ? 'checked' : ''; ?> required>
                            <span><span class="dot dot-<?php echo $dot; ?>"></span><?php echo $label; ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <p class="help" id="statusHint" aria-live="polite">Updates automatically from the quantity until you pick one yourself.</p>
                <?php echo $fieldError('status'); ?>
            </fieldset>
        </div>
    </div>

    <div class="form-actions">
        <?php if (!empty($productId)): ?>
            <span class="spacer muted">Product #<?php echo (int) $productId; ?></span>
        <?php endif; ?>
        <a href="inventory.php" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary"><?php echo icon('check'); ?> <?php echo e($submitLabel); ?></button>
    </div>
</form>

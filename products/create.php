<?php
/**
 * products/create.php — Add product
 * ------------------------------------------------------------
 * Renders and processes the "new product" form.
 * - All fields validated server-side (inline errors per field)
 * - POST → validate → insert → redirect (PRG: no duplicate
 *   submissions when the user refreshes the result page)
 * - CSRF token required on every submission
 * ------------------------------------------------------------
 */

$pageTitle = 'Add Product';
$active    = 'products';
$base      = '../';

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$errors  = [];
$values  = [
    'name'            => '',
    'description'     => '',
    'price'           => '',
    'stock_quantity'  => '',
    'alert_threshold' => '5',
];

// ---------------------------------------------------------------
// Handle the POST submission
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1) CSRF token check
    if (!csrf_check()) {
        flash('error', 'Your session expired or the form was invalid. Please try again.');
        redirect('create.php');
    }

    // 2) Collect (trimmed) input so the form can re-fill on error
    foreach ($values as $key => $default) {
        $values[$key] = post_str($key);
    }

    // 3) Server-side validation ---------------------------------
    if ($values['name'] === '') {
        $errors['name'] = 'Product name is required.';
    } elseif (mb_strlen($values['name']) > 100) {
        $errors['name'] = 'Name must be 100 characters or fewer.';
    }

    if ($values['description'] !== '' && mb_strlen($values['description']) > 2000) {
        $errors['description'] = 'Description must be 2,000 characters or fewer.';
    }

    $price = filter_var($values['price'], FILTER_VALIDATE_FLOAT);
    if ($price === false) {
        $errors['price'] = 'Price must be a valid number (e.g. 19.99).';
    } elseif ($price <= 0) {
        $errors['price'] = 'Price must be greater than 0.';
    }

    // Initial stock may be 0 (a product can be registered before
    // it physically arrives); movement quantities must be > 0.
    $quantity = filter_var($values['stock_quantity'], FILTER_VALIDATE_INT);
    if ($quantity === false) {
        $errors['stock_quantity'] = 'Initial quantity must be a whole number.';
    } elseif ($quantity < 0) {
        $errors['stock_quantity'] = 'Initial quantity cannot be negative.';
    }

    $threshold = filter_var($values['alert_threshold'], FILTER_VALIDATE_INT);
    if ($threshold === false) {
        $errors['alert_threshold'] = 'Alert threshold must be a whole number.';
    } elseif ($threshold < 0) {
        $errors['alert_threshold'] = 'Alert threshold must be 0 or greater.';
    }

    // 4) Save if everything is valid ---------------------------
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO products (name, description, price, stock_quantity, alert_threshold)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $values['name'],
                $values['description'] !== '' ? $values['description'] : null,
                $price,
                $quantity,
                $threshold,
            ]);

            // Success → flash + redirect (prevents double insert on refresh)
            flash('success', 'Product "' . $values['name'] . '" added successfully.');
            redirect('index.php');
        } catch (Throwable $e) {
            error_log('Create product failed: ' . $e->getMessage());
            $errors['general'] = 'Could not save the product. Please check your input and try again.';
        }
    }
}

require __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
    <div>
        <h1>Add Product</h1>
        <p class="subtitle">Register a new item in your inventory.</p>
    </div>
    <a class="btn btn-secondary" href="index.php">&larr; Back to products</a>
</div>

<?php if (!empty($errors['general'])): ?>
    <div class="alert-box alert-error"><?= e($errors['general']) ?></div>
<?php endif; ?>

<section class="card">
    <form action="create.php" method="post" novalidate>
        <?= csrf_field() ?>

        <div class="field">
            <label for="name">Product name *</label>
            <input type="text" id="name" name="name" maxlength="100"
                   class="<?= isset($errors['name']) ? 'invalid' : '' ?>"
                   value="<?= e($values['name']) ?>" placeholder="e.g. Wireless Mouse" required>
            <?php if (isset($errors['name'])): ?>
                <p class="field-error"><?= e($errors['name']) ?></p>
            <?php endif; ?>
        </div>

        <div class="field">
            <label for="description">Description</label>
            <textarea id="description" name="description" maxlength="2000"
                      class="<?= isset($errors['description']) ? 'invalid' : '' ?>"
                      placeholder="Optional details about the product"><?= e($values['description']) ?></textarea>
            <?php if (isset($errors['description'])): ?>
                <p class="field-error"><?= e($errors['description']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="price">Price (USD) *</label>
                <input type="text" inputmode="decimal" id="price" name="price"
                       class="<?= isset($errors['price']) ? 'invalid' : '' ?>"
                       value="<?= e($values['price']) ?>" placeholder="e.g. 19.99" required>
                <?php if (isset($errors['price'])): ?>
                    <p class="field-error"><?= e($errors['price']) ?></p>
                <?php endif; ?>
            </div>

            <div class="field">
                <label for="stock_quantity">Initial quantity *</label>
                <input type="text" inputmode="numeric" id="stock_quantity" name="stock_quantity"
                       class="<?= isset($errors['stock_quantity']) ? 'invalid' : '' ?>"
                       value="<?= e($values['stock_quantity']) ?>" placeholder="e.g. 25" required>
                <?php if (isset($errors['stock_quantity'])): ?>
                    <p class="field-error"><?= e($errors['stock_quantity']) ?></p>
                <?php endif; ?>
                <p class="hint">May be 0 if stock has not arrived yet.</p>
            </div>

            <div class="field">
                <label for="alert_threshold">Alert threshold *</label>
                <input type="text" inputmode="numeric" id="alert_threshold" name="alert_threshold"
                       class="<?= isset($errors['alert_threshold']) ? 'invalid' : '' ?>"
                       value="<?= e($values['alert_threshold']) ?>" required>
                <?php if (isset($errors['alert_threshold'])): ?>
                    <p class="field-error"><?= e($errors['alert_threshold']) ?></p>
                <?php endif; ?>
                <p class="hint">Warn me when stock falls to this level (0 or greater).</p>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn">Save Product</button>
            <a class="btn btn-secondary" href="index.php">Cancel</a>
        </div>
    </form>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>

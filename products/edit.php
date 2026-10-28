<?php
/**
 * products/edit.php — Edit product
 * ------------------------------------------------------------
 * Pre-fills the product form from ?id= and saves changes on POST.
 * Stock quantity is intentionally read-only here: quantity must
 * only change through Stock In / Stock Out so the movement
 * history stays complete and trustworthy.
 * ------------------------------------------------------------
 */

$pageTitle = 'Edit Product';
$active    = 'products';
$base      = '../';

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// ---------------------------------------------------------------
// Load the product (?id= must be a positive integer)
// ---------------------------------------------------------------
$id     = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$product = null;

if ($id === false || $id === null || $id <= 0) {
    flash('error', 'Invalid product selected.');
    redirect('index.php');
}

try {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $product = $stmt->fetch();
} catch (Throwable $e) {
    error_log('Load product failed: ' . $e->getMessage());
    flash('error', 'Could not load the product. Please try again later.');
    redirect('index.php');
}

if (!$product) {
    flash('error', 'Product not found — it may have been deleted.');
    redirect('index.php');
}

$errors = [];
$values = [
    'name'            => $product['name'],
    'description'     => (string) ($product['description'] ?? ''),
    'price'           => number_format((float) $product['price'], 2, '.', ''),
    'alert_threshold' => (string) $product['alert_threshold'],
];

// ---------------------------------------------------------------
// Handle the POST submission
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_check()) {
        flash('error', 'Your session expired or the form was invalid. Please try again.');
        redirect('edit.php?id=' . $id);
    }

    foreach ($values as $key => $default) {
        $values[$key] = post_str($key);
    }

    // Validation (same rules as create)
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

    $threshold = filter_var($values['alert_threshold'], FILTER_VALIDATE_INT);
    if ($threshold === false) {
        $errors['alert_threshold'] = 'Alert threshold must be a whole number.';
    } elseif ($threshold < 0) {
        $errors['alert_threshold'] = 'Alert threshold must be 0 or greater.';
    }

    if (empty($errors)) {
        try {
            // Note: stock_quantity is NOT updated here (see file header).
            $stmt = $pdo->prepare(
                'UPDATE products
                    SET name = ?, description = ?, price = ?, alert_threshold = ?
                  WHERE id = ?'
            );
            $stmt->execute([
                $values['name'],
                $values['description'] !== '' ? $values['description'] : null,
                $price,
                $threshold,
                $id,
            ]);

            flash('success', 'Product "' . $values['name'] . '" updated successfully.');
            redirect('index.php');
        } catch (Throwable $e) {
            error_log('Update product failed: ' . $e->getMessage());
            $errors['general'] = 'Could not save your changes. Please try again.';
        }
    }
}

require __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
    <div>
        <h1>Edit Product</h1>
        <p class="subtitle">Update the details of “<?= e($product['name']) ?>”.</p>
    </div>
    <a class="btn btn-secondary" href="index.php">&larr; Back to products</a>
</div>

<?php if (!empty($errors['general'])): ?>
    <div class="alert-box alert-error"><?= e($errors['general']) ?></div>
<?php endif; ?>

<section class="card">
    <form action="edit.php?id=<?= (int) $id ?>" method="post" novalidate>
        <?= csrf_field() ?>

        <div class="field">
            <label for="name">Product name *</label>
            <input type="text" id="name" name="name" maxlength="100"
                   class="<?= isset($errors['name']) ? 'invalid' : '' ?>"
                   value="<?= e($values['name']) ?>" required>
            <?php if (isset($errors['name'])): ?>
                <p class="field-error"><?= e($errors['name']) ?></p>
            <?php endif; ?>
        </div>

        <div class="field">
            <label for="description">Description</label>
            <textarea id="description" name="description" maxlength="2000"
                      class="<?= isset($errors['description']) ? 'invalid' : '' ?>"><?= e($values['description']) ?></textarea>
            <?php if (isset($errors['description'])): ?>
                <p class="field-error"><?= e($errors['description']) ?></p>
            <?php endif; ?>
        </div>

        <div class="form-grid">
            <div class="field">
                <label for="price">Price (USD) *</label>
                <input type="text" inputmode="decimal" id="price" name="price"
                       class="<?= isset($errors['price']) ? 'invalid' : '' ?>"
                       value="<?= e($values['price']) ?>" required>
                <?php if (isset($errors['price'])): ?>
                    <p class="field-error"><?= e($errors['price']) ?></p>
                <?php endif; ?>
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

        <?php // Current stock shown read-only: change it via Stock In / Stock Out ?>
        <div class="field readonly-stock">
            <label>Current stock (read-only)</label>
            <div class="stock-display">
                <span class="badge badge-<?= stock_status($product) === 'low' ? 'low' : (stock_status($product) === 'warn' ? 'warn' : 'ok') ?>">
                    <?= (int) $product['stock_quantity'] ?> units
                </span>
                <span class="hint">Use <a href="../movements/stock_in.php">Stock In</a> / <a href="../movements/stock_out.php">Stock Out</a> to change quantity.</span>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn">Save Changes</button>
            <a class="btn btn-secondary" href="index.php">Cancel</a>
        </div>
    </form>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>

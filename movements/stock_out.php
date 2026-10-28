<?php
/**
 * movements/stock_out.php — Remove stock (OUT)
 * ------------------------------------------------------------
 * Select a product, enter a quantity (> 0) and an optional note.
 *
 * BUSINESS RULE: stock-out is blocked when the requested
 * quantity exceeds the product's current stock — stock can
 * never go below 0. The UPDATE itself is guarded with
 * `AND stock_quantity >= ?` so even two simultaneous requests
 * cannot drive the balance negative.
 * ------------------------------------------------------------
 */

$pageTitle = 'Stock Out';
$active    = 'stock-out';
$base      = '../';

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$errors = ['quantity' => '', 'product_id' => '', 'note' => ''];
$values = ['product_id' => '', 'quantity' => '', 'note' => ''];

// ---------------------------------------------------------------
// Load products for the dropdown (fresh on every request)
// ---------------------------------------------------------------
try {
    $stmt = $pdo->prepare(
        'SELECT id, name, stock_quantity FROM products ORDER BY name ASC'
    );
    $stmt->execute();
    $products = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log('Stock-out product list failed: ' . $e->getMessage());
    flash('error', 'Could not load products. Please try again later.');
    redirect('../index.php');
}

// ---------------------------------------------------------------
// Handle the POST submission
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_check()) {
        flash('error', 'Your session expired or the form was invalid. Please try again.');
        redirect('stock_out.php');
    }

    $values['product_id'] = post_str('product_id');
    $values['quantity']   = post_str('quantity');
    $values['note']       = post_str('note');

    // --- Validation -------------------------------------------
    $productId = filter_var($values['product_id'], FILTER_VALIDATE_INT);
    if ($productId === false || $productId <= 0) {
        $errors['product_id'] = 'Please select a product.';
    }

    $quantity = filter_var($values['quantity'], FILTER_VALIDATE_INT);
    if ($quantity === false) {
        $errors['quantity'] = 'Quantity must be a whole number.';
    } elseif ($quantity <= 0) {
        $errors['quantity'] = 'Quantity must be greater than 0.';
    } elseif ($quantity > 1000000) {
        $errors['quantity'] = 'Quantity looks unrealistic (max 1,000,000).';
    }

    if (mb_strlen($values['note']) > 255) {
        $errors['note'] = 'Note must be 255 characters or fewer.';
    }

    $productName = '';
    $newStock    = 0;

    if (empty(array_filter($errors))) {
        try {
            // Fetch current stock so we can block insufficient requests
            $stmt = $pdo->prepare('SELECT name, stock_quantity FROM products WHERE id = ?');
            $stmt->execute([$productId]);
            $product = $stmt->fetch();

            if (!$product) {
                $errors['product_id'] = 'Selected product no longer exists.';
            } elseif ($quantity > (int) $product['stock_quantity']) {
                // --- BLOCKED: not enough stock -------------------
                $errors['quantity'] =
                    'Insufficient stock: "' . $product['name'] . '" has only ' .
                    (int) $product['stock_quantity'] . ' unit(s) available, but you requested ' .
                    $quantity . '.';
            } else {
                $productName = $product['name'];
                $newStock    = (int) $product['stock_quantity'] - $quantity;

                // One transaction = movement row + stock decrement together
                $pdo->beginTransaction();

                $stmt = $pdo->prepare(
                    "INSERT INTO stock_movements (product_id, type, quantity, note)
                     VALUES (?, 'out', ?, ?)"
                );
                $stmt->execute([$productId, $quantity, $values['note'] !== '' ? $values['note'] : null]);

                // Guarded decrement: stock can never drop below 0
                $stmt = $pdo->prepare(
                    'UPDATE products
                        SET stock_quantity = stock_quantity - ?
                      WHERE id = ? AND stock_quantity >= ?'
                );
                $stmt->execute([$quantity, $productId, $quantity]);

                if ($stmt->rowCount() === 0) {
                    // Someone else consumed the stock between our check and update
                    $pdo->rollBack();
                    $errors['quantity'] =
                        'Not enough stock anymore — the quantity was changed by another user. Please review and try again.';
                } else {
                    $pdo->commit();

                    flash(
                        'success',
                        'Stock OUT: removed ' . $quantity . ' × "' . $productName .
                        '" (new stock: ' . $newStock . ').'
                    );
                    redirect('stock_out.php');
                }
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Stock out failed: ' . $e->getMessage());
            $errors['quantity'] = 'Could not save the stock movement. Please try again.';
        }
    }
}

require __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
    <div>
        <h1>Stock Out</h1>
        <p class="subtitle">Record outgoing stock — decrements the selected product.</p>
    </div>
    <a class="btn btn-secondary" href="../index.php">&larr; Back to dashboard</a>
</div>

<?php if (empty($products)): ?>
    <div class="alert-box alert-info">
        You need to <a href="../products/create.php"><strong>add a product</strong></a> before recording stock movements.
    </div>
<?php else: ?>
    <section class="card card-narrow">
        <form action="stock_out.php" method="post" novalidate>
            <?= csrf_field() ?>

            <div class="field">
                <label for="product_id">Product *</label>
                <select id="product_id" name="product_id"
                        class="<?= $errors['product_id'] !== '' ? 'invalid' : '' ?>" required>
                    <option value="">— Select a product —</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= (int) $p['id'] ?>"
                            <?= (string) $values['product_id'] === (string) $p['id'] ? 'selected' : '' ?>>
                            <?= e($p['name']) ?> (in stock: <?= (int) $p['stock_quantity'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($errors['product_id'] !== ''): ?>
                    <p class="field-error"><?= e($errors['product_id']) ?></p>
                <?php endif; ?>
            </div>

            <div class="field">
                <label for="quantity">Quantity to remove *</label>
                <input type="text" inputmode="numeric" id="quantity" name="quantity"
                       class="<?= $errors['quantity'] !== '' ? 'invalid' : '' ?>"
                       value="<?= e($values['quantity']) ?>" placeholder="e.g. 5" required>
                <?php if ($errors['quantity'] !== ''): ?>
                    <p class="field-error"><?= e($errors['quantity']) ?></p>
                <?php endif; ?>
                <p class="hint">Must be a whole number greater than 0 — and never more than the current stock.</p>
            </div>

            <div class="field">
                <label for="note">Note (optional)</label>
                <input type="text" id="note" name="note" maxlength="255"
                       class="<?= $errors['note'] !== '' ? 'invalid' : '' ?>"
                       value="<?= e($values['note']) ?>" placeholder="e.g. Order #2201 shipped">
                <?php if ($errors['note'] !== ''): ?>
                    <p class="field-error"><?= e($errors['note']) ?></p>
                <?php endif; ?>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-danger">Remove Stock</button>
                <a class="btn btn-secondary" href="history.php">View history</a>
            </div>
        </form>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>

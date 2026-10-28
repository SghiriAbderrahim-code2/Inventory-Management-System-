<?php
/**
 * products/index.php — Product list
 * ------------------------------------------------------------
 * Lists every product with its price, stock, alert threshold,
 * colour-coded status and row actions (edit / history / delete).
 * Delete requests POST to delete.php.
 * ------------------------------------------------------------
 */

$pageTitle = 'Products';
$active    = 'products';
$base      = '../';

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    // Prepared statement (static SQL, but still never concatenated).
    $stmt = $pdo->prepare('SELECT * FROM products ORDER BY name ASC');
    $stmt->execute();
    $products = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log('Product list failed: ' . $e->getMessage());
    flash('error', 'Could not load the product list. Please try again later.');
    redirect('../index.php');
}

require __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
    <div>
        <h1>Products</h1>
        <p class="subtitle"><?= count($products) ?> product(s) in your inventory.</p>
    </div>
    <a class="btn" href="create.php">+ Add Product</a>
</div>

<section class="card">
    <?php if (empty($products)): ?>
        <p class="empty">
            No products yet. <a href="create.php"><strong>Add your first product</strong></a>.
        </p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th class="num">Price</th>
                        <th class="num">Stock</th>
                        <th class="num">Threshold</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($products as $p):
                    $status = stock_status($p);
                    $statusLabel = ['low' => 'Low stock', 'warn' => 'Running low', 'ok' => 'Healthy'][$status];
                ?>
                    <tr class="<?= $status === 'low' ? 'row-low' : ($status === 'warn' ? 'row-warn' : '') ?>">
                        <td>
                            <strong><?= e($p['name']) ?></strong>
                            <?php if (!empty($p['description'])): ?>
                                <div class="cell-sub"><?= e($p['description']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="num">$<?= e(number_format((float) $p['price'], 2)) ?></td>
                        <td class="num"><strong><?= (int) $p['stock_quantity'] ?></strong></td>
                        <td class="num"><?= (int) $p['alert_threshold'] ?></td>
                        <td>
                            <span class="badge badge-<?= $status === 'low' ? 'low' : ($status === 'warn' ? 'warn' : 'ok') ?>">
                                <?= e($statusLabel) ?>
                            </span>
                        </td>
                        <td>
                            <div class="actions">
                                <a class="btn btn-secondary btn-sm" href="edit.php?id=<?= (int) $p['id'] ?>">Edit</a>
                                <a class="btn btn-secondary btn-sm" href="../movements/history.php?id=<?= (int) $p['id'] ?>">History</a>

                                <?php // Delete posts to delete.php (CSRF-protected, confirmed in JS) ?>
                                <form class="inline-form" action="delete.php" method="post"
                                      data-confirm="Delete &ldquo;<?= e($p['name']) ?>&rdquo;? This cannot be undone.">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>

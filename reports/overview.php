<?php
/**
 * reports/overview.php — Total stock value report
 * ------------------------------------------------------------
 * Lists every product with its value (price × stock_quantity)
 * and a grand total at the bottom.
 * ------------------------------------------------------------
 */

$pageTitle = 'Stock Overview';
$active    = 'reports';
$base      = '../';

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    // Value is computed in SQL — still no user input involved.
    $stmt = $pdo->prepare(
        'SELECT id, name, price, stock_quantity, alert_threshold,
                (price * stock_quantity) AS total_value
           FROM products
          ORDER BY total_value DESC, name ASC'
    );
    $stmt->execute();
    $rows = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log('Overview report failed: ' . $e->getMessage());
    flash('error', 'Could not load the stock overview. Please try again later.');
    redirect('../index.php');
}

// Grand totals
$grandValue = 0.0;
$grandUnits = 0;
foreach ($rows as $row) {
    $grandValue += (float) $row['total_value'];
    $grandUnits += (int) $row['stock_quantity'];
}

require __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
    <div>
        <h1>Stock Overview</h1>
        <p class="subtitle">Total value of your inventory (price × quantity).</p>
    </div>
    <a class="btn btn-secondary" href="../index.php">&larr; Back to dashboard</a>
</div>

<div class="grid">
    <div class="stat">
        <div class="label">Products</div>
        <div class="value"><?= count($rows) ?></div>
    </div>
    <div class="stat">
        <div class="label">Total Units</div>
        <div class="value"><?= $grandUnits ?></div>
    </div>
    <div class="stat">
        <div class="label">Grand Total Value</div>
        <div class="value text-green">$<?= e(number_format($grandValue, 2)) ?></div>
    </div>
</div>

<section class="card">
    <div class="section-head">
        <h2>Inventory Valuation</h2>
        <a href="../reports/alerts.php">Low stock alerts &rarr;</a>
    </div>

    <?php if (empty($rows)): ?>
        <p class="empty">No products in the inventory yet. <a href="../products/create.php"><strong>Add one</strong></a>.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="num">Unit Price</th>
                        <th class="num">Quantity</th>
                        <th class="num">Total Value</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row):
                    $status = stock_status($row);
                ?>
                    <tr class="<?= $status === 'low' ? 'row-low' : ($status === 'warn' ? 'row-warn' : '') ?>">
                        <td>
                            <strong><?= e($row['name']) ?></strong>
                            <?php if ($status !== 'ok'): ?>
                                <span class="badge badge-<?= $status === 'low' ? 'low' : 'warn' ?>" style="margin-left:6px">
                                    <?= $status === 'low' ? 'Low stock' : 'Running low' ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="num">$<?= e(number_format((float) $row['price'], 2)) ?></td>
                        <td class="num"><?= (int) $row['stock_quantity'] ?></td>
                        <td class="num"><strong>$<?= e(number_format((float) $row['total_value'], 2)) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="3">Grand Total</td>
                        <td class="num"><strong>$<?= e(number_format($grandValue, 2)) ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>

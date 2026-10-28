<?php
/**
 * reports/alerts.php — Low stock alerts
 * ------------------------------------------------------------
 * 1) Products at or below their alert threshold → red highlight
 * 2) Products approaching the threshold (≤ 2× threshold) → orange
 * ------------------------------------------------------------
 */

$pageTitle = 'Low Stock Alerts';
$active    = 'alerts';
$base      = '../';

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    // Critical: stock at or below the threshold
    $stmt = $pdo->prepare(
        'SELECT * FROM products
          WHERE stock_quantity <= alert_threshold
          ORDER BY (stock_quantity - alert_threshold) ASC, name ASC'
    );
    $stmt->execute();
    $critical = $stmt->fetchAll();

    // Warning: above the threshold but within 2× of it
    $stmt = $pdo->prepare(
        'SELECT * FROM products
          WHERE stock_quantity > alert_threshold
            AND alert_threshold > 0
            AND stock_quantity <= alert_threshold * 2
          ORDER BY stock_quantity ASC, name ASC'
    );
    $stmt->execute();
    $warning = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log('Alerts report failed: ' . $e->getMessage());
    flash('error', 'Could not load the alerts report. Please try again later.');
    redirect('../index.php');
}

require __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
    <div>
        <h1>Low Stock Alerts</h1>
        <p class="subtitle">Products that need restocking attention.</p>
    </div>
    <div class="actions">
        <a class="btn btn-success btn-sm" href="../movements/stock_in.php">+ Stock In</a>
        <a class="btn btn-secondary" href="../index.php">&larr; Dashboard</a>
    </div>
</div>

<?php if (empty($critical) && empty($warning)): ?>
    <div class="alert-box alert-success">
        All products are above their alert thresholds. Nothing needs restocking right now.
    </div>
<?php endif; ?>

<?php if (!empty($critical)): ?>
    <!-- ====================== CRITICAL (RED) ====================== -->
    <section class="card">
        <div class="section-head">
            <h2 class="text-red">Critical — at or below threshold (<?= count($critical) ?>)</h2>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="num">Current Stock</th>
                        <th class="num">Threshold</th>
                        <th class="num">Shortage</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($critical as $p): ?>
                    <tr class="row-low">
                        <td>
                            <strong class="text-red"><?= e($p['name']) ?></strong>
                            <?php if (!empty($p['description'])): ?>
                                <div class="cell-sub"><?= e($p['description']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="num"><strong><?= (int) $p['stock_quantity'] ?></strong></td>
                        <td class="num"><?= (int) $p['alert_threshold'] ?></td>
                        <td class="num text-red">
                            <?= max(0, (int) $p['alert_threshold'] - (int) $p['stock_quantity']) ?> below
                        </td>
                        <td>
                            <div class="actions">
                                <a class="btn btn-success btn-sm" href="../movements/stock_in.php">Restock</a>
                                <a class="btn btn-secondary btn-sm" href="../movements/history.php?id=<?= (int) $p['id'] ?>">History</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<?php if (!empty($warning)): ?>
    <!-- ====================== WARNING (ORANGE) ====================== -->
    <section class="card">
        <div class="section-head">
            <h2 class="text-orange">Warning — running low soon (<?= count($warning) ?>)</h2>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th class="num">Current Stock</th>
                        <th class="num">Threshold</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($warning as $p): ?>
                    <tr class="row-warn">
                        <td><strong class="text-orange"><?= e($p['name']) ?></strong></td>
                        <td class="num"><strong><?= (int) $p['stock_quantity'] ?></strong></td>
                        <td class="num"><?= (int) $p['alert_threshold'] ?></td>
                        <td>
                            <div class="actions">
                                <a class="btn btn-success btn-sm" href="../movements/stock_in.php">Restock</a>
                                <a class="btn btn-secondary btn-sm" href="../movements/history.php?id=<?= (int) $p['id'] ?>">History</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php endif; ?>

<?php if (!empty($critical) || !empty($warning)): ?>
    <div class="alert-box alert-info">
        Tip: set a higher <a href="../products/index.php"><strong>alert threshold</strong></a>
        on important products so you get warned earlier.
    </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>

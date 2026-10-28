<?php
/**
 * index.php — Dashboard / entry point
 * ------------------------------------------------------------
 * Shows the four headline KPIs (products, stock value, low-stock
 * count, total units) plus the last 5 stock movements.
 * ------------------------------------------------------------
 */

$pageTitle = 'Dashboard';
$active    = 'dashboard';
$base      = '';

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// KPI aggregates + recent activity. Every query is prepared —
// no user input is interpolated into SQL anywhere in this app.
$stats      = ['total_products' => 0, 'total_units' => 0, 'total_value' => 0];
$lowCount   = 0;
$recent     = [];
$queryError = false;

try {
    // 1) Product / stock value totals
    $stmt = $pdo->prepare(
        'SELECT COUNT(*)                                   AS total_products,
                COALESCE(SUM(stock_quantity), 0)           AS total_units,
                COALESCE(SUM(stock_quantity * price), 0)   AS total_value
           FROM products'
    );
    $stmt->execute();
    $stats = $stmt->fetch() ?: $stats;

    // 2) Products at or below their alert threshold
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE stock_quantity <= alert_threshold');
    $stmt->execute();
    $lowCount = (int) $stmt->fetchColumn();

    // 3) Latest 5 movements across all products
    $stmt = $pdo->prepare(
        'SELECT m.type, m.quantity, m.note, m.created_at, p.name AS product_name
           FROM stock_movements m
           JOIN products p ON p.id = m.product_id
          ORDER BY m.created_at DESC, m.id DESC
          LIMIT 5'
    );
    $stmt->execute();
    $recent = $stmt->fetchAll();
} catch (Throwable $e) {
    // Friendly message only — raw errors go to the server log.
    error_log('Dashboard query failed: ' . $e->getMessage());
    $queryError = true;
}

$totalValue = (float) $stats['total_value'];
require __DIR__ . '/includes/header.php';
?>

<?php if ($queryError): ?>
    <div class="alert-box alert-error">
        Something went wrong while loading the dashboard. Please try again later.
    </div>
<?php endif; ?>

<div class="page-head">
    <div>
        <h1>Dashboard</h1>
        <p class="subtitle">Overview of your inventory at a glance.</p>
    </div>
    <div class="actions">
        <a class="btn" href="products/create.php">+ Add Product</a>
        <a class="btn btn-success" href="movements/stock_in.php">Stock In</a>
        <a class="btn btn-danger" href="movements/stock_out.php">Stock Out</a>
    </div>
</div>

<!-- ====================== KPI CARDS ====================== -->
<div class="grid">
    <div class="stat">
        <div class="label">Total Products</div>
        <div class="value"><?= (int) $stats['total_products'] ?></div>
        <a class="link" href="products/index.php">Manage products &rarr;</a>
    </div>

    <div class="stat">
        <div class="label">Total Stock Value</div>
        <div class="value">$<?= e(number_format($totalValue, 2)) ?></div>
        <a class="link" href="reports/overview.php">Stock overview &rarr;</a>
    </div>

    <div class="stat <?= $lowCount > 0 ? 'danger' : '' ?>">
        <div class="label">Low Stock Alerts</div>
        <div class="value"><?= $lowCount ?></div>
        <a class="link" href="reports/alerts.php">
            <?= $lowCount > 0 ? 'Products need attention &rarr;' : 'All products healthy &rarr;' ?>
        </a>
    </div>

    <div class="stat">
        <div class="label">Units In Stock</div>
        <div class="value"><?= (int) $stats['total_units'] ?></div>
        <a class="link" href="movements/history.php">Movement history &rarr;</a>
    </div>
</div>

<!-- ====================== LAST 5 MOVEMENTS ====================== -->
<section class="card">
    <div class="section-head">
        <h2>Last 5 Stock Movements</h2>
        <a href="movements/history.php">View full history &rarr;</a>
    </div>

    <?php if (empty($recent)): ?>
        <p class="empty">No stock movements recorded yet. Use <strong>Stock In</strong> to get started.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Type</th>
                        <th class="num">Quantity</th>
                        <th>Note</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recent as $m): ?>
                    <tr>
                        <td><strong><?= e($m['product_name']) ?></strong></td>
                        <td>
                            <span class="badge <?= $m['type'] === 'in' ? 'badge-in' : 'badge-out' ?>">
                                <?= $m['type'] === 'in' ? 'IN' : 'OUT' ?>
                            </span>
                        </td>
                        <td class="num <?= $m['type'] === 'in' ? 'text-green' : 'text-red' ?>">
                            <?= $m['type'] === 'in' ? '+' : '-' ?><?= (int) $m['quantity'] ?>
                        </td>
                        <td><?= e($m['note'] !== null && $m['note'] !== '' ? $m['note'] : '—') ?></td>
                        <td><?= e(pretty_date($m['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

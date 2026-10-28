<?php
/**
 * movements/history.php — Movement history per product
 * ------------------------------------------------------------
 * Pick a product from the dropdown (GET ?id=) and see every
 * stock movement for it, most recent first.
 * ------------------------------------------------------------
 */

$pageTitle = 'Movement History';
$active    = 'history';
$base      = '../';

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// ---------------------------------------------------------------
// Product dropdown + optional selection
// ---------------------------------------------------------------
try {
    $stmt = $pdo->prepare(
        'SELECT id, name, stock_quantity, alert_threshold FROM products ORDER BY name ASC'
    );
    $stmt->execute();
    $products = $stmt->fetchAll();
} catch (Throwable $e) {
    error_log('History product list failed: ' . $e->getMessage());
    flash('error', 'Could not load products. Please try again later.');
    redirect('../index.php');
}

$selectedId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($selectedId === false) {
    $selectedId = null;
}

$product   = null;
$movement  = [];
$notFound  = false;

if ($selectedId !== null && $selectedId > 0) {
    try {
        $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([$selectedId]);
        $product = $stmt->fetch();

        if ($product) {
            // Most recent first (created_at DESC, id DESC breaks ties)
            $stmt = $pdo->prepare(
                'SELECT * FROM stock_movements
                  WHERE product_id = ?
                 ORDER BY created_at DESC, id DESC'
            );
            $stmt->execute([$selectedId]);
            $movements = $stmt->fetchAll();
        } else {
            $notFound = true;
        }
    } catch (Throwable $e) {
        error_log('Movement history failed: ' . $e->getMessage());
        flash('error', 'Could not load the movement history. Please try again later.');
        redirect('history.php');
    }
}

require __DIR__ . '/../includes/header.php';
?>

<div class="page-head">
    <div>
        <h1>Movement History</h1>
        <p class="subtitle">Every stock IN and OUT recorded for a product.</p>
    </div>
    <div class="actions">
        <a class="btn btn-success btn-sm" href="stock_in.php">+ Stock In</a>
        <a class="btn btn-danger btn-sm" href="stock_out.php">− Stock Out</a>
    </div>
</div>

<?php if ($notFound): ?>
    <div class="alert-box alert-error">That product could not be found — it may have been deleted.</div>
<?php endif; ?>

<!-- ====================== PRODUCT SELECTOR ====================== -->
<section class="card">
    <form action="history.php" method="get" class="filter-row">
        <div class="field field-inline">
            <label for="id">Product</label>
            <select id="id" name="id" onchange="this.form.submit()">
                <option value="">— Select a product —</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= (int) $p['id'] ?>"
                        <?= (int) ($selectedId ?? 0) === (int) $p['id'] ? 'selected' : '' ?>>
                        <?= e($p['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary">View history</button>
    </form>
</section>

<?php if ($product): ?>
    <!-- ====================== PRODUCT SUMMARY ====================== -->
    <div class="grid">
        <div class="stat">
            <div class="label">Product</div>
            <div class="value value-sm"><?= e($product['name']) ?></div>
        </div>
        <div class="stat">
            <div class="label">Current Stock</div>
            <div class="value value-sm">
                <span class="badge badge-<?= stock_status($product) === 'low' ? 'low' : (stock_status($product) === 'warn' ? 'warn' : 'ok') ?>">
                    <?= (int) $product['stock_quantity'] ?> units
                </span>
            </div>
        </div>
        <div class="stat">
            <div class="label">Total Movements</div>
            <div class="value value-sm"><?= count($movements) ?></div>
        </div>
    </div>

    <section class="card">
        <div class="section-head">
            <h2>All Movements (newest first)</h2>
        </div>

        <?php if (empty($movements)): ?>
            <p class="empty">No movements recorded for this product yet.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th class="num">Quantity</th>
                            <th>Note</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($movements as $m): ?>
                        <tr>
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
<?php elseif (!$notFound): ?>
    <div class="alert-box alert-info">Select a product above to view its stock movement history.</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>

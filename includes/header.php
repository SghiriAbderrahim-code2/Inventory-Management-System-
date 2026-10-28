<?php
/**
 * includes/header.php
 * ------------------------------------------------------------
 * Common <head>, navigation bar and flash-message rendering.
 *
 * Variables the including page may define:
 *   $base     – path prefix to the project root ('' or '../')
 *   $pageTitle– shown in <title> and the page heading area
 *   $active   – nav highlight: dashboard|products|stock-in|stock-out|history|reports|alerts
 * ------------------------------------------------------------
 */

require_once __DIR__ . '/functions.php';

$base      = $base ?? '';
$pageTitle = $pageTitle ?? 'Dashboard';
$active    = $active ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> · Inventory Management System</title>
    <link rel="stylesheet" href="<?= e($base) ?>assets/css/style.css">
</head>
<body>

<!-- ====================== TOP NAVIGATION ====================== -->
<header class="topbar">
    <div class="brand">Inventory<span>MS</span></div>
    <nav class="main-nav">
        <a href="<?= e($base) ?>index.php" class="<?= $active === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
        <a href="<?= e($base) ?>products/index.php" class="<?= $active === 'products' ? 'active' : '' ?>">Products</a>
        <a href="<?= e($base) ?>movements/stock_in.php" class="<?= $active === 'stock-in' ? 'active' : '' ?>">Stock In</a>
        <a href="<?= e($base) ?>movements/stock_out.php" class="<?= $active === 'stock-out' ? 'active' : '' ?>">Stock Out</a>
        <a href="<?= e($base) ?>movements/history.php" class="<?= $active === 'history' ? 'active' : '' ?>">History</a>
        <a href="<?= e($base) ?>reports/overview.php" class="<?= $active === 'reports' ? 'active' : '' ?>">Reports</a>
        <a href="<?= e($base) ?>reports/alerts.php" class="<?= $active === 'alerts' ? 'active' : '' ?>">Alerts</a>
    </nav>
</header>

<main class="container">

<?php // One-time success / error messages (flash) ---------------- ?>
<?php foreach (get_flashes() as $f): ?>
    <div class="flash flash-<?= e($f['type']) ?>" role="status">
        <span><?= e($f['message']) ?></span>
        <button type="button" class="flash-close" aria-label="Dismiss">&times;</button>
    </div>
<?php endforeach; ?>

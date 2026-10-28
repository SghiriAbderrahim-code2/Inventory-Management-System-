<?php
/**
 * products/delete.php — Delete product
 * ------------------------------------------------------------
 * POST-only endpoint (all write operations use POST).
 *
 * Business rule: a product may only be deleted when it has NO
 * stock movement history — otherwise an error flash is shown and
 * the user is returned to the product list.
 * ------------------------------------------------------------
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Reject anything that is not a POST (e.g. a hand-typed URL).
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flash('error', 'Invalid request method.');
    redirect('index.php');
}

// CSRF token check
if (!csrf_check()) {
    flash('error', 'Your session expired or the form was invalid. Please try again.');
    redirect('index.php');
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id <= 0) {
    flash('error', 'Invalid product selected.');
    redirect('index.php');
}

try {
    // 1) Load the product (also proves it exists)
    $stmt = $pdo->prepare('SELECT name FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    if (!$product) {
        flash('error', 'Product not found — it may have already been deleted.');
        redirect('index.php');
    }

    // 2) Business rule: block deletion when movement history exists
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM stock_movements WHERE product_id = ?');
    $stmt->execute([$id]);
    $movementCount = (int) $stmt->fetchColumn();

    if ($movementCount > 0) {
        flash(
            'error',
            'Cannot delete "' . $product['name'] . '": it has ' . $movementCount .
            ' stock movement(s) on record. Movement history must be kept for auditing.'
        );
        redirect('index.php');
    }

    // 3) Delete inside a transaction (safety net; FK is also RESTRICT)
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $pdo->commit();

    flash('success', 'Product "' . $product['name'] . '" deleted successfully.');
} catch (Throwable $e) {
    // Roll back if the transaction is still open
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Delete product failed: ' . $e->getMessage());
    flash('error', 'Could not delete the product. Please try again later.');
}

redirect('index.php');

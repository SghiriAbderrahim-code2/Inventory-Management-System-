<?php
/**
 * includes/footer.php
 * ------------------------------------------------------------
 * Closing markup for every page. Requires $base (set by the page)
 * so the script/CSS paths resolve from sub-folders too.
 * ------------------------------------------------------------
 */
?>
</main>

<footer class="footer">
    <p>Inventory Management System &middot; Native PHP + MySQL &copy; <?= date('Y') ?></p>
</footer>

<script src="<?= e($base ?? '') ?>assets/js/main.js"></script>
</body>
</html>

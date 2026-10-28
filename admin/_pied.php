<?php
/* Variable facultative : $editorScript (booléen), fourni par editer.php uniquement. */
?>
    </main>
    <?php if (!empty($editorScript)): ?>
        <script src="<?= e(url('js/admin-editeur.js') . '?v=' . filemtime(__DIR__ . '/../js/admin-editeur.js')) ?>" defer></script>
    <?php endif; ?>
</body>
</html>

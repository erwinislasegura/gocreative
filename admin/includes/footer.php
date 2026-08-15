        </main>
        <footer class="admin-footer">
            <span>© <?= date('Y') ?> Go Creative Chile</span>
            <span>Panel privado · no indexado</span>
        </footer>
    </div>
</div>
<script src="<?= e(admin_url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= e(admin_url('assets/js/admin.js?v=1.2.0')) ?>"></script>
<?php foreach (($pageScripts ?? []) as $pageScript): ?>
<script src="<?= e(admin_url('assets/js/' . ltrim((string) $pageScript, '/'))) ?>"></script>
<?php endforeach; ?>
</body>
</html>

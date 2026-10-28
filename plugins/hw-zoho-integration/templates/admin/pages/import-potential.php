<h1>Import Wedding</h1>

<div class="mb-3">
    <?php zoho_print_notices() ?>
</div>

<?= hwzi_get_template('admin/pages/import-potential/fetch-form', ['id' => $id]) ?>

<?php if($potential): ?>
    <?= hwzi_get_template('admin/pages/import-potential/import-form', ['id' => $id, 'potential' => $potential]) ?>
<?php endif; ?>
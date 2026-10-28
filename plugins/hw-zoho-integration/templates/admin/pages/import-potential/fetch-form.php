<div class="p-3 bg-white rounded">
    <form method="get">
        <input type="hidden" name="page" value="zoho-import-potential" />
        <div class="d-inline-block">
            <label for="wedding-id" class="form-label"><?= __('Wedding ID', 'hw-zoho') ?></label>
            <input type="text" name="id" value="<?= $id ?>" class="form-control" id="wedding-id" placeholder="<?= __('Insert Wedding ID', 'hw-zoho') ?>">
        </div>
        <button type="submit" class="btn btn-primary align-bottom ms-3"><?= __('Fetch Data', 'hw-zoho') ?></button>
    </form>
</div>
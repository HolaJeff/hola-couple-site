<div id="missing-zoho-id-tool" class="mt-3">
    <form class="bg-white p-3 border rounded">
        <input type="hidden" name="action" value="hwzi_get_missing_zoho_id_by_resource" />
        <strong><?= __('Filters', 'hw-zoho') ?></strong>
        <div class="d-flex mt-1">
            <div>
                <label class="form-label" for="resource"><?= __('Resource', 'hw-zoho') ?></label>
                <select id="resource" name="resource" class="form-select">
                    <option value="invoices"><?= __('Invoices', 'hw-zoho') ?></option>
                    <option value="quotes"><?= __('Quotes', 'hw-zoho') ?></option>
                    <option value="room-blocks"><?= __('Room Blocks', 'hw-zoho') ?></option>
                </select>
            </div>
            <div class="ms-3 d-flex flex-column justify-content-between">
                <label class="d-block mb-2"><?= __('Status', 'hw-zoho') ?></label>
                <div>
                    <div class="form-check form-check-inline">
                        <input name="status[]" class="form-check-input mt-1" type="checkbox" id="status-publish" value="publish" checked>
                        <label class="form-check-label" for="status-publish"><?= __('Published') ?></label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input name="status[]" class="form-check-input mt-1" type="checkbox" id="status-draft" value="draft">
                        <label class="form-check-label" for="status-draft"><?= __('Draft') ?></label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input name="status[]" class="form-check-input mt-1" type="checkbox" id="status-trash" value="trash">
                        <label class="form-check-label" for="status-trash"><?= __('Trash') ?></label>
                    </div>
                </div>
            </div>
            <div class="ms-3 d-flex flex-column justify-content-between">
                <label class="form-label" for="date-filter"><?= __('Show Only Weddings After', 'hw-zoho') ?></label>
                <input id="date-filter" readonly class="form-control mt-1" type="text" value="<?= (new DateTime('NOW'))->format('m-d-Y') ?>">
                <input id="date-filter-alt" type="hidden" name="wedding_date" value="<?= (new DateTime('NOW'))->format('Y-m-d') ?>" />
            </div>
            <div class="ms-3 align-self-end">
                <button data-submit class="btn btn-primary" type="submit"><?= __('Search', 'hw-zoho') ?></button>
            </div>
        </div>
    </form>
    <div data-results class="mt-3"></div>
</div>
<?php
$test_data = file_get_contents( HW_ZOHO_INCLUDES_PATH . '/Admin/Test/test_data.json');
?>
<h2>Test</h2>
<div class="notices">
    <?= zoho_print_notices() ?>
</div>
<form method="POST">
    <div>
        <textarea name="data" style="width: 90%; height:450px; display: block; margin-bottom: 20px;"><?= $test_data ?></textarea>
    </div>
    <label>
        <input type="checkbox" name="update_test_data" value="1" />
        <?= __('Update test data with modified from textarea.') ?>
    </label>
    <br>
    <br>
    <input type="hidden" name="action" value="zoho_send_test" />
    <input type="submit" value="Send test">
</form>
<h2><?= __('Zoho CRM Weddings') ?></h2>
<div class="wrap">
    <form method="get">
        <input type="hidden" name="page" value="zoho-crm-weddings" />
        <?php
        $table->search_box( 'search', 'search_id' );
        $table->display();
        ?>
    </form>
</div>
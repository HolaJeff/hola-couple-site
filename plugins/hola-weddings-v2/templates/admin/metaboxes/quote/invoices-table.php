<?php if($query->have_posts()): 
    
    $columns = hw_get_invoices_columns();
    ?>
    <table class="wp-list-table widefat fixed striped table-view-list posts">
        <thead>
            <tr>
                <?php foreach($columns as $slug => $column): ?>
                    <th><?= $column ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach($query->posts as $post): ?>
                <tr>
                    <?php foreach($columns as $slug => $column): ?>
                        <td><?= hw_display_invoices_columns($post, $slug) ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach;?>
        </tbody>
    </table>
<?php else: ?>
    <p><?= __('There are no invoices for this post.', 'hola-weddings') ?></p>
<?php endif;
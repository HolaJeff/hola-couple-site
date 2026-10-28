<?php if($query->have_posts()): ?>
    <table class="table table-striped">
        <thead>
            <tr>
                <th><?= __('ID') ?></th>
                <th><?= __('Title') ?></th>
                <th><?= __('From') ?></th>
                <th><?= __('To') ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php while($query->have_posts()): $query->the_post(); 
            
                $from = get_field('from');
                $to = get_field('to');
                ?>
                <tr>
                    <td>
                        <?= get_the_ID() ?>
                    </td>
                    <td>
                        <a href="<?= get_edit_post_link() ?>"><?= get_the_title() ?></a>
                    </td>
                    <td>
                        <?= $from ? (new DateTime($from))->format('F d, Y') : '' ?>
                    </td>
                    <td>
                        <?= $to ? (new DateTime($to))->format('F d, Y') : '' ?>
                    </td>
                    <td>
                        <a href="<?= get_edit_post_link() ?>" target="_blank">
                            Edit
                        </a>
                    </td>
                </tr>
            <?php endwhile; wp_reset_postdata(); ?>
        </tbody>
    </table>
<?php else: ?>
    <p><?= __('No prices found.') ?></p>
<?php endif; ?>

<a class="btn btn-primary" href="<?= 'edit.php?post_type=dynamic_prices&room_filter=' . get_the_ID() ?>" target="_blank"><?= __('All Room Dynamic Prices') ?></a>

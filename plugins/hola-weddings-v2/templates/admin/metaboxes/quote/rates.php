<?php 
$rates_per_day = $quote->getRates();

if($rates_per_day): ?>
    <table class="wp-list-table widefat fixed striped table-view-list posts">
        <thead>
            <tr>
                <th><?= __('Date', 'hola-weddings'); ?></th>
                <?php foreach(get_person_types() as $type): ?>
                    <th>
                        <?= $type ?>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach($rates_per_day as $date => $rates): ?>
                <tr>
                    <td><?= (new \DateTime($date))->format('d F, Y') ?></td>
                    <?php foreach(get_person_types() as $slug => $type): ?>
                        <td>
                            <?= !empty($rates[$slug]) ? hw_format_price($rates[$slug]) : '--' ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif;
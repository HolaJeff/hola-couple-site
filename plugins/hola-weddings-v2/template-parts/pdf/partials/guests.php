<h6 class="uppercase"><?= __('Guests', 'hola-weddings') ?></h6>
<table class="guest-table box">
    <?php foreach($guests as $i => $guest): ?>
        <tr>
            <td style="width: 10%;">#<?= $i+1 ?></td>
            <td style="width: 40%;"><?= ucwords($guest['title'] . '  '. hw_guest_full_name( $guest )) ?></td>
            <td style="width: 40%;"><?= sprintf(__('Date of birth: %s'), (new DateTime($guest['date']))->format('m/d/Y')) ?></td>
            <td style="width: 10%;"><?= sprintf(__('Age: %s'), $guest['age'] ) ?></td>
        </tr>
    <?php endforeach; ?>
</table>
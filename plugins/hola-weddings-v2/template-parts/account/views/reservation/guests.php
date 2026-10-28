<?php 
$guests = hw_get_quote_guests();

if($guests): ?>
    <div class="panel">
        <div class="title"><?= __('Guests', 'hola-weddings') ?></div>     
        <div class="body">
            <ul class="list-unstyled" style="width: 100%">
                <?php if(!empty($guests)): ?>
                    <li>
                        <table style="width:100%">
                            <?php foreach($guests as $guest): ?>
                                <tr>
                                    <td><?= ucfirst($guest['title']) ?> <?= ucwords( hw_guest_full_name($guest) ) ?></td>
                                    <td>Date of Birth: <?= (new \DateTime($guest['date']))->format('d F Y'); ?></td>
                                    <td>Age: <?= $guest['age']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>
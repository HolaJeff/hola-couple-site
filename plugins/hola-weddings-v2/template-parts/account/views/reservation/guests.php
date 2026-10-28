<?php 
$guests = hw_get_quote_guests();

if(!empty($guests)): ?>
    <ul class="list-unstyled" style="width: 100%">
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
    </ul>
<?php endif; ?>
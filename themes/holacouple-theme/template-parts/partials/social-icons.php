<?php
$social_links = hw_get_network_social_links();

if($social_links):
?>
<ul class="nav justify-content-center justify-content-md-end">
    <?php if($social_links['facebook']): ?>
        <li class="nav-item">
            <a class="nav-link" href="<?= $social_links['facebook'] ?>" target="_blank">
                <i class="fab fa-facebook"></i>
            </a>
        </li>
    <?php endif; ?>
    <?php if($social_links['twitter']): ?>
        <li class="nav-item">
            <a class="nav-link" href="<?= $social_links['twitter'] ?>" target="_blank">
                <i class="fab fa-twitter"></i>
            </a>
        </li>
    <?php endif; ?>
    <?php if($social_links['instagram']): ?>
        <li class="nav-item">
            <a class="nav-link" href="<?= $social_links['instagram'] ?>" target="_blank">
                <i class="fab fa-instagram"></i>
            </a>
        </li>
    <?php endif; ?>
    <?php if($social_links['pinterest']): ?>
        <li class="nav-item">
            <a class="nav-link" href="<?= $social_links['pinterest'] ?>" target="_blank">
                <i class="fab fa-pinterest"></i>
            </a>
        </li>
    <?php endif; ?>
</ul>
<?php endif;
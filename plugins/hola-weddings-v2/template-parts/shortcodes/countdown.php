<div id="<?= $id ?>" class="hw-countdown simply-countdown skin-<?= $attrs['skin'] ?>"></div>
<script type="text/javascript">
    jQuery(function($){
        simplyCountdown("#<?= $id ?>", {
            year: <?= $date->format('Y') ?>,
            month: <?= $date->format('n') ?>,
            day: <?= $date->format('j') ?>,
        });
    });
</script>
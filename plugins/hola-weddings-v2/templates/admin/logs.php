<?php

$page_slug = 'hw-site-logs';
$page = 'default';
$current_log = '';

if( isset( $_GET[ 'tab' ] ) ) {
  $page = $_GET[ 'tab' ];
}

$current_log = $logs[$page] ?? '';
?>

<?= hw_print_notices(); ?>

<h2 class="nav-tab-wrapper">
    <?php foreach($logs as $key => $log): ?>
        <a href="?page=<?= $page_slug ?>&tab=<?= $key ?>" class="nav-tab <?php echo $page == $key ? 'nav-tab-active' : ''; ?>"><?= $log['page_title'] ?></a>
    <?php endforeach ?>
</h2>

<?php if(!empty($current_log)): 

    $content = hw_read_log($current_log['filepath'])
    ?>
    <h1><?= $current_log['page_title'] ?></h1>
    <div id="log" class="bg-white py-3 px-4 border" style="overflow-y: scroll; height: 400px">
        <?= nl2br($content) ?>
    </div>
    <form id="clear-log" method="post" action="?page=hw-site-logs&tab=<?= $current_log['key'] ?>" class="mt-3">  
        <input type="hidden" name="action" value="hw_clear_log" />
        <input type="hidden" name="log_key" value="<?= $current_log['key'] ?>" />
        <button type="submit" class="btn btn-primary">Clear Log</button>
    </form>
<?php endif; ?>
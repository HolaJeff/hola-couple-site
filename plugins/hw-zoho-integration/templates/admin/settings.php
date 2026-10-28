<?php

$page = 'connection';
if( isset( $_GET[ 'tab' ] ) ) {
  $page = $_GET[ 'tab' ];
}
?>
<h1>Zoho CRM</h1>
<h2 class="nav-tab-wrapper">
    <a href="?page=zoho-options&tab=connection" class="nav-tab <?php echo $page == 'connection' ? 'nav-tab-active' : ''; ?>">Connection</a>
    <a href="?page=zoho-options&tab=test" class="nav-tab <?php echo $page == 'test' ? 'nav-tab-active' : ''; ?>">Test</a>
</h2>

<?php hwzi_get_template('admin/tabs/' . $page ); ?>
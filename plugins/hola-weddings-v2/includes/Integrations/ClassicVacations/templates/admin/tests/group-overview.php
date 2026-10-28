<?php
use HW\Integrations\ClassicVacations\CRM\Groups\Group;

?>
<h3>Group Overview</h3>
<div class="p-3 bg-white rounded mt-3">
    <form id="classic-vacation-group" method="get" action="<?= network_admin_url( 'admin.php?page=classic-vacations-tests&page=group-overview' ) ?>"> 
        <input type="hidden" name="page" value="classic-vacations-tests" />
        <div class="row mb-3">
            <div class="form-group col-4">
                <label for="group-id" class="form-label"><?= __('Group ID', 'hola-weddings') ?></label>
                <input type="text" name="group_id" value="<?= isset($_GET['group_id']) ? $_GET['group_id'] : '' ?>" class="form-control d-inline-block" id="client-id" placeholder="<?= __('Insert Group ID', 'hola-weddings') ?>">
            </div>
        </div>
        <button class="btn btn-primary"><?= __('Fetch', 'hola-weddings') ?></button>
    </form>
</div>

<?php if(isset($_GET['group_id']) && !empty($_GET['group_id'])):

    $id = $_GET['group_id'];
    $response = Group::getGroupOverview($id);
    ?>
    <div class="p-3 bg-white rounded mt-3">
        <h3>Group Overview</h3>
        <?php
        if(hw_cv_is_api_exception($response)){
            echo sprintf('API Exception code %s - %s', $response->getErrorCode(), $response->getErrorDescription());
        }else{
            echo '<pre>';
            var_dump($response);
            echo '</pre>';
        } 
        ?>
    </div>
<?php endif;
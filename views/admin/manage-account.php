<div class="row">
    <div class="col-md-8">
        <h3 class="nomargin"><?php echo $account->domain; ?></h3>
    </div>
    <div class="col-md-4">
        <b><?php echo $lang->get('package'); ?>: </b> <?php echo $account->plan; ?><br>
        <b><?php echo $lang->get('ip_address'); ?>: </b> <?php echo $account->ip; ?>
    </div>
</div>
<div class="row">
    <div class="col-md-12">
        <div class="well text-center">
            <a href="<?php echo $router->generate('admin-service-cpanel-create', array('id' => $client->id, 'service_id' => $service->id)); ?>" class="btn btn-secondary">Create Account</a>
            <a href="<?php echo $router->generate('admin-service-cpanel-suspend', array('id' => $client->id, 'service_id' => $service->id)); ?>" class="btn btn-warning">Suspend Account</a>
            <a href="<?php echo $router->generate('admin-service-cpanel-unsuspend', array('id' => $client->id, 'service_id' => $service->id)); ?>" class="btn btn-warning">Unsuspend Account</a>
            <a href="<?php echo $router->generate('admin-service-cpanel-terminate', array('id' => $client->id, 'service_id' => $service->id)); ?>" class="btn btn-danger" onclick="return confirm('<?php echo $lang->get('confirm_delete'); ?>')">Terminate Account</a>
        </div>
    </div>
</div>

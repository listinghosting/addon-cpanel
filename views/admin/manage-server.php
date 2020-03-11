<div class="row">
    <div class="col-md-2">
        <img src="<?php echo $assets->image('Cpanel::logo.png'); ?>" width="100%">
    </div>
    <div class="col-md-1 text-right">
        <div class="btn-group">
            <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown">
                <?php echo $lang->get('options'); ?> <span class="caret"></span>
            </button>
            <ul class="dropdown-menu" role="menu">
                <li><a href="<?php echo $router->generate('admin-server-cpanel-restart-service', array('id' => $group->id, 'server_id' => $server_id, 'service' => 'httpd')); ?>"><?php echo $lang->get('restart_httpd'); ?></a></li>
                <li><a href="<?php echo $router->generate('admin-server-cpanel-restart-service', array('id' => $group->id, 'server_id' => $server_id, 'service' => 'mysql')); ?>"><?php echo $lang->get('restart_mysql'); ?></a></li>
                <li class="divider"></li>
                <li><a href="<?php echo $router->generate('admin-server-cpanel-reboot', array('id' => $group->id, 'server_id' => $server_id)); ?>"><?php echo $lang->get('reboot_server'); ?></a></li>
            </ul>
        </div>
    </div>
    <div class="col-md-9 text-right">

        <h2 class="nomargin"><?php echo $server['hostname']; ?></h2>
        <b><?php echo $lang->get('version'); ?>:</b> <?php echo $server['version']; ?>
        <b><?php echo $lang->get('load_averages'); ?>:</b> <?php echo $server['loadavg']['one'].', '.$server['loadavg']['five'].', '.$server['loadavg']['fifteen']; ?>
    </div>

</div>

<div class="row">
    <div class="col-sm-12">
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th><?php echo $lang->get('domain'); ?> / <?php echo $lang->get('user'); ?></th>
                    <th><?php echo $lang->get('package'); ?></th>
                    <th><?php echo $lang->get('diskspace'); ?></th>
                    <th><?php echo $lang->get('bandwidth'); ?></th>
                    <th><?php echo $lang->get('status'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($server['accounts'] as $account): ?>
                <tr>
                    <td><?php echo $account->domain; ?> / <?php echo $account->user; ?></td>
                    <td><?php echo $account->plan; ?></td>
                    <td><?php echo $account->diskused.' / '.$account->disklimit; ?></td>
                    <td><?php echo $server['bandwidth'][end($account->domain)]['usage']; ?> / <?php echo $server['bandwidth'][end($account->domain)]['limit']; ?></td>
                    <td>
                        <?php if ($account->suspended == '1'): ?>
                            <span class="label label-danger"><?php echo $lang->get('suspended'); ?></span>
                        <?php else: ?>
                            <span class="label label-success"><?php echo $lang->get('active'); ?></span>
                        <?php endif; ?>
                    </td>

                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

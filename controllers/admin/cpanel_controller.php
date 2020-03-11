<?php

class CpanelController extends AdminController
{
    public $api;

    public function onLoad()
    {
        parent::onLoad();

        $this->api = App::factory('\Addon\Cpanel\Libraries\Cpanel');
    }

    public function manageHosting($client_id, $purchase_id)
    {
        $client = Client::find($client_id);
        $purchase = ProductPurchase::find($purchase_id);
        $hosting = $purchase->Hosting()->first();
        
        $server = $hosting->Server()->first();
        $server_group = $server->ServerGroup()->first();
        $server_module = $server_group->ServerModule()->first();

        $this->api->initServer($server, $server_group, $server_module);

        $account = $this->api->retrieveService($hosting->id);

        $this->view->set('client', $client);
        $this->view->set('service', $purchase);

        if (!isset($account->domain)) {
            // Account does not exist.
            $this->view->display('cpanel::admin/no-account.php');
        } else {
            $this->view->set('account', $account);
            $this->view->display('cpanel::admin/manage-account.php');
        }
    }

    public function manageServer($id, $server_id)
    {
        $server_group = ServerGroup::find($id);
        $server = Server::find($server_id);

        $server_data = array('Server' => $server);

        $custom_fields = App::factory('\Whsuite\CustomFields\CustomFields')->getGroup('serverdata_cpanel', $server->id, false);

        foreach ($custom_fields['fields'] as $field) {
            $server_data['CustomFields'][$field['slug']] = $field['value']['value'];
        }
        
        // Test server connection
        if(!$this->api->testConnection($server_data)) {
            die($this->lang->get('server_connection_failed'));
        }

        $server_data = $this->api->serverDetails($server_id);

        $this->view->set('server', $server_data);
        $this->view->set('group', $server_group);
        $this->view->set('server_id', $server->id);
        $this->view->display('cpanel::admin/manage-server.php');
    }

    public function rebootServer($id, $server_id, $force = 0)
    {
        $server = Server::find($server_id);

        if ($force == '1') {
            $force_reboot = true;
        } else {
            $force_reboot = false;
        }

        $reboot = $this->api->rebootServer($server_id, $force_reboot);

        if($reboot) {
            App::get('session')->setFlash('success', $this->lang->get('request_submitted'));
        } else {
            App::get('session')->setFlash('error', $this->lang->get('error_submitting_request'));
        }
        header("Location: ".App::get('router')->generate('admin-server-manage', array('id' => $id, 'server_id' => $server_id)));
    }

    public function restartService($id, $server_id, $service)
    {
        $server = Server::find($server_id);

        if ($service == 'httpd') {
            $restart = $this->api->restartService($server_id, 'httpd');
        } elseif($service == 'mysql') {
            $restart = $this->api->restartService($server_id, 'mysql');
        } else {
            $restart = false;
        }

        if($restart) {
            App::get('session')->setFlash('success', $this->lang->get('request_submitted'));
        } else {
            App::get('session')->setFlash('error', $this->lang->get('error_submitting_request'));
        }
        header("Location: ".App::get('router')->generate('admin-server-manage', array('id' => $id, 'server_id' => $server_id)));
    }

    public function createAccount($client_id, $purchase_id)
    {
        $client = Client::find($client_id);
        $purchase = ProductPurchase::find($purchase_id);
        $hosting = $purchase->Hosting()->first();

        $server = $hosting->Server()->first();
        $server_group = $server->ServerGroup()->first();
        $server_module = $server_group->ServerModule()->first();

        $this->api->initServer($server, $server_group, $server_module);

        $account = $this->api->createService($purchase, $hosting);

        if($account) {
            App::get('session')->setFlash('success', $this->lang->get('account_created'));
        } else {
            App::get('session')->setFlash('error', $this->lang->get('error_creating_account'));
        }
        header("Location: ".App::get('router')->generate('admin-client-service', array('id' => $client->id, 'service_id' => $purchase->id)));
    }

    public function suspendAccount($client_id, $purchase_id)
    {
        $client = Client::find($client_id);
        $purchase = ProductPurchase::find($purchase_id);
        $hosting = $purchase->Hosting()->first();

        $server = $hosting->Server()->first();
        $server_group = $server->ServerGroup()->first();
        $server_module = $server_group->ServerModule()->first();

        $this->api->initServer($server, $server_group, $server_module);

        if($this->api->suspendService($purchase, $hosting)) {
            $purchase->status = '2';
            $purchase->save();

            App::get('session')->setFlash('success', $this->lang->get('account_suspended'));
        } else {
            App::get('session')->setFlash('error', $this->lang->get('error_suspending_account'));
        }
        header("Location: ".App::get('router')->generate('admin-client-service', array('id' => $client->id, 'service_id' => $purchase->id)));
    }

    public function unsuspendAccount($client_id, $purchase_id)
    {
        $client = Client::find($client_id);
        $purchase = ProductPurchase::find($purchase_id);
        $hosting = $purchase->Hosting()->first();

        $server = $hosting->Server()->first();
        $server_group = $server->ServerGroup()->first();
        $server_module = $server_group->ServerModule()->first();

        $this->api->initServer($server, $server_group, $server_module);

        if($this->api->unsuspendService($purchase, $hosting)) {

            $purchase->status = '1';
            $purchase->save();

            App::get('session')->setFlash('success', $this->lang->get('account_unsuspended'));
        } else {
            App::get('session')->setFlash('error', $this->lang->get('error_unsuspending_account'));
        }
        header("Location: ".App::get('router')->generate('admin-client-service', array('id' => $client->id, 'service_id' => $purchase->id)));
    }

    public function terminateAccount($client_id, $purchase_id)
    {
        $client = Client::find($client_id);
        $purchase = ProductPurchase::find($purchase_id);
        $hosting = $purchase->Hosting()->first();

        $server = $hosting->Server()->first();
        $server_group = $server->ServerGroup()->first();
        $server_module = $server_group->ServerModule()->first();

        $this->api->initServer($server, $server_group, $server_module);

        if($this->api->terminateService($purchase, $hosting)) {
            App::get('session')->setFlash('success', $this->lang->get('account_terminated'));
        } else {
            App::get('session')->setFlash('error', $this->lang->get('error_terminating_account'));
        }
        header("Location: ".App::get('router')->generate('admin-client-service', array('id' => $client->id, 'service_id' => $purchase->id)));
    }
}

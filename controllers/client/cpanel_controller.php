<?php

class CpanelController extends ClientController
{
    public $api;

    public function onLoad()
    {
        parent::onLoad();

        $this->api = App::factory('\Addon\Cpanel\Libraries\Cpanel');
    }

    public function manageHosting($id)
    {
        if ($this->logged_in) {
            $purchase = ProductPurchase::find($id);

            if ($this->client->id === $purchase->client_id) {
                $hosting = $purchase->Hosting()->first();
                $server = $hosting->Server()->first();
                $server_group = $server->ServerGroup()->first();
                $server_module = $server_group->ServerModule()->first();

                $this->api->initServer($server, $server_group, $server_module);
                
                $account = $this->api->retrieveService($hosting->id);

                $this->view->set('service', $purchase);

                if (!isset($account->domain)) {
                    // Account does not exist.
                    $this->view->display('cpanel::client/no-account.php');
                } else {
                    $this->view->set('account', $account);
                    $this->view->display('cpanel::client/manage-account.php');
                }
            }
        }
    }
}

<?php
namespace Addon\Cpanel\Libraries;
use Addon\Cpanel\Libraries\Api\Xmlapi;

class Cpanel
{
    public $server;
    public $server_group;
    public $server_module;

    public $hosting;
    public $cmd;

    public function initServer($server, $server_group, $server_module)
    {
        $this->server = $server;
        $this->server_group = $server_group;
        $this->server_module = $server_module;
    }

    public function updateRemote($purchase_id)
    {
        // Load the account and server details
        $purchase = \ProductPurchase::find($purchase_id);
        $hosting = $purchase->Hosting()->first();
        if (!empty($hosting) && isset($hosting->server_id) && $hosting->server_id > 0 && $this->checkServer($hosting->server_id)) {
            if ($hosting->last_sync < (time() - 3600)) {
                $this->loadAccount($hosting->id);

                $service = $this->retrieveService($hosting->id);

                if (isset($service->diskusage)) {
                    $hosting->diskspace_usage = preg_replace("/[^0-9,.]/", "", $service->diskused);
                } else {
                    return false;
                }

                $bandwidth = $this->cmd->showbw(array('searchtype' => 'user', 'search' => end($service->user)));
                $bandwidth = $bandwidth->bandwidth->acct;

                $bandwidth_usage = end($bandwidth->usage);

                $hosting->bandwidth_usage = round($bandwidth_usage / (1024*1024), 2);
                $hosting->last_sync = time();
                $hosting->save();
            }
            return true;
        }
    }

    public function addAddon($product_addon_id, $addon_purchase_id, $purchase_id)
    {
        $purchase = \ProductPurchase::find($purchase_id);
        $hosting = $purchase->Hosting()->first();

        $this->loadAccount($hosting->id);

        return true;
    }

    public function updateAddon($product_addon_id, $addon_purchase_id, $purchase_id)
    {
        $purchase = \ProductPurchase::find($purchase_id);
        $hosting = $purchase->Hosting()->first();

        $this->loadAccount($hosting->id);

        return true;
    }

    public function deleteAddon($product_addon_id, $addon_purchase_id, $purchase_id)
    {
        $purchase = \ProductPurchase::find($purchase_id);
        $hosting = $purchase->Hosting()->first();

        $this->loadAccount($hosting->id);

        return true;
    }

    private function loadAccount($hosting_id)
    {
        // Load the hosting package
        $this->hosting = \Hosting::find($hosting_id);

        // Load the server
        $this->server = $this->hosting->Server()->first();

        // Load the server group
        $this->server_group = $this->server->ServerGroup()->first();
    }

    public function testConnection($server_data)
    {
        if (
            !isset($server_data['Server']['main_ip']) || $server_data['Server']['main_ip'] == '' ||
            !isset($server_data['Server']['username']) || $server_data['Server']['username'] == '' ||
            !isset($server_data['CustomFields']['cpanel_server_remote_access_key']) ||
            $server_data['CustomFields']['cpanel_server_remote_access_key'] == ''
        ) {
            return false;
        }

        $api = new Xmlapi($server_data['Server']['main_ip']);
        $api->hash_auth($server_data['Server']['username'], $server_data['CustomFields']['cpanel_server_remote_access_key']);
        try {
            $api = $api->version();

            if (! empty($api)) {
                if (isset($api->error)) {
                    return false;
                }
            }



        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    private function createConnection($ip, $username, $hash)
    {
        $this->cmd = new Xmlapi($ip);
        $this->cmd->hash_auth($username, $hash);
    }

    public function serverConnection()
    {
        // Load the cPanel server custom fields
        $custom_fields = \App::factory('\Whsuite\CustomFields\CustomFields')->getGroup('serverdata_cpanel', $this->server->id, false);

        $fields = array();

        foreach ($custom_fields['fields'] as $field) {
            if (isset($field['value']['value']) && $field['value']['value'] !='') {
                $fields[$field['slug']] = \App::get('security')->decrypt($field['value']['value']);
            } else {
                $fields[$field['slug']] = null;
            }
        }

        $this->cmd = new Xmlapi($this->server->main_ip);
        $this->cmd->hash_auth(\App::get('security')->decrypt($this->server->username), $fields['cpanel_server_remote_access_key']);
    }

    public function productFields()
    {
        // Get server details
        $this->serverConnection();

        $forms = \App::factory('\Whsuite\Forms\Forms');

        $form = '';
        try {
            $remote_packages = $this->cmd->listpkgs();
            $package_list = array();

            if (! empty($remote_packages)) {
                foreach ($remote_packages as $pkg) {
                    $pkg_name = (string)$pkg->name;
                    $package_list[$pkg_name] = $pkg_name;
                }


                $form .= $forms->select('PackageMeta.cpanel_package_name', \App::get('translation')->get('package'), array('options' => $package_list));
                $form .= $forms->checkbox('PackageMeta.cpanel_is_reseller', \App::get('translation')->get('is_reseller'));
            } else {
                $form = '<div class="alert alert-danger">No remote cPanel packages found.</div>';
            }

        } catch (\Exception $e) {
            $form = \App::get('translation')->get('server_connection_failed');
        }

        echo $form;
    }


    public function productPaid($item)
    {
        return;
    }

    public function createService($purchase, $hosting)
    {
        $this->serverConnection();
        $product = $purchase->Product()->first();
        $product_data = $product->ProductData()->get();

        $service_fields = array();

        foreach ($product_data as $p_data) {
            $service_fields[$p_data->slug] = $p_data->value;
        }

        $security = \App::get('security');

        if ($product->included_ips != '0') {
            $ip = $product->included_ips = '1';
        } else {
            $ip = '0';
        }

        if ($hosting->username == '') {
            // No username was set - create one and update the record.
            $hosting->username = $this->generateUsername($hosting->domain);
            $hosting->save();
        }

        $data = array(
            'domain' => $hosting->domain,
            'username' => $hosting->username,
            'password' => $security->decrypt($hosting->password),
            'plan' => $service_fields['cpanel_package_name'],
            'reseller' => $service_fields['cpanel_is_reseller'],
            'ip' => $ip,
        );

        $account = $this->cmd->createacct($data);

        if (isset($account->result->status) && $account->result->status == '1') {
            $hosting_data = array(
                'domain' => $hosting->domain,
                'nameservers' => $this->server->nameservers,
                'diskspace_limit' => '0',
                'diskspace_usage' => '0',
                'bandwidth_limit' => '0',
                'bandwidth_usage' => '0',
                'status' => '1',
                'username' => $hosting->username,
                'password' => $security->decrypt($hosting->password)
            );

            return $hosting_data;
        }

        return false;
    }

    public function renewService($hosting_id)
    {
        // cPanel accounts dont need to do anything here.
        return true;
    }

    public function terminateService($purchase, $hosting)
    {
        $this->serverConnection();

        $account = $this->cmd->listaccts('domain', $hosting->domain);
        $terminate = $this->cmd->removeacct(end($account->acct->user));
        if (isset($terminate->result->status) && $terminate->result->status == '1') {
            return true;
        }
        return false;
    }

    public function suspendService($purchase, $hosting)
    {
        $this->serverConnection();

        $account = $this->cmd->listaccts('domain', $hosting->domain);
        $terminate = $this->cmd->suspendacct(end($account->acct->user));
        if (isset($terminate->result->status) && $terminate->result->status == '1') {
            return true;
        }
        return false;
    }

    public function unsuspendService($purchase, $hosting)
    {
        $this->serverConnection();

        $account = $this->cmd->listaccts('domain', $hosting->domain);
        $terminate = $this->cmd->unsuspendacct(end($account->acct->user));
        if (isset($terminate->result->status) && $terminate->result->status == '1') {
            return true;
        }
        return false;
    }

    public function retrieveService($hosting_id)
    {
        $hosting = \Hosting::find($hosting_id);
        $this->serverConnection($hosting->server_id);

        $account = $this->cmd->listaccts('domain', $hosting->domain);
        $account_data = null;
        foreach ($account as $a) {
            if (end($a->domain) == $hosting->domain) {
                $account_data = $a;
                break;
            }
        }
        return $account_data;
    }

    public function serverDetails()
    {
        $this->serverConnection();

        $server_details = array();
        $server_details['hostname'] = end($this->cmd->gethostname()->hostname);

        return $server_details;
    }

    /**
     * Format Bytes (Src: http://php.net/manual/de/function.filesize.php)
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    private function checkServer($server_id)
    {
        $server = \Server::find($server_id);

        if ($server) {
            $group = $server->ServerGroup()->first();
            $module = $group->ServerModule()->first();

            if ($module->slug == 'cpanel') {
                return true;
            }
        }
        return false;
    }

    private function generateUsername($domain)
    {
        // Strip special chars
        $username = preg_replace("/[^A-Za-z0-9 ]/", '', $domain);

        // Shorten to 6 chars
        $username = substr($username, 0, 6);

        // Check that the username does not contain reserved/unwanted phrases
        $reserved = array(
            'cpanel' => 'cp4n31',
            'whm' => 'w4m',
            'admin' => 'a6dm1n',
            'root' => 'r007',
            'administrator' => 'a6m1nistrator'
        );

        if (array_key_exists($username, $reserved)) {
            // Username contains one or more reserved words. To get around this, we can simply
            // use the replacement option provided by the reserved words list.
            $username = str_replace(array_flip($reserved), $reserved, $username);
        }

        // Add a random 2 digit number.
        $username .= mt_rand(10, 99);

        // Check the length to ensure we're at 8 chars.
        // If we're not, we'll add some random chars.
        if (strlen($username) < 8) {
            $chars = 'abcdefghijklmnopqrstuvwxyz';

            $length_required = 8 - strlen($username);

            $random_chars = substr(str_shuffle(str_repeat($chars), 10), 0, $length_required);

            $username = $username . $random_chars;

        }

        return $username;
    }
}

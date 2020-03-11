<?php

App::get('hooks')->startListening('client-load-service-cpanel', 'cpanel-update-client-service', function($purchase_id) {
  App::factory('\Addon\Cpanel\Libraries\Cpanel')->updateRemote($purchase_id);
});

App::get('hooks')->startListening('admin-load-service-cpanel', 'admin-update-client-service', function($purchase_id) {
  App::factory('\Addon\Cpanel\Libraries\Cpanel')->updateRemote($purchase_id);
});

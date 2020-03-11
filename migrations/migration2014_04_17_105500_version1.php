<?php
namespace Addon\Cpanel\Migrations;

use \App\Libraries\BaseMigration;

class Migration2014_04_17_105500_version1 extends BaseMigration
{
    public function up($addon_id)
    {
        // Server Module
        $module = new \ServerModule();
        $module->name = 'cPanel';
        $module->slug = 'cpanel';
        $module->addon_id = $addon_id;
        $module->save();

        // Data Group
        $data_group = new \DataGroup();
        $data_group->slug = 'serverdata_cpanel';
        $data_group->name = 'cpanel_server_custom_fields';
        $data_group->addon_id = $addon_id;
        $data_group->is_editable = '0';
        $data_group->is_active = '1';
        $data_group->save();

        // Data fields
        $data_field = new \DataField();
        $data_field->slug = 'cpanel_server_remote_access_key';
        $data_field->data_group_id = $data_group->id;
        $data_field->title = 'cpanel_remote_access_key';
        $data_field->type = 'textarea';
        $data_field->help_text = 'cpanel_server_remote_access_key_help_text';
        $data_field->is_editable = '1';
        $data_field->is_staff_only = '1';
        $data_field->validation_rules = 'required';
        $data_field->sort = '1';
        $data_field->save();
    }

    public function down($addon_id)
    {
        \ServerModule::where('addon_id', '=', $addon_id)->delete();

        $data_group = \DataGroup::where('slug', '=', 'serverdata_cpanel')->first();

        $data_fields = $data_group->DataField()->get();
        foreach ($data_fields as $field) {
            $field_values = $field->DataFieldValue()->delete();

            $field->delete();
        }

        $data_group->delete();
    }
}

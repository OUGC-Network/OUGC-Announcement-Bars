<?php

/***************************************************************************
 *
 *    OUGC Announcement Bars plugin (/inc/plugins/ougc/AnnouncementBars/hooks/admin.php)
 *    Author: Omar Gonzalez
 *    Copyright: © 2012 Omar Gonzalez
 *
 *    Website: https://ougc.network
 *
 *    This plugin will allow administrators and super moderators to manage announcement bars.
 *
 ***************************************************************************
 ****************************************************************************
 * This program is protected software: you can make use of it under
 * the terms of the OUGC Network EULA as detailed by the included
 * "EULA.TXT" file.
 *
 * This program is distributed with the expectation that it will be
 * useful, but WITH LIMITED WARRANTY; with a limited warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * OUGC Network EULA included in the "EULA.TXT" file for more details.
 *
 * You should have received a copy of the OUGC Network EULA along with
 * the package which includes this file.  If not, see
 * <https://ougc.network/eula.txt>.
 ****************************************************************************/

declare(strict_types=1);

namespace ougc\AnnouncementBars\Hooks\Admin;

use MyBB;

function admin_config_plugins_deactivate()
{
    global $mybb, $page;

    if (
        $mybb->get_input('action') != 'deactivate' ||
        $mybb->get_input('plugin') != 'ougc_annbars' ||
        !$mybb->get_input('uninstall', MyBB::INPUT_INT)
    ) {
        return;
    }

    if ($mybb->request_method != 'post') {
        $page->output_confirm_action(
            'index.php?module=config-plugins&amp;action=deactivate&amp;uninstall=1&amp;plugin=ougc_annbars'
        );
    }

    if ($mybb->get_input('no')) {
        admin_redirect('index.php?module=config-plugins');
    }
}

function admin_forum_action_handler(array &$actionHandler): array
{
    $actionHandler['ougc_annbars'] = [
        'active' => 'ougc_annbars',
        'file' => 'module.php'
    ];

    return $actionHandler;
}

function admin_forum_menu(array &$menuArray): array
{
    global $lang, $annbars;

    $annbars->lang_load();

    $menuArray[] = [
        'id' => 'ougc_annbars',
        'title' => $lang->ougc_annbars_menu,
        'link' => 'index.php?module=forum-ougc_annbars'
    ];

    return $menuArray;
}

function admin_load()
{
    global $modules_dir, $run_module, $action_file, $run_module, $page, $modules_dir_backup, $run_module_backup, $action_file_backup;

    if ($run_module != 'forum' || $page->active_action !== 'ougc_annbars') {
        return;
    }

    $modules_dir_backup = $modules_dir;

    $run_module_backup = $run_module;

    $action_file_backup = $action_file;

    $modules_dir = \ougc\AnnouncementBars\ROOT;

    $run_module = 'admin';

    $action_file = 'module.php';
}

function admin_forum_permissions(array &$args): array
{
    global $lang, $annbars;

    $annbars->lang_load();

    $args['ougc_annbars'] = $lang->ougc_annbars_permissions;

    return $args;
}

function admin_tools_get_admin_log_action(&$log)
{
    if($log['logitem']['module'] == 'forum-ougc_annbars')
    {
        global $annbars, $lang;
        $annbars->lang_load();

        $bar = $annbars->get_bar($log['logitem']['data'][0]);

        if(isset($bar['aid']))
        {
            $lang->{$log['lang_string']} = $lang->sprintf($lang->{$log['lang_string']}, 1, $bar['aid']);
        }
    }
}
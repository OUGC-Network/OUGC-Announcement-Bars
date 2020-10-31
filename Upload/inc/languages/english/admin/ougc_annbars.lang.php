<?php

/***************************************************************************
 *
 *	OUGC Announcement Bars plugin (/inc/languages/english/admin/ougc_annbars.php)
 *	Author: Omar Gonzalez
 *	Copyright: © 2012 - 2020 Omar Gonzalez
 *
 *	Website: https://ougc.network
 *
 *	This plugin will allow administrators and super moderators to manage announcement bars.
 *
 ***************************************************************************
 
****************************************************************************
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************/

// Plugin information.
$l['ougc_annbars_plugin'] = 'OUGC Announcement Bars';
$l['ougc_annbars_plugin_d'] = 'This plugin will allow administrators to manage announcement bars.';
#$l['ougc_plugins'] = 'Manage "OUGC Plugins" settings.';

// PluginLibrary
$l['ougc_annbars_plreq'] = 'This plugin requires <a href="{1}">PluginLibrary</a> version {2} or later to be uploaded to your forum.';
$l['ougc_annbars_plold'] = 'This plugin requires PluginLibrary version {1} or later, whereas your current version is {2}. Please do update <a href="{3}">PluginLibrary</a>.';

// Settings
$l['ougc_annbars_setting_limit'] = 'Bars Limit';
$l['ougc_annbars_setting_limit_desc'] = 'Write the maximum number of bars to show at any page. 0 = no limit.';
$l['ougc_annbars_setting_dismisstime'] = 'Dismiss Time';
$l['ougc_annbars_setting_dismisstime_desc'] = 'Input the amount of days users can keep annoucements as dismissed..';

// ACP Page
$l['ougc_annbars_menu'] = 'Manage Bars';
$l['ougc_annbars_permissions'] = 'Can manage announcement bars?';
$l['ougc_annbars_tab_view'] = 'View';
$l['ougc_annbars_tab_view_d'] = 'Manage any existing announcement bar.';
$l['ougc_annbars_tab_view_table'] = 'Manage Existing Bars';
$l['ougc_annbars_tab_add'] = 'Add';
$l['ougc_annbars_tab_add_d'] = 'Add a new announcement bar.';
$l['ougc_annbars_tab_edit'] = 'Edit';
$l['ougc_annbars_tab_preview'] = 'Preview';
$l['ougc_annbars_tab_edit_d'] = 'Edit any existing announcement bar.';
$l['ougc_annbars_form_content'] = 'Content';
$l['ougc_annbars_form_status'] = 'Status';
$l['ougc_annbars_form_order'] = 'Display Order';
$l['ougc_annbars_form_perpage'] = 'Per page';
$l['ougc_annbars_form_submit'] = 'Update Order';

// Form lang
$l['ougc_annbars_form_name'] = 'Name';
$l['ougc_annbars_form_name_d'] = 'Insert a short name for this bar.';
$l['ougc_annbars_form_content'] = 'Content';
$l['ougc_annbars_form_content_d'] = 'Write a the content of this bar.<br />
{1} => Current user username.<br />
{2} => Forum name.<br />
{3} => Forum URL.<br />
{4} => Start date.<br />
{5} => End date.<br />
{6} => Thread count (if rules are enabled).';
$l['ougc_annbars_form_visible'] = 'Visible';
$l['ougc_annbars_form_everywhere'] = 'Everywhere';
$l['ougc_annbars_form_custom'] = 'Custom';
$l['ougc_annbars_form_hidden'] = 'Hidden';
$l['ougc_annbars_form_style'] = 'Style';
$l['ougc_annbars_form_style_d'] = 'Choose the bar style (css class).';
$l['ougc_annbars_form_groups'] = 'Groups';
$l['ougc_annbars_form_groups_d'] = 'Choose the groups to show this bar to.';
$l['ougc_annbars_form_forums'] = 'Forums';
$l['ougc_annbars_form_scripts'] = 'Scripts';
$l['ougc_annbars_form_dismissible'] = 'Can be dismissed';
$l['ougc_annbars_form_dismissible_d'] = 'Enable this for users to be able to dimiss this announcement bar.';
$l['ougc_annbars_form_startdate'] = 'Start Date';
$l['ougc_annbars_form_startdate_d'] = 'Choose the date this bar will being visible.';
$l['ougc_annbars_form_enddate'] = 'End Date';
$l['ougc_annbars_form_enddate_d'] = 'Choose the date this bar will stop being visible.';
$l['ougc_annbars_form_frules'] = 'Display on Forum Rules';
$l['ougc_annbars_form_frules_d'] = 'You can decide to display this bar only if there are threads that meet a specific criteria.';
$l['ougc_annbars_form_frules_fid'] = 'Forum';
$l['ougc_annbars_form_frules_closed'] = 'Closed Threads';
$l['ougc_annbars_form_frules_closed_d'] = 'If selected only closed threads will account.';
$l['ougc_annbars_form_frules_visible'] = 'Visible Threads';
$l['ougc_annbars_form_frules_visible_d'] = 'Select the visible status of threads to take into account.';
$l['ougc_annbars_form_frules_dateline'] = 'Date Cut';
$l['ougc_annbars_form_frules_dateline_d'] = 'Threads older than <i>n</i> weeks will not account.';
$l['ougc_annbars_form_frule_visible'] = 'Visible threads.';
$l['ougc_annbars_form_frule_unapproved'] = 'Unapproved threads.';
$l['ougc_annbars_form_frule_deleted'] = 'Deleted threads.';

$l['ougc_annbars_button_submit'] = 'Submit';

// Error / success message
$l['ougc_annbars_error_invalid'] = 'Invalid announcement bar selected.';
$l['ougc_annbars_error_invalidname'] = 'Invalid name.';
$l['ougc_annbars_error_invalidcontent'] = 'Invalid content.';
$l['ougc_annbars_error_invalidstyle'] = 'Invalid style.';
$l['ougc_annbars_error_invalidstartdate'] = 'Invalid start date.';
$l['ougc_annbars_error_invalidenddate'] = 'Invalid end date.';

$l['ougc_annbars_success_add'] = 'Announcement bar was created successfully.';
$l['ougc_annbars_success_edit'] = 'Announcement bar was edited successfully.';
$l['ougc_annbars_success_delete'] = 'Announcement bar deleted successfully.';
$l['ougc_annbars_success_disporder'] = 'Announcement bar display order updated successfully.';
$l['ougc_annbars_success_cache'] = 'The cache was rebuild successfully.';

// View all
$l['ougc_annbars_view_empty'] = 'There are currently no announcement bars to show.';

// Styles
$l['ougc_annbars_form_style_default'] = 'Default';
$l['ougc_annbars_form_style_colors'] = 'Colors';
$l['ougc_annbars_form_style_black'] = 'Black';
$l['ougc_annbars_form_style_white'] = 'White';
$l['ougc_annbars_form_style_red'] = 'Red';
$l['ougc_annbars_form_style_green'] = 'Green';
$l['ougc_annbars_form_style_blue'] = 'Blue';
$l['ougc_annbars_form_style_brown'] = 'Brown';
$l['ougc_annbars_form_style_pink'] = 'Pink';
$l['ougc_annbars_form_style_orange'] = 'Orange';

// Logs
$l['admin_log_forum_ougc_annbars_add'] = 'OUGC Announcement Bars: "{1}" added (AID: {2}).';
$l['admin_log_forum_ougc_annbars_edit'] = 'OUGC Announcement Bars: "{1}" edited (AID: {2}).';
$l['admin_log_forum_ougc_annbars_delete'] = 'OUGC Announcement Bars: bar deleted.';
$l['task_ougc_annbars_ran'] = 'The OUGC Announcement Bars task successfully ran.';
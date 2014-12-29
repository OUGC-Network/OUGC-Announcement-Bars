<?php

/***************************************************************************
 *
 *   OUGC Announcement Bars plugin (/admin/modules/forum/ougc_annbars.php)
 *	 Author: Omar Gonzalez
 *   Copyright: © 2012 - 2013 Omar Gonzalez
 *   
 *   Website: http://omarg.me
 *
 *   This plugin will allow administrators and super moderators to manage announcement bars.
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

// Die if IN_MYBB is not defined, for security reasons.
defined('IN_MYBB') or die('Direct initialization of this file is not allowed.');

// Check requirements
$annbars->meets_requirements() or $annbars->admin_redirect($annbars->message, true);

// Set url to use
$annbars->set_url('index.php?module=forum-ougc_annbars');

// Set/load defaults
$mybb->input['action'] = isset($mybb->input['action']) ? trim($mybb->input['action']) : '';
$mybb->input['aid'] = isset($mybb->input['aid']) ? (int)$mybb->input['aid'] : 0;
$mybb->input['page'] = (int)(isset($mybb->input['page']) ? (int)$mybb->input['page'] : 0);
$annbars->lang_load();

// Container tabs
$sub_tabs['ougc_annbars_view'] = array(
	'title'			=> $lang->ougc_annbars_tab_view,
	'link'			=> $annbars->build_url(),
	'description'	=> $lang->ougc_annbars_tab_view_d
);
$sub_tabs['ougc_annbars_add'] = array(
	'title'			=> $lang->ougc_annbars_tab_add,
	'link'			=> $annbars->build_url(array('action' => 'add')),
	'description'	=> $lang->ougc_annbars_tab_add_d
);
if($mybb->input['action'] == 'edit')
{
	$sub_tabs['ougc_annbars_edit'] = array(
		'title'			=> $lang->ougc_annbars_tab_edit,
		'link'			=> $annbars->build_url(array('action' => 'edit', 'aid' => $mybb->input['aid'])),
		'description'	=> $lang->ougc_annbars_tab_edit_d
	);
}

$page->add_breadcrumb_item($lang->ougc_annbars_menu, $sub_tabs['ougc_annbars_view']['link']);

if($mybb->input['action'] == 'add' || $mybb->input['action'] == 'edit')
{
	$add = ($mybb->input['action'] == 'add' ? true : false);

	if($add)
	{
		$annbars->set_bar_data();

		$page->add_breadcrumb_item($sub_tabs['ougc_annbars_add']['title'], $sub_tabs['ougc_annbars_add']['link']);
		$page->output_header($lang->ougc_annbars_menu);
		$page->output_nav_tabs($sub_tabs, 'ougc_annbars_add');
	}
	else
	{
		$bar = $annbars->get_bar($mybb->input['aid']);
		if(!(isset($bar['aid']) && (int)$bar['aid'] > 0))
		{
			$annbars->admin_redirect($lang->ougc_annbars_error_invalid, true);
		}

		$annbars->set_bar_data($bar['aid']);

		$page->add_breadcrumb_item($sub_tabs['ougc_annbars_edit']['title'], $sub_tabs['ougc_annbars_edit']['link']);
		$page->output_header($lang->ougc_annbars_menu);
		$page->output_nav_tabs($sub_tabs, 'ougc_annbars_edit');
	}

	foreach(array('groups', 'visible', 'forums', 'scripts', 'style') as $key)
	{
		if(!isset($mybb->input[$key]) && isset($bar[$key]))
		{
			$mybb->input[$key] = $bar[$key];
		}
	}
	unset($key);

	if(!$mybb->get_input('style_picker'))
	{
		$mybb->input['style_picker'] = $mybb->get_input('style');
	}

	$style_checked = array('default' => '', 'custom' => '');
	if($mybb->get_input('style_type') == 'default' || in_array($mybb->get_input('style'), $annbars->styles))
	{
		$mybb->input['style_type'] = 'default';
		$mybb->input['style'] = $mybb->input['style_picker'] = $mybb->get_input('style_picker');
		$style_checked['default'] = 'checked="checked"';
	}
	else
	{
		$mybb->input['style_type'] = 'custom';
		$style_checked['custom'] = 'checked="checked"';
	}
	$annbars->bar_data['style'] = $mybb->get_input('style');

	$group_checked = array('all' => '', 'custom' => '', 'none' => '');
	if($mybb->get_input('groups_type') == 'all' || $mybb->get_input('groups', 1) == -1)
	{
		$mybb->input['groups_type'] = 'all';
		$mybb->input['groups'] = -1;
		$group_checked['all'] = 'checked="checked"';
	}
	elseif($mybb->get_input('groups_type') == 'none' || $mybb->get_input('groups') == '' && !$mybb->get_input('groups', 2))
	{
		$mybb->input['groups_type'] = 'none';
		$mybb->input['groups'] = '';
		$group_checked['none'] = 'checked="checked"';
	}
	else
	{
		$mybb->input['groups_type'] = 'custom';
		$mybb->input['groups'] = $annbars->clean_ints($mybb->input['groups']);
		$group_checked['custom'] = 'checked="checked"';
	}
	$annbars->bar_data['groups'] = $mybb->input['groups'];

	$visible_checked = array('everywhere' => '', 'custom' => '');
	if($mybb->get_input('visible_type') == 'everywhere' || $mybb->get_input('visible', 1) == 1)
	{
		$mybb->input['visible_type'] = 'everywhere';
		$mybb->input['visible'] = 1;
		$visible_checked['everywhere'] = 'checked="checked"';
	}
	else
	{
		$mybb->input['visible_type'] = 'custom';
		$mybb->input['visible'] = 0;
		$visible_checked['custom'] = 'checked="checked"';
	}
	$annbars->bar_data['visible'] = $mybb->input['visible'];

	$forum_checked = array('all' => '', 'custom' => '', 'none' => '');
	if($mybb->get_input('forums_type') == 'all' || $mybb->get_input('forums', 1) == -1)
	{
		$mybb->input['forums_type'] = 'all';
		$mybb->input['forums'] = -1;
		$forum_checked['all'] = 'checked="checked"';
	}
	elseif($mybb->get_input('forums_type') == 'none' || $mybb->get_input('forums') == '' && !$mybb->get_input('forums', 2))
	{
		$mybb->input['forums_type'] = 'none';
		$mybb->input['forums'] = '';
		$forum_checked['none'] = 'checked="checked"';
	}
	else
	{
		$mybb->input['forums_type'] = 'custom';
		$mybb->input['forums'] = $annbars->clean_ints($mybb->input['forums']);
		$forum_checked['custom'] = 'checked="checked"';
	}
	$annbars->bar_data['forums'] = $mybb->input['forums'];

	if($mybb->request_method == 'post')
	{
		if($annbars->validate_data())
		{
			if($add)
			{
				$annbars->insert_bar($annbars->bar_data);
				$lang_var = 'ougc_annbars_success_add';
			}
			else
			{
				$annbars->update_bar($annbars->bar_data, $bar['aid']);
				$lang_var = 'ougc_annbars_success_edit';
			}
			$annbars->log_action();
			$annbars->admin_redirect($lang->$lang_var, !$annbars->update_cache());
		}
		else
		{
			$page->output_inline_error($annbars->validate_errors);
		}
	}

	if($add)
	{
		$form = new Form($annbars->build_url('action=add'), 'post');
		$form_container = new FormContainer($sub_tabs['ougc_annbars_add']['description']);
	}
	else
	{
		$form = new Form($annbars->build_url(array('action' => 'edit', 'aid' => $bar['aid'])), 'post');
		$form_container = new FormContainer($sub_tabs['ougc_annbars_edit']['description']);
	}

	$form_container->output_row($lang->ougc_annbars_form_name.' <em>*</em>', $lang->ougc_annbars_form_name_d, $form->generate_text_box('name', $annbars->bar_data['name']));
	$form_container->output_row($lang->ougc_annbars_form_content, $lang->ougc_annbars_form_content_d, $form->generate_text_area('content', $annbars->bar_data['content'], array('rows' => 10, 'cols' => 90, 'style' => 'width: auto;')));

	ougc_print_selection_javascript();

	$style_select = "
	<dl style=\"margin-top: 0; margin-bottom: 0; width: 100%\">
		<dt><label style=\"display: block;\"><input type=\"radio\" name=\"style_type\" value=\"default\" {$style_checked['default']} class=\"style_forums_groups_check\" onclick=\"checkAction('style');\" style=\"vertical-align: middle;\" /> <strong>{$lang->ougc_annbars_form_style_default}</strong></label></dt>
		<dd style=\"margin-top: 4px;\" id=\"style_forums_groups_default\" class=\"style_forums_groups\">
			<table cellpadding=\"4\">
				<tr>
					<td valign=\"top\"><small>{$lang->ougc_annbars_form_style_colors}</small></td>
					<td>".$form->generate_select_box('style_picker', array(
		'black'		=> $lang->ougc_annbars_form_style_black,
		'white'		=> $lang->ougc_annbars_form_style_white,
		'red'		=> $lang->ougc_annbars_form_style_red,
		'green'		=> $lang->ougc_annbars_form_style_green,
		'blue'		=> $lang->ougc_annbars_form_style_blue,
		'brown'		=> $lang->ougc_annbars_form_style_brown,
		'pink'		=> $lang->ougc_annbars_form_style_pink,
		'orange'	=> $lang->ougc_annbars_form_style_orange,
	), $annbars->bar_data['style'])."</td>
				</tr>
			</table>
		</dd>
		<dt><label style=\"display: block;\"><input type=\"radio\" name=\"style_type\" value=\"custom\" {$style_checked['custom']} class=\"style_forums_groups_check\" onclick=\"checkAction('style');\" style=\"vertical-align: middle;\" /> <strong>{$lang->ougc_annbars_form_custom}</strong></label></dt>
		<dd style=\"margin-top: 4px;\" id=\"style_forums_groups_custom\" class=\"style_forums_groups\">
			<table cellpadding=\"4\">
				<tr>
					<td valign=\"top\"><small>{$lang->ougc_annbars_form_custom}</small></td>
					<td>".$form->generate_text_box('style', $annbars->bar_data['style'], array('style' => '" maxlength="20'))."</td>
				</tr>
			</table>
		</dd>
	</dl>
	<script type=\"text/javascript\">
		checkAction('style');
	</script>";

	$form_container->output_row($lang->ougc_annbars_form_style, $lang->ougc_annbars_form_style_d, $style_select, '', array(), array('id' => 'row_style'));

	$groups_select = "
	<dl style=\"margin-top: 0; margin-bottom: 0; width: 100%\">
		<dt><label style=\"display: block;\"><input type=\"radio\" name=\"groups_type\" value=\"all\" {$group_checked['all']} class=\"groups_forums_groups_check\" onclick=\"checkAction('groups');\" style=\"vertical-align: middle;\" /> <strong>{$lang->all_groups}</strong></label></dt>
		<dt><label style=\"display: block;\"><input type=\"radio\" name=\"groups_type\" value=\"custom\" {$group_checked['custom']} class=\"groups_forums_groups_check\" onclick=\"checkAction('groups');\" style=\"vertical-align: middle;\" /> <strong>{$lang->select_groups}</strong></label></dt>
		<dd style=\"margin-top: 4px;\" id=\"groups_forums_groups_custom\" class=\"groups_forums_groups\">
			<table cellpadding=\"4\">
				<tr>
					<td valign=\"top\"><small>{$lang->groups_colon}</small></td>
					<td>".$form->generate_group_select('groups[]', $mybb->get_input('groups', 2), array('multiple' => true, 'size' => 5))."</td>
				</tr>
			</table>
		</dd>
		<dt><label style=\"display: block;\"><input type=\"radio\" name=\"groups_type\" value=\"none\" {$group_checked['none']} class=\"groups_forums_groups_check\" onclick=\"checkAction('groups');\" style=\"vertical-align: middle;\" /> <strong>{$lang->none}</strong></label></dt>
	</dl>
	<script type=\"text/javascript\">
		checkAction('groups');
	</script>";

	$form_container->output_row($lang->ougc_annbars_form_groups.' <em>*</em>', $lang->ougc_annbars_form_groups_d, $groups_select, '', array(), array('id' => 'row_groups'));

	$forums_select = "
	<dl style=\"margin-top: 0; margin-bottom: 0; width: 100%\">
		<dt><label style=\"display: block;\"><input type=\"radio\" name=\"forums_type\" value=\"all\" {$forum_checked['all']} class=\"forums_forums_groups_check\" onclick=\"checkAction('forums');\" style=\"vertical-align: middle;\" /> <strong>{$lang->all_forums}</strong></label></dt>
		<dt><label style=\"display: block;\"><input type=\"radio\" name=\"forums_type\" value=\"custom\" {$forum_checked['custom']} class=\"forums_forums_groups_check\" onclick=\"checkAction('forums');\" style=\"vertical-align: middle;\" /> <strong>{$lang->select_forums}</strong></label></dt>
		<dd style=\"margin-top: 4px;\" id=\"forums_forums_groups_custom\" class=\"forums_forums_groups\">
			<table cellpadding=\"4\">
				<tr>
					<td valign=\"top\"><small>{$lang->forums_colon}</small></td>
					<td>".$form->generate_forum_select('forums[]', $mybb->get_input('forums', 2), array('multiple' => true, 'size' => 5))."</td>
				</tr>
			</table>
		</dd>
		<dt><label style=\"display: block;\"><input type=\"radio\" name=\"forums_type\" value=\"none\" {$forum_checked['none']} class=\"forums_forums_groups_check\" onclick=\"checkAction('forums');\" style=\"vertical-align: middle;\" /> <strong>{$lang->none}</strong></label></dt>
	</dl>
	<script type=\"text/javascript\">
		checkAction('forums');
	</script>";

	$visible_select = "
	<dl style=\"margin-top: 0; margin-bottom: 0; width: 100%\">
		<dt><label style=\"display: block;\"><input type=\"radio\" name=\"visible_type\" value=\"everywhere\" {$visible_checked['everywhere']} class=\"visible_forums_groups_check\" onclick=\"checkAction('visible');\" style=\"vertical-align: middle;\" /> <strong>{$lang->ougc_annbars_form_everywhere}</strong></label></dt>
		<dt><label style=\"display: block;\"><input type=\"radio\" name=\"visible_type\" value=\"custom\" {$visible_checked['custom']} class=\"visible_forums_groups_check\" onclick=\"checkAction('visible');\" style=\"vertical-align: middle;\" /> <strong>{$lang->ougc_annbars_form_custom}</strong></label></dt>
		<dd style=\"margin-top: 4px;\" id=\"visible_forums_groups_custom\" class=\"visible_forums_groups\">
			<table cellpadding=\"4\">
				<tr>
					<td valign=\"top\"><small>{$lang->ougc_annbars_form_forums}</small></td>
					<td>".$forums_select."</td>
				</tr>
				<tr>
					<td valign=\"top\"><small>{$lang->ougc_annbars_form_scripts}</small></td>
					<td>".$form->generate_text_area('scripts', $annbars->bar_data['scripts'], array('rows' => 7, 'cols' => 60, 'style' => 'width: auto;'))."</td>
				</tr>
			</table>
		</dd>
	</dl>
	<script type=\"text/javascript\">
		checkAction('visible');
	</script>";

	$form_container->output_row($lang->ougc_annbars_form_visible.' <em>*</em>', $lang->ougc_annbars_form_visible_d, $visible_select, '', array(), array('id' => 'row_visible'));
	$form_container->output_row($lang->ougc_annbars_form_date." <em>*</em>", $lang->ougc_annbars_form_date_d, $form->generate_date_select('enddate', $annbars->bar_data['enddate_day'], $annbars->bar_data['enddate_month'], $annbars->bar_data['enddate_year']));

	$form_container->end();

	$form->output_submit_wrapper(array($form->generate_submit_button($lang->ougc_annbars_button_submit), $form->generate_reset_button($lang->reset)));

	$form->end();

	$page->output_footer();
}
elseif($mybb->input['action'] == 'delete')
{
	$bar = $annbars->get_bar($mybb->input['aid']);
	if(!(isset($bar['aid']) && (int)$bar['aid'] > 0))
	{
		$annbars->admin_redirect($lang->ougc_annbars_error_invalid, true);
	}

	if($mybb->request_method == 'post')
	{
		if(isset($mybb->input['no']))
		{
			$annbars->admin_redirect();
		}

		$annbars->delete_bar($mybb->input['aid']);
		$annbars->log_action();
		$annbars->update_cache();
		$annbars->admin_redirect($lang->ougc_annbars_success_delete);
	}

	$page->add_breadcrumb_item($lang->ougc_annbars_tab_delete);

	$page->output_confirm_action($annbars->build_url(array('action' => 'delete', 'aid' => $mybb->input['aid'], 'my_post_key' => $mybb->post_code)));
}
elseif($mybb->input['action'] == 'preview')
{
	$bar = $annbars->get_bar($mybb->input['aid']);
	if(!(isset($bar['aid']) && (int)$bar['aid'] > 0))
	{
		$annbars->admin_redirect($lang->ougc_annbars_error_invalid, true);
	}

	$page->add_breadcrumb_item($lang->ougc_annbars_tab_preview);

	$page->output_header(htmlspecialchars_uni($bar['name']));

	$table = new Table;
	$table->construct_header(htmlspecialchars_uni($bar['name']));
	$table->construct_cell($annbars->parse_message($bar['content']));
	$table->construct_row();
	$table->output($lang->ougc_annbars_tab_preview);

	$page->output_footer();
}
else
{
	$page->output_header($lang->ougc_annbars_menu);
	$page->output_nav_tabs($sub_tabs, 'ougc_annbars_view');

	$table = new Table;
	$table->construct_header($lang->ougc_annbars_form_name, array('width' => '20%'));
	$table->construct_header($lang->ougc_annbars_form_content, array('width' => '60%'));
	$table->construct_header($lang->ougc_annbars_form_status, array('width' => '10%', 'class' => 'align_center'));
	$table->construct_header($lang->options, array('width' => '10%', 'class' => 'align_center'));

	// Multi-page support
	$perpage = (int)(isset($mybb->input['perpage']) ? (int)$mybb->input['perpage'] : 10);
	if($perpage < 1)
	{
		$perpage = 10;
	}
	elseif($perpage > 100)
	{
		$perpage = 100;
	}
	
	if($mybb->input['page'] > 0)
	{
		$start = ($mybb->input['page']-1)*$perpage;
	}
	else
	{
		$start = 0;
		$mybb->input['page'] = 1;
	}

	$query = $db->simple_select('ougc_annbars', 'COUNT(aid) AS bars');
	$barscount = (int)$db->fetch_field($query, 'bars');

	$limitstring = '';
	if($barscount < 1)
	{
		$table->construct_cell('<div align="center">'.$lang->ougc_annbars_view_empty.'</div>', array('colspan' => 6));
		$table->construct_row();
	}
	else
	{
		$query = $db->simple_select('ougc_annbars', '*', '', array('limit' => $perpage, 'limit_start' => $start, 'order_by' => 'aid'));

		while($bar = $db->fetch_array($query))
		{
			$editurl = $annbars->build_url(array('action' => 'edit', 'aid' => $bar['aid']));

			$bar['visible'] = 'on';
			$bar['lang'] = 'ougc_annbars_form_visible';
			$bar['name'] = htmlspecialchars_uni($bar['name']);
			if(!($bar['enddate'] >= TIME_NOW))
			{
				$bar['visible'] = 'off';
				$bar['lang'] = 'ougc_annbars_form_hidden';
				$bar['name'] = '<i>'.$bar['name'].'</i>';
			}

			$table->construct_cell('<a href="'.$editurl.'">'.$bar['name'].'</a>');
			$table->construct_cell(ougc_getpreview($bar['content'], 9999, true, true, array('allow_html' => 1)));

			$table->construct_cell('<img src="../'.$config['admin_dir'].'/styles/default/images/icons/bullet_'.$bar['visible'].($mybb->version_code >= 1800 ? '.png' : '.gif').'" alt="'.$lang->$bar['lang'].'" title="'.$lang->$bar['lang'].'" />', array('class' => 'align_center'));

			$popup = new PopupMenu('bar_'.$bar['aid'], $lang->options);
			$popup->add_item($lang->ougc_annbars_tab_edit, $editurl);
			$popup->add_item($lang->ougc_annbars_tab_preview, $annbars->build_url(array('action' => 'preview', 'aid' => $bar['aid'])));
			$popup->add_item($lang->delete, $annbars->build_url(array('action' => 'delete', 'aid' => $bar['aid'])));
			$table->construct_cell($popup->fetch(), array('class' => 'align_center'));

			$table->construct_row();
		}

		// Set url to use
		$annbars->set_url('index.php');

		// Multipage
		if(($multipage = trim(draw_admin_pagination($mybb->input['page'], $perpage, $barscount, $annbars->build_url(false, 'page')))))
		{
			echo $multipage;
		}
		$limitstring = '<div style="float: right;">Perpage: ';
		for($p = 10; $p < 51; $p = $p+10)
		{
			$s = ' - ';
			if($p == 50)
			{
				$s = '';
			}

			if($mybb->input['page'] == $p/10)
			{
				$limitstring .= $p.$s;
			}
			else
			{
				$limitstring .= '<a href="'.$annbars->build_url(false, array('perpage', 'page')).'&perpage='.$p.'">'.$p.'</a>'.$s;
			}
		}
		$limitstring .= '</div>';
	}

	$table->output($lang->ougc_annbars_tab_view_d.$limitstring);
	$page->output_footer();
}
exit;
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
$sub_tabs['ougc_annbars_cache'] = array(
	'title'			=> $lang->ougc_annbars_tab_cache,
	'link'			=> $annbars->build_url(array('action' => 'rebuilt_cache')),
	'description'	=> $lang->ougc_annbars_tab_cache_d
);

$page->add_breadcrumb_item($lang->ougc_annbars_menu, $sub_tabs['ougc_annbars_view']['link']);

if($mybb->input['action'] == 'rebuilt_cache')
{
	$annbars->log_action();
	$annbars->admin_redirect($lang->ougc_annbars_success_cache, !$annbars->update_cache());
}
elseif($mybb->input['action'] == 'add' || $mybb->input['action'] == 'edit')
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
	$form_container->output_row($lang->ougc_annbars_form_content, $lang->ougc_annbars_form_content_d, $form->generate_text_area('content', $annbars->bar_data['content']));
	$form_container->output_row($lang->ougc_annbars_form_style, $lang->ougc_annbars_form_style_d, $form->generate_select_box('style', array(
		'black'		=> $lang->ougc_annbars_form_style_black,
		'white'		=> $lang->ougc_annbars_form_style_white,
		'red'		=> $lang->ougc_annbars_form_style_red,
		'green'		=> $lang->ougc_annbars_form_style_green,
		'blue'		=> $lang->ougc_annbars_form_style_blue,
		'brown'		=> $lang->ougc_annbars_form_style_brown,
		'pink'		=> $lang->ougc_annbars_form_style_pink,
		'orange'	=> $lang->ougc_annbars_form_style_orange,
	), $annbars->bar_data['style']));
	$form_container->output_row($lang->ougc_annbars_form_groups.' <em>*</em>', $lang->ougc_annbars_form_groups_d, $form->generate_group_select('groups[]', $annbars->bar_data['groups'], array('multiple' => 1, 'size' => 5)));
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
			$table->construct_cell(htmlspecialchars_uni($bar['name']));
			$table->construct_cell(htmlspecialchars_uni($bar['content']));

			$bar['visible'] = 'off';
			$bar['lang'] = 'ougc_annbars_form_hidden';
			if($bar['enddate'] >= TIME_NOW)
			{
				$bar['visible'] = 'on';
				$bar['lang'] = 'ougc_annbars_form_visible';
			}

			$table->construct_cell('<img src="../'.$config['admin_dir'].'/styles/default/images/icons/bullet_'.$bar['visible'].($mybb->version_code >= 1800 ? '.png' : '.gif').'" alt="'.$lang->$bar['lang'].'" title="'.$lang->$bar['lang'].'" />', array('class' => 'align_center'));

			$popup = new PopupMenu('bar_'.$bar['aid'], $lang->options);
			$popup->add_item($lang->ougc_annbars_tab_edit, $annbars->build_url(array('action' => 'edit', 'aid' => $bar['aid'])));
			$popup->add_item($lang->ougc_annbars_tab_delete, $annbars->build_url(array('action' => 'delete', 'aid' => $bar['aid'])));
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
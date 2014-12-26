<?php

/***************************************************************************
 *
 *   OUGC Announcement Bars plugin (/inc/plugins/ougc_annbars.php)
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

// PLUGINLIBRARY
defined('PLUGINLIBRARY') or define('PLUGINLIBRARY', MYBB_ROOT.'inc/plugins/pluginlibrary.php');

// Add our hook
if(defined('IN_ADMINCP'))
{
	// Add our menu at config panel
	$plugins->add_hook('admin_forum_menu', create_function('&$args', 'global $lang, $annbars;	$annbars->lang_load();	$args[] = array(\'id\' => \'ougc_annbars\', \'title\' => $lang->ougc_annbars_menu, \'link\' => \'index.php?module=forum-ougc_annbars\');'));

	// Add our action handler to config module
	$plugins->add_hook('admin_forum_action_handler', create_function('&$args', '$args[\'ougc_annbars\'] = array(\'active\' => \'ougc_annbars\', \'file\' => \'ougc_annbars.php\');'));

	// Insert our plugin into the admin permissions page
	$plugins->add_hook('admin_forum_permissions', create_function('&$args', 'global $lang, $annbars;	$annbars->lang_load();	$args[\'ougc_annbars\'] = $lang->ougc_annbars_permissions;'));

	// ACP logs page
	$plugins->add_hook('admin_tools_get_admin_log_action', 'ougc_annbars_logs');
}
else
{
	global $templatelist;

	if(isset($templatelist))
	{
		$templatelist .= ',';
	}
	else
	{
		$templatelist = '';
	}

	$templatelist .= 'ougcannbars_bar';

	$plugins->add_hook('pre_output_page', 'ougc_annbars_show');
}

// Necessary plugin information for the ACP plugin manager.
function ougc_annbars_info()
{
	global $lang, $annbars;
	$annbars->lang_load();

	return array(
		'name'			=> 'OUGC Announcement Bars',
		'description'	=> $lang->ougc_annbars_plugin_d,
		'website'		=> 'http://omarg.me',
		'author'		=> 'Omar G.',
		'authorsite'	=> 'http://omarg.me',
		'version'		=> '1.8.0',
		'versioncode'	=> 1800,
		'compatibility'	=> '18*',
		'pl'			=> array(
			'version'	=> 12,
			'url'		=> 'http://mods.mybb.com/view/pluginlibrary'
		)
	);
}

// Our awesome class
class OUGC_ANNBARS
{
	// Define our ACP url
	public $url = 'index.php?module=config-plugins';

	// Plugin Message
	public $message = '';

	// Cache
	public $cache = array(
		'fromcache' => array(
		),
		'fromdb' => array(
		),
	);

	// AID which has just been updated/inserted/deleted
	public $aid = 0;

	// Allowed styles
	public $styles = array('black', 'white', 'red', 'green', 'blue', 'brown', 'pink', 'orange');

	// Load lang file
	function lang_load()
	{
		global $lang;

		if(isset($lang->ougc_annbars_plugin))
		{
			return;
		}

		if(defined('IN_ADMINCP'))
		{
			$lang->load('ougc_annbars');
		}
		else
		{
			$lang->load('ougc_annbars', false, true);
		}
	}

	// Set url
	function set_url($url)
	{
		if(($url = trim($url)))
		{
			$this->url = $url;
		}
	}

	// Check PL requirements
	function meets_requirements()
	{
		global $PL;

		$info = ougc_annbars_info();

		if(!file_exists(PLUGINLIBRARY))
		{
			global $lang;
			$this->lang_load();

			$this->message = $lang->sprintf($lang->ougc_annbars_plreq, $info['pl']['url'], $info['pl']['version']);
			return false;
		}

		$PL or require_once PLUGINLIBRARY;

		if($PL->version < $info['pl']['version'])
		{
			global $lang;
			$this->lang_load();

			$this->message = $lang->sprintf($lang->ougc_annbars_plold, $PL->version, $info['pl']['version'], $info['pl']['url']);
			return false;
		}

		return true;
	}

	// Redirect admin help function
	function admin_redirect($message='', $error=false)
	{
		if($message)
		{
			flash_message($message, ($error ? 'error' : 'success'));
		}

		admin_redirect($this->build_url());
		exit;
	}

	// Build an url parameter
	function build_url($urlappend=array(), $fetch_input_url=false)
	{
		global $PL;

		if(!is_object($PL))
		{
			return $this->url;
		}

		if($fetch_input_url === false)
		{
			if($urlappend && !is_array($urlappend))
			{
				$urlappend = explode('=', $urlappend);
				$urlappend = array($urlappend[0] => $urlappend[1]);
			}
		}
		else
		{
			$urlappend = $this->fetch_input_url($fetch_input_url);
		}

		return $PL->url_append($this->url, $urlappend, "&amp;", true);
	}

	// Update the bars cache
	function update_cache()
	{
		global $db, $cache;

		$query = $db->simple_select('ougc_annbars', '*', 'enddate=0 OR enddate>=\''.TIME_NOW.'\'');

		$update = array();

		while($award = $db->fetch_array($query))
		{
			$aid = (int)$award['aid'];
			unset($award['aid'], $award['name']);
			$update[$aid] = $award;
		}

		$db->free_result($query);

		empty($update) or $cache->update('ougc_annbars', $update);

		return (bool)$update;
	}

	// Fetch current url inputs, for multipage mostly
	function fetch_input_url($ignore=false)
	{
		$location = parse_url(get_current_location());
		while(my_strpos($location['query'], '&amp;'))
		{
			$location['query'] = html_entity_decode($location['query']);
		}
		$location = explode('&', $location['query']);

		if($ignore !== false)
		{
			if(!is_array($ignore))
			{
				$ignore = array($ignore);
			}
			foreach($location as $key => $input)
			{
				$input = explode('=', $input);
				if(in_array($input[0], $ignore))
				{
					unset($location[$key]);
				}
			}
		}

		$url = array();
		foreach($location as $input)
		{
			$input = explode('=', $input);
			$url[$input[0]] = $input[1];
		}

		return $url;
	}

	// Get bar from DB or cache
	function get_bar($aid=0, $cache=false)
	{
		if($cache)
		{
			if(!isset($this->cache['fromcache'][$aid]))
			{
				$this->cache['fromdb'][$aid] = false;

				global $PL;

				$bars = $PL->cache_read('ougc_annbars');

				if(isset($bars[$aid]))
				{
					$this->cache['fromcache'][$aid] = $bars[$aid];
				}
			}

			return $this->cache['fromcache'][$aid];
		}
		else
		{
			if(!isset($this->cache['fromdb'][$aid]))
			{
				$this->cache['fromdb'][$aid] = false;

				global $db;

				$query = $db->simple_select('ougc_annbars', '*', 'aid=\''.(int)$aid.'\'', array('limit' => 1));
				$bar = $db->fetch_array($query);
	
				if(isset($bar['aid']) && (int)$bar['aid'] > 0)
				{
					$this->cache['fromdb'][$aid] = $bar;
				}
			}

			return $this->cache['fromdb'][$aid];
		}
	}

	// Get bar from DB or cache
	function delete_bar($aid=0)
	{
		global $db;

		$annbars->aid = (int)$aid;

		$db->delete_query('ougc_annbars', 'aid=\''.$annbars->aid.'\'');

		$this->update_cache();
	}
		
	// Set rate data
	function set_bar_data($aid=null)
	{
		if(isset($aid) && ($bar = $this->get_bar($aid)))
		{
			$this->bar_data = array(
				'name'			=> $bar['name'],
				'content'		=> $bar['content'],
				'style'			=> $bar['style'],
				'groups'		=> explode(',', $bar['groups']),
				'enddate'		=> $bar['enddate'],
				'enddate_day'	=> date('j', $bar['enddate']),
				'enddate_month'	=> date('n', $bar['enddate']),
				'enddate_year'	=> date('Y', $bar['enddate'])
			);
		}
		else
		{
			$this->bar_data = array(
				'name'			=> '',
				'content'		=> '',
				'style'			=> 'black',
				'groups'		=> array(),
				'enddate'		=> TIME_NOW,
				'enddate_day'	=> date('j', TIME_NOW),
				'enddate_month'	=> date('n', TIME_NOW),
				'enddate_year'	=> date('Y', TIME_NOW)
			);
		}

		global $mybb;

		if($mybb->request_method == 'post')
		{
			foreach((array)$mybb->input as $key => $value)
			{
				if(isset($this->bar_data[$key]))
				{
					$this->bar_data[$key] = $value;
				}
			}
		}
	}

	// Validate a rate data to insert into the DB
	function validate_data()
	{
		global $lang;

		$this->validate_errors = array();
		$valid = true;

		$name = trim($this->bar_data['name']);
		if(!$name || my_strlen($name) > 100)
		{
			$this->validate_errors[] = $lang->ougc_annbars_error_invalidname;
			$valid = false;
		}

		$content = trim($this->bar_data['content']);
		if(!$content)
		{
			$this->validate_errors[] = $lang->ougc_annbars_error_invalidcontent;
			$valid = false;
		}

		if(!in_array($this->bar_data['style'], $this->styles))
		{
			$this->validate_errors[] = $lang->ougc_annbars_error_invalidstyle;
			$valid = false;
		}

		if($this->bar_data['enddate_day'] < 1 || $this->bar_data['enddate_day'] > 31 || $this->bar_data['enddate_month'] < 1 || $this->bar_data['enddate_month'] > 12 || $this->bar_data['enddate_year'] < 2000 || $this->bar_data['enddate_year'] > 2100 || ($this->bar_data['enddate_month'] == 2 && $this->bar_data['enddate_day'] > 29))
		{
			$this->validate_errors[] = $lang->ougc_annbars_error_invaliddate;
			$valid = false;
		}

		return $valid;
	}

	function insert_bar($data=array(), $update=false, $aid=0)
	{
		global $db;

		$insert_data = array(
			'name'			=> $db->escape_string((isset($data['name']) ? $data['name'] : '')),
			'content'		=> $db->escape_string((isset($data['content']) ? $data['content'] : '')),
			'style'			=> $db->escape_string((isset($data['style']) ? $data['style'] : 'black')),
			'groups'		=> '',
			'enddate'		=> TIME_NOW,
		);

		// Groups
		if(is_array($data['groups']))
		{
			$gids = array();
			foreach($data['groups'] as $gid)
			{
				$gids[] = (int)$gid;
			}
			$insert_data['groups'] = $db->escape_string(implode(',', $gids));
		}

		// Date
		if(isset($data['enddate_month']) && isset($data['enddate_day']) && isset($data['enddate_year']))
		{
			$insert_data['enddate'] = (int)mktime(date('H', TIME_NOW), date('i', TIME_NOW), date('s', TIME_NOW), $data['enddate_month'], $data['enddate_day'], $data['enddate_year']);
		}

		if($update)
		{
			$this->aid = (int)$aid;
			$db->update_query('ougc_annbars', $insert_data, 'aid=\''.$this->aid.'\'');
		}
		else
		{
			$this->aid = (int)$db->insert_query('ougc_annbars', $insert_data);
		}
	}

	// Update an annoucement bar
	function update_bar($data=array(), $aid=0)
	{
		$this->insert_bar($data, true, $aid);
	}

	// Log admin action
	function log_action()
	{
		if($this->aid)
		{
			log_admin_action($this->aid);
		}
		else
		{
			log_admin_action();
		}
	}
}
$GLOBALS['annbars'] = new OUGC_ANNBARS;

// Activate the plugin.
function ougc_annbars_activate()
{
	global $lang, $annbars, $PL, $cache;
	$annbars->lang_load();
	$annbars->meets_requirements() or $annbars->admin_redirect($annbars->message, true);

	$PL->stylesheet('ougc_annbars', "*[class*='ougc_annbars_'] {
	color:#fff;
	padding:4px;
	text-align:center;
	-webkit-box-shadow:inset 0 0 1px #FFF;
	-moz-box-shadow:inset 0 0 1px #FFF;
	box-shadow:inset 0 0 1px #FFF;
}

*[class*='ougc_annbars_'] strong {
	border-bottom:dashed 1px ;
}

.ougc_annbars_black {
	border:1px solid #000000;
	background: #393939;
	background: -moz-linear-gradient(top, #393939 0%, #000000 100%);
	background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#393939), color-stop(100%,#000000));
	background: -webkit-linear-gradient(top, #393939 0%,#000000 100%);
	background: -o-linear-gradient(top, #393939 0%,#000000 100%);
	background: -ms-linear-gradient(top, #393939 0%,#000000 100%);
	filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#393939', endColorstr='#000000',GradientType=0 );
	background: linear-gradient(top, #393939 0%,#000000 100%);
}

.ougc_annbars_white {
	color:#000;
	border:1px solid #eeeeee;
	background: #fcfcfc;
	background: -moz-linear-gradient(top, #fcfcfc 0%, #eeeeee 100%);
	background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#fcfcfc), color-stop(100%,#eeeeee));
	background: -webkit-linear-gradient(top, #fcfcfc 0%,#eeeeee 100%);
	background: -o-linear-gradient(top, #fcfcfc 0%,#eeeeee 100%);
	background: -ms-linear-gradient(top, #fcfcfc 0%,#eeeeee 100%);
	filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#fcfcfc', endColorstr='#eeeeee',GradientType=0 );
	background: linear-gradient(top, #fcfcfc 0%,#eeeeee 100%);
}

.ougc_annbars_red {
	border:1px solid #ff2929;
	background: #ff3d3d;
	background: -moz-linear-gradient(top, #ff3d3d 0%, #ff2929 100%);
	background: -webk
	border:1px solid #b00 !important;it-gradient(linear, left top, left bottom, color-stop(0%,#ff3d3d), color-stop(100%,#ff2929));
	background: -webkit-linear-gradient(top, #ff3d3d 0%,#ff2929 100%);
	background: -o-linear-gradient(top, #ff3d3d 0%,#ff2929 100%);
	background: -ms-linear-gradient(top, #ff3d3d 0%,#ff2929 100%);
	filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#ff3d3d', endColorstr='#ff2929',GradientType=0 );
	background: linear-gradient(top, #ff3d3d 0%,#ff2929 100%);
}

.ougc_annbars_blue {
	border:1px solid #2e82d6;
	background: #448fda;
	background: -moz-linear-gradient(top, #448fda 0%, #2e82d6 100%);
	background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#448fda), color-stop(100%,#2e82d6));
	background: -webkit-linear-gradient(top, #448fda 0%,#2e82d6 100%);
	background: -o-linear-gradient(top, #448fda 0%,#2e82d6 100%);
	background: -ms-linear-gradient(top, #448fda 0%,#2e82d6 100%);
	background: linear-gradient(top, #448fda 0%,#2e82d6 100%);
	filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#448fda', endColorstr='#2e82d6',GradientType=0 );
}

.ougc_annbars_green {
	border:1px solid #0ac247;
	background: #0bda51;
	background: -moz-linear-gradient(top, #0bda51 0%, #0ac247 100%);
	background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#0bda51), color-stop(100%,#0ac247));
	background: -webkit-linear-gradient(top, #0bda51 0%,#0ac247 100%);
	background: -o-linear-gradient(top, #0bda51 0%,#0ac247 100%);
	background: -ms-linear-gradient(top, #0bda51 0%,#0ac247 100%);
	background: linear-gradient(top, #0bda51 0%,#0ac247 100%);
	filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#0bda51', endColorstr='#0ac247',GradientType=0 );
}

.ougc_annbars_brown {
	border:1px solid #922626;
	background: #a52a2a;
	background: -moz-linear-gradient(top, #a52a2a 0%, #922626 100%);
	background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#a52a2a), color-stop(100%,#922626));
	background: -webkit-linear-gradient(top, #a52a2a 0%,#922626 100%);
	background: -o-linear-gradient(top, #a52a2a 0%,#922626 100%);
	background: -ms-linear-gradient(top, #a52a2a 0%,#922626 100%);
	filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#a52a2a', endColorstr='#922626',GradientType=0 );
	background: linear-gradient(top, #a52a2a 0%,#922626 100%);
}

.ougc_annbars_pink {
	border:1px solid #ff8fbc;
	background: #ffa6c9;
	background: -moz-linear-gradient(top, #ffa6c9 0%, #ff8fbc 100%);
	background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#ffa6c9), color-stop(100%,#ff8fbc));
	background: -webkit-linear-gradient(top, #ffa6c9 0%,#ff8fbc 100%);
	background: -o-linear-gradient(top, #ffa6c9 0%,#ff8fbc 100%);
	background: -ms-linear-gradient(top, #ffa6c9 0%,#ff8fbc 100%);
	background: linear-gradient(top, #ffa6c9 0%,#ff8fbc 100%);
	filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#ffa6c9', endColorstr='#ff8fbc',GradientType=0 );
}

.ougc_annbars_orange {
	border:1px solid #febf04;
	background: #ffd65e;
	background: -moz-linear-gradient(top, #ffd65e 0%, #febf04 100%);
	background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#ffd65e), color-stop(100%,#febf04));
	background: -webkit-linear-gradient(top, #ffd65e 0%,#febf04 100%);
	background: -o-linear-gradient(top, #ffd65e 0%,#febf04 100%);
	background: -ms-linear-gradient(top, #ffd65e 0%,#febf04 100%);
	filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#ffd65e', endColorstr='#febf04',GradientType=0 );
	background: linear-gradient(top, #ffd65e 0%,#febf04 100%);
}

*[class*='ougc_annbars_'] a:link, *[class*='ougc_annbars_'] a:visited, *[class*='ougc_annbars_'] a:hover, *[class*='ougc_annbars_'] a:active {
	text-decoration:none;
	color:inherit;
}");

	// Modify some templates.
	require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
	find_replace_templatesets('header', '#'.preg_quote('<navigation>').'#', '<navigation><!--OUGC_ANNBARS-->');

	// Add our settings
	/*
	$PL->settings('ougc_plugins', 'OUGC Plugins', $lang->ougc_plugins, array(
		'annbars_limit'	=> array(
		   'title'			=> $lang->ougc_annbars_setting_limit,
		   'description'	=> $lang->ougc_annbars_setting_limit_desc,
		   'optionscode'	=> 'text',
			'value'			=>	5,
		)
	));*/
	$PL->settings('ougc_annbars', $lang->ougc_annbars_plugin, $lang->ougc_annbars_plugin_d, array(
		'limit'	=> array(
		   'title'			=> $lang->ougc_annbars_setting_limit,
		   'description'	=> $lang->ougc_annbars_setting_limit_desc,
		   'optionscode'	=> 'text',
			'value'			=>	5,
		)
	));

	// Fill cache
	$annbars->update_cache();

	// Insert template/group
	/*
	$PL->templates('ougcplugins', 'OUGC Plugins', array(
		'annbars_bar'	=> '<div class="ougc_annbars_{$bar[\'style\']}">
	{$bar[\'content\']}
</div><br/>'
	));*/
	$PL->templates('ougcannbars', $lang->ougc_annbars_plugin, array(
		'bar'	=> '<div class="ougc_annbars_{$bar[\'style\']}">
	{$bar[\'content\']}
</div><br/>'
	));

	// Insert/update version into cache
	$plugins = $cache->read('ougc_plugins');
	if(!$plugins)
	{
		$plugins = array();
	}

	$info = ougc_annbars_info();

	if(!isset($plugins['annbars']))
	{
		$plugins['annbars'] = $info['versioncode'];
	}

	/*~*~* RUN UPDATES START *~*~*/
	if($plugins['annbars'] <= 1801)
	{
		$db->modify_column('ougc_annbars', 'content', 'text NOT NULL');
	}
	/*~*~* RUN UPDATES END *~*~*/

	$plugins['annbars'] = $info['versioncode'];
	$cache->update('ougc_plugins', $plugins);
}

// Deactivate the plugin.
function ougc_annbars_deactivate()
{
	global $annbars, $PL;
	$annbars->meets_requirements() or $annbars->admin_redirect($annbars->message, true);

	// Remove stylesheet
	$PL->stylesheet_deactivate('ougc_annbars');

	// Revert template edits
	require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
	find_replace_templatesets('header', '#'.preg_quote('<!--OUGC_ANNBARS-->').'#', '', 0);
	find_replace_templatesets('header', '#'.preg_quote('<ougc_annbars>').'#', '', 0);
}

// Install the plugin.
function ougc_annbars_install()
{
	global $db, $annbars;
	$annbars->meets_requirements() or $annbars->admin_redirect($annbars->message, true);

	// Drop our table
	$db->drop_table('ougc_annbars');

	// Create our tables if none exists
	$db->write_query("CREATE TABLE `".TABLE_PREFIX."ougc_annbars` (
			`aid` bigint(30) UNSIGNED NOT NULL AUTO_INCREMENT,
			`name` varchar(100) NOT NULL DEFAULT '',
			`content` text NOT NULL,
			`style` varchar(20) NOT NULL DEFAULT '',
			`groups` varchar(100) NOT NULL DEFAULT '',
			`enddate` int(10) NOT NULL DEFAULT '0',
			PRIMARY KEY (`aid`)
		) ENGINE=MyISAM{$db->build_create_table_collation()};"
	);
}

// Is the plugin installed?
function ougc_annbars_is_installed()
{
	global $db;

	return $db->table_exists('ougc_annbars');
}

// Uninstall the plugin.
function ougc_annbars_uninstall()
{
	global $annbars, $db, $PL, $cache;
	$annbars->meets_requirements() or $annbars->admin_redirect($annbars->message, true);

	// Drop our table
	$db->drop_table('ougc_annbars');

	// Delete the cache.
	$PL->cache_delete('ougc_annbars');

	// Delete stylesheet
	$PL->stylesheet_delete('ougc_annbars');

	// Delete settings
	$PL->settings_delete('ougc_annbars'); // we can't use this :(
	/*$query = $db->simple_select('settinggroups', 'gid', 'name=\'ougc_plugins\'');
	while($gid = $db->fetch_field($query, 'gid'))
	{
		$gid = (int)$gid;
		$db->delete_query('settings', 'gid=\''.$gid.'\' AND name=\'ougc_plugins_annbars_limit\'');

		$q = $db->simple_select('settings', 'gid', 'gid=\''.$gid.'\'');

		if($db->num_rows($q) < 1)
		{
			$db->delete_query('settinggroups', 'gid=\''.$gid.'\'');
		}
		unset($q);
	}*/

	// Delete template/group
	$PL->templates_delete('ougcannbars'); // we can't use this :(
	/*$db->delete_query('templates', 'title=\'ougcplugins_annbars_bar\''); // Delete template

	$query = $db->simple_select('templates', 'tid', 'title=\'ougcplugins\' OR title LIKE \'ougcplugins=_%\' ESCAPE \'=\'');
	if(!$db->num_rows($query))
	{
		$db->delete_query('templategroups', 'prefix=\'ougcplugins\''); // Delete template groups
	}*/

	// Delete version from cache
	$plugins = (array)$cache->read('ougc_plugins');

	if(isset($plugins['annbars']))
	{
		unset($plugins['annbars']);
	}

	if(!empty($plugins))
	{
		$cache->update('ougc_plugins', $plugins);
	}
	else
	{
		$PL->cache_delete('ougc_plugins');
	}
}

// We are here, so here we write the other global hook too..
function ougc_annbars_show(&$page)
{
	if(my_strpos($page, '<!--OUGC_ANNBARS-->') === false)
	{
		return;
	}

	global $settings;

	$limit = (isset($settings['ougc_annbars_limit']) ? (int)$settings['ougc_annbars_limit'] : 0);
	if($limit > 0)
	{
		global $PL, $annbars;
		$PL or require_once PLUGINLIBRARY;
		$bars = $PL->cache_read('ougc_annbars');

		$ougc_annbars = '';
		if(is_array($bars))
		{
			global $parser, $lang, $templates;

			$annbars->lang_load();

			if(!is_object($parser))
			{
				require_once MYBB_ROOT.'inc/class_parser.php';
				$parser = new postParser;
			}

			$username = $lang->guest;
			if($GLOBALS['mybb']->user['uid'])
			{
				$username = $GLOBALS['mybb']->user['username'];
			}

			$count = 1;
			foreach($bars as $key => $bar)
			{
				if($bar['groups'] && !(bool)$PL->is_member($bar['groups']) || $bar['enddate'] && $bar['enddate'] < TIME_NOW)
				{
					continue;
				}

				if($count > $limit)
				{
					break;
				}

				++$count;

				if(!in_array($bar['style'], $annbars->styles))
				{
					$bar['style'] = 'black';
				}

				$lang_val = 'ougc_annbars_bar_'.$key;
				if(!empty($lang->$lang_val))
				{
					$bar['content'] = $lang->$lang_val;
				}

				$bar['content'] = $parser->parse_message($lang->sprintf($bar['content'], $username, $settings['bbname'], $settings['bburl']), array(
					'allow_html'		=> 1,
					'allow_smilies'		=> 1,
					'allow_mycode'		=> 1,
					'filter_badwords'	=> 1,
					'shorten_urls'		=> 0
				));

				eval('$ougc_annbars .= "'.$templates->get('ougcannbars_bar').'";');
			}
		}

		return str_replace('<!--OUGC_ANNBARS-->', $ougc_annbars, $page);
	}
}

// We like nice stuff
function ougc_annbars_logs(&$log)
{
	if($log['logitem']['module'] == 'forum-ougc_annbars' && $log['logitem']['action'] != 'rebuilt_cache')
	{
		global $annbars, $lang;
		$annbars->lang_load();

		$bar = $annbars->get_bar($log['logitem']['data'][0]);

		if(isset($bar['aid']))
		{
			$lang->$log['lang_string'] = $lang->sprintf($lang->$log['lang_string'], 1, $bar['aid']);
			$lang->$log['lang_string'] = $lang->$log['lang_string'];
		}
	}
}
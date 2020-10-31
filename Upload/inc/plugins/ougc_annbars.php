<?php

/***************************************************************************
 *
 *	OUGC Announcement Bars plugin (/inc/plugins/ougc_annbars.php)
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

	$templatelist .= 'ougcannbars_bar, ougcannbars_wrapper';

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
		'website'		=> 'https://ougc.network',
		'author'		=> 'Omar G.',
		'authorsite'	=> 'https://ougc.network',
		'version'		=> '1.8.20',
		'versioncode'	=> 1820,
		'compatibility'	=> '18*',
		'codename'		=> 'ougc_annbars',
		'pl'			=> array(
			'version'	=> 13,
			'url'		=> 'https://community.mybb.com/mods.php?action=view&pid=573'
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

	// List of tables
	function _db_tables()
	{
		global $db;

		$collation = $db->build_create_table_collation();

		$tables = array(
			'ougc_annbars'	=> array(
				'aid'	=> "bigint(30) UNSIGNED NOT NULL AUTO_INCREMENT",
				'name'	=> "varchar(100) NOT NULL DEFAULT ''",
				'content'	=> "text NOT NULL",
				'style'	=> "varchar(20) NOT NULL DEFAULT ''",
				'groups'	=> "varchar(100) NOT NULL DEFAULT ''",
				'visible'	=> "tinyint(1) NOT NULL DEFAULT '1'",
				'forums'	=> "varchar(100) NOT NULL DEFAULT ''",
				'scripts'	=> "text NOT NULL",
				'dismissible'	=> "tinyint(10) NOT NULL DEFAULT '1'",
				'frules'	=> "tinyint(1) NOT NULL DEFAULT '0'",
				'frules_fid'	=> "varchar(100) NOT NULL DEFAULT ''",
				'frules_closed'	=> "tinyint(1) NOT NULL DEFAULT '0'",
				//prefix
				'frules_visible'	=> "tinyint(1) NOT NULL DEFAULT '1'",
				'frules_dateline'	=> "int(10) NOT NULL DEFAULT '1'",
				'startdate'	=> "int(10) NOT NULL DEFAULT '0'",
				'enddate'	=> "int(10) NOT NULL DEFAULT '0'",
				'disporder'	=> "int(10) NOT NULL DEFAULT '1'",
				'prymary_key'	=> "aid"
			)
		);

		return $tables;
	}

	// Verify DB tables
	function _db_verify_tables()
	{
		global $db;

		$collation = $db->build_create_table_collation();
		foreach($this->_db_tables() as $table => $fields)
		{
			if($db->table_exists($table))
			{
				foreach($fields as $field => $definition)
				{
					if($field == 'prymary_key')
					{
						continue;
					}

					if($db->field_exists($field, $table))
					{
						$db->modify_column($table, "`{$field}`", $definition);
					}
					else
					{
						$db->add_column($table, $field, $definition);
					}
				}
			}
			else
			{
				$query = "CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."{$table}` (";
				foreach($fields as $field => $definition)
				{
					if($field == 'prymary_key')
					{
						$query .= "PRIMARY KEY (`{$definition}`)";
					}
					else
					{
						$query .= "`{$field}` {$definition},";
					}
				}
				$query .= ") ENGINE=MyISAM{$collation};";
				$db->write_query($query);
			}
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

		$query = $db->simple_select('ougc_annbars', '*', 'startdate>\''.TIME_NOW.'\'');

		$sqlnotin = array(0);

		while($aid = (int)$db->fetch_field($query, 'aid'))
		{
			$sqlnotin[] = $aid;
		}

		$query = $db->simple_select('ougc_annbars', '*', 'enddate>=\''.TIME_NOW.'\' AND aid NOT IN (\''.implode('\',\'', $sqlnotin).'\')', array('order_by' => 'disporder'));

		$update = array();

		while($annbar = $db->fetch_array($query))
		{
			$aid = (int)$annbar['aid'];
			unset($annbar['aid'], $annbar['name']);
			$update[$aid] = $annbar;
		}

		$db->free_result($query);

		$cache->update('ougc_annbars', $update);

		return true;
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
				'name'				=> $bar['name'],
				'content'			=> $bar['content'],
				'style'				=> $bar['style'],
				'groups'			=> explode(',', $bar['groups']),
				'visible'			=> (int)$bar['visible'],
				'forums'			=> explode(',', $bar['forums']),
				'scripts'			=> $bar['scripts'],
				'dismissible'		=> $bar['dismissible'],
				'frules'			=> (int)$bar['frules'],
				'frules_fid'		=> explode(',', $bar['frules_fid']),
				'frules_closed'		=> (int)$bar['frules_closed'],
				'frules_visible'	=> (int)$bar['frules_visible'],
				'frules_dateline'	=> (int)$bar['frules_dateline'],
				'startdate'			=> $bar['startdate'],
				'startdate_day'		=> date('j', $bar['startdate']),
				'startdate_month'	=> date('n', $bar['startdate']),
				'startdate_year'	=> date('Y', $bar['startdate']),
				'enddate'			=> $bar['enddate'],
				'enddate_day'		=> date('j', $bar['enddate']),
				'enddate_month'		=> date('n', $bar['enddate']),
				'enddate_year'		=> date('Y', $bar['enddate'])
			);
		}
		else
		{
			$this->bar_data = array(
				'name'				=> '',
				'content'			=> '',
				'style'				=> 'black',
				'groups'			=> array(),
				'visible'			=> 1,
				'forums'			=> array(),
				'scripts'			=> '',
				'dismissible'		=> 1,
				'frules'			=> 0,
				'frules_fid'		=> array(),
				'frules_closed'		=> 0,
				'frules_visible'	=> 1,
				'frules_dateline'	=> 1,
				'startdate'			=> TIME_NOW,
				'startdate_day'		=> date('j', TIME_NOW),
				'startdate_month'	=> date('n', TIME_NOW),
				'startdate_year'	=> date('Y', TIME_NOW),
				'enddate'			=> TIME_NOW,
				'enddate_day'		=> date('j', TIME_NOW),
				'enddate_month'		=> date('n', TIME_NOW),
				'enddate_year'		=> date('Y', TIME_NOW)
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

		if(!in_array($this->bar_data['style'], $this->styles) && !trim($this->bar_data['style']))
		{
			$this->validate_errors[] = $lang->ougc_annbars_error_invalidstyle;
			$valid = false;
		}

		foreach(array('start', 'end') as $key)
		{
			$k = $key.'date_';
			if($this->bar_data[$k.'day'] < 1 || $this->bar_data[$k.'day'] > 31 || $this->bar_data[$k.'month'] < 1 || $this->bar_data[$k.'month'] > 12 || $this->bar_data[$k.'year'] < 2000 || $this->bar_data[$k.'year'] > 2100 || ($this->bar_data[$k.'month'] == 2 && $this->bar_data[$k.'day'] > 29))
			{
				$lang_var = 'ougc_annbars_error_invalid'.$key.'date';
				$this->validate_errors[] = $lang->{$lang_var};
				$valid = false;
				break;
			}
			${$k} = $this->_mktime($this->bar_data[$k.'day'], $this->bar_data[$k.'month'], $this->bar_data[$k.'year']);
		}

		if($valid && $startdate_ > $enddate_)
		{
			$this->validate_errors[] = $lang->ougc_annbars_error_invalidstartdate;
			$valid = false;
		}

		return $valid;
	}

	function insert_bar($data=array(), $update=false, $aid=0)
	{
		global $db;

		$insert_data = array(
			'name'				=> $db->escape_string((isset($data['name']) ? $data['name'] : '')),
			'content'			=> $db->escape_string((isset($data['content']) ? $data['content'] : '')),
			'style'				=> $db->escape_string((trim($data['style']) ? trim($data['style']) : 'black')),
			'groups'			=> '',
			'visible'			=> (int)$data['visible'],
			'forums'			=> '',
			'scripts'			=> $db->escape_string($data['scripts']),
			'dismissible'		=> (int)$data['dismissible'],
			'frules'			=> (int)$data['frules'],
			'frules_fid'		=> '',
			'frules_closed'		=> (int)$data['frules_closed'],
			'frules_visible'	=> (int)$data['frules_visible'],
			'frules_dateline'	=> (int)$data['frules_dateline'],
			'startdate'			=> TIME_NOW,
			'enddate'			=> TIME_NOW
		);

		// Groups
		if($data['groups'] == -1)
		{
			$insert_data['groups'] = -1;
		}
		elseif(is_array($data['groups']))
		{
			$gids = array();
			foreach($data['groups'] as $gid)
			{
				$gids[] = (int)$gid;
			}
			$insert_data['groups'] = $db->escape_string(implode(',', $gids));
		}

		// Forums
		if($data['forums'] == -1)
		{
			$insert_data['forums'] = -1;
		}
		elseif(is_array($data['forums']))
		{
			$gids = array();
			foreach($data['forums'] as $gid)
			{
				$gids[] = (int)$gid;
			}
			$insert_data['forums'] = $db->escape_string(implode(',', $gids));
		}
	
		if(isset($data['frules_fid']))
		{
			$insert_data['frules_fid'] = $db->escape_string(implode(',', (array)$data['frules_fid']));
		}

		// Date
		foreach(array('start', 'end') as $key)
		{
			$k = $key.'date_';
			if(isset($data[$k.'month']) && isset($data[$k.'day']) && isset($data[$k.'year']))
			{
				$insert_data[$key.'date'] = $this->_mktime($data[$k.'month'], $data[$k.'day'], $data[$k.'year']);
			}
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

	// Log admin action
	function parse_message($message, $startdate=TIME_NOW, $enddate=TIME_NOW, $threads_count='')
	{
		global $mybb, $parser, $lang;

		if(!is_object($parser))
		{
			require_once MYBB_ROOT.'inc/class_parser.php';
			$parser = new postParser;
		}

		$message = $parser->parse_message($lang->sprintf($message, $mybb->user['username'], $mybb->settings['bbname'], $mybb->settings['bburl'], my_date($mybb->settings['dateformat'], $startdate)/*.', '.my_date($mybb->settings['timeformat'], $startdate)*/, my_date($mybb->settings['dateformat'], $enddate)/*.', '.my_date($mybb->settings['timeformat'], $enddate)*/, $threads_count, $threads_count, $threads_count), array(
			'allow_html'		=> 1,
			'allow_smilies'		=> 1,
			'allow_mycode'		=> 1,
			'filter_badwords'	=> 1,
			'shorten_urls'		=> 0
		));

		return $message;
	}

	// Clean input
	function clean_ints($val, $implode=false)
	{
		if(!is_array($val))
		{
			$val = (array)explode(',', $val);
		}

		foreach($val as $k => &$v)
		{
			$v = (int)$v;
		}

		$val = array_filter($val);

		if($implode)
		{
			$val = (string)implode(',', $val);
		}

		return $val;
	}

	function _mktime($month, $day, $year)
	{
		return (int)mktime(0, 0, 0, (int)$month, (int)$day, (int)$year);
		//return (int)mktime(date('H', TIME_NOW), date('i', TIME_NOW), date('s', TIME_NOW), $month, $day, $year);
	}

	function update_task($action=2)
	{
		global $db, $lang;
		$this->lang_load();

		$where = 'file=\'ougc_annbars\'';

		switch($action)
		{
			case 1:
			case 0:
				$db->update_query('tasks', array('enabled' => $action), $where);
				break;
			case -1:
				$db->delete_query('tasks', $where);
				break;
			default:
				$query = $db->simple_select('tasks', 'tid', $where);
				if(!$db->fetch_field($query, 'tid'))
				{
					include_once MYBB_ROOT.'inc/functions_task.php';

					$_ = $db->escape_string('*');

					$new_task = array(
						'title'			=> $db->escape_string($lang->ougc_annbars_plugin),
						'description'	=> $db->escape_string($lang->ougc_annbars_plugin_d),
						'file'			=> $db->escape_string('ougc_annbars'),
						'minute'		=> 0,
						'hour'			=> $_,
						'day'			=> $_,
						'weekday'		=> $_,
						'month'			=> $_,
						'enabled'		=> 1,
						'logging'		=> 1
					);

					$new_task['nextrun'] = fetch_next_run($new_task);

					$db->insert_query('tasks', $new_task);
				}
				break;
		}
	}
}
$GLOBALS['annbars'] = new OUGC_ANNBARS;

// Activate the plugin.
function ougc_annbars_activate()
{
	global $lang, $annbars, $PL, $cache, $db;
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

	$PL->settings('ougc_annbars', $lang->ougc_annbars_plugin, $lang->ougc_annbars_plugin_d, array(
		'limit'	=> array(
			'title'			=> $lang->ougc_annbars_setting_limit,
			'description'	=> $lang->ougc_annbars_setting_limit_desc,
			'optionscode'	=> 'numeric',
			'value'			=>	5,
		),
		'dismisstime'	=> array(
			'title'			=> $lang->ougc_annbars_setting_dismisstime,
			'description'	=> $lang->ougc_annbars_setting_dismisstime_desc,
			'optionscode'	=> 'numeric',
			'value'			=>	7,
		)
	));

	$PL->templates('ougcannbars', $lang->ougc_annbars_plugin, array(
		'bar'		=> '<div class="ougc_annbars_{$bar[\'style\']}" id="ougcannbars_bar_{$key}">
	{$dismiss_button}
	{$bar[\'content\']}
</div><br/>',
		'dismiss'		=> '<div class="float_right dismiss_notice"><img src="{$theme[\'imgdir\']}/dismiss_notice.png" alt="{$lang->dismiss_notice}" title="{$lang->dismiss_notice}" /></div>',
		'wrapper'	=> '{$ougc_annbars}
<script type="text/javascript">
	var OUGCAnnoucementBars = {$time};
	var OUGCAnnoucementBarsCutoff = {$days};
</script>
<script type="text/javascript" src="{$mybb->settings[\'bburl\']}/jscripts/ougc_annbars.js?ver=1820"></script>'
	));

	// Update administrator permissions
	change_admin_permission('forums', 'ougc_annbars');

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
	$annbars->_db_verify_tables();
	if($plugins['annbars'] <= 1801)
	{
		$annbars->update_task();
	}
	/*~*~* RUN UPDATES END *~*~*/

	$plugins['annbars'] = $info['versioncode'];
	$cache->update('ougc_plugins', $plugins);

	// Fill cache
	$annbars->update_cache();
	$annbars->update_task(1);
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

	// Update administrator permissions
	change_admin_permission('forums', 'ougc_annbars', 0);
	$annbars->update_task(0);
}

// Install the plugin.
function ougc_annbars_install()
{
	global $db, $annbars;
	$annbars->meets_requirements() or $annbars->admin_redirect($annbars->message, true);

	$annbars->_db_verify_tables();

	$annbars->update_task();
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

	// Drop DB entries
	foreach($ougc_pages->_db_tables() as $name => $table)
	{
		$db->drop_table($name);
	}

	// Delete the cache.
	$PL->cache_delete('ougc_annbars');

	// Delete stylesheet
	$PL->stylesheet_delete('ougc_annbars');

	// Delete settings
	$PL->settings_delete('ougc_annbars');

	// Delete template/group
	$PL->templates_delete('ougcannbars');

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

	// Update administrator permissions
	change_admin_permission('forums', 'ougc_annbars', -1);
}

// We are here, so here we write the other global hook too..
function ougc_annbars_show(&$page)
{
	if(my_strpos($page, '<!--OUGC_ANNBARS-->') === false)
	{
		return;
	}

	global $mybb, $theme;

	$limit = (isset($mybb->settings['ougc_annbars_limit']) ? (int)$mybb->settings['ougc_annbars_limit'] : 0);

	if($limit >= 0)
	{
		global $PL, $annbars;

		$PL or require_once PLUGINLIBRARY;

		$bars = $PL->cache_read('ougc_annbars');

		$ougc_annbars = '';

		if(is_array($bars))
		{
			global $lang, $templates;

			$annbars->lang_load();

			$username = $lang->guest;

			if($mybb->user['uid'])
			{
				$username = $mybb->user['username'];
			}

			$fid = false;

			switch(THIS_SCRIPT)
			{
				// $fid
				case 'announcements.php':
				case 'editpost.php':
				case 'forumdisplay.php':
				case 'newreply.php':
				case 'newthread.php':
				case 'printthread.php':
				case 'polls.php':
				case 'sendthread.php':
				case 'showthread.php':
				case 'ratethread.php':
				case 'moderation.php':
				// $forum
				case 'polls.php':
				case 'sendthread.php':
				case 'report.php':
				// $mybb
				case 'misc.php':
					global $fid, $forum;
					!empty($fid) or $fid = $forum['fid'];
					!empty($fid) or $fid = $mybb->get_input('fid', 1);
					break;
			}

			$fid = (int)$fid;

			if(!empty($_SERVER['PATH_INFO']))
			{
				$location = htmlspecialchars_uni($_SERVER['PATH_INFO']);
			}
			elseif(!empty($_ENV['PATH_INFO']))
			{
				$location = htmlspecialchars_uni($_ENV['PATH_INFO']);
			}
			elseif(!empty($_ENV['PHP_SELF']))
			{
				$location = htmlspecialchars_uni($_ENV['PHP_SELF']);
			}
			else
			{
				$location = htmlspecialchars_uni($_SERVER['PHP_SELF']);
			}

			$count = 1;

			foreach($bars as $key => $bar)
			{
				if($limit != 0 && $count > $limit)
				{
					break;
				}

				if(!$bar['groups'] || ($bar['groups'] != -1 && !is_member($bar['groups'])))
				{
					continue;
				}

				if(!$bar['visible'])
				{
					$valid_forum = false;

					if($bar['forums'] && $fid)
					{
						if($bar['forums'] == -1 || my_strpos(','.$bar['forums'].',', ','.$fid.',') !== false)
						{
							$valid_forum = true;
						}
					}

					if($bar['scripts'] && !$valid_forum)
					{
						$continue = true;
						$scripts = explode("\n", $bar['scripts']);
						foreach($scripts as $script)
						{
							if(my_strpos($script, '{|}') !== false)
							{
								$inputs = explode('{|}', $script);
								$script = $inputs[0];
								$inputs = explode('|', $inputs[1]);
							}

							if(my_strtolower($script) != my_strtolower(basename($location)))
							{
								continue;
							}

							$continue = false;

							if($inputs)
							{
								foreach($inputs as $key)
								{
									if(my_strpos($key, '=') !== false)
									{
										$key = explode('=', $key);
										$value = $key[1];
										$key = $key[0];
									}

									if(isset($mybb->input[$key]))
									{
										$continue = false;

										if($mybb->get_input($key) == (string)$value)
										{
											$continue = false;
											break;
										}

										$continue = false;
									}
									else
									{
										$continue = true;
									}
								}
							}
						}

						if($continue)
						{
							continue;
						}
					}
					//foo.php|foo.php?value|foo.php?value,value
					//foo.php{|}key|key=value
				}

				if($fid && $bar['forums'] != -1 && ($bar['forums'] == '' || my_strpos(','.$bar['forums'].',', ','.$fid.',') === false))
				{
					#continue;
				}

				++$count;

				$threads_count = '';

				if($bar['frules'])
				{
					global $db;

					$fids = implode("','", array_map('intval', explode(',', $bar['frules_fid'])));

					$thread_time_limit = TIME_NOW - 60 * 60 * 24 * 7 * (int)$bar['frules_dateline'];

					$where = array(
						"fid IN ('{$fids}')"
					);

					if($bar['frules_dateline'])
					{
						$where[] = "dateline>'{$thread_time_limit}'";
					}

					if($bar['frules_closed'])
					{
						$where[] = "closed='1'";
					}
					else
					{
						$where[] = "closed NOT LIKE 'moved|%'";
					}

					$bar['frules_visible'] = (int)$bar['frules_visible'];

					$where[] = "visible='{$bar['frules_visible']}'";

					$query = $db->simple_select('threads', 'COUNT(tid) AS total_threads', implode(' AND ', $where), array('limit' => 1));

					$threads_count = (int)$db->fetch_field($query, 'total_threads');

					if(!$threads_count)
					{
						continue;
					}
				}

				if(!in_array($bar['style'], $annbars->styles))
				{
					$bar['style'] = 'custom '.htmlspecialchars_uni($bar['style']);
				}

				$lang_val = 'ougc_annbars_bar_'.$key;

				if(!empty($lang->{$lang_val}))
				{
					$bar['content'] = $lang->{$lang_val};
				}

				$bar['content'] = $annbars->parse_message($bar['content'], $bar['startdate'], $bar['enddate'], $threads_count);

				$dismiss_button = '';

				if($bar['dismissible'])
				{
					$dismiss_button = eval($templates->render('ougcannbars_dismiss'));
				}

				$ougc_annbars .= eval($templates->render('ougcannbars_bar'));
			}
		}

		if($ougc_annbars)
		{
			$time = TIME_NOW;

			$days = TIME_NOW - (60 * 60 * 24 * $mybb->settings['ougc_annbars_dismisstime']);

			$ougc_annbars = eval($templates->render('ougcannbars_wrapper'));
		}

		return str_replace('<!--OUGC_ANNBARS-->', $ougc_annbars, $page);
	}
}

// We like nice stuff
function ougc_annbars_logs(&$log)
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

// Cache manager helper.
function update_ougc_annbars()
{
	global $annbars;

	$annbars->update_cache();
}

if(!function_exists('ougc_getpreview'))
{
	/**
	 * Shorts a message to look like a preview. 2.0
	 * Based off Zinga Burga's "Thread Tooltip Preview" plugin threadtooltip_getpreview() function.
	 *
	 * @param string Message to short.
	 * @param int Maximum characters to show.
	 * @param bool Strip MyCode Quotes from message.
	 * @param bool Strip MyCode from message.
	 * @return string Shortened message
	**/
	function ougc_getpreview($message, $maxlen=100, $stripquotes=true, $stripmycode=true, $parser_options=array())
	{
		// Attempt to remove quotes, skip if going to strip MyCode
		if($stripquotes && !$stripmycode)
		{
			$message = preg_replace(array(
				'#\[quote=([\"\']|&quot;|)(.*?)(?:\\1)(.*?)(?:[\"\']|&quot;)?\](.*?)\[/quote\](\r\n?|\n?)#esi',
				'#\[quote\](.*?)\[\/quote\](\r\n?|\n?)#si',
				'#\[quote\]#si',
				'#\[\/quote\]#si'
			), '', $message);
		}

		// Attempt to remove any MyCode
		if($stripmycode)
		{
			global $parser;
			if(!is_object($parser))
			{
				require_once MYBB_ROOT.'inc/class_parser.php';
				$parser = new postParser;
			}

			$parser_options = array_merge(array(
				'allow_html'		=>	0,
				'allow_mycode'		=>	1,
				'allow_smilies'		=>	0,
				'allow_imgcode'		=>	1,
				'filter_badwords'	=>	1,
				'nl2br'				=>	0
			), $parser_options);

			$message = $parser->parse_message($message, $parser_options);

			// before stripping tags, try converting some into spaces
			$message = preg_replace(array(
				'~\<(?:img|hr).*?/\>~si',
				'~\<li\>(.*?)\</li\>~si'
			), array(' ', "\n* $1"), $message);

			$message = unhtmlentities(strip_tags($message));
		}

		// convert \xA0 to spaces (reverse &nbsp;)
		$message = trim(preg_replace(array('~ {2,}~', "~\n{2,}~"), array(' ', "\n"), strtr($message, array(utf8_encode("\xA0") => ' ', "\r" => '', "\t" => ' '))));

		// newline fix for browsers which don't support them
		$message = preg_replace("~ ?\n ?~", " \n", $message);

		// Shorten the message if too long
		if(my_strlen($message) > $maxlen)
		{
			$message = my_substr($message, 0, $maxlen-1).'...';
		}

		return htmlspecialchars_uni($message);
	}
}

if(!function_exists('ougc_print_selection_javascript'))
{
	function ougc_print_selection_javascript()
	{
		static $already_printed = false;

		if($already_printed)
		{
			return;
		}

		$already_printed = true;

		echo "<script type=\"text/javascript\">
		function checkAction(id)
		{
			var checked = '';

			$('.'+id+'_forums_groups_check').each(function(e, val)
			{
				if($(this).prop('checked') == true)
				{
					checked = $(this).val();
				}
			});

			$('.'+id+'_forums_groups').each(function(e)
			{
				$(this).hide();
			});

			if($('#'+id+'_forums_groups_'+checked))
			{
				$('#'+id+'_forums_groups_'+checked).show();
			}
		}
	</script>";
	}
}
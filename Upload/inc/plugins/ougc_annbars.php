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
defined('PLUGINLIBRARY') or define('PLUGINLIBRARY', constant('MYBB_ROOT').'inc/plugins/pluginlibrary.php');

define('ougc\AnnouncementBars\ROOT', constant('MYBB_ROOT') . 'inc/plugins/ougc/AnnouncementBars');

require_once \ougc\AnnouncementBars\ROOT . '/core.php';

if (defined('IN_ADMINCP')) {
    require_once \ougc\AnnouncementBars\ROOT . '/hooks/admin.php';

    \ougc\AnnouncementBars\Core\addHooks('ougc\AnnouncementBars\Hooks\Admin');
} else {
    require_once \ougc\AnnouncementBars\ROOT . '/hooks/forum.php';

    \ougc\AnnouncementBars\Core\addHooks('ougc\AnnouncementBars\Hooks\Forum');
}

// Add our hook
if(!defined('IN_ADMINCP'))
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
		'version'		=> '1.8.36',
		'versioncode'	=> 1836,
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
        \ougc\AnnouncementBars\Core\loadLanguage();
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
				'frules'	=> "text NOT NULL",
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
				$query = "CREATE TABLE IF NOT EXISTS `{$db->table_prefix}{$table}` (";
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

		$query = $db->simple_select('ougc_annbars', '*', 'startdate>\''.constant('TIME_NOW').'\'');

		$sqlnotin = array(0);

		while($aid = (int)$db->fetch_field($query, 'aid'))
		{
			$sqlnotin[] = $aid;
		}

		$query = $db->simple_select('ougc_annbars', '*', 'enddate>=\''.constant('TIME_NOW').'\' AND aid NOT IN (\''.implode('\',\'', $sqlnotin).'\')', array('order_by' => 'disporder'));

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
		global $db, $annbars;

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
				'frules'			=> $bar['frules'],
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
				'frules'			=> '',
				'startdate'			=> constant('TIME_NOW'),
				'startdate_day'		=> date('j', constant('TIME_NOW')),
				'startdate_month'	=> date('n', constant('TIME_NOW')),
				'startdate_year'	=> date('Y', constant('TIME_NOW')),
				'enddate'			=> constant('TIME_NOW'),
				'enddate_day'		=> date('j', constant('TIME_NOW')),
				'enddate_month'		=> date('n', constant('TIME_NOW')),
				'enddate_year'		=> date('Y', constant('TIME_NOW'))
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

		$name = trim($this->bar_data['name']);

		if(!$name || my_strlen($name) > 100)
		{
			$this->validate_errors[] = $lang->ougc_annbars_error_invalidname;
		}

		$content = trim($this->bar_data['content']);

		if(!$content)
		{
			$this->validate_errors[] = $lang->ougc_annbars_error_invalidcontent;
		}

		if(!trim($this->bar_data['style']))
		{
			$this->validate_errors[] = $lang->ougc_annbars_error_invalidstyle;
		}

		foreach(array('start', 'end') as $key)
		{
			$k = $key.'date_';

			$lang_var = 'ougc_annbars_error_invalid'.$key.'date';

			if(
				$this->bar_data[$k.'day'] < 1 ||
				$this->bar_data[$k.'day'] > 31 ||
				$this->bar_data[$k.'month'] < 1 ||
				$this->bar_data[$k.'month'] > 12 ||
				$this->bar_data[$k.'year'] < 2000 ||
				$this->bar_data[$k.'year'] > 2100
			)
			{
				$this->validate_errors[] = $lang->{$lang_var};

				break;
			}
			else
			{
				$maxDays = cal_days_in_month(CAL_GREGORIAN, $this->bar_data[$k.'month'], $this->bar_data[$k.'year']);
	
				if(
					$this->bar_data[$k.'day'] > $maxDays
				)
				{
					$this->validate_errors[] = $lang->{$lang_var};
	
					break;
				}
			}

			${$k} = $this->_mktime($this->bar_data[$k.'month'], $this->bar_data[$k.'day'], $this->bar_data[$k.'year']);
		}

		if($startdate_ > $enddate_)
		{
			$this->validate_errors[] = $lang->ougc_annbars_error_invalidstartdate;
		}

		return empty($this->validate_errors);
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
			'frules'			=> $db->escape_string($data['frules']),
			'startdate'			=> constant('TIME_NOW'),
			'enddate'			=> constant('TIME_NOW')
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
	function parse_message($message, $startdate=0, $enddate=0)
	{
		global $mybb, $parser, $lang;

        if($startdate === 0)
        {
            $startdate = constant('TIME_NOW');
        }

        if($enddate === 0)
        {
            $enddate = constant('TIME_NOW');
        }

		if(!is_object($parser))
		{
			require_once constant('MYBB_ROOT').'inc/class_parser.php';
			$parser = new postParser;
		}

		$message = $parser->parse_message(
            $lang->sprintf(
                $message,
                $mybb->user['username'],
                $mybb->settings['bbname'],
                $mybb->settings['bburl'],
                my_date($mybb->settings['dateformat'], $startdate),
                my_date($mybb->settings['dateformat'], $enddate)
            ), array(
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
		return (int)gmmktime(0, 0, 0, (int)$month, (int)$day, (int)$year);
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
					include_once constant('MYBB_ROOT').'inc/functions_task.php';

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
	require_once constant('MYBB_ROOT').'/inc/adminfunctions_templates.php';
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

	$PL->templates('ougcannbars', 'OUGC Announcement Bars', array(
		'bar'		=> '<br/><div class="ougc_annbars_{$bar[\'style\']}" id="{$dismissID}">
	{$dismiss_button}
	{$bar[\'content\']}
</div>',
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

    $annbars->_db_verify_tables();

	/*~*~* RUN UPDATES START *~*~*/
    /*
    if($plugins['annbars'] <= 1836)
    {
        foreach(['frules_fid', 'frules_closed', 'frules_visible', 'frules_dateline'] as $fieldName)
        {
            if($db->field_exists('ougc_annbars', $fieldName))
            {
                $db->drop_column('ougc_annbars', $fieldName);
            }
        }
    }
    */

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
	require_once constant('MYBB_ROOT').'/inc/adminfunctions_templates.php';
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
	foreach($annbars->_db_tables() as $name => $table)
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
				require_once constant('MYBB_ROOT').'inc/class_parser.php';
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
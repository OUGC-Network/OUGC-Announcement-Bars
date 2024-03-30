<?php

/***************************************************************************
 *
 *	OUGC Announcement Bars plugin (/inc/tasks/ougc_annbars.php)
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

function task_ougc_annbars($task)
{
    global $lang;
	global $annbars;

	if(!($annbars instanceof OUGC_ANNBARS))
	{
		$annbars = new OUGC_ANNBARS;
	}

	$annbars->lang_load();

	$annbars->update_cache();

	add_task_log($task, $lang->task_ougc_annbars_ran);
}
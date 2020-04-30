/***************************************************************************
 *
 *	OUGC Announcement Bars plugin (/jscripts/ougc_annbars.js)
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

var OUGC_Plugins = OUGC_Plugins || {};

$.extend(true, OUGC_Plugins, {
	initAlertsSystem: function()
	{
		$('div[id^="ougcannbars_bar_"]').each(function () {
			var id = $(this).attr('id');

			if(Cookie.get(id)) {
				$('#' + id).hide();
			}

			$('#' + id + ' .dismiss_notice').on('click', function() {
				$('#' + id).fadeOut(250, function () {
					Cookie.set(id, 7);
				});
			});
		});
	},
});

OUGC_Plugins.initAlertsSystem();

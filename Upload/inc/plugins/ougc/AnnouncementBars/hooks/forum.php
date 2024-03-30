<?php

/***************************************************************************
 *
 *    OUGC Announcement Bars plugin (/inc/plugins/ougc/AnnouncementBars/hooks/forum.php)
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

namespace ougc\AnnouncementBars\Hooks\Forum;

use MyBB;

function pre_output_page(&$page)
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

            switch(constant('THIS_SCRIPT'))
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
                    continue;
                }

                ++$count;

                $replacementParams = [];

                $displayBar = false;

                if($bar['frules'])
                {
                    global $db;

                    $whereClauses = [];

                    $rulesScripts = json_decode($bar['frules'], true);

                    if(!empty($rulesScripts))
                    {
                        if(isset($rulesScripts['threadCountRules']) || isset($rulesScripts['threadCountRule']))
                        {
                            $threadCountRules = isset($rulesScripts['threadCountRules']) ? $rulesScripts['threadCountRules'] : [$rulesScripts['threadCountRule']];

                            foreach($threadCountRules as $threadCountRule)
                            {
                                if(isset($threadCountRule['forumIDs']))
                                {
                                    $forumIDs = implode("','", array_map('intval', $threadCountRule['forumIDs']));

                                    $whereClauses[] = "fid IN ('{$forumIDs}')";
                                }

                                $prefixNot = '';

                                if(isset($threadCountRule['hasPrefix']))
                                {
                                    if($threadCountRule['hasPrefix'] === true)
                                    {
                                        $whereClauses['prefix'] = "prefix>'0'";
                                    } else {
                                        $whereClauses['prefix'] = "prefix='0'";

                                        $prefixNot = 'NOT';
                                    }
                                }

                                if(isset($threadCountRule['prefixIDs']))
                                {
                                    $prefixIDs = implode("','", array_map('intval', $threadCountRule['prefixIDs']));

                                    $whereClauses['prefix'] = "prefix {$prefixNot} IN ('{$prefixIDs}')";
                                }

                                if(isset($threadCountRule['hasPoll']))
                                {
                                    if($threadCountRule['hasPoll'] === true)
                                    {
                                        $whereClauses[] = "poll='1'";
                                    } else {
                                        $whereClauses[] = "poll='0'";
                                    }
                                }

                                if(isset($threadCountRule['createDaysCut']))
                                {
                                    $createDaysStamp = constant('TIME_NOW') - 60 * 60 * 24 * 7 * (int)$threadCountRule['createDaysCut'];

                                    $whereClauses[] = "dateline>'{$createDaysStamp}'";
                                }

                                $repliesOperator = '>';

                                if(isset($threadCountRule['hasReplies']))
                                {
                                    if($threadCountRule['hasReplies'] === true)
                                    {
                                        $whereClauses['replies'] = "replies>'0'";
                                    } else {
                                        $whereClauses['replies'] = "replies='0'";

                                        $repliesOperator = '<';
                                    }
                                }

                                if(isset($threadCountRule['hasRepliesCount']))
                                {
                                    $hasRepliesCount = (int)$threadCountRule['hasRepliesCount'];

                                    $whereClauses['replies'] = "replies{$repliesOperator}'{$hasRepliesCount}'";
                                }

                                if(isset($threadCountRule['closedThreads']))
                                {
                                    if($threadCountRule['closedThreads'] === true)
                                    {
                                        $whereClauses[] = "closed='1'";
                                    } else {
                                        $whereClauses[] = "closed NOT LIKE 'moved|%'";
                                    }
                                }

                                if(isset($threadCountRule['stuckThreads']))
                                {
                                    if($threadCountRule['stuckThreads'] === true)
                                    {
                                        $whereClauses[] = "sticky='1'";
                                    } else {
                                        $whereClauses[] = "sticky='0'";
                                    }
                                }

                                $visibleStatuses = ['in' => [], 'inNot' => []];

                                if(isset($threadCountRule['visibleThreads']))
                                {
                                    if($threadCountRule['visibleThreads'] === true)
                                    {
                                        $visibleStatuses['in'][] = 1;
                                    } else {
                                        $visibleStatuses['notIn'][] = 1;
                                    }
                                }

                                if(isset($threadCountRule['unapprovedThreads']))
                                {
                                    if($threadCountRule['unapprovedThreads'] === true)
                                    {
                                        $visibleStatuses['in'][] = 0;
                                    } else {
                                        $visibleStatuses['notIn'][] = 0;
                                    }
                                }

                                if(isset($threadCountRule['deletedThreads']))
                                {
                                    if($threadCountRule['deletedThreads'] === true)
                                    {
                                        $visibleStatuses['in'][] = -1;
                                    } else {
                                        $visibleStatuses['notIn'][] = -1;
                                    }
                                }

                                if(!empty($visibleStatuses))
                                {
                                    if(!empty($visibleStatuses['in']))
                                    {
                                        $inString = implode("','", $visibleStatuses['in']);

                                        $whereClauses[] = "visible IN ('{$inString}')";
                                    }

                                    if(!empty($visibleStatuses['notIn']))
                                    {
                                        $notInString = implode("','", $visibleStatuses['notIn']);

                                        $whereClauses[] = "visible NOT IN ('{$notInString}')";
                                    }
                                }

                                if(function_exists('ougc_showinportal_info') && isset($threadCountRule['showInPortal']))
                                {
                                    if($threadCountRule['showInPortal'] === true)
                                    {
                                        $whereClauses[] = "showinportal='1'";
                                    } else {
                                        $whereClauses[] = "showinportal='0'";
                                    }
                                }

                                $dbQuery = $db->simple_select(
                                    'threads',
                                    'COUNT(tid) AS total_threads',
                                    implode(' AND ', $whereClauses)
                                );

                                $queryResult = (int)$db->fetch_field($dbQuery, 'total_threads');

                                if(isset($threadCountRule['displayComparisonOperator']))
                                {
                                    $displayComparisonValue = isset($threadCountRule['displayComparisonValue']) ? $threadCountRule['displayComparisonValue'] : 1;

                                    switch($threadCountRule['displayComparisonOperator'])
                                    {
                                        case '<':
                                            if($queryResult < $displayComparisonValue)
                                            {
                                                $displayBar = true;
                                            }
                                            break;
                                        case '>':
                                            if($queryResult > $displayComparisonValue)
                                            {
                                                $displayBar = true;
                                            }
                                            break;
                                    }

                                    $replacementParams["{$threadCountRule['displayKey']}"] = my_number_format($queryResult);
                                } elseif($queryResult) {
                                    $displayBar = true;
                                }

                                if(isset($threadCountRule['displayKey']) && my_strlen($threadCountRule['displayKey']) > 2)
                                {
                                    $replacementParams["{{$threadCountRule['displayKey']}}"] = my_number_format($queryResult);
                                }

                                // we only allow single forum rule for the time being
                                break;
                            }
                        }
                    }
                }

                if(!$displayBar)
                {
                    continue;
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

                if(!empty($replacementParams))
                {
                    $bar['content'] = str_replace(array_keys($replacementParams), array_values($replacementParams), $bar['content']);
                }

                $bar['content'] = $annbars->parse_message($bar['content'], $bar['startdate'], $bar['enddate']);

                $dismiss_button = $dismissID = '';

                if($bar['dismissible'])
                {
                    $dismiss_button = eval($templates->render('ougcannbars_dismiss'));
                    $dismissID = "ougcannbars_bar_{$key}";
                }

                $ougc_annbars .= eval($templates->render('ougcannbars_bar'));
            }
        }

        if($ougc_annbars)
        {
            $time = constant('TIME_NOW');

            $days = constant('TIME_NOW') - (60 * 60 * 24 * $mybb->settings['ougc_annbars_dismisstime']);

            $ougc_annbars = eval($templates->render('ougcannbars_wrapper'));
        }

        return str_replace('<!--OUGC_ANNBARS-->', $ougc_annbars, $page);
    }
}
<?php
/*  Copyright 2010  Black Drumm, Inc.  (email : info@blackdrumm.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*
Plugin name: Clickst Share
Plugin URI: http://click.st/wordpress
Description: Add buttons to your posts that let your users share on facebook, twitter, buzz and email. Track shares and referrals and browse a graph of how your users are connected.
Version: 0.2.2
Author: Black Drumm, Inc.
Author URI: http://www.blackdrumm.com/
License: GPL2
*/

global $clickst_domain;
$clickst_domain = 'click.st';

global $clickst_endpoint;
$clickst_endpoint = 'http://'.$clickst_domain.'/rest';

global $clickst_networks;
$clickst_networks = array(
    'facebook'=> 'Facebook',
    'twitter' => 'Twitter',
    'buzz' => 'Buzz',
    'myspace' => 'MySpace',
    'linkedin' => 'LinkedIn',
    'email' => 'Email',
);


function clickst_call($method, $params, $key=null, $secret=null)
{
    global $clickst_endpoint;
    
    if ($key)
    {
        $params['key'] = $key;
        
        if ($secret)
        {
            $params['nonce'] = sha1(time().'-'.rand(0, 100));
            $params['signature'] = sha1($key.$params['nonce'].$secret);
        }
    }
    
    $query = http_build_query($params);
    $data = @file_get_contents($clickst_endpoint.'/'.$method.'?'.$query);
    
    if (!$data)
    {
        throw new Exception('Request to '.$clickst_endpoint.' failed.');
    }
    
    $response = json_decode($data, true);
    
    if (!$response)
    {
        throw new Exception('Invalid response from '.$clickst_endpoint);
    }
    
    if ($response['error'])
    {
        throw new Exception('Clickst: '.$response['error']);
    }
    
    return $response['result'];
}


function clickst_activate()
{
    global $clickst_networks;
    
    add_option('clickst_key', '');
    add_option('clickst_secret', '');
    add_option('clickst_domain', '');
    add_option('clickst_share_label', 'Share:');
    
    foreach ($clickst_networks as $k => $v)
    {
        add_option('clickst_share_'.$k, 1);
    }
}

register_activation_hook('clickst.php', 'clickst_activate');

function clickst_options()
{
    global $clickst_networks;
    
    if (!current_user_can('manage_options'))
    {
        wp_die('You don\'t have sufficient privileges to access this page.');
    }
?>
<style type="text/css">
#clickst-settings .share-options .share-option
{
    width: 125px;
    padding: 5px 0;
}
#clickst-settings .share-options label span
{
    display: block;
    float: right;
    width: 14px;
    height: 14px;
    background: transparent url(http://click.st/static/img/networks_sprite.png) no-repeat top left;
    margin-top: 4px;
}
#clickst-settings .share-options label.twitter span
{
    background-position: -14px 0;
}

#clickst-settings .share-options label.buzz span
{
    background-position: -56px 0;
}

#clickst-settings .share-options label.email span
{
    background-position: -42px 0;
}

#clickst-settings .share-options label.myspace span
{
    background-position: -28px 0;
}

#clickst-settings .share-options label.linkedin span
{
    background-position: -70px 0;
}
</style>
<div class="wrap" id="clickst-settings">
    <h2>Clickst Settings</h2>
    <form action="options.php" method="post">
        <? settings_fields( 'clickst-settings-group' ) ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">Buttons label</th>
                <td>
                    <input class="regular-text" type="text" name="clickst_share_label" value="<?= get_option('clickst_share_label') ?>" /><br />
                    E.g., "Share:" or "Tell your friends:". This can be left blank if you don't want a label.
                </td>
            </tr>
            <tr valign="top" class="share-options">
                <th scope="row">Which networks should appear?</th>
                <td>
                    <? foreach ($clickst_networks as $k => $v): ?>
                        <div class="share-option"><input type="checkbox" name="clickst_share_<?= $k ?>" id="clickst_share_<?= $k ?>" <?= get_option('clickst_share_'.$k) ? 'checked="true"' : '' ?> /> <label for="clickst_share_<?= $k ?>" class="<?= $k ?>"><?= $v ?> <span></span></label></div>
                    <? endforeach; ?>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Your Clickst key &amp; secret</th>
                <td>
                    <input class="regular-text" type="text" name="clickst_key" value="<?= get_option('clickst_key') ?>" /><br />
                    <input class="regular-text" type="text" name="clickst_secret" value="<?= get_option('clickst_secret') ?>" />
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Your Clickst domain</th>
                <td>
                    <input class="regular-text" type="text" name="clickst_domain" value="<?= get_option('clickst_domain') ?>" />
                    <br />
                    This should match the domain name of your blog.
                </td>
            </tr>
        </table>
        <p class="submit">
        <input type="submit" class="button-primary" value="<? _e('Save Changes') ?>" />
        </p>
    </form>
</div>
<?
}


function clickst_settings_menu()
{
    add_options_page('Clickst Options', 'Clickst', 'manage_options', 'clickst-settings', 'clickst_options');
}


function clickst_register_settings()
{
    global $clickst_networks;
    
    register_setting( 'clickst-settings-group', 'clickst_key' );
    register_setting( 'clickst-settings-group', 'clickst_secret' );
    register_setting( 'clickst-settings-group', 'clickst_domain' );
    register_setting( 'clickst-settings-group', 'clickst_share_label' );
    
    foreach ($clickst_networks as $k => $v)
    {
        register_setting( 'clickst-settings-group', 'clickst_share_'.$k );
    }
}


function clickst_date_selector($name)
{
    $date = new DateTime();
    $year = intval($date->format('Y'));
?>
<select name="<?= $name ?>_month">
    <? for ($i=1; $i<13; $i++): ?>
        <option value="<?= $i ?>"><? $date->setDate($year, $i, 1); echo $date->format('F') ?></option>
    <? endfor; ?>
</select>
<select name="<?= $name ?>_day">
    <? for ($i=1; $i<32; $i++): ?>
        <option value="<?= $i ?>"><?= $i ?></option>
    <? endfor; ?>
</select>
<select name="<?= $name ?>_year">
    <? for ($i=$year-5; $i<$year+1; $i++): ?>
        <option value="<?= $i ?>"><?= $i ?></option>
    <? endfor; ?>
</select>
<?
}


function clickst_stats()
{
    global $clickst_networks, $clickst_domain;
    
    $default_networks = array();
    
    foreach ($clickst_networks as $k => $v)
    {
        if (get_option('clickst_share_'.$k))
        {
            $default_networks[] = $k;
        }
    }
    
    $key = get_option('clickst_key');
    $secret = get_option('clickst_secret');
    
    if (!$key || !$secret)
    {
        echo 'Clickst is not configured properly. Please set a key and secret.';
        return;
    }
    
    $site_ids = array();
    
    foreach (array('shares','referrals') as $n)
    {
        $result = clickst_call(
            'graph.find_nodes', 
            array(
                'sort_by' => array(array('weights.'.$n.'.total', -1)),
                'limit' => 10
            ), 
            $key,
            $secret
        );
        
        foreach ($result['nodes'] as $node)
        {
            $site_ids[] = $node['id'];
        }
    }
    
    $identities = array();
    
    if (count($site_ids) > 0)
    {
        $site_ids = array_unique($site_ids);
        $query_ids = array();
        
        foreach ($site_ids as $site_id)
        {
            $query_ids[] = array('source' => 'site', 'id' => $site_id);
        }
        
        $results = clickst_call(
            'identity.get_list', 
            array(
                'ids' => $query_ids
            ), 
            $key,
            $secret
        );
        
        foreach ($results as $r)
        {
            foreach ($r[1]['global_ids'] as $g)
            {
                if ($g[0] == $key)
                {
                    $user = get_userdata($g[1]);
                    
                    if ($user)
                    {
                        $identities[$r[0]['id']] = array(
                            'confidence' => 1,
                            'memberships' => array(),
                            'profile' => array(
                                'name' => $user->display_name,
                                'email' => $user->user_email
                            ),
                        );
                    }
                }
            }
        }
    }
?>
<link rel="stylesheet" type="text/css" href="http://<?= $clickst_domain ?>/static/css/graph.css" />
<style type="text/css">
.clearfix:after
{
    content:".";
    display:block;
    height:0;
    clear:both;
    visibility:hidden;
}
.clearfix
{
    display:inline-block;
}
/* Hide from IE Mac \*/
.clearfix
{
    display:block;
}
/* End hide from IE Mac */

h2#header
{
    width: 700px;
}

#date
{
    font-size: 12px;
    font-style: normal;
}

#clickst-overview
{
    margin-bottom: 3em;
}

#clickst-overview div
{
    margin-top: 1.5em;
    display: block;
    float: left;
    margin-right: 1.5em;
    background-color: #fff;
    border: 2px solid #ddd;
    padding: 0.5em;
    -webkit-border-radius: 6px;
    -moz-border-radius: 6px;
    -border-radius: 6px;
}

#clickst-overview div span.label,
#clickst-overview div span.current,
#clickst-overview div span.diff
{
    display: block;
    float: left;
}

#clickst-overview div span.label
{
    margin-top: 0.25em;
    font-weight: bold;
    margin-right: 1em;
}
#clickst-overview div span.current
{
    font-size: 2em;
    margin-right: 0.25em;
}
#clickst-overview div span.diff
{
    margin-top: 0.25em;
}

#clickst-overview div span.diff .positive
{
    color: green;
}

#clickst-overview div span.diff .negative
{
    color: red;
}

#referrals-chart
{
    margin: 1.5em 0 3em 0;
    width: 700px;
    height: 250px;
}

#top-posts
{
    margin: 1.5em 0 3em 0;
    width: 700px;
    table-layout: fixed;
}

#top-posts th
{
    text-align: left;
}

#graph
{
    margin-bottom: 3em;
}

#graph #graph-canvas
{
    width: 396px;
    height: 342px;
}

#graph label
{
    vertical-align: baseline;
}

#graph #node-list .nodes li
{
    line-height: 22px;
}
</style>
<div class="wrap">
    <h2 id="header">
        Clickst Stats
        <div id="date">
            <label>From:</label>
            <? clickst_date_selector('start') ?>
            <label>To:</label>
            <? clickst_date_selector('end') ?>
            <input type="submit" name="update" value="Update" />
        </div>
    </h2>
    <div id="clickst-overview" class="clearfix">
        <div class="visitors">
            <span class="label">People that visit:</span>
            <span class="current"></span>
            <span class="diff"></span>
        </div>
        <div class="shares">
            <span class="label">People that share:</span>
            <span class="current"></span>
            <span class="diff"></span>
        </div>
        <div class="referrals">
            <span class="label">Referred people:</span>
            <span class="current"></span>
            <span class="diff"></span>
        </div>
    </div>
    <h3>Sharing by network</h3>
    <div id="referrals-chart">
    </div>
    <table id="top-posts">
        <tr>
            <th scope="column">Top posts by views</th>
            <th scope="column">Top posts by shares</th>
        </tr>
        <tr>
            <td>
                <ol id="posts_by_view">
                </ol>
            </td>
            <td>
                <ol id="posts_by_share">
                </ol>
            </td>
        </tr>
    </table>
    <h3>People</h3>
    <div id="graph" class="clearfix">
        <div id="graph-canvas"></div>
        <div id="node-list">
            <div class="node-details section">
                <div class="head clearfix">
                    <img src="http://<?= $clickst_domain ?>/static/img/person.png" />
                    <div class="head-details">
                        <h3 class="name">Anonymous User</h3>
                        <div class="demographic"></div>
                        <div class="networks clearfix"></div>
                    </div>
                </div>
                <div class="details clearfix">
                </div>
            </div>
            <div class="sorted">
                <h4>Top <span class="limit"></span> people by 
                    <select class="sort">
                    </select>
                </h4>
                <ul class="nodes">
                </ul>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
$ = jQuery
</script>
<script type="text/javascript" src="http://<?= $clickst_domain ?>/static/js/date.js"></script>
<script type="text/javascript" src="http://<?= $clickst_domain ?>/static/js/sanity.js"></script>
<script type="text/javascript" src="http://<?= $clickst_domain ?>/static/js/highcharts-2.0.3.js"></script>
<script type="text/javascript" src="http://<?= $clickst_domain ?>/static/js/raphael.js"></script>
<script type="text/javascript" src="http://<?= $clickst_domain ?>/static/js/graph.js"></script>
<script type="text/javascript" src="http://<?= $clickst_domain ?>/static/js/graph-report.js"></script>
<script type="text/javascript">
function clickst_ready()
{
    var $ = jQuery
    
    var networks = <?= json_encode($default_networks) ?>
    
    var actions = ['pageview', 'button.load', 'button.click', 'sharelink.click']
    
    var queries = {
        actions: {
            view: 'action',
            query: {
                action: {
                    '$in': actions
                }
            }
        },
        last_actions: {
            view: 'action',
            query: {
                action: {
                    '$in': actions
                }
            }
        },
        actions_network: {
            view: 'action,network',
            query: {
                action: {
                    '$in': actions
                }
            }
        },
        items: {
            view: 'action,item',
            query: {
                action: {
                    '$in': ['button.load','button.click']
                }
            }
        }
    }
    
    var update_overview = function ()
    {
        $('#clickst-overview')
            .find('.visitors')
                .find('.current')
                    .html(get_count('actions.pageview.visitors'))
                .end()
                .find('.diff')
                    .html(
                        get_percentage_difference(
                            get_count('last_actions.pageview.visitors'),
                            get_count('actions.pageview.visitors')
                        )
                    )
                .end()
            .end()
            .find('.shares')
                .find('.current')
                    .html(get_count('actions["button.click"].visitors'))
                .end()
                .find('.diff')
                    .html(
                        get_percentage_difference(
                            get_count('last_actions["button.click"].visitors'),
                            get_count('actions["button.click"].visitors')
                        )
                    )
                .end()
            .end()
            .find('.referrals')
                .find('.current')
                    .html(get_count('actions["sharelink.click"].visitors'))
                .end()
                .find('.diff')
                    .html(
                        get_percentage_difference(
                            get_count('last_actions["sharelink.click"].visitors'),
                            get_count('actions["sharelink.click"].visitors')
                        )
                    )
                .end()
            .end()
    }
    
    var update_referrals_chart = function ()
    {
        var share_series = []
        var referral_series = []
        
        $.each(networks, function ()
        {
            share_series.push(get_count('actions_network["button.click"]["'+this+'"].visitors'))
            referral_series.push(get_count('actions_network["sharelink.click"]["'+this+'"].visitors'))
        })
        
        $('#referrals-chart').html('')
        
        referrals_chart = new Highcharts.Chart({
            chart: {
                renderTo: 'referrals-chart',
                defaultSeriesType: 'column',
                margin: [20,20,20,30]
            },
            title: {
                text: null
            },
            xAxis: {
                categories: networks,
                title: {
                    text: null
                }
            },
            yAxis: {
                min: 0,
                tickInterval: 1,
                title: {
                    text: null
                }
            },
            tooltip: {
               formatter: function() {
                  return ''+
                      this.series.name +': '+ this.y;
               }
            },
            plotOptions: {
               column: {
                  dataLabels: {
                     enabled: true
                  },
                  animation: false
               }
            },
            legend: {
               layout: 'horizontal',
               align: 'right',
               verticalAlign: 'top',
               borderWidth: 1,
               backgroundColor: '#FFFFFF',
               x: -20,
               y: 10
            },
            credits: {
               enabled: false
            },
            series: [
                {
                    name: 'People that share',
                    data: share_series
                },
                {
                    name: 'Referred people',
                    data: referral_series
                }
            ]
        });
    }
    
    var update_top_posts = function ()
    {
        var post_stats = {
            posts_by_view: get_count('items["button.load"]', []),
            posts_by_share: get_count('items["button.click"]', [])
        }
        
        var item_ids = []
        
        for (var n in post_stats)
        {
            post_stats[n] = post_stats[n].slice(0,10)
            
            post_stats[n].sort(function (a,b)
            {
                if (a.count < b.count)
                {
                    return 1
                }
                return -1
            })
            
            for (var i=0; i<post_stats[n].length; i++)
            {
                if (item_ids.indexOf(post_stats[n][i].item) < 0)
                {
                    item_ids.push(post_stats[n][i].item)
                }
            }
        }
        
        if (item_ids.length == 0)
        {
            return
        }
        
        var items_by_id = {}
        
        clickst.call('item.getlist', {item_ids: item_ids}, function (items)
        {
            $.each(items, function ()
            {
                items_by_id[this.item_id] = this
            })
            
            for (var n in post_stats)
            {
                var container = $('#'+n)
                container.html('')
                
                $.each(post_stats[n], function ()
                {
                    var item = items_by_id[this.item]
                    
                    container.append(
                        $(document.createElement('li'))
                            .append(
                                $(document.createElement('a'))
                                    .attr({
                                        href: item.url,
                                        target: '_blank'
                                    })
                                    .html(item.summary)
                            ).append(
                                $(document.createElement('span'))
                                    .html(' '+this.count)
                            )
                    )
                })
            }
        })
    }
    
    var update_display = function ()
    {
        $('[disabled]').removeAttr('disabled')
        update_overview()
        update_referrals_chart()
        update_top_posts()
    }
    
    var get_percentage_difference = function (a,b)
    {
        if (a == 0)
        {
            return '';
        }
        
        if (a < b)
        {
            return '<span class="positive">&uarr; '+(Math.round(1000 * (b / a)) / 10)+'%</span>'
        }
        else
        {
            return '<span class="negative">&darr; '+(Math.round(1000 * (a / b))/ 10)+'%</span>'
        }
    }
    
    var get_count = function (path, default_value)
    {
        try
        {
            eval('var v = counts.'+path)
            
            if (typeof v == 'undefined')
            {
                throw {}
            }
            
            return v
        }
        catch (e)
        {
            if (typeof default_value == 'undefined')
            {
                return 0
            }
            
            return default_value
        }
    }
    
    var get_date_from_selector = function (name)
    {
        return Date.parse(
            $('#date select[name='+name+'_month]').val() + '/' +
            $('#date select[name='+name+'_day]').val() + '/' +
            $('#date select[name='+name+'_year]').val()
        )
    }
    
    var counts
    
    var on_date_changed = function ()
    {
        var start = get_date_from_selector('start')
        var end = get_date_from_selector('end')
        var last_start = new Date(start.getTime() - (end - start))
        
        var calls = []
        var names = []
        counts = {}
        
        for (var n in queries)
        {
            var q = queries[n]
            
            if (n == 'last_actions')
            {
                q.start = last_start.getTime() / 1000
                q.end = start.getTime() / 1000
            }
            else
            {
                q.start = start.getTime() / 1000
                q.end = end.getTime() / 1000
            }
            
            calls.push(queries[n])
            names.push(n)
        }
        
        clickst.call('action.find_batch', {calls: calls}, function (results)
        {
            for (var i=0; i<results.length; i++)
            {
                if (results[i].error)
                {
                    counts[names[i]] = null
                }
                else if (names[i] == 'actions_network')
                {
                    counts[names[i]] = []
                    
                    for (var j=0; j<results[i].result.length; j++)
                    {
                        if (!counts[names[i]][results[i].result[j].action])
                        {
                            counts[names[i]][results[i].result[j].action] = {}
                        }
                        
                        counts[names[i]][results[i].result[j].action][results[i].result[j].network] = results[i].result[j]
                    }
                }
                else if (names[i] == 'items')
                {
                    counts[names[i]] = []
                    
                    for (var j=0; j<results[i].result.length; j++)
                    {
                        if (!counts[names[i]][results[i].result[j].action])
                        {
                            counts[names[i]][results[i].result[j].action] = []
                        }
                        
                        counts[names[i]][results[i].result[j].action].push(results[i].result[j])
                    }
                }
                else
                {
                    counts[names[i]] = {}
                    
                    for (var j=0; j<results[i].result.length; j++)
                    {
                        counts[names[i]][results[i].result[j].action] = results[i].result[j]
                    }
                }
            }
            
            update_display()
        })
    }
    
    var end = new Date()
    $('#date select[name=end_year]').val(end.getFullYear())
    $('#date select[name=end_month]').val(end.getMonth()+1)
    $('#date select[name=end_day]').val(end.getDate())
    
    var start = end.last().week()
    $('#date select[name=start_year]').val(start.getFullYear())
    $('#date select[name=start_month]').val(start.getMonth()+1)
    $('#date select[name=start_day]').val(start.getDate())
    
    on_date_changed()
    
    $('#date input[type=submit]').click(function (e)
    {
        e.preventDefault()
        $(this).attr('disabled', true)
        on_date_changed()
    })
    
    var graph = new GraphReport(clickst, 
        {
            depth: 4,
            base_url: 'http://<?= $clickst_domain ?>',
            weights: ['shares', 'referrals'],
            identities: <?= json_encode($identities) ?>
        }
    )
    graph.update()
}
</script>
<?
}

function clickst_stats_menu()
{
    $page = add_submenu_page(
        'index.php',
        'Clickst Stats',
        'Clickst Stats',
        'manage_options',
        'clickst-stats',
        'clickst_stats'
    );
    
    add_action('admin_head-'.$page, 'clickst_admin_head' );
}

function clickst_head()
{
    global $clickst_networks, $clickst_domain, $current_user;
    $default_networks = array();
    
    foreach ($clickst_networks as $k => $v)
    {
        if (get_option('clickst_share_'.$k))
        {
            $default_networks[] = $k;
        }
    }
?>
<script type="text/javascript" src="http://<?= $clickst_domain ?>/api.js?key=<?= get_option('clickst_key') ?><?= $current_user ? '&user_id='.$current_user->ID : '' ?>"></script>
<script type="text/javascript">clickst.default_networks = <?= json_encode($default_networks) ?></script>
<?
}

function clickst_admin_head()
{
    global $clickst_domain;
    
    if (!get_option('clickst_key') || !get_option('clickst_secret'))
    {
        return;
    }
    
    $request_key = sha1($_SERVER['REMOTE_ADDR'].get_option('clickst_secret'));
?>
<script type="text/javascript" src="http://<?= $clickst_domain ?>/api.js?key=<?= get_option('clickst_key') ?>"></script>
<?
}

if (is_admin())
{
    add_action('admin_menu', 'clickst_settings_menu');
    add_action('admin_init', 'clickst_register_settings');
    
    add_action('admin_menu', 'clickst_stats_menu');
}
else if (get_option('clickst_key'))
{
    add_action('wp_head', 'clickst_head');
}


function clickst_buttons()
{
    $description = explode("\n", strip_tags(get_the_content()));
    $description = trim($description[0]);
    
    ?><?= get_option('clickst_share_label') ?> <script type="text/javascript">
    clickst.inline_buttons({
        id: 'blog-<?= the_ID() ?>',
        url: '<?= the_permalink() ?>',
        summary: "<?= str_replace('"', '\"', the_title()) ?>",
        description: "<?= str_replace('"', '\"', $description) ?>"
    })
    </script><?
}
?>
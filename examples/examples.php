<?php
/** @example Insert
 *
 */ 
$values = array('setting_name' => $_POST['name'],
                'setting_value' => $_POST['value'],
                'setting_explanation' => $_POST['desc'], );

insert('settings')->values($values); 

/** @example Update
 * 
 */
update('calendars_events')->values(array('user_id' => $_SESSION['user_id'],
                                         'title' => $_POST['title'],
                                         'text' => $_POST['text'],
                                         'start' => $_POST['start'],
                                         'end' => $_POST['end'],
                                         'allday' => $_POST['allday'],
                                         'url' => $_POST['url'],
                                         'color' => '#'.$_POST['color'],
                                         'textColor' => '#'.$_POST['textColor'], ))->where('calendar_event_id = 1');

/** @example Delete 
 * 
 */
delete('contents_similars')->where('content_id = 1')->run();            

/** @example Select
 * 
 */
select('settings')->where('setting_group = 1')->order('setting_id ASC')->results();

/** @example Select 
 * 
 */
select('dynamic_tables')->where('dynamic_table_name = "settings"')->limit(1)->result('dynamic_table_rules');

/** @example Select with left join
 * 
 */
select('contents')->left('langs ON langs.lang_id = contents.lang_id')->results();

/** @example Select with left join and using
 * 
 */
select('contents')->left('langs')->using('lang_id')->results();


/** @example
 * 
 */
find('contents', 2);
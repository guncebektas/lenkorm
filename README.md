It’s developed as fluent interface design. You can easily access to database just by using select functions anywhere you want.

Build query:
select(‘users’)->where(‘user_id = “’. $user_id .’” ’)->limit(1);
Run query:
select(‘users’)->where(‘user_id = “’. $user_id .’” ’)->limit(1)->run;
Fetch result
select(‘users’)->where(‘user_id = “’. $user_id .’” ’)->limit(1)->result();

Easy to read & write, isn’t it? You can use helper functions insert(), replace(), update(), delete() and select() in everywhere including functions without calling global $pdo. For other features please call global $pdo variable.

Let’s look it deeply with examples… 
For examples I will use ‘users’ as table name of users, and ‘langs’ for table name of available languages

find(‘users’, 1)
Returns just one row of selected table with the match of first column 

select(‘users’)
Returns the row of selected table
It means “select * from” to change * use ->which() after select()

left(‘langs ON langs.lang_id = users.lang_id’)
LEFT JOIN statement for select, usage is;
select(‘users’)->left(‘langs ON langs.lang_id = users.lang_id’)->results();

insert(‘users’)->values(array)
insert(‘users’)->values(array(‘user_name’=>’Jon Snow’));

replace(‘users’)->values(array)
replace(‘users’)->values(array(‘user_name’=>’Jon Snow’));

update(‘users’)->values(array)
update(‘users’)->values(array(‘user_name’=>’Jon Snow’))->where(‘user_id = 1’);

where()
select(‘users’)->where(‘user_id = 1’)->result();

* which()
I know which statement is a little bit odd but it’s simple and points * for select queries like;
select(‘users’)->which(‘user_name, users.lang_id AS lang_id)

group()
select(‘users’)->group(‘lang_id’);

have()
select(‘users’)->have(‘lang_id’);

order()
->order(‘user_id ASC’);

limit()
->limit(10);

offset()
->offset(10);

column() – final function 
column(‘users’)

write() – final function
shows query

* run() – final function
->run();

* result() – final function
->result();

* results() – final function
->results();

results_pairs() – final function (Beta)
Gather results as pair, is very useful when working with lists
->results_pairs();


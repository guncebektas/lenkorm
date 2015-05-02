<h1>Query builder for PDO with memcache support</h1>

It’s developed as fluent interface design. You can easily access to database just by using select functions anywhere you want.

<b>Build query:</b>
select(‘users’)->where(‘user_id = “’. $user_id .’” ’)->limit(1);

<b>Run query:</b>
select(‘users’)->where(‘user_id = “’. $user_id .’” ’)->limit(1)->run;

<b>Fetch result</b>
select(‘users’)->where(‘user_id = “’. $user_id .’” ’)->limit(1)->result();


Easy to read & write, isn’t it? You can use helper functions insert(), replace(), update(), delete() and select() in everywhere including functions without calling global $pdo. For other features please call global $pdo variable.

Let’s look it deeply with examples… 
For examples I will use ‘users’ as table name of users, and ‘langs’ for table name of available languages

<b>find(‘users’, 1)</b>
Returns just one row of selected table with the match of first column 

<b>select(‘users’)</b>
Returns the row of selected table
It means “select * from” to change * use ->which() after select()

<b>left(‘langs ON langs.lang_id = users.lang_id’)</b>
LEFT JOIN statement for select, usage is;
select(‘users’)->left(‘langs ON langs.lang_id = users.lang_id’)->results();

<b>insert(‘users’)->values(array)</b>
insert(‘users’)->values(array(‘user_name’=>’Jon Snow’));

<b>replace(‘users’)->values(array)</b>
replace(‘users’)->values(array(‘user_name’=>’Jon Snow’));

<b>update(‘users’)->values(array)</b>
update(‘users’)->values(array(‘user_name’=>’Jon Snow’))->where(‘user_id = 1’);

<b>where()</b>
select(‘users’)->where(‘user_id = 1’)->result();

<b>* which()</b>
I know which statement is a little bit odd but it’s simple and points * for select queries like;
select(‘users’)->which(‘user_name, users.lang_id AS lang_id)

<b>group()</b>
select(‘users’)->group(‘lang_id’);

<b>have()</b>
select(‘users’)->have(‘lang_id’);

<b>order()</b>
->order(‘user_id ASC’);

<b>limit()</b>
->limit(10);

<b>offset()</b>
->offset(10);

<b>column() – final function </b>
column(‘users’)

<b>write() – final function</b>
shows query

<b>* run() – final function</b>
->run();

<b>* result() – final function</b>
->result();

<b>* results() – final function</b>
->results();

<b>results_pairs() – final function (Beta)</b>
Gather results as pair, is very useful when working with lists
->results_pairs();

<b>PS:</b>
You can send arrays as parameters to insert or update a column, query builder will automatically detect and change it into json

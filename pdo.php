<?php
/** Simple and smart SQL query builder for PDO.
 * 
 * @category 	Library
 * @version		0.9.2
 * @author   	guncebektas <info@guncebektas.com>
 * @license  	http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     	http://guncebektas.com
 * 
 * ->write 		: will show you the query string
 * ->run		: will run the query
 * ->result		: will return the result of selected result (only one row)
 * ->results	: will return the results of query (multi row)
 * 
 * otherwise you will only create query string!
 * 
 * insert_id, find, columns, insert methods will be exacuted directly 
 * 
 * Examples:
 * 
 * 1. THIS WILL SELECT ALL ROWS IN SLIDES TABLE
   select('slides')->results();	

 * 
 * 
 * 2. INSERT ARRAY INTO SLIDES TABLE 

   insert('slides')->values(array('slide_img'=>$_POST['slide_img'], 
									  'slide_title'=>$_POST['slide_title'],
									  'slide_text'=>$_POST['slide_text'],
									  'slide_href'=>$_POST['slide_href']));

 * 
 * 
 * 3. UPDATE SLIDES TABLE 
  
	update('slides')->values(array('slide_img'=>$_POST['slide_img'], 
									  'slide_title'=>$_POST['slide_title'],
									  'slide_text'=>$_POST['slide_text'],
									  'slide_href'=>$_POST['slide_href']))->where('slide_id = 1');

 * 
 * PS 1: you can put array into values like values($_POST) if columns match with the index of array
 * 
 * PS 2: use security function in where clause to block SQL injection like 
 * ->where('slide_id = '.security($_GET['slide_id']));
 */
 
/** 
 * Settings to connect database 
 */
$db = array(
  'server' => 'localhost',
  'db_name' => '',
  'type' => 'mysql',
  'user' => '',
  'pass' => '',
  'charset' => 'charset=utf8',
);

$pdo = new _pdo($db);

class _pdo extends PDO
{
    /** Query string
     *
     * @access public
     * @var string
     */
    public $query;

    /** Type of query such as insert or update, important to determine when the query will run
     *
     * @access public
     * @var string
     */
    private $type;

    /** Values for update and insert statements
     *
     * @access public
     * @var string
     */
    private $values;

    /** Caching with memcache 
	 * 
	 * @access public
     * @var bool
	 */
    private $memcache = false;

    public function __construct($db)
    {
        /*
        try
        {
        */
            /* Connect to database */
            parent::__construct($db['type'].':host='.$db['server'].';dbname='.$db['db_name'].';'.$db['charset'], $db['user'], $db['pass'], array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
            /* Extend PDO statement class*/
            $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('_pdo_statement'));
            /* Disable emulated prepared statements */
            $this->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            /* Set default fetch mode*/
            $this->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            /* Include UPDATED QUERIES in to rowcount() function */
            //$this->setAttribute(PDO::MYSQL_ATTR_FOUND_ROWS, true);
            /* Error mode is exception */
            $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        /*
        }
        catch(PDOException $e)
        {
            die('<p><strong>Error:</strong> '. $e->getMessage(). '</p>
                 <p><strong>File:</strong> '. $e->getFile(). '</br>
                 <p><strong>Line:</strong> '. $e->getLine(). '</p>');
        }
        */
    }
    /** Returns the last inserted id
	 * 
	 * @example last_id();
	 * @return int
	 */
    public function insert_id()
    {
        return $this->lastInsertId();
    }
    /** Returns the selected row from selected table with 
	 * the match of first column
	 * 
	 * @example find('coupons', 5);
	 * @param string  $table  name of the table in the database
	 * @param int  $id  unique id of table which is in the first column of table
 	 * @return array
	 */
    public function find($table, $id)
    {
        $columns = $this->column(security($table));

        return $this->select(security($table))->where($columns['Field'].' = '.security($id))->limit(1)->result();
    }
    /** Selects the table
	 * 
	 * @example select('coupons')->where('coupon_id = 5')->result();
	 * 
	 * @param string  $table  name of the table in the database
	 * @return string
	 */
    public function select($table)
    {
        $this->query = 'SELECT * FROM '.security($table).' ';

        return $this;
    }
	/** LEFT JOIN function 
	 * 
	 * @example select('contents')->left('categories ON categories.category_id = contents.category_id')->where('author_id = 2')->results();
	 * 
	 * @param string  $condition  clause for left join 
	 * @return string
	 */
    public function left($condition)
    {
        $this->query .= 'LEFT JOIN '.security($condition).' ';

        return $this;
    }
	/** USING clause 
	 * 
	 * @example select('contents')->left('categories')->using('category_id')->where('content_id = 2')->result();
	 * 
	 * @param string  $column  column name for using clause
	 * @return string
	 */
    public function using($column)
    {
        $this->query .= ' USING ('.security($column).')';

        return $this;
    }
    /** Insert and Update methods are determining private variable type and these two methods are working with values method
     *
     * Insert prepares the statement and runs it with the given variables
     * Update prepates the statement but where methods runs it because of the syntex
	 * 
	 * @example insert('coupons')->values(array[]);
	 * 
	 * @param string   $table  table name
	 * @return string
     */
    public function insert($table)
    {
        $this->type = 'insert';

        $this->query = 'INSERT INTO '.security($table).' ';

        return $this;
    }
    public function replace($table)
    {
        $this->type = 'insert';

        $this->query = 'REPLACE INTO '.security($table).' ';

        return $this;
    }
    public function update($table)
    {
        $this->type = 'update';

        $this->query = 'UPDATE '.security($table).' SET ';

        return $this;
    }
	/** Increase a value 
	 * 
	 * @example update('coupons')->increase('coupon_amount')->where('coupon_id = 2');
	 * 
	 * @param string  $column  column name of table
	 * @param int  optional $value  amount to increase
	 * 
	 * @return string
	 */
	public function increase($column, $value = 1)
	{
		$column = security($column);
		$this->query .= $column.' = '.$column.' + '.(int)$value.' ';

        return $this;
	}
	/** Decrease a value 
	 * 
	 * @example update('coupons')->decrease('coupon_amount', 4)->where('coupon_id = 2');
	 *
	 * @param string  $column  column name of table
	 * @param int  optional $value  amount to decrease
	 * 
	 * @return string
	 */
	public function decrease($column, $value = 1)
	{
		$column = security($column);
		$this->query .= $column.' = '.$column.' - '.(int)$value.' ';

        return $this;
	}
    /** Values method prepares the query for insert and update methods
     *  It also runs the query for insert queries, update queries will run after where clause is completed
	 * 
	 * @example insert('coupons')->values(array[]);
	 * 
	 * @param array  $values  the array to insert or update
	 * @return string
	 */
    public function values($values)
    {
        $this->values = $values;

        $keys = array_keys($values);
        $vals = array_values($values);
		
		/* INSERT INTO books (title,author) VALUES (:title,:author); */
        if ($this->type == 'insert') {
            $row = '(';
            for ($i = 0; $i < count($values); $i++) {
                $row .= $keys[$i];

                if ($i != count($values) - 1) {
                    $row .= ', ';
                } else {
                    $row .= ') VALUES (';
                }
            }
            for ($i = 0; $i < count($values); $i++) {
            	$row .= ':'.$keys[$i];

                if ($i != count($values) - 1) {
                    $row .= ', ';
                } else {
                    $row .= ')';
                }
            }
            $this->query .= security($row);
            $query = $this->prepare($this->query);

			// If the values are formed as an array than encode it
			foreach ($values AS $value){
				if (is_array($value))
					$value = json_encode($value);
				
				$res[] = $value;
			}
			
            $query->execute($res);
        }
        /* UPDATE books SET title=:title, author=:author */
        elseif ($this->type == 'update') {
            for ($i = 0; $i < count($values); $i++) {
                $this->query .= security($keys[$i]).' = :'.security($keys[$i]).' ';
                if ($i != count($values) - 1) {
                    $this->query .= ', ';
                }
            }

            return $this;
        }
    }
    /** Delete from table, if key is not empty method will delete row by the first column match 
	 * 
	 * @example delete('coupons')->where('coupon_id = 5');
	 * 
	 * @param string  $table  table name
	 * @param int  $id  unique id to match with the first column of table
	 * 
	 * @return deletes from the table
	 */
    public function delete($table, $id = '')
    {
        if (empty($key)) {
            $this->query = 'DELETE FROM '.security($table).' ';

            return $this;
        } else {
            // Key is not empty, so delete by first column match
            $columns = $this->column($table);
            $this->delete($table)->where(''.security($columns['Field']).' = "'.security($id).'"')->limit(1)->run();
        }
    }
    /** Where condition
	 * 
	 * @param string  $condition  condition to appand select, update, delete etc...
	 * @return string, if prepended query has update method it also exacutes update
	 */ 
    public function where($condition)
    {
        $this->query .= ' WHERE '.$condition;

        if ($this->type == 'update') {
            $query = $this->prepare($this->query);
			
			// If the values are formed as an array than encode it
			foreach ($this->values AS $value){
				if (is_array($value))
					$value = json_encode($value);
				
				$res[] = $value;
			}
			
            $query->execute($res);
			
            return $this;
        } else {
            return $this;
        }
    }
    /** Which columns, condition will replace with *
     *
     * @param string  $codition  clause to replace with * 
	 * @return string 
     */
    public function which($condition)
    {
        $this->query = str_replace('*', security($condition), $this->query);

        return $this;
    }
    /** Group condition
	 * 
	 * @param string  $codition  group by clause
	 * @return string  
	 */
    public function group($condition)
    {
        $this->query .= ' GROUP BY '.security($condition);

        return $this;
    }
    /** Having condition
	 * 
	 * @param string  $condition  having clause
	 * @return string 
	 */
    public function have($condition)
    {
        $this->query .= ' HAVING '.security($condition);

        return $this;
    }
    /** Order condition
	 * 
	 * @param string  $condition  order by clause
	 * @return string
	 */
    public function order($condition)
    {
        $this->query .= ' ORDER BY '.security($condition);

        return $this;
    }
    /** Limit condition
	 * 
	 * @example select('contents')->where('author_id = 2')->order('content_time DESC')->limit(100);
	 * 
	 * @param int  $limit
	 * @return string
	 */
    public function limit($limit = 3000)
    {
        $this->query .= ' LIMIT '.(int) $limit.' ';

        return $this;
    }
    /** Offset condition
	 * 
	 * @param int  $offset
	 * @return string
	 */
    public function offset($offset = 3000)
    {
        $this->query .= ' OFFSET '.(int) $offset.' ';

        return $this;
    }
    /** Return the columns of table
	 * 
	 * @example column('coupons')
	 * 
	 * @param string  $table
	 * @return array
	 */
    public function column($table)
    {
        $query = $this->query('SHOW COLUMNS FROM '.$table);

        return $query->fetch();
    }
    /** Writes query string to screen, not works with methods, which returns data set, such as find, coluns etc...
	 * 
	 * @example select('coupons')->where('coupon_id = 5')->write();
	 * @return writes query string to screen
	 */
    final public function write()
    {
        echo $this->query;
    }
    /** Runs the query
     *
     * @param $return  will return query, no need to change it 
     * @return if $return is true function returns query
     */
    final public function run($return = false)
    {
        if ($return) {
            return $this->query($this->query);
        }

        $this->query($this->query);
    }
    /** Run and get the value of query
     *
	 * @example select('coupons')->where('coupon_id = 5')->result();
	 * @example select('coupons')->where('coupon_id = 5')->result('coupon_name);
	 * 
     * @param $key  optional  string
     * @return if $key is empty it returns an array else a string
     */
    final public function result($key = '')
    {
        if (!$this->memcache) {
            $query = $this->run(true);

	        if (!$key) {
	            return $query->fetch();
	        } else {
	            $result = $query->fetch();
	
	            return $result[$key];
	        }
        }

        $memcache = new Memcache();
        $memcache->connect('127.0.0.1', 11211) or die('MemCached connection error!');

        $data = $memcache->get('query-'.md5($this->query));
        
        if (!isset($data) || $data === false) {
            $query = $this->run(true);

	        if (!$key) {
	            return $query->fetch();
	        } else {
	            $result = $query->fetch();
	
	            return $result[$key];
	        }

            $memcache->set('query-'.md5($this->query), $result, MEMCACHE_COMPRESSED, 9000);

            return $result;
        } else {
            return $data;
        }
    }
    /** Runs and fetchs the result set of the query
     *
	 * @example select('coupons')->where('coupon_id = 5')->results();
	 * 
     * @return array  results set
     */
    final public function results()
    {
        if (!$this->memcache) {
            $query = $this->run(true);
            $results = $query->fetch_array();

            return $results;
        }

        $memcache = new Memcache();
        $memcache->connect('127.0.0.1', 11211) or die('MemCached connection error!');

        $data = $memcache->get('query-'.md5($this->query));
        if (!isset($data) || $data === false) {
            $query = $this->run(true);
            $results = $query->fetch_array();

            $memcache->set('query-'.md5($this->query), $results, MEMCACHE_COMPRESSED, 9000);

            return $results;
        } else {
            return $data;
        }
    }
    /** Gather results as pair, is very useful when working with lists
     *
     * @param $key
     * @param $values
     */
    final public function results_pairs($key, $values = '')
    {
        $results = $this->results();

        foreach ($results as $result) {
            foreach ($values as $value) {
                $res[$result[$key]][$value] = $result[$value];
            }
        }

        return $res;
    }
}
/* Extend PDOStatement for some methods */
class _pdo_statement extends PDOStatement
{
    /* Set the rule of fetchAll. Values will be returned as PDO::FETCH_ASSOC in fetch_array and fetch_assoc functions */
    public function fetch_array()
    {
        return $this->fetchAll(PDO::FETCH_ASSOC);
    }
    public function fetch_assoc($result)
    {
        return $this->fetchAll(PDO::FETCH_ASSOC);
    }
    /* Return number of rows */
    public function num_rows()
    {
        return $this->rowcount();
    }
    /* Return affected wors */
    public function affected_rows()
    {
        return $this->rowcount();
    }
}

/* Use these functions instead of $pdo->select() usage.
 * 
 */

/** 
 * @example select('slides')->results();	
 * 
 * or
 * 
 * @example select('slides')->where('slide_id = 2')->limit(1)->result();	
 * 
 */
function select($table)
{
    global $pdo;

    return $pdo->select($table);
}
/** 
 * @example find('slides',3);	
 * 
 */
function find($table, $id)
{
    global $pdo;

    return $pdo->find($table, $id);
}
/** 
 * @example insert('slides')->values(array('slide_img'=>$_POST['slide_img'], 'slide_title'=>$_POST['slide_title'],'slide_text'=>$_POST['slide_text'],'slide_href'=>$_POST['slide_href']));
 * 
 */
function insert($table)
{
    global $pdo;

    return $pdo->insert($table);
}
function replace($table)
{
    global $pdo;

    return $pdo->replace($table);
}
/** 
 * @example update('slides')->values(array('slide_img'=>$_POST['slide_img'], 'slide_href'=>$_POST['slide_href']))->where('slide_id = 1');
 * 
 */
function update($table)
{
    global $pdo;

    return $pdo->update($table);
}
/** 
 * @example delete('slides')->where('slide_id = 2');
 * 
 * or
 * 
 * @example delete('slides',2);
 * 
 */
function delete($table, $key = '')
{
    global $pdo;

    return $pdo->delete($table, $key);
}
/** 
 * @example last_id();
 * 
 */
function last_id()
{
	global $pdo;
	
	return $pdo->insert_id();
}





/** Main security function to check strings
 * 
 * @param string  $input
 * @return string
 */
function security($input)
{
    // Clear not allowed chars
    $input = preg_replace('/([\x00-\x08][\x0b-\x0c][\x0e-\x20])/', '', $input);

    // Search for these
    $search = 'abcdefghijklmnopqrstuvwxyz';
    $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $search .= '1234567890!@#$%^&*()';
    $search .= '~`";:?+/={}[]-_|\'\\';

    // Clear not allowed chars again
    for ($i = 0; $i < strlen($search); $i++) {
        $input = preg_replace('/(&#[x|X]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $input);
        $input = preg_replace('/(&#0{0,8}'.ord($search[$i]).';?)/', $search[$i], $input);
    }

    // Remove java, flash etc..
    $ra1 = array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base');
    $ra2 = array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');

    // Merge arrays
    $ra = array_merge($ra1, $ra2);

    // Remove possible threats which are defined above
    $find = true;
    while ($find == true) {
        $first = $input;
        for ($i = 0; $i < sizeof($ra); $i++) {
            $action = '/';
            for ($j = 0; $j < strlen($ra[$i]); $j++) {
                if ($j > 0) {
                    $action .= '(';
                    $action .= '(&#[x|X]0{0,8}([9][a][b]);?)?';
                    $action .= '|(&#0{0,8}([9][10][13]);?)?';
                    $action .= ')?';
                }
                $action .= $ra[$i][$j];
            }

            $action .= '/i';
            $change = substr($ra[$i], 0, 2).'<x>'.substr($ra[$i], 2);
            $input = preg_replace($action, $change, $input);

            if ($first == $input) {
                $find = false;
            }
        }
    }

    // Allowed tags
    $result = strip_tags($input, '<p><strong><em><b><i><ul><li><pre><hr><blockquote><span>');

    // Change special chars to their html version
    $result = htmlspecialchars($result);

    // \n to <br>
    $result = str_replace("\n", '<br />', $result);

    // Add slash
    $result = addslashes($result);

    return $result;
}

/** Clear unnecessary chars
 * 
 * @param string  $input
 * @return string
 */
function clean($input)
{
    $input = str_replace("\'", "'", $input);
    $input = str_replace('\\\\', '\\', $input);
    $input = str_replace('<br />', "\n", $input);
    $input = str_replace('&amp;', '&', $input);
    $input = str_replace('&quot;', '"', $input);
    $input = str_replace('<', '&lt;', $input);
    $input = str_replace('>', '&gt;', $input);

    return $input;
}
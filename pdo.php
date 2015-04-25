<?php
/* LenkORM is a simple and smart SQL query builder for PDO.
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
 
/* Settings to connect database */
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
    /* Query string
     *
     * @access public
     * @var string
     */
    public $query;

    /* Type of query such as insert or update, important to determine when the query will run
     *
     * @access public
     * @var string
     */
    private $type;

    /* Values for update and insert statements
     *
     * @access public
     * @var string
     */
    private $values;

    /* Caching with memcache 
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
    /* Last inserted id; usage $pdo->insert_id() 
	 * 
	 */
    public function insert_id()
    {
        return $this->lastInsertId();
    }
    /* Return just one row of selected table with 
	 * the match of first column in the table 
	 * 
	 * find('coupons', 5);
	 */
    public function find($table, $id)
    {
        $columns = $this->column(security($table));

        return $this->select(security($table))->where($columns['Field'].' = '.security($id))->limit(1)->result();
    }
    /* Return the rows of selected table 
	 * 
	 * select('coupons')->where('coupon_id = 5')->result();
	 */
    public function select($table)
    {
        $this->query = 'SELECT * FROM '.security($table).' ';

        return $this;
    }
	/* LEFT JOIN function 
	 * 
	 * select('contents')->left('categories ON categories.category_id = contents.category_id')->where('author_id = 2')->results();
	 */
    public function left($condition)
    {
        $this->query .= 'LEFT JOIN '.security($condition).' ';

        return $this;
    }
	/* USING clause 
	 * 
	 * select('contents')->left('categories')->using('category_id')->where('content_id = 2')->result();
	 */
    public function using($column)
    {
        $this->query .= ' USING ('.security($column).')';

        return $this;
    }
    /* Insert and Update methods are determining private variable type and these two methods are working with values method
     *
     * Insert prepares the statement and runs it with the given variables
     * Update prepates the statement but where methods runs it because of the syntex
	 * 
	 * insert('coupons')->values(array[]);
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
	/* Increase a value 
	 * 
	 * update('coupons')->increase('coupon_amount')->where('coupon_id = 2');
	 */
	public function increase($column, $value = 1)
	{
		$column = security($column);
		$this->query .= $column.' = '.$column.' + '.(int)$value.' ';

        return $this;
	}
	/* Decrease a value 
	 * 
	 * update('coupons')->decrease('coupon_amount', 4)->where('coupon_id = 2');
	 */
	public function decrease($column, $value = 1)
	{
		$column = security($column);
		$this->query .= $column.' = '.$column.' - '.(int)$value.' ';

        return $this;
	}
    /* Values method prepares the query for insert and update methods
     *
     * It also runs the query for insert queries, update queries will run after where clause is completed
     */
    public function values($values)
    {
        $this->values = $values;

        $keys = array_keys($values);
        $vals = array_values($values);
		// Example: INSERT INTO books (title,author) VALUES (:title,:author)
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
        // Example: UPDATE books SET title=:title, author=:author
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
    /* Delete from table, if key is not empty method will delete row by the first column match 
	 * 
	 * delete('coupons')->where('coupon_id = 5');
	 */
    public function delete($table, $key = '')
    {
        if (empty($key)) {
            $this->query = 'DELETE FROM '.security($table).' ';

            return $this;
        } else {
            // Key is not empty, so delete by first column match
            $columns = $this->column($table);
            $this->delete($table)->where(''.security($columns['Field']).' = "'.security($key).'"')->limit(1)->run();
        }
    }
    // Where condition
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
    /* Which columns will be gathered by default it's *
     *
     * @param string
     */
    public function which($condition)
    {
        $this->query = str_replace('*', security($condition), $this->query);

        return $this;
    }
    /* Group condition
	 * 
	 */
    public function group($condition)
    {
        $this->query .= ' GROUP BY '.security($condition);

        return $this;
    }
    /* Having condition
	 * 
	 */
    public function have($condition)
    {
        $this->query .= ' HAVING '.security($condition);

        return $this;
    }
    /* Order condition
	 * 
	 */
    public function order($condition)
    {
        $this->query .= ' ORDER BY '.security($condition);

        return $this;
    }
    /* Limit condition
	 * 
	 * select('contents')->where('author_id = 2')->order('content_time DESC')->limit(100);
	 */
    public function limit($limit = 3000)
    {
        $this->query .= ' LIMIT '.(int) $limit.' ';

        return $this;
    }
    /* Offset condition
	 * 
	 */
    public function offset($offset = 3000)
    {
        $this->query .= ' OFFSET '.(int) $offset.' ';

        return $this;
    }
    /* Return the columns of table
	 * 
	 * column('coupons')
	 */
    public function column($table)
    {
        $query = $this->query('SHOW COLUMNS FROM '.$table);

        return $query->fetch();
    }
    /* Echo query string, not works with methods, which returns data set, such as find, coluns etc...
	 * 
	 * select('coupons')->where('coupon_id = 5')->write();
	 */
    final public function write()
    {
        echo $this->query;
    }
    /* Run the query
     *
     * @param $return will it return query
     * @return if $return is true function returns query
     */
    final public function run($return = false)
    {
        if ($return) {
            return $this->query($this->query);
        }

        $this->query($this->query);
    }
    /* Run and get the value of query
     *
	 * select('coupons')->where('coupon_id = 5')->result();
	 * select('coupons')->where('coupon_id = 5')->result('coupon_name);
	 * 
     * @param $key optional
     * @return result set
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
    /* Run and get the result set of the query
     *
	 * select('coupons')->where('coupon_id = 5')->results();
	 * 
     * @return results set
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
    /* Gather results as pair, is very useful when working with lists
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
    // Set the rule of fetchAll. Values will be returned as PDO::FETCH_ASSOC in fetch_array and fetch_assoc functions
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

// Use these functions instead of $pdo->select() in functions and classes
function select($table)
{
    global $pdo;

    return $pdo->select($table);
}
function find($table, $id)
{
    global $pdo;

    return $pdo->find($table, $id);
}
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
function update($table)
{
    global $pdo;

    return $pdo->update($table);
}
function delete($table, $key = '')
{
    global $pdo;

    return $pdo->delete($table, $key);
}

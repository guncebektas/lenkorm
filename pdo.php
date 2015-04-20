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
   

   select('slides')->results();	

   insert('slides')->values(array('slide_img'=>$_POST['slide_img'], 
									  'slide_title'=>$_POST['slide_title'],
									  'slide_text'=>$_POST['slide_text'],
									  'slide_href'=>$_POST['slide_href']));
  
	update('slides')->values(array('slide_img'=>$_POST['slide_img'], 
									  'slide_title'=>$_POST['slide_title'],
									  'slide_text'=>$_POST['slide_text'],
									  'slide_href'=>$_POST['slide_href']))->where('slide_id = 1');

 * 
 * 
 * 
 * PS: use security function in where clause to block SQL injection like 
 * ->where('slide_id = '.security($_GET['slide_id']));
 */
class _pdo extends PDO 
{
	/* Query string
	 *
	 * @access public
	 * @var string
	 */
	var $query;
	
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
	
	function __construct($db)
	{
		/*
        try 
        {
		*/
        	/* Connect to database */
            parent::__construct($db['type'].':host='.$db['server'].';dbname='.$db['db_name'].';'.$db['charset'],$db['user'],$db['pass'],array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
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
	/* Last inserted id; usage $pdo->insert_id() */
	public function insert_id()
	{
		return $this->lastInsertId();
	}
	/* Return just one row of selected table with the match of first column in the table */
	public function find($table, $id)
	{
		$columns = $this->column(security($table));
		return $this->select(security($table))->where($columns['Field'] ." = ".security($id))->limit(1)->result();
	}
	/* Return the rows of selected table */
	public function select($table)
	{		
		$this->query = "SELECT * FROM ".security($table)." ";		
		return $this;		
	}
	public function left($condition)
	{
		$this->query .= "LEFT JOIN ".security($condition)." ";
		return $this;
	}
	public function using($column)
	{
		$this->query .= " USING (".security($column).")";
		return $this;
	}
	/* Insert and Update methods are determining private variable type and these two methods are working with values method 
	 * 
	 * Insert prepares the statement and runs it with the given variables
	 * Update prepates the statement but where methods runs it because of the syntex
	 */
	public function insert($table)
	{
		$this->type = 'insert';
		
		$this->query = "INSERT INTO ".security($table)." ";
		return $this;
	}
	public function replace($table)
	{
		$this->type = 'insert';
		
		$this->query = "REPLACE INTO ".security($table)." ";
		return $this;
	}
	public function update($table)
	{
		$this->type = 'update';
		
		$this->query = "UPDATE ".security($table)." SET ";
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
		if ($this->type == 'insert')
		{
			$row = '(';
			for ($i = 0; $i<count($values); $i++)
			{
				$row .= $keys[$i];
 
				if ($i != count($values) - 1 )
					$row .= ', ';
				else
					$row .= ') VALUES (';
			}	
			for ($i = 0; $i<count($values); $i++)
			{
				$row .= ':'.$keys[$i];
				
				if ($i != count($values) - 1 )
					$row .= ', ';
				else
					$row .= ')';
			}	
			$this->query .= security($row);
			$query = $this->prepare($this->query);
			
			$query->execute($values);
		}
		// Example: UPDATE books SET title=:title, author=:author		
		elseif ($this->type == 'update')
		{
			for ($i = 0; $i<count($values); $i++)
			{
				$this->query .= security($keys[$i]).' = :'.security($keys[$i]).' '; 
				if ($i != count($values) - 1 )	
					$this->query .= ', ';	
			} 
			return $this;
		}		
	}
	/* Delete from table, if key is not empty method will delete row by the first column match */
	public function delete($table, $key = '')
	{
		if (empty($key))
		{
			$this->query = "DELETE FROM ".security($table)." ";		
			return $this;
		}
		else 
		{
			// Key is not empty, so delete by first column match
			$columns = $this->column($table);
			$this->delete($table)->where(''.security($columns['Field']) .' = "'.security($key).'"')->limit(1)->run();
		}
	}
	// Where condition
	public function where($condition)
	{
		$this->query .= " WHERE ".$condition;
		
		if ($this->type == 'update')
		{
			$query = $this->prepare($this->query);
			$query->execute($this->values);
			
			return $this;
		}	
		else
		{
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
	// Group condition
	public function group($condition)
	{
		$this->query .= " GROUP BY ".security($condition);
		return $this;		
	}
	// Having condition
	public function have($condition)
	{
		$this->query .= " HAVING ".security($condition);
		return $this;		
	}
	// Order condition
	public function order($condition)
	{
		$this->query .= " ORDER BY ".security($condition);
		return $this;
	}
	// Limit condition
	public function limit($limit = 3000)
	{
		$this->query .= " LIMIT ".(int)$limit." ";
		return $this;
	}
	// Offset condition
	public function offset($offset = 3000)
	{
		$this->query .= " OFFSET ".(int)$offset." ";
		return $this;
	}
	// Return the columns of table
	public function column($table)
	{
		$query = $this->query("SHOW COLUMNS FROM ".$table);
		return $query->fetch();
	}
	// Echo query string, not works with methods, which returns data set, such as find, coluns etc... 
	public final function write()
	{
		echo $this->query;	
	}
	/* Run the query
	 * 
	 * @param $return will it return query
	 * @return if $return is true function returns query
	 */
	public final function run($return = false)
	{
		if ($return)
			return $this->query($this->query);
		
		$this->query($this->query);
	}
	/* Run and get the value of query
	 * 
	 * @param $key optional 
	 * @return result set
	 */
	public final function result($key = '')
	{
		$query = $this->run(true);
		
		if (!$key)
		{
			return $query->fetch();
		}
		else
		{
			$result = $query->fetch();
			return $result[$key];	
		}
	}
	/* Run and get the result set of the query
	 *
	 * @return results set
	 */
	public final function results()
	{
		$query = $this->run(true);
		
		$query = $this->query($this->query);
		return $query->fetch_array();
	}
	/* Gather results as pair, is very useful when working with lists
	 * 
	 * @param $key
	 * @param $values
	 */
	public final function results_pairs($key, $values = '') 
	{
		$results = $this->results();
		
		foreach ($results AS $result)
			foreach ($values AS $value)
				$res[$result[$key]][$value] = $result[$value];
			
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

// Main security function to check strings
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
	for ($i = 0; $i < strlen($search); $i++) 
	{
		$input = preg_replace('/(&#[x|X]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $input);
		$input = preg_replace('/(&#0{0,8}'.ord($search[$i]).';?)/', $search[$i], $input);
	}
	
	// Remove java, flash etc.. 
	$ra1 = Array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base');
	$ra2 = Array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
	
	// Merge arrays
	$ra = array_merge($ra1, $ra2);
	
	// Remove possible threats which are defined above
	$find = true;
	while ($find == true) 
	{
		$first = $input;
		for ($i = 0; $i < sizeof($ra); $i++) 
		{
			$action = '/';
			for ($j = 0; $j < strlen($ra[$i]); $j++) 
			{
				if ($j > 0) 
				{
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
			
			if ($first == $input) 
			{
				$find = false;
			}
		}
	}
	
	// Allowed tags
	$result = strip_tags($input, '<p><strong><em><b><i><ul><li><pre><hr><blockquote><span>');
	
	// Change special chars to their html version 
	$result = htmlspecialchars($result);

	// \n to <br>
	$result = str_replace("\n", "<br />", $result);

	// Add slash
	$result = addslashes($result);

	return $result;
}

// Clear unnecessary chars
function clean($input)
{
    $input = str_replace("\'", "'", $input);
	$input = str_replace("\\\\", "\\", $input);
	$input = str_replace("<br />", "\n", $input);
	$input = str_replace("&amp;", "&", $input);
	$input = str_replace("&quot;", "\"", $input);
	$input = str_replace("<", "&lt;", $input);
	$input = str_replace(">", "&gt;", $input);

	return $input;
}

<?php
/*
+---------------------------------------------------------------------------
|
|   mysql.class.php (php 4.x)
|
|   by Benjam Welker
|   http://www.iohelix.net
|   based on works by W. Jason Gilmore
|   http://www.wjgilmore.com; http://www.apress.com
|
+---------------------------------------------------------------------------
|
|   > MySQL DB Queries module
|   > Date started: 2005-09-02
|   >  Last edited: 2006-07-01
|
|   > Module Version Number: 0.9.2
+---------------------------------------------------------------------------
*/

class mysql
{
	var $linkid;     // MySQL Resource ID
	var $host;       // MySQL Host name
	var $user;       // MySQL Username
	var $pswd;       // MySQL password
	var $db;         // MySQL Database
	var $query;      // MySQL query
	var $pass_query; // Query passed by argument
	var $result;     // Query result
	var $line;       // Line of query
	var $file;       // File of query
	var $error;      // Any error message encountered while running
	var $log_path;   // The path to the log file
	var $querycount; // Total number of queries executed since class inception
	var $debug;      // Allows for debug output
	var $full_debug; // Allows for output of all queries all the time


	/* Class constructor.  Initializes the host, user, pswd, db and log fields */
	function mysql($host, $user, $pswd, $db, $log_path = './')
	{
		global $CONFIG_STAGE;

		define ('DB_ERR_EMAIL_ERRORS', false); // set to email errors to admin address
		define ('DB_ERR_LOG_ERRORS', true); // set to log errors in mysql.err file
		define ('DB_ERR_TO'     , 'benjam@iohelix.net');
		define ('DB_ERR_SUBJECT', 'Power Query Error');
		define ('DB_ERR_FROM'   , 'auto.mail@iohelix.net');
		define ('FILE_PATH_END' , 'power'); // the directory containing the script
		define ('FILE_PATH_LEN' , strlen(FILE_PATH_END));

		$this->host = $host;
		$this->user = $user;
		$this->pswd = $pswd;
		$this->db   = $db;
		$this->log_path = $log_path;
		$this->debug = false; // set to true for permanent debug output
		$this->full_debug = false; // set to true for output of every query and other debug output

		// make sure the log path ends with /
		if (strrpos($this->log_path,'/') != (strlen($this->log_path) - 1))
		{
			$this->log_path .= '/';
		}
	}


	/* Connect to the MySQL database server */
	function connect( )
	{
		$num_args = func_num_args( );
		$args     = func_get_args( );

		// get the arguments, if any
		if (0 != $num_args)
		{
			$this->load_arguments($args);
		}

		$this->linkid = @mysql_connect($this->host, $this->user, $this->pswd);

		if ( ! $this->linkid)
		{
			$this->error = mysql_errno( ).': '.mysql_error( );
			$this->error_report( );

			if ($this->debug)
			{
				echo "There was an error connecting to the server in {$this->file_name} on line {$this->line}:<br />ERROR - {$this->error}";
			}
			else
			{
				die('There was a database error. An email has been sent to the system administrator.');
			}
		}
	}



	/* Selects the MySQL Database */
	function select( )
	{
		$num_args = func_num_args( );
		$args     = func_get_args( );

		// get the arguments, if any
		if (0 != $num_args)
		{
			$this->load_arguments($args);
		}

		if ( ! @mysql_select_db($this->db, $this->linkid))
		{
			$this->error = mysql_errno($this->linkid).': '.mysql_error($this->linkid);
			$this->error_report( );

			if ($this->debug)
			{
				echo "There was an error selecting the database in {$this->file_name} on line {$this->line}:<br />ERROR - {$this->error}";
			}
			else
			{
				die('There was a database error. An email has been sent to the system administrator.');
			}
		}
	}



	/* Connects to the server AND selects the default database in one function */
	function connect_select( )
	{
		$num_args = func_num_args( );
		$args     = func_get_args( );

		$this->clear_arguments( );

		// get the arguments, if any
		if (0 != $num_args)
		{
			$this->load_arguments($args);
		}

		$this->connect( );
		$this->select( );
	}



	/* Execute Database Query */
	function query( )
	{
		$num_args = func_num_args( );
		$args     = func_get_args( );

		// get the arguments, if any
		if (0 != $num_args)
		{
			$this->clear_arguments( ); // don't clear unless we have new
			$this->load_arguments($args);

			if (false !== $this->pass_query)
			{
				$this->query = $this->pass_query;
			}
		}

		if ($this->full_debug)
		{
			echo "QUERY ";
			print_r($this->get_arguments( ));
		}

		$done = true; // innocent until proven guilty

		$this->result = @mysql_query($this->query, $this->linkid);

		if ($this->full_debug)
		{
			$this->query = trim(preg_replace('/\s+/', ' ', $this->query));
			echo "<div style='background:white;color:black;'>{$this->query} - Aff(".$this->affected_rows( ).") - {$this->file_name}: {$this->line}</div>";
		}

		if ( ! $this->result)
		{
			$this->error = mysql_errno($this->linkid).': '.mysql_error($this->linkid);
			$this->error_report( );

			if ($this->debug)
			{
				echo "<div style='background:#900;color:white;'>There was an error in your query in {$this->file_name} on line {$this->line}: ERROR - {$this->error}<br />Query: {$this->query}</div>";
			}
			else
			{
				$this->error = 'There was a database error. An email has been sent to the system administrator.';
			}

			$done = false;
		}

		if ($done)
		{
			$this->querycount++;
			return $this->result;
		}

		return false;
	}



	/* Determine total rows affected by query */
	function affected_rows( )
	{
		$count = @mysql_affected_rows($this->linkid);
		return $count;
	}



	/* Determine total rows returned by query */
	function num_rows( )
	{
		$count = @mysql_num_rows($this->result);

		if ( ! $count)
		{
			return 0;
		}

		return $count;
	}



	/* Return query result row as an object */
	function fetch_object( )
	{
		$num_args = func_num_args( );
		$args     = func_get_args( );

		// get the arguments, if any
		if (0 != $num_args)
		{
			$this->clear_arguments( );
			$this->load_arguments($args);
		}

		if ($this->full_debug)
		{
			echo "FETCH_OBJECT ";
			print_r($this->get_arguments( ));
		}

		if (false !== $this->pass_query)
		{
			$this->query = $this->pass_query;
			$this->query( );
		}

		$row = @mysql_fetch_object($this->result);
		return $row;
	}



	/* Return query result row as an indexed array */
	function fetch_row( )
	{
		$num_args = func_num_args( );
		$args     = func_get_args( );

		// get the arguments, if any
		if (0 != $num_args)
		{
			$this->clear_arguments( );
			$this->load_arguments($args);
		}

		if ($this->full_debug)
		{
			echo "FETCH_ROW ";
			print_r($this->get_arguments( ));
		}

		if (false !== $this->pass_query)
		{
			$this->query = $this->pass_query;
			$this->query( );
		}

		$row = @mysql_fetch_row($this->result);
		return $row;
	}



	/* Return query result row as an associative array */
	function fetch_assoc( )
	{
		$num_args = func_num_args( );
		$args     = func_get_args( );

		// get the arguments, if any
		if (0 != $num_args)
		{
			$this->clear_arguments( );
			$this->load_arguments($args);
		}

		if ($this->full_debug)
		{
			echo "FETCH_ASSOC ";
			print_r($this->get_arguments( ));
		}

		if (false !== $this->pass_query)
		{
			$this->query = $this->pass_query;
			$this->query( );
		}

		$row = @mysql_fetch_assoc($this->result);
		return $row;
	}



	/* Return query result row as an associative array and an indexed array */
	function fetch_both( )
	{
		$num_args = func_num_args( );
		$args     = func_get_args( );

		// get the arguments, if any
		if (0 != $num_args)
		{
			$this->clear_arguments( );
			$this->load_arguments($args);
		}

		if ($this->full_debug)
		{
			echo "FETCH_BOTH ";
			print_r($this->get_arguments( ));
		}

		if (false !== $this->pass_query)
		{
			$this->query = $this->pass_query;
			$this->query( );
		}

		$row = @mysql_fetch_array($this->result, MYSQL_BOTH);
		return $row;
	}



	/* Return query result as an array of arrays */
	function fetch_array( )
	{
		$num_args = func_num_args( );
		$args     = func_get_args( );

		// get the arguments, if any
		if (0 != $num_args)
		{
			$this->clear_arguments( );
			$this->load_arguments($args);
		}

		if ($this->full_debug)
		{
			echo "FETCH_ARRAY ";
			print_r($this->get_arguments( ));
		}

		if (false !== $this->pass_query)
		{
			$this->query = $this->pass_query;
			$this->query( );
		}

		$arr = array( );
		while ($row = @mysql_fetch_array($this->result))
		{
			$arr[] = $row;
		}

		return $arr;
	}



	/* Return query result as an array of single values */
	function fetch_value_array( )
	{
		$num_args = func_num_args( );
		$args     = func_get_args( );

		// get the arguments, if any
		if (0 != $num_args)
		{
			$this->clear_arguments( );
			$this->load_arguments($args);
		}

		if ($this->full_debug)
		{
			echo "FETCH_VALUE_ARRAY ";
			print_r($this->get_arguments( ));
		}

		if (false !== $this->pass_query)
		{
			$this->query = $this->pass_query;
			$this->query( );
		}

		$arr = array( );
		while ($row = @mysql_fetch_row($this->result))
		{
			$arr[] = $row[0];
		}

		return $arr;
	}



	/* Return single query result value */
	function fetch_value( )
	{
		$num_args = func_num_args( );
		$args     = func_get_args( );

		// get the arguments, if any
		if (0 != $num_args)
		{
			$this->clear_arguments( );
			$this->load_arguments($args);
		}

		if ($this->full_debug)
		{
			echo "FETCH_VALUE ";
			print_r($this->get_arguments( ));
		}

		if (false !== $this->pass_query)
		{
			$this->query = $this->pass_query;
			$this->query( );
		}

		$row = @mysql_fetch_row($this->result);
		return $row[0];
	}



	/* Return the total number of queries executed during
		 the lifetime of this object                         */
	function num_queries( )
	{
		return $this->querycount;
	}



	/* get the id for the previous INSERT command */
	function fetch_insert_id( )
	{
		return @mysql_insert_id($this->linkid);
	}



	/* get the errors, if any */
	function fetch_error( )
	{
		return $this->error;
	}



	/* report the errors to the admin */
	function error_report( )
	{
		// generate an error report and then act according to configuration
		$error_report  = "An error has been generated by the server.\nFollowing is the debug information:\n\n";
		$error_report .= "   *  File: {$this->file_name}\n";
		$error_report .= "   *  Line: {$this->line}\n";
		$error_report .= "   * Error: {$this->error}\n";

		$error_report_short = "\n" . date('Y-m-d H:i:s') . " Error in {$this->file_name} on line {$this->line}: ERROR - {$this->error}";

		// if a database query caused the error, show the query
		if ('' != $this->query)
		{
			$error_report .= "   * Query: {$this->query}\n";
			$error_report_short .= " [sql='{$this->query}']";
		}

		// send the error as email if set
		if (DB_ERR_EMAIL_ERRORS)
		{
			mail(DB_ERR_TO, trim(DB_ERR_SUBJECT), $error_report, 'From: '.DB_ERR_FROM."\r\n\r\n");
		}

		// log the error (remove line breaks and multiple concurrent spaces)
		$this->logger(trim(preg_replace('|\s+|',' ',$error_report_short))."\n");
	}



	/* log any errors */
	function logger($report)
	{
		if (DB_ERR_LOG_ERRORS)
		{
			$log = $this->log_path . "mysql.err";
			$fp = fopen($log,'a+');
			fwrite($fp,$report);
			@chmod($log, 0777);
			fclose($fp);
		}
	}



	/* extract the arguments */
	function load_arguments($args)
	{
		foreach ($args as $arg)
		{
			if ($this->full_debug)
			{
				echo '<hr />';
				var_dump($arg).' - ';
				echo var_dump(is_int($arg)).' ';
				echo var_dump('/' == substr($arg, 0, 1)).' ';
				echo var_dump(is_string($arg) && (0 != strlen($arg)) && (false === strpos($arg, ' '))).' ';
				echo var_dump(0 != preg_match('/^\\s*(SELECT|INSERT|UPDATE|DELETE|DROP|DESC|REPLACE|CREATE|ALTER|ANALYZE|BACKUP|CACHE|CHANGE|CHECK|COMMIT|DEALLOCATE|DO|EXECUTE|EXPLAIN|FLUSH|GRANT|HANDLER|HELP|KILL|LOAD|LOCK|MASTER|OPTIMIZE|PREPARE|PURGE|RENAME|REPAIR|RESET|RESTORE|REVOKE|ROLL|SAVE|SET|SHOW|START|STOP|TRUNCATE|UNLOCK|USE)/i', $arg)).' ';
			}

			if (is_int($arg)) // it's an integer
			{
				if ($this->full_debug) echo 'LINE - ';
				$this->line = $arg;
				if ($this->full_debug) echo $this->line;
			}
			else if (('/' == substr($arg, 0, 1)) || (0 != preg_match('/^\\w:/', $arg))) // the string begins with '/' or a drive letter
			{
				if ($this->full_debug) echo 'FILE - ';
				$this->file_name = substr($arg, (strpos($arg, FILE_PATH_END) + FILE_PATH_LEN));
				if ($this->full_debug) echo $this->file_name;
			}
			else if (is_string($arg) && (0 != strlen($arg)) && (false === strpos($arg, ' '))) // there are no spaces
			{
				if ($this->full_debug) echo 'DATABASE - ';
				$this->db = $arg;
				if ($this->full_debug) echo $this->db;
			}
			else if (0 != preg_match('/^\\s*(SELECT|INSERT|UPDATE|DELETE|DROP|DESC|REPLACE|CREATE|ALTER|ANALYZE|BACKUP|CACHE|CHANGE|CHECK|COMMIT|DEALLOCATE|DO|EXECUTE|EXPLAIN|FLUSH|GRANT|HANDLER|HELP|KILL|LOAD|LOCK|MASTER|OPTIMIZE|PREPARE|PURGE|RENAME|REPAIR|RESET|RESTORE|REVOKE|ROLL|SAVE|SET|SHOW|START|STOP|TRUNCATE|UNLOCK|USE)/i', $arg)) // it begins with a query word
			{
				if ($this->full_debug) echo 'QUERY - ';
				$this->pass_query = $arg;
				if ($this->full_debug) echo $this->pass_query;
			}
			else
			{
				if ($this->full_debug) echo 'UNKNOWN - ';

				// begin output buffering so we don't output the var_dump
				ob_start( );
				var_dump($arg);
				$arg_dump = ob_get_contents( );
				ob_end_clean( );
				// end output buffering

				$this->error = 'Unknown argument found: ' . $arg_dump;
				if ($this->full_debug) echo $this->error;
			}
		}

		// wait until after all arguments are entered before outputting error
		// because the error may happen on the first argument and the other
		// arguments have important error report data (it's what they're for)
		if ('Unknown argument' == substr((string) $this->error, 0, 16))
		{
			$this->error_report( );
		}

		if ($this->full_debug) print_r($this->get_arguments( ));
	}



	/* clear the arguments */
	function clear_arguments( )
	{
		// don't clear query or db as we may use them later
		$this->line = false;
		$this->file_name = false;
		$this->error = false;
		$this->pass_query = false;
	}



	/* return the arguments */
	function get_arguments( )
	{
		$args[] = $this->line;
		$args[] = $this->file_name;
		$args[] = $this->error;
		$args[] = $this->pass_query;

		return $args;
	}

} // end of mysql class



/*
 +---------------------------------------------------------------------------
 |   > Extra SQL Functions
 +---------------------------------------------------------------------------
*/


/* sanitize the data before it gets queried into the database */
function sani($data)
{
	if (is_array($data))
	{
		return array_map('sani',$data);
	}
	else
	{
		if (get_magic_quotes_gpc( ))
		{
			$data = stripslashes($data);
		}

//    $data = htmlentities($data,ENT_NOQUOTES); // convert html to &html;

		if (function_exists('mysql_real_escape_string'))
		{
			$data = mysql_real_escape_string($data); // php 4.3.0+
		}
		else
		{
			$data = mysql_escape_string($data); // php 4.0+
		}

		return $data;
	}
}

?>

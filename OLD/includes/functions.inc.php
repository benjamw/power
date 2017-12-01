<?php

// this file holds generic functions that are not part of any object

// combines arrays by setting one to keys and the other to values
if ( ! function_exists('array_combine'))
{
	function array_combine($keys, $vals)
	{
		$keys = array_values((array) $keys);
		$vals = array_values((array) $vals);

		if (count($keys) != count($vals))
		{
			trigger_error('array_combine arrays must be the same size', E_USER_NOTICE);
		}

		$r = array();
		for ($i = 0; $i < $n; ++$i)
		{
			$r[$keys[$i]] = $vals[$i];
		}

		return $r;
	}
}


// debug function
function call($var = '^^k8)SJ2di!U')
{
	if (false == DEBUG)
	{
		return false;
	}

	if ('^^k8)SJ2di!U' === $var)
	{
		echo '<span style="font-weight:bold;background:white;color:red;">*****</span>';
	}
	else
	{
		// begin output buffering so we can escape any html
		ob_start( );

		if (is_string($var) && isset($GLOBALS[$var]))
		{
			echo '$' . $var . ' = ';
			$var = $GLOBALS[$var];
		}
		
		if (is_bool($var) || is_null($var))
		{
			var_dump($var);
		}
		else
		{
			print_r($var);
		}

		// end output buffering and output the result
		$contents = htmlentities(ob_get_contents( ));
		ob_end_clean( );
		
		echo '<pre style="background:#FFF;color:#000;font-size:larger;">'.$contents.'</pre>';
	}	
}

?>
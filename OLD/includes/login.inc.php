<?php

// TEMPORARY BYPASS
if (true)
{
	$_SESSION['player_id'] = 2;
	$_SESSION['game_id'] = 1;
}
else
{
	// REMEMBER TO CLEAR OUT CLOSING } BELOW



// if we are trying to register a new name
if (isset($_POST['register']))
{
	// test the token
	if ( ! isset($_SESSION['token']) || ($_SESSION['token'] != $_POST['token']))
	{
		die('Hacking attempt detected.');
	}

	// set the 'log in attempted' flag
	$newUser = true;

	// check for existing user with same username
	$query = "
		SELECT p_id
		FROM ".T_PLAYER."
		WHERE p_username = '".sani($_POST['txtUsername'])."'
	";
	$mysql->query($query, __LINE__, __FILE__);

	if (0 < $mysql->num_rows( ))
	{
		echo '<script type="text/javascript">alert(\'That username is already in use. Please try again\'); window.location.replace(\'register.php\');</script>';
		exit( );
	}

	$query = "
		INSERT INTO ".T_PLAYER."
			(p_password, p_username, p_email, p_last_active)
		VALUES
			('".substr($_POST['pwdPassword'],5)."', '".sani($_POST['txtUsername'])."', '".sani($_POST['txtEmail'])."', NOW( ))
	";
	$mysql->query($query, __LINE__, __FILE__);

	// set the session var so we get logged in below
	$_SESSION['player_id'] = $mysql->fetch_insert_id( );

	// add a welcome message for the new user
	$query = "
		INSERT INTO ".T_TALK."
			(t_from_id, t_to_id, t_subject, t_message, t_date_sent)
		VALUES
			(1, '{$_SESSION['player_id']}', 'Welcome to Power', 'Welcome to Power\n\nThis is an alpha release.\nIf you find any bugs, PLEASE send an email to me at benjam@iohelix.net as soon as possible detailing what happened and what went wrong so I can fix it.\n\nNow, please take a moment to familiarize yourself with the menu, and adjust your preferences to your liking.\nWhen that is all done, invite a fellow player to play a game, because well, that\'s what we\'re all here for!\n\nAgain, Welcome\n  --Power Administration', NOW( ))
	";
	$mysql->query($query, __LINE__, __FILE__);
}


// if we are already logged in, and there is nobody attempting to log in...
if (isset($_SESSION['player_id']) && ! isset($_POST['txtUsername']) && ('Power' == $_SESSION['GAME']))
{
	call("REFRESH LOGIN");
	// just refresh the session data with the (possibly new) database data
	$query = "
		SELECT *
		FROM ".T_PLAYER."
		WHERE p_id = '{$_SESSION['player_id']}'
	";
	$player = $mysql->fetch_assoc($query, __LINE__, __FILE__);
	$refreshPlayer = true;
}
// or if we have a cookie, log in using the cookie data
else if (isset($_COOKIE['PowerData']) && ('DELETED!' != $_COOKIE['PowerData']) && ! isset($_POST['txtUsername']))
{
	call("COOKIE LOGIN");
	$data  = base64_decode($_COOKIE['PowerData']);
	$ident = substr($data,0,32);
	$token = substr($data,32);
	$query = "
		SELECT *
		FROM ".T_PLAYER."
		WHERE p_ident = '{$ident}'
			AND p_token = '{$token}'
	";
	call($data);call($ident);call($token);call($query);
	if ($player = $mysql->fetch_assoc($query, __LINE__, __FILE__))
	{
		call("COOKIE OK !");
		$refreshPlayer = true;

		// regenerate the security info
		session_regenerate_id(true);
		$ident = md5(uniqid(rand( ),true));
		$token = md5(uniqid(rand( ),true));
		$data  = base64_encode($ident . $token);
		setcookie('PowerData', $data, time( ) + (60 * 60 * 24 * 7));

		// save the new ident and token to the database
		$query = "
			UPDATE ".T_PLAYER."
			SET p_ident = '{$ident}'
				, p_token = '{$token}'
			WHERE p_id = '{$player['p_id']}'
		";
		$mysql->query($query, __LINE__, __FILE__);
	}
	else // cookie data is invalid
	{
		call("COOKIE INVALID !");
		session_unset( ); // delete any session vars
		setcookie('PowerData','DELETED!',time( ) - 3600); // delete the cookie
		header('Location: login.php'); // redirect to the login page
	}
}
// if somebody is trying to log in
else if (isset($_POST['token']))
{
	// test the token
	if (( ! isset($_SESSION['token'])) || ($_SESSION['token'] != $_POST['token']))
	{
		die('Hacking attempt detected.');
	}

	call('REGULAR LOGIN');
	// check for a player with supplied username and password

	$query = "
		SELECT *
		FROM ".T_PLAYER."
		WHERE p_username = '".sani($_POST['txtUsername'])."'
	";
	$player = $mysql->fetch_assoc($query, __LINE__, __FILE__);
}
else // we need to log in
{
	header('Location: login.php');
}

// just refresh, OR log us in if such a player exists and password is good... otherwise die
if (isset($refreshPlayer) || ((false !== $player) && ($player['p_password'] == substr($_POST['pwdPassword'], 5))))
{
	$_SESSION['GAME'] = 'Power';//-'.$CFG_SITENAME.'-'.$CFG_MAINPAGE; // prevent cross script session stealing due to refresh login
	$_SESSION['player_id'] = $player['p_id'];
	$_SESSION['last_input_time'] = time( );
	$_SESSION['username'] = $player['p_username'];
	$_SESSION['email'] = $player['p_email'];

	if (isset($_POST['remember']) && '' != $_POST['remember'])
	{
		// generate the security info
		session_regenerate_id(true);
		$ident = md5(uniqid(rand( ),true));
		$token = md5(uniqid(rand( ),true));
		$data  = $ident . $token;
		$data  = base64_encode($data);
		setcookie('PowerData', $data, time( ) + (60 * 60 * 24 * 7)); // 1 week

		// save the new ident and token to the database
		$query = "
			UPDATE ".T_PLAYER."
			SET p_ident = '{$ident}'
				, p_token = '{$token}'
			WHERE p_id = '{$player['p_id']}'
		";
		$mysql->query($query, __LINE__, __FILE__);
	}
}
else
{
	if (!DEBUG)
	{
		echo "<script type=\"text/javascript\">alert('Invalid Username or Password. Please try again.'); window.location.replace('login.php');</script>\n";
	}
	else
	{
		echo "There was an error<br />POST:";
		call($_POST);
		echo "Query Results:";
		call($player);
		echo "MySQL error:";
		call($mysql->fetch_error( ));
	}

	exit( );
}


// if we are logging out
if (isset($_POST['todo']) && ('logout' == $_POST['todo']))
{
	session_unset( ); // delete the session
	setcookie('PowerData', 'DELETED!', time( ) - 3600); // delete the cookie
	header('Location: ./login.php'); // redirect to the login page
	exit( );
}


} // END TEMPORARY BYPASS
?>
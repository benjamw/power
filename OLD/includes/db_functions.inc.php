<?php

// this include file holds all the functions used
// to get and put data into the database


// create a game
function create_game($game_id, $num_players, $extra = false)
{
	global $mysql;
	
	$extra = (false != $extra) ? 1 : 0;
	
	// create the game table
	$query = "
		CREATE TABLE ".GAME.$game_id." (
		  g_player_id int(11) NOT NULL,
		  g_color_code enum('Y','K','R','A','Z') NOT NULL,
		  g_cur_command varchar(512) NOT NULL,
		  g_history text NOT NULL,
		  g_state enum('Awarding','Moving','Waiting','Incapacitated','Finished') NOT NULL,
		  g_last_move timestamp NOT NULL default CURRENT_TIMESTAMP,
		  UNIQUE KEY `g_color_code` (`g_color_code`)
		) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci
	";
	$mysql->query(__LINE__,__FILE__,$query);
	
	// enter the data into the table
	$query = "
		INSERT INTO ".GAME.$game_id."
			(g_player_id, g_color_code, g_cur_command, g_history, g_state, g_last_move)
		VALUES
			(0, 'Z', '$num_players', '$extra', 'Waiting', NULL)
	";
	$mysql->query(__LINE__,__FILE__,$query);
	
	$query = "
		INSERT INTO ".GAME.$game_id."
			(g_player_id, g_color_code, g_cur_command, g_history, g_state, g_last_move)
		VALUES
			(0, 'Y', '', '', 'Finished', NULL)
	";
	$mysql->query(__LINE__,__FILE__,$query);
	
	$query = "
		INSERT INTO ".GAME.$game_id."
			(g_player_id, g_color_code, g_cur_command, g_history, g_state, g_last_move)
		VALUES
			(0, 'K', '', '', 'Finished', NULL)
	";
	$mysql->query(__LINE__,__FILE__,$query);
	
	$query = "
		INSERT INTO ".GAME.$game_id."
			(g_player_id, g_color_code, g_cur_command, g_history, g_state, g_last_move)
		VALUES
			(0, 'R', '', '', 'Finished', NULL)
	";
	$mysql->query(__LINE__,__FILE__,$query);
	
	$query = "
		INSERT INTO ".GAME.$game_id."
			(g_player_id, g_color_code, g_cur_command, g_history, g_state, g_last_move)
		VALUES
			(0, 'A', '', '', 'Finished', NULL)
	";
	$mysql->query(__LINE__,__FILE__,$query);
}


function add_player_to_game($game_id, $player_id, $colors)
{
	// get the number of players remaining to be added
	// cur_command = num remaining open spots
	// history = 0, not using extra pieces (3 player - 3 color, or 2 player - 2 color)
	//           1, using extra pieces (3 player - 4 colors w/mercs, or 2 player - 4 color, 2 each)
	$query = "
		SELECT g_cur_command
			, g_history
		FROM ".GAME.$game_id."
		WHERE g_color_code = 'Z'
	";
	$num_remain = $mysql->fetch_value(__LINE__,__FILE__,$query);
	
	foreach ((array) $colors as $color)
	{
		$query = "
			UPDATE ".GAME.$game_id."
			SET g_player_id = '{$player_id}'
				, g_state = 'Waiting'
			WHERE g_color_code = '{$color}'
		";
		$mysql->query(__LINE__,__FILE__,$query);
	}
	
	--$num_remaining;
	// if we have all the players added, save the board and start the game
	if (0 == $num_remaining)
	{
		// get the data from the database
		$query = "
			SELECT g_color_code
			FROM ".GAME.$game_id."
			WHERE g_player_id != 0
		";
		$colors = $mysql->fetch_value_array(__LINE__,__FILE__,$query);
		
		// start with some templates
		$board_template = '//YQ////KQ////////////////////////////////////////////////RQ////AQ//';
		$piece_template = '2XI,2XT,2XF,2XD,XV';
		
		// parse through the players array and enter the pieces into the board template
		foreach ($colors as $color)
		{
			$pieces = str_replace('X', $color, $piece_template);
			$board_template = str_replace($color . 'Q', $pieces, $board_template);
		}
		
		// clear out any empty sectors
		$board = preg_replace('/\\wQ/', '', $board_template);
		
		// update the database
		$query = "
			UPDATE ".GAME.$game_id."
			SET g_cur_command = ''
				, g_history = '{$board}'
				, g_state = 'Moving'
			WHERE g_color_code = 'Z'
		";
		$mysql->query(__LINE__,__FILE__,$query);
		
		// update the players
		$query = "
			UPDATE ".GAME.$game_id."
			SET g_state = 'Moving'
			WHERE g_player_id != 0
		";
		$mysql->query(__LINE__,__FILE__,$query);
		
		// email the players
		// TODO: add this
	}
	else // we still have some players remaining, update the count
	{
		$query = "
			UPDATE ".GAME.$game_id."
			SET g_cur_command = '{$num_remaining}'
			WHERE g_color_code = 'Z'
		";
		$mysql->query(__LINE__,__FILE__,$query);
	}
}


// loads the current board from the database into the CMD object
function load_board($game_id)
{
	global $mysql, $CMD;

	// collect the board from the database
	$query = "
		SELECT g_history
		FROM ".GAME.$game_id."
		WHERE g_player_id = 0
	";
	$history = $mysql->fetch_value(__LINE__,__FILE__,$query);
# call($history);

	// extract the latest board from the history (remove line breaks and spaces)
	$history_array = explode(';', preg_replace('/\\s+/', '', $history));
	$count = count($history_array);
	$board = $history_array[$count - 1]; // -1 for 0 index
# call($board);

	// save the board to the CMD object
	$CMD->setFoldedBoard($board);

	// reset the command array
	$CMD->clearCommands( );
}



// load the current commands for this user
function load_commands($player_id, $game_id, $colors)
{
	global $mysql;

	foreach ($colors as $color)
	{
#   call($color);
		$query = "
			SELECT g_cur_command
			FROM ".GAME.$game_id."
			WHERE g_player_id = '{$player_id}'
				AND g_color_code = '{$color}'
		";
#   call($query);
		$cmd_list = $mysql->fetch_value(__LINE__,__FILE__,$query);
#   call($cmd_list);

		//arrayicize the data
		$cmd_array[$color] = (false != $cmd_list) ? explode(',', $cmd_list) : array( );
	}

	return $cmd_array;
}

function process_commands($cmd_array, $color, $player_id, $game_id)
{
	global $mysql, $CMD;

	$errors = array( );

	$i = 0;
	call($cmd_array);
	foreach ($cmd_array as $command)
	{
		// prepend the color data to the command (if we can)
		$command = $CMD->addColorCode($command, $color);
		call($command);

		// test the possibility for each one
		// this step provides both filtering
		// and sanity checks on the incoming data
		$cmd_data = $CMD->getCommandData($command);

		if ( ! $cmd_data[0])
		{
			$errors[$i] = $cmd_data['error'];
			$q_cmd_array[$i] = '';
		}
		else // the command is possible
		{
			// test the validity of the color of the pieces being moved
			// KLUDGE: this should be tested first, but I need the
			// cmd_data array, instead of just the command string, to check for
			// piece color, because there may be sector color codes in the command
			// string that would return false negatives
			$valid_color = $CMD->isColorValid($cmd_data, $color);

			if ( ! $valid_color)
			{
				$errors[$i] = $CMD->getError( );
				$q_cmd_array[$i] = '';
			}
			else // the piece color is valid
			{
				// now test the validity of each one
				// i.e. make sure there are pieces on the board to move, trade, etc.
				$valid = $CMD->validateCommand($cmd_data);

				// test if the command executed properly
				if ( ! $valid)
				{
					$errors[$i] = $CMD->getError( );
					$q_cmd_array[$i] = '';
				}
				else // the command is valid
				{
					// save the command to the command array
					$CMD->setCommand($command, $color);

					// save the command to the query array
					$q_cmd_array[$i] = $command;
				}
			} // end if color test
		} // end if possible test

		++$i; // increment counter
	} // end foreach command loop

	// lets put some data in the database (if it's good)
	// convert to list for the query
	$q_cmd_list = implode(',', $q_cmd_array);

	$query = "
		UPDATE ".GAME.$game_id."
		SET g_cur_command = '{$q_cmd_list}'
		WHERE g_player_id = '{$player_id}'
			AND g_color_code = '{$color}'
	";
	$mysql->query(__LINE__,__FILE__,$query);

	return $errors;
}


function load_players($game_id)
{
	global $mysql, $CMD;

	$colors = array('Y', 'K', 'R', 'A');
	$players = array( );

	$query = "
		SELECT g_color_code
			, g_player_id
		FROM ".GAME.$game_id."
		WHERE g_color_code != 'Z'
	";
	$result = $mysql->fetch_array(__LINE__,__FILE__,$query);

	foreach ($colors as $color)
	{
		foreach ($result as $row)
		{
			if ($row['g_color_code'] == $color)
			{
				$players[$color] = $row['g_player_id'];
			}
		}
	}

	// save the values into the CMD object
	$CMD->setPlayers($players);
}


function load_colors($player_id, $game_id)
{
	global $mysql;

	$query = "
		SELECT g_color_code
		FROM ".GAME.$game_id."
		WHERE g_player_id = '{$player_id}'
	";
	$colors = $mysql->fetch_value_array(__LINE__,__FILE__,$query);

	return $colors;
}


function load_executable($player_id, $game_id, $colors)
{
	global $mysql;

	// innocent until proven guilty
	$executable = 1;

	// test each color
	foreach ($colors as $color)
	{
		$query = "
			SELECT g_cur_command
			FROM ".GAME.$game_id."
			WHERE g_player_id = '{$player_id}'
				AND g_color_code = '{$color}'
		";
		$command_string = $mysql->fetch_value(__LINE__,__FILE__,$query);

		if (',,,,' == $command_string)
		{
			$executable = 0;
		}
	}

	return $executable;
}


// TODO: fix for each color dead separately
function load_form($cmd_array, $errors, $colors, $player_id, $game_id)
{
	global $mysql, $COLORS;

	$html = '<div id="form-wrap">';
	$execute = false;

	// parse for each color separately
	foreach ($colors as $color)
	{
		// get the players state
		$query = "
			SELECT g_state
			FROM ".GAME.$game_id."
			WHERE g_player_id = '{$player_id}'
				AND g_color_code = '{$color}'
		";
		$state = $mysql->fetch_value(__LINE__,__FILE__,$query);

		switch ($state)
		{
			case 'Moving' :
				$execute = true;

				$html .= "\n<form method=\"post\" action=\"\" name=\"test\" class=\"{$color}\">\n";

				for ($i = 0; $i <= 4; ++$i)
				{
					if (isset($errors[$color][$i]))
					{
						$error = "<span class=\"error\">{$errors[$color][$i]}</span>";
					}
					else
					{
						if (isset($cmd_array[$color][$i]) && preg_match('/\\w+/', $cmd_array[$color][$i]))
						{
							$error = '<span class="ok">Command Accepted</span>';
						}
						else
						{
							$error = '';
						}
					}

					$value = (isset($cmd_array[$color][$i])) ? $cmd_array[$color][$i] : '';

					$html .= "<input type=\"text\" name=\"cmd{$i}\" value=\"{$value}\" onchange=\"reset_executable( );\" /> {$error}<br />\n";
				}

				$html .= "<input type=\"hidden\" name=\"color\" value=\"{$color}\" />\n";
				$html .= '<input type="submit" name="enter" value="Test" /></form>';
				break;


			case 'Waiting' :
				$html .= "\n<div class=\"form {$color}\">\n";

				for ($i = 0; $i <= 4; ++$i)
				{
					$value = (isset($cmd_array[$color][$i])) ? $cmd_array[$color][$i] : '';

					$html .= "<div class=\"text\">{$value}</div>\n";
				}
				$html .= '</div>';
				break;


			case 'Incapacitated' :
				$html .= "<span class=\"error\">You have no {$COLORS[$color]} pieces to move</span>";
				break;


			case 'Finished' :
				$html .= "<span class=\"error\">Your {$COLORS[$color]} flag has been captured, better luck next time</span>";
				break;
		} // end state switch
	}

	if ($execute)
	{
		$html .= "\n<div class=\"clr\"></div>\n";
		$html .= '<form method="post" action="" name="execute">';
		$html .= "<input type=\"submit\" name=\"enter\" value=\"Execute\" onclick=\"if(0==executable){alert('You cannot execute commands without testing them first.\nIf you have commands with errors, you must fix them.');return false;}\" />\n</form>";
	}

	$html .= '</div>';

	return $html;
}


function save_commands($colors, $player_id, $game_id)
{
	global $mysql;

	// make sure the user has commands
	if (0 == load_executable($player_id, $game_id, $colors))
	{
		return false;
	}

	foreach ($colors as $color)
	{
		// the commands have been tested, so we know they are good
		// get the current commands from the database
		$query = "
			SELECT g_cur_command
				, g_history
			FROM ".GAME.$game_id."
			WHERE g_player_id = '{$player_id}'
				AND g_color_code = '{$color}'
		";
		list($command, $history) = $mysql->fetch_row(__LINE__,__FILE__,$query);

		// clean up the commands and append to history
		$matches = array('/^,+/', '/,+/', '/,+$/');
		$replacements = array('', ',', '');
		$command = preg_replace($matches, $replacements, $command);
		$history .= (0 != strlen($history)) ? ";\n" . $command : $command;

		// update the player state and history
		$query = "
			UPDATE ".GAME.$game_id."
			SET g_state = 'Waiting'
				, g_history = '{$history}'
			WHERE g_player_id = '{$player_id}'
				AND g_color_code = '{$color}'
		";
		$mysql->query(__LINE__,__FILE__,$query);
	}

	return true;
}


function save_board($game_id)
{
	global $mysql, $CMD;

	// get the folded board from the object
	$board = $CMD->getFoldedBoard( );

	// get the history from the database
	$query = "
		SELECT g_history
		FROM ".GAME.$game_id."
		WHERE g_color_code = 'Z'
	";
	$history = $mysql->fetch_value(__LINE__,__FILE__,$query);

	// append the board to the history
	$history .= ";\n" . $board;

	// update the history in the database
	$query = "
		UPDATE ".GAME.$game_id."
		SET g_history = '{$history}'
		WHERE g_color_code = 'Z'
	";
	$mysql->query(__LINE__,__FILE__,$query);
}


function save_post_board( )
{
	global $mysql;
	
	$board = $CMD->getFoldedBoard( );
	
	$query = "
		UPDATE ".GAME.$game_id."
		SET g_cur_command = '{$board}'
		WHERE g_color_code = 'Z'
	";
	$mysql->query(__LINE__,__FILE__,$query);
}

function run_game($game_id)
{
	global $mysql, $CMD;

	/*----------------------------------------------------*\
	|  Test for command completion
	\*----------------------------------------------------*/

	// count the number of people still moving
	$query = "
		SELECT COUNT(*)
		FROM ".GAME.$game_id."
		WHERE g_state = 'Moving'
			AND g_color_code != 'Z'
			AND g_player_id != 0
	";
	$count = $mysql->fetch_value(__LINE__,__FILE__,$query);

	// if there are people still moving
	if (0 < $count)
	{
		return false;
	}


	/*----------------------------------------------------*\
	|  Load the game from the database
	\*----------------------------------------------------*/

	// load a fresh board
	load_board($game_id);

	// load a fresh player list
	load_players($game_id);

	// load all the commands
	$query = "
		SELECT g_color_code
			, g_cur_command
		FROM ".GAME.$game_id."
		WHERE g_player_id != 0
			AND g_state = 'Waiting'
	";
	$result = $mysql->fetch_array(__LINE__,__FILE__,$query);
	foreach ($result as $row)
	{
		$command_lists[$row['g_color_code']] = $row['g_cur_command'];
	}
	$CMD->setCommands($command_lists);
	call($command_lists);

	/*----------------------------------------------------*\
	|  Run the game
	\*----------------------------------------------------*/

	// parse through each command list
	foreach ($command_lists as $command_list)
	{
		call($command_list);
		// make an array
		$commands = explode(',', $command_list);

		// parse through each command and process
		foreach ($commands as $command)
		{
			$cmd_data = $CMD->getCommandData($command);
			$CMD->validateCommand($cmd_data);
		}
	}
	
	// save the post-moved board for later viewing
	save_post_board( ); // (db_functions.inc.php)
	
	/*----------------------------------------------------*\
	|  Test for conflicts
	\*----------------------------------------------------*/

	// do the megamissle kills
	$CMD->doMegaKills( );
	
	// test for and resolve any simultaneous flag captures
	// TODO: yep, i need this too
	
	// now test for and resolve conflicts
	$CMD->clearAwards( );
	$CMD->clearConflicts( );
	$bounced = $CMD->getConflicts( );

	// repeat as necessary for bounces
	// TODO: as soon as this works, remove the increment test
	// there is a small possibility of more than 10 bounces
	$i = 0;
	while ($bounced && (10 > $i))
	{
		++$i;
		$CMD->doBounce( );
		$bounced = $CMD->getConflicts( );
	}

	$CMD->resolveConflicts( );


	/*----------------------------------------------------*\
	|  Award Power Units
	\*----------------------------------------------------*/

	// award any points for the remaining pieces in other countries
	$CMD->awardPoints( );


	/*----------------------------------------------------*\
	|  Test for flag captures
	\*----------------------------------------------------*/

	// work out the flag captures
	$CMD->clearFlagCaptures( );
	$CMD->getFlagCaptures( );
	$dead = $CMD->resolveFlagCaptures( );


	/*----------------------------------------------------*\
	|  Test for ambiguous color wins
	\*----------------------------------------------------*/

	// get the awards out of the object
	$awards = $CMD->getAwards( );
	call($awards);

	foreach ($awards as $award)
	{
		// do nothing yet
	}


	/*----------------------------------------------------*\
	|  Update the database with the results
	\*----------------------------------------------------*/


	// green light the players

	// update the board history
	save_board($game_id);
}


// set the game moving again
function green_light($game_id)
{
	global $mysql, $CMD;
	
	// get the incapacitated players
	$incapacitated = $CMD->getIncapacitated( );

	// parse through each and set as such
	foreach ($incapacitated as $color)
	{
		$query = "
			UPDATE ".GAME.$game_id."
			SET g_state = 'Incapacitated'
			WHERE g_color_code = '{$color}'
		";
		$mysql->query(__LINE__,__FILE__,$query);
	}
	
	// update the living players
	// remove current moves and set as moving
	$query = "
		UPDATE ".GAME.$game_id."
		SET g_cur_command = ''
			, g_state = 'Moving'
		WHERE g_state = 'Waiting'
	";
	$mysql->query(__LINE__,__FILE__,$query);
}


function is_game_over($game_id)
{
	return (is_game_won($game_id) || is_game_drawn($game_id));
}


function is_game_won($game_id)
{
	global $mysql;
	
	// check the database for more than one player
	$query = "
		SELECT COUNT(DISTINCT g_player_id)
		FROM ".GAME.$game_id."
		WHERE g_state != 'Finished'
			AND g_color_code != 'Z'
			AND g_player_id != 0
	";
	$count = $mysql->fetch_value(__LINE__,__FILE__,$query);
	
	// if we only have one player left, the game is over
	return (1 >= $count); // returns bool
}


function is_game_drawn($game_id)
{
	global $mysql;
	
	// check the database for more than one capable player
	$query = "
		SELECT COUNT(DISTINCT g_player_id)
		FROM ".GAME.$game_id."
		WHERE g_state NOT IN ('Finished', 'Incapacitated')
			AND g_color_code != 'Z'
			AND g_player_id != 0
	";
	$count = $mysql->fetch_value(__LINE__,__FILE__,$query);
	
	// if we only have one player left, the game is over
	return (0 == $count); // returns bool
}


function draw_board( )
{
	global $CMD;

	$board = $CMD->getExpandedBoard( );
	$html = '<div class="board">';

	foreach ($board as $sector => $contents)
	{
		$Y = $K = $R = $A = '';

		if (is_array($contents))
		{
			foreach ($contents as $piece)
			{
				if (0 < $piece['num'])
				{
					${$piece['color']} .= $piece['num'] . $piece['type'] . ' ';
				}
			}
		}

		$html .= "<div id=\"{$sector}\">";
		$html .= ('' != $Y) ? "<span class=\"y\">$Y</span><br />" : '';
		$html .= ('' != $K) ? "<span class=\"k\">$K</span><br />" : '';
		$html .= ('' != $R) ? "<span class=\"r\">$R</span><br />" : '';
		$html .= ('' != $A) ? "<span class=\"a\">$A</span><br />" : '';
		$html .= "</div>\n";
	}

	$html .= '</div>';
	echo $html;
}


?>
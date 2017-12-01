<?php

// this file holds the email function

/* 

$reason can be any of:
------------------------------------
Invited			- player was invited to a game
Game On			- game is ready to go
Awards			- player needs to place won pieces
Winner			- player won the game
Resign			- opponent resigned the game
Draw				- player tied with another player
Defeated		- player lost their flag
New Turn		- its time for another turn
Wake Up			- player needs to make a move (possibly sent by fellow player)


$to can be any of
------------------------------------
All					- all players in the game (default)
Alive				- all players with flags (Not Finished)
Playing			- all players with pieces (Not Incapacitated or Finished)
Immobile		- all dead players (Finished or Incapacitated)
(player_id)	- the player id of a specific player
(state)			- all the players in a specific state (Awarding, Moving, Waiting, Incapacitated, Finished)

*/
function cmd_email($reason, $game_id, $to = 'All')
{
	// switch based on reason for email
	switch($reason)
	{
		case 'Game On' : break;
	}
			
}

?>
<?php

require_once 'includes/global.inc.php';

// load the color-players array into the CMD object
load_players($_SESSION['game_id']); // (db_functions.inc.php)

// load our color codes into the session var
$_SESSION['color'] = load_colors($_SESSION['player_id'], $_SESSION['game_id']);

// load the players previous moves into the array
$cmd_array = load_commands($_SESSION['player_id'], $_SESSION['game_id'], $_SESSION['color']);

// load the current game board into the CMD object
load_board($_SESSION['game_id']); // (db_functions.inc.php)
call(is_game_won($_SESSION['game_id']));
call(is_game_drawn($_SESSION['game_id']));
call(is_game_over($_SESSION['game_id']));

#call('--CREATE GAME--');
#create_game(1,4);
#call('--END CREATE GAME--');

#$CMD->setFoldedBoard('////////KD,RH,RT//////YI,YT/AF/AB,KB,YC,RC//YT//AI,KC,YB,YH///AT,RT,KD//2YI,YT,AD//KD//KC/YD,AT/AC,KC//AT,RT,KT/KH//AD,YI,RD//////KH,RD,AI///RH,2AF,YF///////////////');
#
#// call($CMD->board);
#$CMD->getConflicts( );
#$CMD->resolveConflicts( );
#$awards = $CMD->getAwards( );
#call('awards');

$executable = load_executable($_SESSION['player_id'], $_SESSION['game_id'], $_SESSION['color']); // (db_functions.inc.php)


// if we have test data to deal with...
$errors = '';
if (isset($_POST['enter']) && ('Test' == $_POST['enter']))
{
	$color = $_POST['color'];

	// save the submitted commands into an array
	for ($i = 0; $i <= 4; ++$i)
	{
		// NOTE: this step will overwrite any values
		// we may have retrieved from the database
		$cmd_array[$color][$i] = $CMD->cleanCommand($_POST['cmd' . $i]);
	}

	$errors[$color] = process_commands($cmd_array[$color], $color, $_SESSION['player_id'], $_SESSION['game_id']); // (db_functions.inc.php)

	// if we have errors, don't allow execute
	foreach ($errors[$color] as $error)
	{
		if ('No command given' != $error)
		{
			$executable = 0;
		}
	}
}


// if we have execute data to deal with...
if (isset($_POST['enter']) && ('Execute' == $_POST['enter']))
{
	// save the users commands
	$result = save_commands($_SESSION['color'], $_SESSION['player_id'], $_SESSION['game_id']); // (db_functions.inc.php)

	// and run the game if it's ready
	$result = run_game($_SESSION['game_id']); // (db_functions.inc.php)

	// deal with any color ambiguities


	// test the game for completion
//  $finished = is_game_over($_SESSION['game_id']);
}

// used a function because there may be more than one form shown
$command_form = load_form($cmd_array, $errors, $_SESSION['color'], $_SESSION['player_id'], $_SESSION['game_id']); // (db_functions.inc.php)

?>
<html><head><title></title>
<link href="css/game.css" type="text/css" rel="stylesheet" />
<script type="text/javascript" src="scripts/game.js"></script>
<script type="text/javascript">
var executable = <?php echo $executable; ?>;
</script>
</head>
<body>
<h1>TEST BED</h1>
<?php echo $command_form; ?>
<hr />
<?php call('_POST'); ?>
</body></html>

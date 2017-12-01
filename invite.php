<?php

require_once 'includes/inc.global.php';

// this has nothing to do with creating a game
// but I'm running it here to prevent long load
// times on other pages where it would be run more often
#GamePlayer::delete_inactive(Settings::read('expire_users'));
#Game::delete_inactive(Settings::read('expire_games'));
#Game::delete_finished(Settings::read('expire_finished_games'));

$Game = new Game( );

if (isset($_POST['invite'])) {
	// make sure this user is not full
	if ($GLOBALS['Player']->max_games && ($GLOBALS['Player']->max_games <= $GLOBALS['Player']->current_games)) {
		Flash::store('You have reached your maximum allowed games !', false);
	}

	test_token( );

	try {
		Game::invite( );
		Flash::store('Invitation Sent Successfully', true);
	}
	catch (MyException $e) {
		Flash::store('Invitation FAILED !', false);
	}
}

// grab the full list of players
$players_full = GamePlayer::get_list(true);
$invite_players = array_shrink($players_full, 'player_id');

// grab the players who's max game count has been reached
$players_maxed = GamePlayer::get_maxed( );
$players_maxed[] = $_SESSION['player_id'];

// remove the maxed players from the invite list
$players = array_diff($invite_players, $players_maxed);

$opponent_selection = '';
#$opponent_selection .= '<option value="X">-- Closed --</option>';
$opponent_selection .= '<option value="">-- Open --</option>';
foreach ($players_full as $player) {
	if ($_SESSION['player_id'] == $player['player_id']) {
		continue;
	}

	if (in_array($player['player_id'], $players)) {
		$opponent_selection .= '
			<option value="'.$player['player_id'].'">'.$player['username'].'</option>';
	}
}

$types = array(
	'4 Player',
//	'2&times;2 Player (Same Side)',
//	'2&times;2 Player (Corners)',
//	'2&times;4 Player (Same Side)',
//	'2&times;4 Player (Corners)',
//	'3 Player Mercenary',
);
$type_selection = '';
foreach ($types as $type) {
	$type_selection .= '<option>'.$type.'</option>';
}

$meta['title'] = 'Send Game Invitation';
$meta['foot_data'] = '
	<script type="text/javascript" src="scripts/invite.js"></script>
';

$hints = array(
	'Invite a player to a game by filling out your desired game options.' ,
	'<span class="highlight">Highlighted</span> invitee names show who has accepted the invite' ,
	'<span class="highlight">WARNING!</span><br />Games will be deleted after '.Settings::read('expire_games').' days of inactivity.' ,
);

// make sure this user is not full
$submit_button = '<div><label></label><input type="submit" name="invite" value="Send Invitation" /></div>';
$warning = '';
if ($GLOBALS['Player']->max_games && ($GLOBALS['Player']->max_games <= $GLOBALS['Player']->current_games)) {
	$submit_button = $warning = '<p class="warning">You have reached your maximum allowed games, you can not create this game !</p>';
}

$contents = <<< EOF
	<form method="post" action="{$_SERVER['REQUEST_URI']}" id="send"><div class="formdiv">

		<input type="hidden" name="token" value="{$_SESSION['token']}" />
		<input type="hidden" name="player_id" value="{$_SESSION['player_id']}" />

		<div>

			{$warning}

			<div><label for="type">Game Type</label><select id="type" name="type">{$type_selection}</select></div>
			<div>
				<label for="opponent1">Opponents</label>
				<div class="block">
					<select id="opponent1" name="opponent1">{$opponent_selection}</select><br />
					<select id="opponent2" name="opponent2">{$opponent_selection}</select><br />
					<select id="opponent3" name="opponent3">{$opponent_selection}</select><br />
				</div>
			</div>
			<div><label for="custom_rules">Custom Rules</label><textarea name="custom_rules" id="custom_rules" rows="5" cols="30"></textarea></div>

			{$submit_button}

		</div>

	</div></form>

EOF;

// create our invitation tables
list($in_vites, $out_vites, $open_vites) = Game::get_invites($_SESSION['player_id']);

$contents .= <<< EOT
	<hr class="clear" />
	<form method="post" action="{$_SERVER['REQUEST_URI']}"><div class="formdiv" id="invites">
EOT;

$table_meta = array(
	'sortable' => true ,
	'no_data' => '<p>There are no received invites to show</p>' ,
	'caption' => 'Invitations Received' ,
);
$table_format = array(
	array('Host', 'host') ,
	array('Invitees', '###((\'[[[player_1_name]]]\') ? ((\'J\' == \'[[[player_1_color]]]\') ? \'<span class="highlight">[[[player_1_name]]]</span>\' : \'[[[player_1_name]]]\') : \'OPEN\').\', \'.((\'[[[player_2_name]]]\') ? ((\'J\' == \'[[[player_2_color]]]\') ? \'<span class="highlight">[[[player_2_name]]]</span>\' : \'[[[player_2_name]]]\') : \'OPEN\').\', \'.((\'[[[player_3_name]]]\') ? ((\'J\' == \'[[[player_3_color]]]\') ? \'<span class="highlight">[[[player_3_name]]]</span>\' : \'[[[player_3_name]]]\') : \'OPEN\').\', \'.((\'[[[player_4_name]]]\') ? ((\'J\' == \'[[[player_4_color]]]\') ? \'<span class="highlight">[[[player_4_name]]]</span>\' : \'[[[player_4_name]]]\') : \'OPEN\')') ,
	array('Type', 'type') ,
	array('Date Sent', '###date(Settings::read(\'long_date\'), strtotime(\'[[[create_date]]]\'))', null, ' class="date"') ,
	array('Action', '<input type="button" id="accept-[[[game_id]]]" value="Accept" /><input type="button" id="decline-[[[game_id]]]" value="Decline" />', false) ,
);
$contents .= get_table($table_format, $in_vites, $table_meta);

$table_meta = array(
	'sortable' => true ,
	'no_data' => '<p>There are no sent invites to show</p>' ,
	'caption' => 'Invitations Sent' ,
);
$table_format = array(
	array('Invitees', '###((\'[[[player_1_name]]]\') ? ((\'J\' == \'[[[player_1_color]]]\') ? \'<span class="highlight">[[[player_1_name]]]</span>\' : \'[[[player_1_name]]]\') : \'OPEN\').\', \'.((\'[[[player_2_name]]]\') ? ((\'J\' == \'[[[player_2_color]]]\') ? \'<span class="highlight">[[[player_2_name]]]</span>\' : \'[[[player_2_name]]]\') : \'OPEN\').\', \'.((\'[[[player_3_name]]]\') ? ((\'J\' == \'[[[player_3_color]]]\') ? \'<span class="highlight">[[[player_3_name]]]</span>\' : \'[[[player_3_name]]]\') : \'OPEN\').\', \'.((\'[[[player_4_name]]]\') ? ((\'J\' == \'[[[player_4_color]]]\') ? \'<span class="highlight">[[[player_4_name]]]</span>\' : \'[[[player_4_name]]]\') : \'OPEN\')') ,
	array('Type', 'type') ,
	array('Date Sent', '###date(Settings::read(\'long_date\'), strtotime(\'[[[create_date]]]\'))', null, ' class="date"') ,
	array('Action', '###\'<input type="button" id="withdraw-[[[game_id]]]" value="Withdraw" />\'.((strtotime(\'[[[invite_date]]]\') >= strtotime(\'[[[resend_limit]]]\')) ? \'\' : \'<input type="button" id="resend-[[[game_id]]]" value="Resend" />\')', false) ,
);
$contents .= get_table($table_format, $out_vites, $table_meta);

$table_meta = array(
	'sortable' => true ,
	'no_data' => '<p>There are no open invites to show</p>' ,
	'caption' => 'Open Invitations' ,
);
$table_format = array(
	array('Host', 'host') ,
	array('Invitees', '###((\'[[[player_1_name]]]\') ? ((\'J\' == \'[[[player_1_color]]]\') ? \'<span class="highlight">[[[player_1_name]]]</span>\' : \'[[[player_1_name]]]\') : \'OPEN\').\', \'.((\'[[[player_2_name]]]\') ? ((\'J\' == \'[[[player_2_color]]]\') ? \'<span class="highlight">[[[player_2_name]]]</span>\' : \'[[[player_2_name]]]\') : \'OPEN\').\', \'.((\'[[[player_3_name]]]\') ? ((\'J\' == \'[[[player_3_color]]]\') ? \'<span class="highlight">[[[player_3_name]]]</span>\' : \'[[[player_3_name]]]\') : \'OPEN\').\', \'.((\'[[[player_4_name]]]\') ? ((\'J\' == \'[[[player_4_color]]]\') ? \'<span class="highlight">[[[player_4_name]]]</span>\' : \'[[[player_4_name]]]\') : \'OPEN\')') ,
	array('Type', 'type') ,
	array('Date Sent', '###date(Settings::read(\'long_date\'), strtotime(\'[[[create_date]]]\'))', null, ' class="date"') ,
	array('Action', '<input type="button" id="accept-[[[game_id]]]" value="Accept" />', false) ,
);
$contents .= get_table($table_format, $open_vites, $table_meta);

$contents .= <<< EOT
	</div></form>
EOT;

echo get_header($meta);
echo get_item($contents, $hints, $meta['title']);
call($GLOBALS);
echo get_footer($meta);


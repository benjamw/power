<?php
/*
+---------------------------------------------------------------------------
|
|   game.class.php (php 5.x)
|
|   by Benjam Welker
|   http://iohelix.net
|
+---------------------------------------------------------------------------
|
|	This module is built to facilitate the game Power, it doesn't really
|	care about how to play, or the deep goings on of the game, only about
|	database structure and how to allow players to interact with the game.
|
+---------------------------------------------------------------------------
|
|   > Power Game module
|   > Date started: 2010-11-15
|
|   > Module Version Number: 0.8.0
|
+---------------------------------------------------------------------------
*/

// TODO: comments & organize better

if (defined('INCLUDE_DIR')) {
	require_once INCLUDE_DIR.'func.array.php';
}

class Game
{

	/**
	 *		PROPERTIES
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** const property GAME_TABLE
	 *		Holds the game table name
	 *
	 * @var string
	 */
	const GAME_TABLE = T_GAME;


	/** const property GAME_PLAYER_TABLE
	 *		Holds the game player glue table name
	 *
	 * @var string
	 */
	const GAME_PLAYER_TABLE = T_GAME_PLAYER;


	/** const property GAME_HISTORY_TABLE
	 *		Holds the game history table name
	 *
	 * @var string
	 */
	const GAME_HISTORY_TABLE = T_GAME_HISTORY;


	/** const property GAME_NUDGE_TABLE
	 *		Holds the game nudge table name
	 *
	 * @var string
	 */
	const GAME_NUDGE_TABLE = T_GAME_NUDGE;


	/** static protected property _PLAYER_DEFAULTS
	 *		Holds the default data for the players
	 *
	 * @var array
	 */
	static protected $_PLAYER_DEFAULTS = array(
			'color' => 'O',
			'commands' => null,
			'state' => 'Waiting',
			'last_move' => '0000-00-00 00:00:00',
		);


	/** static protected property _EXTRA_INFO_DEFAULTS
	 *		Holds the default extra info data
	 *
	 * @var array
	 */
	static protected $_EXTRA_INFO_DEFAULTS = array(
			'custom_rules' => '',
		);


	/** static protected property _GAME_TYPE_CAPACITY
	 *		Holds the capacity for each of the different game types
	 *
	 * @var array
	 */
	static protected $_GAME_TYPE_CAPACITY = array(
			'4 Player' => 4,
			'2 Player' => 2,
			'2x4 Player' => 2,
			'3 Player Mercenary' => 3,
		);


	/** public property id
	 *		Holds the game's id
	 *
	 * @var int
	 */
	public $id;


	/** protected property name
	 *		Holds the game's name (csv of player names)
	 *
	 * @var string
	 */
	protected $name;


	/** protected property type
	 *		Holds the game's type
	 *
	 * @see _GAME_TYPE_CAPACITY
	 * @var string
	 */
	protected $type;


	/** public property state
	 *		Holds the game's current state
	 *		can be one of 'Waiting', 'Playing', 'Finished'
	 *
	 * @var string (enum)
	 */
	public $state;


	/** public property paused
	 *		Holds the game's current pause state
	 *
	 * @var bool
	 */
	public $paused;


	/** public property winners
	 *		Holds the game winner ids
	 *
	 * @var array of int player ids
	 */
	public $winners;


	/** public property create_date
	 *		Holds the game's create date
	 *
	 * @var int (unix timestamp)
	 */
	public $create_date;


	/** public property modify_date
	 *		Holds the game's modified date
	 *
	 * @var int (unix timestamp)
	 */
	public $modify_date;


	/** public property last_move
	 *		Holds the game's last move date
	 *
	 * @var int (unix timestamp)
	 */
	public $last_move;


	/** protected property history
	 *		Holds our game move history
	 *
	 * @var array
	 */
	protected $history;


	/** protected property _power
	 *		Holds the power object reference
	 *
	 * @var Power object reference
	 */
	protected $_power;


	/** protected property _host_id
	 *		Holds the game's host id
	 *
	 * @var int
	 */
	protected $_host_id;


	/** protected property _extra_info
	 *		Holds the extra game info
	 *
	 * @var array
	 */
	protected $_extra_info;


	/** protected property _players
	 *		Holds our player's object references
	 *		along with other game data
	 *
	 * @var array of player data
	 */
	protected $_players;


	/**
	 *		METHODS
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** public function __construct
	 *		Class constructor
	 *		Sets all outside data
	 *
	 * @param int [optional] game id
	 * @action instantiates object
	 * @return void
	 */
	public function __construct($id = 0)
	{
		call(__METHOD__);

		$this->id = (int) $id;
		call($this->id);

		$this->_power = new Power( );

		try {
			$this->_pull( );
		}
		catch (MyException $e) {
			throw $e;
		}
	}


	/** public function __destruct
	 *		Class destructor
	 *		Gets object ready for destruction
	 *
	 * @param void
	 * @action saves changed data
	 * @action destroys object
	 * @return void
	 */
	public function __destruct( )
	{
		// save anything changed to the database
		// BUT... only if PHP didn't die because of an error
		$error = error_get_last( );

		if ($this->id && (0 == ((E_ERROR | E_WARNING | E_PARSE) & $error['type']))) {
			try {
				$this->_save( );
			}
			catch (MyException $e) {
				// do nothing, it will be logged
			}
		}
	}


	/** public function __get
	 *		Class getter
	 *		Returns the requested property if the
	 *		requested property is not _private
	 *
	 * @param string property name
	 * @return mixed property value
	 */
	public function __get($property)
	{
		if ( ! property_exists($this, $property)) {
			throw new MyException(__METHOD__.': Trying to access non-existant property ('.$property.')', 2);
		}

		if ('_' === $property[0]) {
			throw new MyException(__METHOD__.': Trying to access _private property ('.$property.')', 2);
		}

		return $this->$property;
	}


	/** public function __set
	 *		Class setter
	 *		Sets the requested property if the
	 *		requested property is not _private
	 *
	 * @param string property name
	 * @param mixed property value
	 * @action optional validation
	 * @return bool success
	 */
	public function __set($property, $value)
	{
		if ( ! property_exists($this, $property)) {
			throw new MyException(__METHOD__.': Trying to access non-existant property ('.$property.')', 3);
		}

		if ('_' === $property[0]) {
			throw new MyException(__METHOD__.': Trying to access _private property ('.$property.')', 3);
		}

		$this->$property = $value;
	}


	/** public function invite
	 *		Creates a new Power game
	 *		and invites any given players to the game
	 *
	 * @param void
	 * @action inserts a new game into the database
	 * @return insert id
	 */
	public function invite( )
	{
		call(__METHOD__);
		call($_POST);

		// DON'T sanitize the data
		// it gets sani'd in the MySQL->insert method
		$_P = $_POST;

		// translate (filter/sanitize) the data
		$_P['host_id'] = (int) $_P['player_id'];

		call($_P);

		$extra_info = array(
			'custom_rules' => htmlentities($_P['custom_rules'], ENT_QUOTES, 'ISO-8859-1', false),
		);
		call($extra_info);

		$diff = array_compare($extra_info, self::$_EXTRA_INFO_DEFAULTS);
		$extra_info = $diff[0];
		ksort($extra_info);

		call($extra_info);
		if ( ! empty($extra_info)) {
			$_P['extra_info'] = serialize($extra_info);
		}

		// create the game
		$required = array(
			'host_id' ,
		);

		$key_list = array_merge($required, array(
			'extra_info' ,
		));

		try {
			$_DATA = array_clean($_P, $key_list, $required);
		}
		catch (MyException $e) {
			throw $e;
		}

		$_DATA['state'] = 'Waiting';
		$_DATA['create_date '] = 'NOW( )'; // note the trailing space in the field name, this is not a typo
		$_DATA['modify_date '] = 'NOW( )'; // note the trailing space in the field name, this is not a typo

		// THIS IS THE ONLY PLACE IN THE CLASS WHERE IT BREAKS THE _pull / _save MENTALITY
		// BECAUSE I NEED THE INSERT ID FOR THE REST OF THE GAME FUNCTIONALITY

		$insert_id = Mysql::get_instance( )->insert(self::GAME_TABLE, $_DATA);

		if (empty($insert_id)) {
			throw new MyException(__METHOD__.': Game could not be created');
		}

		$this->id = $insert_id;

		// set the modified date
		Mysql::get_instance( )->insert(self::GAME_TABLE, array('modify_date' => NULL), " WHERE game_id = '{$this->id}' ");

		try {
			// pull the fresh data
			$this->_pull( );

			// now add any players to the game
			$this->join( );

			for ($i = 1; $i < 4; ++$i) {
				if ( ! empty($_P['opponent'.$i])) {
					$this->join($_P['opponent'.$i]);
				}
			}
		}
		catch (MyException $e) {
			throw $e;
		}

		return $this->id;
	}


	/** public function accept_invite
	 *		Accepts the game invite
	 *
	 * @param void
	 * @action optionally starts the game
	 * @return void
	 */
	public function accept_invite( )
	{
		call(__METHOD__);

		try {
			if ($this->_players[$_SESSION['player_id']]) {
				$this->_players[$_SESSION['player_id']]['color'] = 'J';
			}
			else {
				$this->join( );
			}

			$this->_test_capacity( );
		}
		catch (MyException $e) {
			throw $e;
		}
	}


	/** public function is_host
	 *		Tests if the given player is the game host
	 *
	 * @param int player id
	 * @return bool is host
	 */
	public function is_host($player_id)
	{
		call(__METHOD__);

		$player_id = (int) $player_id;

		if ($player_id == $this->_host_id) {
			return true;
		}

		return false;
	}


	/** public function is_player
	 *		Tests if the given player is already in this game or not
	 *
	 * @param int player id
	 * @return bool is player in game
	 */
	public function is_player($player_id)
	{
		call(__METHOD__);

		$player_id = (int) $player_id;

		if (isset($this->_players[$player_id])) {
			return true;
		}

		return false;
	}


	/** public function join
	 *		Joins a game that is waiting
	 *
	 * @param int [optional] player id
	 * @action inserts the join into the database
	 * @return void
	 */
	public function join($player_id = null)
	{
		call(__METHOD__);

		$this->_test_capacity( );

		// check the game state
		if ('Waiting' != $this->state) {
			throw new MyException(__METHOD__.': Player #'.$_POST['player_id'].' tried to join non-waiting game #'.$this->id, 211);
		}

		if (is_null($player_id)) {
			$_DATA['player_id'] = (int) $_SESSION['player_id'];
			$_DATA['color'] = 'J';
		}
		else {
			$_DATA['player_id'] = (int) $player_id;
		}

		$required = array(
			'player_id' ,
		);

		$keys = array_merge($required, array(
			'color',
		));

		try {
			$_DATA = array_clean($_DATA, $keys, $required);
		}
		catch (MyException $e) {
			throw $e;
		}

		$_DATA['game_id'] = $this->id;
		$_DATA['state'] = 'Waiting';

		if ( ! $this->is_player($_DATA['player_id'])) {
			$this->_set_player_data($_DATA);
		}
		else {
			$this->_players[$_DATA['player_id']] = array_merge($this->_players[$_DATA['player_id']], $_DATA);
		}

		$this->_test_capacity( );
	}


	/** static public function check_turns
	 *		Returns a count of all games
	 *		in which it is the user's turn
	 *
	 * @param int player id
	 * @return int number of games with player action
	 */
	static public function check_turns($player_id)
	{
		$list = self::get_list($player_id, false);
		$turn_count = array_sum_field($list, 'my_turn');
		return $turn_count;
	}


	/** protected function _pull
	 *		Pulls all game data from the database
	 *
	 * @param void
	 * @action pulls the game data
	 * @return void
	 */
	protected function _pull( )
	{
		call(__METHOD__);

		$query = "
			SELECT G.*
			FROM ".self::GAME_TABLE." AS G
			WHERE G.game_id = '{$this->id}'
		";
		$result = Mysql::get_instance( )->fetch_assoc($query);
		call($result);

		if ((0 != $this->id) && ( ! $result)) {
			throw new MyException(__METHOD__.': Game data not found for game #'.$this->id);
		}

		if ($result) {
			$this->_host_id = (int) $result['host_id'];
			$this->type = $result['type'];
			$this->state = $result['state'];
			$this->create_date = strtotime($result['create_date']);
			$this->modify_date = strtotime($result['modify_date']);
			$this->paused = (bool) $result['paused'];

			$this->_extra_info = array_merge_plus(self::$_EXTRA_INFO_DEFAULTS, unserialize($result['extra_info']));

			try {
				$this->_pull_players( );
				$this->_pull_history( );
				$this->_update_power( );
			}
			catch (MyException $e) {
				throw $e;
			}
		}
	}


	/** protected function _pull_history
	 *		Pulls all game history from the database
	 *
	 * @param void
	 * @action creates the game history array
	 * @return void
	 */
	protected function _pull_history( )
	{
		call(__METHOD__);

		// TODO: build this properly
	}


	/** protected function _pull_players
	 *		Pulls all player data from the database
	 *
	 * @param void
	 * @action pulls the player data
	 * @return void
	 */
	protected function _pull_players( )
	{
		call(__METHOD__);

		$this->_players = array( );

		$query = "
			SELECT player_id
			FROM ".self::GAME_PLAYER_TABLE."
			WHERE game_id = '{$this->id}'
			ORDER BY color = 'Y' DESC
				, color = 'K' DESC
				, color = 'R' DESC
				, color = 'A' DESC
		";
		$result = Mysql::get_instance( )->fetch_array($query);

		if ((0 != $this->id) && ! $result && ! isset($_POST['invite'])) {
			throw new MyException(__METHOD__.': Player data not found for game #'.$this->id);
		}

		if ($result) {
			foreach ($result as $player) {
				$this->_set_player_data($player);
			}

			$names = array( );
			foreach ($this->_players as $player) {
				$names[] = $player['object']->username;
			}
			call($this->_players);

			$this->name = implode(', ', $names);
			call($this->name);
		}
	}


	/** protected function _update_power
	 *		Updates the power object with the current game data
	 *
	 * @param void
	 * @action updates the Power object
	 * @return void
	 */
	protected function _update_power( )
	{
		call(__METHOD__);

		if (0 == $this->id) {
			// no exception, just quit
			return false;
		}

		// TODO: build this properly
	}


	/** protected function _save
	 *		Saves all changed data to the database
	 *
	 * @param void
	 * @action saves the game data
	 * @return void
	 */
	protected function _save( )
	{
		call(__METHOD__);

		if ( ! $this->id) {
			return;
		}

		$Mysql = Mysql::get_instance( );

		// grab the current game data
		$query = "
			SELECT extra_info
				, state
				, modify_date
			FROM ".self::GAME_TABLE."
			WHERE game_id = '{$this->id}'
		";
		$game = $Mysql->fetch_assoc($query);

		$update_modified = false;

		if ( ! $game) {
			throw new MyException(__METHOD__.': Game data not found for game #'.$this->id);
		}

		// test the modified date and make sure we still have valid data
		call($this->modify_date);
		call(strtotime($game['modify_date']));
		if ($this->modify_date != strtotime($game['modify_date'])) {
			$this->_log('== FAILED == DATA SAVE: #'.$this->id.' @ '.time( )."\n".' - '.$this->modify_date."\n".' - '.strtotime($game['modify_date']));
			throw new MyException(__METHOD__.': Trying to save game (#'.$this->id.') with out of sync data');
		}

		// update the game data
		$update_game = false;
		if ($game['state'] != $this->state) {
			$update_game['state'] = $this->state;
		}

		$diff = array_compare($this->_extra_info, self::$_EXTRA_INFO_DEFAULTS);
		$update_game['extra_info'] = $diff[0];
		ksort($update_game['extra_info']);

		$update_game['extra_info'] = serialize($update_game['extra_info']);

		if ('a:0:{}' == $update_game['extra_info']) {
			$update_game['extra_info'] = null;
		}

		if (0 === strcmp($game['extra_info'], $update_game['extra_info'])) {
			unset($update_game['extra_info']);
		}

		if ($update_game) {
			$update_modified = true;
			$Mysql->insert(self::GAME_TABLE, $update_game, " WHERE game_id = '{$this->id}' ");
		}

		// grab the player's data
		$query = "
			SELECT *
			FROM ".self::GAME_PLAYER_TABLE."
			WHERE game_id = '{$this->id}'
		";
		$db_players = $Mysql->fetch_array($query);

		// add missing players
		$db_player_ids = array_shrink($db_players, 'player_id');

		if ( ! $db_player_ids) {
			$db_player_ids = array( );
		}

		$game_player_ids = array_keys($this->_players);
		$new_players = array_diff($game_player_ids, $db_player_ids);

		foreach ($new_players as $new_player_id) {
			$new_power_player = $this->_power->get_player($new_player_id);

			$update_player = array(
				'game_id' => $this->id,
				'player_id' => $new_player_id,
				'color' => $this->_players[$new_player_id]['color'],
				'commands' => $this->_players[$new_player_id]['commands'],
				'state' => (isset($new_power_player['state']) ? $new_power_player['state'] : 'Waiting'),
			);
			call($update_player);

			$Mysql->insert(self::GAME_PLAYER_TABLE, $update_player);

			$update_modified = true;
		}

		// check the player parts
		foreach ($db_players as $db_player) {
			$update_player = false;
			$player_id = $db_player['player_id'];

			$power_player = $this->_power->get_player($player_id);

			if ($power_player && ($db_player['state'] != $power_player['state'])) {
				$update_player['state'] = $power_player['state'];
			}

			if ($update_player) {
				$update_modified = true;
				$Mysql->insert(self::GAME_PLAYER_TABLE, $update_player, " WHERE game_id = '{$this->id}' AND player_id = '{$player_id}' ");
			}
		}

		// update the game modified date
		if ($update_modified) {
			$Mysql->insert(self::GAME_TABLE, array('modify_date' => NULL), " WHERE game_id = '{$this->id}' ");
		}
	}


	/** protected function _test_capacity
	 *		Tests the capacity of the game
	 *		and starts the game if the game is full
	 *
	 * @param void
	 * @action optionally starts the game
	 * @return void
	 */
	protected function _test_capacity( )
	{
		call(__METHOD__);

		if (empty(self::$_GAME_TYPE_CAPACITY[$this->type])) {
			throw new MyException(__METHOD__.': Capacity data not found for game #'.$this->id.' ('.$game->type.')');
		}

		if (self::$_GAME_TYPE_CAPACITY[$this->type] <= count($this->_players)) {
			// make sure all of the player's colors are set to 'J' and not 'O'
			$full = true;
			foreach ($this->_players as $player) {
				if ('O' == $player['color']) {
					$full = false;
				}
			}

			if ($full) {
				$this->start($this->_host_id);
			}
		}
	}


	/** protected function _set_player_data
	 *		Adds a player to the game and power data
	 *
	 * @param array player data
	 * @return void
	 */
	protected function _set_player_data($data)
	{
		call(__METHOD__);

		$player = array_merge_plus(self::$_PLAYER_DEFAULTS, $data);

		if (empty($player['player_id'])) {
			throw new MyException(__METHOD__.': Missing player ID');
		}

		// if the player got deleted, show that
		try {
			$player['object'] = new GamePlayer($player['player_id']);
		}
		catch (MyException $e) {
			$player['object'] = new GamePlayer( );
			$player['object']->username = '[deleted]';
		}

		// move any data we need to over to the power class player data
		// TODO: find out what data goes where

		$this->_players[$player['player_id']] = $player;

		// TODO: add power player
	}


	/** protected function _log
	 *		Report messages to a file
	 *
	 * @param string message
	 * @action log messages to file
	 * @return void
	 */
	protected function _log($msg)
	{
		// log the error
		if (false && class_exists('Log')) {
			Log::write($msg, __CLASS__);
		}
	}


	/** static public function get_list
	 *		Returns a list array of all games in the database
	 *		with games which need the users attention highlighted
	 *
	 * @param int optional player's id
	 * @param bool optional only show given players games
	 * @return array game list (or bool false on failure)
	 */
	static public function get_list($player_id = 0, $only_player = false)
	{
		$player_id = (int) $player_id;
		$only_player = (bool) $only_player;

		if ( ! $player_id) {
			$only_player = false;
		}

		$AND = '';
		if ($only_player) {
			$query = "
				SELECT DISTINCT GP.game_id
				FROM ".self::GAME_PLAYER_TABLE." AS GP
				WHERE GP.player_id = '{$player_id}'
			";
			$game_ids = Mysql::get_instance( )->fetch_value_array($query);

			if ($match_ids) {
				$AND = " AND G.game_id IN (".implode(',', $game_ids).") ";
			}
		}

		// TODO: this needs more info

		$query = "
			SELECT G.*
			FROM ".self::GAME_TABLE." AS G
			WHERE G.state <> 'Waiting'
				{$AND}
		";
		$list = Mysql::get_instance( )->fetch_array($query);

		return $list;
	}


	/** static public function get_count
	 *		Returns a count of all games in the database
	 *		as well as the highest game id (the total number of games played)
	 *
	 * @param void
	 * @return array (int current game count, int total game count)
	 */
	static public function get_count( )
	{
		$query = "
			SELECT COUNT(*)
			FROM ".self::GAME_TABLE."
			WHERE state <> 'Waiting'
		";
		$count = (int) Mysql::get_instance( )->fetch_value($query);

		$query = "
			SELECT MAX(game_id)
			FROM ".self::GAME_TABLE."
		";
		$next = (int) Mysql::get_instance( )->fetch_value($query);

		return array($count, $next);
	}


	/** static public function get_my_count
	 *		Returns a count of the number of games the given player is currently playing,
	 *		as well as the number of games where it is the current player's turn
	 *
	 * @param void
	 * @return array (int player game count, int player turn count)
	 */
	static public function get_my_count($player_id = 0)
	{
		$player_id = (int) $player_id;

		// my games
		$query = "
			SELECT COUNT(GP.player_id)
			FROM ".self::GAME_PLAYER_TABLE." AS GP
				LEFT JOIN ".self::GAME_TABLE." AS G
					USING (game_id)
			WHERE GP.player_id = '{$player_id}'
				AND G.state NOT IN ('Waiting', 'Finished')
		";
		$mine = (int) Mysql::get_instance( )->fetch_value($query);

		// my turns
		$query = "
			SELECT COUNT(GP.player_id)
			FROM ".self::GAME_PLAYER_TABLE." AS GP
				LEFT JOIN ".self::GAME_TABLE." AS G
					USING (game_id)
			WHERE  GP.player_id = '{$player_id}'
				AND G.state NOT IN ('Waiting', 'Finished')
				AND GP.state NOT IN ('Waiting', 'Resigned', 'Dead')
		";
		$turns = (int) Mysql::get_instance( )->fetch_value($query);

		return array($mine, $turns);
	}


	/** static public function get_invites
	 *		Returns a list array of all the invites in the database
	 *		for the given player
	 *
	 * @param int player's id
	 * @return 2D array invite list
	 */
	static public function get_invites($player_id)
	{
		$player_id = (int) $player_id;

		$query = "
			SELECT G.*
				, COUNT(GP.player_id) AS player_count
				, IF(MP.player_id IS NULL, false, true) AS in_game
				, MP.color AS my_color
				, P.username AS host
			FROM ".self::GAME_TABLE." AS G
				LEFT JOIN ".self::GAME_PLAYER_TABLE." AS GP
					ON (G.game_id = GP.game_id)
				LEFT JOIN ".self::GAME_PLAYER_TABLE." AS MP
					ON (G.game_id = MP.game_id
						AND MP.player_id = '{$player_id}')
				JOIN ".Player::PLAYER_TABLE." AS P
					ON (G.host_id = P.player_id)
			WHERE G.state = 'Waiting'
			GROUP BY G.game_id
			ORDER BY G.create_date DESC
		";
		$list = Mysql::get_instance( )->fetch_array($query);
debug($list);

		$in_vites = $out_vites = $open_vites = array( );
		foreach ($list as $item) {
			// grab if the game is open or not
			$open = false;
			if ($item['player_count'] < self::$_GAME_TYPE_CAPACITY[$item['type']]) {
				$open = true;
			}

			// grab the players in the game
			$query = "
				SELECT GP.*
				FROM ".self::GAME_PLAYER_TABLE." AS GP
				WHERE GP.game_id = '{$item['game_id']}'
			";
			$players = Mysql::get_instance( )->fetch_array($query);

			$i = 0;
			foreach ($players as $player) {
				++$i;
				$item['player_'.$i.'_name'] = $GLOBALS['_PLAYERS'][$player['player_id']];
				$item['player_'.$i.'_color'] = $player['color'];
			}

			$item['host'] = $GLOBALS['_PLAYERS'][$item['host_id']];

			if ($player_id == $item['host_id']) {
				$out_vites[] = $item;
			}
			elseif ('O' == $item['my_color']) {
				$in_vites[] = $item;
			}
			elseif ($open && ! $item['in_game']) {
				$open_vites[] = $item;
			}
		}

		return array($in_vites, $out_vites, $open_vites);
	}


	/** static public function resend_invite
	 *		Resends the invite email (if allowed)
	 *
	 * @param void
	 * @action resends an invite email
	 * @return bool invite email sent
	 */
	static public function resend_invite( )
	{
		call(__METHOD__);
		call($_POST);

		// translate (filter/sanitize) the data
		$player_id = (int) $_SESSION['player_id'];
		$game_id = (int) $_POST['game_id'];

		// grab the game data from the database
		$query = "
			SELECT *
				, COUNT(GP.player_id) AS player_count
				, DATE_ADD(NOW( ), INTERVAL -1 DAY) AS resend_limit
			FROM ".self::GAME_TABLE."
			WHERE game_id = '{$game_id}'
		";
		$game = Mysql::get_instance( )->fetch_assoc($query);

		if ( ! $game) {
			throw new MyException(__METHOD__.': Player (#'.$player_id.') trying to resend an invite for a non-existant game (#'.$game_id.')');
		}

		if ((int) $game['host_id'] !== (int) $player_id) {
			throw new MyException(__METHOD__.': Player (#'.$player_id.') trying to resend an invite for a game (#'.$game_id.') that is not theirs');
		}

		if (1 >= $game['player_count']) {
			throw new MyException(__METHOD__.': Player (#'.$player_id.') trying to resend an invite for a game (#'.$game_id.') that is fully open');
		}

		if (strtotime($game['create_date']) >= strtotime($game['resend_limit'])) {
			throw new MyException(__METHOD__.': Player (#'.$player_id.') trying to resend an invite for a game (#'.$game_id.') that is too new');
		}

		// if we get here, all is good...

		// TODO: figure out which players we need to send the email to (only O players)
		// also make sure gauntlet above is finished with player count and all that
		$sent = false;

		if ($sent) {
			// update the invite_date to prevent invite resend flooding
			$_DATA['create_date '] = 'NOW( )'; // note the trailing space in the field name, this is not a typo
			Mysql::get_instance( )->insert(self::GAME_TABLE, $_DATA, " WHERE game_id = '{$game_id}' ");
		}

		return $sent;
	}


	/** static public function delete_invite
	 *		Deletes the invite for the given game
	 *
	 * @param int game id
	 * @action deletes the invite
	 * @return void
	 */
	static public function delete_invite($game_id)
	{
		call(__METHOD__);

		$game_id = (int) $game_id;

		Mysql::get_instance( )->delete(self::GAME_PLAYER_TABLE, " WHERE game_id = '{$game_id}' AND player_id = '{$_SESSION['player_id']}' AND color = 'O' ");
	}


	/** static public function has_invite
	 *		Tests if the given player has an invite to the given game
	 *
	 * @param int game id
	 * @param int player id
	 * @param bool optional player can accept invite
	 * @action deletes the invite
	 * @return void
	 */
	static public function has_invite($game_id, $player_id, $accept = false)
	{
		call(__METHOD__);

		$game_id = (int) $game_id;
		$player_id = (int) $player_id;
		$accept = (bool) $accept;

		$query = "
			SELECT COUNT(*)
			FROM ".self::GAME_PLAYER_TABLE."
			WHERE game_id = '{$game_id}'
				AND player_id = '{$player_id}'
				AND color = 'O'
		";
		$has_invite = (bool) Mysql::get_instance( )->fetch_value($query);

		if ( ! $has_invite && $accept) {
			$query = "
				SELECT G.type
					, COUNT(GP.player_id) AS player_count
				FROM ".self::GAME_TABLE." AS G
					LEFT JOIN ".self::GAME_PLAYER_TABLE." AS GP
						ON (G.game_id = GP.game_id)
				WHERE G.game_id = '{$game_id}'
					AND G.state = 'Waiting'
				GROUP BY G.game_id
			";
			$game = Mysql::get_instance( )->fetch_assoc($query);
debug($game);

			if ($game['player_count'] < self::$_GAME_TYPE_CAPACITY[$game['type']]) {
				$has_invite = true;
			}
		}

		return $has_invite;
	}


	/** static public function get_invite_count
	 *		Returns a count array of all the invites in the database
	 *		for the given player
	 *
	 * @param int player's id
	 * @return 2D array invite count
	 */
	static public function get_invite_count($player_id)
	{
		$player_id = (int) $player_id;

		list($in_vites, $out_vites, $open_vites) = self::get_invites($player_id);

		return array(count($in_vites), count($out_vites), count($open_vites));
	}

} // end of Game class
















































class GameOrig
{


	/** protected function _set_player_data
	 *		Adds a player to the game and power data
	 *
	 * @param array player data
	 * @return void
	 */
	protected function _set_player_data($data)
	{
		call(__METHOD__);

		$player = array_merge_plus(self::$_PLAYER_DEFAULTS, $data);

		if (empty($player['player_id'])) {
			throw new MyException(__METHOD__.': Missing player ID');
		}

		// if the player got deleted, show that
		try {
			$player['object'] = new GamePlayer($player['player_id']);
		}
		catch (MyException $e) {
			$player['object'] = new GamePlayer( );
			$player['object']->username = '[deleted]';
		}

		// move any data we need to over to the power class player data
		$power_player = $player;

		$player_keys = array(
			'player_id',
			'color',
			'move_date',
			'object',
		);

		$player = array_clean($player, $player_keys);

		$power_player_keys = array(
			'player_id',
			'color',
			'commands',
			'state',
			'extra_info',
		);

		$power_player = array_clean($power_player, $power_player_keys);

		$this->_players[$player['player_id']] = $player;
		$this->_power->add_player($power_player['color'], $power_player);
	}


	/** public function start
	 *		Starts a game that is waiting
	 *
	 * @param void
	 * @action sets the game to start in the database
	 * @return bool success
	 */
	public function start( )
	{
		call(__METHOD__);

		if ($this->paused) {
			throw new MyException(__METHOD__.': Trying to perform an action on a paused game');
		}

		if ('Waiting' != $this->state) {
			throw new MyException(__METHOD__.': Trying to start a game (#'.$this->id.') that is not \'Waiting\'');
		}

		// make sure there are no open spaces
		$open = false;
		foreach ($this->_players as $player) {
			if (0 == $player['player_id']) {
				$open = true;
			}
		}
		if ($open) {
			throw new MyException(__METHOD__.': Player attempting to start the game (#'.$this->id.') without enough players.');
		}

		try {
			$color_list = $this->_power->init_board($this->type);
		}
		catch (MyException $e) {
			throw $e;
		}

		// randomly order the players
		$player_ids = array_keys($this->_players);
		shuffle($player_ids);

		$i = 0;
		foreach ($player_ids as $player_id) {
			++$i;
			$this->_power->players[$player_id]['state'] = 'Moving';
			$this->_power->players[$player_id]['color'] = $color_list[$i];
		}

		// set the game state
		$this->state = 'Playing';

		Email::send('start', $player_ids, array('id' => $this->id, 'name' => $this->name));

		return true;
	}


	/** public function get_board
	 *		Returns the game board
	 *
	 * @param void
	 * @return array board data
	 */
	public function get_board( )
	{
		return $this->_power->board;
	}


	/** protected function _pull_history
	 *		Pulls all move data from the database
	 *
	 * @param void
	 * @action pulls the move data
	 * @return void
	 */
	protected function _pull_history( )
	{
		call(__METHOD__);

		$this->history = array( );
		$this->last_move = $this->create_date;

		$query = "
			SELECT *
			FROM ".self::GAME_HISTORY_TABLE."
			WHERE game_id = '{$this->id}'
			ORDER BY move_date DESC
		";
		$result = Mysql::get_instance( )->fetch_array($query);

		if ($result) {
			$this->history = $result;
			if ($this->history[0]) {
				$this->last_move = strtotime($this->history[0]['move_date']);
			}
		}
	}


	/** protected function _pull_players
	 *		Pulls all player data from the database
	 *
	 * @param void
	 * @action pulls the player data
	 * @return void
	 */
	protected function _pull_players( )
	{
		call(__METHOD__);

		$this->_players = array( );

		$query = "
			SELECT player_id
			FROM ".self::GAME_PLAYER_TABLE."
			WHERE game_id = '{$this->id}'
			ORDER BY color = 'Y' DESC
				, color = 'K' DESC
				, color = 'R' DESC
				, color = 'A' DESC
		";
		$result = Mysql::get_instance( )->fetch_array($query);

		if ((0 != $this->id) && ! $result && ! isset($_POST['invite'])) {
			throw new MyException(__METHOD__.': Player data not found for game #'.$this->id);
		}

		if ($result) {
			$names = array( );
			foreach ($result as $key => $player) {
				$player['object'] = new GamePlayer($player['player_id']);
				$this->_players[$player['player_id']] = $player;
				$names[] = $player['object']->username;
			}
			call($this->_players);

			$this->name = implode(', ', $names);
			call($this->name);
		}
	}


	/** protected function _update_power
	 *		Updates the power object with the current game data
	 *
	 * @param void
	 * @action updates the Power object
	 * @return void
	 */
	protected function _update_power( )
	{
		call(__METHOD__);

		if (0 == $this->id) {
			// no exception, just quit
			return false;
		}

		// pull the power player data
		$this->_power->set_players($this->_pull_power_players( ));

		// set up the board
		$moves = array( );
		foreach ($this->history as $history) {
			$moves[] = $history['move'];
		}

		$this->_power->do_moves($moves);
	}


	/** protected function _pull_power_players
	 *		Pull the player data for the power class
	 *
	 * @param void
	 * @return array Power player data
	 */
	protected function _pull_power_players( )
	{
		call(__METHOD__);

		if (empty($this->_players)) {
			$this->_pull_players( );
		}

		// TODO: need to figure out what data I need in which class
		$players = $this->_players;

		return $players;
	}


	/** protected function _save
	 *		Saves the game data to the database
	 *
	 * @param void
	 * @action saves the game data to the database
	 * @return void
	 */
	protected function _save( )
	{
		// TODO
	}


	/**
	 *		STATIC METHODS
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** static public function get_match_id
	 *		Finds the match id for the given game id
	 *
	 * @param int game id
	 * @return int match id
	 */
	static public function get_match_id($game_id)
	{
		$game_id = (int) $game_id;

		$query = "
			SELECT match_id
			FROM ".Game::GAME_TABLE."
			WHERE game_id = '{$game_id}'
		";
		$match_id = (int) Mysql::get_instance( )->fetch_value($query);

		return $match_id;
	}


	/** static public function get_list
	 *		Returns a list array of all games in the database
	 *		with games which need the users attention highlighted
	 *
	 * @param int optional player's id
	 * @param bool optional only show given players games
	 * @return array game list (or bool false on failure)
	 */
	static public function get_list($player_id = 0, $only_player = false)
	{
		$player_id = (int) $player_id;
		$only_player = (bool) $only_player;

		if ( ! $player_id) {
			$only_player = false;
		}

		$WHERE = '';
		if ($only_player) {
			$query = "
				SELECT DISTINCT MP.match_id
				FROM ".Match::MATCH_PLAYER_TABLE." AS MP
				WHERE MP.player_id = '{$player_id}'
			";
			$match_ids = Mysql::get_instance( )->fetch_value_array($query);

			if ($match_ids) {
				$WHERE = " WHERE M.match_id IN (".implode(',', $match_ids).") ";
			}
		}

		$query = "
			SELECT *
			FROM ".self::GAME_TABLE."
		";
		$list = Mysql::get_instance( )->fetch_array($query);

		return $list;
	}


	/** static public function get_count
	 *		Returns a count of all games in the database
	 *		as well as the highest game id (the total number of games played)
	 *
	 * @param void
	 * @return array (int current game count, int total game count)
	 */
	static public function get_count( )
	{
		$query = "
			SELECT COUNT(*)
			FROM ".self::GAME_TABLE."
		";
		$count = (int) Mysql::get_instance( )->fetch_value($query);

		$query = "
			SELECT MAX(game_id)
			FROM ".self::GAME_TABLE."
		";
		$next = (int) Mysql::get_instance( )->fetch_value($query);

		return array($count, $next);
	}


	/** static public function get_my_count
	 *		Returns a count of the number of games the given player is currently playing,
	 *		as well as the number of games where it is the current player's turn
	 *
	 * @param void
	 * @return array (int player game count, int player turn count)
	 */
	static public function get_my_count($player_id = 0)
	{
		$player_id = (int) $player_id;

		// my games
		$query = "
			SELECT COUNT(GP.player_id)
			FROM ".self::GAME_PLAYER_TABLE." AS GP
				LEFT JOIN ".self::GAME_TABLE." AS G
					USING (game_id)
			WHERE GP.player_id = '{$player_id}'
				AND G.state <> 'Finished'
		";
		$mine = Mysql::get_instance( )->fetch_value($query);

		// my turns
		$query = "
			SELECT COUNT(GP.player_id)
			FROM ".self::GAME_PLAYER_TABLE." AS GP
				LEFT JOIN ".self::GAME_TABLE." AS G
					USING (game_id)
			WHERE  GP.player_id = '{$player_id}'
				AND G.state <> 'Finished'
				AND GP.state NOT IN ('Waiting', 'Resigned', 'Dead')
		";
		$turns = Mysql::get_instance( )->fetch_value($query);

		return array($mine, $turns);
	}


	/** static public function check_turns
	 *		Checks if it's the given player's turn in any games
	 *
	 * @param int player id
	 * @return number of games player has a turn in
	 */
	static public function check_turns($player_id)
	{
		call(__METHOD__);

		$player_id = (int) $player_id;

		if ( ! $player_id) {
			return false;
		}

		$query = "
			SELECT COUNT(GP.player_id)
			FROM ".self::GAME_PLAYER_TABLE." AS GP
				LEFT JOIN ".self::GAME_TABLE." AS G
					USING (game_id)
			WHERE  GP.player_id = '{$player_id}'
				AND G.state NOT IN ('Waiting', 'Finished')
				AND GP.state NOT IN ('Waiting', 'Resigned', 'Dead')
		";
		$turn = Mysql::get_instance( )->fetch_value($query);

		return $turn;
	}


	/** public function delete_inactive
	 *		Deletes the inactive games from the database
	 *
	 * @param int age in days (0 = disable)
	 * @action deletes the inactive games
	 * @return void
	 */
	static public function delete_inactive($age)
	{
		call(__METHOD__);

		$age = (int) $age;

		if ( ! $age) {
			return;
		}

		$query = "
			SELECT game_id
			FROM ".self::GAME_TABLE."
			WHERE modify_date < DATE_SUB(NOW( ), INTERVAL {$age} DAY)
				AND create_date < DATE_SUB(NOW( ), INTERVAL {$age} DAY)
		";
		$game_ids = Mysql::get_instance( )->fetch_value_array($query);

		if ($game_ids) {
			self::delete($game_ids);
		}
	}


	/** public function delete_finished
	 *		Deletes the finished games from the database
	 *
	 * @param int age in days (0 = disable)
	 * @action deletes the finished games
	 * @return void
	 */
	static public function delete_finished($age)
	{
		call(__METHOD__);

		$age = (int) $age;

		if ( ! $age) {
			return;
		}

		$query = "
			SELECT game_id
			FROM ".self::GAME_TABLE."
			WHERE state = 'Finished'
				AND modify_date < DATE_SUB(NOW( ), INTERVAL {$age} DAY)
		";
		$game_ids = Mysql::get_instance( )->fetch_value_array($query);

		if ($game_ids) {
			self::delete($game_ids);
		}
	}


	/** static public function delete
	 *		Deletes the given game and all related data
	 *
	 * @param mixed array or csv of game ids
	 * @action deletes the game and all related data from the database
	 * @return void
	 */
	static public function delete($ids)
	{
		array_trim($ids, 'int');

		if (empty($ids)) {
			throw new MyException(__METHOD__.': No game ids given');
		}

		foreach ($ids as $id) {
			self::write_game_file($id);
		}

		$tables = array(
			self::GAME_PLAYER_TABLE ,
			self::GAME_HISTORY_TABLE ,
			self::GAME_TABLE ,
		);

		Mysql::get_instance( )->multi_delete($tables, " WHERE game_id IN (".implode(',', $ids).") ");

		$query = "
			OPTIMIZE TABLE ".self::GAME_TABLE."
				, ".self::GAME_PLAYER_TABLE."
				, ".self::GAME_HISTORY_TABLE."
		";
		Mysql::get_instance( )->query($query);
	}


	/** static public function pause
	 *		Pauses the given games
	 *
	 * @param mixed array or csv of game ids
	 * @param bool optional pause game (false = unpause)
	 * @action pauses the games
	 * @return void
	 */
	static public function pause($ids, $pause = true)
	{
		array_trim($ids, 'int');

		$pause = (int) (bool) $pause;

		if (empty($ids)) {
			throw new MyException(__METHOD__.': No game ids given');
		}

		Mysql::get_instance( )->insert(self::GAME_TABLE, array('paused' => $pause), " WHERE game_id IN (".implode(',', $ids).") ");
	}


	/** static public function write_game_file
	 *		Writes the game logs to a file for storage
	 *
	 * @param int game id
	 * @action writes the game data to a file
	 * @return bool success
	 */
	static public function write_game_file($game_id)
	{
		$game_id = (int) $game_id;

		if ( ! Settings::read('write_file')) {
			return false;
		}

		if (0 == $game_id) {
			return false;
		}

		$query = "
			SELECT *
			FROM ".self::GAME_TABLE."
			WHERE game_id = '{$game_id}'
		";
		$game = Mysql::get_instance( )->fetch_assoc($query);

		if (empty($game)) {
			return false;
		}

		$query = "
			SELECT P.player_id
				, P.username
				, GP.order_num
			FROM ".self::GAME_PLAYER_TABLE." AS GP
				JOIN ".Player::PLAYER_TABLE." AS P
					ON (P.player_id = GP.player_id)
			WHERE GP.game_id = '{$this->id}'
			ORDER BY GP.order_num ASC
		";
		$players = Mysql::get_instance( )->fetch_array($query);

		if (empty($players)) {
			return false;
		}

		$logs = Power::get_logs($game_id, 'machine');

		if (empty($logs)) {
			return false;
		}

		// open the file for writing
		$filename = $GLOBALS['__GAMES_ROOT'].'Power_'.$game_id.'_'.date('Ymd', strtotime($game['create_date'])).'.dat';
		$file = fopen($filename, 'wb');

		if (false === $file) {
			return false;
		}

		fwrite($file, "{$game['game_id']} - {$game['name']}\n");
		fwrite($file, date('Y-m-d', strtotime($game['create_date']))."\n");
		fwrite($file, $GLOBALS['_ROOT_URI']."\n");
		fwrite($file, "=================================\n");

		foreach ($players as $player) {
			fwrite($file, "{$players['player_id']} - {$players['username']}\n");
		}

		fwrite($file, "=================================\n");

		$logs = array_reverse($logs);

		foreach ($logs as $log) {
			fwrite($file, $log['data']."\n");
		}

		fwrite($file, "=================================");

		return fclose($file);
	}


} // end of Game class


/*		schemas
// ===================================

Game table
----------------------
CREATE TABLE po_game (
  game_id int(11) unsigned NOT NULL auto_increment,
  match_id int(11) unsigned NOT NULL,
  paused tinyint(1) NOT NULL default '0',
  finished tinyint(1) NOT NULL default '0',
  create_date timestamp default CURRENT_TIMESTAMP,

  PRIMARY KEY  (game_id)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;


Game Player table
----------------------
CREATE TABLE po_game_player (
  game_id int(11) unsigned NOT NULL,
  player_id int(11) unsigned NOT NULL,
  order_num tinyint(1) NOT NULL,

  UNIQUE KEY game_player (game_id, player_id)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;


Game History Table
----------------------
CREATE TABLE po_game_history (
  game_id int(11) unsigned NOT NULL,
  player_id int(11) unsigned NOT NULL,
  move char(4) NOT NULL,
  move_date timestamp default CURRENT_TIMESTAMP,

  INDEX (game_id),
  INDEX (player_id),
  INDEX (move_date)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci;
*/


<?php
/*
+---------------------------------------------------------------------------
|
|   power.class.php (php 5.x)
|
|   by Benjam Welker
|   http://www.iohelix.net
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

// TODO: convert from OLD class
// TODO: comments & organize better

if (defined('INCLUDE_DIR')) {
	require_once INCLUDE_DIR.'func.array.php';
}

class Power
{

	/**
	 *		PROPERTIES
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */


	/** static protected property COLORS
	 *		Holds the various color codes
	 *
	 * @var array (index starts at 1)
	 */
	static protected $COLORS = array( 1 =>
			'Y', // 1- yellow
			'K', // 2- black
			'R', // 3- red
			'A', // 4- white
		);


/*

				U                     |                     U
				  Y                   |                   K
					+---+-----------+---+-----------+---+
					| Q |   L 0 6   | N |   L 0 7   | Q |
					+---/---+---+---\---/---+---+---\---+
					|   | 8 | 6 | 3 |   | 5 | 7 | 8 |   |
					| L +---+---+---+ L |---+---+---+ L |
					| 0 | 7 | 4 | 1 | 0 | 2 | 4 | 6 | 0 |
					| 5 +---+---+---+ 1 |---+---+---+ 8 |
					|   | 5 | 2 | 0 |   | 0 | 1 | 3 |   |
					+---\---+---+---/---\---+---+---/---+
				----| W |   L 0 4   | X |   L 0 2   | E |----
					+---/---+---+---\---/---+---+---\---+
					|   | 3 | 1 | 0 |   | 0 | 2 | 5 |   |
					| L +---+---+---+ L |---+---+---+ L |
					| 1 | 6 | 4 | 2 | 0 | 1 | 4 | 7 | 0 |
					| 2 +---+---+---+ 3 |---+---+---+ 9 |
					|   | 8 | 7 | 5 |   | 3 | 6 | 8 |   |
					+---\---+---+---/---\---+---+---/---+
					| Q |   L 1 1   | S |   L 1 0   | Q |
					+---+-----------+---+-----------+---+
				  R                   |                   A
				U                     |                     U

*/

	/** static protected property SECTORS
	 *		Holds the master board sector array
	 *		white space added for easier visualization
	 *
	 * @var array
	 */
	static protected $SECTORS = array(
			'YU',                                                     'KU',

				'YQ',        'L06',      'N',         'L07',      'KQ',

					   'Y8', 'Y6', 'Y3',        'K5', 'K7', 'K8',

				'L05', 'Y7', 'Y4', 'Y1', 'L01', 'K2', 'K4', 'K6', 'L08',

					   'Y5', 'Y2', 'Y0',        'K0', 'K1', 'K3',

				 'W',        'L04',       'X',        'L02',       'E',

					   'R3', 'R1', 'R0',        'A0', 'A2', 'A5',

				'L12', 'R6', 'R4', 'R2', 'L03', 'A1', 'A4', 'A7', 'L09',

					   'R8', 'R7', 'R5',        'A3', 'A6', 'A8',

				'RQ',        'L11',       'S',        'L10',      'AQ',

			'RU',                                                     'AU'
		);

	/** static protected property LAND
	 *		Holds the board adjacencies for land
	 *		Reserve -> Headquarters (*U -> *Q) is only one way
	 *
	 * @var array
	 */
	static protected $LAND = array(
			'YU'  => 'YQ',
			'KU'  => 'KQ',

			'YQ'  => 'Y8',
			'N'   => 'Y3,K5',
			'KQ'  => 'K8',

			'Y8'  => 'YQ,Y6,Y7,Y4',
			'Y6'  => 'Y8,Y3,Y7,Y4,Y1',
			'Y3'  => 'N,Y6,Y4,Y1',
			'K5'  => 'N,K7,K2,K4',
			'K7'  => 'K5,K8,K2,K4,K6',
			'K8'  => 'KQ,K7,K4,K6',

			'Y7'  => 'Y8,Y6,Y4,Y5,Y2',
			'Y4'  => 'Y8,Y6,Y3,Y7,Y1,Y5,Y2,Y0',
			'Y1'  => 'Y6,Y3,Y4,Y2,Y0',
			'K2'  => 'K5,K7,K4,K0,K1',
			'K4'  => 'K5,K7,K8,K2,K6,K0,K1,K3',
			'K6'  => 'K7,K8,K4,K1,K3',

			'Y5'  => 'Y7,Y4,Y2,W',
			'Y2'  => 'Y7,Y4,Y1,Y5,Y0',
			'Y0'  => 'Y4,Y1,Y2,X',
			'K0'  => 'K2,K4,K1,X',
			'K1'  => 'K2,K4,K6,K0,K3',
			'K3'  => 'K4,K6,K1,E',

			'W'   => 'Y5,R3',
			'X'   => 'Y0,K0,R0,A0',
			'E'   => 'K3,A5',

			'R3'  => 'W,R1,R6,R4',
			'R1'  => 'R3,R0,R6,R4,R2',
			'R0'  => 'X,R1,R4,R2',
			'A0'  => 'X,A2,A1,A4',
			'A2'  => 'A0,A5,A1,A4,A7',
			'A5'  => 'E,A2,A4,A7',

			'R6'  => 'R3,R1,R4,R8,R7',
			'R4'  => 'R3,R1,R0,R6,R2,R8,R7,R5',
			'R2'  => 'R1,R0,R4,R7,R5',
			'A1'  => 'A0,A2,A4,A3,A6',
			'A4'  => 'A0,A2,A5,A1,A7,A3,A6,A8',
			'A7'  => 'A2,A5,A4,A6,A8',

			'R8'  => 'R6,R4,R7,RQ',
			'R7'  => 'R6,R4,R2,R8,R5',
			'R5'  => 'R4,R2,R7,S',
			'A3'  => 'A1,A4,A6,S',
			'A6'  => 'A1,A4,A7,A3,A8',
			'A8'  => 'A4,A7,A6,AQ',

			'RQ'  => 'R8',
			'S'   => 'R5,A3',
			'AQ'  => 'A8',

			'RU'  => 'RQ',
			'AU'  => 'AQ'
		);

	/** static protected property SEA
	 *		Holds the board adjacencies for sea
	 *		Reserve -> Headquarters (*U -> *Q) is only one way
	 *
	 * @var array
	 */
	static protected $SEA = array(
			'YU'  => 'YQ',
			'KU'  => 'KQ',

			'YQ'  => 'L06,L05,Y8',
			'L06' => 'YQ,N,Y8,Y6,Y3',
			'N'   => 'L06,L07,Y3,L01,K5',
			'L07' => 'N,KQ,K5,K7,K8',
			'KQ'  => 'L07,K8,L08',

			'Y8'  => 'YQ,L06,L05,Y6,Y7',
			'Y6'  => 'L06,Y8,Y3',
			'Y3'  => 'L06,N,Y6,L01,Y1',
			'K5'  => 'N,L07,L01,K7,K2',
			'K7'  => 'L07,K5,K8',
			'K8'  => 'L07,KQ,K7,L08,K6',

			'L05' => 'YQ,Y8,Y7,Y5,W',
			'Y7'  => 'L05,Y8,Y5',
			'Y1'  => 'Y3,L01,Y0',
			'L01' => 'N,Y3,K5,Y1,K2,Y0,K0,X',
			'K2'  => 'L01,K5,K0',
			'K6'  => 'K8,L08,K3',
			'L08' => 'KQ,K8,K6,K3,E',

			'Y5'  => 'L05,Y7,Y2,W,L04',
			'Y2'  => 'Y5,Y0,L04',
			'Y0'  => 'Y1,L01,Y2,L04,X',
			'K0'  => 'L01,K2,K1,X,L02',
			'K1'  => 'K0,K3,L02',
			'K3'  => 'K6,L08,K1,L02,E',

			'W'   => 'L05,Y5,L04,L12,R3',
			'L04' => 'Y5,Y2,Y0,W,X,R3,R1,R0',
			'X'   => 'Y0,L01,K0,L04,L02,R0,L03,A0',
			'L02' => 'K0,K1,K3,X,E,A0,A2,A5',
			'E'   => 'K3,L08,L02,A5,L09',

			'R3'  => 'W,L04,L12,R1,R6',
			'R1'  => 'L04,R3,R0',
			'R0'  => 'L04,X,R1,L03,R2',
			'A0'  => 'X,L02,L03,A2,A1',
			'A2'  => 'L02,A0,A5',
			'A5'  => 'L02,E,A2,L09,A7',

			'L12' => 'W,R3,R6,R8,RQ',
			'R6'  => 'L12,R3,R8',
			'R2'  => 'R0,L03,R5',
			'L03' => 'X,R0,A0,R2,A1,R5,A3,S',
			'A1'  => 'L03,A0,A3',
			'A7'  => 'A5,L09,A8',
			'L09' => 'E,A5,A7,A8,AQ',

			'R8'  => 'L12,R6,R7,RQ,L11',
			'R7'  => 'R8,R5,L11',
			'R5'  => 'R2,L03,R7,L11,S',
			'A3'  => 'L03,A1,A6,S,L10',
			'A6'  => 'A3,A8,L10',
			'A8'  => 'A7,L09,A6,L10,AQ',

			'RQ'  => 'L12,R8,L11',
			'L11' => 'R8,R7,R5,RQ,S',
			'S'   => 'R5,L03,A3,L11,L10',
			'L10' => 'A3,A6,A8,S,AQ',
			'AQ'  => 'A8,L09,L10',

			'RU'  => 'RQ',
			'AU'  => 'AQ'
		);


	/** static protected property PIECES
	 *		Holds the master piece data array
	 *
	 * @var array
	 */
	static protected $PIECES = array(
			'I' => array(
				'name'        => 'Infantry',
				'method'      => 'LAND',
				'power'       => '2',
				'max_sectors' => '2',
				'size'        => 'small',
				'exchange'    => 'G',
			),

			'G' => array(
				'name'        => 'Regiment',
				'method'      => 'LAND',
				'power'       => '20',
				'max_sectors' => '2',
				'size'        => 'large',
				'exchange'    => false,
			),

			'T' => array(
				'name'        => 'Tank',
				'method'      => 'LAND',
				'power'       => '3',
				'max_sectors' => '3',
				'size'        => 'small',
				'exchange'    => 'H',
			),

			'H' => array(
				'name'        => 'Heavy Tank',
				'method'      => 'LAND',
				'power'       => '30',
				'max_sectors' => '3',
				'size'        => 'large',
				'exchange'    => false,
			),

			'F' => array(
				'name'        => 'Fighter',
				'method'      => 'AIR',
				'power'       => '5',
				'max_sectors' => '5',
				'size'        => 'small',
				'exchange'    => 'B',
			),

			'B' => array(
				'name'        => 'Bomber',
				'method'      => 'AIR',
				'power'       => '25',
				'max_sectors' => '5',
				'size'        => 'large',
				'exchange'    => false,
			),

			'D' => array(
				'name'        => 'Destroyer',
				'method'      => 'SEA',
				'power'       => '10',
				'max_sectors' => '1',
				'size'        => 'small',
				'exchange'    => 'C',
			),

			'C' => array(
				'name'        => 'Cruiser',
				'method'      => 'SEA',
				'power'       => '50',
				'max_sectors' => '1',
				'size'        => 'large',
				'exchange'    => false,
			),

			'M' => array(
				'name'        => 'Megamissle',
				'method'      => 'MEGA', // the megamissle can go _anywhere_
				'power'       => '0',    // the megamissle has no defensive power
				'max_sectors' => '10',   // the megamissle can go _anywhere_ (max moves to get anywhere = 8)
				'size'        => 'large',
				'exchange'    => false,  // the megamissle cannot be traded for anything else
			),

			'P' => array(
				'name'        => 'Power Unit',
				'power'       => '1',
				'max_sectors' => '0',    // the power unit cannot go anywhere (stays in reserve)
				'exchange'    => true,   // the power unit can be traded for anything
			),

			'V' => array(
				'name'        => 'Flag',
				'power'       => '0',    // the flag has no defensive power
				'max_sectors' => '0',    // the flag cannot go anywhere (stays in headquarters)
				'exchange'    => false,  // the flag cannot be traded for anything
			),
		);


	/** protected property _command
	 *		The current command being parsed
	 *
	 * @var array
	 */
	protected $_command;


	/** protected property _commands
	 *		The current commands listed
	 *
	 * @var array
	 */
	protected $_commands;


	/** protected property _board
	 *		Holds the game board data array
	 *
	 * @var array
	 */
	protected $_board;


	/** protected property _players
	 *		Holds our player's data
	 *		array(
	 *			color => player_id ,
	 *			color => player_id ,
	 *			color => player_id ,
	 *			color => player_id ,
	 *		)
	 *
	 * @var array of player data
	 */
	protected $_players;


	/** protected property _color_player
	 *		A reverse look-up array with player's color code
	 *		as key and player's id as value
	 *
	 * @var array
	 */
	protected $_color_player;


	/** protected property _DEBUG
	 *		Holds the DEBUG state for the class
	 *
	 * @var bool
	 */
	protected $_DEBUG = false;



	/**
	 *		METHODS
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** public function __construct
	 *		Class constructor
	 *		Sets all outside data
	 *
	 * @parqam void
	 * @action instantiates object
	 * @return void
	 */
	public function __construct( )
	{
		if (defined('DEBUG')) {
			$this->_DEBUG = DEBUG;
		}

		g(get_class_methods($this));
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
			throw new MyException(__METHOD__.': Trying to access non-existent property ('.$property.')', 2);
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
			throw new MyException(__METHOD__.': Trying to access non-existent property ('.$property.')', 3);
		}

		if ('_' === $property[0]) {
			throw new MyException(__METHOD__.': Trying to access _private property ('.$property.')', 3);
		}

		$this->$property = $value;
	}


	protected function clear_command( )
	{
		$this->_command = false;
	}


	protected function clean_command($command)
	{
		// remove anything that's not a letter, number, dash, or greater than sign
		$command = preg_replace('/[^->a-z0-9]/i', '', $command);

		// capitalize the command
		$command = strtoupper($command);

		// convert any entries of 'HQ' into just 'Q'
		// (it would be a bad command to otherwise have
		// H and Q next to each other like that)
		$command = str_replace('HQ', 'Q', $command);

		// make sure any sea lanes have two digits
		// if one digit is matched but not two
		if ((0 < preg_match('/L\\d/i', $command)) && (0 == preg_match('/L\\d\\d/i', $command))) {
			// add an empty space to the end so the regex will match everywhere
			$command .= ' ';

			// add the zero in there
			$command = preg_replace('/L(\\d\\D)/i', 'L0$1', $command);
		}

		// trim any spaces that may have added
		return trim($command);
	}


	protected function get_command_data($command)
	{
		$this->_command = $command;
		call($command);

		// save the default data array values
		$data = array( );
		$data[0] = false;

		// check for a command
		if ('' == $this->_command) {
			$this->error = 'No command given';
			$data['error'] = $this->error;

			call($data);
			return $data; // do not continue
		}

		// extract the command bits
		$command_array = explode('-', $this->_command);
		if (3 == count($command_array)) {
			list($piece, $from, $to) = $command_array;
		}
		else { // the command is obviously bad
			$this->error = 'There is a command format error';
			$data['error'] = $this->error;

			call($data);
			return $data; // do not continue
		}

		// search the 'piece' code for the color code
		if (0 == preg_match('/[YKRA]/i', $piece)) {
			$this->error = 'No color code given';
			$data['error'] = $this->error;

			call($data);
			return $data; // do not continue
		}

		// search the 'to' code for the color code (if needed)
		if ((false !== strpos($to, '>')) && (0 == preg_match('/[YKRA]/i', $to))) {
			$this->error = 'No color code given';
			$data['error'] = $this->error;

			call($data);
			return $data; // do not continue
		}

		// clear any error
		$this->error = false;

		// run the tests and save each error separately
		$trade = $this->is_trade_possible($piece, $to);
		$trade_error = $this->error;

		$move  = $this->is_move_possible($from, $to, $piece);
		$move_error  = $this->error;

		if (false != $trade) {
			call('TRADE PASSED');

			$data[0] = true;
			$data['type'] = 'trade';
			$data['sector'] = $from;
			$data['from'] = explode(',', $piece);
			$data['to'] = substr($to, 1); // remove the > symbol
		}
		elseif (false != $move) {
			call('MOVE PASSED');

			$data[0] = true;
			$data['type'] = 'move';
			$data['piece'] = $piece;
			$data['from'] = $from;
			$data['to'] = $to;
		}
		else {
			call('TESTS FAILED');

			// save the proper error
			$this->error = ('This command is not a trade' == $trade_error) ? $move_error : $trade_error;
			$data['error'] = $this->error;
		}

		// return the data array
		call($data);
		return $data;
	}


	protected function set_command($command, $color)
	{
		if ( ! in_array($color, $this->_colors)) {
			throw new MyException(__METHOD__.': Invalid color given ('.$color.')');
		}

		try {
			// clean the command
			$command = $this->clean_command($command);

			// get the command data
			$command = $this->get_command_data($command);
		}
		catch (MyException $e) {
			throw $e;
		}

		// save the data to the command array
		$this->_commands[$color][] = $command;
		$this->_command = $command;
	}


	public function get_command( )
	{
		return $this->_command;
	}


	public function clear_commands( )
	{
		$this->_commands = false;
	}


	public function set_commands($command_lists)
	{
		$this->clear_commands( );

		foreach ($command_lists as $color => $command_list) {
			$command_list = array_trim($command_list);

			foreach ($command_list as $command) {
				if ( ! empty($command)) {
					$this->set_command($command, $color);
				}
			}
		}
	}


	public function get_commands( )
	{
		return $this->_commands;
	}



	public function validate_command($data)
	{
		// test if the command is valid
		if (false == $data[0]) {
			$this->error = 'The command is not valid';
			return false;
		}

		// do the move
		if ('move' == $data['type']) {
			call('--MOVE--');

			// try to find the piece in the from sector
			$occupants = $this->_board[$data['from']];
			if (false === $this->find_piece($data['piece'], $occupants)) {
				$this->error = 'The piece was not found in the sector';
				return false;
			}

			// move the piece
			$this->move_piece($data['piece'], $data['from'], $data['to']);
			call('PIECE MOVED');

			return true;
		}

		// do the trade
		if ('trade' == $data['type']) {
			call('--TRADE--');

			// try to find the pieces in the sector
			// get the array of pieces in the sector
			$occupants = $this->_board[$data['sector']]; // get the array data
			call($occupants);

			// get the array of pieces in to be traded (already an array)
			$traders = $data['from'];
			call($traders);

			// check for Z
			$traders_string = implode(',', $traders); // convert to string for searching ease
			if (false !== strpos($traders_string, 'Z')) {
				// make sure Z is the only trade in 'piece' entered
				if (1 < count($traders)) {
					$this->error = 'Other trade entered with Z shorthand';
					return false;
				}

				// the 'Z' piece is used exclusively
				$trader = $traders[0];

				// check the sector for enough pieces
				$total_points = 0;
				foreach ($occupants as $occupant) {
					if ($occupant['color'] == $trader['color']) {
						$total_points += $occupant['num'] * self::$PIECES[$occupant['type']]['power'];
					}
				}

				if (100 > $total_points) {
					$this->error = 'At least 100 points must be traded in for a Megamissle';
					return false;
				}

				// create an array of items with the point value the key
				$power_array = array( );
				foreach ($occupants as $occupant) {
					// get the occupant data
					$occupant = $this->get_piece_data($occupant);

					if ($occupant['color'] == $trader['color']) {
						$power_array[self::$PIECES[$occupant['type']]['power']] = array(
							$occupant['num'], $occupant['type']
						);
					}
				}
				krsort($power_array); // sort the array based on the power
				call($power_array);

				// select the proper items for the trade
				$difference = $total_points - 100;
				call($difference);

				// at this point the entire contents of the square are being traded in.
				// time to remove pieces as needed to get the trade value down to 100

				foreach ($power_array as $power => $item) {
					// run through and remove the largest possible items from the trade array
					while ($power <= $difference) {
						// if there are enough of these pieces left
						if (0 < $power_array[$power][0]) {
							--$power_array[$power][0]; // decrease the number of items used
							$difference -= $power; // decrease the power difference
						}
						else { // there aren't any of this kind of piece remaining to remove
							continue 2; // return to the foreach loop and skip to the next piece
						}
					}
				}
				call($difference);
				call($power_array);

				// create the 'from' array for the trade from the edited power_array
				$col = $trader['color']; // get the color for the new array
				$from = array( );
				foreach ($power_array as $piece) {
					if (0 != $piece[0]) {
						$num = (1 < $piece[0]) ? $piece[0] : '';
						$from[] = $num . $col . $piece[1];
					}
				}

				// make the trade
				$this->trade_pieces($data['sector'], $from, $data['to']);
				call('PIECE TRADED');

				return true;
			}

			// parse through each and check them against each other
			foreach ($traders as $trader) {
				// get the trader data
				$trader = $this->get_piece_data($trader);
				call($trader);

				$found = false; // init the found flag
				foreach ($occupants as $occupant) {
					call($occupant);
					// compare them
					if (($trader['color'] == $occupant['color']) && ($trader['type'] == $occupant['type'])) {
						call($trader['num']);
						call($occupant['num']);
						// make sure there are enough of them
						if ($trader['num'] > $occupant['num']) {
							$this->error = 'Not enough pieces to trade';
							return false;
						}
						else { // there are enough
							// let the script know they were found
							$found = true;
						}
					}
				}

				// if they haven't been found yet
				if ( ! $found) {
					$this->error = 'The pieces were not found in the sector';
					return false;
				}
			}
			call($data);

			// make the trade
			$this->trade_pieces($data['sector'], $data['from'], $data['to']);
			call('PIECE TRADED');

			return true;
		}
	}


	protected function get_move_count($from, $to, $method = 'LAND', $full = false)
	{
		call(__METHOD__);
		call($from);
		call($to);
		call($method);
		call($full);

		// if the pieces are moving to the same sector, the player is emphysema... just return 0
		if ($from === $to) {
			call('PLAYER = EMPHYSEMA');
			return 0;
		}

		// init the checked array
		// this array will hold the values for the squares already checked
		$checked = array( );

		// init the next array to start with the 'from' sector
		$next = array($from);

		if ('AIR' == $method) {
			$method = 'LAND'; // use the land adjacencies
		}

		// get the max number of moves, LAND = 5, SEA = 1
		$max = ('LAND' == $method) ? 5 : 1;

		// if the moves need to be counted, ignore the method and just count them
		$max = (true === $full) ? 9 : $max; // the maximum moves to anywhere on the board is 8 (+1 just to be sure)
		call($max);

		// run through and check the sectors
		for ($count = 1; $count <= $max; ++$count) {
			call($count . ' ---');

			// collect the sectors adjacent to the sectors in the $next array
			$all = array( ); // reset or init, whichever
			foreach ($next as $current) {
				// if this sector has been checked already, skip it
				if (in_array($current, $checked)) {
					continue;
				}

				// test for missing sectors
				// this may happen when a tank tries to go in the water, etc.
				if ( ! isset(self::${$method}[$current])) {
					// no need for an error, it is generated by the is_move_possible function
					return false;
				}

				// convert the adjacency string to an array for easier handling
				$new = explode(',', self::${$method}[$current]);
				$all = array_merge($all, $new);
				$all = array_unique($all);
			}

			// check the whole thing for the 'to' sector
			if (in_array($to, $all)) {
				// if it has been found, return the move count
				call($count);
				return $count;
			}

			// if it wasn't found...
			// combine the previous $next values in with the $checked values
			$checked = array_merge($checked, $next);
			$checked = array_unique($checked); // so it doesn't get very big

			// save the new values for the next go around
			$next = $all;
		}

		// if this is hit, the piece went too far
		return false;
	}



	protected function is_move_possible($from, $to, $piece_code)
	{
		call(__METHOD__);
		call($from);
		call($to);
		call($piece_code);

		// test the command for an exchange
		if (false !== strpos($to, '>')) {
			$this->error = 'This command is not a move';
			return false;
		}

		// remove the color code
		$piece_code = preg_replace('/[YKRA]/i' ,'', $piece_code);

		// test if the piece exists (weed out Power units here as well)
		if (( ! isset(self::$PIECES[$piece_code])) || ('P' == $piece_code)) {
			$this->error = 'No such movable piece exists';
			return false;
		}

		// if the megamissle is moving, just do it
		if ('M' == $piece_code) {
			return true;
		}

		// save the piece array for easier handling
		$piece = self::$PIECES[$piece_code];

		// get the total number of moves
		$count = $this->get_move_count($from, $to, $piece['method'], true);

		// test if it can even be done at all
		// this weeds out ships on land, tanks in the water, non-commands, etc.
		if (false === $count) {
			$this->error = 'There is a command format error';
			return false; // not possible
		}

		if (0 == $count) {
			$this->error = 'A move cannot be to the same sector';
			return false;
		}

		// test if the piece can make it that far
		if ($piece['max_sectors'] < $count) {
			$this->error = 'That piece cannot travel that far';
			return false;
		}

		// if the piece is on land
		if ('LAND' == $piece['method']) {
			// make sure the piece is not moving through an island
			// if the codes prefixes are different
			if ($from[0] != $to[0]) {
				// make an island array for comparison
				$islands = array('N','S','E','W','X');

				// if it's not an island, it's no good
				if ( ! in_array($from, $islands) && ! in_array($to, $islands)) {
					$this->error = 'Land based pieces must stop on an island';
					return false;
				}
			}
		}

		// if this is hit, it's all good
		return true;
	}



	protected function is_trade_possible($from, $to)
	{
		call(__METHOD__);
		call($from);
		call($to);

		// test the command for an exchange
		if (false === strpos($to, '>')) {
			$this->error = 'This command is not a trade';
			return false;
		}

		// remove the color codes (only the possibility is being tested)
		$from = preg_replace('/[YKRA]/i' ,'', $from);
		$to   = preg_replace('/[YKRA]/i' ,'', $to);

		// get out the trade-to piece
		$to_piece = substr($to, 1, 1);

		// test for Mega trade
		if ('M' == $to_piece) {
			// test for Z code
			if ('Z' == $from) {
				// this will never fail
				// it may fail later, upon inspection of the sector
				// but not here
			}
			else { // it's a multi-piece trade
				// get the various piece codes out
				$pieces = explode(',', $from);

				// count the total points for all the pieces
				$total = 0;
				foreach($pieces as $piece) {
					$number = substr($from, 0, 1);
					$piece  = substr($from, 1, 1);

					$total += $number * self::$PIECES[$piece]['power'];
				}

				if (100 > $total) {
					$this->error = 'At least 100 points must be traded in for a Megamissle';
					return false;
				}
			}
		}
		else // its a normal piece trade
		{
			// test for multi-piece trades
			$pieces = explode(',', $from);
			if (1 < count($pieces)) {
				$this->error = 'Only one type of piece can be traded per command';
				return false;
			}

			if ('Z' == $from) {
				$this->error = 'Z code is only valid for Megamissle trades';
				return false;
			}

			// there should only be one kind of piece
			// and only one type of piece swapped per command
			// so if the command is bad, it will fail here
			$number = substr($from, 0, 1);
			call($number);
			$piece  = substr($from, 1, 1);
			call($piece);

			// test the piece for possibility of trade
			if (( ! isset(self::$PIECES[$piece])) || (false === self::$PIECES[$piece]['exchange'])) {
				$this->error = 'These pieces cannot be traded';
				return false;
			}

			// test the piece for correct trade
			if (('P' != $piece) && (self::$PIECES[$piece]['exchange'] != $to_piece)) {
				$this->error = 'Wrong type of piece traded';
				return false;
			}

			if (('P' != $piece) && (3 != $number)) {
				$this->error = 'Wrong number of trade-in pieces';
				return false;
			}

			// run tests for power unit trades
			if ('P' == $piece) {
				$reqd = self::$PIECES[$to_piece]['power'];
				$have = $number;

				if ($have != $reqd) {
					$this->error = 'Wrong number of trade-in pieces';
					return false;
				}
			}
		}

		// if this is hit, it's all good
		return true;
	}



	//*/
	//////////////////////////////////////////////////////////
	//
	//   Piece Functions
	//
	//////////////////////////////////////////////////////////
	//*



	protected function find_piece($piece, $sector_array)
	{
		// break apart the piece into its data components
		$piece = $this->get_piece_data($piece);

		// if the sector is empty
		if ( ! is_array($sector_array)) {
			return false;
		}

		// parse through the sector array and look at each piece
		foreach ($sector_array as $key => $piece_array) {
			// if it's the right type
			if (strtoupper($piece_array['type']) == strtoupper($piece['type'])) {
				// if it's the right color
				if (strtoupper($piece_array['color']) == strtoupper($piece['color'])) {
					// if there are some left
					if (0 < $piece_array['num']) {
						// return the array key
						return $key;
					}
				}
			}
		}

		// if it hasn't been found yet, it won't be found
		return false;
	}



	protected function get_piece_data($item) // array or single
	{
		call(__METHOD__);
		call($item);

		if ('' == $item) {
			return false;
		}

		if (is_array($item)) { // recurse through an array of pieces
			foreach ($item as $key => $thing) {
				$item[$key] = $this->get_piece_data($thing);
			}

			return $item;
		}
		else {
			$piece = array( );

			// get the number of this type of piece
			$piece['num'] = (preg_match('/^\\d+/', $item, $match)) ? $match[0] : 1;
			call($piece['num']);

			// get the color of this piece
			if (preg_match('/[YKRA]/i', $item, $match)) {
				$piece['color'] = $match[0];
				call($match);
			}
			else {
				call('NO COLOR FOUND');
				return false;
			}

			// get the type of this piece
			if (preg_match('/[IGTHFBDCPMV]/i', $item, $match)) {
				$piece['type'] = $match[0];
				call($match);
			}
			else {
				call('NO PIECE FOUND');
				return false;
			}

			return $piece;
		}
	}


	protected function add_color_code($command, $color)
	{
		call(__METHOD__);
		call($command);
		call($color);

		if (empty($command) || empty($color)) {
			return false;
		}

		// clean the command
		$command = $this->clean_command($command);

		// get the bits of the command
		$command_array = explode('-', $command);
		if (3 == count($command_array)) {
			list($piece, $from, $to) = $command_array;
		}
		else {
			$this->error = 'There is a command format error';
			return $command; // just give it back so that it stays in the command list for future edits
		}

		// break the piece code into the various pieces
		$pieces = explode(',', $piece);
		call($pieces);

		$codes = array( );
		foreach ($pieces as $code) {
			// search for a color code
			if (0 == preg_match('/[YKRA]/i', $code)) {
				call('-- REPLACING --');
				call(preg_match('/(?=[IGTHFBDCPMZ])/i', $code));
				// place the color code before any piece codes
				$code = preg_replace('/(?=[IGTHFBDCPMZ])/i', $color, $code);
			}

			// save the new code to the array
			$codes[] = $code;
		}

		// test the command for a trade and colorize the to piece
		if (false !== strpos($to, '>')) {
			// search for a color code
			if (0 == preg_match('/[YKRA]/i', $to)) {
				call('-- REPLACING --');
				call(preg_match('/(?=[IGTHFBDCPMZ])/i', $code));
				// place the color code before any piece codes
				$to = preg_replace('/(?=[IGTHFBDCPMZ])/i', $color, $to);
			}
		}

		// recombine the new piece codes
		$piece = implode(',', $codes);

		// recombine the command
		$command = $piece . '-' . $from . '-' . $to;

		call($command);
		return $command;
	}



	// TODO: edit for mercenary forces
	protected function is_color_valid($data, $colors)
	{
		// turn the color list into a regex
		$color_regex = '/[' . str_replace(',', '', $colors) . ']/i';

		// now search the full color regex for the ones included and remove them
		$other_colors = preg_replace($color_regex, '', 'YKRA');

		if ('move' == $data['type'])
		{
			// now search the piece for the 'other' colors
			if (preg_match("/[$other_colors]/i", $data['piece']))
			{
				$this->error = 'Not the correct piece color';
				return false;
			}
			else
			{
				return true;
			}
		}
		elseif ('trade' == $data['type'])
		{
			// search through each of the from pieces given in the array
			foreach ($data['from'] as $from)
			{
				// now search the from piece for the 'other' colors
				if (preg_match("/[$other_colors]/i", $from))
				{
					$this->error = 'Not the correct piece color';
					return false;
				}
			}

			// now search the to piece for the 'other' colors
			if (preg_match("/[$other_colors]/i", $data['to']))
			{
				$this->error = 'Not the correct piece color';
				return false;
			}
			else
			{
				return true;
			}
		}
		else // i have no idea, just output false
		{
			return false;
		}
	}


	protected function remove_piece($piece_code, $sector, $unmoved = true)
	{
		call(__METHOD__);
		call($piece_code);
		call($sector);
		call($unmoved);

		call($this->_board[$sector]);

		// find the piece to be removed
		foreach ($this->_board[$sector] as $key => $piece) {
			// if it was found
			if ($piece_code == $piece['color'] . $piece['type']) {
				// if there is a piece left to be moved
				if (0 < $this->_board[$sector][$key]['num']) {
					// if only non-moved pieces are important
					if ($unmoved) {
						// if there's one that hasn't been moved yet
						if ($piece['num'] > $piece['moved']) {
							// subtract one from the count
							call('--UNMOVED PIECE FOUND--');
							--$this->_board[$sector][$key]['num'];
						}
					}
					else { // moved doesn't matter
						// subtract one from the count
						call('--MOVED (OR DON\'T CARE) PIECE FOUND--');
						--$this->_board[$sector][$key]['num'];

						// if moved is not yet zero
						if (0 < $this->_board[$sector][$key]['moved']) {
							// subtract one from the moved count
							--$this->_board[$sector][$key]['moved'];
						}
					}
				}
			}
		}
		call($this->_board[$sector]);
	}


	protected function place_piece($piece_code, $sector, $moved = true)
	{
		cll("<br/>place_piece($piece_code, $sector, $moved)<div style=\"border:1px dotted green;margin: 0 5px 0 2em;\">");

		// get the sector's contents
		$contents = $this->_board[$sector];
		if ((0 == count((array) $contents)) || (false == $contents)) {
			$contents = array( );
		}
		call($contents);

		// parse through that array and break it in separate piece types
		$not_found = true;
		foreach ($contents as $key => $piece) {
			// if it was found
			if ($piece_code == $piece['color'] . $piece['type']) {
				call('--PIECE ADDED--');

				// add one to the count
				++$contents[$key]['num'];

				// if one needs to be added to the moved count
				if ($moved) {
					call('--MOVE ADDED--');
					++$contents[$key]['moved'];
				}

				// set the error var
				$not_found = false;
			}
		}

		// add the piece if needed
		if ($not_found) {
			// get the piece data
			$piece = $this->get_piece_data($piece_code);
			call($piece);

			$contents[] = array(
				'num' => 1,
				'color' => strtoupper($piece['color']),
				'type' => strtoupper($piece['type']),
				'owner' => $this->_players[$piece['color']],
				'power' => self::$PIECES[$piece['type']]['power'],
				'moves' => self::$PIECES[$piece['type']]['max_sectors'],
				'moved' => ($moved ? 1 : 0)
			);
			call('--NEW PIECE PLACED--');
			if ($moved) { call('--MOVE ADDED--'); }
		}
		call($contents);

		// save it to the board
		$this->_board[$sector] = $contents;
	}



	protected function move_piece($piece_code, $from_sector, $to_sector)
	{
		// if a piece is moving , it cannot be moved again
		$moved = true;

		$this->remove_piece($piece_code, $from_sector, $moved);
		$this->place_piece($piece_code, $to_sector, $moved);
	}



	protected function trade_pieces($sector, $from_pieces, $to_piece)
	{
		// when trading pieces, it doesn't matter if the piece
		// had already been moved prior to the trade
		$moved = false;

		// if there are many pieces to trade (Megamissle)
		if (is_array($from_pieces))
		{
			foreach ($from_pieces as $piece)
			{
				$piece = $this->get_piece_data($piece);

				for ($i = 0; $i < $piece['num']; ++$i)
				{
					$this->remove_piece($piece['color'] . $piece['type'], $sector, $moved);
				}
			}
		}
		else // there is only one type of piece to trade
		{
			$this->remove_piece($from_pieces, $sector, $moved);
		}

		// when trading pieces, the new piece is considered 'unmoved'
		// so it may be moved later in the same command set
		$this->place_piece($to_piece, $sector, $moved);
	}



	//*/
	//////////////////////////////////////////////////////////
	//
	//   Board Functions
	//
	//////////////////////////////////////////////////////////
	//*


	/** public function clear_board
	 *		Clears the board by replacing
	 *		it with an empty board
	 *
	 * @param void
	 * @action clears the board
	 * @return void
	 */
	public function clear_board( )
	{
		$this->_board = array_keys(self::$SECTORS);
	}


	/** public function set_board
	 *		Sets the board with the
	 *		expanded version given
	 *
	 * @param array board data
	 * @action sets the _board var
	 * @return void
	 */
	public function set_board($board)
	{
		// TODO: validate board ?

		if (is_string($board)) {
			$board = $this->parseFEN($board);
		}

		$this->_board = $board;
	}


	/** public function get_board
	 *		Returns the expanded version of the
	 *		current board
	 *
	 *@param void
	 *@return array board data
	 */
	public function get_board( )
	{
		return $this->_board;
	}


	/** public function clean_board
	 *		Removes any zero count pieces from the
	 *		object board by folding it, then expanding it
	 *
	 * @param void
	 * @action cleans out absent pieces
	 * @return void
	 */
	function clean_board( )
	{
		call(__METHOD__);

		$FEN = $this->createFEN( );
		$this->parseFEN($FEN);
	}


	/** public function simplify_board
	 *		simplifies the board by removing empty sectors
	 *		and empty piece arrays
	 *
	 * @param void
	 * @return array simplified board
	 */
	function simplify_board( )
	{
		call(__METHOD__);

		$board = $this->_board;
		foreach ($board as $sector => $contents) {
			// if there is nothing in this sector
			if (false == $contents) {
				// remove it from the array
				unset($board[$sector]);
			}
			else { // there is something in this sector
				// look for zero count pieces
				foreach ($contents as $key => $piece) {
					// if there are no pieces
					if (0 >= $piece['num']) {
						// remove it from the array
						unset($board[$sector][$key]);
					}
				}
			}
		}
		call($board);

		return $board;
	}


	/** public function parseFEN
	 *		Parses the given FEN into full board data
	 *
	 * @param string FEN (or xFEN)
	 * @action creates _board from FEN data
	 * @return array board data
	 */
	public function parseFEN($FEN)
	{
		call(__METHOD__);
		call($FEN);

		$FEN = self::expandFEN($FEN);
		$board = explode('/', strtoupper($FEN));
		$board = array_combine(self::$SECTORS, $board);
		call($board);

		// now run through each sector and parse out each piece array
		foreach ($board as $sector => $contents) {
			if ('' == $contents) {
				continue;
			}

			$pieces = explode(',', $contents);

			// remove the contents from the board
			$board[$sector] = null;

			foreach ($pieces as $piece) {
				$new = array( );
				call($piece);

				// get the number of the pieces
				if (preg_match('/\\d+/i', $piece, $match)) {
					$new['num'] = (int) $match[0];
				}
				else {
					$new['num'] = 1;
				}
				call($new);

				// get the color of the pieces
				if (preg_match('/[YKRA]/i', $piece, $match)) {
					$new['color'] = $match[0];
				}
				else {
					throw new MyException(__METHOD__.': Invalid color code found ('.$piece.')');
					return false;
				}
				call($new);

				// get the type of the pieces
				if (preg_match('/[IGTHFBDCPMV]/i', $piece, $match)) {
					$new['type'] = $match[0];
				}
				else {
					throw new MyException(__METHOD__.': Invalid piece code found ('.$piece.')');
					return false;
				}
				call($new);

				// get the owner of the pieces
				if ( ! empty($this->_players[$new['color']])) {
					$new['owner'] = $this->_players[$new['color']];
				}
				else {
					throw new MyException(__METHOD__.': Invalid owner for this color ('.$new['color'].')');
					return false;
				}
				call($new);

				// get the value of the piece
				if (isset(self::$PIECES[$new['type']]['power'])) {
					$new['power'] = (int) self::$PIECES[$new['type']]['power'];
				}
				else {
					$new['power'] = 0;
				}
				call($new);

				// add the moves and moved values
				$new['moves'] = (int) self::$PIECES[$new['type']]['max_sectors'];
				$new['moved'] = 0;

				// save the new array into the sector
				$board[$sector][] = $new;
			}
		}

		$this->_board = $board;
		return $board;
	}



	/** public function createFEN
	 *		Compacts the expanded board given
	 *		and returns the FEN
	 *
	 * @param array [optional]
	 * @return string
	 */
	public function createFEN($board = null)
	{
		call(__METHOD__);
		call($board);

		if ( ! $board) {
			$board = $this->_board;
		}

		// TODO: validate board ?

		// parse through each sector and fold it up
		foreach ($board as $sector => $contents) {
			if ('' == $contents) {
				continue;
			}

			// remove the contents from the board
			$board[$sector] = '';

			// parse through each piece and fold it up
			$new_contents = array( );
			foreach ($contents as $piece) {
				$new = '';
				call($piece);

				if (0 >= $piece['num']) {
					call('skipped');
					continue;
				}
				elseif (1 == $piece['num']) {
					$new .= $piece['color'] . $piece['type'];
				}
				else { // there is more than one
					$new .= $piece['num'] . $piece['color'] . $piece['type'];
				}
				call($new);

				$new_contents[] = $new;
				call($new_contents);
			}

			// insert the new contents into the sector
			$board[$sector] = implode(',', $new_contents);
		}

		$xFEN = implode('/', $board);

		$FEN = self::packFEN($xFEN);

		return strtoupper($FEN);
	}


	/** static public function expandFEN
	 *		Expands the given FEN
	 *
	 * @param string FEN
	 * @return string xFEN
	 */
	static public function expandFEN($FEN)
	{
		call(__METHOD__);
		call($FEN);

		// TODO: validate FEN ?

		// uncompact slashes
		$xFEN = preg_replace_callback('%\[(\d+)\]%', create_function('$matches', 'return str_repeat(\'/\', $matches[1]);'), $FEN);
		call($xFEN);

		return $xFEN;
	}


	/** static public function packFEN
	 *		Packs the given xFEN
	 *
	 * @param string xFEN
	 * @return string FEN
	 */
	static public function packFEN($xFEN)
	{
		call(__METHOD__);
		call($xFEN);

		// TODO: validate xFEN ?

		// compact slashes ("[3]" is 3 characters, so it doesn't save any space until 4 slashes)
		$FEN = preg_replace_callback('%/{4,}%', create_function('$matches', 'return \'[\'.strlen($matches[0]).\']\';'), $xFEN);
		call($FEN);

		return $FEN;
	}



	//*/
	//////////////////////////////////////////////////////////
	//
	//   Gameplay Functions
	//
	//////////////////////////////////////////////////////////
	//*


	public function get_players( )
	{
		return $this->_players;
	}


	public function get_player($color)
	{
		if (empty($this->_players[$color])) {
			return false;
		}

		return $this->_players[$color];
	}


	public function set_players($players)
	{
		foreach ($players as & $player) { // mind the reference
			$player = (int) $player;
		}
		unset($player); // kill the reference

		$this->_players = $players;
	}


	public function add_player($color, $player_id)
	{
		$this->_players[$color] = (int) $player_id;
	}


	public function remove_player($color)
	{
		$this->_players[$color] = false;
	}


	public function do_mega_kills( )
	{
		// get a simplified board
		$board = $this->simplify_board( );

		// search through the board
		foreach ($board as $sector => $contents) {
			foreach ($contents as $piece) {
				if (('M' == $piece['type']) && (0 < $piece['moved'])) {
					// if it's destroying a headquarters
					if (false !== strpos($sector, 'Q')) {
						// get the color of the headquarters
						$color = substr($sector, 0, 1);

						// if this color is still alive
						if ($this->is_alive($color)) {
							// destroy the whole sector
							$this->_board[$sector] = '';

							// replace the flag
							$this->place_piece($color . 'Q', $color . 'V', false);
						}
						else { // this color is not still alive
							// destroy the whole sector
							$this->_board[$sector] = '';
						}
					}
					else { // it's not destroying a headquarters
						// destroy the whole sector
						$this->_board[$sector] = '';
					}
				}
			}
		}
	}



	protected function clear_conflicts( )
	{
		$this->conflicts = false;
	}



	protected function get_conflicts( )
	{
		// get a simplified board
		$board = $this->simplify_board( );

		// init the bounce flag
		$has_bounce = false;

		// parse through the board and search for conflicts
		$conflicts = array( ); // init a blank array
		foreach ($board as $sector => $contents) {
			// get the players who are occupying this sector
			$occupants = array( ); // init the array
			foreach ($contents as $piece) {
				// if there is already an entry for this player
				if (isset($occupants[$piece['owner']])) {
					// if there is already an entry for this color
					if (isset($occupants[$piece['owner']][$piece['color']])) {
						// add to it
						$occupants[$piece['owner']][$piece['color']] += ($piece['num'] * $piece['power']);

						// add to the total as well
						$occupants[$piece['owner']]['total'] += ($piece['num'] * $piece['power']);
					}
					else { // there isn't an entry for this color yet
						// create one
						$occupants[$piece['owner']][$piece['color']] = ($piece['num'] * $piece['power']);

						// add to the total as well
						$occupants[$piece['owner']]['total'] += ($piece['num'] * $piece['power']);
					}
				}
				else { // there isn't an entry for this player yet
					// create one
					$occupants[$piece['owner']][$piece['color']] = ($piece['num'] * $piece['power']);

					// create the total as well
					$occupants[$piece['owner']]['total'] = ($piece['num'] * $piece['power']);
				}
			}

			// now that the players have been found, look for conflicts
			$bounced = $stayed = $winner = $losers = false; // init the vars
			if (1 < count($occupants)) {
				$max = 0; // look for a winner
				foreach ($occupants as $stats) {
					// set a new max if needed
					$max = ($stats['total'] > $max) ? $stats['total'] : $max;
				}

				// now that the highest power is known
				// find out who got bounced
				$num_high = 0;
				foreach ($occupants as $stats) {
					// find out how many high scores there were
					$num_high += ($stats['total'] == $max) ? 1 : 0;
				}

				// now that the number of high scores is known
				// find out who they belong to
				if (1 < $num_high) {
					foreach ($occupants as $player => $stats) {
						if ($stats['total'] == $max) {
							$has_bounce = true;
							$bounced[] = $player;
						}
					}

					// if there are four players in here
					// the lower players may get bounced as well
					if (4 == count($occupants)) {
						$low_max = 0; // look for a winner
						foreach ($occupants as $player => $stats) {
							if ( ! in_array($player, $bounced)) {
								// set a new low max if needed
								$low_max = ($stats['total'] > $low_max) ? $stats['total'] : $low_max;
							}
						}

						// now that the highest low power is known
						// find out who got bounced
						$num_low = 0;
						foreach ($occupants as $player => $stats) {
							if ( ! in_array($player, $bounced)) {
								// find out how many high lower scores there were
								$num_low += ($stats['total'] == $low_max) ? 1 : 0;
							}
						}

						// now that the number of high lower scores there were is known
						// find out who they belong to
						if (1 < $num_low) {
							foreach ($occupants as $player => $stats) {
								// if this is hit, everybody got bounced
								$bounced[] = $player;
							}

							$bounced = array_unique($bounced);
						}
					}
				}

				// figure out who stayed
				// make sure there is an array to test
				$bounced_test = (is_array($bounced)) ? $bounced : array( );
				foreach ($occupants as $player => $stats) {
					// if they weren't bounced
					if ( ! in_array($player, $bounced_test)) {
						// they must have stayed
						$stayed[] = $player;
					}
				}

				// if some did stay
				if (false !== $stayed) {
					// there is more looking to do
					// find the new max
					$max = 0; // look for a winner
					foreach ($occupants as $player => $stats) {
						if (in_array($player, $stayed)) {
							// set a new max
							$max = ($stats['total'] > $max) ? $stats['total'] : $max;
						}
					}

					// find the winners and losers
					foreach ($occupants as $player => $stats) {
						// if the player stayed
						if (in_array($player, $stayed)) {
							// and their points match the max
							if ($stats['total'] == $max) {
								// there is only one winner
								$winner = $player;
							}
							else { // their points don't match the max
								// add them to the losers array
								$losers[] = $player;
							}
						}
					}
				}

				// save all info to the conflicts array as strings (or false)
				$conflicts[$sector] = $occupants;
				$conflicts[$sector]['bounced'] = (is_array($bounced)) ? implode(',', $bounced) : false;
				$conflicts[$sector]['stayed']  = (is_array($stayed))  ? implode(',', $stayed)  : false;
				$conflicts[$sector]['losers']  = (is_array($losers))  ? implode(',', $losers)  : false;
				$conflicts[$sector]['winner']  = $winner;
			}
		}

		// now save the conflicts array to the object
		$this->conflicts = $conflicts;
		call($conflicts);

		// and return the bounce flag to the script
		return $has_bounce;
	}



	protected function do_bounce( )
	{
		cll("<br/>do_bounce( )<div style=\"border:2px solid #A0A;margin: 0 5px 0 2em;\">");

		// parse through the conflict array and find the bounces
		foreach ($this->conflicts as $sector => $conflict) {
			// if this conflict has a bounce
			if (false !== $conflict['bounced']) {
				call($sector);
				call($this->_board[$sector]);
				call($conflict);
				call($this->commands);

				// find the colors for the players involved
				$bounced_players = explode(',', $conflict['bounced']);
				foreach ($bounced_players as $player) {
					$players[$player] = array_keys($this->_players, $player);
				}
				call($bounced_players);
				call($players);

				// parse through each bounced player's colors
				foreach ($players as $player => $colors) {
					// parse through each color
					foreach ($colors as $color) {
						call('--NEW COLOR--');
						call($color);
						// parse through the commands and undo any trades that were done IN this sector
						foreach ($this->commands[$color] as $command) {
							call($command);
							// if one was found
							if (('trade' == $command['type']) && ($sector == $command['sector'])) {
								call('--UNTRADING PIECE--');
								// remove the traded piece
								$this->remove_piece($command['to'], $sector, false); // if it was moved is unimportant

								// parse through the from pieces and place them all
								foreach ($command['from'] as $piece) {
									// get the piece data
									$data = $this->get_piece_data($piece);
									call($piece);
									call($data);

									// replace all the pieces
									for ($i = 0; $i < $data['num']; ++$i) {
										$this->place_piece($data['color'] . $data['type'], $command['sector'], false); // if it was moved here, it wasn't moved there
									}
								}
							}
						}

						// parse through the commands and bounced any moves that were made to this sector
						foreach ($this->commands[$color] as $command) {
							call($command);
							// if one was found
							if (('move' == $command['type']) && ($sector == $command['to'])) {
								call('--BOUNCING PIECE--');
								// move it back
								$this->remove_piece($command['piece'], $command['to'], false); // move the moved ones
								$this->place_piece($command['piece'], $command['from'], false); // put it back as not moved
							}
						}
					}
				}
			}
		}
	}



	protected function resolve_conflicts( )
	{
		cll("<br/>resolve_conflicts( )<div style=\"border:2px solid #0A0;margin: 0 5px 0 2em;\">");

		// parse through the conflicts array and award pieces
		foreach ($this->conflicts as $sector => $conflict) {
			call($sector);
			call($conflict);

			// if there are no players left, skip it
			if ('' == $conflict['stayed']) {
				call('--NO PIECES REMAIN--');
				continue;
			}

			// now award the pieces to the winner
			// first find out how many colors the winner has in this sector
			$winner = $conflict['winner'];
			call('winner = ' . $winner);
			$win_color = $prev_points = false;

			// parse through each and compare
			foreach($conflict[$winner] as $color_code => $points) {
				// if it's not the total
				if ('total' != $color_code) {
					// if a color points hasn't been seen yet, or this one is larger
					if (false === $win_color || $prev_points < $points) {
						$win_color = $color_code;
						$prev_points = $points;
					}
					elseif ($prev_points == $points) { // there is a tie
						// make the winner choose a color
						$this->awards[$sector] = $winner;
					}
				}
			}
			call($win_color);

			// if there is only one color
			if ( ! isset($this->awards[$sector])) {
				call('--ONLY ONE WINNER COLOR--');
				// parse through the pieces in this sector
				foreach ($this->_board[$sector] as $piece) {
					call($piece);
					// if it's somebody else's piece (and not the flag)
					if (($piece['owner'] != $winner) && ('V' != $piece['type'])) {
						call('--NOT WINNER--');
						// give them to the winner (put in reserve)
						for ($i = 0; $i < $piece['num']; ++$i) {
							$this->remove_piece($piece['color'] . $piece['type'], $sector, false);
							$this->place_piece($win_color . $piece['type'], $win_color . 'U', false);
						}
					}
				}
			}
		}
	}



	protected function clear_flag_captures( )
	{
		$this->flagCaptures = false;
		$this->miniFlag = false;
	}



	protected function get_flag_captures( )
	{
		cll("<br/>get_flag_captures( )<div style=\"border:2px solid #00A;margin: 0 5px 0 2em;\">");

		$captures = $mini = false;
		$colors   = array_keys($this->_players);

		foreach ($colors as $color) {
			if (is_array($this->_board[$color . 'Q'])) {
				$occupants = false;
				$has_flag = false; // it may have already been captured

				foreach ($this->_board[$color . 'Q'] as $piece) {
					if ('V' === $piece['type']) {
						$has_flag = true;
						continue;
					}

					if ($piece['owner'] !== $this->_players[$color]) {
						// if there is already an entry for this player
						if (isset($occupants[$piece['owner']])) {
							// if there is already an entry for this color
							if (isset($occupants[$piece['owner']][$piece['color']])) {
								// add to it
								$occupants[$piece['owner']][$piece['color']]['total'] += ($piece['num'] * $piece['power']);

								// if the piece is a grunt...
								if (preg_match('/[IG]/i', $piece['type'])) {
									$occupants[$piece['owner']][$piece['color']]['infantry'] = true;
								}
							}
							else { // there is no entry for this color yet
								// create one
								$occupants[$piece['owner']][$piece['color']]['total'] = ($piece['num'] * $piece['power']);

								// if the piece is a grunt, flag it
								if (preg_match('/[IG]/i', $piece['type'])) {
									$occupants[$piece['owner']][$piece['color']]['infantry'] = true;
								}
							}
						}
						else { // there is no entry for this player yet
							// create one
							$occupants[$piece['owner']][$piece['color']]['total'] = ($piece['num'] * $piece['power']);

							// if the piece is a grunt, flag it
							if (preg_match('/[IG]/i', $piece['type'])) {
								$occupants[$piece['owner']][$piece['color']]['infantry'] = true;
								$mini[$color][] = $piece['color'];
							}
							else { // the piece is not a grunt, set a default value
								$occupants[$piece['owner']][$piece['color']]['infantry'] = false;
							}
						}
					}
				}
				call($occupants);

				// loop through the newly created array
				// and remove the values if there is no grunt
				if (is_array($occupants)) {
					if (is_array(reset($occupants))) {
						$values = reset(reset($occupants));
						call($values);

						if (false === $values['infantry']) {
							$occupants = false;
						}
					}
				}

				call($occupants);

				// if there are occupants of this sector
				if (false !== $occupants) {
					$captures[$color] = $occupants;
				}
			}
		}
		call($captures);
		call($mini);

		// save it in the object var
		$this->flagCaptures = $captures;
		$this->miniFlag = $mini;
		cll('</div>');
	}



	protected function resolveFlagCaptures($given_color = false)
	{
		cll("<br/>resolveFlagCaptures($given_color)<div style=\"border:2px solid #AA0;margin: 0 5px 0 2em;\">");

		// make sure there is data to deal with
		if (false === $this->flagCaptures) {
			call(false);
			cll('</div>');
			return false;
		}

		// test for simultaneous flag captures
		// TODO: this sucks, find a better way
		if (false !== $this->miniFlag) {
			$captees = array_keys($this->miniFlag);
			foreach ($this->miniFlag as $captee => $capteurs) {
				foreach ($capteurs as $capteur) {
					call($captees);
					call($capteur);

					if (array($captee) == $this->miniFlag[$capteur]) {
						call('--POSSIBLE SIMUL-CAPTURE--');
						call($this->miniFlag);
						call($captees);
						call($capteur);
					}
				}
			}
		}

		// get a simplified board
		$board = $this->simplify_board( );

		// parse through each capture
		$losers = array( );
		foreach ($this->flagCaptures as $loser_color => $winner) {
			cll('<hr />');
			call($loser_color);
			call($winner);

			// find out who to give the winnings to
			if ((false !== $given_color) && isset($given_color[$loser_color])) {
				$win_color = $given_color[$loser_color];
			}
			else { // there was no given color
				$max = $win_color = 0;
				foreach ($winner as $id => $colors) {
					foreach ($colors as $color => $data) {
						// if there was a grunt, and the total is higher than previous
						if ((false !== $data['infantry']) && ($max < $data['total'])) {
							$max = $data['total'];
							$win_color = $color;
						}
						elseif ((false !== $data['infantry']) && ($max == $data['total'])) {
							$this->awards[$loser_color . 'Q'] = $id . 'V';
							continue 3; // skip to next flag capture
						}
					}
				}
			}
			call($win_color);

			// check for a win color, if none, there must have been no grunts
			if (0 === $win_color) {
				call('--CONTINUED TO NEXT FLAG CAPTURE--');
				continue; // skip to next flag capture
			}

			// add this loser to the array
			$losers[] = $loser_color;

			// parse through each occupied sector
			foreach ($board as $sector => $contents) {
				call($sector);
				call($contents);
				// parse through each piece
				foreach ($contents as $piece) {
					// if the piece is loser colored
					if ($piece['color'] == $loser_color) {
						call("--{$piece['color']}{$piece['type']} FOUND AT {$sector}--");
						$this->remove_piece($piece['color'] . $piece['type'], $sector, false);

						// if the piece is the flag, don't place it
						if ('V' != $piece['type']) {
							$this->place_piece($win_color . $piece['type'], $win_color . 'U', false);
						}
					}
				}
			}
		}

		// return the losers array
		call($losers);
		cll('</div>');
		return $losers;
	}



	protected function getIncapacitated( )
	{
		// init the arrays
		$alive = $found = array( );

		// it's basically searching the board for players
		// with a flag but no other pieces, or not enough
		// power units to make a piece

		// find all the players with flags
		// parse through each color
		foreach (self::$COLORS as $color) {
			// check if this color is alive
			if ($this->is_alive($color)) {
				// set this color as 'alive'
				$alive[] = $color;
			}
		}


		// find all the players with pieces
		// parse through each sector
		foreach ($this->_board as $contents) {
			// if there are pieces here
			if (is_array($contents)) {
				// parse through the pieces
				foreach ($contents as $piece) {
					 // if it's not a flag      and        has not been found yet    -- AND --    it's not a point   and at least one of them  OR     it is a point       and   at least two of them
					if ((('V' !== $piece['type']) && ! in_array($piece['color'], $found)) && ((('P' !== $piece['type']) && (1 <= $piece['num'])) || (('P' === $piece['type']) && (2 <= $piece['num'])))) {
						$found[] = $piece['color'];
					}
				}
			}
		}

		// return the incapacitated colors (alive, but not found)
		return array_diff($alive, $found);
	}



	protected function awardPoints( )
	{
		cll("<br/>awardPoints( )<div style=\"border:1px solid #555;margin: 0 5px 0 2em;\">");

		$awarded = array( );

		// parse through each color
		foreach(self::$COLORS as $color) {
			call($color);
			call($this->is_alive($color));
			// make sure this country is alive
			if ($this->is_alive($color)) {
				// search each of the country sectors
				for ($i = 0; $i < 9; ++$i) {
					if (is_array($this->_board[$color . $i])) {
						foreach ($this->_board[$color . $i] as $piece) {
							// if the piece is not the country color _and_ has pieces there AND this color hasn't been found yet _or_ this color hasn't been found in this country yet
							if ((($piece['color'] != $color) && (0 < $piece['num'])) && ( ! isset($awarded[$piece['color']]) || ! in_array($color, $awarded[$piece['color']]))) {
								call("--{$piece['color']} PIECE FOUND AT {$color}{$i}--");
								$awarded[$piece['color']][] = $color;
							}
						}
					}
				}
			}
		}
		call($awarded);

		// count each and award points
		foreach ($awarded as $color => $number) {
			for ($i = 0; $i < count($number); ++$i) {
				$this->place_piece($color . 'P', $color . 'U', false);
			}
		}

		cll('</div>');
	}



	protected function is_alive($color)
	{
		// search the color's headquarters for a flag
		if (is_array($this->_board[$color . 'Q'])) {
			foreach ($this->_board[$color . 'Q'] as $piece) {
				// if a flag was found
				if ('V' === $piece['type']) {
					return true;
				}
			}
		}

		// if this is hit, the color is dead (or never existed)
		return false;
	}



	protected function getAwards( )
	{
		return $this->awards;
	}



	protected function clearAwards( )
	{
		$this->awards = array( );
	}

}


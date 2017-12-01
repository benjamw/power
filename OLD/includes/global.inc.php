<?php

// the global include file

// start the session
session_start( );
session_regenerate_id( );

// set the error display value
error_reporting(E_ALL);
ini_set('display_errors', true);

// check the logged status
require_once 'includes/login.inc.php';

// include the files
require_once 'includes/config.inc.php';
require_once 'includes/constants.inc.php';
require_once 'includes/functions.inc.php';
require_once 'includes/db_functions.inc.php';
require_once 'classes/commander.class.php';
require_once 'classes/mysql.class.php';


// instantiate the classes
$mysql = new mysql($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
$mysql->connect_select(__LINE__,__FILE__);
$mysql->debug = true;

$CMD = new commander($LAND, $SEA, $PIECE, $SECTORS);
$CMD->debug = true;

?>
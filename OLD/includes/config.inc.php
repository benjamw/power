<?php

// ----------------------------------
//   MySQL Server Settings
// ----------------------------------

$DB_HOST = 'localhost'; // your mysql hostname
$DB_USER = 'username'; // your mysql username
$DB_PASS = 'password'; // your mysql password
$DB_NAME = 'power'; // your mysql database


// ----------------------------------
//   MySQL Database Settings
// ----------------------------------

$DB_PREFIX = ''; // 'cmd_';  // your database table name prefix

define ('PLAYER', $DB_PREFIX . 'player'); // the player information
define ('GAME'  , $DB_PREFIX . 'game_');  // each individual game's data (with id: game_1, game_3, etc)
define ('CHAT'  , $DB_PREFIX . 'chat_');  // each individual game's chat data (with id)


// ----------------------------------
//   Game Settings
// ----------------------------------

// none yet

?>
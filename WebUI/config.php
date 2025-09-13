<?php
/* Made by zzzkeil - https://github.com/zzzkeil  
/* Free to use, modify, share.
/* MIT License .....
/* Thanks go also to Gemini, ChatGPT, and some local LLM */

session_start();

// --- Deine Daten unten eintragen
// 
// 
// --- Datenbank verbindung eintragen ---
$db_host = '127.0.0.1';
$db_name = '';
$db_user = '';
$db_pass = '';
//
//
// --- Discord Daten eintragen ---
// --- Daten bekommt man auf https://discord.com/developers
// --- Suchmaschinen oder eine AI fragen, die helfen dir, ist nicht schwer

// --- DISCORD OAUTH2 CONFIGURATION ---
define('DISCORD_CLIENT_ID', '');
define('DISCORD_CLIENT_SECRET', '');
define('DISCORD_REDIRECT_URI', 'https://.../.../callback.php'); // Must match exactly!

// --- DISCORD BOT CONFIGURATION ---
$botToken = '';
$guildId = '';

// --- DISCORD CHANNELID ---
define('ANNOUNCEMENT_CHANNEL_ID', '');
// OPTIONAL: Add a Role ID to mention in the announcement (e.g., an 'Events' role).
// To get a Role ID, right-click the role in Server Settings > Roles and "Copy Role ID".
define('NOTIFICATION_ROLE_ID', ''); // e.g., '932876543210987665432' or leave empty

// --- DISCORD login nur User dieser Server erlauben  ---
define('ALLOWED_GUILD_IDS', [
    '',
    '',
    //'usw. f?r mehr',
]);
?>

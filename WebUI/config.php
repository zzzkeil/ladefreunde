<?php
/* Made by zzzkeil - https://github.com/zzzkeil  
/* Free to use, modify, share.
/* MIT License .....
/* Thanks go also to Gemini, ChatGPT, and some local LLM */

session_start();

// --- Deine Daten unten eintragen 
// --- Datenbank verbindung eintragen ---
$db_host = '';
$db_name = '';
$db_user = '';
$db_pass = '';
//
// --- Discord Daten eintragen ---
// --- Daten bekommt man auf https://discord.com/developers
// --- Suchmaschinen oder eine AI fragen, die helfen dir, ist nicht schwer
// --- DISCORD OAUTH2 CONFIGURATION ---
define('DISCORD_CLIENT_ID', '');
define('DISCORD_CLIENT_SECRET', '');
define('DISCORD_REDIRECT_URI', 'https://.../.../callback.php'); // Wichtig muss 100% passen
// --- DISCORD BOT CONFIGURATION ---
$botToken = '';
$guildId = '';

// --- DISCORD login nur für User dieser Server erlauben  ---
define('ALLOWED_GUILD_IDS', [
    '',
    '',
    //'usw. f?r mehr',
]);
?>

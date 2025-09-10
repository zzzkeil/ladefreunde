<?php
/* Made by zzzkeil - https://github.com/zzzkeil  
/* Free to use, modify, share.
/* MIT License .....
/* Thanks go also to Gemini, ChatGPT, and some local LLM */

session_start();

// --- Deine Daten unten eintragen
// --- Bekommt man auf https://discord.com/developers/
// --- Suchmaschinen oder eine AI fragen, die erklären dir wie das geht, ist nicht schwer


// --- DISCORD OAUTH2 CONFIGURATION  ---
define('DISCORD_CLIENT_ID', '');
define('DISCORD_CLIENT_SECRET', '');
define('DISCORD_REDIRECT_URI', 'https://zum.beispieldomain.yxz/verzeichnis/callback.php'); // Muss 100 Prozent passen!

// --- DISCORD BOT CONFIGURATION  ---
$botToken = '';
$guildId = '';
?>

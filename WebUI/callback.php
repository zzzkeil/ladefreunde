<?php
/* Made by zzzkeil - https://github.com/zzzkeil  
/* Free to use, modify, share.
/* MIT License .....
/* Thanks go also to Gemini, ChatGPT, and some local LLM */

require_once 'config.php';

if (isset($_GET['error'])) {
    die('Discord returned an error: ' . htmlspecialchars($_GET['error_description']));
}

if (!isset($_GET['code'])) {
    die('Error: No code returned from Discord.');
}

$tokenUrl = 'https://discord.com/api/v10/oauth2/token';
$postData = [
    'client_id' => DISCORD_CLIENT_ID,
    'client_secret' => DISCORD_CLIENT_SECRET,
    'grant_type' => 'authorization_code',
    'code' => $_GET['code'],
    'redirect_uri' => DISCORD_REDIRECT_URI,
];

$ch = curl_init($tokenUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$tokenData = json_decode($response);

if (isset($tokenData->error) || !isset($tokenData->access_token)) {
    die('Failed to get access token: ' . ($tokenData->error_description ?? 'Unknown error'));
}

$userUrl = 'https://discord.com/api/v10/users/@me';
$headers = ['Authorization: Bearer ' . $tokenData->access_token];

$ch = curl_init($userUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$userResponse = curl_exec($ch);
curl_close($ch);

$userData = json_decode($userResponse);

if (!$userData || !isset($userData->id)) {
    die('Failed to fetch user data from Discord.');
}

$_SESSION['discord_user'] = $userData;

header('Location: index.php');
exit();

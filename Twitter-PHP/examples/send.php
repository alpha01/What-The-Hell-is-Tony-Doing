<?php

require_once '../twitter.class.php';

$consumerKey = '';
$consumerSecret = '';
$accessToken = '';
$accessTokenSecret = '';



// ENTER HERE YOUR CREDENTIALS (see readme.txt)
$twitter = new Twitter($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret);
$status = $twitter->send('Testing PHP Twitter API');

echo $status ? 'OK' : 'ERROR';

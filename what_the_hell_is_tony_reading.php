#!/usr/bin/env php
<?php

# Using this until I finish writing the currently_reading Drupal module (my knowledge of Drupal's hook system is somewhat limited).
# Tony Baltazar. email: root[@]rubyninja.org

require_once dirname(__FILE__) . '/Twitter-PHP/twitter.class.php';

$url = 'https://www.rubysecurity.org/';
$log_file = '/home/tony/wtf.log';

$fbcmd = '/home/tony/.fbcmd/bin/fbcmd';

# Twitter oath API keys
# https://dev.twitter.com/
$consumerKey = '';
$consumerSecret = '';
$accessToken = '';
$accessTokenSecret = '';


$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$page = curl_exec($ch);
if(strlen($page) < 10) die("Failed to fetch page.\n");

$fetched_page = explode('<span>Currently Reading</span>', $page);
$tmp = explode('<!-- /block -->', $fetched_page[1]); 
$currently_reading = explode("\n", $tmp[0]);


#echo "Number of books are " . floor(count($currently_reading) / 9) . "\n";

$book_info = array();
$iter = 0;
for ($i=4; $i<count($currently_reading); $i+=9) {
    $book_info[$iter]['title'] = $currently_reading[$i];
    if (($i + 5) < count($currently_reading)) {
        preg_match('/(\d+?)\s\/\s(\d+?)\s/', $currently_reading[$i + 5], $match);
        $book_info[$iter]['page_end'] = $match[1];
        $book_info[$iter]['total_pages'] = $match[2];
    }
    $iter++;  
}
curl_close($ch);

array_pop($book_info);
#print_r($book_info) . "\n";

if(file_exists($log_file)) {
    $logged_book_info = unserialize(file_get_contents($log_file));
    for($i=0; $i<count($logged_book_info); $i++) {
        if($logged_book_info[$i]['title'] != $book_info[$i]['title']) {
            spam_my_friends("Tony started reading {$book_info[$i]['title']}");
            continue;
        } elseif($logged_book_info[$i]['page_end'] != $book_info[$i]['page_end']) {
            spam_my_friends('Tony finished reading ' . ($logged_book_info[$i]['page_end'] - $book_info[$i]['page_end']) . ' page(s) on ' . $book_info[$i]['title']);
        } else {
            echo "No update\n";
        }
    }

    file_put_contents($log_file, serialize($book_info));

} else {
    file_put_contents($log_file, serialize($book_info));
    spam_my_friends('Weee');
}

function spam_my_friends($message) {
    global $consumerKey, $consumerSecret, $accessToken, $accessTokenSecret, $fbcmd;
    try {
        $twitter = new Twitter($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret);
        $twitter->send($message);
        exec($fbcmd . ' post ' . escapeshellarg($message)); 
    } catch (Exception $e){
        echo 'Fuck shit up ' . $e->getMessage();
    }
}


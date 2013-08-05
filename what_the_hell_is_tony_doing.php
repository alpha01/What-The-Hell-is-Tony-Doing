<?php
# Written by: Tony Baltazar.Septemter 2012.
# Email: root[@]rubyninja.org
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License as
# published by the Free Software Foundation; either version 3 of the
# License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful, but
# WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
# General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307
# USA

$file = __FILE__;

require_once dirname($file) . '/Twitter-PHP/twitter.class.php';
require_once dirname($file) . '/bitly/bitly.php';

# http://fbcmd.dtompkins.com/
$fbcmd = '/home/tony/.fbcmd/bin/fbcmd';


/* Database config */
define('DB_USER', '');
define('DB_NAME', '');
define('DB_PASSWD', '');
define('DB_HOST', 'localhost');

# Twitter oauth API keys
# https://dev.twitter.com/
$consumerKey = '';
$consumerSecret = '';
$accessToken = '';
$accessTokenSecret = '';

# Legacy Bitly API Key (may need to update to latest API soon)
# http://dev.bitly.com
$bitly_username = '';
$bitly_apikey = '';


/* RSS feeds */
$RSS_FEEDS = array(
		1 => 'https://www.rubysecurity.org/rss.xml',
                2 => 'http://www.rubyninja.org/feed/',
		3 => 'https://www.rubysecurity.org/photos/index.php/rss/feed/gallery/latest',
		4 => 'https://www.rubysecurity.org/books.xml');



$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWD, DB_NAME);
if ($mysqli->connect_errno) {
    die("Failed to connect to MySQL: " . $mysqli->connect_error . "\n");
}



foreach($RSS_FEEDS as $social_media_id => $feed) {

	#checks if records exists on the social_media tbl
	if ($rss_feed_check = $mysqli->query("SELECT id, url FROM social_media WHERE id = '$social_media_id' AND url = '$feed'")) {
 	
		$rss_check_row = $rss_feed_check->num_rows;
		if ($rss_check_row == 0) {
			$rss_feed_check->close();	
			if (!$mysqli->query("INSERT INTO social_media (id, url) VALUES( '$social_media_id', '$feed' )")) {
				die('Error: ' . $mysqli->error . "\n");
			}	
		}

		$rss_feed_check->close();	
	} else {
		die('Error: ' . $mysqli->error . "\n");
	}
	

	$xml_feed = @simplexml_load_file($feed);
	if ($xml_feed == false) {
		echo "Unable to get feed $feed\n";
		continue;
	}

	foreach ($xml_feed->channel->children() as $t) {
		$sql_str = array('social_media_id' => $social_media_id);
		$sql_columns = array('social_media_id');
		#echo $t->getName() . "\t$t\n";
		if ($t->getName() == 'item') {
			foreach($t->children() as $a) {
				#echo $a->getName() . ": '$a'\n";
				$sql_columns[] = $mysqli->real_escape_string($a->getName());
				$sql_str[$a->getName()] = $mysqli->real_escape_string($a);
			} 
			$sql_columns = array_unique($sql_columns);
			$sql_columns_str = implode(', ', $sql_columns);
				
			
			$sql_select_query = "SELECT $sql_columns_str FROM postings WHERE ";
			$sql_insert_query = "INSERT into postings ";
			$sql_insert_query_val = '';			

			foreach ($sql_str as $sql => $sql_val) {
				$sql_select_query .= "$sql = '$sql_val' AND ";
				$sql_insert_query_val .= "'$sql_val', ";
			}
		
			$sql_select_query = substr($sql_select_query, 0, -4); // removes trailing 'AND '
			#echo $sql_select_query . "\n";

			if ($result = $mysqli->query($sql_select_query)) {
				$row = $result->num_rows;

				if ($row == 0) {
					$sql_insert_query_val = substr($sql_insert_query_val, 0, -2); // removes trailing ', '

					$sql_insert_query .= "( $sql_columns_str ) VALUES ( $sql_insert_query_val )";

					#echo $sql_insert_query . "\n";
					$mysqli->query($sql_insert_query);
					if (!$mysqli->affected_rows) {
						die("Error inserting data into database " . $mysqli->error . "\n");
					} else {
						switch($feed) {
							case 'https://www.rubysecurity.org/rss.xml':
								$post_topic = 'Sysadmin/Programming Blog';
								break;
							case 'http://www.rubyninja.org/feed/':
								$post_topic = 'Blog';
								break;
							case 'https://www.rubysecurity.org/photos/index.php/rss/feed/gallery/latest':
								$post_topic = 'Photo';
								break;
							case 'https://www.rubysecurity.org/books.xml':
								$post_topic = 'Book Review';
								break;
						}
						$bitly_object = new Bitly($bitly_username, $bitly_apikey);
						$short_url_link = $bitly_object->shorten($sql_str['link']);
						#$short_url_link = 'http://bit.ly/M9GMgV'; #test
						
						$description = $sql_str['description'];
						
						# Keep it under 140 chars.
						$curr_lengh = strlen($sql_str['title']) + strlen($post_topic) + strlen($short_url_link['url']) + 5;
						#echo "CURR length: $curr_lengh\n";
						if ($curr_lengh > 140) {
							$short_offset = $curr_lengh - 140;
							$msg = substr($sql_str['title'], 0, -$short_offset);
							$shorten_title = preg_replace('/(\s|\/|\\|;|:|\(|\)|\{|\}|[a-zA-Z0-9]){3}$/', '...', $msg);
							$message_to_the_world = $shorten_title . " ($post_topic)" . "\n" . $short_url_link['url']; 
						} else  {
							$message_to_the_world = $sql_str['title'] . " ($post_topic)" . "\n" . $short_url_link['url'] . "\n";
						}
						echo "Message : " . $message_to_the_world . "\n";
						$twitter = new Twitter($consumerKey, $consumerSecret, $accessToken, $accessTokenSecret);
						$tweet_status = $twitter->send($message_to_the_world);
						if (!$tweet_status) mail('root@rubyninja.org', 'Unable to send feed to twitter', "Tweet:\n $message_to_the_world", "from:what_the_hell@rubyninja.org");

						#$sanitized_post = escapeshellcmd($message_to_the_world);
						exec("$fbcmd status \"$message_to_the_world\"", $fbcmd_output, $fbcmd_status);
						#$fbcmd_status = 0; // TESTING
						if($fbcmd_status != 0) mail('root@rubyninja.org', 'Unable to send feed to Facebook', "Facebook status:\n $message_to_the_world\nError:\n" . implode("\n", $fbcmd_output), "from:what_the_hell@rubyninja.org");

						echo $sql_insert_query . "\n";
						echo "------------------------------------\n";
					}

				} else {
					echo 'No update' . "\n";
					continue;
				}
				$result->close();
			} else {
				die('Error: ' . $mysqli->error . "\n");
			}
		}
		
	}

}

$mysqli->close();

?>

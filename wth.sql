CREATE TABLE `social_media` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `social_media_platform` varchar(255) NOT NULL,
 `url` varchar(255) NOT NULL,
 PRIMARY KEY (`id`)
);

CREATE TABLE `postings` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `social_media_id` int(11) NOT NULL,
 `date_loged` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 `pubDate` text NOT NULL,
 `title` varchar(255) NOT NULL,
 `link` varchar(255) NOT NULL,
 `description` text NOT NULL,
 `category` varchar(255) NOT NULL,
 `guid` varchar(255) DEFAULT NULL,
 `comments` text,
 PRIMARY KEY (`id`)
);


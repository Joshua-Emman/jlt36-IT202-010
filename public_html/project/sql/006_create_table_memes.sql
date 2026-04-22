-- UCID: jlt36
-- Date: 04/22/2026
-- Description: Creates the Memes table used to store both API-fetched and manually created meme records. 
-- Includes required fields, timestamps, and an is_api flag to distinguish record origin.

CREATE TABLE `IT202-M25-Memes` (
    `id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `title` varchar(255) NOT NULL,
    `post_link` varchar(255) NOT NULL UNIQUE,
    `image_url` text NOT NULL,
    `author` varchar(100) DEFAULT NULL,
    `subreddit` varchar(100) DEFAULT NULL,
    `is_nsfw` tinyint(1) NOT NULL DEFAULT '0',
    `spoiler` tinyint(1) NOT NULL DEFAULT '0',
    `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `is_api` tinyint(1) DEFAULT '1'
);
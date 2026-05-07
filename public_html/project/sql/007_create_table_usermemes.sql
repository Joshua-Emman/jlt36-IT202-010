-- UCID: jlt36
-- Date: 05/07/2026
-- Description: table connects users to memes
CREATE TABLE `IT202-M25-UserMemes` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `meme_id` INT NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_user_meme` (`user_id`, `meme_id`)
);
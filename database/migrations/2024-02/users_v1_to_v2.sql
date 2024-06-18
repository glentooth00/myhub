ALTER TABLE `users`
ADD `home` varchar(255) COLLATE 'latin1_swedish_ci' NULL AFTER `status`,
ADD `failed_logins` int(11) NOT NULL DEFAULT '0' AFTER `failed_login_attempts`;

# Should remove `failed_login_attempts` once S3.v1 is decomissioned.
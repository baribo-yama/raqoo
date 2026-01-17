SET NAMES utf8mb4;
CREATE DATABASE IF NOT EXISTS `raqoo` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;



create user 'dbuser'@'%' identified by 'dbuser';
GRANT ALL ON *.* to 'dbuser' @'%';
FLUSH PRIVILEGES;
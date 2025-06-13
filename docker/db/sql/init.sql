SET NAMES utf8mb4;
CREATE DATABASE IF NOT EXISTS `platform` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

create user 'dbuser'@'%' identified by 'dbuser';
GRANT ALL ON *.* to 'dbuser' @'%';
FLUSH PRIVILEGES;


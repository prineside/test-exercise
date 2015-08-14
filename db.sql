CREATE DATABASE `test_exercise`;

USE `test_exercise`;

-- test_exercise.account
CREATE TABLE `account` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(128) NOT NULL COMMENT 'Адрес электронной почты',
  `login` varchar(32) NOT NULL COMMENT 'Логин пользователя для входа на сайт',
  `hash` varchar(32) NOT NULL COMMENT 'Зашифрованный пароль пользователя (md5)',
  `salt` varchar(32) NOT NULL COMMENT 'Строка, которая смешивается с паролем пользователя для hash',
  `registration_date` int(11) NOT NULL COMMENT 'UNIX Timestamp времени создания аккаунта',
  `name` varchar(50) NOT NULL COMMENT 'Настоящее имя пользователя',
  `surname` varchar(50) NOT NULL COMMENT 'Настоящая фамилия пользователя',
  `gender` varchar(8) NOT NULL COMMENT 'Пол',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `login` (`login`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Аккаунты пользователей';

-- test_exercise.login_attempt
CREATE TABLE IF NOT EXISTS `login_attempt` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(16) NOT NULL COMMENT 'IP-адрес пользователя, который сделал попытку',
  `date` int(11) NOT NULL COMMENT 'UNIX-Timestamp в момент попытки',
  `account` int(11) unsigned NOT NULL COMMENT 'Целевой аккаунт',
  `status` tinyint(1) unsigned NOT NULL COMMENT 'Результат попытки (1 если успешно)',
  `browser` varchar(128) NOT NULL COMMENT 'Браузер, из которого произведена попытка',
  PRIMARY KEY (`id`),
  KEY `FK_login_attempt_account` (`account`),
  KEY `ip` (`ip`),
  KEY `date` (`date`),
  KEY `status` (`status`),
  CONSTRAINT `FK_login_attempt_account` FOREIGN KEY (`account`) REFERENCES `account` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Попытки входа';
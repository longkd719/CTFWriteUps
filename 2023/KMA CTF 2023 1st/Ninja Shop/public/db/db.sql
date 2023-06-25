USE ninjashop;
CREATE TABLE users(uid int AUTO_INCREMENT PRIMARY KEY, username VARCHAR(32), password VARCHAR(32), fullname VARCHAR(255));
CREATE TABLE coins(id int AUTO_INCREMENT PRIMARY KEY, coin INT, uid INT);
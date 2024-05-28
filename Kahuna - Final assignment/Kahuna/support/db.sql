CREATE DATABASE IF NOT EXISTS kahuna;

USE kahuna;


CREATE TABLE User (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    accessLevel CHAR(10) NOT NULL DEFAULT 'User'
);

-- Insert the admin user with the hashed password
INSERT INTO User (email, password, accessLevel)
VALUES ('admin@ice.com', '$2y$10$DCCkTFcdAoFA.LhALAlZded7qZeSS5qMGh/bPR3t3d492398nf0/y', 'Admin');



CREATE TABLE AccessToken(
    id              INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    userId          INT NOT NULL,
    token           VARCHAR(255) NOT NULL,
    birth           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT c_accesstoken_user
        FOREIGN KEY(userId) REFERENCES User(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);


CREATE TABLE IF NOT EXISTS Product(
    id               INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    userId           INT NOT NULL,
    serial           VARCHAR(255) NOT NULL,
    name             VARCHAR(255) NOT NULL,
    warrantyLength   INT(11) NOT NULL, 
    birth            TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    complete         BOOL NOT NULL DEFAULT FALSE,
    CONSTRAINT c_product_user
        FOREIGN KEY(userId) REFERENCES User(id)
        ON UPDATE CASCADE
        ON DELETE CASCADE

);


	




CREATE DATABASE doings_done DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci;
USE doings_done;

CREATE TABLE projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name CHAR(128) NOT NULL UNIQUE,
    user_id INT
);

CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT,
    user_id INT,
    data_add TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    title CHAR(128) NOT NULL,
    file CHAR(128),
    deadline DATE NOT NULL,
    completed BOOL DEFAULT 0
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    email VARCHAR(128) NOT NULL UNIQUE,
    name_user CHAR(128) NOT NULL,
    password CHAR(64) NOT NULL
);

CREATE INDEX title ON tasks(title);
CREATE INDEX deadline ON tasks(deadline);

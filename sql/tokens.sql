DROP TABLE IF EXISTS `login_tokens`;

CREATE TABLE login_tokens (
    token varchar(255) NOT NULL,
    db_server varchar(30) NOT NULL,
    db_username varchar(30) NOT NULL,
    db_password varchar(30) NOT NULL,
    db_database varchar(30) NOT NULL,
    expires timestamp NOT NULL,
    row_insert_dt timestamp NOT NULL,
    row_update_dt timestamp NOT NULL
);

ALTER TABLE login_tokens ADD INDEX (token), ADD INDEX (db_server);

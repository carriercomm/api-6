DROP TABLE IF EXISTS `login_tokens`;

CREATE TABLE login_tokens (
    token varchar(255) NOT NULL,
    login varchar(30) NOT NULL,
    expires timestamp NOT NULL,
    row_insert_dt timestamp NOT NULL,
    row_update_dt timestamp NOT NULL
);

ALTER TABLE login_tokens ADD INDEX (token), ADD INDEX (login);

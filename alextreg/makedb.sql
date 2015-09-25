# (Lifted from Horde.org  --ryan.)
#
# If you are installing Horde for the first time, you can simply
# direct this file to mysql as STDIN:
#
# $ mysql --user=root --password=<MySQL-root-password> < mysql_create.sql
#
# If you are upgrading from a previous version, you will need to comment
# out the the user creation steps below, as well as the schemas for any
# tables that already exist.

USE mysql;

REPLACE INTO user (host, user, password)
    VALUES (
        'localhost',
        'alextreg',
  -- IMPORTANT: Change this password!
        PASSWORD('kjskdjasd923asd')
    );

REPLACE INTO db (host, db, user, select_priv, insert_priv, update_priv,
                 delete_priv, create_priv, drop_priv)
    VALUES (
        'localhost',
        'alextreg',
        'alextreg',
        'Y', 'Y', 'Y', 'Y',
        'Y', 'Y'
    );

FLUSH PRIVILEGES;

CREATE DATABASE alextreg;

USE alextreg;

CREATE TABLE alextreg_extensions (
    id int not null auto_increment,
    extname varchar(128) not null,
    public bool not null,
    author varchar(128) not null,
    entrydate datetime not null,
    author varchar(128) not null,
    lastedit datetime not null,
    primary key (id)
);

GRANT SELECT, INSERT, UPDATE, DELETE ON alextreg_extensions TO alextreg@localhost;

CREATE TABLE alextreg_tokens (
    id int not null auto_increment,
    tokenname varchar(128) not null,
    tokenval int not null,
    extid int not null,
    author varchar(128) not null,
    entrydate datetime not null,
    lasteditauthor varchar(128) not null,
    lastedit datetime not null,
    primary key (id)
);

GRANT SELECT, INSERT, UPDATE, DELETE ON alextreg_tokens TO alextreg@localhost;

CREATE TABLE alextreg_entrypoints (
    id int not null auto_increment,
    entrypointname varchar(128) not null,
    extid int not null,
    author varchar(128) not null,
    entrydate datetime not null,
    lasteditauthor varchar(128) not null,
    lastedit datetime not null,
    primary key (id)
);

CREATE TABLE alextreg_papertrail (
    id int not null auto_increment,
    action text not null,
    sqltext mediumtext not null,
    author varchar(128) not null,
    entrydate datetime not null,
    primary key (id)
);

GRANT SELECT, INSERT, UPDATE, DELETE ON alextreg_entrypoints TO alextreg@localhost;

FLUSH PRIVILEGES;

# Done!

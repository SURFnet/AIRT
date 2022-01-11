CREATE TABLE domains (
    id           integer,
    domain       varchar(128) not null,
    constituency integer not null,
    primary key (id),
    foreign key (constituency) references constituencies(id)
);
CREATE SEQUENCE domains_sequence;

ALTER TABLE constituencies ADD code varchar(32), ADD guid varchar(36);

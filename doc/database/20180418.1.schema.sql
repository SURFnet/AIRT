CREATE TABLE constituency_types (
	    id          integer,
	    label       integer not null,  -- with unique index
	    name        varchar(80),
	    primary key (id)
);

CREATE SEQUENCE constituency_types_sequence;

ALTER TABLE constituencies ADD type int;

ALTER TABLE incidents ADD subtype text;

create table colorstates
(
    id         integer,
    url        varchar(255),
    colorstate varchar(255),
    created    timestamp,
    createdby  integer,
    active     integer,

    primary key (id)
);

create sequence colorstates_seq;

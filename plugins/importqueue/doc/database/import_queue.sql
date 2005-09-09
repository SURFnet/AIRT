CREATE TABLE import_queue (
  id        integer,
  created   timestamp    not null,
  status    varchar(16)  not null default 'new',
  sender    varchar(50)  not null,
  type      varchar(50)  not null,
  summary   varchar(100) not null,
  primary key (id)
);

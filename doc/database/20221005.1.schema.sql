ALTER TABLE networks
  ADD datasource varchar(10),
  ADD UNIQUE (network, netmask);

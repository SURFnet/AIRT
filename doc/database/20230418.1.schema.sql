ALTER TABLE incidents
  ADD severity INTEGER NOT NULL DEFAULT 0;
ALTER TABLE mailtemplates
  ADD action_severity int;

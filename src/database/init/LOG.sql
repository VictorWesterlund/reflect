CREATE TABLE syslog (
    id TEXT PRIMARY KEY NOT NULL,
    level INT NO NULL,
    data BLOB,
    initiator TEXT,
    date_created INT NOT NULL
);

CREATE INDEX idx_initiator ON syslog(initiator);
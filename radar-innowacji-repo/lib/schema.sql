PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS users (
    id            INTEGER PRIMARY KEY,
    email         TEXT NOT NULL,
    email_norm    TEXT NOT NULL UNIQUE,     -- znormalizowany (bez +tagów, kropek gmaila) = 1 osoba
    created_at    INTEGER NOT NULL,
    ip_hash       TEXT,
    subnet_hash   TEXT,
    fp_hash       TEXT,
    is_admin      INTEGER NOT NULL DEFAULT 0,
    is_banned     INTEGER NOT NULL DEFAULT 0
);
CREATE INDEX IF NOT EXISTS idx_users_subnet ON users(subnet_hash, created_at);
CREATE INDEX IF NOT EXISTS idx_users_fp ON users(fp_hash);

CREATE TABLE IF NOT EXISTS login_tokens (
    token_hash    TEXT PRIMARY KEY,         -- trzymamy tylko hash tokenu
    email         TEXT NOT NULL,
    email_norm    TEXT NOT NULL,
    created_at    INTEGER NOT NULL,
    expires_at    INTEGER NOT NULL,
    used_at       INTEGER
);

CREATE TABLE IF NOT EXISTS projects (
    id            INTEGER PRIMARY KEY,
    title         TEXT NOT NULL,
    institution   TEXT NOT NULL,
    category      TEXT NOT NULL,
    location      TEXT NOT NULL,
    summary       TEXT NOT NULL,
    description   TEXT NOT NULL,
    impact        TEXT NOT NULL,
    link          TEXT,
    contact_email TEXT NOT NULL,
    submitted_by  INTEGER REFERENCES users(id),
    status        TEXT NOT NULL DEFAULT 'pending',   -- pending | approved | rejected
    created_at    INTEGER NOT NULL,
    reviewed_at   INTEGER,
    reviewed_by   INTEGER,
    review_note   TEXT
);
CREATE INDEX IF NOT EXISTS idx_projects_status ON projects(status);

CREATE TABLE IF NOT EXISTS votes (
    id            INTEGER PRIMARY KEY,
    user_id       INTEGER NOT NULL REFERENCES users(id),
    project_id    INTEGER NOT NULL REFERENCES projects(id),
    created_at    INTEGER NOT NULL,
    ip_hash       TEXT,
    subnet_hash   TEXT,
    fp_hash       TEXT,
    ua_hash       TEXT,
    risk_score    INTEGER NOT NULL DEFAULT 0,
    risk_reasons  TEXT,
    status        TEXT NOT NULL DEFAULT 'counted',   -- counted | pending | rejected
    UNIQUE(user_id, project_id)                      -- twarda gwarancja: 1 osoba = 1 głos na projekt
);
CREATE INDEX IF NOT EXISTS idx_votes_project ON votes(project_id, status, created_at);
CREATE INDEX IF NOT EXISTS idx_votes_subnet ON votes(subnet_hash, project_id, created_at);
CREATE INDEX IF NOT EXISTS idx_votes_fp ON votes(fp_hash, project_id);

CREATE TABLE IF NOT EXISTS rate_events (
    key           TEXT NOT NULL,
    ts            INTEGER NOT NULL
);
CREATE INDEX IF NOT EXISTS idx_rate ON rate_events(key, ts);

CREATE TABLE IF NOT EXISTS used_challenges (
    id            TEXT PRIMARY KEY,
    expires_at    INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS audit_log (
    id            INTEGER PRIMARY KEY,
    ts            INTEGER NOT NULL,
    actor_id      INTEGER,
    action        TEXT NOT NULL,
    details       TEXT
);

-- ===== Radar: ocena, dopasowanie, kontakt =====
CREATE TABLE IF NOT EXISTS assessments (           -- ocena ekspercka (moderator/ekspert)
    id            INTEGER PRIMARY KEY,
    project_id    INTEGER NOT NULL REFERENCES projects(id),
    assessor_id   INTEGER NOT NULL REFERENCES users(id),
    scores        TEXT NOT NULL,                    -- JSON {kryterium: 1..5}
    comment       TEXT,
    created_at    INTEGER NOT NULL,
    UNIQUE(project_id, assessor_id)
);

CREATE TABLE IF NOT EXISTS needs (                 -- zapotrzebowania: kto szuka jakiego rozwiązania
    id            INTEGER PRIMARY KEY,
    org_name      TEXT NOT NULL,
    org_type      TEXT NOT NULL,                    -- instytucja publiczna | firma | inwestor | organizacja społeczna | uczelnia
    title         TEXT NOT NULL,
    description   TEXT NOT NULL,
    areas         TEXT,
    offer         TEXT,                             -- co oferuje (wdrożenie, finansowanie, partnerstwo...)
    contact_email TEXT NOT NULL,
    submitted_by  INTEGER REFERENCES users(id),
    status        TEXT NOT NULL DEFAULT 'pending',
    is_demo       INTEGER NOT NULL DEFAULT 0,
    created_at    INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS contact_requests (      -- „jestem zainteresowany” – łączenie twórców z odbiorcami
    id            INTEGER PRIMARY KEY,
    project_id    INTEGER NOT NULL REFERENCES projects(id),
    user_id       INTEGER NOT NULL REFERENCES users(id),
    role          TEXT NOT NULL,
    organization  TEXT,
    message       TEXT NOT NULL,
    created_at    INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS need_responses (       -- twórca odpowiada na zapotrzebowanie („mam takie rozwiązanie”)
    id            INTEGER PRIMARY KEY,
    need_id       INTEGER NOT NULL REFERENCES needs(id),
    user_id       INTEGER NOT NULL REFERENCES users(id),
    project_id    INTEGER REFERENCES projects(id),
    message       TEXT NOT NULL,
    created_at    INTEGER NOT NULL
);

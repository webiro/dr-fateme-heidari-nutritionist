CREATE TABLE IF NOT EXISTS appointments (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  age TEXT,
  height TEXT,
  goal TEXT,
  phone TEXT,
  message TEXT,
  created_at TEXT NOT NULL
);

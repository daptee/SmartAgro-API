UPDATE users
SET event_id = 1
WHERE DATE(created_at) IN ('2026-03-11', '2026-03-12');

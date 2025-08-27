# STK Push Package (HELB Disbursement)

This package contains a simple frontend and PHP backend to initiate Safaricom Daraja STK Push (sandbox-ready).

## Contents
- `index.html` — frontend UI (mock mode default)
- `api/stk_push.php` — backend script (configure credentials)
- `api/callback.php` — receives STK push result and logs to `callback_log.json`
- `callback_log.json` — optional log file created at runtime

## Quick deploy
1. Upload all files to your webhost (e.g., `public_html/`).
2. Ensure `api/` is inside your web root so `api/stk_push.php` is reachable.
3. Edit `api/stk_push.php`:
   - Set `$consumerKey`, `$consumerSecret`, `$passkey`, and `$callbackURL`.
   - For testing keep `$sandbox = true` (sandbox endpoints).
4. Ensure `callback_log.json` is writable by PHP (create it and `chmod 664` if needed).
5. Enable HTTPS (required by Safaricom).
6. Test using mock mode ON first, then mock OFF pointing to `api/stk_push.php`.

## Notes
- Keep live credentials secret. Do not store them in public repos.
- Use sandbox credentials from developer.safaricom.co.ke for testing.
- The `callback.php` simply logs the raw JSON; customize to update DB or send notifications.


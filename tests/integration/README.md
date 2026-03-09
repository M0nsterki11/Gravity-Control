# Integration tests (manual runner)

Ovaj folder je baseline za integration testove `login/register/reserve`.

```powershell
powershell -ExecutionPolicy Bypass -File tests/integration/run.ps1
```

Skripta radi osnovne HTTP pozive prema lokalnoj instanci:
- `POST /backend/register.php`
- `POST /backend/login.php`
- `GET /backend/get_sessions.php`
- `POST /backend/reserve.php`
- `POST /backend/logout.php`

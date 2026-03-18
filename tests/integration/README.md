# Integration tests (manual runner)

Ovaj folder je baseline za integration testove `login/register/reserve`.

```powershell
powershell -ExecutionPolicy Bypass -File tests/integration/run.ps1
```

Skripta radi osnovne HTTP pozive prema lokalnoj instanci:
- `POST /api/register`
- `POST /api/login`
- `GET /api/sessions`
- `POST /api/reserve`
- `POST /api/logout`



# Integration tests (manual runner)

Ovaj folder je baseline za integration testove `login/register/reserve`.

```powershell
powershell -ExecutionPolicy Bypass -File tests/integration/run.ps1
```

Skripta radi osnovne HTTP pozive prema lokalnoj instanci:
- `POST /public/api/register`
- `POST /public/api/login`
- `GET /public/api/sessions`
- `POST /public/api/reserve`
- `POST /public/api/logout`


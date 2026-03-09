$ErrorActionPreference = "Stop"

$BaseUrl = "http://localhost/gravity-control"
$Session = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$Timestamp = [DateTimeOffset]::UtcNow.ToUnixTimeSeconds()
$Email = "itest_$Timestamp@example.com"
$Password = "test1234"

Write-Host "Register..."
$registerBody = @{
  fullName = "Integration Test"
  email = $Email
  password = $Password
  confirmPassword = $Password
} | ConvertTo-Json

$register = Invoke-RestMethod -Uri "$BaseUrl/backend/register.php" -Method POST -WebSession $Session -ContentType "application/json" -Body $registerBody
$csrf = $register.user.csrf_token
if (-not $register.success) { throw "Register failed: $($register.message)" }

Write-Host "Logout after register..."
$logoutHeaders = @{ "X-CSRF-Token" = $csrf }
$logout = Invoke-RestMethod -Uri "$BaseUrl/backend/logout.php" -Method POST -WebSession $Session -Headers $logoutHeaders
if (-not $logout.success) { throw "Logout failed: $($logout.message)" }

Write-Host "Login..."
$loginBody = @{
  email = $Email
  password = $Password
} | ConvertTo-Json

$login = Invoke-RestMethod -Uri "$BaseUrl/backend/login.php" -Method POST -WebSession $Session -ContentType "application/json" -Body $loginBody
if (-not $login.success) { throw "Login failed: $($login.message)" }
$csrf = $login.user.csrf_token

Write-Host "Fetch sessions..."
$sessions = Invoke-RestMethod -Uri "$BaseUrl/backend/get_sessions.php" -Method GET -WebSession $Session
if (-not $sessions.success) { throw "Session list failed: $($sessions.message)" }
if ($sessions.sessions.Count -lt 1) {
  Write-Host "No active sessions, reserve test skipped."
} else {
  $sessionId = $sessions.sessions[0].id
  $reserveBody = @{
    sessionId = $sessionId
    sessionInfo = "integration fallback"
  } | ConvertTo-Json

  Write-Host "Reserve first session..."
  $reserve = Invoke-RestMethod -Uri "$BaseUrl/backend/reserve.php" -Method POST -WebSession $Session -Headers @{ "X-CSRF-Token" = $csrf } -ContentType "application/json" -Body $reserveBody
  if (-not $reserve.success) { throw "Reserve failed: $($reserve.message)" }
}

Write-Host "Logout..."
$logout = Invoke-RestMethod -Uri "$BaseUrl/backend/logout.php" -Method POST -WebSession $Session -Headers @{ "X-CSRF-Token" = $csrf }
if (-not $logout.success) { throw "Logout failed: $($logout.message)" }

Write-Host "Integration flow completed successfully."

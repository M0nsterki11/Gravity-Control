async function requestJson(url, { method = "GET", body, csrfToken = "" } = {}) {
  const headers = {};
  if (body !== undefined) {
    headers["Content-Type"] = "application/json";
  }
  if (csrfToken) {
    headers["X-CSRF-Token"] = csrfToken;
  }

  const response = await fetch(url, {
    method,
    headers,
    body: body !== undefined ? JSON.stringify(body) : undefined,
  });

  const data = await response.json();
  return { response, data };
}

export async function loginRequest(email, password) {
  const { data } = await requestJson("backend/login.php", {
    method: "POST",
    body: { email, password },
  });
  return data;
}

export async function registerRequest(fullName, email, password, confirmPassword) {
  const { data } = await requestJson("backend/register.php", {
    method: "POST",
    body: { fullName, email, password, confirmPassword },
  });
  return data;
}

export async function logoutRequest(csrfToken) {
  const { data } = await requestJson("backend/logout.php", {
    method: "POST",
    csrfToken,
  });
  return data;
}

export async function getSessionsRequest() {
  const { data } = await requestJson("backend/get_sessions.php");
  return data;
}

export async function reserveRequest(sessionId, sessionInfo, csrfToken) {
  const { data } = await requestJson("backend/reserve.php", {
    method: "POST",
    csrfToken,
    body: { sessionId, sessionInfo },
  });
  return data;
}

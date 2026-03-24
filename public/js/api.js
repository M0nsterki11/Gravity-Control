// Zajednicki fetch helper za JSON request/response i opcionalni CSRF header.
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

const API_BASE = "api";

// Poziva API login endpoint.
export async function loginRequest(email, password) {
  const { data } = await requestJson(`${API_BASE}/login`, {
    method: "POST",
    body: { email, password },
  });
  return data;
}

// Poziva API register endpoint.
export async function registerRequest(fullName, email, password, confirmPassword) {
  const { data } = await requestJson(`${API_BASE}/register`, {
    method: "POST",
    body: { fullName, email, password, confirmPassword },
  });
  return data;
}

// Poziva API logout endpoint uz CSRF token.
export async function logoutRequest(csrfToken) {
  const { data } = await requestJson(`${API_BASE}/logout`, {
    method: "POST",
    csrfToken,
  });
  return data;
}

// Dohvaca aktivne termine za raspored.
export async function getSessionsRequest() {
  const { data } = await requestJson(`${API_BASE}/sessions`);
  return data;
}

// Salje rezervaciju odabranog termina korisniku.
export async function reserveRequest(sessionId, sessionInfo, csrfToken) {
  const { data } = await requestJson(`${API_BASE}/reserve`, {
    method: "POST",
    csrfToken,
    body: { sessionId, sessionInfo },
  });
  return data;
}

// Dohvaca admin podatke potrebne za CRUD rezervacija.
export async function getAdminReservationsRequest() {
  const { data } = await requestJson(`${API_BASE}/admin/reservations`);
  return data;
}

// Kreira novu rezervaciju iz admin panela.
export async function createAdminReservationRequest(userId, sessionId, csrfToken) {
  const { data } = await requestJson(`${API_BASE}/admin/reservations`, {
    method: "POST",
    csrfToken,
    body: { userId, sessionId },
  });
  return data;
}

// Azurira postojecu rezervaciju iz admin panela.
export async function updateAdminReservationRequest(id, userId, sessionId, csrfToken) {
  const { data } = await requestJson(`${API_BASE}/admin/reservations`, {
    method: "PUT",
    csrfToken,
    body: { id, userId, sessionId },
  });
  return data;
}

// Brise rezervaciju iz admin panela.
export async function deleteAdminReservationRequest(id, csrfToken) {
  const { data } = await requestJson(`${API_BASE}/admin/reservations`, {
    method: "DELETE",
    csrfToken,
    body: { id },
  });
  return data;
}


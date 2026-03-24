import {
  createAdminReservationRequest,
  deleteAdminReservationRequest,
  getAdminReservationsRequest,
  updateAdminReservationRequest,
} from "./api.js";

const state = {
  reservations: [],
  users: [],
  sessions: [],
};

const refs = {
  form: document.getElementById("reservation-form"),
  feedback: document.getElementById("feedback"),
  reservationId: document.getElementById("reservation-id"),
  userSelect: document.getElementById("user-id"),
  sessionSelect: document.getElementById("session-id"),
  formTitle: document.getElementById("form-title"),
  formHint: document.getElementById("form-hint"),
  submitButton: document.getElementById("submit-button"),
  cancelButton: document.getElementById("cancel-button"),
  count: document.getElementById("reservation-count"),
  tableBody: document.getElementById("reservations-body"),
  emptyState: document.getElementById("empty-state"),
  loadingState: document.getElementById("loading-state"),
};

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || "";

function escapeHtml(value) {
  return String(value ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#39;");
}

function setFeedback(message, type = "info") {
  if (!refs.feedback) return;

  refs.feedback.hidden = false;
  refs.feedback.className = `feedback feedback-${type}`;
  refs.feedback.textContent = message;
}

function clearFeedback() {
  if (!refs.feedback) return;

  refs.feedback.hidden = true;
  refs.feedback.className = "feedback";
  refs.feedback.textContent = "";
}

function setLoading(isLoading) {
  if (refs.loadingState) {
    refs.loadingState.hidden = !isLoading;
  }

  if (refs.form) {
    const fields = refs.form.querySelectorAll("select, button");
    fields.forEach((field) => {
      field.disabled = isLoading;
    });
  }
}

function updateCounter() {
  if (refs.count) {
    refs.count.textContent = String(state.reservations.length);
  }
}

function buildSessionOptionLabel(session) {
  const timeFrom = typeof session.time_from === "string" ? session.time_from.slice(0, 5) : "";
  const timeTo = typeof session.time_to === "string" ? session.time_to.slice(0, 5) : "";
  const parts = [`${session.day || ""} ${timeFrom} - ${timeTo}`.trim()];

  if (session.type || session.coach) {
    parts.push(`(${[session.type, session.coach].filter(Boolean).join(", ")})`);
  }

  if (Number(session.active) !== 1) {
    parts.push("[neaktivan]");
  }

  return parts.join(" ");
}

function renderUserOptions(selectedUserId = "") {
  if (!refs.userSelect) return;

  const options = ['<option value="">Odaberi korisnika</option>'];
  state.users.forEach((user) => {
    const selected = String(user.id) === String(selectedUserId) ? " selected" : "";
    const adminSuffix = Number(user.is_admin) === 1 ? " [admin]" : "";
    options.push(
      `<option value="${escapeHtml(user.id)}"${selected}>${escapeHtml(user.full_name)} (${escapeHtml(
        user.email
      )})${adminSuffix}</option>`
    );
  });

  refs.userSelect.innerHTML = options.join("");
}

function renderSessionOptions(selectedSessionId = "") {
  if (!refs.sessionSelect) return;

  const options = ['<option value="">Odaberi termin</option>'];
  state.sessions.forEach((session) => {
    const selected = String(session.id) === String(selectedSessionId) ? " selected" : "";
    options.push(
      `<option value="${escapeHtml(session.id)}"${selected}>${escapeHtml(
        buildSessionOptionLabel(session)
      )}</option>`
    );
  });

  refs.sessionSelect.innerHTML = options.join("");
}

function renderReservations() {
  if (!refs.tableBody || !refs.emptyState) return;

  if (state.reservations.length === 0) {
    refs.tableBody.innerHTML = "";
    refs.emptyState.hidden = false;
    return;
  }

  refs.emptyState.hidden = true;
  refs.tableBody.innerHTML = state.reservations
    .map((reservation) => {
      return `
        <tr>
          <td class="col-id" data-label="ID">${escapeHtml(reservation.id)}</td>
          <td class="col-user" data-label="Korisnik">${escapeHtml(reservation.full_name)}</td>
          <td class="col-email" data-label="Email">${escapeHtml(reservation.email)}</td>
          <td class="col-session" data-label="Termin">${escapeHtml(reservation.session_label)}</td>
          <td class="col-date" data-label="Kreirano">${escapeHtml(reservation.created_at)}</td>
          <td class="col-actions" data-label="Akcije">
            <div class="actions">
              <button type="button" class="btn btn-secondary" data-action="edit" data-id="${escapeHtml(
                reservation.id
              )}">
                Uredi
              </button>
              <button type="button" class="btn btn-danger" data-action="delete" data-id="${escapeHtml(
                reservation.id
              )}">
                Obrisi
              </button>
            </div>
          </td>
        </tr>
      `;
    })
    .join("");
}

function setCreateMode() {
  if (refs.formTitle) refs.formTitle.textContent = "Nova rezervacija";
  if (refs.formHint) refs.formHint.textContent = "Odaberi korisnika i termin za kreiranje rezervacije.";
  if (refs.submitButton) refs.submitButton.textContent = "Kreiraj rezervaciju";
  if (refs.cancelButton) refs.cancelButton.hidden = true;
  if (refs.reservationId) refs.reservationId.value = "";
  renderUserOptions();
  renderSessionOptions();
}

function setEditMode(reservation) {
  if (!reservation) return;

  if (refs.formTitle) refs.formTitle.textContent = `Uredi rezervaciju #${reservation.id}`;
  if (refs.formHint) refs.formHint.textContent = "Promijeni korisnika ili termin pa spremi izmjene.";
  if (refs.submitButton) refs.submitButton.textContent = "Spremi izmjene";
  if (refs.cancelButton) refs.cancelButton.hidden = false;
  if (refs.reservationId) refs.reservationId.value = String(reservation.id);
  renderUserOptions(reservation.user_id);
  renderSessionOptions(reservation.session_id);
}

function resetForm() {
  refs.form?.reset();
  setCreateMode();
  clearFeedback();
}

async function refreshData() {
  setLoading(true);

  try {
    const data = await getAdminReservationsRequest();
    if (!data.success) {
      throw new Error(data.message || "Ne mogu ucitati admin rezervacije.");
    }

    state.reservations = Array.isArray(data.reservations) ? data.reservations : [];
    state.users = Array.isArray(data.users) ? data.users : [];
    state.sessions = Array.isArray(data.sessions) ? data.sessions : [];

    updateCounter();
    renderUserOptions();
    renderSessionOptions();
    renderReservations();
  } catch (error) {
    console.error(error);
    setFeedback(error.message || "Dogodila se greska pri ucitavanju podataka.", "error");
  } finally {
    setLoading(false);
  }
}

async function handleSubmit(event) {
  event.preventDefault();
  clearFeedback();

  const reservationId = Number.parseInt(refs.reservationId?.value || "", 10) || 0;
  const userId = Number.parseInt(refs.userSelect?.value || "", 10) || 0;
  const sessionId = Number.parseInt(refs.sessionSelect?.value || "", 10) || 0;

  if (!userId || !sessionId) {
    setFeedback("Korisnik i termin su obavezni.", "error");
    return;
  }

  if (!csrfToken) {
    setFeedback("CSRF token nije dostupan. Osvjezi stranicu i pokusaj ponovo.", "error");
    return;
  }

  setLoading(true);

  try {
    const data = reservationId
      ? await updateAdminReservationRequest(reservationId, userId, sessionId, csrfToken)
      : await createAdminReservationRequest(userId, sessionId, csrfToken);

    if (!data.success) {
      throw new Error(data.message || "CRUD operacija nije uspjela.");
    }

    await refreshData();
    resetForm();
    setFeedback(data.message || "Promjena je spremljena.", "success");
  } catch (error) {
    console.error(error);
    setFeedback(error.message || "Promjena nije spremljena.", "error");
  } finally {
    setLoading(false);
  }
}

function handleEdit(reservationId) {
  const reservation = state.reservations.find((item) => Number(item.id) === Number(reservationId));
  if (!reservation) {
    setFeedback("Rezervacija nije pronadjena.", "error");
    return;
  }

  clearFeedback();
  setEditMode(reservation);
  refs.form?.scrollIntoView({ behavior: "smooth", block: "start" });
}

async function handleDelete(reservationId) {
  const reservation = state.reservations.find((item) => Number(item.id) === Number(reservationId));
  if (!reservation) {
    setFeedback("Rezervacija nije pronadjena.", "error");
    return;
  }

  const confirmed = window.confirm(
    `Obrisi rezervaciju #${reservation.id} za ${reservation.full_name}?`
  );
  if (!confirmed) return;

  if (!csrfToken) {
    setFeedback("CSRF token nije dostupan. Osvjezi stranicu i pokusaj ponovo.", "error");
    return;
  }

  setLoading(true);

  try {
    const data = await deleteAdminReservationRequest(reservationId, csrfToken);
    if (!data.success) {
      throw new Error(data.message || "Brisanje nije uspjelo.");
    }

    await refreshData();
    resetForm();
    setFeedback(data.message || "Rezervacija je obrisana.", "success");
  } catch (error) {
    console.error(error);
    setFeedback(error.message || "Brisanje nije uspjelo.", "error");
  } finally {
    setLoading(false);
  }
}

function handleTableClick(event) {
  const target = event.target;
  if (!(target instanceof Element)) return;

  const button = target.closest("button[data-action][data-id]");
  if (!(button instanceof HTMLButtonElement)) return;

  const action = button.dataset.action;
  const reservationId = Number.parseInt(button.dataset.id || "", 10) || 0;

  if (action === "edit") {
    handleEdit(reservationId);
    return;
  }

  if (action === "delete") {
    handleDelete(reservationId);
  }
}

function init() {
  refs.form?.addEventListener("submit", handleSubmit);
  refs.cancelButton?.addEventListener("click", resetForm);
  refs.tableBody?.addEventListener("click", handleTableClick);
  resetForm();
  refreshData();
}

init();


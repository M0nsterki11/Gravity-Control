import { getSessionsRequest, reserveRequest } from "./api.js";
import { clearStoredUser, getCsrfToken, getCurrentUser } from "./state/session.js";
import { showToast } from "./ui/toast.js";

export async function populateSchedule({ onRequireLogin, onSessionInvalid } = {}) {
  const scheduleBody = document.getElementById("schedule-body");
  if (!scheduleBody) return;

  scheduleBody.innerHTML = "";

  try {
    const data = await getSessionsRequest();
    if (!data.success) {
      showToast(data.message || "Ne mogu ucitati termine.", "error");
      return;
    }

    const sessions = data.sessions || [];
    sessions.forEach((session) => {
      const tr = document.createElement("tr");

      const day = session.day ?? "";
      const type = session.type ?? "";
      const coach = session.coach ?? "";
      const timeFrom = typeof session.time_from === "string" ? session.time_from.slice(0, 5) : "";
      const timeTo = typeof session.time_to === "string" ? session.time_to.slice(0, 5) : "";
      const timeLabel = `${timeFrom} - ${timeTo}`;
      const sessionLabel = `${day} ${timeLabel}`;

      const tdDay = document.createElement("td");
      tdDay.textContent = day;
      tr.appendChild(tdDay);

      const tdTime = document.createElement("td");
      tdTime.textContent = timeLabel;
      tr.appendChild(tdTime);

      const tdType = document.createElement("td");
      tdType.textContent = type;
      tr.appendChild(tdType);

      const tdCoach = document.createElement("td");
      tdCoach.textContent = coach;
      tr.appendChild(tdCoach);

      const tdAction = document.createElement("td");
      const reserveBtn = document.createElement("button");
      reserveBtn.type = "button";
      reserveBtn.className = "btn btn-outline btn-small";
      reserveBtn.dataset.session = sessionLabel;
      reserveBtn.dataset.sessionId = String(session.id ?? "");
      reserveBtn.textContent = "Rezerviraj";
      tdAction.appendChild(reserveBtn);
      tr.appendChild(tdAction);

      scheduleBody.appendChild(tr);
    });

    scheduleBody.addEventListener("click", async (event) => {
      const target = event.target;
      if (!(target instanceof Element) || !target.matches("button[data-session]")) {
        return;
      }

      const currentUser = getCurrentUser();
      if (!currentUser) {
        showToast("Za rezervaciju se moras prvo ulogirati.", "error");
        if (typeof onRequireLogin === "function") onRequireLogin();
        return;
      }

      const sessionInfo = target.getAttribute("data-session") || "";
      const sessionId = parseInt(target.getAttribute("data-session-id") || "", 10) || 0;
      const csrfToken = getCsrfToken();

      if (!csrfToken) {
        clearStoredUser();
        showToast("Sesija je istekla. Prijavi se ponovno.", "error");
        if (typeof onSessionInvalid === "function") onSessionInvalid();
        return;
      }

      try {
        const data = await reserveRequest(sessionId, sessionInfo, csrfToken);
        if (!data.success) {
          const message = data.message || "Greska kod rezervacije.";
          if (/csrf|prijavljen/i.test(message)) {
            clearStoredUser();
            if (typeof onSessionInvalid === "function") onSessionInvalid();
          }
          showToast(message, "error");
          return;
        }

        showToast(`Rezervacija potvrdjena za: ${sessionInfo}`, "success");
      } catch (error) {
        console.error(error);
        showToast("Doslo je do greske kod rezervacije.", "error");
      }
    });
  } catch (error) {
    console.error(error);
    showToast("Ne mogu se spojiti na server za termine.", "error");
  }
}

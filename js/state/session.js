const STORAGE_KEY = "gravityUser";
let currentUser = null;

// Ucitava korisnika iz localStorage u runtime state.
export function loadUserFromStorage() {
  const savedUser = localStorage.getItem(STORAGE_KEY);
  if (!savedUser) {
    currentUser = null;
    return;
  }

  try {
    currentUser = JSON.parse(savedUser);
  } catch {
    currentUser = null;
  }
}

// Vraca trenutno ucitanog korisnika iz memorije.
export function getCurrentUser() {
  return currentUser;
}

// Sprema korisnika u memoriju i localStorage.
export function setCurrentUser(user) {
  currentUser = user;
  localStorage.setItem(STORAGE_KEY, JSON.stringify(user));
}

// Brise korisnika iz memorije i localStorage.
export function clearStoredUser() {
  currentUser = null;
  localStorage.removeItem(STORAGE_KEY);
}

// Vraca CSRF token iz spremljenog korisnika ili prazan string.
export function getCsrfToken() {
  if (!currentUser || typeof currentUser.csrf_token !== "string") {
    return "";
  }

  return currentUser.csrf_token.trim();
}

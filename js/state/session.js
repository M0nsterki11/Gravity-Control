const STORAGE_KEY = "gravityUser";
let currentUser = null;

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

export function getCurrentUser() {
  return currentUser;
}

export function setCurrentUser(user) {
  currentUser = user;
  localStorage.setItem(STORAGE_KEY, JSON.stringify(user));
}

export function clearStoredUser() {
  currentUser = null;
  localStorage.removeItem(STORAGE_KEY);
}

export function getCsrfToken() {
  if (!currentUser || typeof currentUser.csrf_token !== "string") {
    return "";
  }

  return currentUser.csrf_token.trim();
}

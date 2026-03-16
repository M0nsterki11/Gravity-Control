// Aktivira modal dodavanjem CSS klase.
export function openModal(modalEl) {
  if (!modalEl) return;
  modalEl.classList.add("active");
}

// Zatvara modal uklanjanjem CSS klase.
export function closeModal(modalEl) {
  if (!modalEl) return;
  modalEl.classList.remove("active");
}

// Vraca funkciju koja prebacuje aktivni tab i pripadajucu formu.
export function createTabSwitcher(tabButtons, forms) {
  return function switchTab(tabId) {
    tabButtons.forEach((btn) => {
      btn.classList.toggle("active", btn.dataset.tab === tabId);
    });
    forms.forEach((form) => {
      form.classList.toggle("active", form.id === tabId);
    });
  };
}

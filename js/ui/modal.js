export function openModal(modalEl) {
  if (!modalEl) return;
  modalEl.classList.add("active");
}

export function closeModal(modalEl) {
  if (!modalEl) return;
  modalEl.classList.remove("active");
}

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

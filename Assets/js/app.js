document.addEventListener('click', (event) => {
  const card = event.target.closest('[data-service-card]');
  if (card) {
    const radio = card.querySelector('input[type="radio"]');
    if (radio) radio.checked = true;
  }
});

document.addEventListener('change', async (event) => {
  if (event.target.matches('[data-secretary-action-select]')) {
    const action = event.target.value;
    const actionCell = event.target.closest('.table-actions');
    const actionTarget = actionCell?.querySelector(`[data-secretary-action-form="${action}"]`);
    event.target.value = '';

    if (!actionTarget) return;
    if (action === 'reschedule') {
      actionTarget.click();
      return;
    }
    actionTarget.requestSubmit();
    return;
  }

  const autoFilterForm = event.target.closest('[data-auto-filter-form]');
  if (autoFilterForm && event.target.matches('input, select')) {
    // Admin appointment filters submit on change, so date/service/status combine through GET params.
    autoFilterForm.requestSubmit();
    return;
  }

  if (event.target.matches('[data-date-input]')) {
    const wrapper = document.querySelector('[data-slots-root]');
    if (!wrapper) return;

    const serviceId = wrapper.dataset.serviceId;
    const date = event.target.value;
    const url = `${wrapper.dataset.endpoint}?service_id=${encodeURIComponent(serviceId)}&date=${encodeURIComponent(date)}`;
    const response = await fetch(url);
    const data = await response.json();

    const slotsWrap = wrapper.querySelector('[data-slots]');
    const messageWrap = wrapper.querySelector('[data-slots-message]');
    slotsWrap.innerHTML = '';
    messageWrap.textContent = data.message || '';

    data.slots.forEach((slot) => {
      const label = document.createElement('label');
      label.className = `slot-chip ${slot.available ? '' : 'disabled'}`;
      label.innerHTML = `
        <input type="radio" name="start_time" value="${slot.start}" ${slot.available ? '' : 'disabled'}>
        <span>${slot.label}</span>
        <small>${slot.available ? 'Available' : 'Booked'}</small>
      `;
      slotsWrap.appendChild(label);
    });
  }

  if (event.target.matches('[data-booking-date]')) {
    const form = event.target.closest('[data-booking-form]');
    if (!form) return;
    const serviceInput = form.querySelector('[data-booking-service-input]');
    const doctorInput = form.querySelector('[data-booking-doctor-input]');
    const dateHidden = form.querySelector('[data-booking-date-hidden]');
    const timeSelect = form.querySelector('[data-booking-time-select]');
    const messageWrap = form.querySelector('[data-booking-slots-message]');
    const slotCountWrap = form.querySelector('[data-booking-slot-count]');
    const date = event.target.value;
    dateHidden.value = date;
    form.querySelector('[data-booking-time-hidden]').value = '';
    if (timeSelect) timeSelect.innerHTML = '<option value="">Select a time slot</option>';

    if (!serviceInput.value || !doctorInput.value) {
      messageWrap.textContent = 'Choose a service and doctor first.';
      if (slotCountWrap) slotCountWrap.textContent = 'Choose a date to see available slots.';
      return;
    }

    // Schedule handling: slots are generated for the selected doctor, service, and date.
    const url = `${form.dataset.endpoint}?service_id=${encodeURIComponent(serviceInput.value)}&doctor_id=${encodeURIComponent(doctorInput.value)}&date=${encodeURIComponent(date)}`;
    const response = await fetch(url);
    const data = await response.json();
    messageWrap.textContent = data.message || '';
    const availableSlots = data.slots.filter((slot) => slot.available);
    if (slotCountWrap) {
      slotCountWrap.textContent = `${availableSlots.length} slot${availableSlots.length === 1 ? '' : 's'} available on this day.`;
    }
    availableSlots.forEach((slot) => {
      const option = document.createElement('option');
      option.value = slot.start;
      option.textContent = slot.label;
      timeSelect.appendChild(option);
    });
  }

  if (event.target.matches('[data-booking-time-select]')) {
    const form = event.target.closest('[data-booking-form]');
    if (!form) return;
    form.querySelector('[data-booking-time-hidden]').value = event.target.value;
  }

  if (event.target.matches('[data-booking-doctor-select]')) {
    const form = event.target.closest('[data-booking-form]');
    if (!form) return;
    const selected = event.target.selectedOptions[0];
    const doctorId = event.target.value;
    form.querySelector('[data-booking-doctor-input]').value = doctorId;
    form.querySelector('[data-booking-date-hidden]').value = '';
    form.querySelector('[data-booking-time-hidden]').value = '';
    const dateInput = form.querySelector('[data-booking-date]');
    const timeSelect = form.querySelector('[data-booking-time-select]');
    if (dateInput) dateInput.value = '';
    if (timeSelect) timeSelect.innerHTML = '<option value="">Select a time slot</option>';
    form.querySelector('[data-booking-doctor]').textContent = selected?.dataset.doctorName || 'Choose a doctor';
    form.querySelector('[data-booking-schedule]').innerHTML = renderScheduleHtml(selected?.dataset.scheduleText || 'Doctor availability will appear here.');
    form.querySelector('[data-booking-summary-doctor]').textContent = selected?.dataset.doctorName || 'Choose a doctor';
    const slotCountWrap = form.querySelector('[data-booking-slot-count]');
    const slotsMessage = form.querySelector('[data-booking-slots-message]');
    if (slotCountWrap) slotCountWrap.textContent = 'Choose a date to see available slots.';
    if (slotsMessage) slotsMessage.textContent = doctorId ? 'Choose a date to load available slots.' : 'Choose a doctor first.';
  }

  if (event.target.matches('[data-patient-reschedule-date]')) {
    const form = event.target.closest('[data-patient-reschedule-form]');
    if (!form) return;

    const serviceId = form.querySelector('[data-patient-reschedule-service]')?.value || '';
    const doctorId = form.querySelector('[data-patient-reschedule-doctor]')?.value || '';
    const timeValue = form.querySelector('[data-patient-reschedule-time-value]');
    const timeLabel = form.querySelector('[data-patient-time-picker-label]');
    const timePanel = form.querySelector('[data-patient-time-picker-panel]');
    const message = form.querySelector('[data-patient-reschedule-message]');
    const date = event.target.value;

    if (timeValue) timeValue.value = '';
    if (timeLabel) timeLabel.textContent = 'Loading available times...';
    if (timePanel) timePanel.innerHTML = '';
    const url = `${form.dataset.endpoint}?service_id=${encodeURIComponent(serviceId)}&doctor_id=${encodeURIComponent(doctorId)}&date=${encodeURIComponent(date)}`;
    const response = await fetch(url);
    const data = await response.json();
    const availableSlots = (data.slots || []).filter((slot) => slot.available);

    if (timePanel) {
      availableSlots.forEach((slot) => {
        const option = document.createElement('button');
        option.className = 'time-picker-option';
        option.type = 'button';
        option.dataset.patientTimeOption = '';
        option.dataset.timeValue = slot.start;
        option.dataset.timeLabel = slot.label;
        option.textContent = slot.label;
        timePanel.appendChild(option);
      });
    }
    if (timeLabel) timeLabel.textContent = availableSlots.length ? 'Choose a time' : 'No available times';
    if (message) {
      message.textContent = availableSlots.length
        ? `${availableSlots.length} available slot${availableSlots.length === 1 ? '' : 's'} found.`
        : 'No available slots for this date.';
    }
  }
});

const profileMenuRoot = document.querySelector('[data-profile-menu]');
const profileMenuToggle = profileMenuRoot?.querySelector('[data-profile-toggle]') || null;
const profileMenuDropdown = profileMenuRoot?.querySelector('[data-profile-dropdown]') || null;
let isProfileMenuOpen = false;
const notificationMenuRoot = document.querySelector('[data-notification-menu]');
const notificationDropdown = notificationMenuRoot?.querySelector('[data-notification-dropdown]') || null;
const notificationBadge = notificationMenuRoot?.querySelector('[data-notification-badge]') || null;
const notificationList = notificationMenuRoot?.querySelector('[data-notification-list]') || null;
let isNotificationMenuOpen = false;

// Login and booking are separate modal states. Booking buttons never open login;
// login only opens from explicit login triggers or a one-time protected redirect.
let isBookingOpen = false;
let isLoginOpen = document.getElementById('loginModal')?.classList.contains('is-open') || false;
if (window.location.search.includes('login_required=1') || window.location.search.includes('login_error=1')) {
  const cleanUrl = `${window.location.pathname}${window.location.hash || ''}`;
  window.history.replaceState({}, document.title, cleanUrl);
}

function setProfileMenuOpen(nextState) {
  isProfileMenuOpen = Boolean(nextState);
  if (!profileMenuDropdown || !profileMenuToggle) return;

  if (isProfileMenuOpen) {
    profileMenuDropdown.removeAttribute('hidden');
    profileMenuToggle.setAttribute('aria-expanded', 'true');
  } else {
    profileMenuDropdown.setAttribute('hidden', 'hidden');
    profileMenuToggle.setAttribute('aria-expanded', 'false');
  }
}

setProfileMenuOpen(false);

function setNotificationMenuOpen(nextState) {
  isNotificationMenuOpen = Boolean(nextState);
  const toggle = notificationMenuRoot?.querySelector('[data-notification-toggle]');
  if (!notificationDropdown || !toggle) return;

  if (isNotificationMenuOpen) {
    notificationDropdown.removeAttribute('hidden');
    toggle.setAttribute('aria-expanded', 'true');
  } else {
    notificationDropdown.setAttribute('hidden', 'hidden');
    toggle.setAttribute('aria-expanded', 'false');
  }
}

function notificationUnreadCount() {
  return notificationList ? notificationList.querySelectorAll('.notification-item.is-unread').length : 0;
}

function updateNotificationBadge() {
  if (!notificationBadge) return;
  const count = notificationUnreadCount();
  notificationBadge.textContent = String(count);
  notificationBadge.toggleAttribute('hidden', count === 0);
}

function timeAgo(dateValue) {
  const timestamp = new Date(String(dateValue).replace(' ', 'T')).getTime();
  if (!timestamp) return dateValue || '';
  const seconds = Math.max(1, Math.floor((Date.now() - timestamp) / 1000));
  if (seconds < 60) return 'just now';
  const minutes = Math.floor(seconds / 60);
  if (minutes < 60) return `${minutes} min ago`;
  const hours = Math.floor(minutes / 60);
  if (hours < 24) return `${hours} hr ago`;
  const days = Math.floor(hours / 24);
  if (days < 30) return `${days} day${days === 1 ? '' : 's'} ago`;
  return new Date(timestamp).toLocaleDateString();
}

function refreshNotificationTimes() {
  document.querySelectorAll('[data-notification-item]').forEach((item) => {
    const time = item.querySelector('[data-notification-time]');
    if (time) time.textContent = timeAgo(item.dataset.createdAt);
  });
}

async function persistNotificationRead(action, notificationId = '') {
  if (!notificationMenuRoot?.dataset.notificationEndpoint) return;
  const body = new FormData();
  body.append('csrf_token', notificationMenuRoot.dataset.notificationCsrf || '');
  body.append('action', action);
  if (notificationId) body.append('notification_id', notificationId);
  await fetch(notificationMenuRoot.dataset.notificationEndpoint, { method: 'POST', body });
}

setNotificationMenuOpen(false);
refreshNotificationTimes();
updateNotificationBadge();

function closeTeamUserMenus(exceptMenu = null) {
  document.querySelectorAll('[data-user-menu]').forEach((menu) => {
    if (exceptMenu && menu === exceptMenu) return;
    const dropdown = menu.querySelector('[data-user-dropdown]');
    const toggle = menu.querySelector('[data-user-menu-toggle]');
    if (dropdown) dropdown.setAttribute('hidden', 'hidden');
    if (toggle) toggle.setAttribute('aria-expanded', 'false');
  });
}

const profileModal = document.querySelector('[data-profile-modal]');
const profileModalForm = profileModal?.querySelector('[data-profile-modal-form]') || null;
const profileModalView = profileModal?.querySelector('[data-profile-view]') || null;
const profileModalEditButton = profileModal?.querySelector('[data-profile-edit]') || null;
const profileModalAlert = profileModal?.querySelector('[data-profile-modal-alert]') || null;
let profileModalSnapshot = null;

// Profile modal edit state: keep a small snapshot so Cancel can restore unsaved input.
function readProfileModalForm() {
  if (!profileModalForm) return {};
  return {
    name: profileModalForm.querySelector('[name="name"]')?.value || '',
    username: profileModalForm.querySelector('[name="username"]')?.value || '',
    email: profileModalForm.querySelector('[name="email"]')?.value || '',
    contact_number: profileModalForm.querySelector('[name="contact_number"]')?.value || '',
    role_label: profileModalForm.querySelector('[name="role_label"]')?.value || '',
    service_id: profileModalForm.querySelector('[name="service_id"]')?.value || '',
    schedule: profileModalForm.querySelector('[name="schedule"]')?.value || '',
  };
}

function writeProfileModalForm(profile) {
  if (!profileModalForm) return;
  Object.entries(profile).forEach(([key, value]) => {
    const field = profileModalForm.querySelector(`[name="${key}"]`);
    if (field) field.value = value || '';
  });
  profileModalForm.querySelectorAll('[name="new_password"], [name="confirm_password"]').forEach((field) => {
    field.value = '';
  });
}

function selectedProfileServiceName() {
  const serviceSelect = profileModalForm?.querySelector('[name="service_id"]');
  return serviceSelect?.selectedOptions?.[0]?.textContent?.trim() || '';
}

function setProfileModalEditMode(isEditing) {
  if (!profileModal || !profileModalForm || !profileModalView) return;
  profileModalView.hidden = isEditing;
  profileModalForm.hidden = !isEditing;
  if (profileModalEditButton) profileModalEditButton.hidden = isEditing;
  if (profileModalAlert) profileModalAlert.hidden = true;
  if (isEditing) {
    profileModalSnapshot = readProfileModalForm();
    const role = profileModal?.dataset.profileRole || '';
    const firstEditable = role === 'doctor'
      ? profileModalForm.querySelector('[name="schedule"]')
      : profileModalForm.querySelector('[name="name"]');
    firstEditable?.focus();
  }
}

function cancelProfileModalEdit() {
  if (profileModalSnapshot) writeProfileModalForm(profileModalSnapshot);
  setProfileModalEditMode(false);
}

// Keep the modal, topbar, and sidebar labels in sync after a successful save.
function updateProfileModalView(profile) {
  if (!profileModal) return;
  const values = {
    name: profile.name || '',
    username: profile.username || '-',
    email: profile.email || '',
    contact_number: profile.contact_number || '-',
    role_label: profile.role_label || '',
    service_id: profile.service_id || '',
    service_role: profile.service_role || selectedProfileServiceName() || '-',
    schedule_display: profile.schedule_display || profile.schedule || 'No schedule assigned yet.',
  };
  values.schedule = values.schedule_display === 'No schedule assigned yet.' ? '' : values.schedule_display;
  Object.entries(values).forEach(([key, value]) => {
    const target = profileModal.querySelector(`[data-profile-value="${key}"]`);
    if (!target) return;
    if (key === 'schedule_display') {
      target.innerHTML = renderScheduleHtml(value);
    } else {
      target.textContent = value;
    }
  });
  const title = profileModal.querySelector('[data-profile-modal-title]');
  if (title) title.textContent = values.name;
  const topbarToggle = document.querySelector('[data-profile-toggle]');
  if (topbarToggle) topbarToggle.textContent = values.name;
  const sidebarName = document.querySelector('[data-sidebar-user-name]');
  if (sidebarName) sidebarName.textContent = values.name;
  writeProfileModalForm(values);
}

function validateProfileModalForm() {
  if (!profileModalForm) return 'Profile form is unavailable.';
  const role = profileModal?.dataset.profileRole || '';
  const name = profileModalForm.querySelector('[name="name"]')?.value.trim() || '';
  const username = profileModalForm.querySelector('[name="username"]')?.value.trim() || '';
  const email = profileModalForm.querySelector('[name="email"]')?.value.trim() || '';
  const contact = profileModalForm.querySelector('[name="contact_number"]')?.value.trim() || '';
  const schedule = profileModalForm.querySelector('[name="schedule"]')?.value.trim() || '';
  const newPassword = profileModalForm.querySelector('[name="new_password"]')?.value || '';
  const confirmPassword = profileModalForm.querySelector('[name="confirm_password"]')?.value || '';

  if (!name) return 'Name is required.';
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return 'A valid email is required.';
  if (!username) return 'Username is required.';
  if (role !== 'admin' && !/^\d{1,11}$/.test(contact)) return 'Contact number must contain digits only, up to 11 digits.';
  if ((role === 'doctor' || role === 'secretary') && schedule) {
    const dayPattern = 'Mon(?:day)?|Tue(?:s|sday|day)?|Wed(?:nesday)?|Thu(?:r|rs|rsday|rday|day)?|Fri(?:day)?|Sat(?:urday)?|Sun(?:day)?';
    const schedulePattern = new RegExp(`^(${dayPattern})(?:\\s*(?:to|-)\\s*(${dayPattern}))?\\s*\\|?\\s*\\d{1,2}:\\d{2}\\s*[AP]M\\s*-\\s*\\d{1,2}:\\d{2}\\s*[AP]M$`, 'i');
    const validSchedule = schedule.split(/[\r\n,]+/).map((part) => part.trim()).filter(Boolean).every((part) => schedulePattern.test(part));
    if (!validSchedule) return 'Office schedule must use format like Monday 9:00 AM - 5:00 PM.';
  }
  if ((newPassword || confirmPassword) && newPassword.length < 8) return 'New password must be at least 8 characters.';
  if (newPassword !== confirmPassword) return 'Password confirmation does not match.';
  return '';
}

document.addEventListener('click', (event) => {
  const openModal = (id) => {
    const modal = document.getElementById(id);
    if (modal) {
      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden', 'false');
      if (id === 'loginModal') isLoginOpen = true;
      if (id === 'bookingModal') isBookingOpen = true;
    }
  };

  const closeModal = (id) => {
    const modal = document.getElementById(id);
    if (modal) {
      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden', 'true');
      if (id === 'loginModal') isLoginOpen = false;
      if (id === 'bookingModal') isBookingOpen = false;
    }
  };

  const profileToggle = event.target.closest('[data-profile-toggle]');
  if (profileToggle && profileMenuRoot?.contains(profileToggle)) {
    setProfileMenuOpen(!isProfileMenuOpen);
    setNotificationMenuOpen(false);
  } else if (isProfileMenuOpen && profileMenuRoot && !profileMenuRoot.contains(event.target)) {
    setProfileMenuOpen(false);
  }

  const notificationToggle = event.target.closest('[data-notification-toggle]');
  if (notificationToggle && notificationMenuRoot?.contains(notificationToggle)) {
    // Facebook-style behavior: bell toggles the panel, outside clicks close it.
    setNotificationMenuOpen(!isNotificationMenuOpen);
    setProfileMenuOpen(false);
  } else if (isNotificationMenuOpen && notificationMenuRoot && !notificationMenuRoot.contains(event.target)) {
    setNotificationMenuOpen(false);
  }

  const markAllNotifications = event.target.closest('[data-notification-mark-all]');
  if (markAllNotifications && notificationMenuRoot?.contains(markAllNotifications)) {
    notificationList?.querySelectorAll('.notification-item').forEach((item) => item.classList.remove('is-unread'));
    updateNotificationBadge();
    persistNotificationRead('mark_all_read').catch(() => {});
  }

  const notificationItem = event.target.closest('[data-notification-item]');
  if (notificationItem && notificationMenuRoot?.contains(notificationItem)) {
    notificationItem.classList.remove('is-unread');
    updateNotificationBadge();
    persistNotificationRead('mark_read', notificationItem.dataset.notificationId || '').catch(() => {});
  }

  const timePickerToggle = event.target.closest('[data-patient-time-picker-toggle]');
  if (timePickerToggle) {
    const picker = timePickerToggle.closest('[data-patient-time-picker]');
    const panel = picker?.querySelector('[data-patient-time-picker-panel]');
    const expanded = timePickerToggle.getAttribute('aria-expanded') === 'true';
    panel?.toggleAttribute('hidden', expanded);
    timePickerToggle.setAttribute('aria-expanded', String(!expanded));
    return;
  }

  const timeOption = event.target.closest('[data-patient-time-option]');
  if (timeOption) {
    const picker = timeOption.closest('[data-patient-time-picker]');
    const form = timeOption.closest('[data-patient-reschedule-form]');
    const panel = picker?.querySelector('[data-patient-time-picker-panel]');
    const toggle = picker?.querySelector('[data-patient-time-picker-toggle]');
    const label = picker?.querySelector('[data-patient-time-picker-label]');
    const value = form?.querySelector('[data-patient-reschedule-time-value]');

    picker?.querySelectorAll('[data-patient-time-option]').forEach((option) => option.classList.remove('is-selected'));
    timeOption.classList.add('is-selected');
    if (value) value.value = timeOption.dataset.timeValue || '';
    if (label) label.textContent = timeOption.dataset.timeLabel || timeOption.textContent.trim();
    panel?.setAttribute('hidden', 'hidden');
    toggle?.setAttribute('aria-expanded', 'false');
    return;
  }

  document.querySelectorAll('[data-patient-time-picker]').forEach((picker) => {
    if (picker.contains(event.target)) return;
    picker.querySelector('[data-patient-time-picker-panel]')?.setAttribute('hidden', 'hidden');
    picker.querySelector('[data-patient-time-picker-toggle]')?.setAttribute('aria-expanded', 'false');
  });

  const userMenuToggle = event.target.closest('[data-user-menu-toggle]');
  if (userMenuToggle) {
    const menu = userMenuToggle.closest('[data-user-menu]');
    const dropdown = menu?.querySelector('[data-user-dropdown]');
    const isOpen = dropdown && !dropdown.hasAttribute('hidden');
    closeTeamUserMenus(menu);
    if (dropdown) {
      if (isOpen) {
        dropdown.setAttribute('hidden', 'hidden');
        userMenuToggle.setAttribute('aria-expanded', 'false');
      } else {
        dropdown.removeAttribute('hidden');
        userMenuToggle.setAttribute('aria-expanded', 'true');
      }
    }
  } else if (!event.target.closest('[data-user-menu]')) {
    closeTeamUserMenus();
  }

  const toggle = event.target.closest('[data-sidebar-toggle]');
  if (toggle) {
    const sidebar = document.getElementById('appSidebar');
    if (sidebar) sidebar.classList.toggle('is-open');
  }

  const navToggle = event.target.closest('[data-nav-toggle]');
  if (navToggle) {
    const nav = document.getElementById('siteNav');
    if (nav) nav.classList.toggle('is-open');
  }

  const loginOpen = event.target.closest('[data-login-open]');
  if (loginOpen) {
    closeModal('registerModal');
    openModal('loginModal');
  }

  const loginClose = event.target.closest('[data-login-close]');
  if (loginClose) {
    closeModal('loginModal');
  }

  const bookingOpen = event.target.closest('[data-booking-open]');
  if (bookingOpen) {
    event.preventDefault();
    // Guest booking is allowed: booking opens the booking modal directly.
    openModal('bookingModal');
  }

  const bookingClose = event.target.closest('[data-booking-close]');
  if (bookingClose) {
    closeModal('bookingModal');
  }

  const genericOpen = event.target.closest('[data-modal-open]');
  if (genericOpen) {
    openModal(genericOpen.dataset.modalOpen);
    closeTeamUserMenus();
    setProfileMenuOpen(false);
    setNotificationMenuOpen(false);
    if (genericOpen.dataset.modalOpen === 'profileModal') {
      setProfileModalEditMode(false);
    }
  }

  const genericClose = event.target.closest('[data-modal-close]');
  if (genericClose) {
    const modal = genericClose.closest('.auth-modal');
    if (modal?.id) {
      closeModal(modal.id);
      if (modal.id === 'profileModal') cancelProfileModalEdit();
    }
  }

  const profileEdit = event.target.closest('[data-profile-edit]');
  if (profileEdit) {
    setProfileModalEditMode(true);
  }

  const profileCancel = event.target.closest('[data-profile-cancel]');
  if (profileCancel) {
    cancelProfileModalEdit();
  }

  const registerOpen = event.target.closest('[data-register-open]');
  if (registerOpen) {
    closeModal('loginModal');
    openModal('registerModal');
  }

  const registerClose = event.target.closest('[data-register-close]');
  if (registerClose) {
    closeModal('registerModal');
  }

  const passwordToggle = event.target.closest('[data-password-toggle]');
  if (passwordToggle) {
    const wrap = passwordToggle.closest('.password-field-wrap');
    const input = wrap?.querySelector('[data-password-input]');
    if (input) {
      const showing = input.type === 'text';
      input.type = showing ? 'password' : 'text';
      passwordToggle.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
      passwordToggle.classList.toggle('is-active', !showing);
    }
  }
});

document.addEventListener('submit', (event) => {
  const acceptForm = event.target.closest('[data-accept-appointment-form]');
  if (!acceptForm) return;

  // Secretary status flow: the only table action is Pending -> Accepted.
  if (!window.confirm('Confirm accepting this appointment?')) {
    event.preventDefault();
    return;
  }

  const button = acceptForm.querySelector('[data-accept-button]');
  if (button) {
    button.disabled = true;
    button.textContent = 'Accepting...';
  }
});

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape') {
    closeTeamUserMenus();
    cancelProfileModalEdit();
    document.querySelectorAll('.auth-modal.is-open').forEach((modal) => {
      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden', 'true');
    });
    isLoginOpen = false;
    isBookingOpen = false;
  }
});

document.addEventListener('click', (event) => {
  const serviceButton = event.target.closest('[data-booking-service]');
  if (serviceButton) {
    const form = serviceButton.closest('[data-booking-form]');
    if (!form) return;
    form.querySelectorAll('[data-booking-service]').forEach((button) => button.classList.remove('is-selected'));
    serviceButton.classList.add('is-selected');
    form.querySelector('[data-booking-service-input]').value = serviceButton.dataset.serviceId;
    form.querySelector('[data-booking-doctor-input]').value = '';
    form.querySelector('[data-booking-price-input]').value = serviceButton.dataset.servicePrice || '';
    form.querySelector('[data-booking-date-hidden]').value = '';
    form.querySelector('[data-booking-time-hidden]').value = '';
    const dateInput = form.querySelector('[data-booking-date]');
    const timeSelect = form.querySelector('[data-booking-time-select]');
    const doctorSelect = form.querySelector('[data-booking-doctor-select]');
    const slotCountWrap = form.querySelector('[data-booking-slot-count]');
    const slotsMessage = form.querySelector('[data-booking-slots-message]');
    if (dateInput) dateInput.value = '';
    if (timeSelect) timeSelect.innerHTML = '<option value="">Select a time slot</option>';
    if (doctorSelect) doctorSelect.innerHTML = '<option value="">Loading assigned doctors...</option>';
    if (slotCountWrap) slotCountWrap.textContent = 'Choose a date to see available slots.';
    if (slotsMessage) slotsMessage.textContent = 'Choose a doctor and date to load available slots.';
    form.querySelector('[data-booking-doctor]').textContent = 'Choose a doctor';
    form.querySelector('[data-booking-schedule]').innerHTML = renderScheduleHtml('Doctor availability will appear here.');
    form.querySelector('[data-booking-summary-service]').textContent = serviceButton.dataset.serviceName;
    form.querySelector('[data-booking-summary-price]').textContent = `PHP ${Number(serviceButton.dataset.servicePrice || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    form.querySelector('[data-booking-summary-doctor]').textContent = 'Choose a doctor';

    // Service -> doctor filtering: load only doctors assigned to the selected service.
    fetch(`${form.dataset.endpoint}?service_id=${encodeURIComponent(serviceButton.dataset.serviceId)}`)
      .then((response) => response.json())
      .then((data) => {
        if (!doctorSelect) return;
        doctorSelect.innerHTML = '<option value="">Select assigned doctor</option>';
        (data.doctors || []).forEach((doctor) => {
          const option = document.createElement('option');
          option.value = doctor.id;
          option.textContent = doctor.name;
          option.dataset.doctorName = doctor.name;
          option.dataset.scheduleText = doctor.schedule_text || '';
          doctorSelect.appendChild(option);
        });
        if (!(data.doctors || []).length) {
          doctorSelect.innerHTML = '<option value="">No doctors assigned</option>';
        }
      });
  }

  const nextButton = event.target.closest('[data-booking-next]');
  if (nextButton) {
    const form = nextButton.closest('[data-booking-form]');
    const currentStep = Number(form.dataset.step || '1');
    const serviceSelected = !!form.querySelector('[data-booking-service-input]').value;
    const doctorSelected = !!form.querySelector('[data-booking-doctor-input]').value;
    const dateSelected = !!form.querySelector('[data-booking-date-hidden]').value;
    const timeSelected = !!form.querySelector('[data-booking-time-hidden]').value;

    if (currentStep === 1 && !serviceSelected) return;
    if (currentStep === 2 && (!doctorSelected || !dateSelected || !timeSelected)) return;
    if (currentStep === 3) {
      const requiredFields = Array.from(form.querySelectorAll('[data-booking-step="3"] [required]'));
      const valid = requiredFields.every((input) => input.value.trim() !== '') && form.querySelector('[name="email"]').checkValidity();
      if (!valid) return;
      form.querySelector('[data-booking-summary-patient]').textContent = form.querySelector('[name="full_name"]').value;
      form.querySelector('[data-booking-summary-email]').textContent = form.querySelector('[name="email"]').value;
      form.querySelector('[data-booking-summary-contact]').textContent = form.querySelector('[name="contact_number"]').value;
      form.querySelector('[data-booking-summary-date]').textContent = new Date(`${form.querySelector('[data-booking-date-hidden]').value}T00:00:00`).toLocaleDateString(undefined, { month: 'long', day: '2-digit', year: 'numeric' });
      const timeValue = form.querySelector('[data-booking-time-hidden]').value;
      form.querySelector('[data-booking-summary-time]').textContent = new Date(`1970-01-01T${timeValue}:00`).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
    }

    const nextStep = Math.min(4, currentStep + 1);
    updateBookingStep(form, nextStep);
  }

  const backButton = event.target.closest('[data-booking-back]');
  if (backButton) {
    const form = backButton.closest('[data-booking-form]');
    const currentStep = Number(form.dataset.step || '1');
    const nextStep = Math.max(1, currentStep - 1);
    updateBookingStep(form, nextStep);
  }
});

function updateBookingStep(form, step) {
  form.dataset.step = String(step);
  form.querySelectorAll('[data-booking-step]').forEach((panel) => {
    panel.classList.toggle('is-active', Number(panel.dataset.bookingStep) === step);
  });
  form.parentElement.querySelectorAll('[data-booking-progress]').forEach((item) => {
    item.classList.toggle('active', Number(item.dataset.bookingProgress) === step);
  });

  const back = form.querySelector('[data-booking-back]');
  const next = form.querySelector('[data-booking-next]');
  const submit = form.querySelector('[data-booking-submit]');
  if (back) back.hidden = step === 1;
  if (next) next.hidden = step === 4;
  if (submit) submit.hidden = step !== 4;
}

document.querySelectorAll('[data-booking-form]').forEach((form) => {
  updateBookingStep(form, Number(form.dataset.initialStep || '1'));
  if (form.querySelector('[data-booking-service-input]')?.value) {
    const serviceButton = form.querySelector(`[data-booking-service][data-service-id="${form.querySelector('[data-booking-service-input]').value}"]`);
    if (serviceButton) serviceButton.classList.add('is-selected');
  }
  form.addEventListener('submit', (event) => {
    const hasService = !!form.querySelector('[data-booking-service-input]')?.value;
    const hasDoctor = !!form.querySelector('[data-booking-doctor-input]')?.value;
    const hasDate = !!form.querySelector('[data-booking-date-hidden]')?.value;
    const hasTime = !!form.querySelector('[data-booking-time-hidden]')?.value;
    const patientFields = Array.from(form.querySelectorAll('[data-booking-step="3"] [required]'));
    const hasPatient = patientFields.every((input) => input.value.trim() && input.checkValidity());
    if (!hasService || !hasDoctor || !hasDate || !hasTime || !hasPatient) {
      event.preventDefault();
      updateBookingStep(form, !hasService ? 1 : (!hasDoctor || !hasDate || !hasTime ? 2 : 3));
    }
  });
});

const monthlyChartCanvas = document.getElementById('monthlyAppointmentsChart');
if (monthlyChartCanvas && window.Chart) {
  const loading = document.querySelector('[data-monthly-chart-loading]');
  const error = document.querySelector('[data-monthly-chart-error]');
  const endpoint = monthlyChartCanvas.dataset.chartEndpoint;

  fetch(endpoint)
    .then((response) => {
      if (!response.ok) throw new Error('Request failed');
      return response.json();
    })
    .then((rows) => {
      if (loading) loading.hidden = true;
      const labels = rows.map((row) => row.month);
      const counts = rows.map((row) => row.count);
      new Chart(monthlyChartCanvas, {
        type: 'line',
        data: {
          labels,
          datasets: [{
            label: 'Appointments',
            data: counts,
            borderColor: '#2a8be7',
            backgroundColor: 'rgba(42, 139, 231, 0.12)',
            pointBackgroundColor: '#2a8be7',
            pointBorderColor: '#ffffff',
            pointRadius: 4,
            pointHoverRadius: 5,
            fill: true,
            tension: 0.35,
            borderWidth: 3,
          }],
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              display: false,
            },
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                precision: 0,
              },
              grid: {
                color: 'rgba(217, 227, 242, 0.8)',
              },
            },
            x: {
              grid: {
                display: false,
              },
            },
          },
        },
      });
    })
    .catch(() => {
      if (loading) loading.hidden = true;
      if (error) error.hidden = false;
    });
}

const dentistSalesChartCanvas = document.querySelector('[data-dentist-sales-chart]');
if (dentistSalesChartCanvas && window.Chart) {
  const monthlySales = JSON.parse(dentistSalesChartCanvas.dataset.monthlySales || '{}');
  const monthInput = document.querySelector('[data-dentist-sales-month]');

  const dailySalesPoints = (monthKey) => {
    const [year, month] = String(monthKey || '').split('-').map(Number);
    if (!year || !month) return [];

    const daysInMonth = new Date(year, month, 0).getDate();
    const monthTotals = monthlySales[monthKey] || {};

    return Array.from({ length: daysInMonth }, (_, index) => {
      const day = index + 1;
      const dateKey = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
      return {
        day: String(day),
        total: Number(monthTotals[dateKey] || 0),
      };
    });
  };

  const selectedMonth = monthInput?.value || dentistSalesChartCanvas.dataset.selectedMonth;
  const initialPoints = dailySalesPoints(selectedMonth);

  const dentistSalesChart = new Chart(dentistSalesChartCanvas, {
    type: 'line',
    data: {
      labels: initialPoints.map((point) => point.day),
      datasets: [{
        label: 'Sales',
        data: initialPoints.map((point) => point.total),
        borderColor: '#1b9aaa',
        backgroundColor: 'rgba(27, 154, 170, 0.12)',
        pointBackgroundColor: '#1b9aaa',
        pointBorderColor: '#ffffff',
        pointRadius: 4,
        pointHoverRadius: 5,
        fill: true,
        tension: 0.35,
        borderWidth: 3,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false,
        },
        tooltip: {
          callbacks: {
            label: (context) => `Sales: ${formatPeso(context.parsed.y)}`,
          },
        },
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            callback: (value) => formatPeso(value),
          },
          grid: {
            color: 'rgba(217, 227, 242, 0.8)',
          },
        },
        x: {
          grid: {
            display: false,
          },
        },
      },
    },
  });

  monthInput?.addEventListener('change', () => {
    const points = dailySalesPoints(monthInput.value);
    dentistSalesChart.data.labels = points.map((point) => point.day);
    dentistSalesChart.data.datasets[0].data = points.map((point) => point.total);
    dentistSalesChart.update();
  });
}

const profileForm = document.querySelector('[data-profile-form]');
const passwordForm = document.querySelector('[data-password-form]');
const adminProfilesBody = document.querySelector('[data-admin-profiles-body]');
const adminUserForm = document.querySelector('.admin-user-form');

if (adminUserForm) {
  const roleSelect = adminUserForm.querySelector('[data-admin-user-role]');
  const serviceField = adminUserForm.querySelector('[data-admin-user-service-field]');
  const serviceSelect = adminUserForm.querySelector('[data-admin-user-service]');

  const toggleDoctorServiceField = () => {
    const isDoctor = roleSelect?.value === 'doctor';
    if (serviceField) serviceField.hidden = !isDoctor;
    if (serviceSelect) {
      serviceSelect.disabled = !isDoctor;
      serviceSelect.required = isDoctor;
      if (!isDoctor) serviceSelect.value = '';
    }
  };

  roleSelect?.addEventListener('change', toggleDoctorServiceField);
  toggleDoctorServiceField();
}

function showInlineAlert(element, message, isError = false) {
  if (!element) return;
  element.hidden = false;
  element.innerHTML = isError ? `<span class="error-text">${message}</span>` : message;
}

function renderScheduleHtml(schedule) {
  const lines = String(schedule || 'No schedule assigned yet.')
    .split(/\r?\n/)
    .map((line) => line.trim())
    .filter(Boolean);

  return `<span class="schedule-stack">${lines.map((line) => `<span class="schedule-line">${escapeHtml(line)}</span>`).join('')}</span>`;
}

function fillProfileForm(profile) {
  if (!profileForm) return;
  profileForm.querySelector('[name="name"]').value = profile.name || '';
  const usernameField = profileForm.querySelector('[name="username"]');
  const usernameWrap = profileForm.querySelector('.profile-username-field');
  const contactField = profileForm.querySelector('[name="contact_number"]');
  const contactWrap = profileForm.querySelector('.profile-contact-field');
  const serviceField = profileForm.querySelector('[name="service_role"]');
  const serviceWrap = profileForm.querySelector('.profile-service-role-field');
  const scheduleField = profileForm.querySelector('[name="schedule_display"]');
  const scheduleWrap = profileForm.querySelector('.profile-schedule-field');

  profileForm.querySelector('[name="email"]').value = profile.email || '';
  profileForm.querySelector('[name="role_label"]').value = profile.role_label || '';

  if (profile.role === 'patient') {
    if (usernameWrap) usernameWrap.hidden = true;
  } else {
    if (usernameWrap) usernameWrap.hidden = false;
    if (usernameField) usernameField.value = profile.username || '';
  }

  if (contactWrap) contactWrap.hidden = false;
  if (contactField) contactField.value = profile.contact_number || '';

  if (profile.role === 'doctor' || profile.role === 'secretary') {
    if (serviceWrap) serviceWrap.hidden = false;
    if (serviceField) serviceField.value = profile.service_role || '';
    if (scheduleWrap) scheduleWrap.hidden = false;
    if (scheduleField) scheduleField.value = profile.schedule_display || '';
  } else {
    if (serviceWrap) serviceWrap.hidden = true;
    if (scheduleWrap) scheduleWrap.hidden = true;
  }
}

function renderAdminProfiles(profiles) {
  if (!adminProfilesBody) return;
  const csrf = adminProfilesBody.dataset.csrf;
  adminProfilesBody.innerHTML = profiles.map((profile) => {
    const canEditSchedule = profile.role === 'doctor' || profile.role === 'secretary';
    const scheduleDisplay = profile.schedule_display || 'No schedule assigned yet.';
    return `
      <tr>
        <td>${escapeHtml(profile.name || '')}</td>
        <td>${escapeHtml(profile.role_label || '')}</td>
        <td>${escapeHtml(profile.username || '-')}</td>
        <td>${escapeHtml(profile.email || '')}</td>
        <td>${escapeHtml(profile.contact_number || '-')}</td>
        <td>${escapeHtml(profile.service_role || '-')}</td>
        <td>${renderScheduleHtml(scheduleDisplay)}</td>
        <td>
          ${canEditSchedule ? `
            <form class="table-actions admin-schedule-form" data-schedule-form>
              <input type="hidden" name="csrf_token" value="${escapeHtml(csrf)}">
              <input type="hidden" name="user_id" value="${escapeHtml(profile.id)}">
              <textarea name="schedule" rows="2" placeholder="Monday 9:00 AM - 5:00 PM">${escapeHtml(scheduleDisplay === 'No schedule assigned yet.' ? '' : scheduleDisplay)}</textarea>
              <button class="button-secondary" type="submit">Save</button>
            </form>
          ` : '-'}
        </td>
      </tr>
    `;
  }).join('');
}

if (profileForm) {
  fetch('get_profile.php')
    .then((response) => response.json())
    .then((profile) => {
      fillProfileForm(profile);
    });

  profileForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const alert = document.querySelector('[data-profile-alert]');
    if (alert) alert.hidden = true;
    const response = await fetch(profileForm.dataset.endpoint, {
      method: 'POST',
      body: new FormData(profileForm),
    });
    const result = await response.json();
    if (!response.ok) {
      showInlineAlert(alert, result.error || 'Unable to update profile.', true);
      return;
    }
    fillProfileForm(result.profile);
    showInlineAlert(alert, result.message || 'Profile updated successfully.');
  });
}

if (profileModalForm) {
  profileModalForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (profileModalAlert) profileModalAlert.hidden = true;
    const validationError = validateProfileModalForm();
    if (validationError) {
      showInlineAlert(profileModalAlert, validationError, true);
      return;
    }

    // Reuse the existing profile endpoint so modal edits follow the same validation rules.
    const response = await fetch(profileModalForm.dataset.endpoint, {
      method: 'POST',
      body: new FormData(profileModalForm),
    });
    const result = await response.json();

    if (!response.ok) {
      showInlineAlert(profileModalAlert, result.error || 'Unable to update profile.', true);
      return;
    }

    updateProfileModalView(result.profile);
    profileModalSnapshot = readProfileModalForm();
    setProfileModalEditMode(false);
    showInlineAlert(profileModalAlert, result.message || 'Profile updated successfully.');
  });
}

if (passwordForm) {
  passwordForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    const alert = document.querySelector('[data-password-alert]');
    if (alert) alert.hidden = true;
    const response = await fetch(passwordForm.dataset.endpoint, {
      method: 'POST',
      body: new FormData(passwordForm),
    });
    const result = await response.json();
    if (!response.ok) {
      showInlineAlert(alert, result.error || 'Unable to change password.', true);
      return;
    }
    passwordForm.reset();
    showInlineAlert(alert, result.message || 'Password changed successfully.');
  });
}

if (adminProfilesBody) {
  fetch('get_profile.php?all=1')
    .then((response) => response.json())
    .then((result) => {
      renderAdminProfiles(result.profiles || []);
    });

  document.addEventListener('submit', async (event) => {
    const scheduleForm = event.target.closest('[data-schedule-form]');
    if (!scheduleForm) return;

    event.preventDefault();
    const response = await fetch(adminProfilesBody.dataset.scheduleEndpoint, {
      method: 'POST',
      body: new FormData(scheduleForm),
    });
    const result = await response.json();
    if (!response.ok) {
      alert(result.error || 'Unable to update schedule.');
      return;
    }

    alert(result.message || 'Schedule updated successfully.');
    const reload = await fetch('get_profile.php?all=1');
    const profiles = await reload.json();
    renderAdminProfiles(profiles.profiles || []);
  });
}

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}

function formatPeso(value) {
  return new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
  }).format(Number(value || 0));
}

function monthKey(dateValue) {
  return String(dateValue || '').slice(0, 7);
}

function addGroupedValue(group, key, amount = 1) {
  group.set(key, (group.get(key) || 0) + amount);
}

function topEntry(group, fallback = 'None') {
  const entries = [...group.entries()];
  if (!entries.length) return [fallback, 0];
  return entries.sort((a, b) => b[1] - a[1])[0];
}

function bottomEntry(group, fallback = 'None') {
  const entries = [...group.entries()];
  if (!entries.length) return [fallback, 0];
  return entries.sort((a, b) => a[1] - b[1])[0];
}

function chartRows(group, formatter = (value) => value) {
  const entries = [...group.entries()].sort((a, b) => b[1] - a[1]);
  const max = Math.max(...entries.map(([, value]) => value), 1);
  if (!entries.length) return '<p class="empty-table-message">No results found.</p>';

  return entries.map(([label, value]) => `
    <div class="chart-row">
      <strong>${escapeHtml(label)} <span>${escapeHtml(formatter(value))}</span></strong>
      <div class="chart-bar" style="width: ${Math.max(24, (value / max) * 100)}%"></div>
    </div>
  `).join('');
}

function reportCard(label, value, note = '') {
  return `
    <div class="stats-card">
      <span>${escapeHtml(label)}</span>
      <strong>${escapeHtml(value)}</strong>
      ${note ? `<small class="muted">${escapeHtml(note)}</small>` : ''}
    </div>
  `;
}

function filterReportAppointments(appointments, startDate, endDate) {
  return appointments.filter((appointment) => {
    if (!appointment.scheduled_date) return false;
    return appointment.scheduled_date >= startDate && appointment.scheduled_date <= endDate;
  });
}

// Revenue report: completed appointments only, summed by day and service.
function renderRevenueReport(appointments) {
  const completed = appointments.filter((appointment) => appointment.status === 'Completed');
  const dailyRevenue = new Map();
  const serviceRevenue = new Map();
  const totalRevenue = completed.reduce((total, appointment) => {
    const amount = Number(appointment.service_fee || 0);
    addGroupedValue(dailyRevenue, appointment.scheduled_date, amount);
    addGroupedValue(serviceRevenue, appointment.service_name || 'Unknown service', amount);
    return total + amount;
  }, 0);

  return `
    <div class="stats-grid">
      ${reportCard('Total Monthly Revenue', formatPeso(totalRevenue), 'Completed appointments only')}
      ${reportCard('Daily Revenue Days', String(dailyRevenue.size), 'Dates with completed revenue')}
      ${reportCard('Revenue by Service', String(serviceRevenue.size), 'Services earning in this range')}
    </div>
    <div class="split-cards reports-panels">
      <section class="table-card"><h3>Daily Revenue</h3><div class="chart-list">${chartRows(dailyRevenue, formatPeso)}</div></section>
      <section class="table-card"><h3>Revenue by Service</h3><div class="chart-list">${chartRows(serviceRevenue, formatPeso)}</div></section>
    </div>
  `;
}

// Doctor performance: completed appointments grouped by assigned doctor.
function renderDoctorReport(appointments, doctors) {
  const completed = appointments.filter((appointment) => appointment.status === 'Completed');
  const doctorCounts = new Map(doctors.map((doctor) => [doctor.name, 0]));
  completed.forEach((appointment) => {
    addGroupedValue(doctorCounts, appointment.staff_name || 'Unassigned');
  });

  const activeCounts = new Map([...doctorCounts.entries()].filter(([, count]) => count > 0));
  const [mostDoctor, mostCount] = topEntry(activeCounts, 'No completed appointments');
  const [leastDoctor, leastCount] = bottomEntry(doctorCounts, 'No doctors found');

  return `
    <div class="stats-grid">
      ${reportCard('Completed Appointments', String(completed.length), 'Grouped by assigned doctor')}
      ${reportCard('Most Active Doctor', mostDoctor, `${mostCount} completed appointment(s)`)}
      ${reportCard('Least Active Doctor', leastDoctor, `${leastCount} completed appointment(s)`)}
    </div>
    <section class="table-card"><h3>Completed Appointments Per Doctor</h3><div class="chart-list">${chartRows(doctorCounts)}</div></section>
  `;
}

// Appointment analytics: all appointments in range, grouped by status, day, and hour.
function renderAppointmentAnalytics(appointments) {
  const statusCounts = new Map([['Pending', 0], ['Accepted', 0], ['Completed', 0]]);
  const dayCounts = new Map();
  const hourCounts = new Map();
  appointments.forEach((appointment) => {
    const statusKey = appointment.status === 'Approved' ? 'Accepted' : appointment.status;
    if (statusCounts.has(statusKey)) addGroupedValue(statusCounts, statusKey);
    addGroupedValue(dayCounts, appointment.scheduled_date);
    const hour = String(appointment.start_time || '').slice(0, 2);
    addGroupedValue(hourCounts, hour ? `${hour}:00` : 'Unknown');
  });
  const [peakDay, peakDayCount] = topEntry(dayCounts);
  const [peakHour, peakHourCount] = topEntry(hourCounts);

  return `
    <div class="stats-grid">
      ${reportCard('Total Appointments', String(appointments.length), 'Selected date range')}
      ${reportCard('Peak Booking Day', peakDay, `${peakDayCount} appointment(s)`)}
      ${reportCard('Peak Booking Hour', peakHour, `${peakHourCount} appointment(s)`)}
    </div>
    <div class="split-cards reports-panels">
      <section class="table-card"><h3>Pending vs Accepted vs Completed</h3><div class="chart-list">${chartRows(statusCounts)}</div></section>
      <section class="table-card"><h3>Peak Booking Days</h3><div class="chart-list">${chartRows(dayCounts)}</div></section>
    </div>
  `;
}

function patientKey(appointment) {
  return appointment.patient_id ? `id:${appointment.patient_id}` : `email:${String(appointment.patient_email || '').toLowerCase()}`;
}

// Patient report: first bookings count as new; multiple bookings in range count as returning.
function renderPatientReport(appointments, allAppointments, patients) {
  const selectedPatientCounts = new Map();
  const firstBookingByPatient = new Map();
  allAppointments.forEach((appointment) => {
    const key = patientKey(appointment);
    if (!key) return;
    if (!firstBookingByPatient.has(key) || appointment.scheduled_date < firstBookingByPatient.get(key)) {
      firstBookingByPatient.set(key, appointment.scheduled_date);
    }
  });
  appointments.forEach((appointment) => addGroupedValue(selectedPatientCounts, patientKey(appointment)));

  let returningPatients = 0;
  let newPatients = 0;
  const newPatientsByMonth = new Map();
  selectedPatientCounts.forEach((count, key) => {
    if (count > 1) returningPatients += 1;
    const firstDate = firstBookingByPatient.get(key);
    if (firstDate && appointments.some((appointment) => patientKey(appointment) === key && appointment.scheduled_date === firstDate)) {
      newPatients += 1;
      addGroupedValue(newPatientsByMonth, monthKey(firstDate));
    }
  });

  return `
    <div class="stats-grid">
      ${reportCard('Total Registered Patients', String(patients.length), 'Users with patient role')}
      ${reportCard('New Patients', String(newPatients), 'First-time bookings in range')}
      ${reportCard('Returning Patients', String(returningPatients), 'Multiple bookings in range')}
    </div>
    <section class="table-card"><h3>New Patients Per Month</h3><div class="chart-list">${chartRows(newPatientsByMonth)}</div></section>
  `;
}

document.querySelectorAll('[data-admin-reports]').forEach((module) => {
  const data = JSON.parse(module.querySelector('[data-report-data]')?.textContent || '{}');
  const typeInput = module.querySelector('[data-report-type]');
  const startInput = module.querySelector('[data-report-start]');
  const endInput = module.querySelector('[data-report-end]');
  const output = module.querySelector('[data-report-output]');
  const loading = module.querySelector('[data-report-loading]');

  const render = () => {
    const now = new Date();
    const defaultStart = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-01`;
    const defaultEnd = new Date(now.getFullYear(), now.getMonth() + 1, 0).toISOString().slice(0, 10);
    const startDate = startInput.value || defaultStart;
    const endDate = endInput.value || defaultEnd;
    const [from, to] = startDate <= endDate ? [startDate, endDate] : [endDate, startDate];
    const scopedAppointments = filterReportAppointments(data.appointments || [], from, to);

    loading.hidden = false;
    output.classList.add('is-loading');
    window.setTimeout(() => {
      // Only one report is rendered at a time; changing controls recomputes the selected report instantly.
      output.innerHTML = {
        revenue: renderRevenueReport(scopedAppointments),
        doctor: renderDoctorReport(scopedAppointments, data.doctors || []),
        appointments: renderAppointmentAnalytics(scopedAppointments),
        patients: renderPatientReport(scopedAppointments, data.appointments || [], data.patients || []),
      }[typeInput.value] || '';
      loading.hidden = true;
      output.classList.remove('is-loading');
    }, 120);
  };

  [typeInput, startInput, endInput].forEach((input) => input.addEventListener('change', render));
  render();
});

window.addEventListener('scroll', () => {
  const header = document.getElementById('siteHeader');
  if (!header) return;
  if (window.scrollY > 10) {
    header.style.boxShadow = '0 24px 60px rgba(18, 44, 62, 0.14)';
    header.style.borderColor = 'rgba(173, 193, 226, 0.9)';
  } else {
    header.style.boxShadow = '';
    header.style.borderColor = '';
  }
});

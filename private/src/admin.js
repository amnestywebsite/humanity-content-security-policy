const ROUTE = '/amnesty/v1/csp';

const showError = (message) => {
  let targetSibling;

  if (document.querySelector('.wrap > .notice')) {
    const notices = document.querySelectorAll('.wrap > .notice');
    targetSibling = notices[notices.length - 1];
  } else {
    targetSibling = document.querySelector('.wrap > h2:first-of-type');
  }

  const wrapper = document.createElement('div');
  wrapper.setAttribute('class', 'notice notice-error settings-error is-dismissible');

  const error = document.createElement('p');
  error.innerHTML = `<strong>${message}</strong>`;

  // eslint-disable-next-line no-underscore-dangle
  const label = wp.i18n.__('Dismiss this notice.');
  const button = document.createElement('button');
  button.setAttribute('type', 'button');
  button.setAttribute('class', 'notice-dismiss');
  button.innerHTML = `<span class="screen-reader-text">${label}</span>`;

  wrapper.appendChild(error);
  wrapper.appendChild(button);

  targetSibling.insertAdjacentElement('afterend', wrapper);
};

const doExport = async (event) => {
  if (!event.target.matches('#aicsp-export')) {
    return;
  }

  event.preventDefault();

  const response = await wp.apiFetch({ path: ROUTE }).catch((error) => {
    showError(error.message);
  });

  if (!response?.data) {
    // eslint-disable-next-line no-underscore-dangle
    showError(wp.i18n.__('Unable to export.', 'aicsp'));
    return;
  }

  const json = JSON.stringify(response?.data, null, 2);

  let link = document.createElement('a');
  const blob = new Blob([json], { type: 'application/json' });

  link.setAttribute('href', window.URL.createObjectURL(blob));
  link.setAttribute('download', 'content-security-policy.json');

  link.click();
  link = null;
};

const doImport = async (event) => {
  const files = event?.target?.files || [];

  if (!files.length) {
    return;
  }

  const body = new FormData();
  body.append('csp', files[0]);

  const args = {
    method: 'POST',
    path: ROUTE,
    body,
  };

  const response = await wp.apiFetch(args).catch((error) => {
    showError(error.message);
  });

  if (response?.success) {
    window.location.reload();
  }
};

document.addEventListener('DOMContentLoaded', () => {
  const exporter = document.getElementById('aicsp-export');

  if (exporter) {
    exporter.addEventListener('click', doExport);
  }

  const importer = document.getElementById('aicsp-import');

  if (importer) {
    importer.addEventListener('change', doImport);
  }
});

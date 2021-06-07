// grab and stash elements
const baseUri = window.location.pathname;
const fetchUri = `${baseUri}ajax.php`;

let dialogStep = 0;
let dialogHash;

const dialogContainer = document.getElementById('dialog-container');
const dialogTitle = dialogContainer.querySelector('.dialog-title');
const dialogBody = dialogContainer.querySelector('.dialog-body');
const dialogButton = dialogContainer.querySelector('#btnDialogSave');
const dialogBtnBack = dialogContainer.querySelector('#btnDialogBack');
const dialogBtnClose = dialogContainer.querySelector('#btnDialogClose');


const article = document.querySelector('article');

const tabgroup     = document.querySelector('snap-tabs')
const tabsection   = tabgroup.querySelector(':scope > section')
const tabnav       = tabgroup.querySelector(':scope nav')
const tabnavitems  = tabnav.querySelectorAll(':scope a')

const setActiveTab = tabbtn => {
  tabnav
    .querySelector(':scope a[active]')
    .removeAttribute('active');
  
  tabbtn.setAttribute('active', '');
  tabbtn.scrollIntoView();

  const date = tabbtn.dataset.date;
  date && setContentOnTab(date);
}
 
const determineActiveTabSection = () => {
  const i = tabsection.scrollLeft / tabsection.clientWidth
  const matchingNavItem = tabnavitems[i]
  
  matchingNavItem && setActiveTab(matchingNavItem)
}

const setContentOnTab = async date => {
  const response = await fetch(`${fetchUri}?a=changedate&d=${date}`);
  const json = await response.json();
  article.innerHTML = json.data;

  addClickAppointment();
}

const toggleDialog = () => {
  document.body.classList.toggle('dialog-scroll-lock');
  dialogContainer.classList.toggle('is-visible');
  removeAllLoadings();
}

const addClickAppointment = () => {
  const appointments = article.querySelectorAll('.appointment.is-available');
  appointments.forEach((appointment) => {
    appointment.addEventListener('click', (evt) => {  
      toggleDialog();

      dialogStep = 1;
      dialogHash = evt.target.dataset.hash;

      dialogSteps();
    });
  });
}

const setContentAppointment = async (hash) => {
  const response = await fetch(`${fetchUri}?a=bookform`);
  const json = await response.json();

  dialogTitle.innerHTML = json.data.title;
  dialogBody.innerHTML = json.data.body;
  dialogButton.textContent = json.data.button;

  setFocus(dialogBody);
}

const showErrors = (data) => {
  const errors = data.errors;
  errors.forEach(error => {
    console.log(error);
    const errorNode = document.createElement('div');
    errorNode.classList.add('error');
    errorNode.textContent = error;

    dialogBody.insertBefore(errorNode, dialogBody.firstChild);
  });
  dialogStep--;
}

const removeAllLoadings = () => {
  const loadings = document.querySelectorAll('.is-loading');
  loadings.forEach((loading) => {
    loading.classList.remove('is-loading');
  });
}

const setFocus = (rootNode) => {
  const inputs = rootNode.querySelectorAll('input');

  let minY = Number.POSITIVE_INFINITY;
  let target = null;
  inputs.forEach((input) => {
    if (input.type != 'hidden') {
      const rect = input.getBoundingClientRect();
      const pos = {
        x: parseFloat(rect.left + window.pageXOffset),
        y: parseFloat(rect.top + window.pageYOffset)
      };

      if (pos.y < minY) {
        minY = pos.y;
        target = input;
      }
    }
  });

  try {
    target && target.focus();
  } catch (_ignored) {}
}

const dialogBack = () => {
  console.log(dialogStep);
  dialogStep -= 2;
  dialogSteps();
}

// Step 1
const confirmBooking = () => {
  dialogTitle.textContent = 'Termin anzeigen';
  dialogBody.textContent =
    `Möchten Sie diesen Termin wirklich buchen?`;

  dialogButton.textContent = 'Buchung starten'; 
}

// Step 2
const reserveAppointment = async () => {
  const response = await fetch(`${fetchUri}?a=reserve&h=${dialogHash}`);
  const json = await response.json();
  
  if (json.data.status == 'OK') {  
    //dialogStep++;
    setContentAppointment();
  }
}

// Step 3
const saveProband = async (data) => {
  const response = await fetch(`${fetchUri}?a=proband&h=${dialogHash}`, {
    method: 'POST',
    body: data,
  });
  const json = await response.json();
  if (json.data.status == 'OK') {
    //dialogStep++;
    importantInfos();
  } else {
    showErrors(json.data);
  }
}

// Step 3.1
const importantInfos = async () => {
  const response = await fetch(`${fetchUri}?a=booking&h=${dialogHash}`);
  const json = await response.json();

  dialogTitle.innerHTML = json.data.title;
  dialogBody.innerHTML = json.data.body;
  dialogButton.innerHTML = json.data.button;
  dialogButton.disabled = json.data.buttonDisabled;

  const cb = dialogBody.querySelectorAll('input[type="checkbox"]');
  cb.forEach(checkbox => {
    checkbox.addEventListener('change', (evt) => {
      const cb_checked = dialogBody.querySelectorAll('input[type="checkbox"]:checked');
      dialogButton.disabled = true;
      if (cb.length == cb_checked.length) {
        dialogButton.disabled = false;
      }
    });
  });
  //dialogStep++;
};

// Step 4.1
const saveBooking = async () => {
  const response = await fetch(`${fetchUri}?a=savebooking&h=${dialogHash}`);
  const json = await response.json();
  if (json.data.status == 'OK') {
    //dialogStep++;
  }
}

// Step 4.2
const summaryBooking = async () => {
  const response = await fetch(`${fetchUri}?a=summarybooking&h=${dialogHash}`);
  const json = await response.json();

  dialogTitle.innerHTML = json.data.title;
  dialogBody.innerHTML = json.data.body;
  dialogButton.style.display = "none";
}

// Step Controller
const dialogSteps = async () => {
  if (!dialogStep) {
    return;
  }
  console.log(dialogStep);

  if (dialogStep === 1) {
    confirmBooking();
    dialogStep++;
  } else if (dialogStep === 2) {
    reserveAppointment();
    dialogStep++;
  } else if (dialogStep === 3) {
    const form = dialogBody.querySelector('form');
    form.reportValidity();
    if (form.checkValidity() !== false) {
      const formData = new FormData(form);
      saveProband(formData);
      dialogStep++;
    }
  } else if (dialogStep === 4) {
    saveBooking();
    summaryBooking();

    dialogHash = null;
    dialogStep = 0;
  }
}


document.addEventListener('click', (evt) => {
  if (!evt.target.getAttribute('data-ajax')) {
    return;
  }

  evt.target.classList.add('is-loading');
});

dialogButton.addEventListener('click', async (evt) => {
  evt.preventDefault();
  dialogSteps();
});

tabnav.addEventListener('click', (evt) => {
  if (evt.target.nodeName !== 'A') return;
  setActiveTab(evt.target);
});

tabsection.addEventListener('scroll', () => {
  clearTimeout(tabsection.scrollEndTimer);              
  tabsection.scrollEndTimer = setTimeout(
    determineActiveTabSection
  , 100);
})

window.onload = () => {
  determineActiveTabSection();

  //dialogBtnBack.addEventListener('click', dialogBack);
  dialogBtnClose.addEventListener('click', toggleDialog);
}


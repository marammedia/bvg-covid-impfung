const baseUri = window.location.pathname;
const fetchUri = `${baseUri}ajax.php`;

let dialogSteps;
let dialogCurrentStep = 0;
let dialogHash;

const dialogBooking = document.getElementById('dialog-booking');
const dialogDetails = document.getElementById('dialog-details');


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
  const i = tabsection.scrollLeft / tabsection.clientWidth;
  const matchingNavItem = tabnavitems[i];
  
  matchingNavItem && setActiveTab(matchingNavItem);
}

const setContentOnTab = async (date) => {
  const response = await fetch(`${fetchUri}?a=changedate&d=${date}`);
  const json = await response.json();
  article.innerHTML = json.data;

  addClickAppointment();
}

const showDialog = (node) => {
  document.body.classList.add('dialog-scroll-lock');
  node.classList.add('is-visible');

  const dialogBtnNext = node.querySelector('#btnDialogNext');
  const dialogBtnPrev = node.querySelector('#btnDialogPrev');
  const dialogBtnClose = node.querySelector('#btnDialogClose');

  dialogSteps = node.querySelectorAll('.dialog-step');
  dialogCurrentStep = 0;
  
  dialogBtnNext && dialogBtnNext.addEventListener('click', (evt) => nextPrev(1));
  dialogBtnPrev && dialogBtnPrev.addEventListener('click', (evt) => nextPrev(-1));
  dialogBtnClose && dialogBtnClose.addEventListener('click', (evt) => hideDialog(node));

  activeDialogStep();
}

const hideDialog = (node) => {
  document.body.classList.remove('dialog-scroll-lock');
  node.classList.remove('is-visible');
  removeAllLoadings();
}

const addClickAppointment = () => {
  const appointments = article.querySelectorAll('.appointment.is-available');
  appointments.forEach((appointment) => {
    appointment.addEventListener('click', (evt) => {  
      dialogHash = evt.target.dataset.hash;
      showDialog(dialogBooking);      
    });
  });
}

const removeAllLoadings = () => {
  [...document.querySelectorAll('.is-loading')].map(
    loading => loading.classList.remove('is-loading'));
}

const showErrors = (data) => {
  const node = dialogSteps[dialogCurrentStep];

  const nodeBody = node.querySelector('.dialog-body');
  [...nodeBody.querySelectorAll('.error')].map(
    oldError => oldError.remove());

  const errors = data.errors;
  errors && errors.forEach(error => {
    console.log(error);
    const errorNode = document.createElement('div');
    errorNode.classList.add('error');
    errorNode.textContent = error;

    nodeBody.insertBefore(errorNode, nodeBody.firstChild);
  });
}



function activeDialogStep() {
  dialogSteps.forEach(dialogStep => {
    dialogStep.classList.remove('is-visible');
  });

  console.log(`Step: ${dialogCurrentStep}`);

  const step = dialogSteps[dialogCurrentStep];
  step && step.classList.add('is-visible');

  const dialogRoot = step.closest('.dialog-container');
  dialogRoot.querySelector('#btnDialogNext').removeAttribute('hidden');
  dialogRoot.querySelector('#btnDialogPrev').removeAttribute('hidden');
  if (dialogCurrentStep == 0) {
    const dialogRoot = step.closest('.dialog-container');
    dialogRoot.querySelector('#btnDialogPrev').setAttribute('hidden', '');
  }
  if (dialogCurrentStep == dialogSteps.length - 1) {
    dialogRoot.querySelector('#btnDialogNext').setAttribute('hidden', '');
  }  
}

const nextPrev = async (n) => {
  const check = await stepAction();
  if (!check) {
    console.warn('Stop');
    return false;
  }

  dialogCurrentStep = dialogCurrentStep + n;
  if (dialogCurrentStep >= dialogSteps.length) {
    //document.getElementById("regForm").submit();
    return false;
  }

  activeDialogStep();
}

const stepAction = async () => {
  const dialog = document.querySelector('.dialog-container.is-visible');
  if (!dialog) {
    return false;
  }

  const name = dialog.getAttribute('id');
  if (name == 'dialog-booking') {
    if (dialogCurrentStep === 0) {
      console.log('Reserve Slot');

      const response = await fetch(`${fetchUri}?a=reserve&h=${dialogHash}`);
      const json = await response.json();
      
      if (json.data.status !== 'OK') {  
        return false;
      }
    } else if (dialogCurrentStep === 7) {
      const form = dialog.querySelector('form');
      form.reportValidity();
      if (form.checkValidity() === false) {
        return false;
      }

      console.log('Save Proband');

      const formData = new FormData(form);
      const response = await fetch(`${fetchUri}?a=proband&h=${dialogHash}`, {
        method: 'POST',
        body: formData,
      });
      const json = await response.json();
      if (json.data.status !== 'OK') {
        showErrors(json.data);
        return false;
      }
    } else if (dialogCurrentStep === 8) {
      const response = await fetch(`${fetchUri}?a=bookingsummary&h=${dialogHash}`);
      const json = await response.json();
      if (json.data.status !== 'OK') {
        return false;
      }

      const fields = json.data.fields;
      Object.keys(fields).forEach(field => {
        document.getElementById(field).textContent = fields[field];
      });
    }
  } else if (name == 'dialog-details') {
    if (dialogCurrentStep === 0) {
      const form = dialog.querySelector('form');
      const formData = new FormData(form);

      const response = await fetch(`${fetchUri}?a=code`, {
        method: 'POST',
        body: formData,
      });
      const json = await response.json();
      if (json.data.status !== 'OK') {
        showErrors(json.data);
        return false;
      }

      dialogHash = json.data.hash;
      console.log(dialogHash);

      const fields = json.data.fields;
      Object.keys(fields).forEach(field => {
        const node = document.getElementById(field);
        if (node) {
          node.textContent = fields[field];
        }
      });

      
    
      const button = dialog.querySelector('#btnDialogNext');
      button.classList.remove('button-colored');
      button.classList.add('button-cancel');
      button.textContent = 'Buchung löschen';
    } else if (dialogCurrentStep === 1) {
      const response = await fetch(`${fetchUri}?a=deletebooking&h=${dialogHash}`);
      const json = await response.json();
      if (json.data.status !== 'OK') {
        return false;
      }
    }
  }

  return true;
}

const releaseBooking = async () => {
  console.log('Release Slot');

  const response = await fetch(`${fetchUri}?a=release&h=${dialogHash}`);
  const json = await response.json();
  if (json.data.status !== 'OK') {  
    return false;
  }

  return true;
}






document.addEventListener('click', (evt) => {
  if (!evt.target.getAttribute('data-ajax')) {
    return;
  }

  evt.target.classList.add('is-loading');
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

  const cancel = document.getElementById('cancel');
  cancel.addEventListener('click', (evt) => showDialog(dialogDetails));
}


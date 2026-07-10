import initCtaForm from './sets/cta_form';
import initGallery from './sets/gallery';
import initHeader from './patterns/header';

const initCategorySelects = () => {
  document.querySelectorAll('[data-category-select]').forEach((select) => {
    if (select.dataset.bound === 'true') {
      return;
    }

    select.dataset.bound = 'true';

    select.addEventListener('change', () => {
      if (select.value) {
        window.location.assign(select.value);
      }
    });
  });
};

const initLivePreviewMorph = () => {
  window.StatamicLivePreviewMorph = (currentDocument, updatedDocument) => {
    const currentMain = currentDocument.querySelector('main');
    const updatedMain = updatedDocument.querySelector('main');
    const fallbackScrollX = window.scrollX;
    const fallbackScrollY = window.scrollY;

    currentDocument.title = updatedDocument.title;

    Array.from(currentDocument.body.attributes).forEach((attribute) => {
      if (!updatedDocument.body.hasAttribute(attribute.name)) {
        currentDocument.body.removeAttribute(attribute.name);
      }
    });

    Array.from(updatedDocument.body.attributes).forEach((attribute) => {
      currentDocument.body.setAttribute(attribute.name, attribute.value);
    });

    if (!currentMain || !updatedMain) {
      currentDocument.body.innerHTML = updatedDocument.body.innerHTML;
      window.StarterKit?.initMainContent?.();
      window.scrollTo(fallbackScrollX, fallbackScrollY);
      return;
    }

    currentMain.innerHTML = updatedMain.innerHTML;
    window.StarterKit?.initMainContent?.();
    window.scrollTo(fallbackScrollX, fallbackScrollY);
  };
};

const initMainContent = () => {
  [
    initCtaForm,
    initGallery,
    initCategorySelects,
  ].forEach((init) => init());
};

window.StarterKit = {
  ...(window.StarterKit || {}),
  initMainContent,
};

initHeader();
initLivePreviewMorph();
initMainContent();

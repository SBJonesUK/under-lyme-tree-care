import initCtaForm from './sets/cta_form';
import initGallery from './sets/gallery';
import initHeader from './patterns/header';

const initMainContent = () => {
  [
    initCtaForm,
    initGallery,
  ].forEach((init) => init());
};

window.StarterKit = {
  ...(window.StarterKit || {}),
  initMainContent,
};

initHeader();
initMainContent();

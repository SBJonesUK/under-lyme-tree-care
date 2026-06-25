import initCtaForm001 from './sets/cta_form_001';
import initGallery from './sets/gallery';
import initHeader001 from './patterns/header_001';

const initMainContent = () => {
  [
    initCtaForm001,
    initGallery,
  ].forEach((init) => init());
};

window.StarterKit = {
  ...(window.StarterKit || {}),
  initMainContent,
};

initHeader001();
initMainContent();

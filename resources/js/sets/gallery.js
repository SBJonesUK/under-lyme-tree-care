export default function initGallery() {
  const galleryRoots = document.querySelectorAll('[data-gallery]');

  galleryRoots.forEach((root) => {
    if (root.dataset.galleryInitialized === 'true') {
      return;
    }

    root.dataset.galleryInitialized = 'true';

    const filter = root.querySelector('[data-gallery-filter]');
    const emptyState = root.querySelector('[data-gallery-empty]');
    const lightbox = root.querySelector('[data-gallery-lightbox]');
    const lightboxImage = root.querySelector('[data-gallery-lightbox-image]');
    const lightboxCaption = root.querySelector('[data-gallery-lightbox-caption]');
    const closeButtons = root.querySelectorAll('[data-gallery-close]');
    const previousButton = root.querySelector('[data-gallery-prev]');
    const nextButton = root.querySelector('[data-gallery-next]');

    if (!lightbox || !lightboxImage || !lightboxCaption || !previousButton || !nextButton) {
      return;
    }

    let activeIndex = -1;
    let lastFocusedElement = null;

    const allItems = () => Array.from(root.querySelectorAll('[data-gallery-item]'));

    const visibleItems = () => allItems().filter((item) => {
      const entry = item.closest('[data-gallery-entry]');

      return entry ? !entry.hidden : true;
    });

    const toggleEmptyState = () => {
      if (!emptyState) {
        return;
      }

      emptyState.classList.toggle('is-hidden', visibleItems().length > 0);
    };

    const updateLightbox = () => {
      const items = visibleItems();
      const activeItem = items[activeIndex];

      if (!activeItem) {
        closeLightbox();
        return;
      }

      lightboxImage.src = activeItem.dataset.galleryImageUrl || '';
      lightboxImage.alt = activeItem.dataset.galleryAlt || activeItem.dataset.galleryTitle || '';

      const caption = (activeItem.dataset.galleryCaption || '').trim();

      lightboxCaption.textContent = caption;
      lightboxCaption.hidden = caption.length === 0;

      const disableControls = items.length <= 1;

      previousButton.disabled = disableControls;
      nextButton.disabled = disableControls;
    };

    const openLightbox = (item) => {
      const items = visibleItems();
      const nextIndex = items.indexOf(item);

      if (nextIndex === -1) {
        return;
      }

      lastFocusedElement = document.activeElement;
      activeIndex = nextIndex;

      updateLightbox();

      lightbox.hidden = false;
      document.body.classList.add('has-gallery-lightbox');

      const closeButton = root.querySelector('.gallery-lightbox__close');

      closeButton?.focus();
    };

    function closeLightbox() {
      if (lightbox.hidden) {
        return;
      }

      lightbox.hidden = true;
      document.body.classList.remove('has-gallery-lightbox');
      activeIndex = -1;

      if (lastFocusedElement instanceof HTMLElement) {
        lastFocusedElement.focus();
      }
    }

    const move = (direction) => {
      const items = visibleItems();

      if (items.length <= 1) {
        return;
      }

      activeIndex = (activeIndex + direction + items.length) % items.length;
      updateLightbox();
    };

    const applyFilter = () => {
      const selectedCategory = filter?.value || '';

      allItems().forEach((item) => {
        const categories = (item.dataset.galleryCategories || '')
          .split('|')
          .filter(Boolean);
        const entry = item.closest('[data-gallery-entry]');
        const shouldShow = !selectedCategory || categories.includes(selectedCategory);

        if (entry) {
          entry.hidden = !shouldShow;
        }
      });

      toggleEmptyState();

      if (!lightbox.hidden) {
        const items = visibleItems();
        const activeItem = items[activeIndex];

        if (!activeItem) {
          closeLightbox();
        } else {
          updateLightbox();
        }
      }
    };

    allItems().forEach((item) => {
      item.addEventListener('click', () => openLightbox(item));
    });

    closeButtons.forEach((button) => {
      button.addEventListener('click', closeLightbox);
    });

    previousButton.addEventListener('click', () => move(-1));
    nextButton.addEventListener('click', () => move(1));

    filter?.addEventListener('change', applyFilter);

    document.addEventListener('keydown', (event) => {
      if (lightbox.hidden) {
        return;
      }

      if (event.key === 'Escape') {
        closeLightbox();
      }

      if (event.key === 'ArrowLeft') {
        event.preventDefault();
        move(-1);
      }

      if (event.key === 'ArrowRight') {
        event.preventDefault();
        move(1);
      }
    });

    toggleEmptyState();
    applyFilter();
  });
}

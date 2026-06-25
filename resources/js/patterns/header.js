export default function initHeader() {
  const header = document.querySelector('[data-header]');

  if (!header) {
    return;
  }

  const desktopMedia = window.matchMedia('(min-width: 64rem)');
  const menuToggle = header.querySelector('[data-menu-toggle]');
  const navPanel = header.querySelector('[data-nav-panel]');
  const dropdownItems = Array.from(header.querySelectorAll('[data-has-children]'));

  const setMenuOpen = (isOpen) => {
    if (!menuToggle || !navPanel) {
      return;
    }

    menuToggle.setAttribute('aria-expanded', String(isOpen));
    navPanel.classList.toggle('is-open', isOpen);
  };

  const setDropdownOpen = (item, isOpen) => {
    item.classList.toggle('is-open', isOpen);

    item.querySelectorAll('[data-dropdown-toggle]').forEach((control) => {
      control.setAttribute('aria-expanded', String(isOpen));
    });
  };

  const closeOtherDropdowns = (activeItem) => {
    dropdownItems.forEach((item) => {
      if (item !== activeItem) {
        setDropdownOpen(item, false);
      }
    });
  };

  const closeAllDropdowns = () => {
    dropdownItems.forEach((item) => {
      setDropdownOpen(item, false);
    });
  };

  setMenuOpen(false);
  closeAllDropdowns();

  menuToggle?.addEventListener('click', () => {
    const isOpen = menuToggle.getAttribute('aria-expanded') === 'true';
    setMenuOpen(!isOpen);
  });

  dropdownItems.forEach((item) => {
    const toggle = item.querySelector('[data-dropdown-toggle]');

    toggle?.addEventListener('click', (event) => {
      event.preventDefault();

      const isOpen = toggle.getAttribute('aria-expanded') === 'true';
      closeOtherDropdowns(item);
      setDropdownOpen(item, !isOpen);
    });

    item.addEventListener('mouseenter', () => {
      if (!desktopMedia.matches) {
        return;
      }

      closeOtherDropdowns(item);
      setDropdownOpen(item, true);
    });

    item.addEventListener('mouseleave', () => {
      if (!desktopMedia.matches) {
        return;
      }

      setDropdownOpen(item, false);
    });
  });

  document.addEventListener('click', (event) => {
    if (!header.contains(event.target)) {
      closeAllDropdowns();
      setMenuOpen(false);
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
      return;
    }

    closeAllDropdowns();
    setMenuOpen(false);
  });

  desktopMedia.addEventListener('change', (event) => {
    if (event.matches) {
      setMenuOpen(false);
    }

    closeAllDropdowns();
  });
}

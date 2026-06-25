export default function initCtaForm001() {
  const ajaxForms = document.querySelectorAll('[data-ajax-form="true"]');

  if (!ajaxForms.length) {
    return;
  }

  const toggleFeedback = (form, isVisible) => {
    const feedback = form.querySelector('[data-form-feedback]');

    if (!feedback) {
      return;
    }

    feedback.hidden = !isVisible;
  };

  const clearFieldErrors = (form) => {
    form.querySelectorAll('[data-field-error]').forEach((element) => {
      element.textContent = '';
      element.hidden = true;
    });

    form.querySelectorAll('.cta-form-001__field').forEach((field) => {
      field.classList.remove('has-error');
    });
  };

  const renderFieldErrors = (form, errors = {}) => {
    Object.entries(errors).forEach(([handle, messages]) => {
      const field = form.querySelector(`[data-field="${handle}"]`);

      if (!field) {
        return;
      }

      const error = field.querySelector('[data-field-error]');
      const message = Array.isArray(messages) ? messages[0] : messages;

      if (!error || !message) {
        return;
      }

      field.classList.add('has-error');
      error.textContent = message;
      error.hidden = false;
    });
  };

  const renderFormErrors = (form, errors = []) => {
    const errorsContainer = form.querySelector('[data-form-errors]');

    if (!errorsContainer) {
      return;
    }

    const messages = Array.isArray(errors) ? errors : Object.values(errors).flat();

    if (!messages.length) {
      errorsContainer.hidden = true;
      toggleFeedback(form, false);

      return;
    }

    errorsContainer.hidden = false;
    toggleFeedback(form, true);
  };

  ajaxForms.forEach((form) => {
    form.addEventListener('submit', async (event) => {
      event.preventDefault();

      const submit = form.querySelector('[data-form-submit]');
      const success = form.querySelector('[data-form-success]');
      const formData = new FormData(form);

      clearFieldErrors(form);
      renderFormErrors(form);
      toggleFeedback(form, false);

      if (success) {
        success.hidden = true;
      }

      form.classList.add('is-submitting');

      if (submit) {
        submit.disabled = true;
      }

      try {
        const response = await fetch(form.action, {
          method: form.method,
          headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: formData,
        });

        const payload = await response.json();

        if (!response.ok) {
          renderFormErrors(form, payload.errors || []);
          renderFieldErrors(form, payload.error || {});

          return;
        }

        form.reset();

        if (success) {
          success.hidden = false;
        }

        toggleFeedback(form, true);
      } catch (error) {
        renderFormErrors(form, {
          form: ['Something went wrong. Please try again.'],
        });
      } finally {
        form.classList.remove('is-submitting');

        if (submit) {
          submit.disabled = false;
        }
      }
    });
  });
}

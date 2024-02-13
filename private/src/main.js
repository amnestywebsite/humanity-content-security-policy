import DOMPurify from 'dompurify';

if (window?.trustedTypes?.createPolicy) {
  window.DOMSanitiser = window.trustedTypes.createPolicy('default', {
    createHTML: (string) => DOMPurify.sanitize(string, { RETURN_TRUSTED_TYPE: true }),
  });
}

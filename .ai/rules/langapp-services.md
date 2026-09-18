---
paths:
  - '{lang,app/Services}/**'
---

# Langapp Services

## Indonesian validation messages and dynamic field labels
The app uses locale/fallback id. Maintain complete framework validation translations in lang/id/validation.php and readable attributes for new form fields; do not replace existing domain-specific messages. Dynamic registration questions and jury score cells pass explicit validation attributes using question labels or participant/criterion names, not internal IDs. ValidationMessagesTest checks missing translation keys and user-visible messages across HTTP and Livewire.

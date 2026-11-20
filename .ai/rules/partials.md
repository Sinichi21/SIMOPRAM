---
paths:
  - resources/views/administration/partials/annual-program-matrix.blade.php
---

# Partials

## Keep annual matrix number cells safe across PDF pages
Do not use body rowspans for Roman numeral sections: Dompdf shifts continuation rows into the wrong columns across page breaks. Keep a number cell in every row and hide internal top/bottom borders for a merged appearance. Verify rendered column positions across multiple pages with both activity and finance rows, not only presence of text.

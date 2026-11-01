---
paths:
  - 'app/Services/Inventory/**'
---

# Services Inventory

## Inventory BA uses MABI numbering and published document archives
Handover/return BA uses the shared MABI outgoing sequence with fixed {sequence}/02/{month_roman}.{year_short}/{gudep}-C and keeps the inventory loan reference. Register each new version as a ReportVerification sourced from InventoryLoanDocument and use its archived PDF as the sole stored file. Draft previews consume no number; corrections get a fresh number/QR, preserve previous bytes and revoke the replaced QR. QR verifies publication, not wet signatures; do not retrofit immutable legacy PDFs on read.

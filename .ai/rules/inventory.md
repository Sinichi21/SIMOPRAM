---
paths:
  - 'app/Livewire/Inventory/**'
---

# Inventory

## Loan stock sources follow item and asset codes
Loan requests resolve existing inventory item/asset QR labels within the active school. Users enter quantity, not owner/location: derive those from available good assets or stock balances, allocating across sources deterministically and accounting for existing cart quantities. Do not partially modify the cart on shortage; InventoryLoanService remains authoritative when requesting and approving.

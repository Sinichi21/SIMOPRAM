---
paths:
  - 'resources/**'
---

# Resources

## Consistent internal forms and tables
Internal pages share the app-content scope and resources/css/internal-ui.css so native controls and Flux use a common palette and sizing. Reuse that presentation for equivalent actions instead of introducing page-specific colors. Preserve semantic action tones and keep letter previews/contenteditable document tables outside application table styling.

## Shared searchable native selects
resources/js/searchable-select.js enhances native selects (including Flux default selects) through delegated events and one transient searchable popup. Keep name, wire:model, required/disabled and onchange on the original select; typing only filters labels and choosing dispatches native input/change. Do not wrap or replace selects or clone their options because Livewire owns them. The popup observes option changes, closes on navigation/removal, and is mounted inside the nearest dialog. Existing layouts load it through app.js; no per-page initialization is needed.

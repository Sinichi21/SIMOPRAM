(() => {
    if (window.simpramSelectSearch) return;
    window.simpramSelectSearch = true;

    let active = null;
    let sequence = 0;
    const normalize = (text) => text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase().trim();
    const eligible = (element) => element instanceof HTMLSelectElement && !element.matches(':disabled') && !element.closest('[data-native-select]');

    function close(restoreFocus = false) {
        if (!active) return;
        const current = active;
        active = null;
        current.observer.disconnect();
        current.popup.remove();
        if (restoreFocus && current.select.isConnected) current.select.focus({ preventScroll: true });
    }

    function position() {
        if (!active) return;
        const { select, popup } = active;
        const rect = select.getBoundingClientRect();
        if (!select.isConnected || !rect.width || !rect.height || select.matches(':disabled')) {
            close();
            return;
        }
        const viewport = window.visualViewport;
        const width = viewport?.width ?? window.innerWidth;
        const height = viewport?.height ?? window.innerHeight;
        const offsetTop = viewport?.offsetTop ?? 0;
        const offsetLeft = viewport?.offsetLeft ?? 0;
        const popupWidth = Math.min(Math.max(rect.width, 280), width - 16);
        const below = height + offsetTop - rect.bottom - 12;
        const above = rect.top - offsetTop - 12;
        const useAbove = below < 220 && above > below;
        const popupHeight = Math.max(100, Math.min(360, useAbove ? above : below));
        popup.style.width = `${popupWidth}px`;
        popup.style.maxHeight = `${popupHeight}px`;
        popup.style.left = `${Math.max(offsetLeft + 8, Math.min(rect.left, offsetLeft + width - popupWidth - 8))}px`;
        popup.style.top = `${useAbove ? Math.max(offsetTop + 8, rect.top - popup.getBoundingClientRect().height - 4) : rect.bottom + 4}px`;
    }

    function highlight(index, scroll = false) {
        if (!active) return;
        const { buttons, input } = active;
        active.highlighted = index;
        buttons.forEach((button, i) => button.classList.toggle('is-highlighted', i === index));
        if (buttons[index]) {
            input.setAttribute('aria-activedescendant', buttons[index].id);
            if (scroll) buttons[index].scrollIntoView({ block: 'nearest' });
        } else {
            input.removeAttribute('aria-activedescendant');
        }
    }

    function choose(option) {
        if (!active) return;
        const { select } = active;
        if (!eligible(select) || option.disabled || option.parentElement?.disabled || !select.contains(option)) return;
        const previous = Array.from(select.selectedOptions);
        if (select.multiple) {
            option.selected = !option.selected;
        } else {
            select.selectedIndex = option.index;
        }
        const changed = select.multiple || previous[0] !== option;
        if (select.multiple) render();
        else close(true);
        if (changed) {
            select.dispatchEvent(new Event('input', { bubbles: true }));
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    function render() {
        if (!active) return;
        const { select, input, list, status } = active;
        const query = normalize(input.value);
        const options = Array.from(select.options).filter((option) => {
            const group = option.closest('optgroup');
            return !option.hidden && !group?.hidden && normalize(`${option.label} ${group?.label ?? ''}`).includes(query);
        });
        const fragment = document.createDocumentFragment();
        let previousGroup = null;
        active.buttons = [];
        options.forEach((option) => {
            const group = option.closest('optgroup');
            if (group && group !== previousGroup) {
                const heading = document.createElement('div');
                heading.className = 'select-search-group';
                heading.textContent = group.label;
                heading.setAttribute('role', 'presentation');
                fragment.append(heading);
            }
            previousGroup = group;
            const button = document.createElement('button');
            button.type = 'button';
            button.tabIndex = -1;
            button.id = `${list.id}-option-${option.index}`;
            button.className = 'select-search-option';
            button.setAttribute('role', 'option');
            button.setAttribute('aria-selected', String(option.selected));
            button.disabled = option.disabled || !!group?.disabled;
            button.textContent = option.label || '—';
            button.addEventListener('pointerdown', (event) => event.preventDefault());
            button.addEventListener('click', () => choose(option));
            if (!button.disabled) active.buttons.push(button);
            fragment.append(button);
        });
        list.replaceChildren(fragment);
        status.textContent = options.length ? `${options.length} pilihan${select.multiple ? ' · pilih satu atau lebih' : ''}` : 'Tidak ada pilihan yang cocok.';
        const selected = active.buttons.findIndex((button) => button.getAttribute('aria-selected') === 'true');
        highlight(selected >= 0 ? selected : (active.buttons.length ? 0 : -1));
        position();
    }

    function labelFor(select) {
        if (select.getAttribute('aria-label')) return select.getAttribute('aria-label');
        const labelledBy = select.getAttribute('aria-labelledby');
        if (labelledBy) return labelledBy.split(' ').map((id) => document.getElementById(id)?.textContent ?? '').join(' ').trim();
        const label = select.labels?.[0]?.cloneNode(true);
        label?.querySelectorAll('select, input, button, textarea').forEach((element) => element.remove());
        return label?.textContent.trim() || 'pilihan';
    }

    function open(select, initialQuery = '') {
        if (!eligible(select)) return;
        if (active?.select === select) return;
        close();
        const popup = document.createElement('div');
        popup.className = 'select-search-popup';
        popup.setAttribute('popover', 'manual');
        popup.setAttribute('data-select-search-popup', '');
        const input = document.createElement('input');
        input.type = 'search';
        input.className = 'select-search-input';
        input.autocomplete = 'off';
        input.placeholder = 'Cari pilihan...';
        input.setAttribute('aria-label', `Cari ${labelFor(select)}`);
        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-expanded', 'true');
        input.value = initialQuery;
        const list = document.createElement('div');
        list.className = 'select-search-list';
        list.id = `select-search-list-${++sequence}`;
        list.setAttribute('role', 'listbox');
        list.setAttribute('aria-label', labelFor(select));
        if (select.multiple) list.setAttribute('aria-multiselectable', 'true');
        input.setAttribute('aria-controls', list.id);
        const status = document.createElement('div');
        status.className = 'select-search-status';
        status.setAttribute('role', 'status');
        popup.append(input, list, status);
        const observer = new MutationObserver((records) => {
            if (!select.isConnected || select.matches(':disabled')) close();
            else if (records.some((record) => select.contains(record.target) || record.target === select)) render();
        });
        active = { select, popup, input, list, status, observer, buttons: [], highlighted: -1 };
        (select.closest('dialog') ?? document.body).append(popup);
        if (typeof popup.showPopover === 'function') popup.showPopover();
        render();
        observer.observe(document.body, { subtree: true, childList: true, attributes: true, characterData: true, attributeFilter: ['disabled', 'selected', 'value', 'label', 'hidden'] });
        input.addEventListener('input', render);
        input.addEventListener('keydown', (event) => {
            if (!active || event.isComposing) return;
            if (['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) {
                event.preventDefault();
                const last = active.buttons.length - 1;
                const next = event.key === 'Home' ? 0 : event.key === 'End' ? last : active.highlighted + (event.key === 'ArrowDown' ? 1 : -1);
                highlight(Math.max(0, Math.min(last, next)), true);
            } else if (event.key === 'Enter') {
                event.preventDefault();
                active.buttons[active.highlighted]?.click();
            } else if (event.key === 'Escape') {
                event.preventDefault();
                event.stopPropagation();
                close(true);
            } else if (event.key === 'Tab') {
                close(true);
            }
        });
        input.focus({ preventScroll: true });
        position();
    }

    document.addEventListener('pointerdown', (event) => {
        if (eligible(event.target) && event.button === 0) {
            event.preventDefault();
            open(event.target);
        } else if (active && !active.popup.contains(event.target)) close();
    });
    document.addEventListener('click', (event) => {
        if (eligible(event.target)) {
            event.preventDefault();
            open(event.target);
        }
    });
    document.addEventListener('keydown', (event) => {
        if (!eligible(event.target) || event.ctrlKey || event.metaKey || event.isComposing) return;
        if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(event.key) || event.key.length === 1) {
            event.preventDefault();
            open(event.target, event.key.length === 1 && event.key !== ' ' ? event.key : '');
        }
    });
    document.addEventListener('change', (event) => {
        if (active?.select === event.target) render();
    });
    document.addEventListener('reset', () => close());
    document.addEventListener('livewire:navigating', () => close());
    document.addEventListener('scroll', (event) => {
        if (active && !active.popup.contains(event.target)) position();
    }, true);
    window.addEventListener('resize', position);
    window.visualViewport?.addEventListener('resize', position);
    window.visualViewport?.addEventListener('scroll', position);

    function registerLivewire() {
        if (!window.Livewire) return;
        const refresh = () => {
            if (!active) return;
            if (!active.select.isConnected) close();
            else render();
        };
        window.Livewire.hook('morphed', refresh);
        window.Livewire.hook('partial.morphed', refresh);
    }
    if (window.Livewire) registerLivewire();
    else document.addEventListener('livewire:init', registerLivewire, { once: true });
})();

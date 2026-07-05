function openModal(id) {
    document.getElementById(id)?.classList.add('active');
}

function closeModal(id) {
    document.getElementById(id)?.classList.remove('active');
}

function prettifyLabel(value) {
    return String(value || '')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, letter => letter.toUpperCase())
        .replace(/\bId\b/g, 'ID')
        .trim();
}

function normalizeText(value) {
    return String(value || '').trim().toLowerCase();
}

function harmonizeLegacyPages() {
    const content = document.querySelector('.content');
    if (content && !content.querySelector('.module-shell') && content.querySelector('.page-head')) {
        content.classList.add('legacy-module-shell');
    }

    document.querySelectorAll('.page-head').forEach(head => {
        head.classList.add('module-head', 'legacy-module-head');
        const titleBlock = head.querySelector('div');
        const title = titleBlock?.querySelector('h1');
        if (titleBlock && title && !titleBlock.querySelector('.module-kicker')) {
            const kicker = document.createElement('span');
            kicker.className = 'module-kicker';
            kicker.textContent = 'Gestion';
            titleBlock.insertBefore(kicker, title);
        }
    });

    document.querySelectorAll('.card').forEach(card => {
        const table = card.querySelector(':scope > table');
        if (!table) return;

        card.classList.add('table-card', 'legacy-table-card');
        if (!table.parentElement.classList.contains('table-wrapper')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'table-wrapper';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
    });

    document.querySelectorAll('.modal-box').forEach(box => {
        if (box.querySelector('.modal-titlebar')) return;
        const title = box.querySelector(':scope > h2');
        if (!title) return;

        const titlebar = document.createElement('div');
        titlebar.className = 'modal-titlebar';
        const titleWrap = document.createElement('div');
        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'modal-x';
        close.setAttribute('aria-label', 'Fermer');
        close.innerHTML = '<i class="fa-solid fa-xmark"></i>';
        close.addEventListener('click', () => closeModal(box.closest('.modal')?.id || 'modalForm'));

        box.insertBefore(titlebar, title);
        titleWrap.appendChild(title);
        titlebar.appendChild(titleWrap);
        titlebar.appendChild(close);
    });
}

function decorateButtons() {
    document.querySelectorAll('.btn-primary, .btn-danger, .actions button, .page-head button').forEach(button => {
        if (button.dataset.decorated === '1') return;
        const text = normalizeText(button.textContent);
        let icon = '';

        if (button.classList.contains('btn-danger') || text.includes('supprimer')) {
            icon = 'fa-trash-can';
        } else if (text.includes('ajouter') || text.includes('nouvel')) {
            icon = 'fa-plus';
        } else if (text.includes('enregistrer')) {
            icon = 'fa-floppy-disk';
        } else if (text.includes('annuler')) {
            icon = 'fa-xmark';
        }

        if (icon === 'fa-plus') {
            button.childNodes.forEach(node => {
                if (node.nodeType === Node.TEXT_NODE) {
                    node.textContent = node.textContent.replace(/^\s*\+\s*/, '');
                }
            });
        }

        if (icon === 'fa-trash-can') {
            button.setAttribute('aria-label', button.getAttribute('aria-label') || 'Supprimer');
            button.title = button.title || 'Supprimer';
            button.childNodes.forEach(node => {
                if (node.nodeType === Node.TEXT_NODE) {
                    node.textContent = '';
                }
            });
            button.classList.remove('btn-danger');
            button.classList.add('action-btn', 'delete', 'icon-only');
        }

        if (icon && !button.querySelector('i, svg')) {
            button.insertAdjacentHTML('afterbegin', `<i class="fa-solid ${icon}"></i>`);
        }

        button.dataset.decorated = '1';
    });
}

function decorateTables() {
    document.querySelectorAll('table').forEach(table => {
        table.querySelectorAll('th').forEach(th => {
            th.textContent = prettifyLabel(th.textContent);
        });

        const body = table.tBodies[0];
        if (body && body.rows.length === 0) {
            const colCount = table.tHead?.rows[0]?.cells.length || 1;
            const row = body.insertRow();
            const cell = row.insertCell();
            cell.colSpan = colCount;
            cell.className = 'empty-state';
            cell.innerHTML = '<i class="fa-solid fa-folder-open"></i><strong>Aucune donnée</strong><span>Ajoutez un premier élément pour remplir ce module.</span>';
        }

        table.querySelectorAll('tbody td').forEach(cell => {
            const text = normalizeText(cell.textContent);
            const isActionCell = cell.querySelector('form, button, a');
            if (isActionCell || cell.querySelector('.badge')) return;

            const statusMap = {
                actif: 'success',
                active: 'success',
                ouvert: 'success',
                valide: 'success',
                validé: 'success',
                termine: 'success',
                terminé: 'success',
                disponible: 'success',
                inactif: 'muted',
                ferme: 'muted',
                fermé: 'muted',
                brouillon: 'muted',
                attente: 'warning',
                pending: 'warning',
                encours: 'warning',
                'en cours': 'warning',
                urgent: 'danger',
                retard: 'danger',
                bloque: 'danger',
                bloqué: 'danger'
            };

            if (statusMap[text]) {
                cell.innerHTML = `<span class="badge ${statusMap[text]}">${cell.textContent.trim()}</span>`;
            }
        });
    });
}

function decorateForms() {
    document.querySelectorAll('label').forEach(label => {
        const nodes = Array.from(label.childNodes);
        const textNode = nodes.find(node => node.nodeType === Node.TEXT_NODE && node.textContent.trim());
        if (textNode) {
            textNode.textContent = prettifyLabel(textNode.textContent) + ' ';
        }
    });
}

function bindTableSearch() {
    document.querySelectorAll('[data-table-search]').forEach(input => {
        if (input.dataset.bound === '1') return;
        input.addEventListener('input', () => {
            const table = document.querySelector(input.dataset.tableSearch);
            const query = normalizeText(input.value);
            if (!table) return;

            table.querySelectorAll('tbody tr').forEach(row => {
                const haystack = normalizeText(row.dataset.search || row.textContent);
                row.hidden = query !== '' && !haystack.includes(query);
            });
        });
        input.dataset.bound = '1';
    });
}

function ensureSearchBars() {
    document.querySelectorAll('table').forEach((table, index) => {
        if (table.closest('.dash-page')) return;
        if (table.id === '') {
            table.id = `dataTable${index + 1}`;
        }

        if (document.querySelector(`[data-table-search="#${table.id}"]`)) return;

        const wrapper = table.closest('.card, .table-card') || table.closest('.table-wrapper') || table.parentElement;
        if (!wrapper || wrapper.dataset.searchInjected === '1') return;

        const rowCount = table.querySelectorAll('tbody tr').length;
        const toolbar = document.createElement('div');
        toolbar.className = 'toolbar auto-toolbar';
        toolbar.innerHTML = `
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input class="search-input" type="search" data-table-search="#${table.id}" placeholder="Rechercher...">
            </div>
            <div class="toolbar-summary">
                <span>${rowCount} élément(s)</span>
            </div>
        `;

        wrapper.parentNode.insertBefore(toolbar, wrapper);
        wrapper.dataset.searchInjected = '1';
    });
}

function getCurrentModule() {
    return window.EvotechConfig?.currentPath || window.location.pathname.split('/').filter(Boolean).pop() || '';
}

function getUpdateUrl() {
    return window.EvotechConfig?.updateUrl || '';
}

function getRowEditPayload(row) {
    if (row.dataset.editPayload) {
        try {
            return JSON.parse(row.dataset.editPayload);
        } catch (error) {
            return {};
        }
    }

    const table = row.closest('table');
    const headers = Array.from(table?.querySelectorAll('thead th') || [])
        .map(th => normalizeText(th.textContent).replace(/\s+/g, '_'));
    const cells = Array.from(row.children);
    const payload = {};

    headers.forEach((header, index) => {
        if (!header || header === 'actions') return;
        payload[header] = cells[index]?.textContent.trim() || '';
    });

    return payload;
}

function openEditModal(row) {
    const modal = document.getElementById('modalForm');
    const form = modal?.querySelector('form');
    const updateUrl = getUpdateUrl();
    const module = getCurrentModule();
    const deleteForm = row.querySelector('form[action*="/delete"]');
    const id = deleteForm?.querySelector('input[name="id"]')?.value || row.dataset.recordId || '';

    if (!modal || !form || !updateUrl || !id) return;

    const payload = getRowEditPayload(row);
    form.action = updateUrl;

    form.querySelectorAll('input[name="_module"], input[name="id"]').forEach(input => input.remove());
    form.insertAdjacentHTML('afterbegin', `<input type="hidden" name="_module" value="${module}"><input type="hidden" name="id" value="${id}">`);

    form.querySelectorAll('input, select, textarea').forEach(input => {
        if (['_module', 'id', '_csrf'].includes(input.name)) return;

        if (input.type === 'file') {
            input.disabled = false;
            input.required = false;
            input.value = '';
            const preview = input.closest('label')?.querySelector('.upload-preview');
            if (preview) {
                preview.hidden = true;
                preview.removeAttribute('src');
            }
            return;
        }

        if (input.type === 'password') {
            input.disabled = false;
            input.value = '';
            input.required = false;
            return;
        }

        const hasValue = Object.prototype.hasOwnProperty.call(payload, input.name);

        if (input.type === 'checkbox') {
            input.disabled = !hasValue;
            if (!hasValue) return;
            const value = payload[input.name];
            input.checked = ['1', 'true', 'actif', 'oui', 'on'].includes(normalizeText(value));
            return;
        }

        input.disabled = !hasValue;
        if (hasValue) {
            input.value = payload[input.name];
        } else {
            input.value = '';
        }
    });

    const title = modal.querySelector('h2');
    if (title) title.textContent = 'Modifier';

    openModal('modalForm');
}

function addEditButtons() {
    document.querySelectorAll('tbody tr').forEach(row => {
        const actionCell = row.querySelector('td:last-child');
        const deleteForm = actionCell?.querySelector('form[action*="/delete"]');
        if (!actionCell || !deleteForm || actionCell.querySelector('[data-edit-button]')) return;

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'action-btn edit';
        button.dataset.editButton = '1';
        button.dataset.decorated = '1';
        button.title = 'Modifier';
        button.setAttribute('aria-label', 'Modifier');
        button.innerHTML = '<i class="fa-solid fa-pen-to-square"></i>';
        button.addEventListener('click', () => openEditModal(row));

        actionCell.classList.add('table-actions');
        actionCell.insertBefore(button, deleteForm);
    });
}

function resetCreateModal() {
    document.querySelectorAll('[onclick*="openModal(\'modalForm\')"], [onclick*="openModal(&quot;modalForm&quot;)"]').forEach(trigger => {
        if (trigger.dataset.createBound === '1') return;
        trigger.addEventListener('click', () => {
            const modal = document.getElementById('modalForm');
            const form = modal?.querySelector('form');
            if (!modal || !form) return;

            form.querySelectorAll('input[name="_module"], input[name="id"]').forEach(input => input.remove());
            if (form.dataset.storeAction) {
                form.action = form.dataset.storeAction;
            }
            form.querySelectorAll('input, select, textarea').forEach(input => {
                input.disabled = false;
                if (input.dataset.defaultRequired !== undefined) {
                    input.required = input.dataset.defaultRequired === '1';
                }
            });
            form.reset();
            form.querySelectorAll('.upload-preview').forEach(preview => {
                preview.hidden = true;
                preview.removeAttribute('src');
            });

            const title = modal.querySelector('h2');
            if (title) title.textContent = title.dataset.defaultTitle || title.textContent || 'Ajouter';
        });
        trigger.dataset.createBound = '1';
    });

    document.querySelectorAll('#modalForm form').forEach(form => {
        form.dataset.storeAction = form.action;
        form.querySelectorAll('input, select, textarea').forEach(input => {
            if (input.dataset.defaultRequired === undefined) {
                input.dataset.defaultRequired = input.required ? '1' : '0';
            }
        });
    });

    document.querySelectorAll('#modalForm h2').forEach(title => {
        title.dataset.defaultTitle = title.textContent;
    });
}

function bindUploadPreviews() {
    document.querySelectorAll('input[type="file"][accept*="image"]').forEach(input => {
        if (input.dataset.previewBound === '1') return;

        input.addEventListener('change', () => {
            const preview = input.closest('label')?.querySelector('.upload-preview');
            const file = input.files?.[0];
            if (!preview) return;

            if (!file) {
                preview.hidden = true;
                preview.removeAttribute('src');
                return;
            }

            preview.src = URL.createObjectURL(file);
            preview.hidden = false;
        });

        input.dataset.previewBound = '1';
    });
}

function bindProjectTaskModal() {
    document.querySelectorAll('[data-project-task-button]').forEach(button => {
        if (button.dataset.taskBound === '1') return;

        button.addEventListener('click', () => {
            const projectId = button.dataset.projectId || '';
            const projectTitle = button.dataset.projectTitle || 'Projet';
            const modal = document.getElementById('taskModal');
            const form = modal?.querySelector('form');
            const projectInput = modal?.querySelector('[data-task-project-input]');
            const projectLabel = modal?.querySelector('[data-task-project-label]');
            const agentSelect = modal?.querySelector('[data-task-agent-select]');
            const emptyNote = modal?.querySelector('[data-task-empty-note]');
            const submit = modal?.querySelector('[data-task-submit]');

            if (!modal || !form || !projectInput || !agentSelect) return;

            form.reset();
            projectInput.value = projectId;
            if (projectLabel) projectLabel.textContent = projectTitle;

            let visibleCount = 0;
            Array.from(agentSelect.options).forEach(option => {
                if (!option.value) {
                    option.hidden = false;
                    option.disabled = false;
                    return;
                }

                const isVisible = option.dataset.projectId === projectId;
                option.hidden = !isVisible;
                option.disabled = !isVisible;
                if (isVisible) visibleCount += 1;
            });

            agentSelect.value = '';
            if (emptyNote) emptyNote.hidden = visibleCount > 0;
            if (submit) submit.disabled = visibleCount === 0;

            openModal('taskModal');
        });

        button.dataset.taskBound = '1';
    });
}

function bindParticipantTaskModal() {
    document.querySelectorAll('[data-participant-task-button]').forEach(button => {
        if (button.dataset.participantTaskBound === '1') return;

        button.addEventListener('click', () => {
            const modal = document.getElementById('participantTaskModal');
            const form = modal?.querySelector('form');
            const projectInput = modal?.querySelector('[data-participant-task-project]');
            const agentInput = modal?.querySelector('[data-participant-task-agent]');
            const label = modal?.querySelector('[data-participant-task-label]');

            if (!modal || !form || !projectInput || !agentInput) return;

            form.reset();
            projectInput.value = button.dataset.projectId || projectInput.value || '';
            agentInput.value = button.dataset.agentId || '';
            if (label) label.textContent = button.dataset.agentName || 'Participant sélectionné';

            openModal('participantTaskModal');
        });

        button.dataset.participantTaskBound = '1';
    });
}

function bindTaskTimeModal() {
    document.querySelectorAll('[data-task-time-button]').forEach(button => {
        if (button.dataset.timeBound === '1') return;

        button.addEventListener('click', () => {
            const modal = document.getElementById('taskTimeModal');
            const form = modal?.querySelector('form');
            const taskInput = modal?.querySelector('[data-task-time-task]');
            const agentInput = modal?.querySelector('[data-task-time-agent]');
            const label = modal?.querySelector('[data-task-time-label]');
            const note = modal?.querySelector('[data-task-time-empty]');
            const submit = modal?.querySelector('[data-task-time-submit]');
            const agentId = button.dataset.agentId || '';

            if (!modal || !form || !taskInput || !agentInput) return;

            form.reset();
            taskInput.value = button.dataset.taskId || '';
            agentInput.value = agentId;
            if (label) label.textContent = button.dataset.taskLabel || 'Tâche sélectionnée';
            if (note) note.hidden = agentId !== '' && agentId !== '0';
            if (submit) submit.disabled = agentId === '' || agentId === '0';

            openModal('taskTimeModal');
        });

        button.dataset.timeBound = '1';
    });
}

function bindTimesheetTaskSelect() {
    document.querySelectorAll('select[name="tache_id"]').forEach(select => {
        const form = select.closest('form');
        const agentSelect = form?.querySelector('select[name="agent_id"]');
        if (!agentSelect || select.dataset.timesheetBound === '1') return;

        select.addEventListener('change', () => {
            const selected = select.selectedOptions?.[0];
            const agentId = selected?.dataset.agentId || '';
            if (agentId && agentId !== '0') {
                agentSelect.value = agentId;
            }
        });

        select.dataset.timesheetBound = '1';
    });
}

document.addEventListener('click', event => {
    if (event.target.classList.contains('modal')) {
        event.target.classList.remove('active');
    }
});

function ensureConfirmModal() {
    let modal = document.getElementById('confirmModal');
    if (modal) return modal;

    modal = document.createElement('div');
    modal.id = 'confirmModal';
    modal.className = 'confirm-modal';
    modal.innerHTML = `
        <div class="confirm-card" role="dialog" aria-modal="true" aria-labelledby="confirmTitle" aria-describedby="confirmText">
            <div class="confirm-icon">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div class="confirm-copy">
                <h2 id="confirmTitle">Confirmer l'action</h2>
                <p id="confirmText">Cette opération est sensible. Voulez-vous continuer ?</p>
            </div>
            <div class="confirm-actions">
                <button type="button" class="confirm-cancel">Annuler</button>
                <button type="button" class="confirm-accept">
                    <i class="fa-solid fa-check"></i>
                    Confirmer
                </button>
            </div>
        </div>
    `;

    document.body.appendChild(modal);
    return modal;
}

function askConfirmation(options = {}) {
    const modal = ensureConfirmModal();
    const title = modal.querySelector('#confirmTitle');
    const text = modal.querySelector('#confirmText');
    const accept = modal.querySelector('.confirm-accept');
    const cancel = modal.querySelector('.confirm-cancel');

    title.textContent = options.title || 'Confirmer l\'action';
    text.textContent = options.message || 'Cette opération est sensible. Voulez-vous continuer ?';
    accept.innerHTML = `<i class="fa-solid ${options.icon || 'fa-check'}"></i>${options.acceptLabel || 'Confirmer'}`;

    modal.classList.add('active');
    accept.focus();

    return new Promise(resolve => {
        const cleanup = result => {
            modal.classList.remove('active');
            accept.removeEventListener('click', onAccept);
            cancel.removeEventListener('click', onCancel);
            modal.removeEventListener('click', onBackdrop);
            document.removeEventListener('keydown', onKeydown);
            resolve(result);
        };

        const onAccept = () => cleanup(true);
        const onCancel = () => cleanup(false);
        const onBackdrop = event => {
            if (event.target === modal) cleanup(false);
        };
        const onKeydown = event => {
            if (event.key === 'Escape') cleanup(false);
        };

        accept.addEventListener('click', onAccept);
        cancel.addEventListener('click', onCancel);
        modal.addEventListener('click', onBackdrop);
        document.addEventListener('keydown', onKeydown);
    });
}

document.addEventListener('submit', event => {
    const form = event.target;
    if (!form.matches('form[action*="/delete"]') || form.dataset.confirmed === '1') return;

    event.preventDefault();
    event.stopImmediatePropagation();

    askConfirmation({
        title: 'Supprimer cet élément ?',
        message: 'Cette action peut masquer définitivement l\'enregistrement dans le système.',
        acceptLabel: 'Supprimer',
        icon: 'fa-trash-can'
    }).then(confirmed => {
        if (confirmed) {
            form.dataset.confirmed = '1';
            form.submit();
        }
    });
}, true);

document.addEventListener('click', event => {
    const command = event.target.closest('[data-confirm-command]');
    if (!command || command.dataset.confirmed === '1') return;

    event.preventDefault();

    askConfirmation({
        title: command.dataset.confirmTitle || 'Confirmer cette commande ?',
        message: command.dataset.confirmMessage || 'Voulez-vous continuer ?',
        acceptLabel: command.dataset.confirmAccept || 'Confirmer',
        icon: command.dataset.confirmIcon || 'fa-check'
    }).then(confirmed => {
        if (!confirmed) return;

        command.dataset.confirmed = '1';
        if (command.tagName === 'A' && command.href) {
            window.location.href = command.href;
            return;
        }

        command.click();
    });
});

document.addEventListener('DOMContentLoaded', () => {
    harmonizeLegacyPages();
    resetCreateModal();
    ensureSearchBars();
    decorateButtons();
    decorateTables();
    decorateForms();
    bindTableSearch();
    bindUploadPreviews();
    bindProjectTaskModal();
    bindParticipantTaskModal();
    bindTaskTimeModal();
    bindTimesheetTaskSelect();
    addEditButtons();
});

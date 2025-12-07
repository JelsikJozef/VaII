// Custom JS for VAIICKO project
// Client-side validation, dynamic treasury balance preview, transaction filtering
// and AJAX-based refresh for the ESN Treasury module.

(function () {
    'use strict';

    /**
     * Display a success flash message if the backend stored one in the layout.
     *
     * The root layout writes the last flash message coming from the server
     * into the <body> as a data attribute:
     *   <body data-flash-success="...">
     *
     * This function:
     *  - Reads that value
     *  - Locates an existing .treasury-flash element or creates one
     *  - Injects the message text into the element
     *
     * It is intentionally idempotent: if there is no message, it simply
     * returns without touching the DOM.
     */
    function displayFlashMessage() {
        const body = document.body;
        const flashMessage = body.dataset.flashSuccess;
        if (!flashMessage) {
            return;
        }

        let container = document.querySelector('.treasury-flash');
        if (!container) {
            container = document.createElement('div');
            container.className = 'alert alert-success treasury-flash';
            const main = document.querySelector('.esn-main-content');
            if (main) {
                main.prepend(container);
            } else {
                document.body.prepend(container);
            }
        }

        container.textContent = flashMessage;
    }

    /**
     * Initialize the Treasury transaction form.
     *
     * Responsibilities:
     *  - Read the current treasury balance from the <body> attribute
     *    (data-current-balance) that the layout sets from PHP.
     *  - Show current and projected ("new") balance above the form and
     *    recompute it live when the user changes the type or amount.
     *  - Perform lightweight client-side validation that mirrors most of the
     *    server-side rules in `TreasuryController::store`:
     *      * required: type, amount, description
     *      * type must be either "deposit" or "withdrawal"
     *      * amount must be a positive number
     *      * description must be <= 255 characters
     *      * for withdrawals, do not allow going below zero balance
     *  - Highlight invalid fields using Bootstrap's `is-invalid` class and
     *    update the associated `.invalid-feedback` text when we have a
     *    specific error message.
     *
     * This function only runs on pages that actually contain the
     * #treasury-form element, so it is safe to call on every page.
     */
    function initTreasuryForm() {
        const form = document.getElementById('treasury-form');
        if (!form) {
            return;
        }

        const body = document.body;
        const currentBalanceEl = document.getElementById('current-balance');
        const newBalanceEl = document.getElementById('new-balance');
        const typeEl = document.getElementById('type');
        const amountEl = document.getElementById('amount');
        const descriptionEl = document.getElementById('description');

        const baseBalance = parseFloat(body.dataset.currentBalance || '0') || 0;

        function formatAmount(value) {
            if (Number.isNaN(value)) {
                return '0.00';
            }
            return value.toFixed(2);
        }

        function recalculateBalancePreview() {
            if (!currentBalanceEl || !newBalanceEl || !typeEl || !amountEl) {
                return;
            }

            const type = typeEl.value;
            const amount = parseFloat(amountEl.value.replace(',', '.'));

            currentBalanceEl.textContent = formatAmount(baseBalance);

            let newBalance = baseBalance;
            if (!Number.isNaN(amount) && amount > 0 && (type === 'deposit' || type === 'withdrawal')) {
                if (type === 'deposit') {
                    newBalance = baseBalance + amount;
                } else if (type === 'withdrawal') {
                    newBalance = baseBalance - amount;
                }
            }

            newBalanceEl.textContent = formatAmount(newBalance);
        }

        function clearFieldError(field) {
            if (!field) return;
            field.classList.remove('is-invalid');
        }

        function setFieldError(field, message) {
            if (!field) return;
            field.classList.add('is-invalid');
            const feedback = field.parentElement && field.parentElement.querySelector('.invalid-feedback');
            if (feedback && message) {
                feedback.textContent = message;
            }
        }

        function validateForm() {
            let isValid = true;

            clearFieldError(typeEl);
            clearFieldError(amountEl);
            clearFieldError(descriptionEl);

            const type = typeEl.value;
            if (!type) {
                setFieldError(typeEl, 'Type is required.');
                isValid = false;
            } else if (type !== 'deposit' && type !== 'withdrawal') {
                setFieldError(typeEl, 'Type must be deposit or withdrawal.');
                isValid = false;
            }

            const amountRaw = amountEl.value.trim();
            const amount = parseFloat(amountRaw.replace(',', '.'));
            if (!amountRaw) {
                setFieldError(amountEl, 'Amount is required.');
                isValid = false;
            } else if (Number.isNaN(amount)) {
                setFieldError(amountEl, 'Amount must be a number.');
                isValid = false;
            } else if (amount <= 0) {
                setFieldError(amountEl, 'Amount must be greater than 0.');
                isValid = false;
            }

            const description = descriptionEl.value.trim();
            if (!description) {
                setFieldError(descriptionEl, 'Description is required.');
                isValid = false;
            } else if (description.length > 255) {
                setFieldError(descriptionEl, 'Description must be at most 255 characters.');
                isValid = false;
            }

            if (isValid && type === 'withdrawal') {
                const newBalance = baseBalance - (parseFloat(amountRaw.replace(',', '.')) || 0);
                if (newBalance < 0) {
                    setFieldError(amountEl, 'Withdrawal cannot exceed current balance.');
                    isValid = false;
                }
            }

            if (!isValid) {
                form.classList.add('was-validated');
            }

            return isValid;
        }

        form.addEventListener('submit', function (event) {
            if (!validateForm()) {
                event.preventDefault();
                event.stopPropagation();
            }
        });

        if (typeEl) {
            typeEl.addEventListener('change', recalculateBalancePreview);
        }

        if (amountEl) {
            amountEl.addEventListener('input', recalculateBalancePreview);
        }

        recalculateBalancePreview();
    }

    /**
     * Enable client-side filtering of the transaction list by status.
     *
     * Expects the Treasury index page HTML structure:
     *  - A <select id="transaction-status-filter"> with values:
     *      * "all", "pending", "approved", "rejected"
     *  - A grid container #transactions-grid with child elements that have
     *    the class .treasury-card and a data-status="..." attribute.
     *  - A paragraph #no-transactions-message that serves as an
     *    "empty state" message.
     *
     * When the filter changes, we:
     *  - show/hide cards based on their data-status
     *  - update #no-transactions-message to reflect whether there are
     *    any matching cards for the current filter.
     */
    function initTransactionsFilter() {
        const filterEl = document.getElementById('transaction-status-filter');
        const grid = document.getElementById('transactions-grid');
        const noTxMessage = document.getElementById('no-transactions-message');

        if (!filterEl || !grid) {
            return;
        }

        const cards = grid.querySelectorAll('.treasury-card');

        function applyStatusFilter(selected) {
            let visibleCount = 0;

            cards.forEach(function (card) {
                const status = card.dataset.status || '';
                const visible = selected === 'all' || status === selected;
                card.style.display = visible ? '' : 'none';
                if (visible) {
                    visibleCount += 1;
                }
            });

            if (noTxMessage) {
                if (cards.length === 0) {
                    noTxMessage.classList.remove('d-none');
                } else if (visibleCount === 0) {
                    noTxMessage.textContent = 'No transactions for selected status.';
                    noTxMessage.classList.remove('d-none');
                } else {
                    noTxMessage.classList.add('d-none');
                }
            }
        }

        filterEl.addEventListener('change', function () {
            applyStatusFilter(filterEl.value);
        });

        applyStatusFilter(filterEl.value || 'all');
    }

    /**
     * Render a fresh list of transactions on the Treasury index page.
     *
     * @param {Array<Object>} transactions
     *   Raw transaction objects returned from the /treasury/refresh endpoint.
     *   Each object typically contains:
     *     - type: 'deposit' | 'withdrawal'
     *     - status: 'pending' | 'approved' | 'rejected'
     *     - amount: number-like value (in EUR)
     *     - description/title: short text
     *     - proposed_by: person who created the transaction
     *     - created_at: timestamp string
     *
     * This function completely replaces the contents of #transactions-grid,
     * creating one `.treasury-card` per transaction. It also keeps the current
     * filter value and re-applies it so the user does not lose their selection
     * after a refresh.
     */
    function renderTransactions(transactions) {
        const grid = document.getElementById('transactions-grid');
        const noTxMessage = document.getElementById('no-transactions-message');
        const filterEl = document.getElementById('transaction-status-filter');

        if (!grid || !noTxMessage) {
            return;
        }

        grid.innerHTML = '';
        if (!transactions || transactions.length === 0) {
            noTxMessage.textContent = 'No transactions yet.';
            noTxMessage.classList.remove('d-none');
            return;
        }

        noTxMessage.classList.add('d-none');

        const typeMap = {
            deposit: { label: 'Deposit', chip: 'treasury-chip--deposit', amountClass: 'treasury-amount--positive' },
            withdrawal: { label: 'Withdrawal', chip: 'treasury-chip--withdrawal', amountClass: 'treasury-amount--negative' },
        };

        const statusMap = {
            pending: { label: 'Pending', className: 'treasury-status--pending' },
            approved: { label: 'Approved', className: 'treasury-status--approved' },
            rejected: { label: 'Rejected', className: 'treasury-status--rejected' },
        };

        transactions.forEach(function (tx) {
            const type = tx.type || 'deposit';
            const status = tx.status || 'pending';
            const typeData = typeMap[type] || typeMap.deposit;
            const statusData = statusMap[status] || statusMap.pending;
            const amount = Number(tx.amount || 0);
            const formattedAmount = (type === 'withdrawal' ? '-' : '+') + ' ' + amount.toFixed(2).replace('.', ',') + ' €';

            const card = document.createElement('article');
            card.className = 'treasury-card';
            card.dataset.status = status;
            card.innerHTML = `
                <header class="treasury-card__header">
                    <span class="treasury-chip ${typeData.chip}">${typeData.label}</span>
                    <span class="treasury-amount ${typeData.amountClass}">${formattedAmount}</span>
                </header>
                <h3 class="treasury-card__title">${(tx.title || tx.description || 'Untitled transaction')}</h3>
                <p class="treasury-card__meta">Proposed by ${(tx.proposed_by || 'Unspecified member')}</p>
                <footer class="treasury-card__footer">
                    <span class="treasury-card__date">${tx.created_at || ''}</span>
                    <span class="treasury-status ${statusData.className}">${statusData.label}</span>
                </footer>
            `;
            grid.appendChild(card);
        });

        if (filterEl && filterEl.value) {
            const event = new Event('change');
            filterEl.dispatchEvent(event);
        }
    }

    /**
     * Wire up the "Refresh" button on the Treasury index page.
     *
     * Behaviour:
     *  - Sends a GET request to `?c=treasury&a=refresh` when the user clicks
     *    the button.
     *  - While the request is in-flight, disables the button and adds a
     *    CSS helper class `is-loading` so the UI can reflect the busy state.
     *  - On success:
     *      * Updates the summary balance in the hero section.
     *      * Stores the new balance into `document.body.dataset.currentBalance`
     *        so that if the user navigates to the form from this page, the
     *        preview starts with a fresh value.
     *      * Delegates to `renderTransactions()` to redraw the grid.
     *  - On failure: logs the error and shows a simple alert.
     */
    function initTreasuryRefresh() {
        const refreshBtn = document.getElementById('treasury-refresh-btn');
        if (!refreshBtn) {
            return;
        }

        refreshBtn.addEventListener('click', function () {
            refreshBtn.disabled = true;
            refreshBtn.classList.add('is-loading');

            fetch('?c=treasury&a=refresh')
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('Failed to refresh data');
                    }
                    return response.json();
                })
                .then(function (payload) {
                    const balance = typeof payload.balance === 'number' ? payload.balance : 0;
                    const balanceEl = document.querySelector('.treasury-hero__balance strong');
                    if (balanceEl) {
                        balanceEl.textContent = balance.toFixed(2).replace('.', ',') + ' €';
                    }
                    document.body.dataset.currentBalance = String(balance);
                    renderTransactions(payload.transactions || []);
                })
                .catch(function (error) {
                    console.error(error);
                    alert('Unable to refresh treasury data right now.');
                })
                .finally(function () {
                    refreshBtn.disabled = false;
                    refreshBtn.classList.remove('is-loading');
                });
        });
    }

    /**
     * Entrypoint: once the DOM is ready we attach all behaviour needed for
     * the Treasury-related pages. Each initializer is defensive and will
     * simply exit if the expected DOM elements are missing, so calling them
     * globally is safe.
     */
    document.addEventListener('DOMContentLoaded', function () {
        displayFlashMessage();
        initTreasuryForm();
        initTransactionsFilter();
        initTreasuryRefresh();
    });
})();

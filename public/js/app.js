// Custom JS for VAIICKO project
// Client-side validation, dynamic treasury balance preview, and transaction filtering.

(function () {
    'use strict';

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

    document.addEventListener('DOMContentLoaded', function () {
        displayFlashMessage();
        initTreasuryForm();
        initTransactionsFilter();
        initTreasuryRefresh();
    });
})();

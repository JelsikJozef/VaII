// Custom JS for VAIICKO project
// Client-side validation, dynamic treasury balance preview, and transaction filtering.

(function () {
    'use strict';

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

            // optional UX: prevent obvious overdraft client-side
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
        const table = document.getElementById('transactions-table');
        const noTxMessage = document.getElementById('no-transactions-message');

        if (!filterEl || !table) {
            return;
        }

        const rows = table.querySelectorAll('tbody tr');

        function applyStatusFilter(selected) {
            let visibleCount = 0;

            rows.forEach(function (row) {
                const status = row.dataset.status || '';
                if (selected === 'all' || status === selected) {
                    row.style.display = '';
                    visibleCount += 1;
                } else {
                    row.style.display = 'none';
                }
            });

            if (noTxMessage) {
                if (rows.length === 0) {
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

        // initial filter application
        applyStatusFilter(filterEl.value || 'all');
    }

    document.addEventListener('DOMContentLoaded', function () {
        initTreasuryForm();
        initTransactionsFilter();
    });
})();

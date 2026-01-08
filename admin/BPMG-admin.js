document.addEventListener('DOMContentLoaded', function () {
    const typeSelect = document.getElementById('bpmpesa_payment_type');
    const accountRow = document.getElementById('bpmpesa_account_row');

    function toggleAccountField() {
        accountRow.style.display = (typeSelect.value === 'paybill') ? 'table-row' : 'none';
    }

    toggleAccountField();
    typeSelect.addEventListener('change', toggleAccountField);
});
/**
 * Sistem Informasi Perpajakan - Client JS Utilities
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Tooltips initialization
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // 2. Client-side table live search
    const tableSearchInput = document.getElementById('tableSearchInput');
    if (tableSearchInput) {
        tableSearchInput.addEventListener('keyup', function () {
            const query = this.value.toLowerCase();
            const targetTable = document.querySelector('.table-searchable tbody');
            if (targetTable) {
                const rows = targetTable.getElementsByTagName('tr');
                for (let i = 0; i < rows.length; i++) {
                    const text = rows[i].textContent.toLowerCase();
                    rows[i].style.display = text.indexOf(query) > -1 ? '' : 'none';
                }
            }
        });
    }

    // 3. Confirm Delete handler
    const deleteButtons = document.querySelectorAll('.btn-confirm-delete');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function (e) {
            const name = this.getAttribute('data-name') || 'data ini';
            if (!confirm(`Apakah Anda yakin ingin menghapus ${name}? Tindakan ini tidak dapat dibatalkan.`)) {
                e.preventDefault();
            }
        });
    });

    // 4. Auto format Rupiah on input fields with class .input-currency
    const currencyInputs = document.querySelectorAll('.input-currency');
    currencyInputs.forEach(input => {
        input.addEventListener('input', function (e) {
            let value = this.value.replace(/[^0-9]/g, '');
            if (value) {
                this.value = new Intl.NumberFormat('id-ID').format(value);
            }
        });
    });
});

/**
 * Tax Simulator for Landing Page
 */
function hitungSimulasiPajak() {
    const jenis = document.getElementById('simJenisPajak');
    const nominalInput = document.getElementById('simNominal');
    const hasilDiv = document.getElementById('simHasil');
    const hasilNominal = document.getElementById('simHasilNominal');
    const hasilTarif = document.getElementById('simHasilTarif');
    const hasilDasar = document.getElementById('simHasilDasar');

    if (!jenis || !nominalInput || !hasilDiv) return;

    const tarif = parseFloat(jenis.value);
    const nominal = parseFloat(nominalInput.value.replace(/[^0-9]/g, '')) || 0;

    if (nominal <= 0) {
        alert('Silakan masukkan nilai transaksi atau dasar pengenaan pajak!');
        return;
    }

    const nilaiPajak = (nominal * tarif) / 100;

    hasilDasar.innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(nominal);
    hasilTarif.innerText = tarif + '% (' + jenis.options[jenis.selectedIndex].text + ')';
    hasilNominal.innerText = 'Rp ' + new Intl.NumberFormat('id-ID').format(nilaiPajak);
    hasilDiv.classList.remove('d-none');
}


/* assets/js/main.js */
// Aset JavaScript Utama untuk SPK Kelayakan Kredit KNN

document.addEventListener("DOMContentLoaded", function() {
    // 1. Inisialisasi Efek Fade-In pada Main Content
    const mainContent = document.querySelector(".main-content");
    if (mainContent) {
        mainContent.style.opacity = "0";
        mainContent.style.transition = "opacity 0.4s ease-in-out";
        setTimeout(() => {
            mainContent.style.opacity = "1";
        }, 50);
    }

    // 2. Formatter Rupiah Otomatis untuk Input Keuangan
    // Cukup tambahkan class 'input-rupiah' pada input number/text
    const rupiahInputs = [
        document.getElementById('add_pendapatan'),
        document.getElementById('add_pendapatan_bersama'),
        document.getElementById('add_jumlah_pinjaman'),
        document.getElementById('edit_pendapatan'),
        document.getElementById('edit_pendapatan_bersama'),
        document.getElementById('edit_jumlah_pinjaman'),
        document.getElementById('pendapatan'),
        document.getElementById('pendapatan_bersama'),
        document.getElementById('jumlah_pinjaman')
    ];

    rupiahInputs.forEach(input => {
        if (input) {
            // Kita ubah tipenya dari 'number' ke 'text' agar bisa memuat karakter separator titik (.)
            // Tapi untuk form POST di backend kita perlu membersihkan titiknya terlebih dahulu.
            // Di kode PHP kita sudah menggunakan (float)$_POST[...] yang otomatis mengabaikan teks non-numerik,
            // namun agar aman, input diubah menjadi type="text" dan di-format saat input
            input.setAttribute('type', 'text');
            
            // Format awal jika sudah ada nilainya
            if (input.value) {
                input.value = formatRupiahValue(input.value);
            }

            input.addEventListener('keyup', function(e) {
                this.value = formatRupiahValue(this.value);
            });
        }
    });

    // Menghilangkan nilai non-digit sebelum form disubmit
    // Menghilangkan nilai non-digit sebelum form disubmit
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            rupiahInputs.forEach(input => {
                if (input && form.contains(input)) {
                    // Bersihkan karakter titik separator rupiah agar terbaca sebagai angka float murni di PHP
                    input.value = input.value.replace(/\./g, '');
                }
            });
        });
    });

    // 3. Theme Toggler Event Listener
    const themeToggleBtn = document.getElementById("theme-toggle");
    if (themeToggleBtn) {
        themeToggleBtn.addEventListener("click", function() {
            const currentTheme = document.documentElement.getAttribute("data-theme") || "dark";
            const newTheme = currentTheme === "dark" ? "light" : "dark";
            
            document.documentElement.setAttribute("data-theme", newTheme);
            localStorage.setItem("theme", newTheme);
            
            // Sync Chart.js labels if charts exist on this page
            updateChartTheme(newTheme);
        });
    }
});

/**
 * Memformat string angka menjadi format ribuan Rupiah (contoh: 12000000 menjadi 12.000.000)
 * @param {string} angka 
 * @returns {string}
 */
function formatRupiahValue(angka) {
    let number_string = angka.toString().replace(/[^0-9]/g, ''),
        split = number_string.split(','),
        sisa = split[0].length % 3,
        rupiah = split[0].substr(0, sisa),
        ribuan = split[0].substr(sisa).match(/\d{3}/gi);

    if (ribuan) {
        let separator = sisa ? '.' : '';
        rupiah += separator + ribuan.join('.');
    }

    rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
    return rupiah;
}

/**
 * Update Chart.js label colors dynamically based on active theme
 * @param {string} theme 
 */
function updateChartTheme(theme) {
    const textColor = theme === 'light' ? '#475569' : '#e5e7eb';
    
    // Check global Chart object and instances on window
    if (typeof Chart !== 'undefined') {
        Chart.defaults.color = textColor;
    }
    
    if (window.datasetChart && typeof window.datasetChart.update === 'function') {
        window.datasetChart.options.plugins.legend.labels.color = textColor;
        window.datasetChart.update();
    }
    if (window.riwayatChart && typeof window.riwayatChart.update === 'function') {
        window.riwayatChart.options.plugins.legend.labels.color = textColor;
        window.riwayatChart.update();
    }
}

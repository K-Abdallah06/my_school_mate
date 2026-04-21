// assets/js/main.js

document.addEventListener('DOMContentLoaded', function() {
    // Select all score inputs
    const inputs = document.querySelectorAll('.score-input');

    inputs.forEach(input => {
        input.addEventListener('input', function() {
            // Find the parent row (tr)
            const row = this.closest('tr');
            
            // Get values from this row only
            const ca1 = parseFloat(row.querySelector('input[name="ca1[]"]').value) || 0;
            const ca2 = parseFloat(row.querySelector('input[name="ca2[]"]').value) || 0;
            const exam = parseFloat(row.querySelector('input[name="exam[]"]').value) || 0;

            // Calculate Total
            let total = ca1 + ca2 + exam;

            // Update the Total Cell
            row.querySelector('.total-display').textContent = total.toFixed(1);
        });
    });
});
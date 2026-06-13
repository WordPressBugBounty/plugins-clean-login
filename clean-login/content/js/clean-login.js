(function() {
    function bindToggle(toggleId, fieldId) {
        var toggle = document.getElementById(toggleId);
        var field = document.getElementById(fieldId);
        if (!toggle || !field) return;
        toggle.addEventListener('click', function() {
            field.setAttribute('type', field.getAttribute('type') === 'password' ? 'text' : 'password');
            this.classList.toggle('bi-eye');
        });
    }

    function initStrengthMeter(inputId, containerId) {
        var input = document.getElementById(inputId);
        var container = document.getElementById(containerId);
        if (!input || !container) return;

        var fill  = container.querySelector('.cleanlogin-strength-fill');
        var label = container.querySelector('.cleanlogin-strength-label');

        input.addEventListener('input', function() {
            var val = this.value;

            fill.className = 'cleanlogin-strength-fill';
            if (!val) { label.textContent = ''; return; }

            var score = 0;
            if (val.length >= 8)                         score++;
            if (/[a-z]/.test(val) && /[A-Z]/.test(val)) score++;
            if (/\d/.test(val))                          score++;
            if (/[^a-zA-Z0-9]/.test(val))               score++;
            if (score === 0) score = 1;

            var labels = [
                '',
                container.dataset.labelWeak,
                container.dataset.labelFair,
                container.dataset.labelGood,
                container.dataset.labelStrong
            ];

            fill.classList.add('strength-' + score);
            label.textContent = labels[score] || '';
            label.style.color = { 1: '#e74c3c', 2: '#e67e22', 3: '#f1c40f', 4: '#27ae60' }[score];
        });
    }

    bindToggle('togglePassword', 'pwd');
    bindToggle('togglePassword', 'pass1');
    bindToggle('togglePassword2', 'pass2');
    initStrengthMeter('pass1', 'cleanlogin-strength-meter');
})();

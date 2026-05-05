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

    bindToggle('togglePassword', 'pwd');
    bindToggle('togglePassword', 'pass1');
    bindToggle('togglePassword2', 'pass2');
})();

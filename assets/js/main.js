/**
 * assets/js/main.js — vanilla JS, no frameworks
 * ------------------------------------------------------------
 * 1) Dismiss flash messages (auto after 6s or via ×)
 * 2) Confirm destructive actions (data-confirm on <form>)
 * 3) Light client-side UX hints (server re-validates everything)
 * ------------------------------------------------------------
 */
(function () {
    'use strict';

    /* ---------- 1) Flash messages ---------- */
    var flashes = document.querySelectorAll('.flash');

    Array.prototype.forEach.call(flashes, function (flash) {
        // Auto-hide after 6 seconds
        var timer = setTimeout(function () {
            hideFlash(flash);
        }, 6000);

        // Manual dismiss
        var closeBtn = flash.querySelector('.flash-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                clearTimeout(timer);
                hideFlash(flash);
            });
        }
    });

    function hideFlash(flash) {
        flash.style.transition = 'opacity .3s, transform .3s';
        flash.style.opacity = '0';
        flash.style.transform = 'translateY(-6px)';
        setTimeout(function () {
            if (flash.parentNode) {
                flash.parentNode.removeChild(flash);
            }
        }, 300);
    }

    /* ---------- 2) Confirm before submitting ---------- */
    var confirmForms = document.querySelectorAll('form[data-confirm]');

    Array.prototype.forEach.call(confirmForms, function (form) {
        form.addEventListener('submit', function (event) {
            var message = form.getAttribute('data-confirm');
            if (!window.confirm(message)) {
                event.preventDefault(); // user cancelled
            }
        });
    });

    /* ---------- 3) Light validation UX ---------- */
    // Prevent accidental empty submits on required text/number fields.
    var validatedForms = document.querySelectorAll('form[novalidate]');

    Array.prototype.forEach.call(validatedForms, function (form) {
        form.addEventListener('submit', function (event) {
            var firstInvalid = null;

            Array.prototype.forEach.call(
                form.querySelectorAll('input[required], select[required], textarea[required]'),
                function (field) {
                    if (!field.value.trim()) {
                        field.classList.add('invalid');
                        if (!firstInvalid) {
                            firstInvalid = field;
                        }
                    } else {
                        field.classList.remove('invalid');
                    }
                }
            );

            if (firstInvalid) {
                event.preventDefault();
                firstInvalid.focus();
            }
        });

        // Clear the red border as soon as the user fixes the field
        Array.prototype.forEach.call(
            form.querySelectorAll('input, select, textarea'),
            function (field) {
                field.addEventListener('input', function () {
                    if (field.value.trim()) {
                        field.classList.remove('invalid');
                    }
                });
            }
        );
    });
})();

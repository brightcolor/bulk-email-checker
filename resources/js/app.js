import 'overlayscrollbars';
import { OverlayScrollbars } from 'overlayscrollbars';
import * as bootstrap from 'bootstrap';
import { createApp } from 'admin-lte';

// Make Bootstrap available globally for inline scripts
window.bootstrap = bootstrap;

// Initialize AdminLTE on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    // Init OverlayScrollbars on body (AdminLTE 4 requirement)
    const body = document.querySelector('body');
    if (body) {
        OverlayScrollbars(body, { scrollbars: { autoHide: 'scroll' } });
    }

    // Enable all Bootstrap tooltips
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new bootstrap.Tooltip(el);
    });

    // Enable all Bootstrap popovers
    document.querySelectorAll('[data-bs-toggle="popover"]').forEach(el => {
        new bootstrap.Popover(el);
    });

    // Auto-hide alerts after 5 seconds
    document.querySelectorAll('.alert-autohide').forEach(el => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(el);
            bsAlert.close();
        }, 5000);
    });
});

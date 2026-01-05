import './bootstrap';
import './print-listeners';
import './offline-manager';

// Import SweetAlert2 for production builds

import Swal from 'sweetalert2';

// Make Swal globally available
window.Swal = Swal;

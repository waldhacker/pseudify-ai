import '@fortawesome/fontawesome-free/css/all.css';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'datatables.net-bs5/css/dataTables.bootstrap5.min.css';
import 'toastr/build/toastr.min.css';
import 'bootstrap-select/dist/css/bootstrap-select.min.css';
import 'admin-lte/dist/css/adminlte.min.css';
import '@xterm/xterm/css/xterm.min.css';
import './styles/app.css';

import $ from 'jquery';
import DataTable from 'datatables.net-bs5';
import Toastr from 'toastr';
import { Dropdown } from 'bootstrap';
window.Dropdown = Dropdown;
import 'admin-lte';

Toastr.options = {
  "closeButton": true,
  "debug": false,
  "newestOnTop": false,
  "progressBar": false,
  "positionClass": "toast-top-right",
  "preventDuplicates": false,
  "onclick": null,
  "showDuration": "300",
  "hideDuration": "1000",
  "timeOut": "5000",
  "extendedTimeOut": "1000",
  "showEasing": "swing",
  "hideEasing": "linear",
  "showMethod": "fadeIn",
  "hideMethod": "fadeOut"
};

DataTable.ext.errMode = 'throw';

document.addEventListener('showToast', (event) => {
  switch (event.detail.type) {
    case 'success':
      Toastr.success(event.detail.message);
      break;
    case 'warning':
      Toastr.warning(event.detail.message);
      break;
    case 'error':
    case 'danger':
      Toastr.error(event.detail.message);
      break;
    default:
      Toastr.info(event.detail.message);
  }
}, false);

$(function () {
  $('a[data-redirect]').on('click', function() {
    window.location.href = $(this).attr('data-uri');
  });

  $('table[data-table-type="minimal"]').each(function() {
    new DataTable($(this), {
      paging: true,
      lengthChange: false,
      searching: false,
      ordering: true,
      info: true,
      autoWidth: false,
      responsive: false,
    });
  });
});


import $ from 'jquery'
import DataTable from 'datatables.net-bs5/js/dataTables.bootstrap5.mjs'
import Swal from 'sweetalert2'
import '@fortawesome/fontawesome-free/css/all.min.css';

window.$ = $
window.jQuery = $
window.Swal = Swal
window.DataTable = DataTable

await import('select2/dist/js/select2.full')

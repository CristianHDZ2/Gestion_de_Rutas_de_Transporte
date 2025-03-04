// Función para inicializar tooltips de Bootstrap
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Inicializar popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Auto-cierre de alertas después de 5 segundos
    setTimeout(function() {
        var alertList = document.querySelectorAll('.alert');
        alertList.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    
    // Filtro para tablas
    var searchInput = document.getElementById('searchTable');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            var value = this.value.toLowerCase();
            var table = document.querySelector('.table');
            var rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(function(row) {
                var text = row.textContent.toLowerCase();
                row.style.display = text.indexOf(value) > -1 ? '' : 'none';
            });
        });
    }
    
    // Confirmación para eliminación
var deleteButtons = document.querySelectorAll('.btn-delete:not(.custom-confirm)');
deleteButtons.forEach(function(button) {
    button.addEventListener('click', function(e) {
        if (!confirm('¿Está seguro que desea eliminar este registro? Esta acción no se puede deshacer.')) {
            e.preventDefault();
        }
    });
});
    
    // Manejo del formulario de ingresos
    var ingresoForm = document.getElementById('ingresoForm');
    if (ingresoForm) {
        var tipoSelect = document.getElementById('tipo_dia');
        var montoInput = document.getElementById('monto');
        var estadoSelect = document.getElementById('estado_entrega');
        
        if (tipoSelect) {
            tipoSelect.addEventListener('change', function() {
                // Si es día de descanso, deshabilitar campos de ingreso
                if (this.value === 'Descanso') {
                    montoInput.disabled = true;
                    estadoSelect.disabled = true;
                    montoInput.value = '';
                    estadoSelect.value = '';
                } else {
                    montoInput.disabled = false;
                    estadoSelect.disabled = false;
                }
            });
            
            // Ejecutar el evento change al cargar la página
            tipoSelect.dispatchEvent(new Event('change'));
        }
    }
    
    // Cambio de mes en calendario
    var mesSelect = document.getElementById('mes');
    var anioSelect = document.getElementById('anio');
    if (mesSelect && anioSelect) {
        mesSelect.addEventListener('change', function() {
            document.getElementById('filtroCalendario').submit();
        });
        
        anioSelect.addEventListener('change', function() {
            document.getElementById('filtroCalendario').submit();
        });
    }

    // Imprimir reportes
    var printBtn = document.getElementById('printReport');
    if (printBtn) {
        printBtn.addEventListener('click', function() {
            window.print();
        });
    }
});
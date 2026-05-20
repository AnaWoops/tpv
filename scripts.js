function abrirModal() {
    // ENSEÑAR EL MODAL DE CIERRE DE DIA
    document.getElementById("modal").style.display = "block";
}

function cerrarModal() {
    // OCULTAR EL MODAL DE CIERRE DE DIA
    document.getElementById("modal").style.display = "none";
}

function abrirModalBorrar(id, concepto, importe) {
    // ABRIR EL MODAL PARA CONFIRMAR QUE QUEREMOS BORRAR UNA ENTRADA
    document.getElementById("modal-borrar").style.display = "block";

    // METER EL TEXTO CON LOS DATOS DE LO QUE VAMOS A CARGARNOS
    document.getElementById("texto-borrar").innerHTML =
        "Vas a eliminar la entrada <b>" + concepto + "</b> con importe <b>" + importe + " €</b>";

    // PASARLE EL ID AL ENLACE DE BORRAR PARA QUE SEPA CUAL TIENE QUE ELIMINAR
    document.getElementById("btn-confirm-borrar").href = "borrar.php?id=" + id;
}

function cerrarModalBorrar() {
    // CERRAR EL MODAL DE BORRADO SI NOS ARREPENTIMOS
    document.getElementById("modal-borrar").style.display = "none";
}

// LOGICA PARA LAS TARJETAS Y LOS DESPLEGABLES EN EL MOVIL
document.addEventListener("DOMContentLoaded", function () {

    // ESTO SOLO SE ACTIVA SI LA PANTALLA ES PEQUEÑA
    if (window.innerWidth <= 768) {

        const slides = document.querySelectorAll(".slide");

        if (slides.length) {

            let current = 0;

            slides.forEach((slide) => {

                // CAMBIAR DE TARJETA (DIA/SEMANA/MES) AL HACER CLICK
                slide.addEventListener("click", function (e) {

                    // SI PINCHAMOS EN UN BOTON O FORMULARIO NO CAMBIAMOS DE TARJETA
                    if (e.target.closest("button") || e.target.closest("a") || e.target.closest("form")) {
                        return;
                    }

                    slides[current].classList.remove("active");

                    // PASAR A LA SIGUIENTE TARJETA O VOLVER A LA PRIMERA
                    current = (current + 1) % slides.length;

                    slides[current].classList.add("active");
                });

            });
        }
    }

    // BOTON PARA DESPLEGAR LA LISTA DE MOVIMIENTOS EN EL MOVIL
    const toggle = document.querySelector(".toggle-movimientos");
    const contenedor = document.querySelector(".movimientos-movil");

    if (toggle && contenedor) {
        toggle.addEventListener("click", function () {
            // ABRIR O CERRAR EL CONTENEDOR Y CAMBIAR LA FLECHA
            contenedor.classList.toggle("activo");
            toggle.classList.toggle("activo");
        });
    }

});

// LOGICA PARA SELECCIONAR MOVIMIENTOS Y HACER EL TICKET
function toggleModoTicket() {
    // CAMBIAR EL ESTADO DEL BODY PARA ENTRAR O SALIR DEL MODO TICKET
    document.body.classList.toggle('modo-ticket');
    
    // PONER EL BOTON EN COLOR ACTIVO
    const btnTicket = document.getElementById('btn-modo-ticket');
    btnTicket.classList.toggle('activo');
    
    // SI ACTIVAMOS EL MODO TICKET HACEMOS COSAS EXTRA
    if (document.body.classList.contains('modo-ticket')) {
        
        // ABRIR AUTOMATICAMENTE LA LISTA EN EL MOVIL PARA QUE SE VEAN LOS CHECKBOX
        const contenedorMovimientos = document.querySelector(".movimientos-movil");
        const toggleTitle = document.querySelector(".toggle-movimientos");
        
        if (contenedorMovimientos && !contenedorMovimientos.classList.contains("activo")) {
            contenedorMovimientos.classList.add("activo");
            if (toggleTitle) toggleTitle.classList.add("activo");
        }
        
    } else {
        // SI SALIMOS DEL MODO TICKET LIMPIAMOS TODAS LAS MARCAS DE LOS CUADRITOS
        document.querySelectorAll('.check-ticket').forEach(cb => cb.checked = false);
    }
}

function generarTicket() {
    // COGER TODOS LOS MOVIMIENTOS QUE HEMOS MARCADO CON EL CHECK
    const seleccionados = document.querySelectorAll('.check-ticket:checked');
    
    // CREAR UNA LISTA DE IDS SEPARADOS POR COMAS
    const ids = Array.from(seleccionados).map(cb => cb.value).join(',');
    
    // SI NO HAY NADA MARCADO AVISAMOS AL USUARIO
    if (ids === '') {
        alert('Por favor, selecciona al menos un movimiento para el ticket.');
        return;
    }
    
    // MANDAR LOS IDS A LA PAGINA QUE GENERA EL TICKET FINAL
    window.location.href = 'ticket.php?ids=' + ids;
}

//-----------------------------------------------------------------------------------------

function imprimirTicketHTML() {
    // PASO 1: Hacemos la foto aislando el ancho a 576px (límite del cabezal de 80mm)
    var elemento = document.querySelector('.ticket-preview-container');
    
    if (!elemento) {
        alert("Error: No se encuentra el diseño del ticket en la pantalla.");
        return;
    }

    html2canvas(elemento, {
        backgroundColor: "#FFFFFF",
        scale: 1, // A escala 1 para asegurar que no nos pasamos de memoria
        onclone: function (documentClonado) {
            // Ajustamos el ticket "fantasma" antes de la foto para que encaje perfecto en el rollo
            var ticketClonado = documentClonado.querySelector('.ticket-preview-container');
            ticketClonado.style.width = '576px';
            ticketClonado.style.maxWidth = '576px';
            ticketClonado.style.boxSizing = 'border-box';
            ticketClonado.style.boxShadow = 'none'; // Fuera sombras para imprimir
            ticketClonado.style.borderRadius = '0'; // Bordes rectos
        }
    }).then(function(canvas) {
        // Extraemos solo el código base64 limpio
        var fotoBase64 = canvas.toDataURL("image/png").split(',')[1];
        
        // Pasamos al siguiente paso
        conectarYImprimir(fotoBase64);
        
    }).catch(function(error) {
        alert("Error de Javascript al capturar la imagen: " + error);
    });
}

function conectarYImprimir(fotoBase64) {
    // PASO 2: Conectamos con QZ Tray
    if (!qz.websocket.isActive()) {
        qz.websocket.connect().then(function() {
            mandarFoto(fotoBase64);
        }).catch(function(e) {
            alert("Error: QZ Tray está cerrado o bloqueado. " + e);
        });
    } else {
        mandarFoto(fotoBase64);
    }
}

function mandarFoto(fotoBase64) {
    // PASO 3: Enviamos la foto junto con los comandos físicos corregidos
    qz.printers.find("Printer-POS-80").then(function(printer) {
        var config = qz.configs.create(printer);
        
        var data = [
            // ¡AQUÍ ESTABA EL ERROR! La sintaxis correcta de QZ Tray es format: 'command', flavor: 'hex'
            { type: 'raw', format: 'command', flavor: 'hex', data: '1B40' }, // Reset
            { type: 'raw', format: 'command', flavor: 'hex', data: '1B6101' }, // Centrar
            { 
                type: 'raw', 
                format: 'image', 
                flavor: 'base64', 
                data: fotoBase64, 
                options: { language: "ESCPOS", dotDensity: "double" } // Pasa la foto a calidad gráfica térmica
            },
            { type: 'raw', format: 'command', flavor: 'hex', data: '1B6405' }, // 5 saltos de margen inferior
            { type: 'raw', format: 'command', flavor: 'hex', data: '1D564200' } // Hachazo de la guillotina
        ];
        
        return qz.print(config, data);
        
    }).then(function() {
        // Todo perfecto, volvemos a inicio
        window.location.href = "index.php";
    }).catch(function(e) {
        console.error(e);
        alert("Fallo al enviar la orden final a la impresora: " + e);
    });
}
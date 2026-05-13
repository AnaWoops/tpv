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
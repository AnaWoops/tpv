# 📊 TPV Pequeño Comercio - Gestión Ágil y Sencilla

Este es un sistema de **Terminal Punto de Venta (TPV)** ligero y profesional, diseñado para pequeños negocios que necesitan llevar su contabilidad diaria sin complicaciones. El sistema es totalmente responsive y está optimizado para su uso en tablets, móviles y PCs.

> **Propósito:** Ofrecer una herramienta intuitiva para usuarios sin conocimientos técnicos, centralizando ventas, cierres de caja y reportes.

---

## 🚀 Funcionalidades Principales

* **Registro de Movimientos:** Interfaz rápida para entradas y salidas de caja con autocompletado de precios basado en el histórico de ventas.
* **Gestión de Tickets:** Sistema de selección múltiple para generar tickets de venta formateados para impresoras térmicas de 80mm.
* **Blindaje de Cierre:** Función de bloqueo de registros para días ya cerrados, garantizando la integridad de la contabilidad.
* **Entorno de Pruebas (Sandbox):** Lógica de detección automática que desvía la conexión a una base de datos de testeo para el usuario "pruebas", permitiendo ensayos sin alterar los registros reales.
* **Reportes PDF:** Generación automática de resúmenes (diarios, semanales y mensuales) listos para generar un archivo pdf.
* **Diseño Mobile-First:** Menús desplegables y tarjetas interactivas pensadas para la agilidad en el punto de venta.
* **Seguridad por Roles:** Diferenciación entre administradores (gestión de personal y reapertura de días) y empleados.

---

## 🛠️ Tecnologías y Arquitectura

* **Backend:** PHP 8.x con arquitectura procedimental limpia.
* **Base de Datos:** MySQL / MariaDB (Relacional).
* **Frontend:** HTML5 Semántico, CSS3 moderno (Flexbox/Grid) y JavaScript Vanilla (sin dependencias externas).
* **Librerías:** [DomPDF](https://github.com/dompdf/dompdf) para exportación de documentos.

### 📂 Estructura del Proyecto
* `conexion.php`: Singleton de conexión a DB, configuración de entorno (HTTPS/Timezone) y gestión de entorno Sandbox.
* `seguridad.php`: Middleware de validación de sesiones.
* `index.php`: Panel principal dinámico con estados (Abierto/Cerrado).
* `guardar.php` / `editar.php` / `borrar.php`: CRUD completo de movimientos.
* `ticket.php`: Generador de vista de impresión térmica con lógica de "Ingeniería Inversa" de conceptos.

---

## 🗄️ Esquema de Base de Datos

El sistema se apoya en tres tablas principales:
1.  **`usuarios`**: Almacena credenciales (passwords encriptados con `BCRYPT`) y niveles de acceso.
2.  **`movimientos`**: Registro histórico de cada venta o gasto.
3.  **`cierres`**: Registro de totales y auditoría de días finalizados.

---

## 📦 Instalación y Configuración

1.  **Clonar el repositorio:**
    ```bash
    git clone [https://github.com/AnaWoops/tpv.git](https://github.com/AnaWoops/tpv.git)
    ```
2.  **Base de Datos:**
    * Importar el archivo `estructura_db.sql` desde PHPMyAdmin.
3.  **Configurar Conexión:**
    * Configurar las credenciales en `conexion.php` ($host, $usuario, $password, $base_datos).
4.  **Requisitos del Servidor:**
    * Soporte para HTTPS (recomendado).
    * Extensión `GD` de PHP activa para DomPDF.

---

## 🔒 Seguridad
* **Prevención de SQL Injection:** Uso de `prepare` y `bind_param` en las consultas críticas.
* **Protección de Rutas:** Validación de sesión en cada cabecera para evitar accesos directos por URL.
* **Gestión de Errores:** `mysqli_report(MYSQLI_REPORT_OFF)` configurado para evitar fugas de información técnica en producción.

---
**Desarrollado por Ana María Valcárcel Fernández** - Software enfocado en la digitalización del comercio local.
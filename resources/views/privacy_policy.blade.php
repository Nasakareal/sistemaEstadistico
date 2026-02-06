<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Política de Privacidad | Seguridad Vial / Sistema Estadístico</title>
    <meta name="robots" content="index,follow">
    <style>
        :root{
            --bg:#0b1220;
            --card:#0f1b33;
            --text:#e8eefc;
            --muted:#b9c7e6;
            --line:rgba(255,255,255,.12);
            --accent:#ff1b8f; /* color que ya usas como marca en partes del proyecto */
        }
        body{
            margin:0;
            font-family: Arial, Helvetica, sans-serif;
            background: linear-gradient(180deg, var(--bg), #070b14 70%);
            color: var(--text);
        }
        .wrap{
            max-width: 980px;
            margin: 0 auto;
            padding: 28px 16px 60px;
        }
        .card{
            background: rgba(15,27,51,.92);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 22px 20px;
            box-shadow: 0 14px 40px rgba(0,0,0,.35);
        }
        h1{
            margin: 0 0 8px;
            font-size: 26px;
            letter-spacing: .2px;
        }
        .subtitle{
            color: var(--muted);
            margin: 0 0 14px;
            font-size: 14px;
        }
        .badge{
            display:inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(255,27,143,.14);
            border: 1px solid rgba(255,27,143,.35);
            color: var(--text);
            font-size: 12px;
            margin: 8px 0 0;
        }
        h2{
            margin-top: 22px;
            font-size: 18px;
            color: var(--text);
        }
        p, li{
            color: var(--muted);
            line-height: 1.65;
            font-size: 14.5px;
        }
        ul{
            padding-left: 18px;
        }
        .hr{
            height:1px;
            background: var(--line);
            margin: 16px 0;
        }
        .note{
            background: rgba(255,255,255,.06);
            border: 1px solid var(--line);
            padding: 12px 12px;
            border-radius: 12px;
            color: var(--muted);
        }
        a{
            color: #9fd2ff;
            text-decoration: none;
        }
        a:hover{ text-decoration: underline; }
        .small{
            font-size: 13px;
            color: var(--muted);
        }
        .footer{
            margin-top: 18px;
            color: var(--muted);
            font-size: 13px;
        }
        code{
            color: #d7e5ff;
            background: rgba(255,255,255,.07);
            padding: 2px 6px;
            border-radius: 6px;
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>Política de Privacidad</h1>
        <p class="subtitle">
            Aplicación móvil Android <strong>Seguridad Vial</strong> (también operada junto con el sistema backend
            <strong>Sistema Estadístico</strong>).
        </p>

        <div class="badge">Uso institucional / operativo (Secretaría de Seguridad Pública)</div>

        <div class="hr"></div>

        <p>
            La presente Política de Privacidad describe cómo se recopila, utiliza, almacena y protege la información
            cuando se utiliza la aplicación Android <strong>Seguridad Vial</strong>, conectada al backend
            <strong>Sistema Estadístico</strong>. Esta herramienta está diseñada para uso institucional y operativo,
            principalmente para registro, consulta y seguimiento de eventos (hechos) y funcionalidades de apoyo
            relacionadas con seguridad vial y operación.
        </p>

        <p class="small">
            <strong>Última actualización:</strong> 06 de febrero de 2026.
        </p>

        <h2>1. Responsable del tratamiento</h2>
        <p>
            El responsable del tratamiento de datos es el equipo operador del sistema (proyecto institucional),
            quien administra el backend y el acceso de usuarios autorizados.
        </p>

        <h2>2. Alcance y público objetivo</h2>
        <p>
            Esta aplicación está dirigida a <strong>usuarios autorizados</strong> (personal asignado y con credenciales),
            y no está orientada al público general. El acceso se controla mediante autenticación y permisos.
        </p>

        <h2>3. Información que podemos recopilar</h2>
        <p>
            Dependiendo del rol del usuario y de las funciones habilitadas, la aplicación y el sistema pueden recopilar:
        </p>
        <ul>
            <li>
                <strong>Datos de cuenta:</strong> nombre, correo electrónico y/o teléfono, identificadores internos de usuario,
                roles/permisos asociados.
            </li>
            <li>
                <strong>Datos de autenticación:</strong> tokens de sesión emitidos por el servidor para mantener la sesión iniciada.
                Las contraseñas se almacenan cifradas/hasheadas del lado del servidor.
            </li>
            <li>
                <strong>Ubicación (cuando está habilitada):</strong> si el usuario concede permisos y el rol lo requiere,
                se pueden registrar coordenadas y marcas temporales para funciones como mapa/seguimiento operativo
                (por ejemplo, visualización de unidades).
            </li>
            <li>
                <strong>Contenido operativo capturado por el usuario:</strong> registros de hechos, formularios,
                datos de vehículos, lesionados, servicios, notas, y evidencias (fotos/archivos) cuando se usan módulos
                de carga de documentos.
            </li>
            <li>
                <strong>Datos técnicos mínimos:</strong> información básica para diagnóstico y seguridad (por ejemplo,
                tipo de dispositivo, versión de app, y eventos de error necesarios para corregir fallas).
            </li>
            <li>
                <strong>Notificaciones:</strong> tokens de dispositivo para envío de notificaciones (si la app utiliza notificaciones),
                con el fin de alertas operativas (por ejemplo, eventos de desconexión o avisos internos).
            </li>
        </ul>

        <div class="note">
            <strong>Importante:</strong> La app no está diseñada para recolectar datos sensibles con fines comerciales.
            Todo el uso es institucional y operativo, limitado por roles y permisos.
        </div>

        <h2>4. Permisos del dispositivo</h2>
        <p>La aplicación puede solicitar permisos del dispositivo, tales como:</p>
        <ul>
            <li><strong>Internet:</strong> necesario para comunicarse con el servidor y obtener/guardar información.</li>
            <li>
                <strong>Ubicación (precisa y/o aproximada):</strong> necesaria para funcionalidades de mapa y seguimiento cuando estén activas.
                La ubicación se usa únicamente para las funciones operativas del sistema.
            </li>
            <li>
                <strong>Cámara / almacenamiento (si aplica):</strong> para capturar y/o subir evidencias (fotos/archivos)
                relacionadas con registros operativos (por ejemplo, documentos o fotografías de un hecho).
            </li>
        </ul>
        <p class="small">
            Puedes gestionar permisos desde la configuración del dispositivo. Algunas funciones pueden no operar si se deniegan.
        </p>

        <h2>5. Finalidades del uso de la información</h2>
        <p>La información se utiliza para:</p>
        <ul>
            <li>Autenticar al usuario y permitir acceso a módulos conforme a rol/permisos.</li>
            <li>Registrar, consultar y administrar información operativa (hechos, vehículos, lesionados, servicios, etc.).</li>
            <li>Mostrar mapas y funcionalidades de seguimiento institucional (cuando esté habilitado y autorizado).</li>
            <li>Generar reportes internos y documentos (por ejemplo, exportaciones o formatos institucionales).</li>
            <li>Mantener seguridad del sistema: auditoría, bitácoras internas y prevención de accesos no autorizados.</li>
            <li>Enviar notificaciones operativas (si se encuentran habilitadas) para alertas internas.</li>
        </ul>

        <h2>6. Base legal y control de acceso</h2>
        <p>
            El acceso al sistema es restringido. Cada usuario se autentica y se le asignan permisos mediante el backend.
            Las acciones disponibles dentro de la app dependen de su rol y autorizaciones.
        </p>

        <h2>7. Compartición de datos</h2>
        <p>
            No vendemos datos personales ni los compartimos con fines publicitarios.
            La información se procesa dentro de infraestructura controlada por el proyecto institucional.
        </p>
        <p>
            Podremos compartir información únicamente en estos casos:
        </p>
        <ul>
            <li><strong>Operación del servicio:</strong> proveedores de infraestructura (servidores, alojamiento, dominios) necesarios para operar el sistema.</li>
            <li><strong>Obligación legal:</strong> cuando una autoridad competente lo requiera conforme a ley aplicable.</li>
            <li><strong>Administración interna:</strong> personal autorizado para operación y soporte del sistema.</li>
        </ul>

        <h2>8. Almacenamiento, retención y eliminación</h2>
        <p>
            La información se almacena en el servidor del sistema. Se conserva el tiempo necesario para operación,
            auditoría y cumplimiento de requerimientos institucionales.
        </p>
        <p>
            Si se requiere eliminación o corrección de datos, debe solicitarse por los canales institucionales.
            La eliminación puede estar sujeta a restricciones por auditoría/obligaciones de conservación institucional.
        </p>

        <h2>9. Seguridad de la información</h2>
        <p>
            Se aplican medidas razonables de seguridad para proteger la información, incluyendo controles de acceso,
            autenticación, restricciones por permisos y prácticas de resguardo en el servidor.
        </p>
        <p>
            Aun así, ningún sistema es 100% infalible. Se recomienda mantener credenciales seguras y no compartir accesos.
        </p>

        <h2>10. Menores de edad</h2>
        <p>
            Esta aplicación es de uso institucional y no está dirigida a menores de edad.
        </p>

        <h2>11. Cambios a esta Política</h2>
        <p>
            Esta Política puede actualizarse para reflejar cambios en la app, el backend o requerimientos normativos.
            La versión vigente estará disponible en esta misma URL.
        </p>

        <h2>12. Contacto</h2>
        <p>
            Para dudas sobre esta Política o solicitudes relacionadas con datos, contacta al área responsable del sistema.
        </p>
        <ul>
            <li><strong>Correo:</strong> <a href="mailto:contacto@rrb-soluciones.com">contacto@rrb-soluciones.com</a></li>
            <li><strong>Sitio:</strong> <a href="https://rrb-soluciones.com">rrb-soluciones.com</a></li>
        </ul>

        <div class="hr"></div>

        <p class="footer">
            Este documento se publica para cumplir los requisitos de publicación de aplicaciones y transparencia.
        </p>
    </div>
</div>
</body>
</html>

<?php
/**
 * EJEMPLO de configuracion SMTP del modulo de Equipos Rentados.
 *
 * INSTRUCCIONES (en el servidor, una sola vez):
 *   1. Copie este archivo como config/mail.php:
 *        cp config/mail.example.php config/mail.php
 *   2. Edite config/mail.php y coloque la clave real del buzon.
 *   3. Proteja el archivo:  chmod 640 config/mail.php
 *
 * config/mail.php esta en .gitignore: NUNCA se sube al repositorio.
 */

return [
    'host'      => 'app.arconel.gob.ec',
    'puerto'    => 587,                               // STARTTLS
    'usuario'   => 'notificaciones@app.arconel.gob.ec',
    'clave'     => 'COLOQUE-AQUI-LA-CLAVE-REAL',
    'remitente' => 'notificaciones@app.arconel.gob.ec',
    'nombre'    => 'Sistema de Formularios ARCONEL',
];

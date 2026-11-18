<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clave secreta interna para firmar los tokens de sesión por rol
define('SECRET_KEY_NOVILLERO', 'Taqueria_Novillero_Token_2026_Key');
// Tiempo máximo de inactividad en segundos (ej. 900 seg = 15 minutos)
define('TIEMPO_EXPIRACION_SESION', 900);

/**
 * Genera un token HMAC firmado con el usuario, su rol y su ID de sesión
 */
function generarTokenSesion($usuario, $rol) {
    return hash_hmac('sha256', $usuario . '|' . $rol . '|' . session_id(), SECRET_KEY_NOVILLERO);
}

/**
 * Valida sesión activa, token de seguridad, expiración por inactividad y permisos de rol
 * @param string|null $rol_requerido 'administrador' para rutas admin, null para rutas generales
 */
function verificarAccesoRuta($rol_requerido = null) {
    // 1. Verificar que existan credenciales y token en la sesión
    if (!isset($_SESSION['usuario']) || !isset($_SESSION['rol']) || !isset($_SESSION['token_sesion'])) {
        header("Location: login.php?error=no_autenticado");
        exit();
    }

    // 2. Criterio de Aceptación: Expiración automática tras periodo de inactividad
    if (isset($_SESSION['ultima_actividad'])) {
        $inactividad = time() - $_SESSION['ultima_actividad'];
        if ($inactividad > TIEMPO_EXPIRACION_SESION) {
            session_unset();
            session_destroy();
            header("Location: login.php?error=sesion_expirada");
            exit();
        }
    }
    // Actualizar marca de tiempo de actividad
    $_SESSION['ultima_actividad'] = time();

    // 3. Validar integridad del token de sesión contra el rol actual
    $token_calculado = generarTokenSesion($_SESSION['usuario'], $_SESSION['rol']);
    if (!hash_equals($token_calculado, $_SESSION['token_sesion'])) {
        session_unset();
        session_destroy();
        header("Location: login.php?error=token_invalido");
        exit();
    }

    // 4. Criterio de Aceptación: Si un "trabajador" intenta acceder a rutas de /admin -> Error 403
    if ($rol_requerido === 'administrador' && $_SESSION['rol'] !== 'administrador') {
        http_response_code(403);
        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Error 403 - Acceso Restringido</title>
            <style>
                body {
                    background-color: #f4ece1;
                    font-family: 'Courier New', Courier, monospace;
                    color: #2c1d11;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                    margin: 0;
                }
                .card-403 {
                    background-color: #ffffff;
                    border: 3px solid #d32f2f;
                    border-radius: 20px;
                    padding: 35px;
                    max-width: 480px;
                    text-align: center;
                    box-shadow: 0 6px 16px rgba(0,0,0,0.15);
                }
                .codigo-error {
                    font-size: 54px;
                    font-weight: bold;
                    color: #d32f2f;
                    margin: 0;
                }
                h2 {
                    color: #3d2514;
                    text-transform: uppercase;
                    border-bottom: 3px double #d32f2f;
                    padding-bottom: 10px;
                    margin-top: 5px;
                }
                p {
                    font-size: 14px;
                    line-height: 1.5;
                    margin-bottom: 25px;
                }
                .acciones {
                    display: flex;
                    gap: 12px;
                    justify-content: center;
                }
                .btn {
                    padding: 10px 18px;
                    border-radius: 10px;
                    text-decoration: none;
                    font-weight: bold;
                    font-size: 12px;
                    text-transform: uppercase;
                    color: #ffffff;
                }
                .btn-panel { background-color: #2c1d11; }
                .btn-login { background-color: #d32f2f; }
            </style>
        </head>
        <body>
            <div class="card-403">
                <div class="codigo-error">403</div>
                <h2>Acceso Denegado</h2>
                <p>
                    Hola <strong><?php echo htmlspecialchars($_SESSION['usuario']); ?></strong>, tu cuenta tiene rol de 
                    <strong><?php echo strtoupper(htmlspecialchars($_SESSION['rol'])); ?></strong> y no cuenta con un token de permisos para acceder a los módulos de Administración.
                </p>
                <div class="acciones">
                    <a href="panel_trabajador.php" class="btn btn-panel">← Ir a mi Panel</a>
                    <a href="login.php" class="btn btn-login">Cambiar de Cuenta</a>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit();
    }
}
?>
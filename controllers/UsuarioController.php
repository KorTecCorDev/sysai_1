<?php

namespace Controllers;

use MVC\Router;
use Model\Poa;
use Model\Cargo;
use Model\Persona;
use Model\Usuario;
use Model\Programa;
use Model\UsuarioVista;
use Model\CoordinadorPrograma;
use Model\ProgramasinCoordinadorVista;

class UsuarioController
{
    public static function index(Router $router)
    {
        exigirRol([1]); // Solo administradores gestionan usuarios
        $usuarios = UsuarioVista::all();
        //Mostrando el mensaje condicional
        $resultado = $_GET['resultado'] ?? null;
        $router->render('usuario/admin', [
            'usuarios' => $usuarios,
            'resultado' => $resultado
        ]);
    }

    public static function crear(Router $router)
    {
        exigirRol([1]); // Solo administradores
        $persona = new Persona();
        $usuario = new Usuario();
        $errores = Usuario::getErrores();

        $cargos = Cargo::all();
        // Programas sin coordinador activo (la vista ya deriva de coordinador_programa)
        $programas = self::programasDisponibles();
        // Habilitar el select de programa solo si hay programas sin coordinador
        $cmbstatus = !empty($programas);
        // Programa preseleccionado en el formulario (ninguno al crear)
        $programa_actual_id = 0;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $persona = new Persona($_POST['persona']);
            $usuario = new Usuario($_POST['usuario']);
            // ¿Se eligió un programa? (solo aplica a coordinadores)
            $programaSeleccionado = validarPropiedadArray($_POST, 'coordinador_programa', 'programa_id');
            $programa_actual_id = $_POST['coordinador_programa']['programa_id'] ?? 0;

            // Hash del password al crear el usuario
            $usuario->password = password_hash($usuario->password, PASSWORD_DEFAULT);

            $errores = $persona->validar();
            $errores = $usuario->validar();

            if (empty($errores)) {
                // 1) Persona
                if (!$persona->guardarsinRedireccion()) {
                    $errores[] = 'No se pudo registrar la persona. Intente nuevamente.';
                } else {
                    // 2) Usuario (vinculado a la persona recién creada)
                    $usuario->persona_id = $persona->devolverIdLastInsercion();
                    $persona->id = $usuario->persona_id;
                    if (!$usuario->guardarsinRedireccion()) {
                        // No dejar personas huérfanas si el usuario no se pudo crear
                        // (p. ej. email duplicado que atrapó el UNIQUE de la migr. 032).
                        $persona->eliminarsinRedireccion();
                        $errores[] = 'No se pudo registrar el usuario. Intente nuevamente.';
                    } else {
                        $nuevoUsuarioId = $usuario->devolverIdLastInsercion();

                        // 3) Vínculo coordinador-programa (solo coordinadores con programa elegido)
                        if ($usuario->cargo_id == 3 && $programaSeleccionado) {
                            CoordinadorPrograma::asignarPrograma(
                                $nuevoUsuarioId,
                                $_POST['coordinador_programa']['programa_id']
                            );
                        }
                        header("Location: /usuario/admin?resultado=1");
                        exit();
                    }
                }
            }
        }
        $router->render('usuario/crear', [
            'persona' => $persona,
            'programas' => $programas,
            'usuario' => $usuario,
            'cargos' => $cargos,
            'cmbstatus' => $cmbstatus,
            'programa_actual_id' => $programa_actual_id,
            'errores' => $errores
        ]);
    }

    public static function actualizar(Router $router)
    {
        exigirRol([1]); // Solo administradores
        $id = validarORedireccionar('/usuario/admin');
        $usuario = Usuario::find($id);
        $persona = Persona::find($usuario->persona_id);
        $cargos = Cargo::all();

        // Programas sin coordinador + el programa actual del coordinador (si tiene)
        $programas = self::programasDisponibles();
        $vinculo = CoordinadorPrograma::vinculoActivoPorUsuario($id);
        $programa_actual_id = 0;
        $cmbstatus = false;
        if ((int) $usuario->cargo_id === 3) {
            // Para coordinadores siempre se muestra el select de programa
            $cmbstatus = true;
            if ($vinculo) {
                $programa_actual_id = (int) $vinculo->programa_id;
                $programaActual = Programa::find($programa_actual_id);
                if ($programaActual) {
                    $programas[] = $programaActual;
                }
            }
        }

        $errores = Usuario::getErrores();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // A2 — mass assignment: por POST solo se editan los datos del formulario.
            // El password se cambia únicamente por el flujo de recuperación; los ids,
            // persona_id y reset_token no se reasignan nunca desde el formulario.
            $protegidos = [$usuario->id, $usuario->persona_id, $usuario->password, $usuario->reset_token, $persona->id];
            $persona->sincronizar($_POST['persona']);
            $usuario->sincronizar($_POST['usuario']);
            [$usuario->id, $usuario->persona_id, $usuario->password, $usuario->reset_token, $persona->id] = $protegidos;
            $programaSeleccionadoId = (int) ($_POST['coordinador_programa']['programa_id'] ?? 0);

            $errores = $persona->validar();
            $errores = $usuario->validar();

            if (empty($errores)) {
                // Auditoría: registrar quién hace el cambio
                Usuario::setUsuarioActual();
                $persona->guardarsinRedireccion();
                $usuario->guardarsinRedireccion();

                // Gestión del vínculo coordinador-programa
                if ((int) $usuario->cargo_id === 3) {
                    if ($programaSeleccionadoId > 0) {
                        // Asigna (o reasigna) el programa respetando las invariantes
                        CoordinadorPrograma::asignarPrograma($usuario->id, $programaSeleccionadoId);
                    } else {
                        // Coordinador sin programa elegido → se le retira el vínculo
                        CoordinadorPrograma::desactivarPorUsuario($usuario->id);
                    }
                } else {
                    // Dejó de ser coordinador → liberar cualquier vínculo activo
                    CoordinadorPrograma::desactivarPorUsuario($usuario->id);
                }

                header("Location: /usuario/admin?resultado=2");
                exit();
            }
        }
        $router->render('usuario/actualizar', [
            'persona' => $persona,
            'programas' => $programas,
            'usuario' => $usuario,
            'cargos' => $cargos,
            'cmbstatus' => $cmbstatus,
            'errores' => $errores,
            'programa_actual_id' => $programa_actual_id
        ]);
    }

    public static function eliminar(Router $router)
    {
        exigirRol([1]); // Solo administradores
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $idusuario = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            $tipo = $_POST['tipo'] ?? '';
            if ($idusuario && validarTipoContenido($tipo)) {
                $usuario = Usuario::find($idusuario);
                if ($usuario) {
                    $idpersona = filter_var($usuario->persona_id, FILTER_VALIDATE_INT);
                    $persona = $idpersona ? Persona::find($idpersona) : null;

                    // Auditoría
                    Usuario::setUsuarioActual();

                    // 1) Quitar vínculos coordinador-programa (FK → usuario)
                    CoordinadorPrograma::eliminarPorUsuario($idusuario);

                    // 2) Eliminar los documentos ligados al usuario por FK: poa,
                    //    poa_indicadores y poa_rendicion (vestigial). Sin esto el
                    //    DELETE del usuario fallaba EN SILENCIO (MYSQLI_REPORT_OFF)
                    //    y el admin veía "Eliminado correctamente" con el usuario intacto.
                    foreach (Poa::findxatributo('usuario_id', $idusuario) as $poa) {
                        $poa->eliminarsinRedireccion();
                    }
                    foreach (\Model\PoaIndicadores::findxatributo('usuario_id', $idusuario) as $poai) {
                        $poai->eliminarsinRedireccion();
                    }
                    Usuario::ejecutarPreparado(
                        "DELETE FROM poa_rendicion WHERE usuario_id = ?",
                        'i',
                        [$idusuario]
                    );

                    // 3) Eliminar usuario y su persona — VERIFICANDO el resultado:
                    //    si algo lo impide, se informa (resultado=25), no se miente.
                    if (!$usuario->eliminarsinRedireccion()) {
                        header("Location: /usuario/admin?resultado=25");
                        exit();
                    }
                    if ($persona) {
                        $persona->eliminarsinRedireccion();
                    }

                    header("Location: /usuario/admin?resultado=3");
                    exit();
                }
            }
        }
    }

    // Programas sin coordinador activo, como objetos Programa para el formulario.
    private static function programasDisponibles(): array
    {
        $programas = [];
        foreach (ProgramasinCoordinadorVista::all() as $pv) {
            $programa = Programa::find($pv->programa_id);
            if ($programa) {
                $programas[] = $programa;
            }
        }
        return $programas;
    }
}

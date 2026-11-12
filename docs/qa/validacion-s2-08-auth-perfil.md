# S2-08 — Validación de autenticación y perfil

## Objetivo

Comprobar el funcionamiento y la adaptación móvil de los
módulos de inicio de sesión, registro y perfil de UniRoute.

## Entorno

- Aplicación: UniRoute
- Entorno: desarrollo local
- Framework: Laravel con React e Inertia
- Navegador: Google Chrome
- Resolución móvil: 375 × 812 px
- Pruebas automatizadas: Laravel Feature Tests

## Revisión visual

| Elemento                             | Resultado        |
| ------------------------------------ | ---------------- |
| Formulario de registro en escritorio | Correcto         |
| Registro adaptable a 375 px          | Correcto         |
| Campos de nombre, matrícula y correo | Correcto         |
| Selector de campus                   | Correcto         |
| Indicador de seguridad de contraseña | Correcto         |
| Confirmación de contraseña           | Campo disponible |
| Perfil del estudiante en móvil       | Correcto         |
| Datos del estudiante                 | Visibles         |
| Matrícula de solo lectura            | Correcto         |
| Navegación inferior                  | Visible          |

## Pruebas automatizadas

Comando ejecutado:

`php artisan test tests/Feature/Auth/RegistrationTest.php tests/Feature/Auth/AuthenticationTest.php tests/Feature/Settings/ProfileUpdateTest.php`

| Archivo                | Pruebas aprobadas |
| ---------------------- | ----------------: |
| RegistrationTest.php   |                 4 |
| AuthenticationTest.php |                 6 |
| ProfileUpdateTest.php  |                 8 |
| **Total**              |            **18** |

Resultado: **18 pruebas aprobadas, 57 aserciones**.

## Funcionalidades verificadas

- Registro de estudiantes y conservación del rol.
- Rechazo de matrículas duplicadas y campus inexistentes.
- Inicio y cierre de sesión.
- Validación de contraseñas incorrectas.
- Autenticación de dos factores y limitación de intentos.
- Actualización de información del perfil.
- Modificación de campus y preferencias de avisos.
- Unicidad del correo electrónico.
- Validaciones para eliminar cuentas.

## Limitaciones

- La creación de una cuenta nueva se validó mediante pruebas
  automatizadas, no mediante un registro manual completo.
- No se realizó una auditoría completa de accesibilidad.
- No se verificó el envío real de correos electrónicos.
- No se modificó el código de autenticación ni del perfil.

## Conclusión

Los módulos revisados presentan una adaptación visual móvil
adecuada y superaron las 18 pruebas funcionales ejecutadas.

No se identificaron fallos en las verificaciones realizadas.

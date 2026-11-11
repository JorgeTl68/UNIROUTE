# UNIROUTE

Proyecto escolar UNIROUTE solucionando problemas de transporte

## Base del proyecto

Laravel 13 con el starter oficial de React: React 19, Inertia 3, TypeScript,
Tailwind CSS 4, shadcn/ui y rutas tipadas con Wayfinder. Incluye registro,
inicio de sesión, recuperación de contraseña y configuración de cuenta.

Se conservan los ajustes locales de IDAP para MySQL, sesiones en archivos,
caché en archivos y colas en base de datos. UNIROUTE utiliza su propia base
`uniroute`, clave de aplicación y configuración de entorno.

## Instalación local

Requisitos: PHP 8.3 o superior, Composer, MySQL y Node.js 22.18+ de la rama 22
o 24.11+ de la rama 24. Se incluye `.nvmrc` con Node 22.22.0.

1. Crear una base MySQL vacía llamada `uniroute`.
2. Ejecutar `composer install` y `npm install`.
3. Copiar `.env.example` a `.env` y ajustar las credenciales de MySQL.
4. Ejecutar `php artisan key:generate` y `php artisan migrate`.
5. Ejecutar `npm run build`.

En PowerShell, usar `npm.cmd` si la política de ejecución bloquea `npm.ps1`.
Si el Node predeterminado es 25, usar el Node compatible de Laragon solo
en la terminal actual:

```powershell
$env:Path = 'C:\laragon\bin\nodejs\node-v22;' + $env:Path
node --version
```

## Desarrollo

Ejecutar en terminales separadas:

```sh
php artisan serve
npm run dev
php artisan queue:listen --tries=1
```

La aplicación estará en `http://localhost:8000`. Con Laragon, el sitio debe
apuntar a `public/` y `APP_URL` debe coincidir con su dominio local.
El correo se escribe en el registro local hasta configurar un proveedor SMTP.

## Verificación

```sh
composer validate --strict
composer test
npm run check
npm run types:check
npm run build
```

Las pruebas usan SQLite en memoria y no modifican la base MySQL local.
El starter actual utiliza Vite+ para formato y lint. Las rutas TypeScript
se generan con `php artisan wayfinder:generate --with-form` o al compilar.

Documentación oficial: https://laravel.com/docs/13.x/starter-kits

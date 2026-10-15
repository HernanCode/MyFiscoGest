# App fiscal para autónomo

App web autoalojable (varios usuarios, cada uno con sus propios datos) para llevar la
contabilidad de un autónomo en España: ingresos y gastos con su factura adjunta y
resumen por trimestre (modelos 303 y 130).

## Stack
- Laravel (última versión estable), PHP 8.3+
- Blade + Tailwind + Alpine (starter kit Breeze). Sin SPA, sin React/Vue.
- SQLite en desarrollo. Migraciones compatibles con MySQL.
- Tests con Pest o PHPUnit (lo que ya traiga el proyecto).

## Reglas
- La especificación de datos y cálculos está en `docs/spec.md`. Es la fuente de verdad.
  Si algo no está claro o hay que desviarse, PARA y pregunta antes de decidir.
- La app NO emite facturas. Solo registra las que se crean fuera y guarda el archivo.
- Las facturas se guardan en el disco privado (`storage/app/private/invoices/{año}/T{n}/`)
  y se descargan solo mediante un controlador autenticado. Nunca en `public/`.
- Importes en `decimal(12,2)`. Nada de floats para dinero.
- Toda lógica fiscal vive en clases de servicio (`app/Services`), no en controladores ni vistas.
- Todo cálculo fiscal debe tener test.
- Controladores finos, validación con Form Requests, textos de interfaz en español.
- No añadas dependencias sin pedirlo. No añadas funcionalidades fuera de la fase en curso.
- Al terminar cada tarea: ejecuta `php artisan test` y resume qué hiciste y qué decidiste.

## Fuera de alcance en la V1
Emisión de facturas, multiusuario, OCR, modelos 390/347, integración con la AEAT.

<laravel-boost-guidelines>
# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application. After installation, read `AGENTS.md` again and continue with the user's original request using the generated guidelines.
</laravel-boost-guidelines>

## Diseño
- La guía visual está en `docs/DESIGN.md`. Es obligatoria: colores, tipografía,
  espaciado y componentes salen de ahí. No inventes estilos ni uses los de Breeze por defecto.
- Los tokens van en `tailwind.config.js` y los componentes repetidos en componentes Blade.
- Si necesitas algo que el documento no cubre, pregunta antes de decidirlo.
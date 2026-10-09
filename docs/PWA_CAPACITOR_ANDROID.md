# Implementación PWA y aplicación híbrida Android — COOPAECA

## 1. Clasificación del producto

COOPAECA es un aplicativo híbrido. La interfaz se desarrolla con HTML, CSS y JavaScript; la lógica de negocio y los datos se ejecutan en el servidor mediante PHP y MySQL; y Capacitor proporciona el contenedor nativo que permite distribuir el sistema como una aplicación Android.

La PWA mejora la experiencia web instalable, mientras que Capacitor es la capa que genera el proyecto Android y permite obtener el APK o AAB.

## 2. Arquitectura

```text
Aplicación Android (Capacitor)
          │ HTTPS
          ▼
https://coopaeca.org.pe
          │
          ├── Interfaz responsive/PWA: HTML + CSS + JavaScript
          ├── Backend: PHP 8
          └── Base de datos: MySQL/MariaDB
```

El APK no incorpora PHP ni MySQL. Necesita conexión para autenticarse, consultar datos y ejecutar operaciones del sistema.

## 3. Componentes PWA implementados

- `public/manifest.webmanifest`: identidad, modo standalone, colores, orientación, iconos y accesos directos.
- `public/sw.js`: Service Worker con caché limitada a recursos estáticos.
- `public/offline.html`: respuesta informativa cuando no existe conexión.
- `public/assets/js/pwa.js`: registro controlado del Service Worker.
- Iconos de 192 px, 512 px y variante maskable.
- Metadatos PWA incorporados en las vistas pública, autenticación y panel administrativo.

Por seguridad, el Service Worker no almacena páginas privadas ni respuestas dinámicas del dashboard.

## 4. Componentes Capacitor

- Identificador: `com.ccopaeca.app`.
- Nombre: `COOPAECA`.
- Entrada de la APK: `https://coopaeca.org.pe/dashboard` (solicita iniciar sesión cuando no existe una sesión activa).
- Tráfico HTTP sin cifrar: deshabilitado.
- Contenido mixto: deshabilitado.
- Depuración de WebView en producción: deshabilitada.
- Plataforma: Android.
- Capacitor: 8.5.2.
- SDK mínimo: Android 7.0 (API 24).
- SDK objetivo: API 36.

## 5. Preparación del servidor

Antes de generar una versión final se debe publicar la PWA en producción y comprobar:

1. Certificado HTTPS válido.
2. Acceso a `/manifest.webmanifest` y `/sw.js`.
3. Iconos accesibles desde `/assets/icons/`.
4. Inicio de sesión y cierre de sesión desde un teléfono.
5. Carga de imágenes, comprobantes y archivos.
6. Funcionamiento de la pantalla offline.

## 6. Comandos de desarrollo

```powershell
npm.cmd install
npx.cmd cap sync android
npx.cmd cap open android
```

La compilación verificada utiliza el JDK 23. El runtime JDK 25 incluido en la instalación actual de Android Studio no es compatible con el analizador de Gradle 8.14.3 usado por este proyecto.

Generar APK de pruebas:

```powershell
npm.cmd run android:debug
```

Salida esperada:

```text
android/app/build/outputs/apk/debug/app-debug.apk
```

Generar paquete de publicación:

```powershell
npm.cmd run android:release
```

Para una publicación real se debe crear y proteger un keystore, firmar la aplicación y generar preferentemente un archivo AAB.

## 7. Pruebas recomendadas

- Instalación PWA desde Chrome Android.
- Apertura standalone sin barra del navegador.
- Inicio de sesión y persistencia de sesión.
- Adaptación responsive del dashboard.
- Navegación hacia noticias, productos, inventario y administración.
- Carga y descarga de archivos.
- Comportamiento sin Internet y recuperación al reconectar.
- Instalación del APK en dispositivo físico.
- Prueba del botón Atrás de Android.
- Validación en diferentes resoluciones.

## 8. Sustento académico

> El producto COOPAECA se clasifica como aplicativo híbrido porque reutiliza una interfaz desarrollada con tecnologías web dentro de un contenedor nativo Android proporcionado por Capacitor. La aplicación se distribuye como APK, mientras que la lógica PHP y la base de datos MySQL permanecen en un servidor seguro y son consumidas mediante HTTPS. Esta arquitectura permite mantener una sola base de código, ofrecer una versión PWA instalable y acceder posteriormente a funcionalidades nativas mediante plugins de Capacitor.

## 9. Evidencias para el documento final

Se recomienda capturar:

1. Vista responsive del dashboard.
2. Manifest analizado desde Chrome DevTools.
3. Service Worker registrado y activo.
4. Opción de instalación de la PWA.
5. Estructura del proyecto Android en Android Studio.
6. APK generado.
7. Aplicación instalada en un teléfono.
8. Inicio de sesión y navegación dentro del APK.

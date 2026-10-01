# Informe técnico breve — Evaluación final

## 1. Solución implementada

Se empleó el sistema web CCOPAECA, una aplicación PHP 8.2 con arquitectura MVC propia y MySQL. El sistema permite publicar contenido institucional, administrar productos y servicios, atender contactos y gestionar el flujo de pedidos, ventas, inventario y comprobantes.

Para esta evaluación se añadieron una prueba unitaria automatizada y los archivos necesarios para ejecutar la aplicación en contenedores Docker.

## 2. Experimentación y prueba unitaria

**Objetivo.** Verificar que el módulo de internacionalización selecciona la traducción correcta según el idioma solicitado por el visitante.

**Hipótesis.** Si el parámetro `lang=en` está presente, la función `t('nav.home')` devuelve `Home`; si el idioma no está soportado, devuelve el valor seguro por defecto en español: `Inicio`.

**Diseño.** Se implementaron dos pruebas unitarias independientes en `tests/Unit/TranslationTest.php`. Cada una reinicia los datos de consulta y sesión para evitar dependencia entre experimentos.

**Ejecución y resultado esperado.**

```bash
composer install
composer test
```

Resultado esperado: 2 pruebas, 2 aserciones exitosas y 0 fallos. Esta evidencia demuestra el comportamiento de traducción y de recuperación ante una entrada no válida.

## 3. Gestión de versiones

El repositorio usa Git. La rama `main` representa una versión estable y la rama `develop` integra los cambios en desarrollo antes de publicar.

Flujo propuesto:

```text
feature/prueba-traduccion ──> develop ──> main
```

Para iniciar una mejora se crea una rama desde `develop`; luego se prueba, se integra en `develop` y, tras la validación, se fusiona en `main`.

```bash
git checkout develop
git checkout -b feature/nombre-de-la-mejora
composer test
git add .
git commit -m "Describe la mejora"
git checkout develop
git merge feature/nombre-de-la-mejora
```

## 4. Contenerización y despliegue

`Dockerfile` construye una imagen con Apache, PHP 8.2 y las extensiones `mbstring`, `pdo_mysql` y `zip`. El servidor publica únicamente `public/` como document root. `docker-compose.yml` crea además una base de datos MySQL 8 e importa el esquema inicial.

Ejecución:

```bash
docker compose up --build
```

La aplicación queda disponible en `http://localhost:8080`. Para detener los contenedores:

```bash
docker compose down
```

Las credenciales de ejemplo en `docker-compose.yml` son exclusivas para desarrollo académico. En producción deben sustituirse por secretos gestionados fuera del repositorio y debe usarse HTTPS.

## 5. Herramientas modernas usadas

| Herramienta | Aplicación en el proyecto |
| --- | --- |
| Git | Historial de cambios y flujo con `main`/`develop`. |
| Composer | Gestión de dependencias y ejecución de pruebas. |
| PHPUnit | Pruebas unitarias automatizadas. |
| Docker Compose | Entorno reproducible de aplicación y base de datos. |

# PHP CS Fixer MCP

Servidor MCP para revisar y formatear codigo PHP con PHP CS Fixer y PSR-12.

## Instalacion

Mientras el paquete no este publicado en Packagist, anade el repositorio GitHub
al `composer.json` de tu proyecto:

```json
{
	"repositories": [
		{
			"type": "vcs",
			"url": "https://github.com/GonzaloLopezGonzalez/php-cs-fixer-mcp"
		}
	],
	"require": {
		"gonzalolopezgonzalez/php-cs-fixer-mcp": "dev-main"
	}
}
```

Despues ejecuta:

```powershell
composer update gonzalolopezgonzalez/php-cs-fixer-mcp
```

El ejecutable se instalara en `vendor/bin/php-cs-fixer-mcp`.

## Herramientas

- `php_cs_fixer_check`: revisa un archivo o directorio sin modificarlo.
- `php_cs_fixer_fix`: aplica el formato PSR-12 al archivo o directorio indicado.

Las rutas se resuelven dentro de `PHP_CS_FIXER_PROJECT_ROOT`. Por defecto se usa
el directorio de trabajo del proceso MCP.

### Revisar o corregir una carpeta

Las dos herramientas recorren la carpeta indicada y sus subcarpetas. Las rutas
son relativas a `PHP_CS_FIXER_PROJECT_ROOT`:

```text
php_cs_fixer_check
path: mi-proyecto/src
```

Esto revisa los archivos PHP sin modificarlos. Para aplicar las correcciones,
usa `php_cs_fixer_fix` con la misma ruta:

```text
php_cs_fixer_fix
path: mi-proyecto/src
```

Si quieres procesar todo un proyecto, indica `mi-proyecto`. Para evitar tocar
dependencias externas, es preferible indicar las carpetas de tu propio codigo
como `src`, `app` o `bin`, y no `vendor`.

## Configuracion de VS Code

El servidor usa STDIO para que VS Code lo inicie automaticamente en Windows y
Linux.

Para la instalacion local de este repositorio en Windows, configura el
`.vscode/mcp.json` asi:

```json
{
	"servers": {
		"php-cs-fixer": {
			"type": "stdio",
			"command": "php",
			"args": [
				"C:/xampp/htdocs/packages/php-cs-fixer-mcp/bin/php-cs-fixer-mcp"
			],
			"env": {
				"PHP_CS_FIXER_PROJECT_ROOT": "C:/xampp/htdocs"
			}
		}
	}
}
```

Si instalas el paquete mediante Composer en tu proyecto, usa esta
configuracion tanto en Windows como en Linux:

```json
{
	"servers": {
		"php-cs-fixer": {
			"type": "stdio",
			"command": "php",
			"args": [
				"${workspaceFolder}/vendor/bin/php-cs-fixer-mcp"
			],
			"env": {
				"PHP_CS_FIXER_PROJECT_ROOT": "${workspaceFolder}"
			}
		}
	}
}
```

En Linux, concede permisos de ejecucion al servidor si fuera necesario:

```bash
chmod +x vendor/bin/php-cs-fixer-mcp
```

## Estructura

- `bin/`: punto de entrada STDIO del servidor MCP.
- `src/`: clases PHP con autocarga PSR-4.
- `tests/`: pruebas del paquete.
- `composer.json`: metadatos y dependencias.

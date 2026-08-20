# PHP CS Fixer MCP

Servidor MCP para revisar y formatear codigo PHP con PHP CS Fixer y PSR-12.

## Herramientas

- `php_cs_fixer_check`: revisa un archivo o directorio sin modificarlo.
- `php_cs_fixer_fix`: aplica el formato PSR-12 al archivo o directorio indicado.

Las rutas se resuelven dentro de `PHP_CS_FIXER_PROJECT_ROOT`. Por defecto se usa
el directorio de trabajo del proceso MCP.

## Configuracion de VS Code

El servidor usa STDIO para que VS Code lo inicie automaticamente, igual que el
MCP de MySQL. Incluye un transporte bloqueante compatible con Windows.

Para probarlo manualmente:

```powershell
$env:PHP_CS_FIXER_PROJECT_ROOT = "C:/xampp/htdocs"
php C:/xampp/htdocs/vendor/php-cs-fixer-mcp/bin/php-cs-fixer-mcp
```

Configuralo en el `mcp.json` de VS Code:

```json
{
	"servers": {
		"php-cs-fixer": {
			"type": "stdio",
			"command": "php",
			"args": [
				"C:/xampp/htdocs/vendor/php-cs-fixer-mcp/bin/php-cs-fixer-mcp"
			],
			"env": {
				"PHP_CS_FIXER_PROJECT_ROOT": "C:/xampp/htdocs"
			}
		}
	}
}
```

## Estructura

- `bin/`: punto de entrada STDIO del servidor MCP.
- `src/`: clases PHP con autocarga PSR-4.
- `tests/`: pruebas del paquete.
- `composer.json`: metadatos y dependencias.

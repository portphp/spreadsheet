# Upgrade from 1.x to 2.0

- Minimum PHP is **8.2** (`^8.2`).
- Requires `portphp/portphp` **^2.0**.
- Writer methods use `: void` return types from the core interfaces.
- Iterator methods on `SpreadsheetReader` use PHP 8 return types (`mixed`, `void`, etc.).
- `phpoffice/phpspreadsheet` supports **^1.29 || ^2 || ^3 || ^4 || ^5** (uses coordinate API compatible with 2+).
- CI is GitHub Actions (Travis/Scrutinizer removed).
- PHPUnit 9 replaces the old PHPUnit 4 suite; phpspec is no longer a dependency.

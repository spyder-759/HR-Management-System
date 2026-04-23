# HR Management System Configuration Files

This directory contains configuration files for the HR Management System.

## Files Description

### `.env`
Environment variables file containing sensitive configuration data:
- Database credentials
- Application settings
- Security keys
- Email configuration
- File upload settings

**Important**: This file should NOT be committed to version control and should be kept secure.

### `env.php`
Environment configuration loader that:
- Loads variables from `.env` file
- Sets up PHP constants
- Configures timezone and basic settings

### `database.php`
Database configuration that provides:
- Centralized database connection management
- Connection pooling and optimization
- Backup and maintenance settings
- Database helper functions

### `app.php`
Main application configuration including:
- Application paths and constants
- Security settings
- Session configuration
- File upload settings
- Email configuration
- Company settings
- Logging and error handling

## Usage

The configuration files are automatically loaded by the system. To use them:

1. Copy `.env.example` to `.env` (if available)
2. Update the `.env` file with your specific settings
3. Ensure proper file permissions are set

## Security Notes

- Keep the `.env` file secure and never expose it publicly
- Use strong encryption keys and secrets
- Regularly rotate security credentials
- Set appropriate file permissions (644 for PHP files, 600 for .env)

## Environment Types

- `development`: For local development with debugging enabled
- `staging`: For testing environments
- `production`: For live production use with security hardened

## File Permissions

Recommended permissions:
- `.env`: 600 (read/write by owner only)
- PHP files: 644 (read by everyone, write by owner)
- Directories: 755 (read/execute by everyone, write by owner)

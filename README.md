# LAPS Plugin for GLPI

[![License](https://img.shields.io/badge/License-GPL%20v2%2B-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.txt)
[![GLPI Version](https://img.shields.io/badge/GLPI-9.5%20to%2010.1-green.svg)](https://glpi-project.org/)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)](https://www.php.net/)

## Overview

The LAPS (Local Administrator Password Solution) plugin for GLPI provides seamless integration with Microsoft LAPS to retrieve and display local administrator passwords for computers in your Active Directory environment.

## Features

- 🔐 **Secure Password Retrieval**: Safely retrieve LAPS passwords from your LAPS server
- 🎯 **Computer Integration**: Adds a dedicated "LAPS Password" tab to computer items
- ⚡ **Caching System**: Configurable password caching to reduce server load
- 🔒 **Access Control**: Respects GLPI's permission system for computer access
- 🌐 **Multi-language**: Supports English and Portuguese (Brazil)
- 📊 **Audit Trail**: Logs all password access attempts for security auditing
- 🔧 **Easy Configuration**: Simple web-based configuration interface

## Requirements

- **GLPI**: 9.5.0 to 10.1.0
- **PHP**: 7.4 or higher
- **Extensions**: cURL, JSON (usually included by default)
- **LAPS Server**: Microsoft LAPS or compatible API server

## Installation

### Method 1: From GLPI Marketplace (Recommended)

1. Go to **Setup** > **Plugins** in your GLPI interface
2. Click on **Marketplace**
3. Search for "LAPS"
4. Click **Install**

### Method 2: Manual Installation

1. Download the latest release from [GitHub Releases](https://github.com/pluginsGLPI/laps/releases)
2. Extract the archive to your GLPI plugins directory:
   ```bash
   cd /path/to/glpi/plugins
   tar -xzf glpi-laps-2.0.0.tar.gz
   ```
3. Go to **Setup** > **Plugins** in GLPI
4. Find "LAPS" and click **Install**
5. Click **Enable**

## Configuration

1. After installation, go to **Setup** > **Plugins**
2. Click on **LAPS** configuration
3. Configure the following settings:
   - **LAPS Server URL**: The URL of your LAPS API server
   - **API Key**: Authentication key for the LAPS server
   - **Connection Timeout**: Timeout for API requests (default: 30 seconds)
   - **Cache Duration**: How long to cache passwords (default: 60 minutes)
   - **Enable LAPS Plugin**: Toggle to enable/disable the plugin

4. Click **Test Connection** to verify your settings
5. Click **Save Configuration**

## Usage

1. Navigate to any computer in GLPI (**Assets** > **Computers**)
2. Click on a computer to view its details
3. Look for the **LAPS Password** tab
4. Click the tab to view the administrator password (if available)
5. Use the **Show/Hide Password** button to toggle password visibility
6. Use the **Copy Password** button to copy the password to clipboard

## API Server Setup

This plugin requires a LAPS API server that provides password information. The server should:

- Accept GET requests with computer name as parameter
- Return JSON response with password and expiration information
- Support API key authentication
- Use HTTPS for secure communication

### Example API Response

```json
{
  "success": true,
  "password": "AdminPassword123!",
  "expiration": "2024-12-31T23:59:59Z",
  "computer": "COMPUTER-NAME"
}
```

## Security Considerations

- Always use HTTPS for the LAPS server URL
- Regularly rotate API keys
- Monitor access logs for unauthorized attempts
- Configure appropriate GLPI permissions for computer access
- Consider network segmentation for the LAPS server

## Troubleshooting

### Common Issues

1. **"No LAPS password found"**
   - Verify the computer name matches exactly in LAPS
   - Check if the computer has a LAPS password set
   - Ensure the API server is accessible

2. **"Connection test failed"**
   - Verify the LAPS server URL is correct and accessible
   - Check the API key is valid
   - Ensure firewall allows outbound HTTPS connections

3. **"Plugin not configured"**
   - Complete the plugin configuration in Setup > Plugins
   - Ensure the plugin is enabled

### Debug Mode

Enable GLPI debug mode to see detailed error messages:

1. Edit `config/config_db.php`
2. Add: `$CFG_GLPI['debug_mode'] = true;`
3. Check GLPI logs for detailed error information

## Development

### Project Structure

```
laps/
├── hook.php               # Installation/uninstallation hooks
├── setup.php              # Plugin initialization and autoloading
├── plugin.xml             # Plugin metadata
├── README.md              # This file
├── install/               # Installation classes
│   ├── Install.php        # Installation logic
│   └── Uninstall.php      # Uninstallation logic
├── locales/               # Translations
│   └── pt_BR.po          # Portuguese (Brazil)
└── src/                   # Main plugin classes
    ├── Config.php         # Configuration management
    └── Computer.php       # Computer tab integration
```

### Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/new-feature`
3. Make your changes
4. Test the plugin in a GLPI environment
5. Submit a pull request

### Coding Standards

This project follows:
- PSR-4 autoloading (manual implementation)
- PSR-12 coding style
- Modern PHP practices with namespaces

## Changelog

### Version 2.0.0
- Complete refactoring with modern PHP practices
- PSR-4 autoloading and namespaces (manual implementation)
- Improved security with encrypted API keys
- Better error handling and logging
- Enhanced user interface
- No external dependencies - installs directly from GLPI interface
- Updated for GLPI 9.5+ compatibility

### Version 1.0.0
- Initial release
- Basic LAPS integration
- Password retrieval and display
- Simple configuration interface

## License

This project is licensed under the GPL v2+ License - see the [LICENSE](LICENSE) file for details.

## Support

- **Issues**: [GitHub Issues](https://github.com/pluginsGLPI/laps/issues)
- **Documentation**: [GitHub Wiki](https://github.com/pluginsGLPI/laps/wiki)
- **Community**: [GLPI Community Forums](https://community.glpi-project.org/)

## Authors

- **Rafael Cavalheri** - *Initial work and maintenance*

## Acknowledgments

- GLPI Development Team for the excellent framework
- Microsoft for the LAPS solution
- The GLPI community for feedback and contributions
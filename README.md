# Role Based Content Hider

Hide menus, widgets, and content based on WordPress user roles.

## Requirements

- WordPress 5.8+
- PHP 7.4+
- Composer

## Installation

1. Clone or download to `wp-content/plugins/rb-content-hider/`
2. Run `composer install` in the plugin directory
3. Activate the plugin from WordPress admin

## Features

### Menu Visibility
Go to **Appearance > Menus**, expand a menu item, and select which roles should NOT see it.

### Widget Visibility
Go to **Appearance > Widgets**, expand a widget, and select which roles should NOT see it.

### Shortcode

Hide content from specific roles:
```
[rb_hide roles="subscriber,guest"]Hidden from subscribers and guests[/rb_hide]
```

Show content only to specific roles:
```
[rb_hide show_to="administrator,editor"]Only for admins and editors[/rb_hide]
```

### Available Roles
- `guest` — Not logged in users
- `subscriber`
- `contributor`
- `author`
- `editor`
- `administrator`

## Settings

Navigate to **Settings > Content Hider** for:
- **Overview** — See all restricted menu items and widgets
- **Usage Guide** — Examples and documentation
- **Settings** — General configuration

## Development

```bash
# Install dependencies
composer install

# Check coding standards
composer phpcs

# Auto-fix coding standards
composer phpcbf
```

## Hooks & Filters

| Filter | Description |
|--------|-------------|
| `rbch_menu_hidden_roles` | Filter hidden roles for a menu item |
| `rbch_widget_hidden_roles` | Filter hidden roles for a widget |
| `rbch_shortcode_show_roles` | Filter show_to roles in shortcode |
| `rbch_shortcode_hidden_roles` | Filter hidden roles in shortcode |
| `rbch_shortcode_should_hide` | Final filter on shortcode visibility |
| `rbch_loaded` | Action fired when plugin is loaded |

## License

GPL v2 or later

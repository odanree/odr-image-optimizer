# Image Optimizer - Developer Guide

## Project Structure

```
image-optimizer/
├── image-optimizer.php          # Main plugin file
├── README.md                    # User documentation
├── DEVELOPMENT.md               # This file
├── LICENSE                      # GPL v2 license
├── composer.json                # Project metadata
├── includes/
│   ├── class-autoloader.php     # PSR-4 autoloader
│   ├── class-core.php           # Main plugin class
│   ├── admin/
│   │   ├── class-dashboard.php  # Admin dashboard UI
│   │   └── class-settings.php   # Settings page
│   └── core/
│       ├── class-optimizer.php  # Image optimization engine
│       ├── class-database.php   # Database operations
│       ├── class-api.php        # REST API endpoints
│       └── class-image-editor.php # Custom image editor (future)
├── assets/
│   ├── css/
│   │   ├── dashboard.css        # Dashboard styles
│   │   └── settings.css         # Settings styles
│   └── js/
│       ├── dashboard.js         # Dashboard functionality
│       └── lazy-load.js         # Lazy loading script
└── languages/
    └── image-optimizer.pot      # Translation template
```

## Development Setup

### Prerequisites
- PHP 7.4+
- WordPress 5.0+
- Composer (optional)
- Node.js (for asset building, optional)

### Installation

```bash
# Clone repository
git clone https://github.com/odanree/image-optimizer.git

# Navigate to directory
cd image-optimizer

# Install WordPress coding standards
composer install --dev
```

### Code Standards

This plugin follows [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/).

#### Check Code Standards
```bash
phpcs --standard=WordPress image-optimizer.php includes/
```

#### Auto-fix Issues
```bash
phpcbf --standard=WordPress image-optimizer.php includes/
```

## Architecture Overview

### Initialization Flow

1. **Plugin Loader** (`image-optimizer.php`)
   - Registers hooks
   - Loads autoloader
   - Initializes Core

2. **Autoloader** (`class-autoloader.php`)
   - PSR-4 namespace mapping
   - Lazy-loads classes on demand

3. **Core** (`class-core.php`)
   - Initializes all components
   - Registers admin menu
   - Sets up WordPress hooks

4. **Components**
   - **Optimizer**: Handles image compression
   - **Database**: Manages storage
   - **API**: REST endpoints
   - **Admin**: User interface

### Class Hierarchy

```
ImageOptimizer\
├── Core (main plugin orchestrator)
├── Admin\
│   ├── Dashboard
│   └── Settings
├── Core\
│   ├── Optimizer (image compression)
│   ├── Database (storage layer)
│   ├── API (REST endpoints)
│   └── Image_Editor (WordPress integration)
└── Autoloader
```

## Key Classes

### Core

Main plugin orchestrator. Initializes all components and manages WordPress integration.

**Methods**
- `get_instance()` - Singleton accessor
- `init()` - Initialize plugin
- `register_admin_menu()` - Register admin menu
- `activate()` - Plugin activation hook
- `deactivate()` - Plugin deactivation hook

### Optimizer

Image compression and optimization engine.

**Methods**
- `optimize_attachment($id)` - Optimize single image
- `optimize_file($path, $method)` - Process image file
- `optimize_jpeg($path)` - JPEG optimization
- `optimize_png($path)` - PNG optimization
- `create_webp_version($path)` - WebP conversion

**Compression Levels**
- `LOW (1)`: 95% quality (minimal compression)
- `MEDIUM (2)`: 85% quality (balanced)
- `HIGH (3)`: 75% quality (maximum compression)

### Database

Custom database operations and caching.

**Tables**
- `wp_image_optimizer_history` - Optimization records
- `wp_image_optimizer_cache` - Performance cache

**Methods**
- `get_optimization_history($id)` - Get last optimization
- `save_optimization_result($id, $data)` - Store result
- `get_statistics()` - Get aggregate statistics
- `get_cache($key)` - Retrieve cached value
- `set_cache($key, $value, $expires_in)` - Store cached value

### API

REST API endpoints for programmatic access.

**Endpoints**
- `GET /image-optimizer/v1/stats` - Get statistics
- `GET /image-optimizer/v1/images` - List images
- `POST /image-optimizer/v1/optimize/{id}` - Optimize image
- `GET /image-optimizer/v1/history/{id}` - Get history

## Common Tasks

### Adding a New Image Format

1. Update `Optimizer::is_optimizable()`:
```php
$optimizable_types = array(
	'image/jpeg',
	'image/png',
	'image/gif',
	'image/webp',
	'image/x-icon', // Add new format
);
```

2. Add optimization method:
```php
private function optimize_ico($file_path) {
	// Implementation
}
```

3. Update `optimize_file()` switch statement:
```php
case 'image/x-icon':
	return $this->optimize_ico($file_path);
```

### Adding a Custom Hook

```php
// In Optimizer class
do_action('image_optimizer_before_optimization', $attachment_id);

// For users to hook into:
add_action('image_optimizer_before_optimization', 'my_callback');
```

### Creating a New Settings Field

```php
// In Settings::register_settings()
add_settings_field(
	'new_field',
	__('New Setting', 'image-optimizer'),
	array($this, 'render_new_field'),
	'image-optimizer-settings',
	'image-optimizer-general'
);

// Add renderer
public function render_new_field() {
	$settings = get_option('image_optimizer_settings', array());
	// Render field
}
```

## Extending the Plugin

### Example: Custom Optimization Service

```php
// Add to functions.php or custom plugin
add_filter('image_optimizer_optimization_method', function($method, $file) {
	// Use external API if available
	if (my_api_available()) {
		return 'external_api';
	}
	return $method;
}, 10, 2);

// Handle custom method in Optimizer
add_filter('image_optimizer_optimize_file', function($result, $file, $method) {
	if ('external_api' === $method) {
		// Call external API
		return call_external_api($file);
	}
	return $result;
}, 10, 3);
```

### Example: Custom REST Endpoint

```php
add_action('rest_api_init', function() {
	register_rest_route('image-optimizer/v1', '/analyze', array(
		'methods' => 'POST',
		'callback' => function($request) {
			$attachment_id = $request->get_param('attachment_id');
			// Custom analysis logic
			return rest_ensure_response(['analysis' => 'data']);
		},
		'permission_callback' => function() {
			return current_user_can('manage_options');
		}
	));
});
```

## Performance Considerations

### Query Optimization
- Indexed columns: `attachment_id`, `status`, `expires_at`
- Use `LIMIT` for pagination
- Cache frequently accessed data

### Image Processing
- Lazy load processing for bulk operations
- Async processing for large images
- Stream processing for memory efficiency

### Database
- Regular cache cleanup
- Archiving old records
- Index maintenance

## Testing

### Manual Testing
1. Upload test images in various formats
2. Verify optimization in database
3. Check WebP conversion
4. Test bulk operations
5. Verify settings save/load

### API Testing
```bash
# Get statistics
curl http://localhost:8080/wp-json/image-optimizer/v1/stats

# List images
curl http://localhost:8080/wp-json/image-optimizer/v1/images

# Optimize image
curl -X POST http://localhost:8080/wp-json/image-optimizer/v1/optimize/1
```

## Security Checklist

- ✅ Sanitize all inputs
- ✅ Escape all outputs
- ✅ Check capabilities
- ✅ Verify nonces
- ✅ Use prepared statements
- ✅ Validate file types
- ✅ Check file sizes
- ✅ Prevent direct access

## Publishing to WordPress.org

1. Create SVN repository structure
2. Add banner and icon images
3. Tag stable release
4. Submit for review
5. Update documentation

## Troubleshooting

### Images Not Optimizing
- Check GD library is enabled: `phpinfo()`
- Verify file permissions
- Check error logs: `wp_get_last_error()`
- Verify file types are supported

### Database Errors
- Ensure tables created: Check `$wpdb->prefix . 'image_optimizer_history'`
- Check MySQL version: Requires 5.6+
- Verify user has necessary permissions

### Performance Issues
- Check database indexes
- Monitor query performance
- Enable query caching
- Consider pagination

## Future Enhancements

- [ ] AVIF format support
- [ ] Cron-based background optimization
- [ ] Bulk image import/export
- [ ] CDN integration
- [ ] Advanced image editing
- [ ] AI-powered quality detection
- [ ] Multi-language support
- [ ] WooCommerce product image optimization

---

For questions or contributions, visit: https://github.com/odanree/image-optimizer

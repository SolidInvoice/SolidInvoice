# Email Styles - SCSS Source Files

This directory contains SCSS source files for email styles using the SolidInvoice design system.

## Files

- **`_variables.scss`** - Design system tokens mapped to email-safe SCSS variables
- **`email-colors.scss`** - Color overrides for Foundation for Emails
- **`modern.scss`** - Modern email enhancements with design system colors

## Design System Integration

All color values are derived from `assets/scss/design-system/_tokens.scss` to ensure brand consistency across the application and emails.

### Color Mapping

| Design System Token | Email Variable | Hex Value |
|---------------------|----------------|-----------|
| `--swp-primary` | `$email-primary` | #2e963a (Green) |
| `--swp-success` | `$email-success` | #10b981 |
| `--swp-danger` | `$email-danger` | #ef4444 |
| `--swp-warning` | `$email-warning` | #f59e0b |
| `--swp-info` | `$email-info` | #3b82f6 |
| `--swp-gray-*` | `$email-gray-*` | Slate gray scale |

## Webpack Compilation

The SCSS files are compiled by Webpack Encore (see `webpack.config.js`):

```javascript
.addStyleEntry('email-colors', './assets/scss/email/email-colors.scss')
.addStyleEntry('email-modern', './assets/scss/email/modern.scss')
```

Compiled CSS outputs to `public/static/`:
- `email-colors.css`
- `email-modern.css`

## Email Template Usage

Email templates use Twig CSS files (`src/CoreBundle/Resources/views/Email/*.css.twig`) which have been manually updated with design system colors.

The SCSS files in this directory serve as:
1. **Source of truth** for color values
2. **Maintenance tool** for future updates
3. **Development reference** with proper variable names

### Updating Email Colors

To update email colors:

1. **Update SCSS variables** in `_variables.scss`
2. **Compile with webpack**: `bun run build`
3. **Copy compiled CSS** to corresponding `.twig` files if needed
4. **Test emails** across email clients

## Why Not Use Compiled CSS Directly?

Email templates use Twig's `inline_css()` filter which requires template files (`.twig`), not webpack-compiled assets. This is necessary because:

1. **CSS must be inlined** for email client compatibility
2. **Twig source() function** only works with template paths
3. **No HTTP requests** - emails can't load external stylesheets

## Future Improvements

Potential optimizations:

1. **Automated sync** - Build process that copies compiled CSS to .twig files
2. **Email CSS optimizer** - Webpack plugin to remove unused CSS for emails
3. **Twig extension** - Custom filter to read compiled CSS from webpack manifest

## Email Client Compatibility

Styles are designed for maximum compatibility:

- **Foundation for Emails** - Battle-tested framework for email HTML/CSS
- **Graceful degradation** - Modern features degrade safely in Outlook 2007-2013
- **Inlined CSS** - All styles are inlined for reliability
- **Table-based layout** - Works across all major email clients

## Development

```bash
# Install dependencies
bun install

# Development build with watch
bun run dev

# Production build (minified)
bun run build
```

## Resources

- [Foundation for Emails](https://get.foundation/emails.html)
- [Email Client CSS Support](https://www.campaignmonitor.com/css/)
- [SolidInvoice Design System](../design-system/)

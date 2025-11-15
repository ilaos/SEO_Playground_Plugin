#!/bin/bash

# AlmaSEO SEO Playground Build Script
# Version: 2.1.0
# Description: Minifies CSS and JS files for production

echo "Building AlmaSEO SEO Playground v2.1.0..."

# Check if required tools are installed
check_command() {
    if ! command -v $1 &> /dev/null; then
        echo "Warning: $1 is not installed. Installing via npm..."
        npm install -g $2
    fi
}

# Install tools if needed
check_command "uglifyjs" "uglify-js"
check_command "cleancss" "clean-css-cli"

# Create minified directory
mkdir -p assets/css/min
mkdir -p assets/js/min

echo "Minifying CSS files..."

# Minify consolidated CSS
if [ -f "assets/css/seo-playground-consolidated.css" ]; then
    cleancss -o assets/css/min/seo-playground.min.css assets/css/seo-playground-consolidated.css
    echo "✓ seo-playground.min.css"
fi

# Minify individual CSS files
for file in assets/css/*.css; do
    if [[ ! "$file" == *"consolidated"* ]] && [[ ! "$file" == *".min."* ]]; then
        filename=$(basename "$file" .css)
        cleancss -o "assets/css/min/${filename}.min.css" "$file"
        echo "✓ ${filename}.min.css"
    fi
done

echo "Minifying JavaScript files..."

# Minify consolidated JS
if [ -f "assets/js/seo-playground-consolidated.js" ]; then
    uglifyjs assets/js/seo-playground-consolidated.js -c -m -o assets/js/min/seo-playground.min.js
    echo "✓ seo-playground.min.js"
fi

# Minify individual JS files
for file in assets/js/*.js; do
    if [[ ! "$file" == *"consolidated"* ]] && [[ ! "$file" == *".min."* ]]; then
        filename=$(basename "$file" .js)
        uglifyjs "$file" -c -m -o "assets/js/min/${filename}.min.js"
        echo "✓ ${filename}.min.js"
    fi
done

# Create combined production file
echo "Creating combined production files..."

# Combine all CSS into one production file
cat assets/css/min/seo-playground.min.css > assets/css/seo-playground-all.min.css
for file in assets/css/min/*.min.css; do
    if [[ ! "$file" == *"seo-playground.min.css"* ]] && [[ ! "$file" == *"all.min.css"* ]]; then
        echo "" >> assets/css/seo-playground-all.min.css
        cat "$file" >> assets/css/seo-playground-all.min.css
    fi
done
echo "✓ seo-playground-all.min.css"

# Combine all JS into one production file
cat assets/js/min/seo-playground.min.js > assets/js/seo-playground-all.min.js
for file in assets/js/min/*.min.js; do
    if [[ ! "$file" == *"seo-playground.min.js"* ]] && [[ ! "$file" == *"all.min.js"* ]]; then
        echo ";" >> assets/js/seo-playground-all.min.js
        cat "$file" >> assets/js/seo-playground-all.min.js
    fi
done
echo "✓ seo-playground-all.min.js"

# Calculate file sizes
echo ""
echo "File size comparison:"
echo "===================="

original_css_size=$(du -ch assets/css/*.css 2>/dev/null | grep total | awk '{print $1}')
minified_css_size=$(du -ch assets/css/min/*.min.css 2>/dev/null | grep total | awk '{print $1}')
echo "CSS: $original_css_size → $minified_css_size"

original_js_size=$(du -ch assets/js/*.js 2>/dev/null | grep total | awk '{print $1}')
minified_js_size=$(du -ch assets/js/min/*.min.js 2>/dev/null | grep total | awk '{print $1}')
echo "JS: $original_js_size → $minified_js_size"

echo ""
echo "✅ Build complete!"
echo ""
echo "Production files:"
echo "- assets/css/seo-playground-all.min.css"
echo "- assets/js/seo-playground-all.min.js"
echo ""
echo "To use minified files in production, set WP_DEBUG to false in wp-config.php"
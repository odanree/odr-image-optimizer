#!/bin/bash

# Screenshot 1: Dashboard
convert -size 1200x900 xc:white \
  -pointsize 48 -fill "#23282d" -gravity NorthWest -annotate +40+40 "Image Optimizer Dashboard" \
  -pointsize 16 -fill "#666" -gravity NorthWest -annotate +40+100 "Real-time Optimization Statistics" \
  \
  -fill "none" -stroke "#ddd" -strokewidth 1 -draw "rectangle 40,150 560,280" \
  -pointsize 36 -fill "#0073aa" -gravity NorthWest -annotate +60+170 "2,847" \
  -pointsize 14 -fill "#666" -gravity NorthWest -annotate +60+230 "Images Optimized" \
  \
  -fill "none" -stroke "#ddd" -strokewidth 1 -draw "rectangle 600,150 1120,280" \
  -pointsize 36 -fill "#28a745" -gravity NorthWest -annotate +620+170 "342 MB" \
  -pointsize 14 -fill "#666" -gravity NorthWest -annotate +620+230 "Total Savings" \
  \
  -fill "none" -stroke "#ddd" -strokewidth 1 -draw "rectangle 40,320 560,450" \
  -pointsize 14 -fill "#333" -gravity NorthWest -annotate +60+340 "Recent Optimizations" \
  -pointsize 12 -fill "#666" -gravity NorthWest -annotate +60+380 "✓ photo-1.jpg (2.1 MB → 342 KB)" \
  -pointsize 12 -fill "#666" -gravity NorthWest -annotate +60+410 "✓ vacation-2024.png (5.8 MB → 1.2 MB)" \
  \
  -fill "none" -stroke "#ddd" -strokewidth 1 -draw "rectangle 600,320 1120,450" \
  -pointsize 14 -fill "#333" -gravity NorthWest -annotate +620+340 "Compression Summary" \
  -pointsize 12 -fill "#666" -gravity NorthWest -annotate +620+380 "Average Compression: 84%" \
  -pointsize 12 -fill "#666" -gravity NorthWest -annotate +620+410 "WebP: 87% Smaller" \
  \
  -pointsize 14 -fill "#0073aa" -gravity SouthWest -annotate +40+40 "WordPress Plugin Directory Preview" \
  screenshot-1.png

# Screenshot 2: Image Library with Optimization Status
convert -size 1200x900 xc:white \
  -pointsize 48 -fill "#23282d" -gravity NorthWest -annotate +40+40 "Media Library with Optimization" \
  -pointsize 16 -fill "#666" -gravity NorthWest -annotate +40+100 "Visual Status View - See Compression Results" \
  \
  -fill "none" -stroke "#ddd" -strokewidth 1 -draw "rectangle 40,160 580,350" \
  -fill "#f5f5f5" -draw "rectangle 45,165 575,345" \
  -pointsize 12 -fill "#999" -gravity NorthWest -annotate +240+250 "[Image Preview]" \
  -pointsize 11 -fill "#333" -gravity NorthWest -annotate +50+360 "photo-01.jpg" \
  -pointsize 10 -fill "#28a745" -gravity NorthWest -annotate +50+380 "✓ Optimized (2.1 MB → 342 KB)" \
  \
  -fill "none" -stroke "#ddd" -strokewidth 1 -draw "rectangle 620,160 1160,350" \
  -fill "#f5f5f5" -draw "rectangle 625,165 1155,345" \
  -pointsize 12 -fill "#999" -gravity NorthWest -annotate +780+250 "[Image Preview]" \
  -pointsize 11 -fill "#333" -gravity NorthWest -annotate +630+360 "landscape-view.png" \
  -pointsize 10 -fill "#28a745" -gravity NorthWest -annotate +630+380 "✓ Optimized (5.8 MB → 1.2 MB)" \
  \
  -fill "none" -stroke "#ddd" -strokewidth 1 -draw "rectangle 40,420 580,610" \
  -fill "#f5f5f5" -draw "rectangle 45,425 575,605" \
  -pointsize 12 -fill "#999" -gravity NorthWest -annotate +240+510 "[Image Preview]" \
  -pointsize 11 -fill "#333" -gravity NorthWest -annotate +50+620 "photo-02.jpg" \
  -pointsize 10 -fill "#ffc107" -gravity NorthWest -annotate +50+640 "◐ Pending Optimization" \
  \
  -fill "none" -stroke "#ddd" -strokewidth 1 -draw "rectangle 620,420 1160,610" \
  -fill "#f5f5f5" -draw "rectangle 625,425 1155,605" \
  -pointsize 12 -fill "#999" -gravity NorthWest -annotate +780+510 "[Image Preview]" \
  -pointsize 11 -fill "#333" -gravity NorthWest -annotate +630+620 "banner-image.jpg" \
  -pointsize 10 -fill "#28a745" -gravity NorthWest -annotate +630+640 "✓ Optimized (3.4 MB → 580 KB)" \
  \
  -pointsize 14 -fill "#0073aa" -gravity SouthWest -annotate +40+40 "Bulk Optimization Available • One-Click Operations" \
  screenshot-2.png

# Screenshot 3: Settings Configuration
convert -size 1200x900 xc:white \
  -pointsize 48 -fill "#23282d" -gravity NorthWest -annotate +40+40 "Image Optimizer Settings" \
  -pointsize 16 -fill "#666" -gravity NorthWest -annotate +40+100 "Configure Compression & Processing Options" \
  \
  -fill "none" -stroke "#ddd" -strokewidth 2 -draw "rectangle 40,160 1120,280" \
  -pointsize 14 -fill "#333" -gravity NorthWest -annotate +60+180 "Compression Level" \
  -pointsize 12 -fill "#666" -gravity NorthWest -annotate +60+210 "○ Low (High Quality)  ● Medium (Balanced)  ○ High (Smaller Size)" \
  -pointsize 11 -fill "#999" -gravity NorthWest -annotate +60+240 "Recommended: Medium for most WordPress sites" \
  \
  -fill "none" -stroke "#ddd" -strokewidth 2 -draw "rectangle 40,310 1120,430" \
  -pointsize 14 -fill "#333" -gravity NorthWest -annotate +60+330 "Processing Options" \
  -pointsize 12 -fill "#666" -gravity NorthWest -annotate +60+365 "☑ Enable WebP Conversion" \
  -pointsize 11 -fill "#999" -gravity NorthWest -annotate +80+390 "Create WebP versions for modern browsers" \
  -pointsize 12 -fill "#666" -gravity NorthWest -annotate +60+415 "☑ Enable Lazy Loading" \
  \
  -fill "none" -stroke "#ddd" -strokewidth 2 -draw "rectangle 40,460 1120,580" \
  -pointsize 14 -fill "#333" -gravity NorthWest -annotate +60+480 "Advanced Options" \
  -pointsize 12 -fill "#666" -gravity NorthWest -annotate +60+515 "☑ Auto-Optimize on Upload  (New images automatically optimized)" \
  -pointsize 12 -fill "#666" -gravity NorthWest -annotate +60+545 "☑ Create Image Backups  (Revert to original anytime)" \
  \
  -fill "#0073aa" -draw "rectangle 40,620 200,670" \
  -pointsize 14 -fill "white" -gravity NorthWest -annotate +50+633 "Save Settings" \
  \
  -pointsize 14 -fill "#0073aa" -gravity SouthWest -annotate +40+40 "Flexible Configuration • Production Ready" \
  screenshot-3.png

echo "✓ Screenshots generated successfully"
ls -lh screenshot-*.png


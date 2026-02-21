/**
 * Image Optimizer Dashboard
 */

(function() {
	'use strict';

	// Wait for DOM to be ready
	document.addEventListener('DOMContentLoaded', function() {
		const container = document.getElementById('image-optimizer-dashboard');
		
		if (!container) {
			console.log('Dashboard container not found');
			return;
		}

		// Verify imageOptimizerData is available
		if (typeof imageOptimizerData === 'undefined') {
			console.error('imageOptimizerData not found');
			container.innerHTML = '<p>Error: Plugin data not loaded. Please refresh the page.</p>';
			return;
		}

		// Fetch images from REST API
		const restUrl = imageOptimizerData.rest_url || '/wp-json/image-optimizer/v1/';
		
		// Use the REST URL directly - it's already properly formatted by WordPress
		let fetchUrl = restUrl + 'images';
		
		console.log('REST URL:', restUrl);
		console.log('Fetch URL:', fetchUrl);
		
		fetch(fetchUrl, {
			method: 'GET',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': imageOptimizerData.nonce || ''
			}
		})
		.then(response => {
			console.log('Response status:', response.status);
			if (!response.ok) {
				throw new Error('HTTP error status: ' + response.status);
			}
			return response.text();
		})
		.then(text => {
			console.log('Raw response:', text.substring(0, 200));
			let data;
			try {
				data = JSON.parse(text);
			} catch (e) {
				console.error('JSON parse error:', e, 'Text:', text.substring(0, 500));
				throw new Error('Invalid JSON response: ' + e.message);
			}
			console.log('Data received:', data);
			// Handle both array and object responses
			const images = Array.isArray(data) ? data : (data.images || []);
			
			if (!images || images.length === 0) {
				console.log('No images found');
				container.innerHTML = '<p>No images found in your media library.</p>';
				return;
			}

			// Render images with responsive srcset/sizes for Lighthouse compliance
			let html = '<div class="image-optimizer-gallery">';
			
			images.forEach(image => {
				// Build responsive img tag with srcset and sizes attributes
				const srcsetAttr = image.srcset ? `srcset="${escapeHtml(image.srcset)}"` : '';
				const sizesAttr = image.sizes ? `sizes="${escapeHtml(image.sizes)}"` : '';
				
				html += `
					<div class="image-optimizer-card">
						<div class="image-optimizer-card-image">
							<img src="${escapeHtml(image.url)}" 
							     alt="${escapeHtml(image.title)}" 
							     ${srcsetAttr}
							     ${sizesAttr}
							     class="image-optimizer-thumbnail"
							     loading="lazy"
							     decoding="async"
							     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22250%22 height=%22140%22%3E%3Crect fill=%22%23ddd%22 width=%22250%22 height=%22140%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 font-family=%22Arial%22 font-size=%2214%22 text-anchor=%22middle%22 dominant-baseline=%22middle%22 fill=%22%23999%22%3EImage Not Found%3C/text%3E%3C/svg%3E'">
						</div>
						<h3>${escapeHtml(image.title)}</h3>
						<p>Size: ${formatBytes(image.size)}</p>
						${image.webp_available ? '<p class="status-webp">📦 WebP Available</p>' : ''}
						<div class="image-optimizer-actions">
							${image.optimized ? `
								<p class="status-optimized">✓ Optimized</p>
								<button class="revert-btn" data-id="${image.id}">Revert</button>
							` : `
								<button class="optimize-btn" data-id="${image.id}">Optimize</button>
							`}
						</div>
					</div>
				`;
			});
			
			html += '</div>';
			container.innerHTML = html;

			// Add event listeners for optimize buttons
			document.querySelectorAll('.optimize-btn').forEach(btn => {
				btn.addEventListener('click', function() {
					optimizeImage(this.dataset.id);
				});
			});

			// Add event listeners for revert buttons
			document.querySelectorAll('.revert-btn').forEach(btn => {
				btn.addEventListener('click', function() {
					revertImage(this.dataset.id);
				});
			});
		})
		.catch(error => {
			console.error('Error loading images:', error);
			container.innerHTML = '<p>Error loading images. Check the browser console.</p>';
		});
	});

	// Format bytes to human readable
	function formatBytes(bytes) {
		if (bytes === 0) return '0 Bytes';
		const k = 1024;
		const sizes = ['Bytes', 'KB', 'MB'];
		const i = Math.floor(Math.log(bytes) / Math.log(k));
		return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
	}

	// Escape HTML to prevent XSS attacks
	function escapeHtml(text) {
		if (!text) return '';
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	// Optimize image
	function optimizeImage(id) {
		const restUrl = imageOptimizerData.rest_url || '/wp-json/image-optimizer/v1/';
		
		fetch(restUrl + 'optimize/' + id, {
			method: 'POST',
			headers: {
				'X-WP-Nonce': imageOptimizerData.nonce,
				'Content-Type': 'application/json'
			}
		})
		.then(response => {
			console.log('Optimize response status:', response.status);
			console.log('Optimize response type:', response.headers.get('Content-Type'));
			
			// Check if response is JSON or HTML
			const contentType = response.headers.get('Content-Type');
			if (!contentType || !contentType.includes('application/json')) {
				// Might be an HTML error page
				return response.text().then(text => {
					if (text.includes('critical error') || text.includes('fatal')) {
						throw new Error('Server returned HTML error: ' + text.substring(0, 200));
					}
					throw new Error('Response was not JSON: ' + text.substring(0, 200));
				});
			}
			
			return response.json().then(data => ({ status: response.status, data }));
		})
		.then(({ status, data }) => {
			console.log('Optimize response data:', data);
			if (status === 200 && data.success) {
				alert('Image optimized successfully!\nOriginal: ' + formatBytes(data.original_size) + '\nOptimized: ' + formatBytes(data.optimized_size));
				location.reload();
			} else {
				const errorMsg = data.message || data.error || data.code || 'Unknown error';
				console.error('Optimization failed:', errorMsg, data);
				alert('Error: ' + errorMsg);
			}
		})
		.catch(error => {
			console.error('Error optimizing image:', error);
			alert('Error: ' + error.message);
		});
	}

	// Revert image optimization
	function revertImage(id) {
		if (!confirm('Are you sure you want to revert this image to its original version?')) {
			return;
		}

		const restUrl = imageOptimizerData.rest_url || '/wp-json/image-optimizer/v1/';
		
		fetch(restUrl + 'revert/' + id, {
			method: 'POST',
			headers: {
				'X-WP-Nonce': imageOptimizerData.nonce,
				'Content-Type': 'application/json'
			}
		})
		.then(response => {
			console.log('Revert response status:', response.status);
			return response.json().then(data => ({ status: response.status, data }));
		})
		.then(({ status, data }) => {
			console.log('Revert response data:', data);
			if (status === 200 && data.success) {
				alert('Image reverted successfully!\nRestored size: ' + formatBytes(data.restored_size));
				location.reload();
			} else {
				// Handle WP_Error response format
				const errorMsg = data.message || data.error || data.code || 'Unknown error';
				console.error('Revert failed:', errorMsg, data);
				alert('Error: ' + errorMsg);
			}
		})
		.catch(error => {
			console.error('Error reverting image:', error);
			alert('Error: ' + error.message);
		});
	}
})();
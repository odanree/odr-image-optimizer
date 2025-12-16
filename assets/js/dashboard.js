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
		
		// Try direct /wp-json/ URL first, fall back to index.php?rest_route= if needed
		let fetchUrl = restUrl + 'images';
		
		// If restUrl contains "index.php", normalize it for direct wp-json access
		if (restUrl.includes('index.php')) {
			fetchUrl = '/wp-json/image-optimizer/v1/images';
		}
		
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
			return response.json();
		})
		.then(data => {
			console.log('Data received:', data);
			// Handle both array and object responses
			const images = Array.isArray(data) ? data : (data.images || []);
			
			if (!images || images.length === 0) {
				console.log('No images found');
				container.innerHTML = '<p>No images found in your media library.</p>';
				return;
			}

			// Render images
			let html = '<div class="image-optimizer-gallery">';
			
			images.forEach(image => {
				html += `
					<div class="image-optimizer-card">
						<img src="${image.url}" alt="${image.title}" class="image-optimizer-thumbnail" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22200%22%3E%3Crect fill=%22%23ddd%22 width=%22200%22 height=%22200%22/%3E%3C/svg%3E'">
						<h3>${image.title}</h3>
						<p>Size: ${formatBytes(image.size)}</p>
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
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				alert('Image optimized successfully!\nOriginal: ' + formatBytes(data.original_size) + '\nOptimized: ' + formatBytes(data.optimized_size));
				location.reload();
			} else {
				alert('Error: ' + (data.message || 'Unknown error'));
			}
		})
		.catch(error => {
			console.error('Error optimizing image:', error);
			alert('Error optimizing image');
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
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				alert('Image reverted successfully!\nRestored size: ' + formatBytes(data.restored_size) + '\nSpace freed: ' + formatBytes(data.freed_space));
				location.reload();
			} else {
				alert('Error: ' + (data.message || 'Unknown error'));
			}
		})
		.catch(error => {
			console.error('Error reverting image:', error);
			alert('Error reverting image');
		});
	}
})();
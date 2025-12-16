// Image Optimizer Lazy Loading
document.addEventListener('DOMContentLoaded', function() {
	const settings = window.imageOptimizerSettings || {};
	
	if (!settings.enable_lazy_load) {
		return;
	}

	// Lazy load images with loading="lazy"
	if ('loading' in HTMLImageElement.prototype) {
		document.querySelectorAll('img[data-src]').forEach(img => {
			img.src = img.dataset.src;
			img.removeAttribute('data-src');
		});
	} else {
		// Fallback for older browsers
		const imageObserver = new IntersectionObserver((entries, observer) => {
			entries.forEach(entry => {
				if (entry.isIntersecting) {
					const img = entry.target;
					img.src = img.dataset.src;
					img.removeAttribute('data-src');
					observer.unobserve(img);
				}
			});
		});

		document.querySelectorAll('img[data-src]').forEach(img => {
			imageObserver.observe(img);
		});
	}

	// WebP image support detection
	function supports_webp() {
		const canvas = document.createElement('canvas');
		canvas.width = 1;
		canvas.height = 1;
		return canvas.toDataURL('image/webp').indexOf('image/webp') === 5;
	}

	// Replace images with WebP versions if supported
	if (settings.enable_webp && supports_webp()) {
		document.querySelectorAll('img, picture source').forEach(el => {
			if (el.tagName === 'SOURCE') {
				const srcset = el.srcset || '';
				el.srcset = srcset.replace(/\.(jpg|jpeg|png)([^"]*)(?=")/g, '$1.webp$2');
				el.type = 'image/webp';
			}
		});
	}
});

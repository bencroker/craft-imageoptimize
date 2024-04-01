// ref: https://web.dev/articles/browser-level-image-lazy-loading#how-do-i-handle-browsers-that-don't-yet-support-native-lazy-loading
if ('loading' in HTMLImageElement.prototype) {
// Replace the img.src with what is in the data-src property
  const images = document.querySelectorAll('img[loading="lazy"]');
  images.forEach(img => {
    if (img.dataset.src) {
      img.src = img.dataset.src;
      img.removeAttribute('data-src');
    }
    if (img.dataset.srcset) {
      img.srcset = img.dataset.srcset;
      img.removeAttribute('data-srcset');
    }
  });
// Replace the source.srcset with what is in the data-srcset property
  const sources = document.querySelectorAll('source[data-srcset]')
  sources.forEach(source => {
    if (source.dataset.srcset) {
      source.srcset = source.dataset.srcset;
      source.removeAttribute('data-srcset');
    }
    if (source.dataset.sizes) {
      source.sizes = source.dataset.sizes;
      source.removeAttribute('data-sizes');
    }
  });
} else {
  // Dynamically import the LazySizes library
  const script = document.createElement('script');
  script.src = '{{ scriptSrc }}';
  document.body.appendChild(script);
}

import './bootstrap';
import { initImageHoverComponents } from './components/image-hover';

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initImageHoverComponents, { once: true });
} else {
    initImageHoverComponents();
}

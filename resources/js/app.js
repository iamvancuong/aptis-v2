import './bootstrap';
import './components/sidebar.js';

// Alpine bundle qua Vite (thay CDN). Nút +/- số lượng, menu mobile, dropdown admin
// đều dùng Alpine → không còn vỡ khi CDN bị chặn (mạng công ty/trường học).
import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();

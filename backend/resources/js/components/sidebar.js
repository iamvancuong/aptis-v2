/**
 * Sidebar Management
 * Handles sidebar toggle, state persistence, and mobile menu
 */

class SidebarManager {
    constructor() {
        this.sidebar = document.getElementById("admin-sidebar");
        this.toggleBtn = document.getElementById("sidebar-toggle");
        this.mobileToggleBtn = document.getElementById("mobile-menu-toggle");
        this.overlay = document.getElementById("sidebar-overlay");
        this.main = document.getElementById("main-content");

        if (!this.sidebar) return;

        this.loadState();
        this.attachEvents();
    }

    /**
     * Load sidebar state from localStorage
     */
    loadState() {
        const isOpen = localStorage.getItem("sidebar-open") !== "false";
        const isMobile = window.innerWidth < 768;

        // Desktop: Apply saved state
        // Mobile: Always start closed
        if (!isMobile) {
            this.setState(isOpen, false);
        } else {
            this.closeMobile();
        }
    }

    /**
     * Set sidebar state (open/closed)
     */
    setState(isOpen, saveState = true) {
        if (isOpen) {
            this.sidebar.classList.remove("collapsed");
            this.main.classList.remove("expanded");
        } else {
            this.sidebar.classList.add("collapsed");
            this.main.classList.add("expanded");
        }

        if (saveState) {
            localStorage.setItem("sidebar-open", isOpen);
        }
    }

    /**
     * Toggle sidebar (desktop)
     */
    toggle() {
        const isOpen = !this.sidebar.classList.contains("collapsed");
        this.setState(!isOpen);
    }

    /**
     * Open mobile menu
     */
    openMobile() {
        this.sidebar.classList.add("mobile-open");
        this.overlay.classList.remove("hidden");
        document.body.style.overflow = "hidden";
    }

    /**
     * Close mobile menu
     */
    closeMobile() {
        this.sidebar.classList.remove("mobile-open");
        this.overlay.classList.add("hidden");
        document.body.style.overflow = "";
    }

    /**
     * Attach event listeners
     */
    attachEvents() {
        // Desktop toggle
        if (this.toggleBtn) {
            this.toggleBtn.addEventListener("click", () => this.toggle());
        }

        // Mobile toggle
        if (this.mobileToggleBtn) {
            this.mobileToggleBtn.addEventListener("click", () =>
                this.openMobile(),
            );
        }

        // Overlay click (close mobile menu)
        if (this.overlay) {
            this.overlay.addEventListener("click", () => this.closeMobile());
        }

        // Handle window resize
        window.addEventListener("resize", () => {
            const isMobile = window.innerWidth < 768;
            if (isMobile) {
                this.closeMobile();
            }
        });
    }
}

// Initialize when DOM is ready
document.addEventListener("DOMContentLoaded", () => {
    new SidebarManager();
});

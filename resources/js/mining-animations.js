/**
 * Néré Mining Theme - Advanced Animations & Effects
 */

document.addEventListener('DOMContentLoaded', () => {
    initializeMiningEffects();
    initializeScrollAnimations();
    initializeInteractiveElements();
    initializeParticleEffects();
});

/**
 * Initialize mining theme effects
 */
function initializeMiningEffects() {
    // Add animation classes to elements on page load
    const elements = document.querySelectorAll('[data-animate]');
    elements.forEach((el, index) => {
        const animation = el.getAttribute('data-animate');
        const delay = el.getAttribute('data-delay') || index * 0.1;
        
        el.style.setProperty('--animation-delay', `${delay}s`);
        el.classList.add(animation);
    });

    // Animate mining cards on hover
    const miningCards = document.querySelectorAll('.mining-card');
    miningCards.forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-4px) scale(1.01)';
        });
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(0) scale(1)';
        });
    });

    // Add gold glow effect to buttons on hover
    const goldButtons = document.querySelectorAll('.btn-gold, .mining-button[variant="gold"]');
    goldButtons.forEach(button => {
        button.addEventListener('mouseenter', () => {
            button.classList.add('gold-glow-animated');
        });
        button.addEventListener('mouseleave', () => {
            button.classList.remove('gold-glow-animated');
        });
    });

    // Add crimson pulse to alerts
    const alerts = document.querySelectorAll('[role="alert"]');
    alerts.forEach(alert => {
        alert.classList.add('crimson-pulse');
    });
}

/**
 * Initialize scroll-triggered animations
 */
function initializeScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -100px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in-up');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Observe all elements with data-scroll-animate
    document.querySelectorAll('[data-scroll-animate]').forEach(el => {
        observer.observe(el);
    });

    // Also observe dashboard cards
    document.querySelectorAll('.dashboard-card').forEach(el => {
        observer.observe(el);
    });
}

/**
 * Initialize interactive elements
 */
function initializeInteractiveElements() {
    // Animated counters
    const counters = document.querySelectorAll('[data-counter]');
    counters.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-counter'));
        const duration = 1000;
        const start = Date.now();
        const initialValue = parseInt(counter.innerText) || 0;

        const animate = () => {
            const elapsed = Date.now() - start;
            const progress = Math.min(elapsed / duration, 1);
            const value = Math.floor(initialValue + (target - initialValue) * progress);
            counter.innerText = value;

            if (progress < 1) {
                requestAnimationFrame(animate);
            }
        };

        animate();
    });

    // Tab switcher animations
    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Remove active from all
            tabButtons.forEach(b => b.classList.remove('active'));
            // Add active to clicked
            button.classList.add('active');
            button.classList.add('scale-pulse');
            
            setTimeout(() => {
                button.classList.remove('scale-pulse');
            }, 500);
        });
    });

    // Filter chip animations
    const filterChips = document.querySelectorAll('.filter-chip');
    filterChips.forEach(chip => {
        chip.addEventListener('click', () => {
            chip.classList.toggle('active');
            chip.classList.add('scale-pulse');
            
            setTimeout(() => {
                chip.classList.remove('scale-pulse');
            }, 500);
        });
    });

    // Tooltip animations
    const tooltips = document.querySelectorAll('.tooltip');
    tooltips.forEach(tooltip => {
        tooltip.addEventListener('mouseenter', () => {
            const tooltipText = tooltip.querySelector('.tooltip-text');
            if (tooltipText) {
                tooltipText.classList.add('fade-in-up');
            }
        });
    });
}

/**
 * Initialize particle effects
 */
function initializeParticleEffects() {
    // Create floating particles in background
    const createParticle = () => {
        const particle = document.createElement('div');
        const size = Math.random() * 5 + 2;
        const duration = Math.random() * 20 + 10;
        const x = Math.random() * window.innerWidth;
        const delay = Math.random() * 5;

        particle.style.cssText = `
            position: fixed;
            left: ${x}px;
            top: -10px;
            width: ${size}px;
            height: ${size}px;
            background: radial-gradient(circle, rgba(255, 215, 0, 0.8), rgba(211, 47, 47, 0.4));
            border-radius: 50%;
            pointer-events: none;
            z-index: -1;
            opacity: 0;
            animation: particleFloat ${duration}s linear ${delay}s infinite;
            box-shadow: 0 0 ${size * 2}px rgba(255, 215, 0, 0.5);
        `;

        document.body.appendChild(particle);
    };

    // Create particles periodically
    for (let i = 0; i < 5; i++) {
        setTimeout(() => createParticle(), i * 2000);
    }

    // Add particle animation keyframes
    const style = document.createElement('style');
    style.innerHTML = `
        @keyframes particleFloat {
            0% {
                transform: translateY(0) translateX(0);
                opacity: 0;
            }
            10% {
                opacity: 0.5;
            }
            90% {
                opacity: 0.5;
            }
            100% {
                transform: translateY(${window.innerHeight + 20}px) translateX(100px);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
}

/**
 * Utility function to add ripple effect to buttons
 */
function addRippleEffect(element) {
    element.addEventListener('click', function(e) {
        const ripple = document.createElement('span');
        const rect = this.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;

        ripple.style.cssText = `
            position: absolute;
            width: ${size}px;
            height: ${size}px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            transform: scale(0);
            left: ${x}px;
            top: ${y}px;
            pointer-events: none;
            animation: ripple 0.6s ease-out;
        `;

        this.appendChild(ripple);

        setTimeout(() => ripple.remove(), 600);
    });
}

/**
 * Add ripple effect to all buttons
 */
document.querySelectorAll('button, [role="button"]').forEach(btn => {
    if (!btn.classList.contains('no-ripple')) {
        addRippleEffect(btn);
    }
});

/**
 * Add ripple animation keyframes
 */
const rippleStyle = document.createElement('style');
rippleStyle.innerHTML = `
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
`;
document.head.appendChild(rippleStyle);

/**
 * Smooth scroll to elements
 */
function smoothScrollTo(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

/**
 * Debounce function for performance
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Add loading animation
 */
function showLoadingAnimation(element) {
    const loader = document.createElement('div');
    loader.className = 'animate-spin-mining';
    loader.innerHTML = `
        <svg class="w-6 h-6 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
        </svg>
    `;
    element.appendChild(loader);
}

/**
 * Remove loading animation
 */
function hideLoadingAnimation(element) {
    const loader = element.querySelector('.animate-spin-mining');
    if (loader) {
        loader.remove();
    }
}

/**
 * Export functions for use in other scripts
 */
window.MiningAnimations = {
    smoothScrollTo,
    debounce,
    showLoadingAnimation,
    hideLoadingAnimation,
    addRippleEffect
};

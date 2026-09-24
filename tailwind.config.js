/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/views/**/*.blade.php',
    './resources/js/**/*.js',
    './resources/js/**/*.vue',
  ],
  theme: {
    extend: {
      colors: {
        // Néré Mining Brand Colors - Inspired by the logo
        // Gold/Yellow from the sun (primary)
        gold: {
          50: '#fffef2',
          100: '#fffce0',
          200: '#fff9c0',
          300: '#fff49e',
          400: '#ffed7c',
          500: '#ffd700', // Main gold
          600: '#ffcc00',
          700: '#e6b800',
          800: '#ccaa00',
          900: '#b39500',
        },
        // Red/Crimson from the rays (accent)
        crimson: {
          50: '#ffe8e8',
          100: '#ffd1d1',
          200: '#ffadad',
          300: '#ff8989',
          400: '#ff6565',
          500: '#d32f2f', // Main crimson/red
          600: '#c62828',
          700: '#b71c1c',
          800: '#a31a1a',
          900: '#8b1818',
        },
        // Deep charcoal/black for background
        charcoal: {
          50: '#f5f5f5',
          100: '#e8e8e8',
          200: '#d0d0d0',
          300: '#b8b8b8',
          400: '#a0a0a0',
          500: '#808080',
          600: '#606060',
          700: '#404040',
          800: '#1a1a1a',
          900: '#0d0d0d',
          950: '#050505',
        },
      },
      boxShadow: {
        'gold-glow': '0 0 20px rgba(255, 215, 0, 0.3)',
        'crimson-glow': '0 0 20px rgba(211, 47, 47, 0.3)',
        'sun-shadow': '-4px 0 20px rgba(255, 215, 0, 0.1)',
      },
      animation: {
        'sun-pulse': 'sun-pulse 3s ease-in-out infinite',
        'ray-rotate': 'ray-rotate 8s linear infinite',
      },
      keyframes: {
        'sun-pulse': {
          '0%, 100%': { boxShadow: '0 0 20px rgba(255, 215, 0, 0.3)' },
          '50%': { boxShadow: '0 0 40px rgba(255, 215, 0, 0.6)' },
        },
        'ray-rotate': {
          'from': { transform: 'rotate(0deg)' },
          'to': { transform: 'rotate(360deg)' },
        },
      },
    },
  },
}

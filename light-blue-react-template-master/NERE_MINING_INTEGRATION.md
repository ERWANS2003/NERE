# Néré Mining - Light Blue React Template Integration

## Overview

This document outlines the complete integration of the Néré Mining brand design system with the Light Blue React Template. The integration provides a professional, modern ITSM platform with Néré Mining's signature gold, crimson, and charcoal color palette.

---

## 🎨 Design System

### Color Palette

#### Primary Colors
- **Gold** (#ffd700) - Primary accent, CTAs, highlights
  - Light: #ffed7c
  - Dark: #ccaa00
  - Lighter: #fffef2
  
- **Crimson** (#d32f2f) - Alerts, high priority, danger states
  - Light: #ff6565
  - Dark: #8b1818
  - Lighter: #ffe8e8

- **Charcoal** (#0d0d0d) - Dark backgrounds, text
  - Light: #1a1a1a
  - Dark: #050505

#### Secondary Colors
- **Light Blue** (#2477ff) - Secondary accent, info states
- **Green** (#2d8515) - Success states
- **Orange** (#e49400) - Warning states

### Theme Structure

```
src/styles/
├── _nere-variables.scss     # Néré color definitions and overrides
├── _nere-theme.scss          # Néré animations, components, utilities
└── theme.scss                # Main theme (imports nere files)
```

---

## 📦 Components

### NereComponents Library

Located in `src/components/NereComponents/`, the library exports 5 reusable React components:

#### 1. **MiningButton**

A versatile button component with Néré Mining styling.

**Props:**
```javascript
<MiningButton
  variant="primary"          // 'primary', 'secondary', 'outline', 'gold', 'crimson'
  size="md"                  // 'sm', 'md', 'lg', 'xl'
  onClick={handleClick}
  disabled={false}
  loading={false}
  className="custom-class"
>
  Click Me
</MiningButton>
```

**Usage Examples:**
```javascript
import { MiningButton } from '../../components/NereComponents';

// Gold button
<MiningButton variant="gold" size="lg">Submit</MiningButton>

// Crimson button
<MiningButton variant="crimson">Delete</MiningButton>

// Outline button
<MiningButton variant="outline">Cancel</MiningButton>

// Loading state
<MiningButton loading>Processing...</MiningButton>
```

---

#### 2. **MiningCard**

A card component with accent borders and Néré styling.

**Props:**
```javascript
<MiningCard
  accent="gold"              // 'gold', 'crimson', 'blue'
  hover={true}               // Enable hover effects
  title="Card Title"
  subtitle="Subtitle text"
  className="custom-class"
>
  Card content goes here
</MiningCard>
```

**Usage Examples:**
```javascript
import { MiningCard } from '../../components/NereComponents';

// Gold accent card
<MiningCard accent="gold" title="Dashboard Stats">
  <p>Your statistics here</p>
</MiningCard>

// Crimson accent card
<MiningCard accent="crimson" title="Critical Alerts">
  <p>Important alerts</p>
</MiningCard>
```

---

#### 3. **MiningBadge**

A badge component for status and category tags.

**Props:**
```javascript
<MiningBadge
  type="success"             // 'success', 'warning', 'danger', 'gold', 'info', 'default'
  size="md"                  // 'sm', 'md', 'lg'
  icon="la la-check-circle"  // Optional icon class
  className="custom-class"
>
  Badge Text
</MiningBadge>
```

**Usage Examples:**
```javascript
import { MiningBadge } from '../../components/NereComponents';

// Success badge
<MiningBadge type="success" icon="la la-check">Resolved</MiningBadge>

// Gold badge
<MiningBadge type="gold">In Progress</MiningBadge>

// Danger badge
<MiningBadge type="danger" icon="la la-exclamation">Critical</MiningBadge>
```

---

#### 4. **MiningAlert**

A dismissible alert component with 4 types.

**Props:**
```javascript
<MiningAlert
  type="info"                // 'success', 'warning', 'danger', 'info'
  title="Alert Title"
  dismissible={true}
  onDismiss={handleDismiss}
  icon="la la-info-circle"   // Optional custom icon
  className="custom-class"
>
  Alert message content
</MiningAlert>
```

**Usage Examples:**
```javascript
import { MiningAlert } from '../../components/NereComponents';

// Info alert
<MiningAlert type="info" title="System Status" dismissible>
  All systems operational
</MiningAlert>

// Success alert
<MiningAlert type="success">
  Ticket has been created successfully
</MiningAlert>

// Danger alert
<MiningAlert type="danger" title="Error">
  An error occurred while processing
</MiningAlert>
```

---

#### 5. **MiningStatCard**

A statistics card with trend indicators.

**Props:**
```javascript
<MiningStatCard
  title="Stat Title"
  value={42}                 // String or number
  color="gold"               // 'gold', 'crimson', 'blue', 'green'
  trend={5}                  // Positive or negative percentage
  icon="la la-ticket"        // Optional icon class
  className="custom-class"
/>
```

**Usage Examples:**
```javascript
import { MiningStatCard } from '../../components/NereComponents';

// Open tickets stat
<MiningStatCard
  title="Open Tickets"
  value={24}
  color="gold"
  trend={5}
  icon="la la-ticket"
/>

// Resolved stat
<MiningStatCard
  title="Resolved Today"
  value={8}
  color="green"
  trend={12}
  icon="la la-check-circle"
/>

// Critical stat
<MiningStatCard
  title="Critical Items"
  value={3}
  color="crimson"
  trend={-2}
  icon="la la-exclamation-triangle"
/>
```

---

## 🎬 Animations

The theme includes 12+ CSS animations for enhanced UX:

### Animation Classes

```html
<!-- Fade in from bottom -->
<div class="fade-in-up">Content</div>

<!-- Gold glow effect -->
<div class="gold-glow">Glowing element</div>

<!-- Crimson pulse -->
<div class="crimson-pulse">Pulsing alert</div>

<!-- Scale pulse -->
<div class="scale-pulse">Scaling element</div>

<!-- Slide in from left -->
<div class="slide-in-left">Sliding content</div>

<!-- Slide in from right -->
<div class="slide-in-right">Sliding content</div>

<!-- Shimmer loading effect -->
<div class="shimmer">Loading...</div>

<!-- Rotating border -->
<div class="rotating-border">Border animation</div>

<!-- Spin mining (custom spinner) -->
<i class="spin-mining">⟳</i>

<!-- Bounce animation -->
<div class="bounce-mining">Bouncing element</div>
```

### Hover Effects

```html
<!-- Lift on hover -->
<div class="hover-lift">Hover me</div>

<!-- Gold glow on hover -->
<div class="hover-glow-gold">Hover me</div>

<!-- Crimson glow on hover -->
<div class="hover-glow-crimson">Hover me</div>
```

---

## 🎯 CSS Classes

### Text Colors

```html
<p class="text-gold">Gold text</p>
<p class="text-crimson">Crimson text</p>
<p class="text-charcoal">Charcoal text</p>
```

### Background Colors

```html
<div class="bg-gold">Gold background</div>
<div class="bg-crimson">Crimson background</div>
<div class="bg-charcoal">Charcoal background</div>
```

### Border Colors

```html
<div class="border-gold">Gold border</div>
<div class="border-crimson">Crimson border</div>
```

### Shadow Effects

```html
<div class="border-gold-glow">Gold glow</div>
<div class="border-crimson-glow">Crimson glow</div>
```

---

## 🏠 Dashboard

### DashboardNereMining Component

The main ITSM dashboard is located at `src/pages/dashboard/DashboardNereMining.js`.

**Features:**
- 4 KPI stat cards (Open Tickets, Resolved Today, Pending Items, Avg Response Time)
- System status alert
- Recent tickets table with status and priority badges
- Map statistics and charts
- Responsive grid layout
- Animations on page load

**Layout:**
```
Dashboard
├── Page Title (with fade-in animation)
├── System Alert (dismissible)
├── KPI Stats Row (4 columns on desktop, responsive)
├── Main Content Grid
│   ├── Map (7 columns)
│   └── Map Statistics Card (4 columns)
├── Recent Tickets Table
└── Charts Row (Calendar + Trends)
```

---

## 📁 File Structure

```
light-blue-react-template-master/
├── src/
│   ├── components/
│   │   ├── NereComponents/
│   │   │   ├── MiningButton.js
│   │   │   ├── MiningCard.js
│   │   │   ├── MiningBadge.js
│   │   │   ├── MiningAlert.js
│   │   │   ├── MiningStatCard.js
│   │   │   ├── NereComponents.module.scss
│   │   │   ├── index.js
│   │   │   └── package.json
│   │   ├── Layout/
│   │   │   └── Layout.js (updated with DashboardNereMining)
│   │   └── ... (other components)
│   ├── pages/
│   │   └── dashboard/
│   │       ├── DashboardNereMining.js (new)
│   │       └── ... (other pages)
│   ├── styles/
│   │   ├── _nere-variables.scss (new)
│   │   ├── _nere-theme.scss (new)
│   │   ├── theme.scss (updated)
│   │   └── ... (other styles)
│   └── ... (other files)
```

---

## 🚀 Quick Start

### 1. Import Components

```javascript
import { 
  MiningButton, 
  MiningCard, 
  MiningBadge, 
  MiningAlert,
  MiningStatCard 
} from '../../components/NereComponents';
```

### 2. Use in Your Component

```javascript
import React from 'react';
import { MiningButton, MiningCard, MiningStatCard } from '../../components/NereComponents';

export default function MyComponent() {
  return (
    <div>
      <MiningCard accent="gold" title="My Card">
        <MiningStatCard 
          title="Tickets" 
          value={42} 
          color="gold" 
          trend={5}
        />
        <MiningButton variant="gold">Submit</MiningButton>
      </MiningCard>
    </div>
  );
}
```

### 3. Use CSS Classes

```html
<div class="fade-in-up">
  <h1 class="text-gold">Welcome</h1>
  <div class="card-mining accent-gold hover-lift">
    Content here
  </div>
</div>
```

---

## 🎨 Customization

### Modify Colors

Edit `src/styles/_nere-variables.scss`:

```scss
// Change primary gold color
$nere-gold: #new-color;

// Change all theme colors
$theme-colors: (
  primary: $nere-gold,
  danger: $nere-crimson,
  // ... other colors
);
```

### Add New Animations

Edit `src/styles/_nere-theme.scss` and add new keyframes:

```scss
@keyframes myAnimation {
  from {
    // Start state
  }
  to {
    // End state
  }
}

.my-animation {
  animation: myAnimation 1s ease-in-out;
}
```

### Extend Components

Create new components in `src/components/NereComponents/`:

```javascript
import React from 'react';
import PropTypes from 'prop-types';

const MyComponent = ({ prop1, children, ...props }) => {
  return (
    <div className="my-component-class" {...props}>
      {children}
    </div>
  );
};

MyComponent.propTypes = {
  prop1: PropTypes.string,
  children: PropTypes.node.isRequired,
};

export default MyComponent;
```

---

## 📚 Integration Points

### SCSS Variables

All Light Blue components now have access to Néré colors:

```scss
$nere-gold        // Primary gold color
$nere-crimson     // Primary crimson color
$nere-charcoal    // Primary charcoal color
$shadow-gold      // Gold shadow effects
$shadow-crimson   // Crimson shadow effects
```

### Component Classes

Automatically available on all elements:

- `.btn-mining-primary` - Gold button
- `.btn-mining-secondary` - Crimson button
- `.card-mining` - Néré card
- `.badge-mining-*` - Badge variants
- `.alert-mining` - Alert component
- `.table-mining` - Table styling

---

## 🔄 Updating the Dashboard

### Current Routes

```javascript
/app/main/dashboard         // DashboardNereMining
/app/components/icons       // Icon showcase
/app/notifications          // Notifications page
/app/components/charts      // Charts page
/app/tables                 // Tables page
/app/components/maps        // Maps page
/app/typography             // Typography
```

### Adding New Pages with Néré Theme

```javascript
import { MiningButton, MiningCard } from '../../components/NereComponents';

export default function MyPage() {
  return (
    <div className="fade-in-up">
      <MiningCard accent="gold">
        {/* Your content */}
      </MiningCard>
    </div>
  );
}
```

---

## 🐛 Troubleshooting

### Components Not Showing Correct Colors

**Issue:** Components appear with default Light Blue colors.

**Solution:** Ensure `theme.scss` imports are correct:
```scss
@import 'nere-variables';
@import 'nere-theme';
```

### Animations Not Working

**Issue:** Animation classes don't trigger animations.

**Solution:** Verify `_nere-theme.scss` is imported in `theme.scss`.

### SCSS Variables Not Found

**Issue:** `$nere-gold` variable is undefined.

**Solution:** Check that `_nere-variables.scss` is imported before usage.

---

## 📝 Best Practices

1. **Use Components Over CSS Classes**
   - Prefer `<MiningButton>` over `<button class="btn-mining-primary">`
   - Better for maintainability and prop handling

2. **Consistent Color Usage**
   - Gold for primary actions
   - Crimson for alerts/danger
   - Charcoal for dark backgrounds

3. **Responsive Design**
   - Use Bootstrap grid system
   - Components are responsive by default

4. **Animations**
   - Use sparingly for better performance
   - Combine multiple animations for complex effects

5. **Type Safety**
   - Leverage PropTypes for component validation
   - Import types from component exports

---

## 📊 Component Variants Summary

| Component | Variants | Sizes | Colors |
|-----------|----------|-------|--------|
| MiningButton | primary, secondary, outline | sm, md, lg, xl | Gold, Crimson |
| MiningCard | - | - | gold, crimson, blue |
| MiningBadge | success, warning, danger, gold, info, default | sm, md, lg | 6 types |
| MiningAlert | success, warning, danger, info | - | 4 types |
| MiningStatCard | - | - | gold, crimson, blue, green |

---

## 🔗 Related Files

- Component Library: `src/components/NereComponents/`
- Theme Variables: `src/styles/_nere-variables.scss`
- Theme Styles: `src/styles/_nere-theme.scss`
- Enhanced Dashboard: `src/pages/dashboard/DashboardNereMining.js`
- Layout: `src/components/Layout/Layout.js`

---

## 📞 Support

For questions or issues related to the Néré Mining integration:

1. Check the component PropTypes for valid props
2. Review examples in this documentation
3. Inspect element in browser to verify CSS classes
4. Check browser console for warnings/errors

---

**Last Updated:** September 2026
**Version:** 1.0.0
**Status:** Production Ready

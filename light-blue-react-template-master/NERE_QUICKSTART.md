# Néré Mining - Quick Start Guide

## 5-Minute Setup

### Step 1: Install Dependencies
```bash
npm install
# or
yarn install
```

### Step 2: Start Development Server
```bash
npm start
# or
yarn start
```

The app will be available at `http://localhost:3000`

---

## Using Néré Components

### Import Components
```javascript
import { 
  MiningButton, 
  MiningCard, 
  MiningBadge, 
  MiningAlert,
  MiningStatCard 
} from '../../components/NereComponents';
```

### Example 1: Simple Button
```javascript
<MiningButton variant="gold" size="lg">
  Submit
</MiningButton>
```

### Example 2: Card with Stats
```javascript
<MiningCard accent="gold" title="Dashboard">
  <MiningStatCard
    title="Open Tickets"
    value={24}
    color="gold"
    trend={5}
    icon="la la-ticket"
  />
</MiningCard>
```

### Example 3: Alert with Badge
```javascript
<MiningAlert type="success" title="Success" dismissible>
  Your action completed successfully!
  <MiningBadge type="success" className="ml-2">
    Done
  </MiningBadge>
</MiningAlert>
```

### Example 4: Status Table
```javascript
<table className="table-mining">
  <thead>
    <tr>
      <th>Ticket</th>
      <th>Status</th>
      <th>Priority</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td className="text-gold">TKT-001</td>
      <td><MiningBadge type="gold">In Progress</MiningBadge></td>
      <td><MiningBadge type="danger">High</MiningBadge></td>
    </tr>
  </tbody>
</table>
```

---

## Néré Colors

### CSS Classes
```html
<!-- Text colors -->
<p class="text-gold">Gold text</p>
<p class="text-crimson">Crimson text</p>
<p class="text-charcoal">Charcoal text</p>

<!-- Backgrounds -->
<div class="bg-gold">Gold background</div>
<div class="bg-crimson">Crimson background</div>

<!-- Borders -->
<div class="border-gold">Gold border</div>
<div class="border-crimson">Crimson border</div>

<!-- Effects -->
<div class="border-gold-glow">Gold glow shadow</div>
<div class="border-crimson-glow">Crimson glow shadow</div>
```

---

## Animations

### Available Animations
```html
<!-- Fade in from bottom -->
<div class="fade-in-up">Content</div>

<!-- Gold glow effect -->
<div class="gold-glow">Glowing</div>

<!-- Crimson pulse -->
<div class="crimson-pulse">Pulsing</div>

<!-- Scale pulse -->
<div class="scale-pulse">Scaling</div>

<!-- Slide in left/right -->
<div class="slide-in-left">Sliding</div>
<div class="slide-in-right">Sliding</div>

<!-- Shimmer (loading) -->
<div class="shimmer">Loading...</div>

<!-- Hover lift -->
<div class="hover-lift">Hover me</div>

<!-- Custom spinner -->
<i class="spin-mining"></i>

<!-- Bounce -->
<div class="bounce-mining">Bouncing</div>
```

---

## Component Props Reference

### MiningButton
```javascript
<MiningButton
  variant="primary"  // 'primary'|'secondary'|'outline'|'gold'|'crimson'
  size="md"          // 'sm'|'md'|'lg'|'xl'
  onClick={fn}
  disabled={false}
  loading={false}
>
  Button Text
</MiningButton>
```

### MiningCard
```javascript
<MiningCard
  accent="gold"      // 'gold'|'crimson'|'blue'
  hover={true}
  title="Title"
  subtitle="Subtitle"
>
  Content
</MiningCard>
```

### MiningBadge
```javascript
<MiningBadge
  type="success"     // 'success'|'warning'|'danger'|'gold'|'info'|'default'
  size="md"          // 'sm'|'md'|'lg'
  icon="la la-icon"
>
  Badge Text
</MiningBadge>
```

### MiningAlert
```javascript
<MiningAlert
  type="info"        // 'success'|'warning'|'danger'|'info'
  title="Title"
  dismissible={true}
  onDismiss={fn}
  icon="la la-icon"
>
  Alert content
</MiningAlert>
```

### MiningStatCard
```javascript
<MiningStatCard
  title="Title"
  value={42}
  color="gold"       // 'gold'|'crimson'|'blue'|'green'
  trend={5}          // Positive or negative %
  icon="la la-icon"
/>
```

---

## File Locations

| Item | Path |
|------|------|
| Components | `src/components/NereComponents/` |
| Styles | `src/styles/` |
| Dashboard | `src/pages/dashboard/DashboardNereMining.js` |
| Colors | `src/styles/_nere-variables.scss` |
| Animations | `src/styles/_nere-theme.scss` |

---

## Common Tasks

### Add a New Page
```javascript
// src/pages/mypage/MyPage.js
import React from 'react';
import { MiningCard, MiningButton } from '../../components/NereComponents';

export default function MyPage() {
  return (
    <div className="fade-in-up">
      <h1 className="text-gold mb-4">My Page</h1>
      <MiningCard accent="gold">
        <MiningButton variant="gold">Click Me</MiningButton>
      </MiningCard>
    </div>
  );
}
```

### Add a Route
```javascript
// In src/components/Layout/Layout.js
import MyPage from '../../pages/mypage/MyPage';

// Add to routes:
<Route path="/app/mypage" exact component={MyPage} />
```

### Use Animations
```javascript
<div className="fade-in-up">Page Content</div>
<div className="gold-glow">Glowing element</div>
```

### Change Colors
Edit `src/styles/_nere-variables.scss`:
```scss
$nere-gold: #new-gold-color;
$nere-crimson: #new-crimson-color;
```

---

## Project Structure

```
light-blue-react-template-master/
├── src/
│   ├── components/
│   │   ├── NereComponents/     ← Néré components
│   │   ├── Layout/
│   │   ├── Header/
│   │   ├── Sidebar/
│   │   └── ... other components
│   ├── pages/
│   │   ├── dashboard/
│   │   │   ├── DashboardNereMining.js  ← Main dashboard
│   │   │   └── ... other dashboard files
│   │   └── ... other pages
│   ├── styles/
│   │   ├── _nere-variables.scss    ← Néré colors
│   │   ├── _nere-theme.scss        ← Néré styles
│   │   ├── theme.scss              ← Main theme
│   │   └── ... other styles
│   └── ... other files
├── public/
├── package.json
└── README.md
```

---

## Tips & Tricks

1. **Always use components instead of plain HTML**
   ```javascript
   // ✅ Good
   <MiningButton variant="gold">Submit</MiningButton>
   
   // ❌ Avoid
   <button className="btn-mining-primary">Submit</button>
   ```

2. **Combine animations with components**
   ```javascript
   <div className="fade-in-up">
     <MiningCard accent="gold">
       <MiningStatCard title="Stats" value={42} color="gold" />
     </MiningCard>
   </div>
   ```

3. **Use proper colors semantically**
   - Gold (#ffd700): Primary actions, success, emphasis
   - Crimson (#d32f2f): Danger, alerts, high priority
   - Charcoal (#0d0d0d): Dark backgrounds, text

4. **Mobile responsive by default**
   - All components are responsive
   - Use Bootstrap grid (Col, Row)
   - Test on multiple screen sizes

---

## Troubleshooting

### Components not styled
- Check imports: `import { MiningButton } from '../../components/NereComponents'`
- Verify `theme.scss` includes Néré imports

### Colors wrong
- Clear browser cache
- Rebuild project: `npm run build`
- Check `_nere-variables.scss` for color definitions

### Animations not working
- Verify element has correct class name
- Check browser DevTools for applied classes
- Ensure `_nere-theme.scss` is imported

---

## Documentation

- **Full Guide**: `NERE_MINING_INTEGRATION.md`
- **This Guide**: `NERE_QUICKSTART.md`
- **Component Props**: Check JSDoc comments in component files

---

## Next Steps

1. ✅ Installation complete
2. 📦 Import components in your pages
3. 🎨 Use Néré colors and animations
4. 📱 Test on mobile devices
5. 🚀 Deploy to production

---

**Need help?** Check the component files for PropTypes and examples!

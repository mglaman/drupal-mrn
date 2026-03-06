# Testing Guide

This directory contains tests for the Drupal MRN frontend application.

## Test Structure

- **Unit/Component Tests** (`*.test.js`): Vitest tests for component logic and behavior
- **Visual Regression Tests** (`visual/*.spec.js`): Playwright tests for visual consistency

## Running Tests

### Unit/Component Tests

```bash
# Run all tests
npm run test

# Run tests in watch mode
npm run test -- --watch

# Run tests with UI
npm run test:ui

# Run tests with coverage
npm run test:coverage
```

### Visual Regression Tests

Visual regression tests use Playwright to capture screenshots and compare them against baseline images. This is especially useful for detecting Tailwind CSS changes when upgrading packages.

```bash
# Run visual regression tests
npm run test:visual

# Run with UI mode (interactive)
npm run test:visual:ui

# Update baseline screenshots (use when UI changes are intentional)
npm run test:visual:update
```

**Note**: On first run, you'll need to generate baseline screenshots. The tests will fail initially, then you can update the baselines with `npm run test:visual:update`.

### Run All Tests

```bash
npm run test:all
```

## Visual Regression Testing

Visual regression tests capture screenshots of the application in various states:

- Initial page state
- Form with project filled
- Form with all fields filled
- Error states
- Loading states
- Release notes displayed (preview and source modes)

### When to Update Baselines

Update baseline screenshots when:
- You intentionally change the UI design
- You upgrade Tailwind CSS or other styling dependencies
- You modify component layouts or styling

**Important**: Always review the visual diff before updating baselines to ensure changes are intentional.

### Screenshot Location

Baseline screenshots are stored in `tests/visual/app.spec.js-snapshots/` directory.

## Writing Tests

### Unit/Component Tests

Use Vitest with `@testing-library/svelte` for component tests:

```javascript
import { describe, it, expect } from 'vitest'
import { render, screen } from '@testing-library/svelte'
import App from '../src/App.svelte'

describe('App Component', () => {
  it('renders correctly', () => {
    render(App)
    expect(screen.getByText('Generate release notes')).toBeInTheDocument()
  })
})
```

### Visual Regression Tests

Use Playwright for visual regression tests:

```javascript
import { test, expect } from '@playwright/test'

test('my feature', async ({ page }) => {
  await page.goto('/')
  await expect(page).toHaveScreenshot('my-feature.png', {
    fullPage: true,
    animations: 'disabled'
  })
})
```

## CI/CD Integration

Visual regression tests should run in CI to catch unintended visual changes. The tests will fail if screenshots don't match baselines, requiring manual review and baseline updates if changes are intentional.

## Test Coverage

Current test coverage includes:
- Component rendering
- Form interactions
- API mocking and error handling
- Version finding logic
- Button state management
- Loading states

import { test, expect } from '@playwright/test'

test.describe('Visual Regression Tests', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/')
  })

  test('initial page state', async ({ page }) => {
    await expect(page).toHaveScreenshot('initial-state.png', {
      fullPage: true,
      animations: 'disabled'
    })
  })

  test('form with project filled', async ({ page }) => {
    const projectInput = page.getByLabel(/project/i)
    await projectInput.fill('views_remote_data')
    await projectInput.blur()

    // Wait for any loading to complete
    await page.waitForTimeout(500)

    await expect(page).toHaveScreenshot('form-project-filled.png', {
      fullPage: true,
      animations: 'disabled'
    })
  })

  test('form with all fields filled', async ({ page }) => {
    const projectInput = page.getByLabel(/project/i)
    const versionInput = page.getByLabel(/version/i)
    const previousInput = page.getByLabel(/previous release/i)

    await projectInput.fill('views_remote_data')
    await projectInput.blur()
    await page.waitForTimeout(500) // Wait for project data to load

    await versionInput.fill('2.0.5')
    await previousInput.fill('2.0.4')

    await expect(page).toHaveScreenshot('form-all-fields-filled.png', {
      fullPage: true,
      animations: 'disabled'
    })
  })

  test('error state', async ({ page }) => {
    const projectInput = page.getByLabel(/project/i)

    // Mock a failed API response
    await page.route('**/project?*', route => {
      route.fulfill({
        status: 404,
        contentType: 'application/json',
        body: JSON.stringify({ message: 'Project not found' })
      })
    })

    await projectInput.fill('invalid_project_name')
    await projectInput.blur()
    await page.waitForTimeout(500)

    await expect(page).toHaveScreenshot('error-state.png', {
      fullPage: true,
      animations: 'disabled'
    })
  })

  test('loading state', async ({ page }) => {
    const projectInput = page.getByLabel(/project/i)
    const versionInput = page.getByLabel(/version/i)
    const previousInput = page.getByLabel(/previous release/i)

    // Mock project API response
    await page.route('**/project?*', route => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          branches: ['main'],
          tags: [{ name: '1.0.0' }, { name: '1.0.1' }]
        })
      })
    })

    // Mock delayed changelog API response
    await page.route('**/changelog?*', route => {
      setTimeout(() => {
        route.fulfill({
          status: 200,
          contentType: 'text/html',
          body: '<h1>Release Notes</h1>'
        })
      }, 1000)
    })

    await projectInput.fill('test_project')
    await projectInput.blur()

    // Wait for project data to load
    await page.waitForTimeout(500)

    await versionInput.fill('1.0.1')
    await previousInput.fill('1.0.0')

    // Wait for button to be enabled
    const button = page.getByRole('button', { name: /generate release notes/i })
    await button.waitFor({ state: 'visible' })
    await page.waitForTimeout(100) // Give Svelte time to enable the button

    // Click button to trigger loading state
    await button.click()

    // Capture during loading (before the delayed response completes)
    await page.waitForTimeout(200)

    await expect(page).toHaveScreenshot('loading-state.png', {
      fullPage: true,
      animations: 'disabled'
    })
  })

  test('release notes displayed', async ({ page }) => {
    const projectInput = page.getByLabel(/project/i)
    const versionInput = page.getByLabel(/version/i)
    const previousInput = page.getByLabel(/previous release/i)

    // Mock successful responses
    await page.route('**/project?*', route => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          branches: ['main'],
          tags: ['1.0.0', '1.0.1']
        })
      })
    })

    await page.route('**/changelog?*', route => {
      route.fulfill({
        status: 200,
        contentType: 'text/html',
        body: `
          <h1>Release Notes</h1>
          <h2>Bug Fixes</h2>
          <ul>
            <li>Fixed issue with data fetching (#12345)</li>
            <li>Resolved memory leak (#12346)</li>
          </ul>
          <h2>New Features</h2>
          <ul>
            <li>Added new API endpoint (#12347)</li>
          </ul>
        `
      })
    })

    await projectInput.fill('test_project')
    await projectInput.blur()
    await page.waitForTimeout(500)

    await versionInput.fill('1.0.1')
    await previousInput.fill('1.0.0')

    const button = page.getByRole('button', { name: /generate release notes/i })
    await button.click()
    
    // Wait for content to load and render
    await page.waitForLoadState('networkidle')
    await page.waitForTimeout(500)

    await expect(page).toHaveScreenshot('release-notes-displayed.png', {
      fullPage: true,
      animations: 'disabled',
      maxDiffPixelRatio: 0.02  // Allow up to 2% difference for font rendering variations
    })
  })

  test('release notes preview mode', async ({ page }) => {
    const projectInput = page.getByLabel(/project/i)
    const versionInput = page.getByLabel(/version/i)
    const previousInput = page.getByLabel(/previous release/i)

    await page.route('**/project?*', route => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          branches: ['main'],
          tags: ['1.0.0', '1.0.1']
        })
      })
    })

    await page.route('**/changelog?*', route => {
      route.fulfill({
        status: 200,
        contentType: 'text/html',
        body: '<h1>Release Notes</h1><p>Test content</p>'
      })
    })

    await projectInput.fill('test_project')
    await projectInput.blur()
    await page.waitForTimeout(500)

    await versionInput.fill('1.0.1')
    await previousInput.fill('1.0.0')

    const button = page.getByRole('button', { name: /generate release notes/i })
    await button.click()
    await page.waitForTimeout(1000)

    // Switch to preview mode (should be default)
    const previewButton = page.getByRole('tab', { name: /preview/i })
    await previewButton.click()
    await page.waitForTimeout(300)

    await expect(page).toHaveScreenshot('release-notes-preview-mode.png', {
      fullPage: true,
      animations: 'disabled'
    })
  })

  test('release notes source mode', async ({ page }) => {
    const projectInput = page.getByLabel(/project/i)
    const versionInput = page.getByLabel(/version/i)
    const previousInput = page.getByLabel(/previous release/i)

    await page.route('**/project?*', route => {
      route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({
          branches: ['main'],
          tags: ['1.0.0', '1.0.1']
        })
      })
    })

    await page.route('**/changelog?*', route => {
      route.fulfill({
        status: 200,
        contentType: 'text/html',
        body: '<h1>Release Notes</h1><p>Test content</p>'
      })
    })

    await projectInput.fill('test_project')
    await projectInput.blur()
    await page.waitForTimeout(500)

    await versionInput.fill('1.0.1')
    await previousInput.fill('1.0.0')

    const button = page.getByRole('button', { name: /generate release notes/i })
    await button.click()
    await page.waitForTimeout(1000)

    // Switch to source mode
    const sourceButton = page.getByRole('tab', { name: /source/i })
    await sourceButton.click()
    await page.waitForTimeout(300)

    await expect(page).toHaveScreenshot('release-notes-source-mode.png', {
      fullPage: true,
      animations: 'disabled'
    })
  })
})


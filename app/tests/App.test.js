import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { render, screen, waitFor } from '@testing-library/svelte'
import userEvent from '@testing-library/user-event'
import App from '../src/App.svelte'

// Mock fetch globally
global.fetch = vi.fn()

describe('App Component', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    // Reset fetch mock
    global.fetch.mockClear()
  })

  afterEach(() => {
    vi.restoreAllMocks()
  })

  it('renders the main heading', () => {
    render(App)
    const heading = screen.getByRole('heading', { name: /generate release notes/i })
    expect(heading).toBeInTheDocument()
    expect(heading.tagName).toBe('H1')
  })

  it('renders the project input field', () => {
    render(App)
    const projectInput = screen.getByLabelText(/project/i)
    expect(projectInput).toBeInTheDocument()
    expect(projectInput).toHaveAttribute('type', 'text')
    expect(projectInput).toHaveAttribute('required')
  })

  it('renders version and previous release input fields', () => {
    render(App)
    const versionInput = screen.getByLabelText(/version/i)
    const previousInput = screen.getByLabelText(/previous release/i)

    expect(versionInput).toBeInTheDocument()
    expect(previousInput).toBeInTheDocument()
    expect(versionInput).toHaveAttribute('required')
    expect(previousInput).toHaveAttribute('required')
  })

  it('renders the generate button', () => {
    render(App)
    const button = screen.getByRole('button', { name: /generate release notes/i })
    expect(button).toBeInTheDocument()
    expect(button).toBeDisabled() // Should be disabled initially
  })

  it('enables button when project and versions are filled', async () => {
    const user = userEvent.setup()
    render(App)

    const versionInput = screen.getByLabelText(/version/i)
    const previousInput = screen.getByLabelText(/previous release/i)

    // Initially disabled
    const button = screen.getByRole('button', { name: /generate release notes/i })
    expect(button).toBeDisabled()

    // Fill in the form - directly set values and trigger input events
    await user.type(versionInput, '1.0.1')
    await user.type(previousInput, '1.0.0')

    // Give Svelte time to process the reactivity
    await new Promise(resolve => setTimeout(resolve, 100))

    // Button should be enabled - check the disabled attribute directly
    await waitFor(() => {
      const updatedButton = screen.getByRole('button', { name: /generate release notes/i })
      // Check that button is not disabled by checking the disabled attribute
      expect(updatedButton.hasAttribute('disabled')).toBe(false)
    }, { timeout: 2000 })
  })

  it('fetches project data on blur', async () => {
    const user = userEvent.setup()
    const mockProjectData = {
      branches: ['main', 'develop'],
      tags: ['1.0.0', '1.0.1', '1.1.0']
    }

    global.fetch.mockResolvedValueOnce({
      ok: true,
      json: async () => mockProjectData
    })

    render(App)
    const projectInput = screen.getByLabelText(/project/i)

    await user.type(projectInput, 'test_project')
    await user.tab() // Triggers blur

    await waitFor(() => {
      expect(global.fetch).toHaveBeenCalledWith(
        expect.stringContaining('/project?project=test_project')
      )
    })
  })

  it('displays error message when project fetch fails', async () => {
    const user = userEvent.setup()
    const errorMessage = 'Project not found'

    global.fetch.mockResolvedValueOnce({
      ok: false,
      json: async () => ({ message: errorMessage })
    })

    render(App)
    const projectInput = screen.getByLabelText(/project/i)

    await user.type(projectInput, 'invalid_project')
    await user.tab()

    await waitFor(() => {
      expect(screen.getByText(errorMessage)).toBeInTheDocument()
    })
  })

  it('auto-fills previous version when version changes', async () => {
    const user = userEvent.setup()
    const mockProjectData = {
      branches: [],
      tags: [{ name: '1.0.0' }, { name: '1.0.1' }, { name: '1.0.2' }, { name: '1.1.0' }]
    }

    global.fetch.mockResolvedValueOnce({
      ok: true,
      json: async () => mockProjectData
    })

    render(App)
    const projectInput = screen.getByLabelText(/project/i)
    const versionInput = screen.getByLabelText(/version/i)
    const previousInput = screen.getByLabelText(/previous release/i)

    // First, fetch project data
    await user.type(projectInput, 'test_project')
    await user.tab()

    await waitFor(() => {
      expect(global.fetch).toHaveBeenCalled()
    }, { timeout: 2000 })

    // Wait a bit for project data to be processed
    await new Promise(resolve => setTimeout(resolve, 100))

    // Then set version
    await user.clear(versionInput)
    await user.type(versionInput, '1.0.2')
    await user.tab()

    // Previous version should be auto-filled
    await waitFor(() => {
      const updatedInput = screen.getByLabelText(/previous release/i)
      expect(updatedInput.value).toBe('1.0.1')
    }, { timeout: 3000 })
  })

  it('submits form and fetches changelog', async () => {
    const user = userEvent.setup()
    const mockProjectData = {
      branches: [],
      tags: [{ name: '1.0.0' }, { name: '1.0.1' }]
    }
    const mockChangelog = '<h1>Release Notes</h1><p>Test changelog</p>'

    global.fetch
      .mockResolvedValueOnce({
        ok: true,
        json: async () => mockProjectData
      })
      .mockResolvedValueOnce({
        ok: true,
        text: async () => mockChangelog
      })

    render(App)
    const projectInput = screen.getByLabelText(/project/i)
    const versionInput = screen.getByLabelText(/version/i)
    const previousInput = screen.getByLabelText(/previous release/i)

    // Fill form
    await user.type(projectInput, 'test_project')
    await user.tab()

    await waitFor(() => {
      expect(global.fetch).toHaveBeenCalled()
    }, { timeout: 2000 })

    // Wait for project data processing
    await new Promise(resolve => setTimeout(resolve, 100))

    await user.type(versionInput, '1.0.1')
    await user.type(previousInput, '1.0.0')

    // Submit form
    const button = screen.getByRole('button', { name: /generate release notes/i })
    await waitFor(() => {
      expect(button).not.toBeDisabled()
    })

    // Click the button to submit
    await user.click(button)

    // Wait for the changelog fetch call
    await waitFor(() => {
      const calls = global.fetch.mock.calls
      const changelogCall = calls.find(call =>
        call[0] && call[0].includes('/changelog')
      )
      expect(changelogCall).toBeDefined()
      if (changelogCall) {
        expect(changelogCall[0]).toContain('project=test_project')
        expect(changelogCall[0]).toContain('to=1.0.1')
        expect(changelogCall[0]).toContain('from=1.0.0')
        expect(changelogCall[0]).toContain('format=html')
      }
    }, { timeout: 3000 })
  })

  it('displays loading spinner when processing', async () => {
    const user = userEvent.setup()
    const mockProjectData = {
      branches: [],
      tags: [{ name: '1.0.0' }, { name: '1.0.1' }]
    }

    global.fetch
      .mockResolvedValueOnce({
        ok: true,
        json: async () => mockProjectData
      })
      .mockImplementationOnce(() =>
        new Promise(resolve => setTimeout(() => resolve({
          ok: true,
          text: async () => 'Loading...'
        }), 200))
      )

    render(App)
    const projectInput = screen.getByLabelText(/project/i)
    const versionInput = screen.getByLabelText(/version/i)
    const previousInput = screen.getByLabelText(/previous release/i)

    await user.type(projectInput, 'test_project')
    await user.tab()

    await waitFor(() => {
      expect(global.fetch).toHaveBeenCalled()
    }, { timeout: 2000 })

    await new Promise(resolve => setTimeout(resolve, 100))

    await user.type(versionInput, '1.0.1')
    await user.type(previousInput, '1.0.0')

    const button = screen.getByRole('button', { name: /generate release notes/i })
    await waitFor(() => {
      expect(button).not.toBeDisabled()
    })
    await user.click(button)

    // Check for spinner (the SVG with animate-spin class)
    await waitFor(() => {
      const spinner = document.querySelector('.animate-spin')
      expect(spinner).toBeInTheDocument()
    }, { timeout: 2000 })
  })

  it('handles findPreviousVersion logic correctly', async () => {
    const user = userEvent.setup()
    const mockProjectData = {
      branches: [],
      tags: [{ name: '8.x-1.0.0' }, { name: '8.x-1.0.1' }, { name: '8.x-1.0.2' }, { name: '8.x-1.1.0' }]
    }

    global.fetch.mockResolvedValueOnce({
      ok: true,
      json: async () => mockProjectData
    })

    render(App)
    const projectInput = screen.getByLabelText(/project/i)
    const versionInput = screen.getByLabelText(/version/i)

    await user.type(projectInput, 'test_project')
    await user.tab()

    await waitFor(() => {
      expect(global.fetch).toHaveBeenCalled()
    }, { timeout: 2000 })

    await new Promise(resolve => setTimeout(resolve, 100))

    // Set version to 1.0.2, should auto-fill 1.0.1
    await user.clear(versionInput)
    await user.type(versionInput, '8.x-1.0.2')
    await user.tab()

    await waitFor(() => {
      const updatedInput = screen.getByLabelText(/previous release/i)
      expect(updatedInput.value).toBe('8.x-1.0.1')
    }, { timeout: 3000 })
  })
})


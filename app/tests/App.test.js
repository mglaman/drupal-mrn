import { describe, it, expect, beforeEach, vi } from 'vitest'
import { render, screen, fireEvent, waitFor } from '@testing-library/svelte'
import App from '../src/App.svelte'

const projectResponse = {
  branches: [{ name: '8.x-1.x' }, { name: '2.0.x' }],
  tags: [
    { name: '8.x-1.15' },
    { name: '8.x-1.16' },
    { name: '8.x-1.17' },
    { name: '2.0.0-beta1' },
    { name: '2.0.0' },
  ],
}

const notesHtml = '<h3>Contributors (2)</h3><p>Changes since 8.x-1.16</p>'

function mockFetch({ projectOk = true } = {}) {
  return vi.fn(async (url) => {
    if (url.includes('/project?')) {
      if (!projectOk) {
        return {
          ok: false,
          json: async () => ({ message: 'Unable to load project.' }),
        }
      }
      return { ok: true, json: async () => projectResponse }
    }
    if (url.includes('/changelog?')) {
      return { ok: true, text: async () => notesHtml }
    }
    throw new Error(`Unexpected fetch: ${url}`)
  })
}

async function loadProject(fetchMock) {
  const projectInput = screen.getByLabelText('Project')
  await fireEvent.input(projectInput, { target: { value: 'token' } })
  await fireEvent.blur(projectInput)
  await waitFor(() => {
    expect(fetchMock).toHaveBeenCalledWith(
      expect.stringContaining('/project?project=token')
    )
  })
}

describe('App', () => {
  beforeEach(() => {
    vi.unstubAllGlobals()
  })

  it('renders the form with a disabled submit button', () => {
    vi.stubGlobal('fetch', mockFetch())
    render(App)

    expect(
      screen.getByRole('heading', { name: 'Generate release notes' })
    ).toBeInTheDocument()
    expect(
      screen.getByRole('button', { name: 'Generate release notes' })
    ).toBeDisabled()
  })

  it('loads branches and tags into the version datalists on project blur', async () => {
    const fetchMock = mockFetch()
    vi.stubGlobal('fetch', fetchMock)
    const { container } = render(App)

    await loadProject(fetchMock)

    await waitFor(() => {
      const options = [...container.querySelectorAll('#ref2options option')]
      expect(options.map((o) => o.value)).toEqual([
        '2.0.0',
        '2.0.0-beta1',
        '2.0.x',
        '8.x-1.15',
        '8.x-1.16',
        '8.x-1.17',
        '8.x-1.x',
      ])
    })
  })

  it('auto-fills the previous release when a version is entered', async () => {
    const fetchMock = mockFetch()
    vi.stubGlobal('fetch', fetchMock)
    render(App)

    await loadProject(fetchMock)

    const versionInput = screen.getByLabelText('Version')
    await fireEvent.input(versionInput, { target: { value: '8.x-1.17' } })
    await fireEvent.change(versionInput)

    expect(screen.getByLabelText('Previous release')).toHaveValue('8.x-1.16')
  })

  it('warns when the previous release is newer than the version', async () => {
    const fetchMock = mockFetch()
    vi.stubGlobal('fetch', fetchMock)
    render(App)

    await loadProject(fetchMock)

    const versionInput = screen.getByLabelText('Version')
    const fromInput = screen.getByLabelText('Previous release')
    await fireEvent.input(versionInput, { target: { value: '8.x-1.16' } })
    await fireEvent.input(fromInput, { target: { value: '8.x-1.17' } })

    expect(
      await screen.findByText(/appears to be newer than the "Version"/)
    ).toBeInTheDocument()

    // Correcting the order clears the warning.
    await fireEvent.input(fromInput, { target: { value: '8.x-1.15' } })
    await waitFor(() => {
      expect(
        screen.queryByText(/appears to be newer than the "Version"/)
      ).not.toBeInTheDocument()
    })
  })

  it('shows the API error message when the project lookup fails', async () => {
    const fetchMock = mockFetch({ projectOk: false })
    vi.stubGlobal('fetch', fetchMock)
    render(App)

    const projectInput = screen.getByLabelText('Project')
    await fireEvent.input(projectInput, { target: { value: 'nope' } })
    await fireEvent.blur(projectInput)

    expect(await screen.findByText('Unable to load project.')).toBeInTheDocument()
    expect(
      screen.getByRole('button', { name: 'Generate release notes' })
    ).toBeDisabled()
  })

  it('generates release notes and renders the HTML preview', async () => {
    const fetchMock = mockFetch()
    vi.stubGlobal('fetch', fetchMock)
    render(App)

    await loadProject(fetchMock)

    const versionInput = screen.getByLabelText('Version')
    await fireEvent.input(versionInput, { target: { value: '8.x-1.17' } })
    await fireEvent.change(versionInput)

    const submit = screen.getByRole('button', { name: 'Generate release notes' })
    await waitFor(() => expect(submit).toBeEnabled())
    await fireEvent.click(submit)

    expect(await screen.findByText('Here are your release notes!')).toBeInTheDocument()
    await waitFor(() => {
      expect(fetchMock).toHaveBeenCalledWith(
        expect.stringContaining(
          '/changelog?project=token&to=8.x-1.17&from=8.x-1.16&format=html'
        )
      )
    })

    // The HTML preview is rendered by default.
    const preview = screen.getByRole('region', { name: 'Release notes preview' })
    expect(preview.innerHTML).toContain('<h3>Contributors (2)</h3>')
  })

  it('switches between preview and source views', async () => {
    const fetchMock = mockFetch()
    vi.stubGlobal('fetch', fetchMock)
    render(App)

    await loadProject(fetchMock)

    const versionInput = screen.getByLabelText('Version')
    await fireEvent.input(versionInput, { target: { value: '8.x-1.17' } })
    await fireEvent.change(versionInput)

    const submit = screen.getByRole('button', { name: 'Generate release notes' })
    await waitFor(() => expect(submit).toBeEnabled())
    await fireEvent.click(submit)

    await screen.findByText('Here are your release notes!')

    await fireEvent.click(screen.getByRole('tab', { name: 'Source' }))
    const textarea = screen.getByRole('textbox', { name: '' })
    expect(textarea).toHaveValue(notesHtml)

    await fireEvent.click(screen.getByRole('tab', { name: 'Preview' }))
    expect(
      screen.getByRole('region', { name: 'Release notes preview' })
    ).toBeInTheDocument()
  })

  it('copies the notes to the clipboard', async () => {
    const fetchMock = mockFetch()
    vi.stubGlobal('fetch', fetchMock)
    const writeText = vi.fn()
    Object.defineProperty(navigator, 'clipboard', {
      value: { writeText },
      configurable: true,
    })
    render(App)

    await loadProject(fetchMock)

    const versionInput = screen.getByLabelText('Version')
    await fireEvent.input(versionInput, { target: { value: '8.x-1.17' } })
    await fireEvent.change(versionInput)

    const submit = screen.getByRole('button', { name: 'Generate release notes' })
    await waitFor(() => expect(submit).toBeEnabled())
    await fireEvent.click(submit)
    await screen.findByText('Here are your release notes!')

    await fireEvent.click(screen.getByRole('button', { name: 'Copy' }))

    expect(writeText).toHaveBeenCalledWith(notesHtml)
    expect(await screen.findByText('Copied!')).toBeInTheDocument()
  })
})

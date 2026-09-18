<script>
  import DOMPurify from 'dompurify';
  import { onMount } from 'svelte';
  import { findPreviousVersion as findPreviousVersionInTags, isVersionNewer } from './lib/versions.js';

  const apiUrl = 'https://api.drupal-mrn.dev'
  const formats = ['html', 'markdown']
  let project = ''
  let projectData = {
    branches: [],
    tags: [],
  }
  let options = [];
  let from = ''
  let to = ''
  let format = 'html'
  let notes = ''
  let error = ''
  let notesError = ''
  let processing = false;
  let copied = false;
  let viewMode = 'preview'; // 'source' or 'preview'
  let versionWarning = false;

  function findPreviousVersion(version) {
    return findPreviousVersionInTags(version, projectData.tags);
  }

  function handleVersionChange() {
    if (to) {
      from = findPreviousVersion(to);
    }
  }

  function setFormat(newFormat) {
    if (format !== newFormat) {
      format = newFormat;
      // Notes were generated in the old format; require a regenerate.
      notes = '';
      notesError = '';
    }
  }

  // Reactive statement to check if versions are in wrong order
  $: {
    versionWarning = from && to && isVersionNewer(from, to);
  }

  async function getProject() {
    if (!project) {
      return;
    }
    processing = true;
    // Clear from/to values immediately when project changes
    from = '';
    to = '';
    try {
      const res = await fetch(`${apiUrl}/project?${new URLSearchParams({project})}`)
      if (!res.ok) {
        const data = await res.json()
        error = data.message
      } else {
        error = ''
        projectData = await res.json()
        options = [...projectData.branches, ...projectData.tags].map(obj => obj.name).sort();
      }
    } catch (e) {
      console.error(e)
      error = e.message
    } finally {
      processing = false;
    }
  }

  async function getChangeLog (event) {
    event?.preventDefault()
    processing = true;
    try {
      const res = await fetch(`${apiUrl}/changelog?${new URLSearchParams({project, to, from, format})}`)
      if (!res.ok) {
        let message = `Unable to generate release notes (HTTP ${res.status}).`
        try {
          const data = await res.json()
          message = data.message || message
        } catch {
          // The error body was not JSON; keep the generic message.
        }
        notesError = message
        notes = ''
      } else {
        notesError = ''
        notes = await res.text()
        updateUrl()
      }
    } catch (e) {
      console.error(e)
      notesError = e.message
      notes = ''
    } finally {
        processing = false;
    }
  }

  // Keep the URL shareable: it reproduces these notes when opened.
  function updateUrl() {
    const params = new URLSearchParams({project, to, from, format})
    history.replaceState(null, '', `?${params}`)
  }

  // Load ?project=token&to=8.x-1.11 links. The previous release is
  // detected when from is omitted, and the notes generate right away.
  async function loadFromUrl() {
    const params = new URLSearchParams(window.location.search)
    const initialProject = params.get('project')
    if (!initialProject) {
      return
    }
    project = initialProject
    if (formats.includes(params.get('format'))) {
      format = params.get('format')
    }
    await getProject()
    to = params.get('to') ?? ''
    if (error || !to) {
      return
    }
    from = params.get('from') || findPreviousVersion(to)
    if (from) {
      await getChangeLog()
    }
  }

  onMount(loadFromUrl)

  function copyNotes() {
    navigator.clipboard.writeText(notes);
    copied = true;
    setTimeout(() => {
      copied = false;
    }, 1000);
  }

</script>
<svelte:head>
  <!-- Fathom - beautiful, simple website analytics -->
  <script src="https://cdn.usefathom.com/script.js" data-spa="auto" data-site="IAGJCEDB" defer></script>
  <!-- / Fathom -->
</svelte:head>
<div class="py-8">
    <main class="container mx-auto max-w-(--breakpoint-md) shadow-xs bg-white rounded-lg overflow-hidden">
        <div class="p-8">
            <h1 class="text-3xl font-bold mb-1">Generate release notes</h1>
            <p class="mb-2 text-gray-700">Generates release notes for projects hosted on Drupal.org</p>
            <form class="space-y-4" on:submit={getChangeLog}>
                <div class="border border-gray-300 rounded-md px-3 py-2 shadow-xs focus-within:ring-1 focus:within:ring-drupal-navy-blue focus-within:border-drupal-navy-blue {error !== '' ? 'border-red-300 focus:within:ring-red-300 focus-within:border-red-300' : ''}">
                    <label for="project" class="block text-xs font-medium text-gray-800">Project</label>
                    <input type="text" name="project" id="project" bind:value={project}
                           on:blur={getProject}
                           class="block w-full border-0 p-0 text-gray-900 placeholder-gray-400 focus:ring-0 sm:text-sm lg:text-lg {error !== '' ? 'text-red-900 placeholder:text-red-300' : ''}"
                           placeholder="machine_name"
                           required>
                </div>
                <div class="isolate -space-x-px grid grid-cols-2 rounded-md shadow-xs">
                    <div class="relative border border-gray-300 rounded-md rounded-r-none px-3 py-2 focus-within:z-10 focus-within:ring-1 focus-within:ring-drupal-navy-blue focus-within:border-drupal-navy-blue">
                        <label class="block text-xs font-medium text-gray-900" for="ref2">Version</label>
                        <input id="ref2" list="ref2options" type="text" bind:value={to} placeholder="1.0.1"
                               on:change={handleVersionChange}
                               class="block w-full border-0 p-0 text-gray-900 placeholder-gray-400 focus:ring-0 sm:text-sm lg:text-lg"
                               required/>
                        <datalist id="ref2options">
                            {#each options as value}
                                <option value={value}></option>
                            {/each}
                        </datalist>
                    </div>
                    <div class="relative border border-gray-300 rounded-md rounded-l-none px-3 py-2 focus-within:z-10 focus-within:ring-1 focus-within:ring-drupal-navy-blue focus-within:border-drupal-navy-blue">
                        <label class="block text-xs font-medium text-gray-900" for="ref1">Previous release</label>
                        <input id="ref1" list="ref1options" type="text" bind:value={from} placeholder="1.0.0"
                               class="block w-full border-0 p-0 text-gray-900 placeholder-gray-400 focus:ring-0 sm:text-sm lg:text-lg"
                               required />
                        <datalist id="ref1options">
                            {#each options as value}
                                <option value={value}></option>
                            {/each}
                        </datalist>
                    </div>
                </div>
                {#if versionWarning}
                    <div class="rounded-md bg-yellow-50 p-4 my-4">
                        <div class="flex">
                            <div class="shrink-0">
                                <!-- Heroicon name: mini/exclamation-triangle -->
                                <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.19-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    <strong>Warning:</strong> The "Previous release" appears to be newer than the "Version". Did you get them the wrong way around?
                                </p>
                            </div>
                        </div>
                    </div>
                {/if}
                {#if error.length > 0}
                    <div class="rounded-md bg-red-50 p-4 my-4">
                        <div class="flex">
                            <div class="shrink-0">
                                <!-- Heroicon name: mini/x-circle -->
                                <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                {error}
                            </div>
                        </div>
                    </div>
                {/if}
                {#if notesError.length > 0}
                    <div class="rounded-md bg-red-50 p-4 my-4">
                        <div class="flex">
                            <div class="shrink-0">
                                <!-- Heroicon name: mini/x-circle -->
                                <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                {notesError}
                            </div>
                        </div>
                    </div>
                {/if}
                <fieldset>
                    <legend class="block text-xs font-medium text-gray-800 mb-1">Format</legend>
                    <div class="inline-flex rounded-lg border border-gray-300 p-1">
                        <button
                            type="button"
                            aria-pressed={format === 'html'}
                            on:click={() => setFormat('html')}
                            class="px-4 py-2 text-sm font-medium rounded-md transition-colors {format === 'html' ? 'bg-drupal-light-navy-blue text-white' : 'text-gray-700 hover:bg-gray-100'}"
                        >
                            HTML
                        </button>
                        <button
                            type="button"
                            aria-pressed={format === 'markdown'}
                            on:click={() => setFormat('markdown')}
                            class="px-4 py-2 text-sm font-medium rounded-md transition-colors {format === 'markdown' ? 'bg-drupal-light-navy-blue text-white' : 'text-gray-700 hover:bg-gray-100'}"
                        >
                            Markdown
                        </button>
                    </div>
                </fieldset>
                <div class="flex flex-row items-center">
                    <button
                        type="submit"
                        disabled={from === '' || to === '' || processing || error.length > 0}
                        class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-xs text-white bg-drupal-light-navy-blue disabled:bg-drupal-pale-gray disabled:text-gray-400 hover:bg-drupal-navy-blue focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-drupal-navy-blue {processing ? 'disabled:cursor-wait' : ''} {error ? 'cursor-not-allowed' : ''}">
                        Generate release notes
                    </button>
                    {#if processing}
                    <svg class="animate-spin ml-3 h-6 w-6 text-drupal-light-navy-blue" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {/if}
                </div>
            </form>
        </div>
        <div class="bg-drupal-pale-gray px-8 py-3 text-sm flex">
            <a href="https://github.com/mglaman/drupal-mrn" class="mr-2 block text-slate-400 hover:text-slate-500 dark:hover:text-slate-300"><span class="sr-only">Tailwind CSS on GitHub</span><svg viewBox="0 0 16 16" class="w-5 h-5" fill="currentColor" aria-hidden="true"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"></path></svg></a>
            <p>Created by <a href="https://mglaman.dev/" class="underline decoration-drupal-light-navy-blue text-slate-700 hover:text-slate-900">Matt Glaman</a></p>
            <div class="mx-3">|</div>
            <p>Inspired by <a href="https://www.drupal.org/project/grn"  class="underline decoration-drupal-light-navy-blue text-slate-700 hover:text-slate-900">Git Release Notes for Drush</a> </p>
        </div>
    </main>
    {#if notes.length > 0}
        <section class="container mx-auto max-w-(--breakpoint-md) mt-8 shadow-xs bg-white p-8 rounded-lg">
            <div class="mb-4 flex items-center justify-between">
                <p class="mb-0">Here are your release notes!</p>
                <div class="flex items-center gap-3">
                    {#if format === 'html'}
                        <div class="flex rounded-lg border border-gray-300 p-1" role="tablist">
                            <button
                                type="button"
                                role="tab"
                                on:click={() => viewMode = 'preview'}
                                class="px-4 py-2 text-sm font-medium rounded-md transition-colors {viewMode === 'preview' ? 'bg-drupal-light-navy-blue text-white' : 'text-gray-700 hover:bg-gray-100'}"
                            >
                                Preview
                            </button>
                            <button
                                type="button"
                                role="tab"
                                on:click={() => viewMode = 'source'}
                                class="px-4 py-2 text-sm font-medium rounded-md transition-colors {viewMode === 'source' ? 'bg-drupal-light-navy-blue text-white' : 'text-gray-700 hover:bg-gray-100'}"
                            >
                                Source
                            </button>
                        </div>
                    {/if}
                    <button
                        on:click={copyNotes}
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-xs text-white bg-drupal-light-navy-blue hover:bg-drupal-navy-blue focus:outline-hidden focus:ring-2 focus:ring-offset-2 focus:ring-drupal-navy-blue"
                    >
                        Copy
                    </button>
                    {#if copied}
                        <span class="text-sm text-gray-600 font-bold">Copied!</span>
                    {/if}
                </div>
            </div>
            {#if format === 'html' && viewMode === 'preview'}
                <div
                    role="region"
                    aria-label="Release notes preview"
                    class="prose prose-sm max-w-none border border-gray-300 rounded-md p-6 h-96 overflow-y-auto"
                    on:dblclick={() => viewMode = 'source'}
                    title="Double-click to view source"
                >
                    {@html DOMPurify.sanitize(notes)}
                </div>
            {:else}
                <textarea class="shadow-xs focus:ring-drupal-light-navy-blue focus:border-drupal-light-navy-blue block w-full h-96 sm:text-sm border-gray-300 rounded-md font-mono">{notes}</textarea>
            {/if}
        </section>
    {/if}
</div>

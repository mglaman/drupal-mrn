<script>
  const apiUrl = 'https://api.drupal-mrn.dev'
  let project = ''
  let projectData = {
    branches: [],
    tags: [],
  }
  let from = ''
  let to = ''
  let format = 'html'
  let notes = ''
  let error = ''
  let processing = false;

  async function getProject() {
    processing = true;
    try {
      const res = await fetch(`${apiUrl}/project?${new URLSearchParams({project})}`)
      projectData = await res.json()
    } catch (e) {
      console.error(e)
      error = e.message
    } finally {
      processing = false;
    }
  }

  async function getChangeLog (event) {
    event.preventDefault()
    processing = true;
    try {
      const res = await fetch(`${apiUrl}/changelog?${new URLSearchParams({project, to, from, format})}`)
      notes = await res.text()
    } catch (e) {
      console.error(e)
      error = e.message
    } finally {
        processing = false;
    }
  }

</script>
<div class="py-8">
    <main class="container mx-auto max-w-screen-md shadow-sm bg-white rounded-lg overflow-hidden">
        <div class="p-8">
            <h1 class="text-3xl">Generate release notes</h1>
            <p class="mb-2">Generates release notes for projects hosted on Drupal.org</p>

            {#if error.length > 0}
                <div class="rounded-md bg-red-50 p-4 my-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
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
            <form class="space-y-4" on:submit={getChangeLog}>
                <div class="border border-gray-300 rounded-md px-3 py-2 shadow-sm focus-within:ring-1 focus:within:ring-drupal-navy-blue focus-within:border-drupal-navy-blue">
                    <label for="project" class="block text-xs font-medium text-gray-800">Project</label>
                    <input type="text" name="project" autocomplete="off" id="project" bind:value={project}
                           on:blur={getProject}
                           class="block w-full border-0 p-0 text-gray-900 placeholder-gray-400 focus:ring-0 sm:text-sm lg:text-lg"
                           placeholder="machine_name"
                           required>
                </div>
                <div class="isolate -space-x-px grid grid-cols-2 rounded-md shadow-sm">
                    <div class="relative border border-gray-300 rounded-md rounded-r-none px-3 py-2 focus-within:z-10 focus-within:ring-1 focus-within:ring-drupal-navy-blue focus-within:border-drupal-navy-blue">
                        <label class="block text-xs font-medium text-gray-900" for="ref1">From</label>
                        <input id="ref1" type="text" bind:value={from} placeholder="1.0.0"
                               class="block w-full border-0 p-0 text-gray-900 placeholder-gray-400 focus:ring-0 sm:text-sm lg:text-lg"
                               required/>
                    </div>
                    <div class="relative border border-gray-300 rounded-md rounded-l-none px-3 py-2 focus-within:z-10 focus-within:ring-1 focus-within:ring-drupal-navy-blue focus-within:border-drupal-navy-blue">
                        <label class="block text-xs font-medium text-gray-900" for="ref2">To</label>
                        <input id="ref2" type="text" bind:value={to} placeholder="1.0.1"
                               class="block w-full border-0 p-0 text-gray-900 placeholder-gray-400 focus:ring-0 sm:text-sm lg:text-lg"
                               required/>
                    </div>
                </div>
                <div class="flex flex-row items-center">
                    <button
                        type="submit"
                        disabled={processing}
                        class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-drupal-light-navy-blue hover:bg-drupal-navy-blue focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-drupal-navy-blue disabled:cursor-wait">
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
        </div>
    </main>
    {#if notes.length > 0}
        <section class="container mx-auto max-w-screen-md mt-8 shadow-sm bg-white p-8 rounded-lg">
            <p class="mb-2">Here are you release notes!</p>
            <textarea class="shadow-sm focus:ring-drupal-light-navy-blue focus:border-drupal-light-navy-blue block w-full h-96 sm:text-sm border-gray-300 rounded-md">
                {notes.trim()}
            </textarea>
        </section>
    {/if}
</div>

<script>
  const apiUrl = 'https://qzr5qeis20.execute-api.us-east-1.amazonaws.com/'
  let project = ''
  let from = ''
  let to = ''
  let format = 'html'
  let notes = ''
  let error = ''

  async function getChangeLog () {
    try {
      const res = await fetch(`${apiUrl}?${new URLSearchParams({project, to, from, format})}`)
      notes = await res.text()
    } catch (e) {
      console.error(e)
      error = e.message
    }
  }

</script>
<div class="">
    <main class="container mx-auto max-w-screen-md mt-8 shadow-sm bg-white p-8 rounded-lg">
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
        <div class="space-y-4">
            <div class="border border-gray-300 rounded-md px-3 py-2 shadow-sm focus-within:ring-1 focus:within:ring-drupal-navy-blue focus-within:border-drupal-navy-blue">
                <label for="project" class="block text-xs font-medium text-gray-800">Project</label>
                <input type="text" name="project" autocomplete="off" id="project" bind:value={project}
                       class="block w-full border-0 p-0 text-gray-900 placeholder-gray-400 focus:ring-0 sm:text-sm lg:text-lg"
                       placeholder="machine_name">
            </div>
            <div class="isolate -space-x-px grid grid-cols-2 rounded-md shadow-sm">
                <div class="relative border border-gray-300 rounded-md rounded-r-none px-3 py-2 focus-within:z-10 focus-within:ring-1 focus-within:ring-drupal-navy-blue focus-within:border-drupal-navy-blue">
                    <label class="block text-xs font-medium text-gray-900" for="ref1">From</label>
                    <input id="ref1" type="text" bind:value={from} placeholder="1.0.0"
                           class="block w-full border-0 p-0 text-gray-900 placeholder-gray-400 focus:ring-0 sm:text-sm lg:text-lg"/>
                </div>
                <div class="relative border border-gray-300 rounded-md rounded-l-none px-3 py-2 focus-within:z-10 focus-within:ring-1 focus-within:ring-drupal-navy-blue focus-within:border-drupal-navy-blue">
                    <label class="block text-xs font-medium text-gray-900" for="ref2">To</label>
                    <input id="ref2" type="text" bind:value={to} placeholder="1.0.1"
                           class="block w-full border-0 p-0 text-gray-900 placeholder-gray-400 focus:ring-0 sm:text-sm lg:text-lg"/>
                </div>
            </div>
            <button
                    type="button"
                    disabled={project.length === 0 && from.length === 0} on:click={getChangeLog}
                    class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-drupal-light-navy-blue hover:bg-drupal-navy-blue focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-drupal-navy-blue disabled:bg-drupal-pale-gray disabled:text-gray-700">
                Generate release notes
            </button>
        </div>
    </main>
    {#if notes.length > 0}
        <section class="container mx-auto max-w-screen-md mt-8 shadow-sm bg-white p-8 rounded-lg">
            {notes}
        </section>
    {/if}
</div>

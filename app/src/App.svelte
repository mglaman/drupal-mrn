<script>
  const apiUrl = 'https://qzr5qeis20.execute-api.us-east-1.amazonaws.com/';
  let project = '';
  let from = '';
  let to = '';
  let format = 'html'
  let notes = '';

  async function getChangeLog() {
    const res = await fetch(`${apiUrl}?${new URLSearchParams({project, to, from, format})}`)
    notes = await res.text()
  }

</script>
<div class="">
  <main class="container mx-auto max-w-screen-md mt-8 shadow-sm bg-white p-8 rounded-lg">
    <h1 class="text-3xl">Generate release notes</h1>
    <p>Generates release notes for projects hosted on Drupal.org</p>

    <div class="space-y-6 mt-8">
      <div class="flex flex-col">
        <label class="text-sm" for="project">Project</label>
        <input id="project" type="text" bind:value={project} placeholder="Project machine name"/>
      </div>
      <div class="grid grid-cols-2">
        <div class="flex flex-col">
          <label class="text-sm" for="ref1">From</label>
          <input id="ref1" type="text" bind:value={from} placeholder="1.0.0"/>
        </div>
        <div class="flex flex-col">
          <label class="text-sm" for="ref2">To</label>
          <input id="ref2" type="text" bind:value={to} placeholder="1.0.1"/>
        </div>
      </div>
      <button disabled={project.length === 0 && from.length === 0} on:click={getChangeLog} class="text-white bg-drupal-light-navy-blue px-2 py-1 rounded-md hover:bg-drupal-navy-blue">Generate!</button>
    </div>
  </main>
  {#if notes.length > 0}
  <section class="container mx-auto max-w-screen-md mt-8 shadow-sm bg-white p-8 rounded-lg">
    {notes}
  </section>
  {/if}
</div>

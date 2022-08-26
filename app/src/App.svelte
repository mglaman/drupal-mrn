<script>
  let project = '';
  let ref1 = '';
  let ref2 = 'HEAD';
  let notes = '';

  async function getChanges() {
    const projectParam = encodeURIComponent(`project/${project}`)
    const url = `https://git.drupalcode.org/api/v4/projects/${projectParam}/repository/compare?from=${ref1}&to=${ref2}`
    const compareResponse = await fetch(url);
    const compareJson = await compareResponse.json();

    const commits = compareJson.commits;
    console.log(commits);
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
          <input id="ref1" type="text" bind:value={ref1} placeholder="1.0.0"/>
        </div>
        <div class="flex flex-col">
          <label class="text-sm" for="ref2">To</label>
          <input id="ref2" type="text" bind:value={ref2} placeholder="1.0.1"/>
        </div>
      </div>
      <button disabled={project.length === 0 && ref1.length === 0} on:click={getChanges} class="text-white bg-drupal-light-navy-blue px-2 py-1 rounded-md hover:bg-drupal-navy-blue">Generate!</button>
    </div>
  </main>
  {#if notes.length > 0}
  <section class="container mx-auto max-w-screen-md mt-8 shadow-sm bg-white p-8 rounded-lg">
    {notes}
  </section>
  {/if}
</div>

import './app.css'
import App from './App.svelte'

import * as Sentry from "@sentry/svelte";

Sentry.init({
  dsn: "https://ec3b995c19739bbb1a00f14d0ef4c723@o4505060230627328.ingest.us.sentry.io/4507170537340928",
  integrations: [],
});

const app = new App({
  target: document.getElementById('app')
})

export default app

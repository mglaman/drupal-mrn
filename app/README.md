# drupal-mrn.dev frontend

Svelte 5 single-page app for [drupal-mrn.dev](https://drupal-mrn.dev/). It
collects a project machine name and two release tags, then requests generated
release notes from the API at `api.drupal-mrn.dev` in HTML or Markdown.

Built with Vite and Tailwind CSS. The HTML preview is sanitized with DOMPurify
before rendering.

## Development

Requires the Node version in `.nvmrc`.

```bash
nvm use
npm ci
npm run dev
```

## Tests

Vitest with @testing-library/svelte. Version-comparison logic lives in
`src/lib/versions.js`; component tests mock `fetch` and cover the full
form-to-notes flow, including sanitization and error handling.

```bash
npm test        # single run, used by CI
npm run test:watch
```

## Deployment

Pushes to `main` that touch `app/**` trigger `.github/workflows/app_deploy.yml`:
tests run, `vite build` produces `dist/`, hashed assets sync to S3 with
immutable cache headers, and CloudFront is invalidated. There is no manual
deploy step.

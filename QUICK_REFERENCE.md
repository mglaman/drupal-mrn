# Quick Reference Guide

## Common Commands

### Backend
```bash
cd api
composer install              # Install PHP dependencies
vendor/bin/phpunit            # Run tests
npm run deploy                # Deploy to AWS Lambda
```

### Frontend
```bash
cd app
npm install                   # Install dependencies
npm run dev                   # Start dev server
npm run build                 # Build for production
```

## API Endpoints

### GET /changelog
Generate release notes between versions.

**Parameters:**
- `project` (required): Drupal.org project machine name
- `from` (required): Starting version/tag/branch
- `to` (optional): Ending version/tag/branch (default: HEAD)
- `format` (optional): Output format - `html`, `markdown`, `json` (default: html)

**Example:**
```
GET /changelog?project=token&from=8.x-1.10&to=8.x-1.11&format=markdown
```

### GET /project
Get project metadata (tags and branches).

**Parameters:**
- `project` (required): Drupal.org project machine name

**Example:**
```
GET /project?project=token
```

## Key File Locations

- **Lambda Handlers**: `api/changelog.php`, `api/project.php`
- **Core Logic**: `api/src/Changelog.php`, `api/src/GitLab.php`, `api/src/CommitParser.php`
- **Output Formats**: `api/src/FormatOutput/*.php`
- **Frontend**: `app/src/App.svelte`
- **Tests**: `api/tests/src/*.php`
- **Config**: `api/serverless.yml`, `app/vite.config.js`

## External APIs

- **GitLab**: `https://git.drupalcode.org/api/v4`
- **Drupal.org API**: `https://www.drupal.org/api-d7/node/{nid}.json`

## Version Formats

Drupal projects use various version formats:
- `8.x-1.11` (Drupal 8 with major.minor)
- `1.0.1` (semantic versioning)
- `1.0.0-alpha1` (pre-release versions)

Frontend automatically detects previous versions using sophisticated sorting.

## Issue Categories

- 0 = Misc
- 1 = Bug
- 2 = Task
- 3 = Feature
- 4 = Support
- 5 = Plan

## Contributor Attribution Patterns

The system extracts contributors from:
1. Commit title: `Issue #123 by user1, user2: Description`
2. Commit message trailers:
   - `By: username`
   - `Authored-by: username`
   - `Co-authored-by: username`
3. GitLab user lookup (author/committer names)
4. Email parsing: `username@users.noreply.drupalcode.org`

## Deployment

- **Automatic**: Push to `main` branch triggers GitHub Actions
- **Manual Backend**: `cd api && npm run deploy`
- **Manual Frontend**: Build then upload `app/dist/` to S3

## Testing

```bash
# Backend tests
cd api && vendor/bin/phpunit

# Frontend build test
cd app && npm run build
```

## Troubleshooting

- **Lambda timeout**: 28 second limit, check for slow GitLab/Drupal.org API calls
- **CORS errors**: Backend has CORS enabled, check API URL in frontend
- **Missing contributors**: User may not exist in GitLab, falls back to email parsing
- **Build failures**: Check Node.js version (requires 20), PHP version (requires 8.3)



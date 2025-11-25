# Drupal MRN - Agent Context Guide

**Drupal Maintainer Release Notes (MRN)** is a web application for generating release notes for projects hosted on Drupal.org. It extracts contributor information, groups issues by type, and automatically detects previous release versions.

## Project Overview

- **Purpose**: Generate formatted release notes for Drupal.org projects
- **Architecture**: Serverless PHP backend (AWS Lambda via Bref) + Svelte frontend
- **Deployment**: AWS Lambda (PHP) + Static frontend hosting

## Project Structure

```
drupal-mrn/
├── api/                 # PHP Lambda backend
│   ├── src/            # Core application logic
│   ├── tests/          # PHPUnit tests
│   ├── changelog.php   # Lambda handler for changelog endpoint
│   ├── project.php     # Lambda handler for project metadata endpoint
│   └── serverless.yml  # Serverless Framework configuration
└── app/                # Svelte frontend
    ├── src/            # Svelte components and styles
    └── dist/           # Built static assets (deployed)
```

## Architecture

### Backend (API)

**Technology Stack:**
- PHP 8.3 (via Bref runtime)
- Serverless Framework
- AWS Lambda + API Gateway
- Guzzle HTTP client
- Symfony HTTP Foundation

**Lambda Functions:**
1. `changelog` - `/changelog` endpoint (GET)
   - Generates release notes between two versions
   - Parameters: `project`, `from`, `to`, `format` (html|markdown|json)
   - Returns formatted release notes

2. `project` - `/project` endpoint (GET)
   - Returns project metadata (tags, branches)
   - Parameters: `project`
   - Used by frontend to populate version dropdowns

**Key Classes:**

- `App\GitLab` - Interacts with Drupal.org's GitLab instance (git.drupalcode.org)
  - Methods: `compare()`, `tags()`, `branches()`, `users()`, `project()`, `search()`
  - All endpoints use the GitLab API v4

- `App\Changelog` - Core changelog generation logic
  - Processes commits between versions
  - Extracts issue information from Drupal.org API
  - Groups changes by category (Bug, Feature, Task, Support, Plan, Misc)
  - Collects contributor information

- `App\CommitParser` - Parses commit messages
  - `extractUsernames()` - Extracts usernames from commit titles/messages
    - Supports classic "by user1, user2:" format
    - Supports "By:", "Authored-by:", "Co-authored-by:" trailers
    - Handles @ prefixes in usernames
  - `getNid()` - Extracts Drupal.org issue ID from commit title
    - Looks for "#12345" format
    - Falls back to 4+ digit numbers

- `App\FormatOutput\*` - Output formatters
  - `HtmlFormatOutput` - HTML format (default)
  - `MarkdownFormatOutput` - Markdown format
  - `JsonFormatOutput` - JSON format
  - All implement `FormatOutputInterface`

- `App\Formatter` - Utility for formatting contributor links
- `App\TextBuffer` - Utility for building text output

### Frontend (App)

**Technology Stack:**
- Svelte 5
- Vite
- Tailwind CSS
- Sentry (error tracking)
- Fathom Analytics

**Key Features:**
- Project input with autocomplete
- Version selection (tags/branches dropdown)
- Automatic previous version detection
- Format selection (HTML/Markdown)
- Copy to clipboard functionality

**API Integration:**
- API URL: `https://api.drupal-mrn.dev` (configurable in App.svelte)
- CORS enabled from backend

## External APIs

### GitLab API (Drupal.org)
- **Base URL**: `https://git.drupalcode.org/api/v4`
- **Endpoints Used**:
  - `/projects/{project}/repository/compare?from={from}&to={to}`
  - `/projects/{project}/repository/tags`
  - `/projects/{project}/repository/branches`
  - `/users?search={name}`
  - `/projects/{project}`

### Drupal.org API
- **Node API**: `https://www.drupal.org/api-d7/node/{nid}.json`
  - Used to fetch issue metadata (category, title, etc.)
- **Issue Categories**:
  - 0 = Misc
  - 1 = Bug
  - 2 = Task
  - 3 = Feature
  - 4 = Support
  - 5 = Plan

## Data Flow

1. **Frontend**: User selects project → calls `/project` endpoint
2. **Backend**: Fetches tags/branches from GitLab → returns to frontend
3. **Frontend**: User selects versions → calls `/changelog` endpoint
4. **Backend**:
   - Calls GitLab compare API to get commits
   - For each commit:
     - Extracts issue ID (NID) from commit title
     - Fetches issue metadata from Drupal.org API
     - Extracts contributors from commit message
     - Looks up user info in GitLab
   - Groups changes by issue type
   - Formats output (HTML/Markdown/JSON)
   - Returns formatted release notes

## Development Setup

### Backend (API)

```bash
cd api
composer install
vendor/bin/phpunit  # Run tests
```

**Local Development:**
- Uses Bref Local for testing Lambda functions locally
- Serverless Framework for deployment

**Testing:**
- PHPUnit 11
- Tests located in `tests/src/`
- Test fixtures in `tests/fixtures/`

### Frontend (App)

```bash
cd app
npm install
npm run dev      # Development server
npm run build    # Production build
```

**Build Output:**
- Built files go to `app/dist/`
- Static assets suitable for deployment to CDN/S3

## Deployment

### CI/CD Pipeline

**GitHub Actions Workflows:**

1. **API Deployment** (`.github/workflows/api_deploy.yml`)
   - Triggers: Push to `main` branch, changes in `api/**`
   - Steps:
     - Installs Node.js 20 and PHP 8.3
     - Runs `npm install` in `api/`
     - Runs `composer install`
     - Runs PHPUnit tests
     - Deploys via `npm run deploy` (runs `serverless deploy`)
   - Runs tests before deployment
   - Uses concurrency group: `api_build`

2. **Frontend Deployment** (`.github/workflows/app_deploy.yml`)
   - Triggers: Push to `main` branch, changes in `app/**`
   - Steps:
     - Installs Node.js 20
     - Runs `npm install` and `npm run build` in `app/`
     - Syncs `app/dist/` to S3 bucket: `s3://drupal-mrn-web`
     - Invalidates CloudFront distribution: `E2CL99C9FHPLRG`
   - Uses concurrency group: `app_build`

3. **PR Testing**
   - `api_pr.yml`: Runs PHPUnit tests on API changes
   - `app_pr.yml`: Builds frontend to verify it compiles

**Required Secrets:**
- `AWS_ACCESS_KEY_ID`
- `AWS_SECRET_ACCESS_KEY`

### Backend Deployment

- Deployed via Serverless Framework (`npm run deploy` in `api/`)
- AWS Lambda runtime: `php-83-fpm`
- Timeout: 28 seconds (API Gateway limit is 29s)
- Region: `us-east-1`
- CORS enabled
- Deployment command: `serverless deploy` (defined in `api/package.json`)

### Frontend Deployment

- Static files built to `app/dist/`
- Deployed to S3 bucket: `s3://drupal-mrn-web`
- Served via CloudFront distribution: `E2CL99C9FHPLRG`
- Production API URL: `https://api.drupal-mrn.dev` (configured in `App.svelte`)

## Configuration

### Environment Variables
- **Backend**: Sentry DSN configured in `project.php` (hardcoded)
- **Frontend**: API URL configured in `App.svelte` (`https://api.drupal-mrn.dev`)
- **CI/CD**: AWS credentials via GitHub Secrets

### AWS Resources
- **S3 Bucket**: `drupal-mrn-web` (frontend static hosting)
- **CloudFront Distribution**: `E2CL99C9FHPLRG` (CDN for frontend)
- **Lambda Functions**: Managed by Serverless Framework
- **API Gateway**: Created automatically by Serverless Framework

### Caching
- Project metadata: 600 seconds (10 minutes)
- Changelog: 86400 seconds (24 hours)
- Uses ETag and Last-Modified headers

## Development Workflow

### Making Changes

1. **Backend Changes:**
   - Edit files in `api/src/`
   - Run tests: `cd api && vendor/bin/phpunit`
   - Push to `main` triggers automatic deployment (if tests pass)

2. **Frontend Changes:**
   - Edit files in `app/src/`
   - Test locally: `cd app && npm run dev`
   - Build: `cd app && npm run build`
   - Push to `main` triggers automatic deployment

### Branching Strategy

- `main` branch is production
- PRs trigger test workflows
- Merges to `main` trigger deployment workflows
- Uses path-based triggers (only deploys if relevant files change)

### Local Testing

**Backend:**
```bash
cd api
composer install
vendor/bin/phpunit
# Use Bref Local for Lambda testing (if configured)
```

**Frontend:**
```bash
cd app
npm install
npm run dev  # Starts local dev server
# API URL is hardcoded in App.svelte, may need to change for local testing
```

## Important Patterns & Conventions

### Commit Message Parsing
- Supports multiple formats for contributor attribution
- Handles Drupal.org-specific email format: `{username}@users.noreply.drupalcode.org`
- Regex patterns for username extraction are critical

### Issue ID Extraction
- Primary pattern: `#12345` in commit title
- Fallback: 4+ digit numbers (for commits missing #)

### Version Format
- Drupal projects use formats like: `8.x-1.11`, `1.0.1`, etc.
- Frontend handles version sorting (including pre-release versions: alpha, beta, rc)

### Error Handling
- Backend returns JSON error responses with appropriate HTTP status codes
- Frontend displays errors in UI
- Sentry integration for error tracking (backend)

## Testing

### Backend Tests
- Comprehensive test coverage for `CommitParser`
- Tests for format output classes
- Fixtures include real GitLab API responses

### Test Commands
```bash
cd api
vendor/bin/phpunit
```

## Common Tasks

### Adding a New Output Format
1. Create new class in `api/src/FormatOutput/`
2. Implement `FormatOutputInterface`
3. Add format to `FormatOutputFactory::getFormatOutput()`
4. Add tests

### Modifying Commit Parsing
1. Update `CommitParser` class
2. Add/update tests in `CommitParserTest.php`
3. Ensure backward compatibility with existing commit formats

### Updating Dependencies
- Backend: Update `composer.json`, run `composer update`
- Frontend: Update `package.json`, run `npm update`

## Known Limitations

- API Gateway has a 29-second timeout limit
- GitLab API rate limits may affect large projects
- Contributor lookup may fail for users not found in GitLab
- Requires projects to be on Drupal.org's GitLab instance

## Future Improvements (TODOs)

- [ ] Local development setup documentation (mentioned in README)
- [ ] Environment variable management for Sentry DSN
- [ ] Better error handling for rate limits
- [ ] Caching layer for issue metadata

## Related Projects

- Inspired by [Git Release Notes for Drush (grn)](https://www.drupal.org/project/grn)
- Uses [Bref](https://bref.sh/) for PHP on Lambda

## Author

Matt Glaman (nmd.matt@gmail.com)


# API Development & Testing

This directory contains the PHP API endpoints for the project.

## Running Locally

You can run the API locally using PHP's built-in web server.

```bash
php -d max_execution_time=28 -S localhost:8000
```

> **Note:** The `-d max_execution_time=30` flag sets a 30-second timeout to simulate the AWS Lambda environment.
```

## Testing Endpoints

Once the server is running, you can test the endpoints using your browser or `curl`.

### `changelog.php`

Generates a changelog for a specific project between two git references.

**Parameters:**
- `project` (required): The machine name of the project (e.g., `views_remote_data`).
- `from` (optional): The starting git reference (tag or commit). Defaults to empty (beginning of time).
- `to` (optional): The ending git reference (tag or commit). Defaults to `HEAD`.
- `format` (optional): The output format (`html`, `json`, `markdown`). Defaults to `html`.

**Examples:**

Get HTML changelog for `views_remote_data`:
```
http://localhost:8000/changelog.php?project=views_remote_data&from=1.0.0
```

Get JSON output:
```
http://localhost:8000/changelog.php?project=views_remote_data&from=1.0.0&format=json
```

### `project.php`

Retrieves available tags and branches for a project from GitLab.

**Parameters:**
- `project` (required): The machine name of the project.

**Example:**

Get tags and branches for `views_remote_data`:
```
http://localhost:8000/project.php?project=views_remote_data
```
